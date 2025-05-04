#!/bin/bash

# check_services.sh - Утилита для проверки наличия служб
# Расположение: bash/utils/check_services.sh

set -e

# Проверка root-прав
if [[ $EUID -ne 0 ]]; then
    echo "This operation requires root privileges" >&2
    exit 1
fi

# Подключение глобального config.sh
GLOBAL_CONFIG="$(dirname "${BASH_SOURCE[0]}")/../config.sh"
source "$GLOBAL_CONFIG" || {
    echo "Failed to source script $GLOBAL_CONFIG" >&2
    exit 1
}

# Подключение проверки зависимостей
source_script "$CHECK_DEPS_SCRIPT" "$@" || {
    echo "Failed to source script $CHECK_DEPS_SCRIPT" >&2
    exit "${EXIT_GENERAL_ERROR}"
}

# Проверка зависмостей
check_deps "${REQUIRED_SERVICES[@]}" || {
    echo "Failed check dependencies" >&2
    exit "${EXIT_NO_DEPENDENCY}"
}

exit "${EXIT_SUCCESS}"