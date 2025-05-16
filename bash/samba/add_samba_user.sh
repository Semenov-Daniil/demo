#!/bin/bash

# add_samba_user.sh - Скрипт добавления пользователя Samba
# Расположение: bash/samba/add_samba_user.sh

set -euo pipefail

# Подключение локального config.sh
source "$(dirname "${BASH_SOURCE[0]}")/config.sh" || {
    echo "Failed to source local config.sh"
    exit 1
}

# Подключение скрипта удаления Samba-пользователя
source "$DELETE_USER_SAMBA" || {
    echo "Failed to source script $DELETE_USER_SAMBA"
    exit ${EXIT_GENERAL_ERROR}
}

# Очистка при ошибке
cleanup() {
    exit_code=$?
    [[ $exit_code -eq 0 || $exit_code -eq $EXIT_INVALID_ARG ]] && return

    with_lock ${LOCK_SAMBA_FILE} remove_samba_user "$USERNAME" || {
        log_message "error" "Failed to rollback Samba user '$USERNAME'"
    }
}

# Функция добавления пользователя Samba
add_user_samba() {
    local username="$1" password="$2"
    pdbedit -L -u "$username" | grep -q "^$username:" && {
        log_message "warning" "Samba user '$username' already exists"
        return ${EXIT_SUCCESS}
    }
    printf "%s\n" "$password" | smbpasswd -s -a "$username" >/dev/null || {
        log_message "error" "Failed to add Samba user '$username'"
        return ${EXIT_SAMBA_USER_ADD_FAILED}
    }
    touch "${RELOAD_NEEDED_FILE}" || {
        log_message "error" "Failed to create Samba reload flag"
        return ${EXIT_SAMBA_SERVICE_FAILED}
    }
}

# Основная логика
trap cleanup SIGINT SIGTERM EXIT

# Проверка массива ARGS
[[ -n "${ARGS+x}" ]] || { echo "ARGS array is not defined"; exit ${EXIT_INVALID_ARG}; }

# Проверка аргументов
[[ ${#ARGS[@]} -ge 2 ]] || { echo "Usage: $0 <username> <password>"; exit ${EXIT_INVALID_ARG}; }

# Установка переменных
USERNAME="${ARGS[0]}"
PASSWORD="${ARGS[1]}"

# Проверка пользователя
[[ "$USERNAME" =~ ^[a-zA-Z0-9._-]+$ ]] || {
    log_message "error" "Invalid username: $USERNAME"
    exit ${EXIT_INVALID_ARG}
}

id "$USERNAME" &>/dev/null || {
    log_message "error" "User '$USERNAME' does not exist"
    exit ${EXIT_INVALID_ARG}
}

groups "$USERNAME" | grep -q "$STUDENT_GROUP" || {
    log_message "error" "User '$USERNAME' is not a member of the '$STUDENT_GROUP' group"
    exit ${EXIT_INVALID_ARG}
}

log_message "info" "Adding Samba user '$username'"

# Установка блокировки
with_lock ${LOCK_SAMBA_FILE} add_user_samba "$USERNAME" "$PASSWORD" || exit $?

log_message "info" "Samba user '$username' added successfully"

exit ${EXIT_SUCCESS}