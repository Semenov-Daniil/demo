#!/bin/bash
# remove_workspace.fn.sh - Скрипт экспортирующий функции удаления chroot-workspace пользователя
# Расположение: bash/chroot/remove_workspace.sh

set -euo pipefail

_remove_home_lock() {
    rm -rf "$CHROOT_HOME/$USERNAME" || {
        log_message "error" "Failed to delete the home directory '$CHROOT_HOME/$USERNAME'"
        return "$EXIT_GENERAL_ERROR"
    }
}

remove_chroot_workspace() {
    [[ $# -ne 1 || -z "${1:-}" ]] && {
        log_message "error" "Usage: ${FUNCNAME[0]} <username>"
        return "$EXIT_INVALID_ARG"
    }

    local username="$1"
    local chroot_workspace="$(chroot_workspace "$username")"

    log_message "info" "Starting to remove user '$username' workspace from chroot '$CHROOT_ROOT'"

    [[ ! -d "$CHROOT_WORKSPACE" ]] && { log_message "warning" "Failed to find the '$CHROOT_WORKSPACE' workspace"; return 0; }

    remove_systemd_unit "$(title_mount_unit "$CHROOT_WORKSPACE")" || return $?

    with_lock "$TMP_DIR/$LOCK_CHROOT_PREF.lock" _remove_home_lock || return $?

    log_message "info" "Successful removal of user '$username' workspace from chroot '$CHROOT_ROOT'"

    return 0
}

export -f remove_chroot_workspace _remove_home_lock
return 0