#!/bin/bash
# with_lock.fn.sh - Скрипт экспортирующий функцию выполнения операций с блокировкой
# Расположение: bash/lib/with_lock.fn.sh

set -euo pipefail

# Проверкa подключения скрипта
[[ "${BASH_SOURCE[0]}" == "$0" ]] && {
    echo "This script ('$0') is meant to be sourced" >&2
    return 1
}

: "${TMP_DIR:="/tmp"}"
: "${LOCK_PREF:="lock"}"
: "${LOCK_TIMEOUT:="15"}"

! command -v flock >/dev/null 2>&1 && {
    echo "Command 'flock' not found" >&2
    return 1
}

# Выполнения операций с блокировкой
# Usage: with_lock <lockfile> <action> [<args> ...]
with_lock() {
    local lockfile action

    if [ $# -lt 2 ]; then
        echo "Usage: ${FUNCNAME[0]} <lockfile> <action> [<args> ...]" >&2
        return 1
    fi

    lockfile="$1"
    action="$2"
    shift 2

    ! command -v "$action" >/dev/null && ! type -t "$action" >/dev/null && {
        echo "Invalid lock action '$action'" >&2
        return 1
    }

    ! touch "$lockfile" 2>/dev/null && {
        echo "Failed to create lockfile '$lockfile'" >&2
        return 1
    }

    local old_trap=$(trap -p EXIT | sed "s/^trap -- '\(.*\)' EXIT$/\1/" || true)

    exec 200>"$lockfile"

    if ! flock -x -w "$LOCK_TIMEOUT" 200; then
        echo "Failed to acquire lock for '$lockfile' within ${LOCK_TIMEOUT}s" >&2
        trap - EXIT
        exec 200>&-
        return 1
    fi

    "$action" "$@"
    local ret=$?

    trap - EXIT
    exec 200>&-
    rm -f "$lockfile" 2>/dev/null

    if [ -n "$old_trap" ]; then
        trap "$old_trap" EXIT
    else
        trap - EXIT
    fi

    return $ret
}

export -f with_lock
return 0