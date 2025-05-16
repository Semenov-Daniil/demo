#!/bin/bash

# config.sh - Локальный конфигурационный файл для скриптов настройки ssh и создания/удаления chroot-окружения
# Расположение: bash/ssh/config.sh

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
export EXIT_MOUNT_FAILED=10
export EXIT_CHROOT_INIT_FAILED=11
export EXIT_SSH_CONFIG_FAILED=12
export EXIT_SSH_SERVICE=13

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
export CHROOT_DIR="/var/chroot"
export CHROOT_STUDENTS="${CHROOT_DIR}/${STUDENT_GROUP}"
export SSH_CONFIG_FILE="/etc/ssh/sshd_config"
export SSH_BACKUP_CONFIG="/etc/ssh/sshd_config.conf.bak"
export SSH_CONFIGS_DIR="/etc/ssh/sshd_config.d"
export STUDENT_CONF_FILE="${SSH_CONFIGS_DIR}/${STUDENT_GROUP}.conf"
export SSH_BACKUP_STUDENT_CONFIG="${SSH_CONFIGS_DIR}/${STUDENT_GROUP}.conf.bak"

export REMOVE_CHROOT="$(dirname "${BASH_SOURCE[0]}")/remove_chroot.fn.sh"
export CHECK_SSH_SERVICES="$(dirname "${BASH_SOURCE[0]}")/check_ssh_services.sh"
export SETUP_SSH_CONFIG="$(dirname "${BASH_SOURCE[0]}")/setup_ssh_config.sh"

export LOCK_SSH_PREF="lock_ssh"
export LOCK_CHROOT_FILE="${TMP_DIR}/${LOCK_SSH_PREF}_chroot.lock"
export LOCK_FSTAB_FILE="${TMP_DIR}/${LOCK_SSH_PREF}_fstab.lock"
export CONFIG_HASH_FILE="${TMP_DIR}/ssh_config_hash"
export SSH_DEPS_CACHE="${TMP_DIR}/ssh_deps_checked"
export SSH_MAIN_CONFIG_HASH="${TMP_DIR}/ssh_main_config_hash"
export SSH_STUDENT_CONFIG_HASH="${TMP_DIR}/ssh_student_config_hash"

export MOUNT_DIRS=("dev" "proc" "usr" "bin" "lib" "lib64" "home")
export MOUNT_FILES=("/etc/resolv.conf" "/etc/hosts" "/etc/nsswitch.conf" "/etc/protocols" "/etc/services")
export CHROOT_BASE_DIRS=("dev" "etc" "home" "bin" "usr" "lib" "lib64" "proc" "tmp")

export BASH_BASHRC_STUDENT=$(cat <<EOF
export PS1='\u@\h:\w\\\$ '
export HOME=/tmp
export PATH=/bin:/usr/bin
export PAGER=cat
cd /home
unset CDPATH
EOF
)

export MATCH_STUDENT=$(cat <<EOF
Match Group ${STUDENT_GROUP}
    ChrootDirectory ${CHROOT_STUDENTS}/%u
    ForceCommand /bin/bash
    X11Forwarding no
    AllowTcpForwarding no
    PasswordAuthentication yes
EOF
)

# Подключение логирования
source_script "$LOGGING_SCRIPT" "$LOG_FILE" || {
    echo "Failed to source script $LOGGING_SCRIPT" >&2
    exit "${EXIT_GENERAL_ERROR}"
}

# Подключение вспомогательных скриптов/функций
source_script "${LIB_DIR}/common.sh" || exit $?

# Настройка конфигурации SSH
source_script "${SETUP_SSH_CONFIG}" || exit $?

if [[ -f "$CONFIG_HASH_FILE" ]]; then
    current_hash=$(md5sum "$SSH_CONFIG_FILE" "$SSH_CONFIGS_DIR"/*.conf 2>/dev/null | md5sum | cut -d' ' -f1)
    saved_hash=$(cat "$CONFIG_HASH_FILE")
    if [[ "$current_hash" != "$saved_hash" ]]; then
        source_script "$SETUP_SSH_CONFIG" || {
            log_message "error" "Failed to setup SSH configuration"
            exit ${EXIT_SSH_CONFIG_FAILED}
        }
    fi
else
    source_script "$SETUP_SSH_CONFIG" || {
        log_message "error" "Failed to setup SSH configuration"
        exit ${EXIT_SSH_CONFIG_FAILED}
    }
fi

return ${EXIT_SUCCESS}