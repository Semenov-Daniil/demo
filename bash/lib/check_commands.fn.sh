#!/bin/bash
# check_commands.fn.sh - Скрипт экспортирующий функцию проверки наличия команд
# Расположение: bash/lib/check_commands.fn.sh

set -euo pipefail

# Проверка наличия команд
# check_commands <command> [command ...]
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