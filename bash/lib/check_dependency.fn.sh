#!/bin/bash
# check_dependency.fn.sh - Скрипт экспортирующий функцию проверки наличия зависимостей
# Расположение: bash/lib/check_dependency.fn.sh

set -euo pipefail

# Проверка наличия зависимостей
# check_dependency <dependence> [dependence ...]
check_dependency() {
    local -a missing_deps=()
    local dep

    for dep in "$@"; do
        [[ -z "$dep" ]] && continue
        dpkg-query -W -f='${Status}' "$dep" 2>/dev/null | grep -q "install ok installed" || {
            echo "Dependency '$dep' not installed"
            missing_deps+=("$dep")
        }
    done

    [[ ${#missing_deps[@]} -gt 0 ]] && {
        echo "Missing dependencies: ${missing_deps[*]}"
        return 1
    }

    return 0
}

export -f check_dependency
return 0