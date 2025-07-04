#!/bin/bash
# add_samba_user.sh - Скрипт добавления пользователя Samba
# Расположение: bash/samba/add_samba_user.sh

set -euo pipefail

# Подключение локального config.sh
LOCAL_CONFIG="$(realpath $(dirname "${BASH_SOURCE[0]}")/config.sh)"
source "$LOCAL_CONFIG" || {
    echo "Failed to source local config '$LOCAL_CONFIG'" >&2
    exit 1
}

# Очистка при ошибке
cleanup() {
    exit_code=$?
    [[ $exit_code -eq 0 || -z "${USERNAME:-}" ]] && return
    bash "$REMOVE_USER" "$USERNAME" 2>/dev/null
}

trap cleanup SIGINT SIGTERM EXIT

# Добавление пользователя Samba
add_samba_user() {
    local username="$1" password="$2"
    [[ -z "$username" || -z "$password" ]] && {
        log_message "error" "Username or password missing"
        return "$EXIT_INVALID_ARG"
    }

    log_message "info" "Adding Samba user '$username'"
    if pdbedit -u "$username" >/dev/null 2>&1; then
        log_message "warning" "Samba user '$username' already exists, updating password"
        echo -e "$password\n$password" | smbpasswd -s "$username" >/dev/null 2>&1 || {
            log_message "error" "Failed to update password for '$username'"
            return "$EXIT_ADD_SAMBA_USER_FAILED"
        }
    else
        echo -e "$password\n$password" | smbpasswd -s -a "$username" >/dev/null 2>&1 || {
            log_message "error" "Failed to add Samba user '$username'"
            return "$EXIT_ADD_SAMBA_USER_FAILED"
        }
    fi
    log_message "ok" "Samba user '$username' added successfully"
    return 0
}

# Проверка аргументов
[[ ${#ARGS[@]} -eq 2 ]] || { echo "Usage: $0 <username> <password>"; exit "$EXIT_INVALID_ARG"; }

USERNAME="${ARGS[0]}"
PASSWORD="${ARGS[1]}"
[[ -z "$USERNAME" ]] || [[ ! "$USERNAME" =~ ^[a-zA-Z0-9._-]+$ ]] && {
    log_message "error" "Invalid username: $USERNAME"
    exit "$EXIT_INVALID_ARG"
}

# Проверка существования пользователя и группы
id "$USERNAME" >/dev/null 2>&1 || {
    log_message "error" "User '$USERNAME' does not exist"
    exit "$EXIT_INVALID_ARG"
}
groups "$USERNAME" 2>/dev/null | grep -qw "$STUDENT_GROUP" || {
    log_message "error" "User '$USERNAME' is not in '$STUDENT_GROUP' group"
    exit "$EXIT_INVALID_ARG"
}

# Добавление пользователя Samba с блокировкой
with_lock "$TMP_DIR/${LOCK_SAMBA_PREF}_${USERNAME}.lock" add_samba_user "$USERNAME" "$PASSWORD"
exit $?