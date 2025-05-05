#!/bin/bash
# config.sh - Локальный конфигурационный файл для скриптов настройки виртуальных хостов Apache2
# Расположение: bash/vhost/config.sh

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
export EXIT_VHOST_NOT_INSTALLED=40
export EXIT_VHOST_CONFIG_FAILED=41
export EXIT_VHOST_ENABLE_FAILED=42
export EXIT_VHOST_DISABLE_FAILED=43
export EXIT_VHOST_DELETE_FAILED=44
export EXIT_VHOST_INVALID_CONFIG=45

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
export VHOST_AVAILABLE_DIR="/etc/apache2/sites-available"
export VHOST_ENABLED_DIR="/etc/apache2/sites-enabled"
export VHOST_LOG_DIR="/var/log/apache2"
export VHOST_LOG_FILE="${VHOST_LOG_DIR}/vhost.log"
export APACHE_SERVICES=("apache2")
export APACHE_PORTS=("80/tcp" "443/tcp")
export VHOST_PERMS="644"
export VHOST_OWNER="root:root"

# Пути к скриптам
export REMOVE_VHOST_SCRIPT="$(dirname "${BASH_SOURCE[0]}")/remove_vhost.fn.sh"

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

# Подключение обновления владельца и прав
source_script "$UPDATE_PERMS_SCRIPT" || {
    echo "Failed to source script $UPDATE_PERMS_SCRIPT" >&2
    exit "${EXIT_GENERAL_ERROR}"
}

# Подключение проверки и настройки Apache2
SETUP_APACHE_SCRIPT="$(dirname "${BASH_SOURCE[0]}")/setup_apache.sh"
source_script "$SETUP_APACHE_SCRIPT" || {
    echo "Script error '$SETUP_APACHE_SCRIPT'" >&2
    exit "${EXIT_GENERAL_ERROR}"
}

return ${EXIT_SUCCESS}