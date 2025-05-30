#!/bin/bash
# check_commands.fn.sh - Скрипт экспортирующий функцию проверки наличия команд
# Расположение: bash/lib/check_commands.fn.sh

set -euo pipefail

# Проверкa подключения скрипта
[[ "${BASH_SOURCE[0]}" == "$0" ]] && {
    echo "This script ('$0') is meant to be sourced"
    return 1
}

# Проверка наличия команд
# Usage: check_commands [command ...]
check_commands() {
    local -a missing_cmds=()
    local cmd

    for cmd in "$@"; do
        [[ -z "$cmd" ]] && continue
        command -v "$cmd" >/dev/null 2>&1 || {
            echo "Command '$cmd' not found"
            missing_cmds+=("$cmd")
        }
    done

    [[ ${#missing_cmds[@]} -gt 0 ]] && {
        echo "Missing commands: ${missing_cmds[*]}"
        return 1
    }

    return 0
}

export -f check_commands
return 0