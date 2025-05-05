#!/bin/bash
# delete_student_samba.sh - Скрипт удаления пользователя из Samba
# Расположение: bash/samba/delete_student_samba.sh

set -euo pipefail

# Подключение локального config.sh
LOCAL_CONFIG="$(dirname "${BASH_SOURCE[0]}")/config.sh"
source "$LOCAL_CONFIG" || {
    echo "Failed to source script $LOCAL_CONFIG" >&2
    exit 1
}

# Подключение скрипта удаления Samba-пользователя
source "$DELETE_USER_SAMBA" || {
    echo "Failed to source script $DELETE_USER_SAMBA" >&2
    exit ${EXIT_GENERAL_ERROR}
}

# Основная логика
# Проверка массива ARGS
if ! declare -p ARGS >/dev/null 2>&1; then
    echo "ARGS array is not defined" >&2
    exit ${EXIT_INVALID_ARG}
fi

# Проверка аргументов
if [[ ${#ARGS[@]} -lt 1 ]]; then
    echo "Usage: $0 <username>" >&2
    exit ${EXIT_INVALID_ARG}
fi

USERNAME="${ARGS[0]}"

delete_user_samba "${USERNAME}" || exit $?

exit ${EXIT_SUCCESS}