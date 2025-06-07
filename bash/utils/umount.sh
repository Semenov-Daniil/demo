#!/bin/bash
# mount.sh - Скрипт для монтирования фалов/директорий
# Расположение: bash/utils/mount.sh

set -euo pipefail

# Подключение логального config.sh
LOCAL_CONFIG="$(realpath $(dirname "${BASH_SOURCE[0]}")/config.sh)"
source "$LOCAL_CONFIG" || {
    echo "Failed to source local config '$LOCAL_CONFIG'" >&2
    exit 1
}

declare -rx MOUNTS_FN="$LIB_DIR/mounts.fn.sh"
source "$MOUNTS_FN" || { log_message "error" "Failed to source '$MOUNTS_FN'"; return "$EXIT_GENERAL_ERROR"; }

[[ ${#ARGS[@]} -ge 1 ]] || { echo "Usage: $0 <target>"; exit "$EXIT_INVALID_ARG"; }

TARGET="${ARGS[0]}"

umount_target() {
    local target="$1"
    [[ -z "$target" || ! -e "$target" ]] && { log_message "error" "No target provided"; return "$EXIT_INVALID_ARG"; }

    local units=$(get_mount_units "$target") unit
    for unit in $units; do
        remove_systemd_unit "$unit" || return $?
    done

    mount | grep -q -F "$target" && {
        local mountpoint
        mount | grep "$target" | awk '{print $3}' | sort -r | while IFS= read -r mountpoint; do
            umount "$mountpoint" 2>/dev/null || {
                fuser -v "$mountpoint" 2>/dev/null
                fuser -km "$mountpoint" 2>/dev/null
                sleep 1
                umount -f "$mountpoint" 2>/dev/null || umount -l "$mountpoint" 2>/dev/null || true
            }
        done
    }

    return 0
}

umount_target $TARGET
exit $?