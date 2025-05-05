#!/bin/bash
# create_dirs.sh - Функция для создания и настройки директорий
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

# Создание и настройка директорий
# create_directories /path/dir /path/dir2 750 root:root
create_directories() {
    local -a dirs=("${@:1:$#-2}") 
    local perms="${@: -2:1}"
    local owner="${@: -1:1}"
    local missing_dirs=()
    local dir

    if [[ ! "$perms" =~ ^[0-7]{3}$ ]]; then
        echo "Invalid permissions format: $perms (expected octal, e.g., 755)" >&2
        return "${EXIT_INVALID_ARG}"
    fi

    if [[ ! "$owner" =~ ^[a-zA-Z0-9._-]+(:[a-zA-Z0-9._-]+)?$ ]]; then
        echo "Invalid owner format: $owner (expected user:group or user)" >&2
        return "${EXIT_INVALID_ARG}"
    fi

    for dir in "${dirs[@]}"; do
        [[ -z "$dir" ]] && continue
        if [[ ! -d "$dir" ]]; then
            if ! mkdir -p "$dir" >&2; then
                echo "Cannot create directory '$dir'" >&2
                return "${EXIT_GENERAL_ERROR}" 
            fi
        fi

        current_perms=$(stat -c %a "$dir")
        current_owner=$(stat -c %U:%G "$dir")

        if [[ "$current_owner" != "$owner" ]]; then
            chown "$owner" "$dir" || {
                echo "Failed to set ownership $owner for '$dir'" >&2
                missing_dirs+=($dir)
            }
        fi

        if [[ "$current_perms" != "$perms" ]]; then
            chown "$perms" "$dir" || {
                echo "Failed to set permissions $perms for '$dir'" >&2
                missing_dirs+=($dir)
            }
        fi
    done

    if [[ ${#missing_dirs[@]} -gt 0 ]]; then
        echo "Missing create directories: ${missing_dirs[*]}" >&2
        return "${EXIT_GENERAL_ERROR}"
    fi

    return ${EXIT_SUCCESS}
}

# Экспорт функции
export -f create_directories

return ${EXIT_SUCCESS}