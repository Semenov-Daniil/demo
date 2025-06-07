#!/bin/bash
# setup_workspace.sh - Скрипт инициализации chroot-workspace пользователя
# Расположение: bash/chroot/setup_workspace.sh

set -euo pipefail

# Подключение логального config.sh
LOCAL_CONFIG="$(realpath $(dirname "${BASH_SOURCE[0]}")/config.sh)"
source "$LOCAL_CONFIG" || {
    echo "Failed to source local config '$LOCAL_CONFIG'" >&2
    exit 1
}

cleanup() {
    local exit_code=$?
    [[ $exit_code -eq 0 || -z "${USERNAME:-}" ]] && return
    bash "$REMOVE_WORKSPACE" "$USERNAME"
}

trap cleanup SIGINT SIGTERM EXIT

[[ ${#ARGS[@]} -ge 2 ]] || { echo "Usage: $0 <username> <workspace>"; exit "$EXIT_INVALID_ARG"; }

declare -rx USERNAME="${ARGS[0]}"
declare -rx USER_WORKSPACE="${ARGS[1]}"

_setup_chroot() {
    bash "$SETUP_CHROOT" || {
        log_message "error" "Failed to source '$SETUP_CHROOT'"
        return "$EXIT_GENERAL_ERROR"
    }
    return 0
}

with_lock "$TMP_DIR/${LOCK_CHROOT_PREF}_setup.log" _setup_chroot || exit $?

# Создание chroot-workspace пользователя
setup_user_workspace() {
    local username="$1" workspace="$2"
    [[ -z "$username" ]] && { log_message "error" "No username provided"; return "$EXIT_INVALID_ARG"; }
    [[ -z "$workspace" ]] && { log_message "error" "No workspace provided"; return "$EXIT_INVALID_ARG"; }

    [[ "$username" =~ ^[a-zA-Z0-9._-]+$ ]] || { log_message "error" "Invalid username $username"; exit "$EXIT_INVALID_ARG"; }
    id "$username" >/dev/null 2>&1 || { log_message "error" "User '$username' does not exist"; exit "$EXIT_INVALID_ARG"; }
    groups "$username" 2>/dev/null | grep -qw "$STUDENT_GROUP" || { log_message "error" "User '$username' is not in '$STUDENT_GROUP' group"; exit "$EXIT_INVALID_ARG"; }

    [[ ! -d "$workspace" ]] && { log_message "error" "User workspace '$workspace' not found"; return "$EXIT_CHROOT_WORKSPACE_FAILED"; }

    chroot_workspace="$(chroot_workspace "$username")"

    [[ -d "$chroot_workspace" ]] && {
        log_message "warning" "Workspace '$chroot_workspace' already exists"
        bash "$REMOVE_WORKSPACE" "$username" || return $?
    }

    log_message "info" "Starting to create a workspace for user '$username' in chroot '$CHROOT_ROOT'"

    [[ ! -d "$BASE_CHROOT" || ! -d "$CHROOT_ROOT" ]] && {
        log_message "warning" "Chroot '$BASE_CHROOT' not found. Attempting to initialize chroot '$BASE_CHROOT'"
        bash "$INIT_CHROOT" || { log_message "error" "Failed to initialize chroot '$BASE_CHROOT'"; return "$EXIT_CHROOT_INIT_FAILED"; }
    }

    create_directories "$CHROOT_HOME/$username" 700 "$username:$STUDENT_GROUP" || return "$EXIT_CHROOT_WORKSPACE_FAILED"
    usermod -d "$HOME_USERS/$username" "$username" &>/dev/null || {
        log_message "error" "Failed to set the home directory for user '$username'"
        return "$EXIT_CHROOT_WORKSPACE_FAILED"
    }

    ln -sf "$ETC_BASHRC" "$CHROOT_HOME/$username/.bashrc" || {
        log_message "error" "Failed to create .bashrc in '$CHROOT_HOME/$username'"
        return "$EXIT_CHROOT_WORKSPACE_FAILED"
    }

    ln -sf "$ETC_BASH_PREEXEC" "$CHROOT_HOME/$username/.bash-preexec.sh" || {
        log_message "error" "Failed to create .bash-preexec.sh in '$CHROOT_HOME/$username'"
        return "$EXIT_CHROOT_WORKSPACE_FAILED"
    }

    update_permissions "$CHROOT_HOME/$username/.bashrc" "$CHROOT_HOME/$username/.bash-preexec.sh" 755 root:root || return $?

    create_directories "$chroot_workspace" 755 root:root || return "$EXIT_CHROOT_WORKSPACE_FAILED"
    
    mount_rbind "$workspace" "$chroot_workspace" "" $(get_mount_units "$workspace") || return $?
    mount_rslave "$chroot_workspace" $(title_mount_unit "$chroot_workspace") || return $?

    log_message "ok" "Workspace of user '$username' was successfully created in chroot '$CHROOT_ROOT'"
    return 0
}

# Создание chroot-workspace пользователя с временной блокировкой
with_lock "$TMP_DIR/${LOCK_CHROOT_PREF}_${USERNAME}.lock" setup_user_workspace "$USERNAME" "$USER_WORKSPACE"
exit $?