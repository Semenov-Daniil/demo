#!/bin/bash
# remove_student_chroot.sh - Скрипт удаления chroot-окружения студента
# Расположение: bash/ssh/remove_student_chroot.sh

set -euo pipefail

# Подключение локального config.sh
LOCAL_CONFIG="$(dirname "${BASH_SOURCE[0]}")/config.sh"
source "$LOCAL_CONFIG" || {
    echo "Failed to source script $LOCAL_CONFIG" >&2
    exit 1
}

# Подключение скрипта удаления chroot-окружения
source "$REMOVE_CHROOT" || {
    echo "Failed to source script $REMOVE_CHROOT" >&2
    exit ${EXIT_GENERAL_ERROR}
}



# Основная логика
# Проверка массива ARGS
if ! declare -p ARGS >/dev/null 2>&1; then
    echo "ARGS array is not defined"
    exit ${EXIT_INVALID_ARG}
fi

# Проверка аргументов
if [[ ${#ARGS[@]} -lt 1 ]]; then
    echo "Usage: $0 <username>"
    exit ${EXIT_INVALID_ARG}
fi

USERNAME="${ARGS[0]}"

remove_chroot ${USERNAME} || exit $?
