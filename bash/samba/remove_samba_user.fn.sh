#!/bin/bash

# remove_samba_user.fn.sh - Скрипт экспортирующий функцию удаления пользователя Samba
# Расположение: bash/samba/remove_samba_user.fn.sh

set -euo pipefail

# Проверка, что скрипт не запущен напрямую
[[ "${BASH_SOURCE[0]}" == "$0" ]] && { 
    echo "This script ('$0') is meant to be sourced"
    exit 1
}

# Проверка активности пользователя и завершение его сеансов
check_and_terminate_user() {
    local username="$1"

    # Закрытие Samba-сессий пользователя
    smbcontrol smbd close-session "$username" 2>/dev/null || {
        log_message "warning" "Failed to close Samba sessions for '$username'"
    }

    # Проверка, есть ли активные Samba-процессы пользователя
    pgrep -u "$username" -f "smbd|smbclient" >/dev/null && {
        pkill -u "$username" -f "smbd|smbclient" 2>/dev/null || {
            pkill -9 -u "$username" -f "smbd|smbclient" 2>/dev/null || {
                log_message "error" "Failed to terminate Samba processes for '$username'"
                return ${EXIT_GENERAL_ERROR}
            }
        }
        sleep 1
        pgrep -u "$username" -f "smbd|smbclient" >/dev/null && {
            log_message "error" "Samba processes for '$username' still running"
            return ${EXIT_GENERAL_ERROR}
        }
    }
}

# Функция удаления пользователя Samba
# remove_samba_user <username>
remove_samba_user() {
    local username="$1"

    # Проверка, существует ли пользователь в базе Samba
    pdbedit -L -u "$username" | grep -q "^$username:" || {
        log_message "info" "Samba user '$username' does not exist"
        return ${EXIT_SUCCESS}
    }

    log_message "info" "Removing Samba user '$username'"

    # Завершение активных сеансов пользователя
    check_and_terminate_user "$username" || return $?

    # Удаление пользователя из Samba
    smbpasswd -x "$username" >/dev/null || {
        log_message "error" "Failed to delete Samba user '$username'"
        return ${EXIT_SAMBA_USER_DELETE_FAILED}
    }

    # Создание флага для отложенной перезагрузки
    touch "${RELOAD_NEEDED_FILE}" 2>>"$LOG_FILE" || {
        log_message "error" "Failed to create Samba reload flag"
        return ${EXIT_SAMBA_SERVICE_FAILED}
    }

    log_message "info" "Samba user '$username' removed successfully"

    return ${EXIT_SUCCESS}
}

# Экспорт функций
export -f delete_user_samba check_and_terminate_user

return ${EXIT_SUCCESS}