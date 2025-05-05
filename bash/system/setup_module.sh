#!/bin/bash
# setup_module.sh - Скрипт настройки папок модулей
# Расположение: bash/system/setup_module.sh

set -euo pipefail

# Подключение логального config.sh
LOCAL_CONFIG="$(dirname "${BASH_SOURCE[0]}")/config.sh"
source "$LOCAL_CONFIG" || {
    echo "Failed to source script $LOCAL_CONFIG" >&2
    exit 1
}

# Основная логика
# Проверка массива ARGS
if ! declare -p ARGS >/dev/null 2>&1; then
    echo "ARGS array is not defined"
    exit ${EXIT_INVALID_ARG}
fi

# Проверка аргументов
if [[ ${#ARGS[@]} -lt 2 ]]; then
    echo "Usage: $0 <username> <module_dir>"
    exit ${EXIT_INVALID_ARG}
fi

USERNAME="${ARGS[0]}"
MODULE_DIR="${ARGS[1]}"

if ! id "$USERNAME" >/dev/null 2>&1; then
    log_message "error" "User '$USERNAME' does not exist"
    exit ${EXIT_USER_NOT_FOUND}
fi

if [[ ! -d "$MODULE_DIR" ]]; then
    log_message "error" "Module directory '$MODULE_DIR' does not exist"
    exit ${EXIT_NOT_FOUND}
fi

update_permissions "$MODULE_DIR" 770 "$USERNAME:$SITE_GROUP" || exit $? 
