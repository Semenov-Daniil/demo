#!/bin/bash
# delete_user.sh - Скрипт удаления системного пользователя
# Расположение: bash/system/delete_user.sh

set -euo pipefail

# Подключение логального config.sh
source "$(dirname "${BASH_SOURCE[0]}")/config.sh" || {
    echo "Failed to source local config.sh"
    exit 1
}

# Проверка активности пользователя и завершение его сеансов
check_and_terminate_user() {
    local username="$1"

    pgrep -u "$username" >/dev/null || return ${EXIT_SUCCESS}
    pkill -u "$username" 2>/dev/null || pkill -9 -u "$username" 2>/dev/null || {
        log_message "error" "Failed to terminate processes for '$username'"
        return ${EXIT_GENERAL_ERROR}
    }

    local timeout=1 start=$(date +%s)
    while pgrep -u "$username" >/dev/null; do
        [[ $(( $(date +%s) - start )) -gt $timeout ]] && {
            log_message "error" "Processes for '$username' still running after timeout"
             ps -u "$username" -f | grep -E "$samba_processes" | log_message "error"
            return ${EXIT_GENERAL_ERROR}
        }
        sleep 0.05
    done

    log_message "info" "All processes for '$username' terminated successfully"
    return 0
}

# Функция удаления пользователя
delete_user () {
    check_and_terminate_user "$USERNAME" || return $?
    userdel -r "$USERNAME" &>/dev/null || {
        log_message "error" "Failed to delete user '$USERNAME'"
        return ${EXIT_FAILED_DELETE_USER}
    }

    return 0
}

# Основная логика

# Проверка аргументов
[[ ${#ARGS[@]} -eq 1 ]] || { echo "Usage: $0 <username>"; exit ${EXIT_INVALID_ARG}; }

# Установка переменных
USERNAME="${ARGS[0]}"

# Проверка существования пользователя
id "$USERNAME" &>/dev/null || {
    log_message "info" "User '$USERNAME' does not exist"
    exit ${EXIT_SUCCESS}
}

if ! groups "$USERNAME" | grep -q "$STUDENT_GROUP"; then
    log_message "error" "User '$USERNAME' is not a member of the '$STUDENT_GROUP' group"
    exit ${EXIT_GENERAL_ERROR}
fi

log_message "info" "Starting deletion of system user '$USERNAME'"

# Установка блокировки
with_lock "${TMP_DIR}/${LOCK_USER_PREF}_${USERNAME}.lock" delete_user || exit $?

log_message "info" "System user '$USERNAME' successfully deleted"

exit ${EXIT_SUCCESS}