#!/bin/bash
# with_lock.fn.sh - Скрипт экспортирующий функцию выполнения операций с блокировкой
# Расположение: bash/lib/with_lock.fn.sh

set -euo pipefail

! command -v flock >/dev/null 2>&1 && {
    echo "Command 'flock' not found"
    return 1
}

# Выполнения операций с блокировкой
# with_lock <lockfile> <action>
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