#!/bin/bash
# common.sh - Скрипт подключения вспомогательных скриптов/функций
# Расположение: bash/lib/common.sh

set -euo pipefail

# Проверкa подключения скрипта
[[ "${BASH_SOURCE[0]}" == "$0" ]] && {
    echo "This script ('$0') is meant to be sourced"
    return 1
}

SCRIPTS=(
    "$(dirname "${BASH_SOURCE[0]}")/check_commands.fn.sh"
    "$(dirname "${BASH_SOURCE[0]}")/check_dependency.fn.sh"
    "$(dirname "${BASH_SOURCE[0]}")/create_directories.fn.sh"
    "$(dirname "${BASH_SOURCE[0]}")/with_lock.fn.sh"
    "$(dirname "${BASH_SOURCE[0]}")/update_permissions.fn.sh"
)

for script in ${SCRIPTS[@]}; do
    source ${script} || {
        echo "Failed to source supporting script '${script}'"
        return 1
    }
done

return 0