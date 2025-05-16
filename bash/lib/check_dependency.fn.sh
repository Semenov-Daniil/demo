#!/bin/bash

# check_dependency.fn.sh - Функция для проверки наличия зависимостей
# Расположение: bash/lib/check_dependency.fn.sh

set -euo pipefail

# Проверка, что скрипт не запущен напрямую
[[ "${BASH_SOURCE[0]}" == "$0" ]] && {
    echo "This script ('$0') is meant to be sourced"
    exit 1
}

# Функция проверки наличия зависимостей
# check_dependency <dependence> [dependence ...]
check_dependency() {
    local -a missing_deps=()
    local dep cache_file="${DEPS_CACHE}_$(echo "$*" | sha256sum | cut -d' ' -f1)"

    # Проверка кэша (24 часа)
    [[ -f "$cache_file" && $(( $(date +%s) - $(stat -c %Y "$cache_file") )) -lt 86400 ]] && return 0

    for dep in "$@"; do
        [[ -z "$dep" ]] && continue
        dpkg-query -W -f='${Status}' "$dep" 2>/dev/null | grep -q "install ok installed" || {
            echo "Dependency '$dep' not installed"
            missing_deps+=("$dep")
        }
    done

    if [[ ${#missing_deps[@]} -gt 0 ]]; then
        echo "Missing dependencies: ${missing_deps[*]}"
        return 1
    fi

    touch "$cache_file" 2>/dev/null || echo "Warning: Failed to update dependency cache"
    return 0
}

export -f check_dependency
return 0