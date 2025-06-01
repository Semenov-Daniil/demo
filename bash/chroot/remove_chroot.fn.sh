#!/bin/bash
# remove_chroot.fn.sh - Скрипт экспортирующий функцию для удаления chroot-окружения
# Расположение: bash/chroot/remove_chroot.fn.sh

set -euo pipefail

# Проверка, что скрипт не запущен напрямую
[[ "${BASH_SOURCE[0]}" == "$0" ]] && { 
    echo "This script ('$0') is meant to be sourced"
    exit 1
}

# Основная функция очистки и удаления chroot-окружения
# Usage: remove_chroot <username>
remove_chroot() {
    log_message "info" "Starting chroot removal '$BASE_CHROOT'"

    if [[ ! -d "$BASE_CHROOT" ]]; then
        log_message "info" "Chroot directory '$BASE_CHROOT' does not exist"
        return
    fi

    remove_systemd_unit "$(title_mount_unit "$CHROOT_ROOT/dev/pts")" || return $?
    remove_systemd_unit "$(title_mount_unit "$CHROOT_ROOT/dev/shm")" || return $?
    remove_systemd_unit "$(title_mount_unit "$CHROOT_ROOT/dev")" || return $?
    remove_systemd_unit "$(title_mount_unit "$CHROOT_ROOT/proc")" || return $?

    local path
    for path in "${SYSTEM_DIRS[@]}"; do
        remove_systemd_unit "$(title_mount_unit "${CHROOT_ROOT}${path}")" || return $?
    done
    
    local unit
    for unit in $(get_mount_units_in_dir "$BASE_CHROOT"); do
        remove_systemd_unit "$unit" || return $?
    done

    local mountpoint
    mount | grep "$BASE_CHROOT" | awk '{print $3}' | sort -r | while IFS= read -r mountpoint; do
        umount "$mountpoint" 2>/dev/null || {
            fuser -km "$mountpoint" 2>/dev/null
            sleep 1
            umount -f "$mountpoint" 2>/dev/null || umount -l "$mountpoint" 2>/dev/null || true
        }
    done

    rm -rf "$BASE_CHROOT" || {
        log_message "error" "Failed to delete chroot directory '$BASE_CHROOT'"
        return "$EXIT_GENERAL_ERROR"
    }

    log_message "info" "Chroot '$BASE_CHROOT' was successfully removed"
}

export -f remove_chroot
return 0