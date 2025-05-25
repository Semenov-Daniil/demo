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
source_script "$REMOVE_SAMBA_USER_FN" || exit $?

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
    if pdbedit -L -u "$username" &>/dev/null | grep -q "^$username:" &>/dev/null; then
        log_message "warning" "Samba user '$username' already exists"
        printf "%s\n%s\n" "$password" "$password" | smbpasswd -s "$username" &>/dev/null || {
            log_message "error" "Failed to update password for Samba user '$username'"
            return ${EXIT_SAMBA_USER_ADD_FAILED}
        }
    else
        printf "%s\n%s\n" "$password" "$password" | smbpasswd -s -a "$username" &>/dev/null || {
            log_message "error" "Failed to add Samba user '$username'"
            return ${EXIT_SAMBA_USER_ADD_FAILED}
        }
    fi
    return 0
}

# Основная логика
trap cleanup SIGINT SIGTERM EXIT

# Проверка аргументов
[[ ${#ARGS[@]} -eq 2 ]] || { echo "Usage: $0 <username> <password>"; exit ${EXIT_INVALID_ARG}; }

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
    exit ${EXIT_GENERAL_ERROR}
}

groups "$USERNAME" | grep -q "$STUDENT_GROUP" || {
    log_message "error" "User '$USERNAME' is not a member of the '$STUDENT_GROUP' group"
    exit ${EXIT_GENERAL_ERROR}
}

log_message "info" "Adding Samba user '$USERNAME'"

# Добавление пользователя Samba с блокировкой
with_lock ${LOCK_SAMBA_FILE} add_user_samba "$USERNAME" "$PASSWORD" || exit $?

log_message "info" "Samba user '$USERNAME' added successfully"

exit ${EXIT_SUCCESS}