#!/bin/bash
# create_user.sh - Скрипт создания системного пользователя
# Расположение: bash/system/create_user.sh

set -euo pipefail

# Подключение логального config.sh
LOCAL_CONFIG="$(dirname "${BASH_SOURCE[0]}")/config.sh"
source "$LOCAL_CONFIG" || {
    echo "Failed to source script $LOCAL_CONFIG" >&2
    exit 1
}

# Основная логика
# Проверка массива ARGS
if ! declare -p ARGS >/dev/null 2>&1; then
    echo "ARGS array is not defined"
    exit ${EXIT_INVALID_ARG}
fi

# Проверка аргументов
if [[ ${#ARGS[@]} -lt 3 ]]; then
    echo "Usage: $0 <username> <password> <home_dir>"
    exit ${EXIT_INVALID_ARG}
fi

USERNAME="${ARGS[0]}"
PASSWORD="${ARGS[1]}"
HOME_DIR="${ARGS[2]}"

if [[ ! "$USERNAME" =~ ^[a-zA-Z0-9._-]+$ ]]; then
    echo "Invalid username: $USERNAME"
    exit ${EXIT_INVALID_ARG}
fi

if id "$USERNAME" &>/dev/null; then
    log_message "error" "User '$USERNAME' exists"
    exit ${EXIT_GENERAL_ERROR}
fi

if [[ ! -d "$HOME_DIR" ]]; then
    log_message "error"  "Home directory '$HOME_DIR' does not exist"
    exit ${EXIT_NOT_FOUND}
fi

update_permissions "$HOME_DIR" 755 ${SITE_USER}:${SITE_GROUP} || {
    exit_status=$?
    log_message "error" "Failed to update permissions '1' or ownership '$SITE_USER:$SITE_GROUP' for: '$HOME_DIR'"
    exit $exit_status
}

log_message "info" "Start creating the system user '$USERNAME'"

useradd -d "$HOME_DIR" -s /bin/bash -g "$STUDENT_GROUP" "$USERNAME" 2>&1 || {
    log_message "error" "Failed to create user '$USERNAME'"
    exit ${EXIT_FAILED_CREATE_USER}
}

printf "%s:%s\n" "$USERNAME:$PASSWORD" | chpasswd 2>&1 || {
    userdel "$USERNAME" || true
    log_message "error" "Failed to set password for '$USERNAME'"
    exit ${EXIT_FAILED_CREATE_USER}
}

log_message "info" "System user '$USERNAME' successfully created"

exit ${EXIT_SUCCESS}