#!/bin/bash

# Файл конфигурации для скриптов
# Определяет константы, пути и коды выхода

# Проверка, что скрипт не вызывается напрямую
if [[ "${BASH_SOURCE[0]}" == "$0" ]]; then
    echo "Error: This script ('$0') is meant to be sourced, not executed directly" >&2
    exit 1
fi

# Константы переменных
declare -r SITE_DIR="/var/www/demo"
declare -r SITE_USER="www-data"
declare -r SITE_GROUP="www-data"
declare -r LOGGING_SCRIPT="${SITE_DIR}/bash/utilites/logging.sh"
declare -r STUDENTS_DIR="students"
declare -r STUDENTS_HOME="${SITE_DIR}/${STUDENTS_DIR}"
declare -r STUDENTS_GROUP="students"
declare -r CHROOT_DIR="/var/chroot"
declare -r CHROOTS_HOME="${CHROOT_DIR}/home"
declare -r SSH_CONFIG_MAIN="/etc/ssh/sshd_config"
declare -r SSH_CONFIG_DIR="/etc/ssh/sshd_config.d"
declare -r STUDENT_CONF_FILE="${SSH_CONFIG_DIR}/${STUDENTS_GROUP}.conf"

# Стандартные коды выхода
declare -r ERR_GENERAL=1
declare -r ERR_ROOT_REQUIRED=2
declare -r ERR_FILE_NOT_FOUND=3
declare -r ERR_MOUNT_FAILED=4
declare -r ERR_CHROOT_INIT_FAILED=5
declare -r ERR_SSH_CONFIG_FAILED=6
declare -r ERR_FSTAB_FAILED=7
declare -r ERR_INVALID_USERNAME=8
