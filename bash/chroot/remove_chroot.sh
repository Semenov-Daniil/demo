#!/bin/bash
# remove_chroot.sh - Скрипт исполняющий удаление chroot-окружения
# Расположение: bash/chroot/remove_chroot.sh

set -euo pipefail

# Подключение логального config.sh
source "$(dirname "${BASH_SOURCE[0]}")/config.sh" || {
    echo "Failed to source local config.sh"
    exit 1
}

# Подключение скрипта удаления chroot-окружения
source "$REMOVE_CHROOT_FN" || { log_message "error" "Failed to source '$REMOVE_CHROOT_FN'" >&2; return "$EXIT_GENERAL_ERROR"; }

# Удаление chroot-окружения с временной блокировкой
with_lock "$TMP_DIR/$LOCK_CHROOT_PREF.lock" remove_chroot
exit $?