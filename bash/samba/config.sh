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
source "$(dirname "${BASH_SOURCE[0]}")/../config.sh" || {
    echo "Failed to source global config.sh" >&2
    return 1
}

# Коды выхода
declare -rx EXIT_SAMBA_NOT_INSTALLED=20
declare -rx EXIT_SAMBA_CONFIG_FAILED=21
declare -rx EXIT_SAMBA_SHARE_FAILED=22
declare -rx EXIT_SAMBA_USER_DELETE_FAILED=23
declare -rx EXIT_SAMBA_USER_ADD_FAILED=24
declare -rx EXIT_SAMBA_SERVICE_FAILED=25

# Установка переменных
declare -x DEFAULT_LOG_FILE="samba.log"
declare -rx SAMBA_CONFIG_FILE="/etc/samba/smb.conf"
declare -rx SAMBA_BACKUP_CONFIG="/etc/samba/smb.conf.bak"
declare -rx SAMBA_TEMP_CONFIG="${TMP_DIR}/smb.conf.tmp"
declare -rx CONFIG_HASH_FILE="${TMP_DIR}/samba_config_hash"
declare -rx SETUP_CONFIG_SAMBA_SCRIPT="$(dirname "${BASH_SOURCE[0]}")/setup_config_samba.sh"
declare -rx SAMBA_PORTS=("137/udp" "138/udp" "139/tcp" "445/tcp")
declare -rax SAMBA_GLOBAL_PARAMS=(
    "workgroup = WORKGROUP"
    "server string = %h server (Samba, Ubuntu)"
    "server role = standalone server"
    "security = user"
    "map to guest = never"
    "smb encrypt = required"
    "min protocol = SMB3"
    "log file = /var/log/samba/samba.log"
    "max log size = 1000"
)
generate_user_share() {
    cat <<EOF
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
}
declare -rx USER_SHARE=$(generate_user_share)
declare -rax SAMBA_REQUIRED_COMMAND=("pdbedit" "smbpasswd" "smbcontrol")
declare -rax SAMBA_REQUIRED_DEPENCY=("samba" "samba-common-bin")
declare -rax SAMBA_SERVICES=("smbd" "nmbd")
declare -rx LOCK_SAMBA_PREF="${LOCK_PREF}_samba"
declare -rx LOCK_SAMBA_FILE="${TMP_DIR}/${LOCK_SAMBA_PREF}_global.lock"
declare -rx REMOVE_SAMBA_USER_FN="$(dirname "${BASH_SOURCE[0]}")/remove_samba_user.fn.sh"

get_config_hash() {
    echo "${SAMBA_CONFIG_FILE} ${SAMBA_GLOBAL_PARAMS} ${USER_SHARE}" | cksum | cut -d' ' -f1
}

# Настройка конфигурации Samba
update_config_samba() {
    local current_hash=$(get_config_hash) || {
        log_message "error" "Failed to compute config hash Samba"
        return ${EXIT_SAMBA_CONFIG_FAILED}
    }
    local saved_hash=""
    [[ -f "$CONFIG_HASH_FILE" ]] && saved_hash=$(cat "$CONFIG_HASH_FILE") || saved_hash=""
    if [[ "$current_hash" != "$saved_hash" ]]; then
        log_message "info" "Configuration changed or hash file missing"
        source_script "$SETUP_CONFIG_SAMBA_SCRIPT" || return $?
    fi
}

with_lock "${TMP_DIR}/${LOCK_SAMBA_PREF}_hash.lock" update_config_samba || return $?

export -f get_config_hash
return ${EXIT_SUCCESS}