#!/bin/bash

# config.sh - Локальный конфигурационный файл для скриптов настройки Samba
# Расположение: bash/samba/config.sh

set -euo pipefail

# Проверка, что скрипт не запущен напрямую
[[ "${BASH_SOURCE[0]}" == "$0" ]] && {
    echo "This script ('$0') is meant to be sourced"
    exit 1
}

# Проверка root-прав
[[ $EUID -ne 0 ]] || {
    echo "This operation requires root privileges"
    exit 1
}

# Подключение глобального config.sh
source "$(dirname "${BASH_SOURCE[0]}")/../config.sh" || {
    echo "Failed to source global config.sh"
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
export LOG_FILE="$(basename "${BASH_SOURCE[1]}" .sh).log"

while [[ $# -gt 0 ]]; do
    case "$1" in
        --log=*) LOG_FILE="${1#--log=}"; shift ;;
        *) ARGS+=("$1"); shift ;;
    esac
done

export ARGS

# Установка переменных
export SAMBA_CONFIG_FILE="/etc/samba/smb.conf"
export SAMBA_BACKUP_CONFIG="/etc/samba/smb.conf.bak"
export SAMBA_LOG_DIR="/var/log/samba"
export SAMBA_LOG_FILE="${SAMBA_LOG_DIR}/samba.log"
export SAMBA_TEMP_CONFIG="/tmp/smb.conf.tmp"
export SAMBA_DEPS_CACHE="${TMP_DIR}/samba_deps_checked"
export CONFIG_HASH_FILE="${TMP_DIR}/samba_config_hash"
export RELOAD_NEEDED_FILE="${TMP_DIR}/samba_reload_needed"
export SETUP_CONFIG_SAMBA_SCRIPT="$(dirname "${BASH_SOURCE[0]}")/setup_config_samba.sh"
export SAMBA_PORTS=("137/udp" "138/udp" "139/tcp" "445/tcp")
export SAMBA_GLOBAL_PARAMS=(
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
export USER_SHARE=$(cat <<EOF
[%U]
   path = ${STUDENTS_DIR}/%U
   valid users = %U
   read only = no
   browsable = yes
   create mask = 0775
   force create mode = 0775
   directory mask = 2775
   force directory mode = 2775
   force user = %U
   force group = ${SITE_GROUP}
EOF
)
export SAMBA_REQUIRED_COMMAND=("pdbedit" "smbpasswd" "smbcontrol")
export LOCK_SAMBA_PREF="lock_samba"
export LOCK_SAMBA_FILE="${TMP_DIR}/${LOCK_SAMBA_PREF}_global.lock"
export REMOVE_SAMBA_USER_FN="$(dirname "${BASH_SOURCE[0]}")/remove_samba_user.fn.sh"

# Подключение логирования
source_script "$LOGGING_SCRIPT" "$LOG_FILE" || {
    echo "Failed to source script $LOGGING_SCRIPT" >&2
    exit "${EXIT_GENERAL_ERROR}"
}

# Подключение вспомогательных скриптов/функций
source_script "${LIB_DIR}/common.sh" || exit $?

# Настройка конфигурации Samba
if [[ -f "$CONFIG_HASH_FILE" ]]; then
    current_hash=$(md5sum "$SAMBA_CONFIG_FILE" | cut -d' ' -f1)
    saved_hash=$(cat "$CONFIG_HASH_FILE")
    if [[ "$current_hash" != "$saved_hash" ]]; then
        source_script "$SETUP_CONFIG_SAMBA_SCRIPT" || {
            log_message "error" "Failed to setup Samba configuration"
            exit ${EXIT_SAMBA_CONFIG_FAILED}
        }
    fi
else
    source_script "$SETUP_CONFIG_SAMBA_SCRIPT" || {
        log_message "error" "Failed to setup Samba configuration"
        exit ${EXIT_SAMBA_CONFIG_FAILED}
    }
fi

return ${EXIT_SUCCESS}