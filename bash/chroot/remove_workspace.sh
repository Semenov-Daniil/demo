#!/bin/bash
# remove_workspace.sh - Скрипт удаления chroot-workspace пользователя
# Расположение: bash/chroot/remove_workspace.sh

set -euo pipefail

# Подключение локального config.sh
LOCAL_CONFIG="$(realpath $(dirname "${BASH_SOURCE[0]}")/config.sh)"
source "$LOCAL_CONFIG" || {
    echo "Failed to source local config '$LOCAL_CONFIG'" >&2
    exit 1
}

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

# Проверка аргументов
[[ ${#ARGS[@]} -ge 1 ]] || { echo "Usage: $0 <username>"; exit "$EXIT_INVALID_ARG"; }

# Установка переменных
declare -rx USERNAME="${ARGS[0]}"

# Проверка имени пользователя
[[ "$USERNAME" =~ ^[a-zA-Z0-9._-]+$ ]] || { log_message "error" "Invalid username '$USERNAME'"; exit "$EXIT_INVALID_ARG"; }

# Удаление chroot-workspace пользователя с блокировкой
with_lock "$TMP_DIR/${LOCK_CHROOT_PREF}_${USERNAME}.lock" remove_chroot_workspace "$USERNAME"
exit $?