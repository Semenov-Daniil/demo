#!/bin/bash

# create_directories.sh - Скрипт для создания директорий
# Расположение: bash/utils/create_directories.sh

set -euo pipefail

# Подключение локального config.sh
source "$(dirname "${BASH_SOURCE[0]}")/config.sh" || {
    echo "Failed to source local config.sh"
    exit 1
}

create_directories "$@" || exit $?

exit ${EXIT_SUCCESS}