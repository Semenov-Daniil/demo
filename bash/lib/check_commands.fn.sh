#!/bin/bash

# check_commands.fn.sh - Функция для проверки наличия команд
# Расположение: bash/lib/check_commands.fn.sh

set -euo pipefail

# Проверка, что скрипт не запущен напрямую
[[ "${BASH_SOURCE[0]}" == "$0" ]] && {
    echo "This script ('$0') is meant to be sourced"
    exit 1
}

# Функция проверки наличия команд
# check_commands <command> [command ...]
check_commands() {
    local -a missing_cmds=()
    local cmd cache_key cache_file="${CMDS_CACHE}_$(echo "$*" | sha256sum | cut -d' ' -f1)"

    # Проверка кэша (24 часа)
    [[ -f "$cache_file" && $(( $(date +%s) - $(stat -c %Y "$cache_file") )) -lt 86400 ]] && return 0

    for cmd in "$@"; do
        [[ -z "$cmd" ]] && continue
        command -v "$cmd" >/dev/null 2>&1 || {
            echo "Command '$cmd' not found"
            missing_cmds+=("$cmd")
        }
    done

    if [[ ${#missing_cmds[@]} -gt 0 ]]; then
        echo "Missing commands: ${missing_cmds[*]}"
        return 1
    fi

    touch "$cache_file" 2>/dev/null || echo "Warning: Failed to update command cache"

    return 0
}

export -f check_commands
return 0