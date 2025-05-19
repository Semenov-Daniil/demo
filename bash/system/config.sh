#!/bin/bash
# config.sh - Локальный конфигурационный файл для скриптов создания/удаления, настройки системных пользователей
# Расположение: bash/system/config.sh

set -euo pipefail

# Проверкa подключения скрипта
[[ "${BASH_SOURCE[0]}" == "$0" ]] && {
    echo "This script ('$0') is meant to be sourced" >&2
    exit 1
}

# Файл логирования
declare -x DEFAULT_LOG_FILE="samba.log"

# Подключение глобального config.sh
source "$(dirname "${BASH_SOURCE[0]}")/../config.sh" || {
    echo "Failed to source global config.sh" >&2
    return 1
}

# Коды выхода
export EXIT_FAILED_CREATE_USER=30
export EXIT_FAILED_DELETE_USER=31

# Установка переменных
export LOCK_USER_PREF="${LOCK_PREF}_user"

return ${EXIT_SUCCESS}