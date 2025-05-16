#!/bin/bash

# update_permissions.sh - Скрипт для обновления владельца и прав фалов/директорий
# Расположение: bash/utils/update_permissions.sh

set -euo pipefail

# Подключение локального config.sh
source "$(dirname "${BASH_SOURCE[0]}")/config.sh" || {
    echo "Failed to source local config.sh"
    exit 1
}

update_permissions "$@" || exit $?

exit ${EXIT_SUCCESS}