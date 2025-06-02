#!/bin/bash
# update_permissions.sh - Скрипт для обновления владельца и прав фалов/директорий
# Расположение: bash/utils/update_permissions.sh

set -euo pipefail

# Подключение логального config.sh
LOCAL_CONFIG="$(realpath $(dirname "${BASH_SOURCE[0]}")/config.sh)"
source "$LOCAL_CONFIG" || {
    echo "Failed to source local config '$LOCAL_CONFIG'" >&2
    exit 1
}

update_permissions "${ARGS[@]}"
exit $?