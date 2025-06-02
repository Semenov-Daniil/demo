#!/bin/bash
# delete_user.sh - Скрипт удаления системного пользователя
# Расположение: bash/system/delete_user.sh

set -euo pipefail

# Подключение логального config.sh
LOCAL_CONFIG="$(realpath $(dirname "${BASH_SOURCE[0]}")/config.sh)"
source "$LOCAL_CONFIG" || {
    echo "Failed to source local config '$LOCAL_CONFIG'" >&2
    exit 1
}

# Проверка активности пользователя и завершение его сеансов
terminate_user() {
    local username="$1"

    pgrep -u "$username" >/dev/null || return 0
    
    pkill -u "$username" 2>/dev/null || pkill -9 -u "$username" 2>/dev/null || {
        log_message "error" "Failed to terminate processes for '$username'"
        return "$EXIT_FAILED_DELETE_USER"
    }

    local start_time=$SECONDS timeout=3
    while pgrep -u "$username" >/dev/null; do
        if (( SECONDS - start_time > timeout )); then
            log_message "error" "Processes for '$username' still running after ${timeout}s"
            return 1
        fi
        sleep 0.01
    done

    log_message "info" "All processes for '$username' terminated successfully"
    return 0
}

# Функция удаления пользователя
delete_user () {
    local username="$1"
    [[ -z "$username" ]] && { log_message "error" "No username provided"; return "$EXIT_INVALID_ARG"; }

    log_message "info" "Starting deletion of system user '$USERNAME'"

    terminate_user "$USERNAME" || return $?

    userdel -r "$USERNAME" &>/dev/null || {
        log_message "error" "Failed to delete user '$USERNAME'"
        return "$EXIT_FAILED_DELETE_USER"
    }

    log_message "ok" "System user '$USERNAME' successfully deleted"
    return 0
}

# Проверка аргументов
[[ ${#ARGS[@]} -eq 1 ]] || { echo "Usage: $0 <username>"; exit ${EXIT_INVALID_ARG}; }

# Установка переменных
USERNAME="${ARGS[0]}"

# Проверка пользователя
[[ -z "$USERNAME" ]] || [[ ! "$USERNAME" =~ ^[a-zA-Z0-9._-]+$ ]] && {
    log_message "error" "Invalid username '$USERNAME'"
    exit "$EXIT_INVALID_ARG"
}

# Проверка существования пользователя
id "$USERNAME" >/dev/null 2>&1 || {
    log_message "info" "User '$USERNAME' does not exist"
    exit 0
}

groups "$USERNAME" 2>/dev/null | grep -qw "$STUDENT_GROUP" || {
    log_message "error" "User '$USERNAME' is not in '$STUDENT_GROUP' group"
    exit "$EXIT_INVALID_ARG"
}

# Удаление рабочей области в chroot
bash "$REMOVE_WORKSPACE" "$USERNAME"

# Удаление пользователя Samba
bash "$REMOVE_SAMBA_USER" "$USERNAME"

# Удаление пользователя с блокировкой
with_lock "${TMP_DIR}/${LOCK_USER_PREF}_${USERNAME}.lock" delete_user "$USERNAME"
exit 0