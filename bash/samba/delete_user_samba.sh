#!/bin/bash
# delete_user_samba.sh - Функция для удаления пользователя из Samba
# Расположение: bash/samba/delete_user_samba.sh

set -euo pipefail

# Проверка, что скрипт не запущен напрямую
if [[ "${BASH_SOURCE[0]}" == "$0" ]]; then
    echo "This script ('$0') is meant to be sourced" >&2
    return 1
fi

# Установка переменных по умолчанию
: "${SAMBA_SERVICES:=("smbd" "nmbd")}"
: "${EXIT_SUCCESS:=0}"
: "${EXIT_GENERAL_ERROR:=1}"
: "${EXIT_INVALID_ARG:=5}"
: "${EXIT_SAMBA_NOT_INSTALLED:=20}"
: "${EXIT_SAMBA_USER_DELETE_FAILED:=25}"

# Проверка активности пользователя и завершение его сеансов
check_and_terminate_user() {
    local username="$1"
    local user_processes

    # Проверяем, есть ли активные процессы пользователя
    if user_processes=$(pgrep -u "$username" 2>/dev/null); then
        if ! pkill -u "$username" 2>/dev/null; then
            log_message "warning" "Some processes for $username could not be terminated gracefully"
            pkill -9 -u "$username" 2>/dev/null || {
                log_message "error" "Failed to terminate processes for $username"
                return ${EXIT_GENERAL_ERROR}
            }
        fi

        sleep 1

        if pgrep -u "$username" >/dev/null 2>&1; then
            log_message "error" "Some processes for $username are still running after termination attempt"
            return ${EXIT_GENERAL_ERROR}
        fi
    fi
}

# Основная функция удаления Samba-пользователя
# delete_user_samba username
delete_user_samba() {
    local username="$1"

    # Проверка наличия pdbedit
    if ! command -v pdbedit >/dev/null 2>&1; then
        log_message "error" "pdbedit command not found, is Samba installed?"
        return ${EXIT_SAMBA_NOT_INSTALLED}
    fi

    # Проверка наличия Samba
    if ! command -v smbpasswd >/dev/null 2>&1; then
        log_message "error" "smbpasswd command not found, is Samba installed?"
        return ${EXIT_SAMBA_NOT_INSTALLED}
    fi

    # Проверка, существует ли пользователь в базе Samba
    if ! pdbedit -L | grep -q "^$username:"; then
        log_message "info" "Samba user '$username' does not exist, nothing to delete"
        return ${EXIT_SUCCESS}
    fi

    log_message "info" "Starting Samba user removal for $username"

    # Завершение активных сеансов пользователя
    check_and_terminate_user "$username" || return $?

    # Удаление пользователя из Samba
    smbpasswd -x "$username" >/dev/null 2>>"$LOG_FILE" || {
        log_message "error" "Failed to delete Samba user $username"
        return ${EXIT_SAMBA_USER_DELETE_FAILED}
    }

    # Перезагрузка конфигурации Samba
    smbcontrol smbd reload-config >/dev/null 2>>"$LOG_FILE" || {
        log_message "error" "Failed to reload Samba configuration after removing $username"
        return ${EXIT_SAMBA_SERVICE_FAILED}
    }

    log_message "info" "Samba user $username removed successfully"
    return ${EXIT_SUCCESS}
}

# Экспорт функций
export -f delete_user_samba
export -f check_and_terminate_user

return ${EXIT_SUCCESS}