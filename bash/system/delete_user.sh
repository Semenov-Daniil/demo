#!/bin/bash
# delete_user.sh - Скрипт удаления системного пользователя
# Расположение: bash/system/delete_user.sh

set -euo pipefail

# Подключение логального config.sh
LOCAL_CONFIG="$(dirname "${BASH_SOURCE[0]}")/config.sh"
source "$LOCAL_CONFIG" || {
    echo "Failed to source script $LOCAL_CONFIG" >&2
    exit 1
}

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

# Основная логика
# Проверка массива ARGS
if ! declare -p ARGS >/dev/null 2>&1; then
    echo "ARGS array is not defined"
    exit ${EXIT_INVALID_ARG}
fi

# Проверка аргументов
if [[ ${#ARGS[@]} -lt 1 ]]; then
    echo "Usage: $0 <username>"
    exit ${EXIT_INVALID_ARG}
fi

USERNAME="${ARGS[0]}"

if ! id "$USERNAME" &>/dev/null; then
    log_message "warning" "User '$USERNAME' does not exist"
    exit ${EXIT_SUCCESS}
fi

if ! groups "$USERNAME" | grep -q "$STUDENT_GROUP"; then
    log_message "error" "User '$USERNAME' is not a member of the '$STUDENT_GROUP' group"
    exit ${EXIT_GENERAL_ERROR}
fi

log_message "info" "Start deleting the system user '$USERNAME'"

# Завершение активных сеансов пользователя
check_and_terminate_user "$USERNAME" || return $?

# Удаление пользователя
if ! userdel "$USERNAME" 2>&1; then
    log_message "error" "Failed to delete user '$USERNAME'"
    exit ${EXIT_FAILED_DELETE_USER}
fi

log_message "info" "System user '$USERNAME' has been successfully deleted"

exit ${EXIT_SUCCESS}