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
    for service in ${SAMBA_SERVICES[@]}; do
        systemctl is-active --quiet "$service" && continue
        systemctl enable "$service" && systemctl start "$service" || {
            log_message "error" "Failed to start $service"
            return ${EXIT_SAMBA_NOT_INSTALLED}
        }
        sleep 1
        systemctl is-active --quiet "$service" || {
            log_message "error" "$service failed to start"
            return ${EXIT_SAMBA_NOT_INSTALLED}
        }
    done

    log_message "info" "All Samba services are running: ${SAMBA_SERVICES[*]}"

    return 0
}

# Настройка UFW для Samba-портов
configure_ufw() {
    command -v ufw >/dev/null || { log_message "warning" "UFW not installed"; return; }
    ufw status | grep -q "Status: active" || { log_message "info" "UFW is inactive"; return; }

    for port in "${SAMBA_PORTS[@]}"; do
        ufw status numbered | grep -q "$port.*ALLOW" || {
            ufw allow "$port" || {
                log_message "error" "Failed to open port $port in UFW"
                return ${EXIT_GENERAL_ERROR}
            }
        }
    done

    log_message "info" "All UFW ports for Samba are running: ${SAMBA_PORTS[*]}"

    return 0
}

# ПРоверка наличия зависимостей и команд Samba
check_samba_dependencies() {
    check_dependency ${SAMBA_REQUIRED_DEPENCY[*]} || return $?
    check_commands ${SAMBA_REQUIRED_COMMAND[*]} || return $?
    log_message "info" "All Samba dependencies are installed: ${SAMBA_REQUIRED_DEPENCY[*]} ${SAMBA_REQUIRED_COMMAND[*]}"
    return 0
}

# Проверка зависимостей и команд
check_samba_dependencies &
pid1=$!

# Запуск Samba-сервисов
start_samba_services &
pid2=$!

# Настройка UFW для Samba-портов
configure_ufw &
pid3=$!

# Ожидание завершения
wait $pid1 || exit $?
wait $pid2 || exit $?
wait $pid3 || exit $?

exit ${EXIT_SUCCESS}