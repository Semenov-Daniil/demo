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
: "${LOCK_TIMEOUT:=60}"

! command -v flock >/dev/null 2>&1 && {
    echo "Command 'flock' not found" >&2
    return 1
}

# Выполнения операций с блокировкой
# Usage: with_lock [-t timeout] <lockfile> <action> [<args> ...]
with_lock() {
    local timeout="$LOCK_TIMEOUT"
    local lockfile action

    while getopts ":t:" opt; do
        case $opt in
            t) [[ "$OPTARG" =~ ^[0-9]+$ ]] && timeout="$OPTARG" || { echo "Lock error: timeout must be a positive integer" >&2; return "${EXIT_INVALID_ARG}"; } ;;
            \?) echo "Lock error: invalid option -$OPTARG" >&2; return 1 ;;
        esac
    done
    shift $((OPTIND-1))

    if [ $# -lt 2 ]; then
        echo "Usage: ${FUNCNAME[0]} [-t timeout] <lockfile> <action> [<args> ...]" >&2
        return 1
    fi

    lockfile="$1"
    action="$2"
    shift 2

    ! command -v "$action" >/dev/null && ! type -t "$action" >/dev/null && {
        echo "Lock error: Invalid action '$action'" >&2
        return 1
    }

    ! touch "$lockfile" 2>/dev/null && {
        echo "Lock error: failed to create lockfile '$lockfile'" >&2
        return 1
    }

    local old_trap
    old_trap=$(trap -p EXIT | sed "s/^trap -- '\(.*\)' EXIT$/\1/" || true)

    exec 200>"$lockfile"

    trap 'rm -f "${lockfile}" 2>/dev/null; exec 200>&-; '"${old_trap}" EXIT

    if ! flock -x -w "$timeout" 200; then
        echo "Lock error: failed to acquire lock for '$lockfile' within ${timeout}s" >&2
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