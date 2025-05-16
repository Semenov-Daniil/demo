#!/bin/bash

# remove_chroot.fn.sh - Скрипт экспортирующий функцию для удаления chroot-окружения
# Расположение: bash/ssh/remove_chroot.fn.sh

set -euo pipefail

# Проверка, что скрипт не запущен напрямую
[[ "${BASH_SOURCE[0]}" == "$0" ]] && { 
    echo "This script ('$0') is meant to be sourced"
    exit 1
}

# Проверка активности пользователя и завершение его сеансов
check_and_terminate_user() {
    local username="$1"
    pgrep -u "$username" >/dev/null || return ${EXIT_SUCCESS}
    pkill -u "$username" 2>/dev/null || pkill -9 -u "$username" 2>/dev/null || {
        log_message "error" "Failed to terminate processes for '$username'"
        return ${EXIT_GENERAL_ERROR}
    }
    local timeout=1 start=$(date +%s)
    while pgrep -u "$username" >/dev/null; do
        [[ $(( $(date +%s) - start )) -gt $timeout ]] && {
            log_message "error" "Processes for '$username' still running after timeout"
            return ${EXIT_GENERAL_ERROR}
        }
        sleep 0.05
    done
}

# Очистка монтирований
cleanup_mounts() {
    local chroot="$1"
    local failed_mounts=()
    local paths=("${MOUNT_DIRS[@]}" "${MOUNT_FILES[@]}")
    
    for path in "${paths[@]}"; do
        [[ -z "$path" ]] && continue
        local mount_point="$chroot/${path#/}"
        mountpoint -q "$mount_point" || continue
        umount "$mount_point" 2>/dev/null || {
            umount -l "$mount_point" 2>/dev/null || failed_mounts+=("$mount_point")
        }
    done
    [[ ${#failed_mounts[@]} -eq 0 ]] || {
        log_message "error" "Failed to unmount: ${failed_mounts[*]}"
        return ${EXIT_MOUNT_FAILED}
    }
}

# Удаление записей из /etc/fstab
clean_fstab() {
    local chroot_dir="$1"

    (
        flock -w 1 200 || { log_message "error" "Failed to acquire fstab lock"; return ${EXIT_GENERAL_ERROR}; }
        local temp_fstab=$(mktemp)
        grep -v "$chroot_dir" /etc/fstab > "$temp_fstab" || {
            log_message "error" "Failed to filter '/etc/fstab'"
            return ${EXIT_CHROOT_INIT_FAILED}
        }
        mv "$temp_fstab" /etc/fstab || {
            log_message "error" "Failed to update '/etc/fstab'"
            return ${EXIT_CHROOT_INIT_FAILED}
        }
    ) 200>"$LOCK_FSTAB_FILE"
}

# Удаление chroot-директории
remove_chroot_dir() {
    local chroot="$1"

    [[ -d "$chroot" ]] && rm -rf "$chroot" || {
        log_message "error" "Failed to remove '$chroot'"
        return ${EXIT_GENERAL_ERROR}
    }
}

# Основная функция удаления chroot-окружения
# remove_chroot <username>
remove_chroot() {
    local username="$1"

    # Проверка пользователя
    [[ "$username" =~ ^[a-zA-Z0-9._-]+$ ]] || {
        log_message "error" "Invalid username: $username"
        return ${EXIT_INVALID_ARG}
    }

    id "$username" &>/dev/null || {
        log_message "error" "User '$username' does not exist"
        return ${EXIT_INVALID_ARG}
    }

    groups "$username" | grep -q "$STUDENT_GROUP" || {
        log_message "error" "User '$username' is not a member of the '$STUDENT_GROUP' group"
        return ${EXIT_INVALID_ARG}
    }

    local student_chroot="${CHROOT_STUDENTS}/${username}"

    [[ -d "$student_chroot" ]] || { 
        log_message "info" "Chroot '$student_chroot' does not exist"
        return ${EXIT_SUCCESS}
    }

    log_message "info" "Starting chroot removal for '$username'"

    check_and_terminate_user "$username" || return $?
    cleanup_mounts "$student_chroot" || return $?
    clean_fstab "$student_chroot" || return $?
    remove_chroot_dir "$student_chroot" || return $?

    log_message "info" "Chroot for '$username' removed successfully"

    return ${EXIT_SUCCESS}
}

export -f remove_chroot check_and_terminate_user cleanup_mounts clean_fstab remove_chroot_dir
return ${EXIT_SUCCESS}