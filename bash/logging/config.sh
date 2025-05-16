#!/bin/bash
# config.sh - Локальный конфигурационный файл для скриптов логирования
# Расположение: bash/logging/config.sh

set -euo pipefail

source "$(dirname "${BASH_SOURCE[0]}")/../config.sh" || {
    echo "Failed to source global config.sh"
    exit 1
}

declare -x LOG_RETENTION_DAYS=30
declare -x LOCK_LOG_PREF="lock_log"
declare -ax LOG_LEVELS=("info" "warning" "error")

return ${EXIT_SUCCESS}