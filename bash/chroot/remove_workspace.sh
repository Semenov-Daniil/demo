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

# Проверка аргументов
[[ ${#ARGS[@]} -ge 1 ]] || { echo "Usage: $0 <username>"; exit "$EXIT_INVALID_ARG"; }

# Установка переменных
declare -rx USERNAME="${ARGS[0]}"

# Удаление chroot-workspace
remove_chroot_workspace() {
    local username="$1"
    [[ -z "$username" ]] && { log_message "error" "No username provided"; return "$EXIT_INVALID_ARG"; }

    [[ "$username" =~ ^[a-zA-Z0-9._-]+$ ]] || { log_message "error" "Invalid username '$username'"; exit "$EXIT_INVALID_ARG"; }

    local chroot_workspace="$(chroot_workspace "$username")"

    log_message "info" "Starting to remove user '$username' workspace from chroot '$CHROOT_ROOT'"

    [[ ! -d "$chroot_workspace" ]] && { log_message "warning" "Failed to find the '$chroot_workspace' workspace"; return 0; }

    local units=$(get_mount_units "$chroot_workspace") unit
    for unit in $units; do
        remove_systemd_unit "$unit" || return $?
    done

    mount | grep -q -F "$CHROOT_HOME/$username" && {
        local mountpoint
        mount | grep "$CHROOT_HOME/$username" | awk '{print $3}' | sort -r | while IFS= read -r mountpoint; do
            umount "$mountpoint" 2>/dev/null || {
                fuser -v "$mountpoint" 2>/dev/null
                fuser -km "$mountpoint" 2>/dev/null
                sleep 1
                umount -f "$mountpoint" 2>/dev/null || umount -l "$mountpoint" 2>/dev/null || true
            }
        done
    }

    rm -rf "$CHROOT_HOME/$username" || {
        log_message "error" "Failed to delete the home directory '$CHROOT_HOME/$username'"
        return "$EXIT_GENERAL_ERROR"
    }

    log_message "ok" "Successful removal of user '$username' workspace from chroot '$CHROOT_ROOT'"
    return 0
}

# Удаление chroot-workspace пользователя с блокировкой
with_lock "$TMP_DIR/${LOCK_CHROOT_PREF}_${USERNAME}.lock" remove_chroot_workspace "$USERNAME"
exit $?