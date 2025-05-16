#!/bin/bash

# check_setup_samba.sh - Скрипт проверки настроек Samba
# Расположение: bash/samba/check_setup_samba.sh

set -euo pipefail

# Подключение локального config.sh
source "$(dirname "${BASH_SOURCE[0]}")/config.sh" "--log=samba.log" || {
    echo "Failed to source local config.sh"
    exit 1
}

# Проверка и запуск Samba-сервисов
start_samba_services() {
    for service in "smbd" "nmbd"; do
        systemctl is-active --quiet "$service" && continue
        systemctl enable "$service" && systemctl start "$service" || {
            log_message "error" "Failed to start $service"
            exit ${EXIT_SAMBA_NOT_INSTALLED}
        }
        sleep 1
        systemctl is-active --quiet "$service" || {
            log_message "error" "$service failed to start"
            exit ${EXIT_SAMBA_NOT_INSTALLED}
        }
    done
}

# Настройка UFW для Samba-портов
configure_ufw() {
    command -v ufw >/dev/null || { log_message "warning" "UFW not installed"; return; }
    ufw status | grep -q "Status: active" || { log_message "info" "UFW is inactive"; return; }

    local cache_file="${TMP_DIR}/ufw_samba_hash"
    local port_hash=$(echo "${SAMBA_PORTS[*]}" | md5sum | cut -d' ' -f1)
    [[ -f "$cache_file" && "$(cat "$cache_file")" == "$port_hash" ]] && return

    for port in "${SAMBA_PORTS[@]}"; do
        ufw status numbered | grep -q "$port.*ALLOW" || {
            ufw allow "$port" || {
                log_message "error" "Failed to open port $port in UFW"
                exit ${EXIT_GENERAL_ERROR}
            }
        }
    done

    echo "$port_hash" > "$cache_file" 2>/dev/null || log_message "warning" "Failed to cache UFW config for Samba: ${SAMBA_PORTS[*]}"
}

# Проверка зависимостей и команд
[[ ! -f "$SAMBA_DEPS_CACHE" || $(( $(date +%s) - $(stat -c %Y "$SAMBA_DEPS_CACHE") )) -gt 86400 ]] && {
    check_deps "samba" "samba-common-bin" || {
        log_message "error" "Failed check dependencies"
        exit ${EXIT_SAMBA_NOT_INSTALLED}
    }
    check_commands pdbedit smbpasswd smbcontrol || {
        log_message "error" "Failed check commands"
        exit ${EXIT_SAMBA_NOT_INSTALLED}
    }
    touch "$SAMBA_DEPS_CACHE"
}

# Запуск Samba-сервисов
start_samba_services

# Настройка UFW для Samba-портов
configure_ufw

return ${EXIT_SUCCESS}