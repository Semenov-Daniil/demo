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
GLOBAL_CONFIG="$(realpath $(dirname "${BASH_SOURCE[0]}")/../config.sh)"
source "$GLOBAL_CONFIG" || {
    echo "Failed to source global config '$GLOBAL_CONFIG'" >&2
    return 1
}

# Коды выхода
declare -rx EXIT_APACHE_NOT_INSTALLED=40
declare -rx EXIT_VHOST_CONFIG_FAILED=41
declare -rx EXIT_VHOST_ENABLE_FAILED=42
declare -rx EXIT_VHOST_DISABLE_FAILED=43
declare -rx EXIT_VHOST_DELETE_FAILED=44
declare -rx EXIT_VHOST_INVALID_CONFIG=45
declare -rx EXIT_APACHE_SERVICE_FAILED=46
declare -rx EXIT_RELOAD_APACHE_FAILED=47

# Logging
[[ "$LOG_FILE" == "${DEFAULT_LOG_FILE:-}" ]] && LOG_FILE="vhost.log"

# Установка переменных
declare -rx VHOST_AVAILABLE="/etc/apache2/sites-available"
declare -rx VHOST_ENABLED="/etc/apache2/sites-enabled"

# Lock
declare -rx LOCK_VHOST_PREF="lock_vhost"
declare -rx LOCK_GLOBAL_VHOST="lock_vhost_global"

# Scripts
declare -rx REMOVE_VHOST="$(dirname "${BASH_SOURCE[0]}")/remove_vhost.sh"
declare -rx ENABLE_VHOST="$(dirname "${BASH_SOURCE[0]}")/enable_vhost.sh"
declare -rx DISABLE_VHOST="$(dirname "${BASH_SOURCE[0]}")/disable_vhost.sh"

return 0