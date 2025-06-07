#!/bin/bash
# update_permissions.fn.sh - Скрипт экспортирующий функцию обновления владельца и прав фалов/директорий
# Расположение: bash/lib/update_permissions.fn.sh

set -euo pipefail

# Проверкa подключения скрипта
[[ "${BASH_SOURCE[0]}" == "$0" ]] && {
    echo "This script ('$0') is meant to be sourced"
    return 1
}

: "${TMP_DIR:=/tmp}"
: "${LOCK_PREF:="lock"}"

# Обновление владельца и прав фалов/директорий
# Usage: update_permissions [directory/file ...] <perms: 755> <owner: root:root>
update_permissions() {
    [[ ${#@} -lt 3 ]] && {
        echo "Usage: ${FUNCNAME[0]} <directory/file> <perms> <owner>" >&2
        return 1
    }

    local -a paths=("${@:1:$#-2}") perms="${@: -2:1}" owner="${@: -1:1}"
    local missing_paths=() user group path

    [[ "$perms" =~ ^[0-7]{3,4}$ ]] || { echo "Invalid permissions '$perms'"; return 1; }

    if [[ "$owner" =~ ^[a-zA-Z0-9._-]+(:[a-zA-Z0-9._-]+)?$ ]]; then
        user="${owner%%:*}"
        group="${owner#*:}"
        [[ "$group" == "$owner" ]] && group="$user"
    else
        echo "Invalid owner '$owner'"
        return 1
    fi

    id "$user" &>/dev/null || { echo "User '$user' does not exist" >&2; return 1; }
    getent group "$group" &>/dev/null || { echo "Group '$group' does not exist" >&2; return 1; }

    for path in "${paths[@]}"; do
        [[ -z "$path" ]] && continue
        [[ -e "$path" ]] || { echo "Path '$path' does not exist"; missing_paths+=("$path"); continue; }
        local current_perms=$(stat -c %a "$path" 2>/dev/null || echo "unknown")
        local current_owner=$(stat -c %U:%G "$path" 2>/dev/null || echo "unknown")
        [[ "$current_owner" != "$user:$group" ]] && {
            chown "$user:$group" "$path" 2>/dev/null || { missing_paths+=("$path"); continue; }
        }
        [[ "$current_perms" != "$perms" ]] && {
            chmod "$perms" "$path" 2>/dev/null || { missing_paths+=("$path"); continue; }
        }
    done

    [[ "${#missing_paths[@]}" -gt 0 ]] && {
        echo "Failed to update permissions/ownership for: ${missing_paths[*]}" >&2
        return 1
    }

    return 0
}

export -f update_permissions
return 0