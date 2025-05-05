#!/bin/bash

# config.sh - Локальный конфигурационный файл для скриптов настройки Samba
# Расположение: bash/samba/config.sh

set -euo pipefail

# Проверка, что скрипт не запущен напрямую
if [[ "${BASH_SOURCE[0]}" == "$0" ]]; then
    echo "This script ('$0') is meant to be sourced" >&2
    exit 1
fi

# Проверка root-прав
if [[ $EUID -ne 0 ]]; then
    echo "This operation requires root privileges" >&2
    exit 1
fi

# Подключение глобального config.sh
GLOBAL_CONFIG="$(dirname "${BASH_SOURCE[0]}")/../config.sh"
source "$GLOBAL_CONFIG" || {
    echo "Failed to source script $GLOBAL_CONFIG" >&2
    exit 1
}

# Коды выхода
export EXIT_SAMBA_NOT_INSTALLED=20
export EXIT_SAMBA_CONFIG_FAILED=21
export EXIT_SAMBA_SHARE_FAILED=22
export EXIT_SAMBA_TEST_FAILED=23
export EXIT_SAMBA_UPDATE_FAILED=24
export EXIT_SAMBA_USER_DELETE_FAILED=25
export EXIT_SAMBA_SERVICE_FAILED=26
export EXIT_SAMBA_USER_ADD_FAILED=27

# Парсинг аргументов
declare -a ARGS=()
LOG_FILE="$(basename "${BASH_SOURCE[1]}" .sh).log"
while [[ $# -gt 0 ]]; do
    case "$1" in
        --log=*)
            LOG_FILE="${1#--log=}"
            shift
            ;;
        *)
            ARGS+=("$1")
            shift
            ;;
    esac
done
export ARGS

# Переменные
export SAMBA_CONFIG_FILE="/etc/samba/smb.conf"
export SAMBA_BACKUP_CONFIG="/etc/samba/smb.conf.bak"
export SAMBA_LOG_DIR="/var/log/samba"
export SAMBA_LOG_FILE="${SAMBA_LOG_DIR}/samba.log"
export SAMBA_TEMP_CONFIG="/tmp/smb.conf.tmp"

SAMBA_SERVICES=(
    "smbd"
    "nmbd"
)

SAMBA_PORTS=(
    "137/udp"
    "138/udp"
    "139/tcp"
    "445/tcp"
)

SAMBA_GLOBAL_PARAMS=(
    "workgroup = WORKGROUP"
    "server string = %h server (Samba, Ubuntu)"
    "server role = standalone server"
    "security = user"
    "map to guest = never"
    "smb encrypt = required"
    "min protocol = SMB3"
    "log file = ${SAMBA_LOG_FILE}"
    "max log size = 1000"
)

# Пути к скриптам
export DELETE_USER_SAMBA="$(dirname "${BASH_SOURCE[0]}")/delete_user_samba.sh"

# Подключение логирования
source_script "$LOGGING_SCRIPT" "$LOG_FILE" || {
    echo "Failed to source script $LOGGING_SCRIPT" >&2
    exit "${EXIT_GENERAL_ERROR}"
}

# Подключение проверки зависимостей
source_script "$CHECK_DEPS_SCRIPT" || {
    echo "Failed to source script $CHECK_DEPS_SCRIPT" >&2
    exit "${EXIT_GENERAL_ERROR}"
}

# Подключение проверки команд
source_script "$CHECK_CMDS_SCRIPT" || {
    echo "Failed to source script $CHECK_CMDS_SCRIPT" >&2
    exit "${EXIT_GENERAL_ERROR}"
}

# Подключение создания директорий
source_script "$CREATE_DIRS_SCRIPT" || {
    echo "Failed to source script $CREATE_DIRS_SCRIPT" >&2
    exit "${EXIT_GENERAL_ERROR}"
}

# Подключение обновления владельца и прав файлов/директорий
source_script "$UPDATE_PERMS_SCRIPT" || {
    echo "Failed to source script $UPDATE_PERMS_SCRIPT" >&2
    exit "${EXIT_GENERAL_ERROR}"
}

# Подключение настройки Samba
SETUP_SAMBA_SCRIPT="$(dirname "${BASH_SOURCE[0]}")/setup_samba.sh"
source_script "$SETUP_SAMBA_SCRIPT" || {
    echo "Failed to source script $SETUP_SAMBA_SCRIPT" >&2
    exit "${EXIT_GENERAL_ERROR}"
}

return ${EXIT_SUCCESS}