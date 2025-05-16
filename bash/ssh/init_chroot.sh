#!/bin/bash

# init_chroot.sh - Скрипт инициализации chroot-окружения пользователя
# Расположение: bash/ssh/init_chroot.sh

set -euo pipefail

# Подключение логального config.sh
source "$(dirname "${BASH_SOURCE[0]}")/config.sh" || {
    echo "Failed to source local config.sh"
    exit 1
}

# Подключение скрипта удаления chroot-окружения
source "$REMOVE_CHROOT" || {
    echo "Failed to source script '$REMOVE_CHROOT'"
    exit ${EXIT_GENERAL_ERROR}
}

# Очистка
cleanup() {
    local exit_code=$?
    [[ $exit_code -eq 0 || $exit_code -eq $EXIT_INVALID_ARG ]] && return

    with_lock ${LOCK_CHROOT_FILE} remove_chroot "$USERNAME" || {
        log_message "error" "Failed to rollback chroot for $USERNAME"
    }
}

# Создание базовых директорий окружения
create_basic_dirs() {
    local chroot="$1"

    for dir in ${CHROOT_BASE_DIRS[@]}; do
        create_directories "$chroot/$dir" 755 root:root || {
            log_message "error" "Failed to create basic directories in $chroot"
            return ${EXIT_CHROOT_INIT_FAILED}
        }
    done

    chmod 1777 "$chroot/tmp" || {
        log_message "error" "Failed to set permissions for $chroot/tmp"
        return ${EXIT_CHROOT_INIT_FAILED}
    }
}

# Монтирование
mount_bind() {
    local src="$1" dest="$2" opts="$3"

    [[ -e "$src" ]]  || { log_message "error" "Source '$src' does not exist"; return ${EXIT_MOUNT_FAILED}; }
    [[ -e "$dest" ]]  || { log_message "error" "Source '$dest' does not exist"; return ${EXIT_MOUNT_FAILED}; }

    mount --bind "$src" "$dest" || { log_message "error" "Failed to bind mount $src to $dest"; return ${EXIT_MOUNT_FAILED}; }

    [[ -z "$opts" ]] || mount -o remount,"$opts",bind "$dest" || {
        log_message "error" "Failed to set options mount '$opts' for '$dest'"
        return ${EXIT_MOUNT_FAILED}
    }

    FSTAB_ENTRY+=("$src $dest none bind${opts:+,$opts} 0 0")
}

# Монтирование фалов
mount_chroot_files() {
    local chroot="$1"

    for path in ${MOUNT_FILES[@]}; do
        [[ -z "$path" ]] && continue

        local real_path="$path"

        [[ -L "$path" ]] && real_path=$(readlink -f "$path") || {
            log_message "error" "Failed to resolve symbolic link '$path'"
            return ${EXIT_CHROOT_INIT_FAILED}
        }

        [[ -e "$real_path" ]] || { 
            log_message "error" "Path '$real_path' does not exist"
            return ${EXIT_CHROOT_INIT_FAILED}
        }

        touch "$chroot_dir$path" && mount_bind "$real_path" "$chroot_dir$path" "ro" || return $?
    done
}

# Монтирование устройств
mount_dev() {
    local chroot_dev="$1/dev"

    create_directories "$chroot_dev" 755 root:root || {
        log_message "error" "Failed to create '$chroot_dev'"
        return ${EXIT_CHROOT_INIT_FAILED}
    }

    mount_bind "/dev" "$chroot_dev" "" || return $?
}

# Монтирование /proc с ограничениями
mount_proc() {
    local chroot_proc="$1/proc"

    create_directories "$chroot_proc" 755 root:root || {
        log_message "error" "Failed to create '$chroot_proc'"
        return ${EXIT_CHROOT_INIT_FAILED}
    }

    mount -t proc proc "$chroot_proc" -o defaults || {
        log_message "error" "Failed to mount proc to '$chroot_proc'"
        return ${EXIT_MOUNT_FAILED}
    }

    FSTAB_ENTRY+=("proc $chroot_proc proc defaults 0 0")
}

# Синхронизация файлов /etc
sync_etc_files() {
    local username="$1" target_etc="$2/etc"

    create_directories "$target_etc" 755 root:root || {
        log_message "error" "Failed to create '$target_etc'"
        return ${EXIT_CHROOT_INIT_FAILED}
    }

    getent passwd "$username" > "$target_etc/passwd" && \
    getent group "$STUDENT_GROUP" > "$target_etc/group" && \
    getent shadow "$username" > "$target_etc/shadow" || {
        log_message "error" "Failed to sync '/etc' files to '$target_etc'"
        return ${EXIT_CHROOT_INIT_FAILED}
    }

    echo "$BASH_BASHRC_STUDENT" > "$target_etc/bash.bashrc" || {
        log_message "error" "Failed to create and write to the file '$target_etc/bash.bashrc'"
        exit ${EXIT_CHROOT_INIT_FAILED}
    }
}

# Обновление /etc/fstab
update_fstab_entries() {
    (
        flock -w 1 200 || { log_message "error" "Failed to acquire fstab lock"; return ${EXIT_GENERAL_ERROR}; }
        local temp_fstab=$(mktemp)
        cp /etc/fstab "$temp_fstab" || {
            log_message "error" "Failed to copy '/etc/fstab'"
            return ${EXIT_CHROOT_INIT_FAILED}
        }
        for entry in "${FSTAB_ENTRY[@]}"; do
            grep -qs "^${entry%% *} ${entry#* }" "$temp_fstab" || echo "$entry" >> "$temp_fstab"
        done
        mv "$temp_fstab" /etc/fstab || {
            log_message "error" "Failed to update '/etc/fstab'"
            return ${EXIT_CHROOT_INIT_FAILED}
        }
    ) 200>"$LOCK_FSTAB_FILE"
}

# Основная логика
trap cleanup SIGINT SIGTERM EXIT

# Проверка массива ARGS
[[ -n "${ARGS+x}" ]] || { echo "ARGS array is not defined"; exit ${EXIT_INVALID_ARG}; }

# Проверка аргументов
[[ ${#ARGS[@]} -ge 1 ]] || { echo "Usage: $0 <username>"; exit ${EXIT_INVALID_ARG}; }

# Установка переменных
USERNAME="${ARGS[0]}"

# Проверка пользователя
[[ "$USERNAME" =~ ^[a-zA-Z0-9._-]+$ ]] || {
    log_message "error" "Invalid username: $USERNAME"
    exit ${EXIT_INVALID_ARG}
}

id "$USERNAME" &>/dev/null || {
    log_message "error" "User '$USERNAME' does not exist"
    exit ${EXIT_INVALID_ARG}
}

groups "$USERNAME" | grep -q "$STUDENT_GROUP" || {
    log_message "error" "User '$USERNAME' is not a member of the '$STUDENT_GROUP' group"
    exit ${EXIT_INVALID_ARG}
}

HOME_DIR="$(getent passwd "$USERNAME" | cut -d: -f6)"
STUDENT_CHROOT="${CHROOT_STUDENTS}/${USERNAME}"
FSTAB_ENTRY=()

[[ -d "$HOME_DIR" && -r "$HOME_DIR" ]] || { 
    log_message "error" "Home directory user '$USERNAME' - '$HOME_DIR' is not accessible"
    exit ${EXIT_INVALID_ARG}
}

log_message "info" "Starting chroot initialization for '$USERNAME'"

# Функция инициализации chroot-окружения
init_chroot() {
    # Проверка существования chroot
    if [[ -d "$STUDENT_CHROOT" && -d "$STUDENT_CHROOT/etc" ]]; then
        log_message "info" "Chroot directory '$STUDENT_CHROOT' already exists, removing"
        remove_chroot "$USERNAME" || exit $?
    fi

    # Создание chroot-директории
    create_directories "$STUDENT_CHROOT" 755 root:root || exit ${EXIT_CHROOT_INIT_FAILED}

    # Создание базовой структуры chroot-окружения
    create_basic_dirs '$STUDENT_CHROOT' || exit $?

    # Синхронизация /etc c /etc chroot-окружения
    sync_etc_files '$USERNAME' '$STUDENT_CHROOT' || exit $?

    # Монтирование устройкств в chroot-окружение
    mount_dev '$STUDENT_CHROOT' || exit $?

    create_basic_dirs "$STUDENT_CHROOT"
    sync_etc_files "$USERNAME" "$STUDENT_CHROOT"
    mount_dev "$STUDENT_CHROOT"

    # Монтирование директорий
    mount_bind "/usr" "$STUDENT_CHROOT/usr" ro || exit $?
    mount_bind "/bin" "$STUDENT_CHROOT/bin" ro || exit $?
    mount_bind "/lib" "$STUDENT_CHROOT/lib" ro || exit $?
    mount_bind "/lib64" "$STUDENT_CHROOT/lib64" ro || exit $?
    mount_bind "$HOME_DIR" "$STUDENT_CHROOT/home" "" || exit $?
    mount_proc "$STUDENT_CHROOT" || exit $?
    mount_chroot_files "$STUDENT_CHROOT" || exit $?

    # Обновление /etc/fstab
    update_fstab_entries || exit $?

    log_message "info" "Chroot for '$USERNAME' initialized successfully"

    return ${EXIT_SUCCESS}
}

# Установка блокировки
with_lock ${LOCK_CHROOT_FILE} init_chroot

exit ${EXIT_SUCCESS}