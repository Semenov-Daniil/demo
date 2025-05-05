#!/bin/bash
# remove_vhost.sh - Функция для удаления виртуального хоста Apache2
# Расположение: bash/vhost/remove_vhost.sh

set -euo pipefail

# Проверка, что скрипт не запущен напрямую
if [[ "${BASH_SOURCE[0]}" == "$0" ]]; then
    echo "This script ('$0') is meant to be sourced" >&2
    exit 1
fi

# Установка переменных по умолчанию
: "${VHOST_AVAILABLE_DIR:=/etc/apache2/sites-available}"
: "${VHOST_ENABLED_DIR:=/etc/apache2/sites-enabled}"
: "${EXIT_SUCCESS:=0}"
: "${EXIT_GENERAL_ERROR:=1}"
: "${EXIT_INVALID_ARG:=5}"
: "${EXIT_VHOST_NOT_INSTALLED:=40}"
: "${EXIT_VHOST_DELETE_FAILED:=44}"

# Основная функция удаления виртуального хоста
# remove_vhost vhost_name
remove_vhost() {
    local vhost_name="$1"

    if [[ ! "$vhost_name" =~ ^[a-zA-Z0-9._-]+$ ]]; then
        log_message "error" "Invalid virtual host name: $vhost_name"
        return ${EXIT_INVALID_ARG}
    fi

    local vhost_file="${VHOST_AVAILABLE_DIR}/${vhost_name}.conf"
    local vhost_enabled_file="${VHOST_ENABLED_DIR}/${vhost_name}.conf"

    # Проверка, существует ли файл конфигурации
    if [[ ! -f "$vhost_file" ]]; then
        log_message "info" "Virtual host configuration '$vhost_file' does not exist"
        return ${EXIT_SUCCESS}
    fi

    log_message "info" "Starting virtual host removal for $vhost_name"

    # Отключение виртуального хоста, если он активирован
    if [[ -f "$vhost_enabled_file" ]]; then
        if command -v a2dissite >/dev/null 2>&1; then
            a2dissite "$vhost_name" >/dev/null 2>>"$LOG_FILE" || {
                log_message "error" "Failed to disable virtual host $vhost_name"
                return ${EXIT_VHOST_DELETE_FAILED}
            }
        else
            log_message "error" "Command a2dissite not found"
            return ${EXIT_VHOST_NOT_INSTALLED}
        fi
    fi

    # Удаление файла конфигурации
    rm -f "$vhost_file" || {
        log_message "error" "Failed to delete configuration file $vhost_file"
        return ${EXIT_VHOST_DELETE_FAILED}
    }

    # Перезагрузка Apache2
    systemctl reload "apache2" >/dev/null 2>>"$LOG_FILE" || {
        log_message "error" "Failed to reload Apache2 service"
        return ${EXIT_VHOST_DELETE_FAILED}
    }

    log_message "info" "Virtual host $vhost_name removed successfully"
    return ${EXIT_SUCCESS}
}

# Экспорт функций
export -f remove_vhost

return ${EXIT_SUCCESS}