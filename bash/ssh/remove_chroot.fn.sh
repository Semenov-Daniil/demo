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
    local username="$1" timeout="${2:-2}"
    local ssh_processes="ssh|sshd|ssh-agent"

    pgrep -u "${username}" -f "${ssh_processes}" >/dev/null || {
        log_message "info" "No SSH processes found for '${username}'"
        return "${EXIT_SUCCESS}"
    }

    pkill -u "${username}" -f "${ssh_processes}" 2>/dev/null || {
        pkill -9 -u "${username}" -f "${ssh_processes}" 2>/dev/null || {
            log_message "error" "Failed to terminate SSH processes for '$username'"
            return "${EXIT_GENERAL_ERROR}"
        }
    }

    local start=$(date +%s)
    while pgrep -u "${username}" -f "${ssh_processes}" >/dev/null; do
        [[ $(( $(date +%s) - start )) -gt $timeout ]] && {
            log_message "error" "SSH processes for '$username' still running after ${timeout}s"
            ps -u "${username}" -f | grep -E "${ssh_processes}" | log_message "error"
            return "${EXIT_GENERAL_ERROR}"
        }
        sleep 0.05
    done

    log_message "info" "All SSH processes for '$username' terminated successfully"
    return "${EXIT_SUCCESS}"
}

# Удаление chroot
delete_chroot() {
    local chroot="$1"
    rm -rf "$chroot" || {
        log_message "error" "Failed to delete chroot directory '$chroot'"
        return "$EXIT_GENERAL_ERROR"
    }
    return "$EXIT_SUCCESS"
}

# Основная функция очистки и удаления chroot-окружения
# Usage: remove_chroot <username>
remove_chroot() {
    local username="$1"
    local user_chroot="$CHROOT_STUDENTS/$username"
    local overlay_upper="$user_chroot/upper"
    local overlay_work="$user_chroot/work"
    local chroot_root="$user_chroot/root"
    local chroot_home="$chroot_root/home/$username"
    local chroot_workspace="$chroot_home/$username"

    [[ -n "$username" ]] || {
        log_message "error" "Empty username. Usage ${FUNCNAME[0]} <username>" >&2
        return "$EXIT_SYSTEMD_UNIT"
    }

    # Проверка существования chroot-директории
    if [[ ! -d "$user_chroot" ]]; then
        log_message "info" "Chroot directory '$user_chroot' does not exist, skip deletion"
        return "$EXIT_INVALID_ARG"
    fi

    # Размонтирование директорий root
    remove_systemd_unit "$(title_mount_unit "$chroot_root/dev/pts")" || return $?
    remove_systemd_unit "$(title_mount_unit "$chroot_root/dev")" || return $?
    remove_systemd_unit "$(title_mount_unit "$chroot_root/proc")" || return $?
    remove_systemd_unit "$(title_mount_unit "$chroot_root/sys")" || return $?
    remove_systemd_unit "$(title_mount_unit "$chroot_root/run")" || return $?
    remove_systemd_unit "$(title_mount_unit "$chroot_workspace")" || return $?

    # Размонтирование OverlayFS
    local path
    for path in "${SYSTEM_DIRS[@]}"; do
        remove_systemd_unit "$(title_mount_unit "${chroot_root}${path}")" || return $?
    done
    

    # Попытка размонтировать оставшиеся точки
    findmnt -l | grep "^$user_chroot" | while read -r line; do
        mountpoint=$(echo "$line" | awk '{print $1}')
        log_message "warning" "Some mount points in '$mountpoint' are still active. Attempting to unmount the point '$mountpoint'."
        remove_systemd_unit "$(title_mount_unit "$mountpoint")"
        umount -f "$mountpoint" 2>/dev/null || umount -l "$mountpoint" 2>/dev/null || true
    done

    # Удаление директорий chroot
    with_lock "$LOCK_CHROOT_STUDENTS_FILE" delete_chroot "$user_chroot" || return $?

    log_message "info" "Chroot directory '$user_chroot' was successfully deleted"

    return "$EXIT_SUCCESS"
}

export -f check_and_terminate_user delete_chroot remove_chroot
return "$EXIT_SUCCESS"