#!/bin/bash

# config.sh - Локальный конфигурационный файл для скриптов создания/удаления, настройки системных пользователей и папок модулей
# Расположение: bash/system/config.sh

set -euo pipefail

# Проверка, что скрипт не запущен напрямую
[[ "${BASH_SOURCE[0]}" == "$0" ]] && {
    echo "This script ('$0') is meant to be sourced"
    exit 1
}

# Проверка root-прав
[[ $EUID -ne 0 ]] || {
    echo "This operation requires root privileges"
    exit 1
}

# Подключение глобального config.sh
source "$(dirname "${BASH_SOURCE[0]}")/../config.sh" || {
    echo "Failed to source global config.sh"
    exit 1
}

# Коды выхода
export EXIT_FAILED_CREATE_USER=30
export EXIT_FAILED_DELETE_USER=31

# Парсинг аргументов
declare -a ARGS=()
export LOG_FILE="$(basename "${BASH_SOURCE[1]}" .sh).log"

while [[ $# -gt 0 ]]; do
    case "$1" in
        --log=*) LOG_FILE="${1#--log=}"; shift ;;
        *) ARGS+=("$1"); shift ;;
    esac
done

export ARGS

# Установка переменных
export LOCK_USER_PREF="${LOCK_PREF}_user"

# Подключение логирования
source_script "$LOGGING_SCRIPT" "$LOG_FILE" || {
    echo "Failed to source script $LOGGING_SCRIPT"
    exit "${EXIT_GENERAL_ERROR}"
}

# Подключение вспомогательных скриптов/функций
source_script "${LIB_DIR}/common.sh" || exit $?

return ${EXIT_SUCCESS}