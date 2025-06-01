#!/bin/bash
# setup_workspace.sh - Скрипт инициализации chroot-workspace пользователя
# Расположение: bash/chroot/setup_workspace.sh

set -euo pipefail

# Подключение логального config.sh
LOCAL_CONFIG="$(realpath $(dirname "${BASH_SOURCE[0]}")/config.sh)"
source "$LOCAL_CONFIG" || {
    echo "Failed to source local config.sh '$LOCAL_CONFIG'" >&2
    exit 1
}

# Подключение скрипта удаления chroot-workspace
source "$REMOVE_WORKSPACE_FN" || { log_message "error" "Failed to source '$REMOVE_WORKSPACE_FN'" >&2; exit "$EXIT_GENERAL_ERROR"; }

with_lock "$TMP_DIR/${LOCK_CHROOT_PREF}_setup.log" bash "$SETUP_CHROOT" || { log_message "error" "Failed to source '$SETUP_CHROOT'" >&2; exit "$EXIT_GENERAL_ERROR"; }

cleanup() {
    local exit_code=$?
    [[ $exit_code -eq 0 ]] && return
    [[ ! -z "${USERNAME:-}" ]] && remove_chroot_workspace "$USERNAME"
}

# Создание chroot-workspace пользователя
setup_user_workspace() {
    log_message "info" "Starting to create a workspace for user '$USERNAME' in chroot '$CHROOT_ROOT'"

    [[ -d "$CHROOT_WORKSPACE" ]] && {
        log_message "warning" "Workspace '$CHROOT_WORKSPACE' already exists"
        remove_chroot_workspace "$USERNAME" || return $?
    }

    [[ ! -d "$BASE_CHROOT" || ! -d "$CHROOT_ROOT" ]] && {
        log_message "warning" "Chroot '$BASE_CHROOT' not found. Attempting to initialize chroot '$BASE_CHROOT'"
        bash "$INIT_CHROOT" || { log_message "error" "Failed to initialize chroot '$BASE_CHROOT'"; return "$EXIT_CHROOT_INIT_FAILED"; }
    }

    with_lock "$TMP_DIR/$LOCK_CHROOT_PREF.lock" create_directories "$CHROOT_HOME/$USERNAME" 755 "root:root" || return $?

    ln -sf "$ETC_BASHRC" "$CHROOT_HOME/$USERNAME/.bashrc" || {
        log_message "error" "Failed to create .bashrc in '$CHROOT_HOME/$USERNAME'"
        return $?
    }

    ln -sf "$ETC_BASH_PREEXEC" "$CHROOT_HOME/$USERNAME/.bash-preexec.sh" || {
        log_message "error" "Failed to create .bash-preexec.sh in '$CHROOT_HOME/$USERNAME'"
        return $?
    }

    create_directories "$CHROOT_WORKSPACE" 755 root:root || return $?
    [[ ! -d "$USER_WORKSPACE" ]] && { log_message "error" "User workspace '$USER_WORKSPACE' not found"; return "$EXIT_CHROOT_INIT_FAILED"; }
    mount_bind "$USER_WORKSPACE" "$CHROOT_WORKSPACE" || return $?

    log_message "info" "Workspace of user '$USERNAME' was successfully created in chroot '$CHROOT_ROOT'"

    return 0
}

# Основная логика
trap cleanup SIGINT SIGTERM EXIT

# Проверка аргументов
[[ ${#ARGS[@]} -ge 2 ]] || { echo "Usage: $0 <username> <workspace>"; exit "$EXIT_INVALID_ARG"; }

# Установка переменных
declare -rx USERNAME="${ARGS[0]}"
declare -rx USER_WORKSPACE="${ARGS[1]}"
declare -rx CHROOT_WORKSPACE="$(chroot_workspace "$USERNAME")"

# Проверка имени пользователя
[[ "$USERNAME" =~ ^[a-zA-Z0-9._-]+$ ]] || { log_message "error" "Invalid USERNAME: $USERNAME"; exit "$EXIT_INVALID_ARG"; }

# Создание chroot-workspace пользователя с временной блокировкой
with_lock "$TMP_DIR/${LOCK_CHROOT_PREF}_${USERNAME}.lock" setup_user_workspace
exit $?