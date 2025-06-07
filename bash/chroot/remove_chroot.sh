#!/bin/bash
# remove_chroot.sh - Скрипт удаления chroot-окружения
# Расположение: bash/chroot/remove_chroot.sh

set -euo pipefail

# Подключение локального config.sh
LOCAL_CONFIG="$(realpath $(dirname "${BASH_SOURCE[0]}")/config.sh)"
source "$LOCAL_CONFIG" || {
    echo "Failed to source local config '$LOCAL_CONFIG'" >&2
    exit 1
}

remove_chroot() {
    log_message "info" "Starting chroot removal '$BASE_CHROOT'"

    [[ ! -d "$BASE_CHROOT" ]] && {
        log_message "info" "Chroot directory '$BASE_CHROOT' does not exist"
        return 0
    }

    local units=$(get_mount_units "$BASE_CHROOT") unit
    for unit in $units; do
        remove_systemd_unit "$unit" || return $?
    done

    mount | grep -q -F "$BASE_CHROOT" && {
        local mountpoint
        mount | grep "$BASE_CHROOT" | awk '{print $3}' | sort -r | while IFS= read -r mountpoint; do
            umount "$mountpoint" 2>/dev/null || {
                fuser -v "$mountpoint" 2>/dev/null
                fuser -km "$mountpoint" 2>/dev/null
                sleep 1
                umount -f "$mountpoint" 2>/dev/null || umount -l "$mountpoint" 2>/dev/null || true
            }
        done
    }

    rm -rf "$BASE_CHROOT" || {
        log_message "error" "Failed to delete chroot directory '$BASE_CHROOT'"
        return "$EXIT_GENERAL_ERROR"
    }

    log_message "ok" "Chroot '$BASE_CHROOT' was successfully removed"
}

# Удаление chroot-окружения с временной блокировкой
with_lock "$TMP_DIR/$LOCK_CHROOT_PREF.lock" remove_chroot
exit $?