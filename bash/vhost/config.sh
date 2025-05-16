#!/bin/bash

# config.sh - Локальный конфигурационный файл для скриптов настройки виртуальных хостов Apache2
# Расположение: bash/vhost/config.sh

set -euo pipefail

# Проверка, что скрипт не запущен напрямую
[[ "${BASH_SOURCE[0]}" == "$0" ]] && {
    echo "This script ('$0') is meant to be sourced"
    exit 1
}

# Подключение глобального config.sh
source "$(dirname "${BASH_SOURCE[0]}")/../config.sh" || {
    echo "Failed to source global config.sh"
    exit 1
}
# Коды выхода
export EXIT_VHOST_NOT_INSTALLED=40
export EXIT_VHOST_CONFIG_FAILED=41
export EXIT_VHOST_ENABLE_FAILED=42
export EXIT_VHOST_DISABLE_FAILED=43
export EXIT_VHOST_DELETE_FAILED=44
export EXIT_VHOST_INVALID_CONFIG=45
export EXIT_APACHE_SERVICE_FAILED=46

# Парсинг аргументов
declare -a ARGS=()
export LOG_FILE="virtual_host.log"

while [[ $# -gt 0 ]]; do
    case "$1" in
        --log=*) LOG_FILE="${1#--log=}"; shift ;;
        *) ARGS+=("$1"); shift ;;
    esac
done

export ARGS

# Установка переменных
export VHOST_AVAILABLE_DIR="/etc/apache2/sites-available"
export VHOST_ENABLED_DIR="/etc/apache2/sites-enabled"
export VHOST_LOG_DIR="/var/log/apache2"
export VHOST_LOG_FILE="${VHOST_LOG_DIR}/vhost.log"
export APACHE_PORTS=("80/tcp" "443/tcp")
export RELOAD_NEEDED_FILE="${TMP_DIR}/apache_reload_needed"
export LOCK_VHOST_PREF="lock_vhost"
export APACHE_DEPS=("apache2")
export APACHE_CMDS=("a2ensite" "a2dissite" "apache2ctl")

# Пути к скриптам
export REMOVE_VHOST="$(dirname "${BASH_SOURCE[0]}")/remove_vhost.fn.sh"

# Подключение логирования
source_script "$LOGGING_SCRIPT" "$LOG_FILE" || {
    echo "Failed to source script $LOGGING_SCRIPT" >&2
    exit "${EXIT_GENERAL_ERROR}"
}

# Подключение вспомогательных скриптов/функций
source_script "${LIB_DIR}/common.sh" || exit $?

# Создание директории логирования apache
create_directories "$VHOST_LOG_DIR" "755" "root:root" || {
    log_message "error" "Failed to create directory: ${VHOST_LOG_DIR}"
    exit ${EXIT_GENERAL_ERROR}
}

return ${EXIT_SUCCESS}