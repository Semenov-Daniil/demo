#!/bin/bash
# init_chroot.sh - Скрипт инициализации chroot-окружения пользователя
# Расположение: bash/chroot/init_chroot.sh

set -euo pipefail

# Подключение логального config.sh
source "$(dirname "${BASH_SOURCE[0]}")/config.sh" || {
    echo "Failed to source local config.sh" >&2
    exit 1
}

# Подключение скрипта удаления chroot-окружения
source "$REMOVE_CHROOT_FN" || { log_message "error" "Failed to source '$REMOVE_CHROOT_FN'" >&2; return "$EXIT_GENERAL_ERROR"; }

# Очистка
cleanup() {
    local exit_code=$?
    [[ $exit_code -eq 0 ]] && return
    remove_chroot
}

# Монтирование директории /dev
mount_dev() {
    local dest="$1"

    mount_devtmpfs "$dest" || return $?

    local unit_dev="$(title_mount_unit "$dest")"

    create_directories "$dest/pts" "$dest/shm" 755 root:root || return $?

    mount_devpts "$dest/pts" "$unit_dev" || return $?
    mount_tmpfs "$dest/shm" "mode=1777" "$unit_dev" || return $?

    return "$EXIT_SUCCESS"
}

# Ограничение прав доступа к командам


# Основная логика
trap cleanup SIGINT SIGTERM EXIT

# Установка переменных
declare -r CHROOT_OVERLAY="$BASE_CHROOT/overlay"
declare -r OVERLAY_UPPER="$CHROOT_OVERLAY/upper"
declare -r OVERLAY_WORK="$CHROOT_OVERLAY/work"

log_message "info" "Starting chroot initialization '$BASE_CHROOT'"

# Функция инициализации chroot-окружения
init_chroot() {
    [[ -d "$BASE_CHROOT" ]] && {
        remove_chroot || return $?
    }

    create_directories "$BASE_CHROOT" 755 root:root || return $?

    create_directories "$CHROOT_ROOT" "$OVERLAY_UPPER" "$OVERLAY_WORK" 755 root:root || {
        log_message "error" "Failed to create overlay directories in '$BASE_CHROOT'"
        return "$EXIT_CHROOT_INIT_FAILED"
    }

    create_directories "${SYSTEM_DIRS[@]/#/$OVERLAY_UPPER}" "${SYSTEM_DIRS[@]/#/$OVERLAY_WORK}" "${SYSTEM_DIRS[@]/#/$CHROOT_ROOT}" 755 root:root || {
        log_message "error" "Failed to create system overlay directories in '$BASE_CHROOT'"
        return "$EXIT_CHROOT_INIT_FAILED"
    }

    local path
    for path in "${SYSTEM_DIRS[@]}"; do
        mount_overlay "$path" "${OVERLAY_UPPER}${path}" "${OVERLAY_WORK}${path}" "${CHROOT_ROOT}${path}" || return $?
    done

    create_directories "$CHROOT_ROOT/dev" "$CHROOT_ROOT/proc" "$CHROOT_ROOT/tmp" 755 root:root || {
        log_message "error" "Failed to create overlay directories in '$CHROOT_ROOT'"
        return "$EXIT_CHROOT_INIT_FAILED"
    }

    mount_dev "$CHROOT_ROOT/dev" || return $?
    mount_proc "$CHROOT_ROOT/proc" || return $?
    chown 1777 "$CHROOT_ROOT/tmp" || { log_message "error" "Failed to setup directory '$CHROOT_ROOT/tmp'"; return "$EXIT_CHROOT_INIT_FAILED"; }
    
    restrict_binaries "$CHROOT_ROOT" "${RESTRICTED_CMDS[@]}" || return $?

    log_message "info" "Chroot '$BASE_CHROOT' initialized successfully"

    return "$EXIT_SUCCESS"
}

# Инициализация chroot-окружения с временной блокировкой
with_lock "$TMP_DIR/$LOCK_CHROOT_PREF.lock" init_chroot
exit $?