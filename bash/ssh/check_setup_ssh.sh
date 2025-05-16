#!/bin/bash

# check_setup_ssh.sh - Скрипт проверки настроек SSH
# Расположение: bash/ssh/check_setup_ssh.sh

set -euo pipefail

# Подключение локального config.sh
source "$(dirname "${BASH_SOURCE[0]}")/config.sh" "--log=samba.log" || {
    echo "Failed to source local config.sh"
    exit 1
}

# Проверка и запуск SSH-сервисов
start_ssh_services() {
    for service in "sshd"; do
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

# Получение SSH-порта
get_ssh_port() {
    local cache_file="${TMP_DIR}/ssh_port_cache"
    [[ -f "$cache_file" && $(( $(date +%s) - $(stat -c %Y "$cache_file") )) -lt 86400 ]] && {
        cat "$cache_file"
        return
    }
    local port=$(grep -h -E "^Port\s+[0-9]+" "$SSH_CONFIG_FILE" "$SSH_CONFIGS_DIR"/*.conf 2>/dev/null | awk '{print $2}' | head -n 1)
    [[ -z "$port" || ! "$port" =~ ^[0-9]+$ || "$port" -lt 1 || "$port" -gt 65535 ]] && port=22
    echo "$port" > "$cache_file" 2>/dev/null || log_message "warning" "Failed to cache SSH port"
    echo "$port"
}

# Настройка UFW для SSH-порта
configure_ufw() {
    local port="$1"
    command -v ufw >/dev/null || { log_message "warning" "UFW not installed"; return; }
    ufw status | grep -q "Status: active" || { log_message "info" "UFW is inactive"; return; }
    ufw status numbered | grep -E "${port}/tcp.*ALLOW" >/dev/null || {
        ufw allow "$port/tcp" || {
            log_message "error" "Failed to configure UFW for SSH port $port"
            return ${EXIT_GENERAL_ERROR}
        }
    }
}

# Проверка директории sshd_config.d
check_configs_dir() {
    if [[ ! -d "$SSH_CONFIGS_DIR" || ! -w "$SSH_CONFIGS_DIR" ]]; then
        log_message "error" "SSH config include directory not found or not writable: $SSH_CONFIGS_DIR"
        exit ${EXIT_GENERAL_ERROR}
    fi
}

# Проверка зависимостей и команд
[[ ! -f "$SSH_DEPS_CACHE" || $(( $(date +%s) - $(stat -c %Y "$SSH_DEPS_CACHE") )) -gt 86400 ]] && {
    check_deps "openssh-server" || {
        log_message "error" "Missing openssh-server"
        exit ${EXIT_SAMBA_NOT_INSTALLED}
    }
    check_commands sshd || {
        log_message "error" "Failed check commands"
        exit ${EXIT_SAMBA_NOT_INSTALLED}
    }
    create_directories "$CHROOT_DIR" "$CHROOT_STUDENTS" 755 root:root || {
        log_message "error" "Failed create directories: ${CHROOT_DIR}, ${CHROOT_STUDENTS}"
        exit ${EXIT_CHROOT_INIT_FAILED}
    }
    touch "$SSH_DEPS_CACHE"
}

# Запуск SSH-сервисов
start_ssh_services

# Настройка UFW для SSH-порта
configure_ufw "$(get_ssh_port)"

return ${EXIT_SUCCESS}