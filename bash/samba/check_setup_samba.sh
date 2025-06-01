#!/bin/bash
# check_setup_samba.sh - Скрипт проверки настроек Samba
# Расположение: bash/samba/check_setup_samba.sh

set -euo pipefail

# Подключение локального config.sh
LOCAL_CONFIG="$(realpath $(dirname "${BASH_SOURCE[0]}")/config.sh)"
source "$LOCAL_CONFIG" || {
    echo "Failed to source local config '$LOCAL_CONFIG'" >&2
    exit 1
}

declare -rx SAMBA_PORTS=("137/udp" "138/udp" "139/tcp" "445/tcp")
declare -rax SAMBA_REQUIRED_COMMAND=("pdbedit" "smbpasswd" "smbcontrol" "testparm")
declare -rax SAMBA_REQUIRED_DEPENCY=("samba" "samba-common-bin")
declare -rax SAMBA_SERVICES=("smbd" "nmbd")
declare -x SERVICE_START_TIMEOUT=5

# Проверка и запуск Samba-сервисов
start_samba_services() {
    local service
    for service in "${SAMBA_SERVICES[@]}"; do
        systemctl is-active --quiet "$service" && continue
        systemctl enable "$service" && systemctl start "$service" >/dev/null 2>&1 || {
            log_message "error" "Failed to start service $service"
            return "$EXIT_SAMBA_START_FAILED"
        }

        local start_time=$SECONDS
        while ! systemctl is-active --quiet "$service"; do
            (( SECONDS - start_time > SERVICE_START_TIMEOUT )) && {
                log_message "error" "Service '$service' failed to start within ${SERVICE_START_TIMEOUT}s"
                return "$EXIT_SAMBA_START_FAILED"
            }
            sleep 0.01
        done
    done

    log_message "info" "All Samba services are running"
    return 0
}

# Настройка UFW для Samba-портов
configure_ufw() {
    command -v ufw >/dev/null || { log_message "warning" "UFW not installed, skipping"; return 0; }
    ufw status verbose | grep -q "Status: active" || { log_message "info" "UFW is inactive, skipping"; return 0; }

    local port
    for port in "${SAMBA_PORTS[@]}"; do
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

    log_message "info" "All UFW ports for Samba are running"
    return 0
}

# Проверка наличия зависимостей и команд Samba
check_samba_dependencies() {
    check_dependency "${SAMBA_REQUIRED_DEPENCY[@]}" || return $?
    check_commands "${SAMBA_REQUIRED_COMMAND[@]}" || return $?
    log_message "info" "All Samba dependencies are installed"
    return 0
}

start_samba_services || exit $?
configure_ufw || exit $?
check_samba_dependencies || exit $?

exit 0