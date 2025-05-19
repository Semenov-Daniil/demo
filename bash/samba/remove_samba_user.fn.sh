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
    local username="$1" timeout="${2:-2}"
    local samba_processes="smbd|smbclient|nmbd|winbindd"

    smbcontrol smbd close-session "$username" 2>/dev/null || {
        log_message "warning" "Failed to close Samba sessions for '$username'"
    }

    pgrep -u "$username" -f "$samba_processes" >/dev/null || {
        log_message "info" "No Samba processes found for '$username'"
        return ${EXIT_SUCCESS}
    }

    pkill -u "$username" -f "$samba_processes" 2>/dev/null || {
        pkill -9 -u "$username" -f "$samba_processes" 2>/dev/null || {
            log_message "error" "Failed to terminate Samba processes for '$username'"
            return ${EXIT_GENERAL_ERROR}
        }
    }

    local start=$(date +%s)
    while pgrep -u "$username" -f "$samba_processes" >/dev/null; do
        [[ $(( $(date +%s) - start )) -gt $timeout ]] && {
            log_message "error" "Samba processes for '$username' still running after ${timeout}s"
            ps -u "$username" -f | grep -E "$samba_processes" | log_message "error"
            return ${EXIT_GENERAL_ERROR}
        }
        sleep 0.05
    done

    log_message "info" "All Samba processes for '$username' terminated successfully"
    return ${EXIT_SUCCESS}
}

# Функция удаления пользователя Samba
# remove_samba_user <username>
remove_samba_user() {
    local username="$1"

    pdbedit -L -u "$username" | grep -q "^$username:" || {
        log_message "info" "Samba user '$username' does not exist"
        return ${EXIT_SUCCESS}
    }

    log_message "info" "Removing Samba user '$username'"

    check_and_terminate_user "$username" || return $?

    smbpasswd -x "$username" >/dev/null || {
        log_message "error" "Failed to delete Samba user '$username'"
        return ${EXIT_SAMBA_USER_DELETE_FAILED}
    }

    log_message "info" "Samba user '$username' removed successfully"

    return ${EXIT_SUCCESS}
}

export -f remove_samba_user check_and_terminate_user
return ${EXIT_SUCCESS}