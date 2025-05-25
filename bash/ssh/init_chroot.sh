#!/bin/bash
# init_chroot.sh - Скрипт инициализации chroot-окружения пользователя
# Расположение: bash/ssh/init_chroot.sh

set -euo pipefail

# Подключение логального config.sh
source "$(dirname "${BASH_SOURCE[0]}")/config.sh" || {
    echo "Failed to source local config.sh" >&2
    exit 1
}

# Подключение скрипта удаления chroot-окружения
source_script "$REMOVE_CHROOT_FN" || exit $?

# Подключение скрипта с функциями монтирования
source_script "$MOUNTS_FN" || exit $?

# Подключение скрипта настройки chroot
source "$SETUP_CHROOT" || {
    echo "Failed to source script '$SETUP_CHROOT'" >&2
    exit "$EXIT_GENERAL_ERROR"
}

# Очистка
cleanup() {
    local exit_code=$?
    [[ $exit_code -eq 0 ]] && return
    # trap - EXIT
    with_lock "${TMP_DIR}/${LOCK_SSH_PREF}_chroot_$USERNAME.lock" remove_chroot "$USERNAME" || {
        log_message "error" "Failed to rollback chroot for $USERNAME"
    }
}

mount_workspace() {
    mount_bind "$USER_WORKSPACE" "$CHROOT_WORKSPACE"
    return $?
}

# Монтирование директории /dev
# Usage: mount_dev <where>
mount_dev() {
    local dest="$1"

    mount_devtmpfs "$dest" || return $?
    local unit_devtmpfs="$(title_mount_unit "$dest")"

    create_directories "$dest/pts" 755 root:root || return $?
    mount_devpts "$dest/pts" "$unit_devtmpfs" || return $?

    return "$EXIT_SUCCESS"
}

# Синхронизация файлов /etc
setup_etc() {
    local target_etc="$1/etc"

    [[ ! -d "${target_etc}" ]] && { log_message "error" "Directory '${target_etc}' does not exist"; return "$EXIT_NOT_FOUND"; }

    { getent passwd "$USERNAME" root > "${target_etc}/passwd" && update_permissions "${target_etc}/passwd" 644 root:root; } || { log_message "error" "Failed to create or write '${target_etc}/passwd'"; return "$EXIT_CHROOT_INIT_FAILED"; }
    { getent group "${STUDENT_GROUP}" root > "${target_etc}/group" && update_permissions "${target_etc}/passwd" 644 root:root; } || { log_message "error" "Failed to create or write '${target_etc}/group'"; return "$EXIT_CHROOT_INIT_FAILED"; }
    { getent shadow "$USERNAME" root > "${target_etc}/shadow" && update_permissions "${target_etc}/passwd" 640 root:root; } || { log_message "error" "Failed to create or write '${target_etc}/shadow'"; return "$EXIT_CHROOT_INIT_FAILED"; }

    local src dest
    for src in "${!NEEDED_ETC_FILES[@]}"; do
        dest="${target_etc}/${NEEDED_ETC_FILES[$src]}"
        rm "${dest}" > /dev/null 2>&1
        ln -s "${src}" "${dest}" || { log_message "error" "Failed to create a symlink '${src}' to '${dest}'"; return "$EXIT_CHROOT_INIT_FAILED"; }
        update_permissions "${dest}" 644 root:root || return $?
    done

    return "$EXIT_SUCCESS"
}

setup_logging () {
    touch "$CHROOT_USER_LOG" || { log_message "error" "Failed to create logging file '$CHROOT_USER_LOG'"; return "$EXIT_CHROOT_INIT_FAILED"; }
    update_permissions "$CHROOT_USER_LOG" 755 "$USERNAME:$STUDENT_GROUP" || return $?
    return "$EXIT_SUCCESS"
}

# Ограничение прав доступа к командам
restrict_binaries() {
    local root="$1"

    local cmd path
    for cmd in "${RESTRICTED_CMDS[@]}"; do
        for path in "$root/bin/$cmd" "$root/usr/bin/$cmd"; do
            if [[ -f "$path" ]]; then
                chmod 000 "$path" || {
                    log_message "error" "Failed to restrict '$path'"
                    return "$EXIT_CHROOT_INIT_FAILED"
                }
            fi
        done
    done
    return "$EXIT_SUCCESS"
}

# Основная логика
trap cleanup SIGINT SIGTERM EXIT

# Проверка аргументов
[[ ${#ARGS[@]} -ge 1 ]] || { echo "Usage: $0 <username>"; exit "$EXIT_INVALID_ARG"; }

# Установка переменных
declare -r USERNAME="${ARGS[0]}"
declare -r USER_WORKSPACE="$STUDENTS_DIR/$USERNAME"
declare -r USER_CHROOT="$CHROOT_STUDENTS/$USERNAME"
declare -r OVERLAY_UPPER="$USER_CHROOT/upper"
declare -r OVERLAY_WORK="$USER_CHROOT/work"
declare -r CHROOT_ROOT="$USER_CHROOT/root"
declare -r CHROOT_HOME="$CHROOT_ROOT/home/$USERNAME"
declare -r CHROOT_WORKSPACE="$CHROOT_HOME/$USERNAME"
declare -r CHROOT_USER_LOG="${CHROOT_ROOT}${LOG_USER_ACTIVE}"
declare -ar RESTRICTED_CMDS=("sudo" "su" "cron" "sshfs")
declare -Ar NEEDED_ETC_FILES=(
    ["${TEMPLATE_PROFILE}"]="bash_profile"
)

# Проверка пользователя
[[ "$USERNAME" =~ ^[a-zA-Z0-9._-]+$ ]] || {
    log_message "error" "Invalid USERNAME: $USERNAME"
    exit ${EXIT_INVALID_ARG}
}
id "$USERNAME" &>/dev/null || {
    log_message "error" "User '$USERNAME' does not exist"
    exit "$EXIT_INVALID_ARG"
}
groups "$USERNAME" | grep -q "$STUDENT_GROUP" || {
    log_message "error" "User '$USERNAME' is not a member of the '$STUDENT_GROUP' group"
    exit "$EXIT_INVALID_ARG"
}

log_message "info" "Starting chroot initialization for '$USERNAME'"

# Функция инициализации chroot-окружения
init_chroot() {
    [[ -d "$USER_CHROOT" ]] && {
        log_message "info" "Chroot directory '$USER_CHROOT' already exists, removing"
        remove_chroot "$USERNAME" || return $?
    }

    # Создани директории chroot-окружения
    with_lock "$LOCK_CHROOT_STUDENTS_FILE" create_directories "$USER_CHROOT" 755 root:root || return $?

    # Создание стуктуры chroot-окружения
    create_directories "$CHROOT_ROOT" "$OVERLAY_UPPER" "$OVERLAY_WORK" 755 root:root || {
        log_message "error" "Failed to create overlay directories in '$USER_CHROOT'"
        return "$EXIT_CHROOT_INIT_FAILED"
    }

    # Создание файла логирования действий пользователя
    setup_logging || return $?

    # Создание системных директорий для overlay
    create_directories "${SYSTEM_DIRS[@]/#/$OVERLAY_UPPER}" "${SYSTEM_DIRS[@]/#/$OVERLAY_WORK}" "${SYSTEM_DIRS[@]/#/$CHROOT_ROOT}" 700 root:root || {
        log_message "error" "Failed to create system overlay directories in '$USER_CHROOT'"
        return "$EXIT_CHROOT_INIT_FAILED"
    }

    # Настройка директорий etc
    setup_etc "$OVERLAY_UPPER" || return $?
    
    # Overlay монтирвоание системных директорий
    local path
    for path in "${SYSTEM_DIRS[@]}"; do
        mount_overlay "$path" "${OVERLAY_UPPER}${path}" "${OVERLAY_WORK}${path}" "${CHROOT_ROOT}${path}" || return $?
    done

    # local unit_overlay="$(title_mount_unit "$CHROOT_ROOT")"

    # Настройка директорий root
    { 
        create_directories "$CHROOT_HOME" 755 "$USERNAME:$STUDENT_GROUP" && create_directories "$CHROOT_WORKSPACE" "$CHROOT_ROOT/dev" "$CHROOT_ROOT/proc" "$CHROOT_ROOT/sys" "$CHROOT_ROOT/run" 755 root:root
    } || {
        log_message "error" "Failed to create overlay directories in '$CHROOT_ROOT'"
        return "$EXIT_CHROOT_INIT_FAILED"
    }

    mount_workspace || return $?
    mount_dev "$CHROOT_ROOT/dev" || return $?
    mount_proc "$CHROOT_ROOT/proc" || return $?
    mount_sys "$CHROOT_ROOT/sys" || return $?
    mount_sys "$CHROOT_ROOT/run" || return $?
    
    # Блокировка команд
    restrict_binaries "${CHROOT_ROOT}" || return $?
    
    log_message "info" "Chroot for '$USERNAME' initialized successfully"

    return "$EXIT_SUCCESS"
}

# Инициализация chroot-окружения с блокировкой
with_lock "${TMP_DIR}/${LOCK_SSH_PREF}_chroot_$USERNAME.lock" init_chroot || exit $?

exit $EXIT_SUCCESS