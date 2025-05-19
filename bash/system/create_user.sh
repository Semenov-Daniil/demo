#!/bin/bash
# create_user.sh - Скрипт создания системного пользователя
# Расположение: bash/system/create_user.sh

set -euo pipefail

# Подключение логального config.sh
source "$(dirname "${BASH_SOURCE[0]}")/config.sh" || {
    echo "Failed to source local config.sh"
    exit 1
}

# Функция создания пользователя
create_user () {
    if id "$USERNAME" > /dev/null; then
        log_message "warning" "User '$USERNAME' already exists"

        current_group=$(id -gn "$USERNAME")
        if [[ "$current_group" != "$STUDENT_GROUP" ]]; then
            usermod -g "$STUDENT_GROUP" "$USERNAME" || {
                userdel "$USERNAME" || true
                log_message "error" "Failed to change the master group of user '$USERNAME'"
                exit ${EXIT_FAILED_CREATE_USER}
            }
        fi
    else
        useradd -d "$HOME_DIR" -s /bin/bash -g "$STUDENT_GROUP" "$USERNAME" || {
            log_message "error" "Failed to create user '$USERNAME'"
            exit ${EXIT_FAILED_CREATE_USER}
        }
    fi

    printf "%s:%s\n" "$USERNAME:$PASSWORD" | chpasswd || {
        userdel "$USERNAME" || true
        log_message "error" "Failed to set password for '$USERNAME'"
        exit ${EXIT_FAILED_CREATE_USER}
    }

    return ${EXIT_SUCCESS}
}

# Проверка массива ARGS
[[ -n "${ARGS+x}" ]] || { echo "ARGS array is not defined"; exit ${EXIT_INVALID_ARG}; }

# Проверка аргументов
[[ ${#ARGS[@]} -eq 3 ]] || { echo "Usage: $0 <username> <password> <home_dir>"; exit ${EXIT_INVALID_ARG}; }

# Установка переменных
USERNAME="${ARGS[0]}"
PASSWORD="${ARGS[1]}"
HOME_DIR="${ARGS[2]}"

# Проверка имени пользователя
[[ "$USERNAME" =~ ^[a-zA-Z0-9._-]+$ ]] || {
    log_message "error" "Invalid username: $USERNAME"
    exit ${EXIT_INVALID_ARG}
}

# Проверка домашней директории
[[ -d "$HOME_DIR" ]] || {
    log_message "error" "Home directory '$HOME_DIR' does not exist"
    exit ${EXIT_NOT_FOUND}
}

update_permissions "$HOME_DIR" 755 ${SITE_USER}:${SITE_GROUP} || exit $?

log_message "info" "Starting creation of system user '$USERNAME'"

# Установка блокировки
with_lock "${TMP_DIR}/${LOCK_USER_PREF}_${USERNAME}.lock" create_user || exit $?

log_message "info" "System user '$USERNAME' successfully created"

exit ${EXIT_SUCCESS}