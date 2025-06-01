#!/bin/bash
# restrict_binaries.fn.sh - Скрипт экспортирующий функцию ограничения команд
# Расположение: bash/chroot/restrict_binaries.fn.sh

set -euo pipefail

# Проверка, что скрипт не запущен напрямую
[[ "${BASH_SOURCE[0]}" == "$0" ]] && {
    echo "This script ('$0') is meant to be sourced"
    exit 1
}

restrict_binaries() {
    local root="$1"
    shift

    local cmd path
    for cmd in "$@"; do
        for path in "$root/bin/$cmd" "$root/usr/bin/$cmd"; do
            if [[ -f "$path" ]]; then
                chmod 000 "$path" || {
                    log_message "error" "Failed to restrict '$path'"
                    return 1
                }
            fi
        done
    done
    return 0
}

export -f restrict_binaries
return 0