#!/bin/bash
# remove_vhost.sh - Скрипт удаления виртуального хоста Apache2
# Расположение: bash/vhost/remove_vhost.sh

set -euo pipefail

# Подключение локального config.sh
LOCAL_CONFIG="$(dirname "${BASH_SOURCE[0]}")/config.sh"
source "$LOCAL_CONFIG" || {
    echo "Failed to source script $LOCAL_CONFIG" >&2
    exit 1
}

# Подключение скрипта удаления виртуального хоста
source "$REMOVE_VHOST_SCRIPT" || {
    echo "Failed to source script $REMOVE_VHOST_SCRIPT" >&2
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
    echo "Usage: $0 <vhost-name>" >&2
    exit ${EXIT_INVALID_ARG}
fi

VHOST_NAME="${ARGS[0]}"

remove_vhost "${VHOST_NAME}" || exit $?

exit ${EXIT_SUCCESS}