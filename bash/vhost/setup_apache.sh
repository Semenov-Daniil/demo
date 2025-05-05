#!/bin/bash
# setup_apache.sh - Скрипт для настройки Apache2
# Расположение: bash/vhost/setup_apache.sh

set -euo pipefail

# Проверка, что скрипт не запущен напрямую
if [[ "${BASH_SOURCE[0]}" == "$0" ]]; then
    echo "This script ('$0') is meant to be sourced" >&2
    return 1
fi

# Установка переменных по умолчанию
: "${EXIT_SUCCESS:=0}"
: "${EXIT_GENERAL_ERROR:=1}"
: "${EXIT_VHOST_NOT_INSTALLED:=40}"

# Проверка и запуск Apache2
start_apache_service() {
    for service in "${APACHE_SERVICES[@]}"; do
        if ! systemctl is-active --quiet "$service"; then
            log_message "info" "Starting $service service"
            systemctl enable "$service" && systemctl start "$service" || {
                log_message "error" "Failed to start $service service"
                return ${EXIT_VHOST_NOT_INSTALLED}
            }
        fi
    done
}

# Настройка UFW для Apache2-портов
configure_ufw() {
    if ! command -v ufw >/dev/null; then
        return
    fi
    if ufw status | grep -qw "active"; then
        log_message "info" "UFW is active, checking Apache2 ports"
        for port in "${APACHE_PORTS[@]}"; do
            if ! ufw status numbered | grep -E "${port}\s+ALLOW" >/dev/null; then
                ufw allow "$port" || {
                    log_message "error" "Failed to open port $port in UFW"
                    return ${EXIT_GENERAL_ERROR}
                }
            fi
        done
    fi
}

# Основная логика
log_message "info" "Starting to check Apache settings"

# Проверка наличия Apache2
check_deps "apache2" || {
    log_message "error" "Failed check dependencies"
    return ${EXIT_VHOST_NOT_INSTALLED}
}

check_cmds "a2ensite" || {
    log_message "error" "Command a2ensite not found"
    return ${EXIT_VHOST_NOT_INSTALLED}
}

# Запуск Apache2
start_apache_service || return $?

# Настройка UFW
configure_ufw || return $?

log_message "info" "Apache is configured and running"

return ${EXIT_SUCCESS}