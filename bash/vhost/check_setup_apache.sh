#!/bin/bash

# check_setup_apache.sh - Скрипт проверки настройки Apache2
# Расположение: bash/vhost/check_setup_apache.sh

set -euo pipefail

# Подключение логального config.sh
LOCAL_CONFIG="$(realpath $(dirname "${BASH_SOURCE[0]}")/config.sh)"
source "$LOCAL_CONFIG" || {
    echo "Failed to source local config '$LOCAL_CONFIG'" >&2
    exit 1
}

declare -rax APACHE_SERVICES=("apache2")
declare -rax APACHE_COMMAND=("a2ensite" "a2dissite" "apache2ctl")
declare -rax APACHE_PORTS=("80/tcp" "443/tcp")
declare -rx SERVICE_START_TIMEOUT=5

# Проверка и запуск Apache2
start_apache_service() {
    local service
    for service in "${APACHE_SERVICES[@]}"; do
        systemctl is-active --quiet "$service" && continue
        systemctl enable "$service" && systemctl start "$service" >/dev/null 2>&1 || {
            log_message "error" "Failed to start service $service"
            return "$EXIT_APACHE_SERVICE_FAILED"
        }

        local start_time=$SECONDS
        while ! systemctl is-active --quiet "$service"; do
            (( SECONDS - start_time > SERVICE_START_TIMEOUT )) && {
                log_message "error" "Service '$service' failed to start within ${SERVICE_START_TIMEOUT}s"
                return "$EXIT_APACHE_SERVICE_FAILED"
            }
            sleep 0.01
        done
    done

    log_message "info" "All Apache services are running"
    return 0
}

# Настройка UFW для Apache2-портов
configure_ufw() {
    command -v ufw >/dev/null || { log_message "warning" "UFW not installed, skipping"; return 0; }
    ufw status verbose | grep -q "Status: active" || { log_message "info" "UFW is inactive, skipping"; return 0; }

    local port
    for port in "${APACHE_PORTS[@]}"; do
        ufw status verbose | grep -qw "$port.*ALLOW" || {
            ufw allow "$port" >/dev/null 2>&1 || {
                log_message "error" "Failed to open port '$port' in UFW"
                return "$EXIT_GENERAL_ERROR"
            }
        }
    done

    ufw reload || {
        log_message "error" "Failed to restart UFW"
        return "$EXIT_GENERAL_ERROR"
    }

    log_message "info" "All UFW ports for Apache are running"
    return 0
}

# Проверка зависимостей и команд с кэшированием
check_apache_dependencies() {
    check_dependency "${APACHE_SERVICES[@]}" || return $?
    check_commands "${APACHE_COMMAND[@]}" || return $?
    log_message "info" "All Apache dependencies are installed"
    return 0
}

start_apache_service || exit $?
configure_ufw || exit $?
check_apache_dependencies || exit $?

exit 0