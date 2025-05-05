#!/bin/bash
# check_deps.sh - Функция для проверки наличия зависимостей
# Расположение: bash/lib/check_deps.sh

set -euo pipefail

# Проверка, что скрипт не запущен напрямую
if [[ "${BASH_SOURCE[0]}" == "$0" ]]; then
    echo "This script ('$0') is meant to be sourced" >&2
    exit 1
fi

# Установка переменных по умолчанию
: "${EXIT_SUCCESS:=0}"
: "${EXIT_NO_DEPENDENCY:=1}"

# Проверка наличия зависимостей
# check_deps grep tar
check_deps() {
    local missing_deps=()
    local dep

    for dep in "$@"; do
        [[ -z "$dep" ]] && continue
        if ! dpkg-query -W -f='${Status}' "$dep" 2>/dev/null | grep -q "install ok installed"; then
            echo "Dependency '$dep' is not installed" >&2
            missing_deps+=("$dep")
        fi
    done

    if [[ ${#missing_deps[@]} -gt 0 ]]; then
        echo "Missing dependencies: ${missing_deps[*]}" >&2
        return "${EXIT_NO_DEPENDENCY}"
    fi

    return ${EXIT_SUCCESS}
}

# Экспорт функции
export -f check_deps

return ${EXIT_SUCCESS}