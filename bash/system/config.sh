#!/bin/bash
# config.sh - Локальный конфигурационный файл для скриптов создания/удаления, настройки системных пользователей
# Расположение: bash/system/config.sh

set -euo pipefail

# Проверкa подключения скрипта
[[ "${BASH_SOURCE[0]}" == "$0" ]] && {
    echo "This script ('$0') is meant to be sourced" >&2
    exit 1
}

# Подключение глобального config.sh
GLOBAL_CONFIG="$(realpath $(dirname "${BASH_SOURCE[0]}")/../config.sh)"
source "$GLOBAL_CONFIG" || {
    echo "Failed to source global config '$GLOBAL_CONFIG'" >&2
    return 1
}

# Коды выхода
declare -rx EXIT_FAILED_CREATE_USER=30
declare -rx EXIT_FAILED_DELETE_USER=31

# Logging
[[ "$LOG_FILE" == "$DEFAULT_LOG_FILE" ]] && LOG_FILE="users.log"

# Scripts
declare -rx DELETE_USER="$(realpath $(dirname "${BASH_SOURCE[0]}")/delete_user.sh)"
declare -rx ADD_SAMBA_USER="$(realpath $(dirname "${BASH_SOURCE[0]}")/../samba/add_samba_user.sh)"
declare -rx REMOVE_SAMBA_USER="$(realpath $(dirname "${BASH_SOURCE[0]}")/../samba/remove_samba_user.sh)"
declare -rx CONFIG_SSH="$(realpath $(dirname "${BASH_SOURCE[0]}")/../ssh/config_ssh.sh)"
declare -rx SETUP_WORKSPACE="$(realpath $(dirname "${BASH_SOURCE[0]}")/../chroot/setup_workspace.sh)"
declare -rx REMOVE_WORKSPACE="$(realpath $(dirname "${BASH_SOURCE[0]}")/../chroot/remove_workspace.sh)"

# Lock
declare -rx LOCK_USER_PREF="${LOCK_PREF}_user"

return 0