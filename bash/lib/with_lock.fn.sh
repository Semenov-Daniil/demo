#!/bin/bash
# with_lock.fn.sh - Скрипт экспортирующий функцию выполнения операций с блокировкой
# Расположение: bash/lib/with_lock.fn.sh

set -euo pipefail

# Проверкa подключения скрипта
[[ "${BASH_SOURCE[0]}" == "$0" ]] && {
    echo "This script ('$0') is meant to be sourced" >&2
    return 1
}

: "${TMP_DIR:=/tmp}"
: "${LOCK_PREF:="lock"}"

! command -v flock >/dev/null 2>&1 && {
    echo "Command 'flock' not found" >&2
    return 1
}

# Выполнения операций с блокировкой
# with_lock <lockfile> <action> <args> [<args> ...]
with_lock() {
    [ ${#@} -lt 2 ] && {
        echo "Usage with_lock: <lockfile> <action> <args> [<args> ...]" >&2
        return 1
    }

    local lockfile="$1" action="$2"
    shift 2

    if ! command -v "$action" >/dev/null && ! type -t "$action" >/dev/null; then
        echo "Invalid action: '$action'" >&2
        return 1
    fi

    touch "$lockfile" || {
        echo "Failed to create lockfile '$lockfile'" >&2
        return 1
    }

    trap 'rm -f "${lockfile}"; exec 200>&-' EXIT
    (
        flock -x 200 || { 
            echo "Failed to acquire lock for '$lockfile'" >&2
            trap - EXIT
            return 1
        }

        "$action" "$@" || return $?
    
    ) 200>"$lockfile"

    local ret=$?
    trap - EXIT
    exec 200>&-
    return $ret
}

export -f with_lock
return 0