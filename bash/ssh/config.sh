#!/bin/bash
# config.sh - Локальный конфигурационный файл для скриптов настройки ssh и создания/удаления chroot-окружения
# Расположение: bash/chroot/config.sh

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
declare -rx EXIT_SSH_CONFIG_FAILED=40
declare -rx EXIT_SSH_NOT_INSTALLED=41
declare -rx EXIT_SSH_SERVICE_FAILED=42
declare -rx EXIT_SSH_START_FAILED=42

# Установка переменных
[[ "$LOG_FILE" == "$DEFAULT_LOG_FILE" ]] && LOG_FILE="ssh.log"

declare -rx CONFIG_FILE="/etc/ssh/sshd_config"
declare -rx CONFIG_DIR="/etc/ssh/sshd_config.d"

return 0