#!/bin/bash

# check_setup_apache.sh - Скрипт проверки настройки Apache2
# Расположение: bash/vhost/check_setup_apache.sh

set -euo pipefail

# Подключение локального config.sh
source "$(dirname "${BASH_SOURCE[0]}")/config.sh" || {
    echo "Failed to source local config.sh"
    exit 1
}

# Проверка и запуск Apache2
start_apache_service() {
    for service in "${APACHE_DEPS[@]}"; do
        systemctl is-active --quiet "$service" && continue
        systemctl enable "$service" && systemctl start "$service" || {
            log_message "error" "Failed to start $service"
            exit ${EXIT_VHOST_NOT_INSTALLED}
        }
        sleep 1
        systemctl is-active --quiet "$service" || {
            log_message "error" "$service failed to start"
            exit ${EXIT_VHOST_NOT_INSTALLED}
        }
    done
}

# Настройка UFW для Apache2-портов
configure_ufw() {
    command -v ufw >/dev/null || { log_message "warning" "UFW not installed"; return; }
    ufw status | grep -q "Status: active" || { log_message "info" "UFW is inactive"; return; }

    local cache_file="${TMP_DIR}/ufw_apache_hash"
    local port_hash=$(echo "${APACHE_PORTS[*]}" | md5sum | cut -d' ' -f1)
    [[ -f "$cache_file" && "$(cat "$cache_file")" == "$port_hash" ]] && return

    for port in "${APACHE_PORTS[@]}"; do
        ufw status numbered | grep -E "${port}\s+ALLOW" >/dev/null || {
            ufw allow "$port" || {
                log_message "error" "Failed to open port $port in UFW"
                return ${EXIT_GENERAL_ERROR}
            }
        }
    done

    echo "$port_hash" > "$cache_file" 2>/dev/null || log_message "warning" "Failed to cache UFW config for Apache: ${APACHE_PORTS[*]}"
}

# Проверка зависимостей и команд с кэшированием
check_apache_deps() {
    local cache_file="${TMP_DIR}/apache_deps_hash"
    local deps_hash=$(echo "${APACHE_DEPS[*]} ${APACHE_CMDS[*]}" | md5sum | cut -d' ' -f1)
    [[ -f "$cache_file" && "$(cat "$cache_file")" == "$deps_hash" && $(( $(date +%s) - $(stat -c %Y "$cache_file") )) -lt 86400 ]] && return

    check_deps "${APACHE_DEPS[@]}" || {
        log_message "error" "Missing apache2 dependency"
        return ${EXIT_VHOST_NOT_INSTALLED}
    }

    check_commands "${APACHE_CMDS[@]}" || {
        log_message "error" "Missing required commands"
        return ${EXIT_VHOST_NOT_INSTALLED}
    }

    echo "$deps_hash" > "$cache_file" 2>/dev/null || log_message "warning" "Failed to cache deps"
}

# Основная логика
log_message "info" "Checking Apache settings"

check_apache_deps || return $?

start_apache_service || return $?

configure_ufw || return $?

log_message "info" "Apache is configured and running"

return ${EXIT_SUCCESS}