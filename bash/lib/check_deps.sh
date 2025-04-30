#!/bin/bash

# Скрипт для проверки зависимостей
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

if ! declare -p LOG_INFO >/dev/null 2>&1; then
    echo "[ERROR]: LOG_INFO is not defined" >&2
fi

if ! declare -p LOG_ERROR >/dev/null 2>&1; then
    echo "[ERROR]: LOG_ERROR is not defined" >&2
fi

# Проверка наличия функции log
if ! declare -F log >/dev/null; then
    echo "[ERROR]: Logging function 'log' not defined after sourcing '$LOGGING_SCRIPT'" >&2
    return $ERR_FILE_NOT_FOUND
fi

# Функция проверки зависимостей
# Принимает массив зависимостей как аргумент
check_deps() {
    local deps=("$@")
    local missing_deps=()
    local dep

    log "$LOG_INFO: Starting dependency checking"

    for dep in "${deps[@]}"; do
        if dpkg-query -W -f='${Status}' "$dep" 2>/dev/null | grep -q "install ok installed"; then
            log "$LOG_INFO: $dep is installed"
        else
            log "$LOG_ERROR: $dep is not installed"
            missing_deps+=("$dep")
        fi
    done

    if [[ ${#missing_deps[@]} -gt 0 ]]; then
        log "$LOG_ERROR: Missing dependencies: ${missing_deps[*]}"
        return $ERR_GENERAL
    fi

    log "$LOG_INFO: All dependencies are installed"
    return 0
}

# Экспорт функции
export -f check_deps

return 0