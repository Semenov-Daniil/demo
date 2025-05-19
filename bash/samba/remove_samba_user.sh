#!/bin/bash
# remove_samba_user.sh - Скрипт исполняющий удаления пользователя Samba
# Расположение: bash/samba/remove_samba_user.sh

set -euo pipefail

# Подключение локального config.sh
source "$(dirname "${BASH_SOURCE[0]}")/config.sh" || {
    echo "Failed to source local config.sh"
    exit 1
}

# Подключение скрипта удаления Samba-пользователя
source "$REMOVE_SAMBA_USER_FN" || exit $?

# Основная логика
# Проверка массива ARGS
[[ -n "${ARGS+x}" ]] || { echo "ARGS array is not defined"; exit ${EXIT_INVALID_ARG}; }

# Проверка аргументов
[[ ${#ARGS[@]} -eq 1 ]] || { echo "Usage: $0 <username>"; exit ${EXIT_INVALID_ARG}; }

# Установка переменных
USERNAME="${ARGS[0]}"

# Удаления Samba-пользователя с блокировкой
with_lock ${LOCK_SAMBA_FILE} remove_samba_user "$USERNAME" || exit $?

exit ${EXIT_SUCCESS}