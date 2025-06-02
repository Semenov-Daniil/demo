#!/bin/bash
# config.sh - Локальный конфигурационный файл для скриптов логирования
# Расположение: bash/logging/config.sh

set -euo pipefail

# Проверкa подключения скрипта
[[ "${BASH_SOURCE[0]}" == "$0" ]] && {
    echo "This script ('$0') is meant to be sourced"
    exit 1
}

# Подключение глобального config.sh
[[ -z "${CNT_MAIN_CONFIG:-}" || "$CNT_MAIN_CONFIG" -ne 1 ]] && {
    source "$(dirname "${BASH_SOURCE[0]}")/../config.sh" || {
        echo "Failed to source global config.sh"
        return 1
    }
}

declare -x LOG_RETENTION_DAYS=30
declare -x LOCK_LOG_PREF="lock_log"
declare -rax LOG_LEVELS=("info" "warning" "error" "ok")

return 0