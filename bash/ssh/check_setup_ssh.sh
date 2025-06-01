#!/bin/bash
# check_setup_ssh.sh - Скрипт проверки настроек SSH
# Расположение: bash/ssh/check_setup_ssh.sh

set -euo pipefail

# Подключение логального config.sh
LOCAL_CONFIG="$(realpath $(dirname "${BASH_SOURCE[0]}")/config.sh)"
source "$LOCAL_CONFIG" || {
    echo "Failed to source local config '$LOCAL_CONFIG'" >&2
    exit 1
}

declare -rax SSH_SERVICES=("sshd")
declare -x SERVICE_START_TIMEOUT=5

# Проверка и запуск SSH-сервисов
start_ssh_services() {
    local service
    for service in "${SAMBA_SERVICES[@]}"; do
        systemctl is-active --quiet "$service" && continue
        systemctl enable "$service" && systemctl start "$service" >/dev/null 2>&1 || {
            log_message "error" "Failed to start service $service"
            return "$EXIT_SSH_START_FAILED"
        }

        local start_time=$SECONDS
        while ! systemctl is-active --quiet "$service"; do
            (( SECONDS - start_time > SERVICE_START_TIMEOUT )) && {
                log_message "error" "Service '$service' failed to start within ${SERVICE_START_TIMEOUT}s"
                return "$EXIT_SSH_START_FAILED"
            }
            sleep 0.01
        done
    done

    log_message "info" "All SSH services are running"
    return 0
}

# Получение SSH-порта
get_ssh_port() {
    local port=$(grep -h -E "^Port\s+[0-9]+" "$CONFIG_FILE" "$CONFIG_DIR"/*.conf 2>/dev/null | awk '{print $2}' | head -n 1) || {
        log_message "error" "Failed to get SSH port"
        return "$EXIT_SSH_CONFIG_FAILED"
    }
    [[ -z "$port" || ! "$port" =~ ^[0-9]+$ || "$port" -lt 1 || "$port" -gt 65535 ]] && port=22
    echo "$port"
}

# Настройка UFW для SSH-порта
configure_ufw() {
    local port="$(get_ssh_port)/tcp" || return $?

    command -v ufw >/dev/null || { log_message "warning" "UFW not installed, skipping"; return 0; }
    ufw status verbose | grep -q "Status: active" || { log_message "info" "UFW is inactive, skipping"; return 0; }

    ufw status verbose | grep -qw "$port.*ALLOW" || {
        ufw allow "$port" >/dev/null 2>&1 || {
            log_message "error" "Failed to open port '$port' in UFW"
            return "$EXIT_GENERAL_ERROR"
        }
    }

    ufw reload || {
        log_message "error" "Failed to restart UFW"
        return "$EXIT_GENERAL_ERROR"
    }

    log_message "info" "UFW port '$port' for SSH are running"
    return "$EXIT_SUCCESS"
}

# Проверка наличия зависимостей и команд SSH
check_samba_dependencies() {
    check_dependency "openssh-server" || {
        log_message "error" "Missing openssh-server"
        return "$EXIT_SSH_NOT_INSTALLED"
    }
    check_commands sshd || {
        log_message "error" "Failed check commands"
        return "$EXIT_SSH_NOT_INSTALLED"
    }
    log_message "info" "All SSH dependencies are installed"
    return 0
}

start_ssh_services || exit $?
configure_ufw || exit $?
check_samba_dependencies || exit $?

exit 0