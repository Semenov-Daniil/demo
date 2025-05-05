#!/bin/bash

# add_student_samba.sh - Скрипт добавления пользователя в Samba
# Расположение: bash/samba/add_student_samba.sh

set -euo pipefail

# Подключение локального config.sh
LOCAL_CONFIG="$(dirname "${BASH_SOURCE[0]}")/config.sh"
source "$LOCAL_CONFIG" || {
    echo "Failed to source script $LOCAL_CONFIG" >&2
    exit 1
}

# Подключение скрипта удаления Samba-пользователя
source "$DELETE_USER_SAMBA" || {
    echo "Failed to source script $DELETE_USER_SAMBA" >&2
    exit ${EXIT_GENERAL_ERROR}
}

# Очистка при ошибке
cleanup() {
    exit_code=$?
    
    if [[ $exit_code -eq 0 ]] || [[ $exit_code -eq $EXIT_INVALID_ARG ]]; then
        return
    fi

    delete_user_samba "${USERNAME}" || {
        log_message "error" "Failed to rollback Samba user $USERNAME"
    }
}

# Основная логика
trap cleanup SIGINT SIGTERM EXIT

# Проверка массива ARGS
if ! declare -p ARGS >/dev/null 2>&1; then
    echo "ARGS array is not defined" >&2
    exit ${EXIT_INVALID_ARG}
fi

# Проверка аргументов
if [[ ${#ARGS[@]} -lt 2 ]]; then
    echo "Usage: $0 <username> <password>" >&2
    exit ${EXIT_INVALID_ARG}
fi

USERNAME="${ARGS[0]}"
PASSWORD="${ARGS[1]}"

# Валидация имени пользователя
if [[ ! "$USERNAME" =~ ^[a-zA-Z0-9._-]+$ ]]; then
    log_message "error" "Invalid username: $USERNAME"
    exit ${EXIT_INVALID_ARG}
fi

# Проверка существования системного пользователя
if ! id "$USERNAME" &>/dev/null; then
    log_message "error" "User '$USERNAME' does not exist"
    exit ${EXIT_INVALID_ARG}
fi

# Проверка принадлежности к группе students
if ! groups "$USERNAME" | grep -q "$STUDENT_GROUP"; then
    log_message "error" "User '$USERNAME' is not a member of the '$STUDENT_GROUP' group"
    exit ${EXIT_INVALID_ARG}
fi

if ! command -v pdbedit >/dev/null 2>&1; then
    log_message "error" "pdbedit command not found, is Samba installed?"
    exit ${EXIT_SAMBA_NOT_INSTALLED}
fi

# Проверка, существует ли пользователь в базе Samba
if pdbedit -L | grep -q "^$USERNAME:"; then
    log_message "error" "Samba user '$USERNAME' already exists"
    delete_user_samba "${USERNAME}" || {
        log_message "error" "Failed to rollback Samba user $USERNAME"
    }
fi

log_message "info" "Starting Samba user setup for $USERNAME"

# Добавление пользователя в Samba
printf "%s\n%s\n" "$PASSWORD" "$PASSWORD" | smbpasswd -s -a "$USERNAME" >/dev/null 2>>"$LOG_FILE" || {
    log_message "error" "Failed to setup Samba user $USERNAME"
    exit ${EXIT_SAMBA_USER_ADD_FAILED}
}

# Перезагрузка конфигурации Samba
smbcontrol smbd reload-config >/dev/null 2>>"$LOG_FILE" || {
    log_message "error" "Failed to reload Samba configuration for $USERNAME"
    exit ${EXIT_SAMBA_SERVICE_FAILED}
}

log_message "info" "Samba user $USERNAME setup successfully"

exit ${EXIT_SUCCESS}