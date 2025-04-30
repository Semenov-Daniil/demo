#!/bin/bash

# Локальный файл конфигурации для скриптов SSH
# Подключает главный конфиг и логирование

# Проверка, что скрипт не вызывается напрямую
if [[ "${BASH_SOURCE[0]}" == "$0" ]]; then
    echo "[ERROR]: This script ('$0') is meant to be sourced, not executed directly" >&2
    exit 1
fi

# Подключение общей конфигурации
CONFIG_SCRIPT="$(dirname "${BASH_SOURCE[0]}")/../config.sh"
if [[ ! -f "$CONFIG_SCRIPT" ]]; then
    echo "[ERROR]: Config script '$CONFIG_SCRIPT' not found" >&2
    return 3
fi
source "$CONFIG_SCRIPT" "$@"
if [[ $? -ne 0 ]]; then
    echo "[ERROR]: Failed to source config script '$CONFIG_SCRIPT'" >&2
    return 3
fi

# Проверка наличия функции source_script
if ! declare -F source_script >/dev/null; then
    echo "$LOG_ERROR: Function 'source_script' not defined after sourcing '$CONFIG_SCRIPT'" >&2
    return $ERR_FILE_NOT_FOUND
fi

# Константы переменных
declare -r CHROOT_DIR="/var/chroot"
declare -r CHROOT_TEMPLATE="${CHROOT_DIR}/template"
declare -r CHROOT_STUDENTS="${CHROOT_DIR}/${STUDENTS_GROUP}"
declare -r SSH_CONFIG_FILE="/etc/ssh/sshd_config"
declare -r SSH_CONFIGS_DIR="/etc/ssh/sshd_config.d"
declare -r STUDENT_CONF_FILE="${SSH_CONFIGS_DIR}/${STUDENTS_GROUP}.conf"

# Коды выхода
declare -r ERR_MOUNT_FAILED=4
declare -r ERR_CHROOT_INIT_FAILED=5
declare -r ERR_SSH_CONFIG_FAILED=6
declare -r ERR_FSTAB_FAILED=7
declare -r ERR_INVALID_USERNAME=8

# Подключение логирования
DEFAULT_LOG="logs/$(basename "${BASH_SOURCE[1]}" .sh).log"
source_script "$LOGGING_SCRIPT" "$@"
if [[ $? -ne 0 ]]; then
    echo "$LOG_ERROR: Failed to source script '$LOGGING_SCRIPT'"
    return $ERR_GENERAL
fi

# Проверка наличия функции log
if ! declare -F log >/dev/null; then
    echo "$LOG_ERROR: Logging function 'log' not defined after sourcing '$LOGGING_SCRIPT'" >&2
    return $ERR_FILE_NOT_FOUND
fi

# Подключение проверки root-прав
source_script "$CHECK_ROOT_SCRIPT" "$@"
if [[ $? -ne 0 ]]; then
    log "$LOG_ERROR: Failed to source script '$CHECK_ROOT_SCRIPT'" >&2
    return $ERR_FILE_NOT_FOUND
fi

# Проверка наличия функции check_root
if ! declare -F check_root >/dev/null; then
    log "$LOG_ERROR: Function 'check_root' not defined after sourcing '$CHECK_ROOT_SCRIPT'" >&2
    return $ERR_FILE_NOT_FOUND
fi

return 0