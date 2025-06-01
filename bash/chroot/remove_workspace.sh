#!/bin/bash
# remove_workspace.sh - Скрипт удаления chroot-workspace пользователя
# Расположение: bash/chroot/remove_workspace.sh

set -euo pipefail

# Подключение логального config.sh
source "$(dirname "${BASH_SOURCE[0]}")/config.sh" || {
    echo "Failed to source local config.sh" >&2
    exit 1
}

# Подключение скрипта удаления chroot-workspace
source "$REMOVE_WORKSPACE_FN" || { log_message "error" "Failed to source '$REMOVE_WORKSPACE_FN'" >&2; return "$EXIT_GENERAL_ERROR"; }

# Проверка аргументов
[[ ${#ARGS[@]} -ge 1 ]] || { echo "Usage: $0 <username>"; exit "$EXIT_INVALID_ARG"; }

# Установка переменных
declare -rx USERNAME="${ARGS[0]}"

# Проверка имени пользователя
[[ "$USERNAME" =~ ^[a-zA-Z0-9._-]+$ ]] || { log_message "error" "Invalid USERNAME: $USERNAME"; exit "$EXIT_INVALID_ARG"; }

# Удаление chroot-workspace пользователя с временной блокировкой
with_lock "$TMP_DIR/${LOCK_CHROOT_PREF}_${USERNAME}.lock" remove_chroot_workspace "$USERNAME"
exit $?