#!/bin/bash

# Скрипт для проверки root-прав
# Предназначен для подключения через source

# Проверка на прямой запуск
if [[ "${BASH_SOURCE[0]}" == "$0" ]]; then
    echo "[ERROR]: This script ('$0') is meant to be sourced, not executed directly" >&2
    exit 1
fi

# Проверка переменных
if ! declare -p ERR_FILE_NOT_FOUND >/dev/null 2>&1; then
    echo "[ERROR]: ERR_FILE_NOT_FOUND is not defined" >&2
fi

if ! declare -p ERR_ROOT_REQUIRED >/dev/null 2>&1; then
    echo "[ERROR]: ERR_ROOT_REQUIRED is not defined" >&2
fi

if ! declare -p LOG_ERROR >/dev/null 2>&1; then
    echo "[ERROR]: LOG_ERROR is not defined" >&2
fi

# Проверка наличия функции log
if ! declare -F log >/dev/null; then
    echo "[ERROR]: Logging function 'log' not defined" >&2
    return $ERR_FILE_NOT_FOUND
fi

# Функция проверки root-прав
check_root() {
    if [[ $EUID -ne 0 ]]; then
        log "$LOG_ERROR: This operation requires root privileges"
        return $ERR_ROOT_REQUIRED
    fi
    return 0
}

# Экспорт функции
export -f check_root

return 0