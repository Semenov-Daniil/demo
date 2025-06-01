#!/bin/bash
# remove_samba_user.sh - Скрипт удаления пользователя Samba
# Расположение: bash/samba/remove_samba_user.sh

set -euo pipefail

# Подключение локального config.sh
LOCAL_CONFIG="$(realpath $(dirname "${BASH_SOURCE[0]}")/config.sh)"
source "$LOCAL_CONFIG" || {
    echo "Failed to source local config '$LOCAL_CONFIG'" >&2
    exit 1
}

# Получение PID активных сессий пользователя
get_user_pids() {
    local username="$1"
     [[ -z "$username" ]] && return 0
    smbstatus -p 2>/dev/null | grep -w "$username" | awk '{print $1}' | sort -u || echo ""
}

# Завершение активных сессий пользователя
close_samba_sessions() {
    local username="$1"

    local pids
    pids=$(get_user_pids "$username") || return 1
    [[ -z "$pids" ]] && return 0

    local pid
    for pid in $pids; do
        smbcontrol smbd close-session "$pid" 2>/dev/null &
        local smb_pid=$!
        wait "$smb_pid" || {
            log_message "warning" "Failed to close session PID $pid for user '$username', attempting to terminate"
            kill -TERM "$pid" 2>/dev/null || kill -KILL "$pid" 2>/dev/null || {
                log_message "error" "Failed to terminate PID $pid for user '$username'"
                return 1
            }
        }
    done

    local start_time=$SECONDS timeout=3
    while [[ -n $(get_user_pids "$username") ]]; do
        if (( SECONDS - start_time > timeout )); then
            log_message "error" "Samba processes for '$username' still running after ${timeout}s"
            return 1
        fi
        sleep 0.01
    done

    return 0
}

# Удаление пользователя Samba
remove_samba_user() {
    local username="$1"
    [[ -z "$username" ]] && { log_message "error" "No username provided"; return "$EXIT_INVALID_ARG"; }

    pdbedit -u "$username" >/dev/null 2>&1 || {
        log_message "info" "Samba user '$username' does not exist"
        return 0
    }

    log_message "info" "Removing Samba user '$username'"

    close_samba_sessions "$username" || return $?

    smbpasswd -x "$username" >/dev/null 2>&1 || {
        log_message "error" "Failed to delete Samba user '$username'"
        return "$EXIT_DELETE_SAMBA_USER_FAILED"
    }

    log_message "ok" "Samba user '$username' removed successfully"
    return 0
}

# Проверка аргументов
[[ ${#ARGS[@]} -eq 1 ]] || { echo "Usage: $0 <username>"; exit "$EXIT_INVALID_ARG"; }

USERNAME="${ARGS[0]}"

# Проверка пользователя
[[ -z "$USERNAME" ]] || [[ ! "$USERNAME" =~ ^[a-zA-Z0-9._-]+$ ]] && {
    log_message "error" "Invalid username '$USERNAME'"
    exit "$EXIT_INVALID_ARG"
}

groups "$USERNAME" 2>/dev/null | grep -qw "$STUDENT_GROUP" || {
    log_message "error" "User '$USERNAME' is not in '$STUDENT_GROUP' group"
    exit "$EXIT_INVALID_ARG"
}

# Удаления Samba-пользователя с блокировкой
with_lock "$TMP_DIR/${LOCK_SAMBA_PREF}_${USERNAME}.lock" remove_samba_user "$USERNAME"
exit $?