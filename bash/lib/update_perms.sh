#!/bin/bash
# update_perms.sh - Функция для обновления владельца и прав фалов/директорий
# Расположение: bash/lib/create_dirs.sh

set -euo pipefail

# Проверка, что скрипт не запущен напрямую
if [[ "${BASH_SOURCE[0]}" == "$0" ]]; then
    echo "This script ('$0') is meant to be sourced" >&2
    exit 1
fi

# Установка переменных по умолчанию
: "${EXIT_SUCCESS:=0}"
: "${EXIT_GENERAL_ERROR:=1}"
: "${EXIT_INVALID_ARG:=2}"

# Обновление владельца и прав фалов/директорий
# update_permissions /path/dir /path/dir/file "755" "root:root"
update_permissions() {
    local -a files=("${@:1:$#-2}") 
    local perms="${@: -2:1}"
    local owner="${@: -1:1}"
    local missing_files=()
    local file

    if [[ ! "$perms" =~ ^[0-7]{3}$ ]]; then
        echo "Invalid permissions format: $perms (expected octal, e.g., 755)"
        return ${EXIT_INVALID_ARG}
    fi

    if [[ ! "$owner" =~ ^[a-zA-Z0-9._-]+(:[a-zA-Z0-9._-]+)?$ ]]; then
        echo "Invalid owner format: $owner (expected user:group or user)"
        return ${EXIT_INVALID_ARG}
    fi

    for file in "${files[@]}"; do
        [[ -z "$file" ]] && continue
        current_perms=$(stat -c %a "$file")
        current_owner=$(stat -c %U:%G "$file")
        
        if [[ "$current_owner" != "$owner" ]]; then
            chown "$owner" "$file" || {
                echo "Failed to set ownership $owner for '$file'"
                missing_files+=($file)
            }
        fi

        if [[ "$current_perms" != "$perms" ]]; then
            chown "$perms" "$file" || {
                echo "Failed to set permissions $perms for '$file'"
                missing_files+=($file)
            }
        fi
    done

    if [[ ${#missing_files[@]} -gt 0 ]]; then
        echo "Missing update permissions: ${missing_files[*]}" >&2
        return "${EXIT_GENERAL_ERROR}"
    fi

    return ${EXIT_SUCCESS}
}

# Экспорт функции
export -f update_permissions

return ${EXIT_SUCCESS}