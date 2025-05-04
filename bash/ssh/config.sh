#!/bin/bash

# config.sh - Локальный конфигурационный файл для скриптов ssh
# Расположение: bash/ssh/config.sh

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
export CHROOT_DIR="/var/chroot"
export CHROOT_STUDENTS="${CHROOT_DIR}/${STUDENT_GROUP}"
export SSH_CONFIG_FILE="/etc/ssh/sshd_config"
export SSH_CONFIGS_DIR="/etc/ssh/sshd_config.d"
export STUDENT_CONF_FILE="${SSH_CONFIGS_DIR}/${STUDENT_GROUP}.conf"

export MOUNT_DIRS=(
    "dev"
    "proc"
    "usr"
    "bin"
    "lib"
    "lib64"
    "home"
)
export MOUNT_FILES=(
    "/etc/resolv.conf"
    "/etc/nsswitch.conf"
)
export CHROOT_BASE_DIRS=(
    "dev"
    "etc"
    "home"
    "usr"
    "bin"
    "lib"
    "lib64"
    "proc"
    "tmp"
)

# Коды выхода
export EXIT_MOUNT_FAILED=6
export EXIT_CHROOT_INIT_FAILED=7
export EXIT_SSH_CONFIG_FAILED=8
export EXIT_SSH_SERVICE=9

# Пути к скриптам
export REMOVE_CHROOT="$(dirname "${BASH_SOURCE[0]}")/remove_chroot.sh"

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

# Подключение проверки и настройки SSH
SETUP_SSH_SCRIPT="$(dirname "${BASH_SOURCE[0]}")/setup_ssh.sh"
source_script "$SETUP_SSH_SCRIPT" || {
    echo "Failed to source script $SETUP_SSH_SCRIPT" >&2
    exit "${EXIT_GENERAL_ERROR}"
}

return ${EXIT_SUCCESS}