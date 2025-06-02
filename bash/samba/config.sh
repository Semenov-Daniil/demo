#!/bin/bash
# config.sh - Локальный конфигурационный файл для скриптов настройки Samba
# Расположение: bash/samba/config.sh

set -euo pipefail

# Проверкa подключения скрипта
[[ "${BASH_SOURCE[0]}" == "$0" ]] && {
    echo "This script ('$0') is meant to be sourced" >&2
    exit 1
}

# Подключение глобального config.sh
GLOBAL_CONFIG="$(dirname "${BASH_SOURCE[0]}")/../config.sh"
source "$GLOBAL_CONFIG" || {
    echo "Failed to source '$GLOBAL_CONFIG'" >&2
    return 1
}

# Коды выхода
declare -rx EXIT_SAMBA_NOT_INSTALLED=20
declare -rx EXIT_SAMBA_CONFIG_FAILED=21
declare -rx EXIT_DELETE_SAMBA_USER_FAILED=23
declare -rx EXIT_SAMBA_SERVICE_FAILED=25
declare -rx EXIT_SAMBA_START_FAILED=26
declare -rx EXIT_ADD_SAMBA_USER_FAILED=26

# Logging
[[ "$LOG_FILE" == "${DEFAULT_LOG_FILE:-}" ]] && LOG_FILE="samba.log"

# Scripts
declare -rx CONFIG_SAMBE="$(dirname "${BASH_SOURCE[0]}")/config_samba.sh"
declare -rx REMOVE_USER="$(dirname "${BASH_SOURCE[0]}")/remove_samba_user.sh"

# Lock
declare -rx LOCK_SAMBA_PREF="${LOCK_PREF}_samba"

return 0