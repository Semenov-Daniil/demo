#!/bin/bash
# setup_module_dirs.sh - Скрипт настройки папок модулей
# Расположение: bash/system/setup_module_dirs.sh

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
    echo "Usage: $0 <username> <module_dirs>"
    exit ${EXIT_INVALID_ARG}
fi

USERNAME="${ARGS[0]}"
MODULE_DIRS="${ARGS[@]:1}"

if ! id "$USERNAME" >/dev/null 2>&1; then
    log_message "error" "User '$USERNAME' does not exist"
    exit ${EXIT_USER_NOT_FOUND}
fi

for dir in "${MODULE_DIRS[@]}"; do
    if [[ ! -d "$dir" ]]; then
        log_message "error" "Module directory '$dir' does not exist"
        exit ${EXIT_NOT_FOUND}
    fi
done

update_permissions ${MODULE_DIRS[@]} 770 "$USERNAME:$SITE_GROUP" || exit $?
