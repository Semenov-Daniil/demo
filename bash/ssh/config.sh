#!/bin/bash
# config.sh - Локальный конфигурационный файл для скриптов настройки ssh и создания/удаления chroot-окружения
# Расположение: bash/ssh/config.sh

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
declare -rx EXIT_MOUNT_FAILED=10
declare -rx EXIT_CHROOT_INIT_FAILED=11
declare -rx EXIT_SSH_CONFIG_FAILED=12
declare -rx EXIT_SSH_SERVICE=13

# Установка переменных
declare -x DEFAULT_LOG_FILE="ssh.log"
declare -rx CHROOT_DIR="/var/chroot"
declare -rx CHROOT_STUDENTS="${CHROOT_DIR}/students"
declare -rx CHROOT_TEMPLATE="${CHROOT_DIR}/templates"
declare -rx SSH_CONFIG_FILE="/etc/ssh/sshd_config"
declare -rx SSH_BACKUP_CONFIG="/etc/ssh/sshd_config.conf.bak"
declare -rx SSH_CONFIGS_DIR="/etc/ssh/sshd_config.d"
declare -rx SSH_CONFIG_FILE_STUDENTS="${SSH_CONFIGS_DIR}/students.conf"
declare -rx SSH_BACKUP_CONFIG_STUDENTS="${SSH_CONFIGS_DIR}/students.conf.bak"

declare -rx REMOVE_CHROOT="$(dirname "${BASH_SOURCE[0]}")/remove_chroot.fn.sh"
declare -rx SETUP_CONFIG_SSH="$(dirname "${BASH_SOURCE[0]}")/setup_config_ssh.sh"

declare -rx LOCK_SSH_PREF="lock_ssh"
declare -rx LOCK_CHROOT_FILE="${TMP_DIR}/${LOCK_SSH_PREF}_chroot.lock"
declare -rx LOCK_FSTAB_FILE="${TMP_DIR}/${LOCK_SSH_PREF}_fstab.lock"
declare -rx CONFIG_HASH_FILE="${TMP_DIR}/ssh_config_hash"
declare -rx SSH_CONFIG_HASH_MAIN="${TMP_DIR}/ssh_config_hash_main"
declare -rx SSH_CONFIG_HASH_STUDENTS="${TMP_DIR}/ssh_config_hash_student"

declare -rax SYSTEM_DIRS=("usr" "bin" "lib" "lib64")
declare -rax MOUNT_DIRS=()
declare -rax SYSTEM_FILES=("/etc/resolv.conf")
declare -rax MOUNT_FILES=()
declare -rax CHROOT_DIRS=()

generate_user_bash_bashrc() {
    cat <<EOF
declare -rx PS1='\u@\h:\w\\\$ '
declare -rx HOME=/tmp
declare -rx PATH=/bin:/usr/bin
declare -rx PAGER=cat
cd /home
unset CDPATH
EOF
}

generate_match_group_student() {
    cat <<EOF
Match Group ${STUDENT_GROUP}
    ChrootDirectory ${CHROOT_STUDENTS}/%u
    ForceCommand /bin/bash
    X11Forwarding no
    AllowTcpForwarding no
    PasswordAuthentication yes
EOF
}

declare -rx MATCH_GROUP_STUDENT=$(generate_match_group_student)

get_config_hash() {
    echo "${SSH_CONFIG_FILE} ${SSH_CONFIG_FILE_STUDENTS} ${MATCH_GROUP_STUDENT}" | sha256sum | cut -d' ' -f1
}

# Настройка конфигурации SSH
update_config_ssh() {
    local current_hash=$(get_config_hash) || {
        log_message "error" "Failed to compute config hash SSH"
        return ${EXIT_SSH_CONFIG_FAILED}
    }
    local saved_hash=""
    [[ -f "$CONFIG_HASH_FILE" ]] && saved_hash=$(cat "$CONFIG_HASH_FILE") || saved_hash=""
    if [[ "$current_hash" != "$saved_hash" ]]; then
        log_message "info" "Configuration changed or hash file missing"
        source_script "$SETUP_CONFIG_SSH" || return $?
    fi
}

with_lock "${TMP_DIR}/${LOCK_SSH_PREF}_hash.lock" update_config_ssh || return $?

export -f get_config_hash
return ${EXIT_SUCCESS}