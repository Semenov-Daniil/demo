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

umount_unit $TARGET
exit $?