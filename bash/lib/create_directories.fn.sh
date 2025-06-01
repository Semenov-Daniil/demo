#!/bin/bash
# create_directories.fn.sh - Скрипт экспортирующий функцию создания директорий
# Расположение: bash/lib/create_directories.fn.sh

set -euo pipefail

# Проверкa подключения скрипта
[[ "${BASH_SOURCE[0]}" == "$0" ]] && {
    echo "This script ('$0') is meant to be sourced"
    return 1
}

: "${TMP_DIR:=/tmp}"
: "${LOCK_PREF:="lock"}"

# Создания директорий
create_directories() {
    [[ ${#@} -lt 3 ]] && {
        echo "Usage: ${FUNCNAME[0]} directory [directory...] <perms> <owner>"
        return 1
    }

    local -a dirs=("${@:1:$#-2}") perms="${@: -2:1}" owner="${@: -1:1}"
    local missing_dirs=() user group dir

    [[ "$perms" =~ ^[0-7]{3,4}$ ]] || { echo "Invalid permissions '$perms'"; return 1; }

    if [[ "$owner" =~ ^[a-zA-Z0-9._-]+(:[a-zA-Z0-9._-]+)?$ ]]; then
        user="${owner%%:*}"
        group="${owner#*:}"
        [[ "$group" == "$owner" ]] && group="$user"
    else
        echo "Invalid owner '$owner'"
        return 1
    fi

    id "$user" &>/dev/null || { echo "User '$user' does not exist"; return 1; }
    getent group "$group" &>/dev/null || { echo "Group '$group' does not exist"; return 1; }

    for dir in "${dirs[@]}"; do
        [[ -z "$dir" ]] && continue
        mkdir -p "$dir" 2>/dev/null || { echo "Cannot create '$dir'"; missing_dirs+=("$dir"); continue; }
        local current_perms=$(stat -c %a "$dir" 2>/dev/null || echo "unknown")
        local current_owner=$(stat -c %U:%G "$dir" 2>/dev/null || echo "unknown")
        [[ "$current_owner" != "$user:$group" ]] && { chown "$user:$group" "$dir" 2>/dev/null || missing_dirs+=("$dir"); continue; }
        [[ "$current_perms" != "$perms" ]] && { chmod "$perms" "$dir" 2>/dev/null || missing_dirs+=("$dir"); continue; }
    done

    [[ "${#missing_dirs[@]}" -gt 0 ]] && {
        echo "Missing create or setting permissions/ownership directories: ${missing_dirs[*]}"
        return 1
    }

    return 0
}

export -f create_directories
return 0