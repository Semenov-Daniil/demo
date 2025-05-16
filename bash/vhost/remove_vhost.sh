#!/bin/bash
# remove_vhost.sh - Скрипт удаления виртуального хоста Apache2
# Расположение: bash/vhost/remove_vhost.sh

set -euo pipefail

# Подключение логального config.sh
source "$(dirname "${BASH_SOURCE[0]}")/config.sh" || {
    echo "Failed to source local config.sh"
    exit 1
}

# Подключение скрипта удаления виртуального хоста
source "$REMOVE_VHOST" || {
    echo "Failed to source script '$REMOVE_VHOST'"
    exit ${EXIT_GENERAL_ERROR}
}

# Основная логика
# Проверка массива ARGS
[[ -n "${ARGS+x}" ]] || { echo "ARGS array is not defined"; exit ${EXIT_INVALID_ARG}; }

# Проверка аргументов
[[ ${#ARGS[@]} -ge 1 ]] || { echo "Usage: $0 <vhost-name> <vhost-config>"; exit ${EXIT_INVALID_ARG}; }

# Установка переменных
VHOST_NAME="${ARGS[0]}"

# Валидация имени виртуального хоста
if [[ ! "$VHOST_NAME" =~ ^[a-zA-Z0-9._-]+$ ]]; then
    log_message "error" "Invalid virtual host name $VHOST_NAME"
    exit ${EXIT_INVALID_ARG}
fi

with_lock "${TMP_DIR}/${LOCK_VHOST_PREF}_${VHOST_NAME}.lock" remove_vhost "$VHOST_NAME" || exit $?

exit ${EXIT_SUCCESS}