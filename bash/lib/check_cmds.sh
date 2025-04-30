#!/bin/bash

# Скрипт для проверки наличия команд
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

# Функция проверки команд
# Принимает массив команд как аргумент
check_cmds() {
    local cmds=("$@")
    local missing_cmds=()
    local cmd

    log "$LOG_INFO: Starting commands checking"

    for cmd in "${cmds[@]}"; do
        if ! command -v "$cmd" >/dev/null 2>&1; then
            log "$LOG_ERROR: $cmd is not exist"
            missing_cmds+=("$cmd") 
        fi
    done

    if [[ ${#missing_cmds[@]} -gt 0 ]]; then
        log "$LOG_ERROR: Missing command: ${missing_cmds[*]}"
        return $ERR_GENERAL
    fi

    log "$LOG_INFO: All commands are exist"
    return 0
}

# Экспорт функции
export -f check_cmds

return 0