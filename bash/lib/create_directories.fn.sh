#!/bin/bash

# create_directories.fn.sh - Функция для создания директорий
# Расположение: bash/lib/create_directories.fn.sh

set -euo pipefail

# Проверка, что скрипт не запущен напрямую
[[ "${BASH_SOURCE[0]}" == "$0" ]] && {
    echo "This script ('$0') is meant to be sourced"
    exit 1
}

# Функция создания директорий
# create_directories <directory> [directory ...] <perms: 755> <owner: root:root>
create_directories() {
    [[ ${#@} -lt 3 ]] || {
        echo "Usage create_directories: <directory> <perms> <owner>"
        exit 1
    }

    local -a dirs=("${@:1:$#-2}") perms="${@: -2:1}" owner="${@: -1:1}" missing_dirs=()
    local user group

    [[ "$perms" =~ ^[0-7]{3}$ ]] || { echo "Invalid permissions '$perms'"; return 1; }

    if [[ "$owner" =~ ^[a-zA-Z0-9._-]+(:[a-zA-Z0-9._-]+)?$ ]]; then
        user="${owner%%:*}"
        group="${owner#*:}"
        [[ -z "$group" ]] && group="$user"
    else
        echo "Invalid owner '$owner'"
        return 1
    fi

    id "$user" &>/dev/null || { echo "User '$user' does not exist"; return 1; }
    getent group "$group" &>/dev/null || { echo "Group '$group' does not exist"; return 1; }

    local lockfile="${TMP_DIR}/${LOCK_PREF}_dirs.lock"
    trap 'rm -f "$lockfile"' EXIT
    (
        flock -x 200 || { echo "Failed to acquire lock"; return 1; }
        for dir in "${dirs[@]}"; do
            [[ -z "$dir" ]] && continue
            mkdir -p "$dir" 2>/dev/null || { echo "Cannot create '$dir'"; missing_dirs+=("$dir"); continue; }
            local current_perms=$(stat -c %a "$dir" 2>/dev/null || echo "unknown")
            local current_owner=$(stat -c %U:%G "$dir" 2>/dev/null || echo "unknown")
            [[ "$current_owner" != "$user:$group" ]] && chown "$user:$group" "$dir" 2>/dev/null || missing_dirs+=("$dir")
            [[ "$current_perms" != "$perms" ]] && chmod "$perms" "$dir" 2>/dev/null || missing_dirs+=("$dir")
        done
    ) 200>"$lockfile"

    [[ ${#missing_dirs[@]} -gt 0 ]] || {
        echo "Missing create or setting permissions/ownership directories: ${missing_dirs[*]}"
        return 1
    }

    return 0
}

export -f create_directories
return 0