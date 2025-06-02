#!/bin/bash
# create_user.sh - Скрипт создания системного пользователя
# Расположение: bash/system/create_user.sh

set -euo pipefail

# Подключение логального config.sh
LOCAL_CONFIG="$(realpath $(dirname "${BASH_SOURCE[0]}")/config.sh)"
source "$LOCAL_CONFIG" || {
    echo "Failed to source local config '$LOCAL_CONFIG'" >&2
    exit 1
}

cleanup() {
    exit_code=$?
    [[ $exit_code -eq 0 || -z "${USERNAME:-}" ]] && return
    bash "$DELETE_USER" "$USERNAME" 2>/dev/null
}

# Функция создания пользователя
create_user () {
    log_message "info" "Starting creation of system user '$USERNAME'"

    if id "$USERNAME" &>/dev/null; then
        log_message "warning" "User '$USERNAME' already exists"
        usermod -s /bin/bash -g "$STUDENT_GROUP" "$USERNAME" >/dev/null || {
            log_message "error" "Failed to update user '$USERNAME'"
            exit "$EXIT_FAILED_CREATE_USER"
        }
    else
        useradd -M -s /bin/bash -g "$STUDENT_GROUP" "$USERNAME" || {
            log_message "error" "Failed to create user '$USERNAME'"
            exit "$EXIT_FAILED_CREATE_USER"
        }
    fi

    echo "$USERNAME:$PASSWORD" | chpasswd || {
        log_message "error" "Failed to set password for '$USERNAME'"
        exit "$EXIT_FAILED_CREATE_USER"
    }

    log_message "ok" "System user '$USERNAME' successfully created"
    return 0
}

# Основная логика
trap cleanup SIGINT SIGTERM EXIT

# Проверка аргументов
[[ ${#ARGS[@]} -eq 3 ]] || { echo "Usage: $0 <username> <password> <home_dir>"; exit "$EXIT_INVALID_ARG"; }

# Установка переменных
USERNAME="${ARGS[0]}"
PASSWORD="${ARGS[1]}"
WORKSPACE="${ARGS[2]}"

# Проверка имени пользователя
[[ "$USERNAME" =~ ^[a-zA-Z0-9._-]+$ ]] || {
    log_message "error" "Invalid username $USERNAME"
    exit "$EXIT_INVALID_ARG"
}

# Проверка домашней директории
[[ -d "$WORKSPACE" ]] || {
    log_message "error" "Directory '$WORKSPACE' does not exist"
    exit "$EXIT_NOT_FOUND"
}

# Создание пользователя
with_lock "$TMP_DIR/${LOCK_USER_PREF}_${USERNAME}.lock" create_user || exit $?

# Создание рабочей области в chroot
bash "$SETUP_WORKSPACE" "$USERNAME" "$WORKSPACE" || exit $?

# Добавление пользователя Samba
bash "$ADD_SAMBA_USER" "$USERNAME" "$PASSWORD" || exit $?

# Настройка SSH
bash "$CONFIG_SSH" || exit $?

exit 0