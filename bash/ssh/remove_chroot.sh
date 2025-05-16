#!/bin/bash

# remove_chroot.sh - Скрипт исполняющий удаление chroot-окружения
# Расположение: bash/ssh/remove_chroot.sh

set -euo pipefail

# Подключение логального config.sh
source "$(dirname "${BASH_SOURCE[0]}")/config.sh" || {
    echo "Failed to source local config.sh"
    exit 1
}

# Подключение скрипта удаления chroot-окружения
source "$REMOVE_CHROOT" || {
    echo "Failed to source script '$REMOVE_CHROOT'"
    exit ${EXIT_GENERAL_ERROR}
}

# Основная логика
# Проверка массива ARGS
[[ -n "${ARGS+x}" ]] || { echo "ARGS array is not defined"; exit ${EXIT_INVALID_ARG}; }

# Проверка аргументов
[[ ${#ARGS[@]} -ge 1 ]] || { echo "Usage: $0 <username>"; exit ${EXIT_INVALID_ARG}; }

# Установка переменных
USERNAME="${ARGS[0]}"

with_lock ${LOCK_CHROOT_FILE} remove_chroot "$USERNAME" || exit $?
exit ${EXIT_SUCCESS}