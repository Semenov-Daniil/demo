#!/bin/bash
# check_cmds.sh - Функция для проверки наличия команд
# Расположение: bash/lib/check_cmds.sh

set -euo pipefail

# Проверка, что скрипт не запущен напрямую
if [[ "${BASH_SOURCE[0]}" == "$0" ]]; then
    echo "This script ('$0') is meant to be sourced" >&2
    exit 1
fi

# Установка переменных по умолчанию
: "${EXIT_SUCCESS:=0}"
: "${EXIT_NO_CMD:=1}"

# Проверка наличия команд
# check_cmds ls cat rm
check_cmds() {
    local missing_cmds=()
    local cmd

    for cmd in "$@"; do
        [[ -z "$cmd" ]] && continue
        if ! command -v "$cmd" >/dev/null 2>&1; then
            echo "Command '$cmd' not found" >&2
            missing_cmds+=("$cmd")
        fi
    done

    if [[ ${#missing_cmds[@]} -gt 0 ]]; then
        echo "Missing commands: ${missing_cmds[*]}"
        return ${EXIT_NO_CMD}
    fi

    return ${EXIT_SUCCESS}
}

# Экспорт функции
export -f check_cmds

return ${EXIT_SUCCESS}