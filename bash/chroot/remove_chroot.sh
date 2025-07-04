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

    umount_unit $BASE_CHROOT || return $?

    rm -rf "$BASE_CHROOT" || {
        log_message "error" "Failed to delete chroot directory '$BASE_CHROOT'"
        return "$EXIT_GENERAL_ERROR"
    }

    log_message "ok" "Chroot '$BASE_CHROOT' was successfully removed"
}

# Удаление chroot-окружения с временной блокировкой
with_lock "$TMP_DIR/$LOCK_CHROOT_PREF.lock" remove_chroot
exit $?