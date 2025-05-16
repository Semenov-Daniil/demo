#!/bin/bash

# with_lock.fn.sh - Функция для выполнения операций с блокировкой
# Расположение: bash/lib/with_lock.fn.sh

set -euo pipefail

[[ "${BASH_SOURCE[0]}" == "$0" ]] && { 
    echo "This script ('$0') is meant to be sourced"
    exit 1
}

with_lock() {
    local lockfile="$1" action="$2"
    shift 2
    trap 'rm -f "$lockfile"' EXIT
    (
        flock -x 200 || { 
            log_message "error" "Failed to acquire lock for '$lockfile'"
            return 1
        }

        "$action" "$@"
    
    ) 200>"$lockfile"
}

export -f with_lock
return 0