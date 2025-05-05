#!/bin/bash

# config.sh - Локальный конфигурационный файл для скриптов создания/удаления, настройки системных пользователей и папок модулей
# Расположение: bash/system/config.sh

set -euo pipefail

# Проверка, что скрипт не запущен напрямую
if [[ "${BASH_SOURCE[0]}" == "$0" ]]; then
    echo "This script ('$0') is meant to be sourced" >&2
    exit 1
fi

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

# Коды выхода
export EXIT_FAILED_CREATE_USER=30
export EXIT_FAILED_DELETE_USER=31

# Парсинг аргументов
declare -a ARGS=()
LOG_FILE="$(basename "${BASH_SOURCE[1]}" .sh).log"
while [[ $# -gt 0 ]]; do
    case "$1" in
        --log=*)
            LOG_FILE="${1#--log=}"
            shift
            ;;
        *)
            ARGS+=("$1")
            shift
            ;;
    esac
done
export ARGS

# Переменные



# Пути к скриптам


# Подключение логирования
source_script "$LOGGING_SCRIPT" "$LOG_FILE" || {
    echo "Failed to source script $LOGGING_SCRIPT" >&2
    exit "${EXIT_GENERAL_ERROR}"
}

# Подключение проверки команд
source_script "$CHECK_CMDS_SCRIPT" || {
    echo "Failed to source script $CHECK_CMDS_SCRIPT" >&2
    exit "${EXIT_GENERAL_ERROR}"
}

# Подключение создания директорий
source_script "$CREATE_DIRS_SCRIPT" || {
    echo "Failed to source script $CREATE_DIRS_SCRIPT" >&2
    exit "${EXIT_GENERAL_ERROR}"
}

# Подключение обновления владельца и прав фалов/директорий
source_script "$UPDATE_PERMS_SCRIPT" || {
    echo "Failed to source script $UPDATE_PERMS_SCRIPT" >&2
    exit "${EXIT_GENERAL_ERROR}"
}

return ${EXIT_SUCCESS}