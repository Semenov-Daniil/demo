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
source_script "${REMOVE_CHROOT_FN}" || exit $?

# Подключение скрипта с функциями монтирования
source_script "${MOUNTS_FN}" || exit $?

# Подключение скрипта настройки chroot
source "$SETUP_CHROOT" || {
    echo "Failed to source script '$SETUP_CHROOT'" >&2
    exit "$EXIT_GENERAL_ERROR"
}

# Основная логика
# Проверка аргументов
[[ ${#ARGS[@]} -ge 1 ]] || { echo "Usage: $0 <USERNAME>"; exit "${EXIT_INVALID_ARG}"; }

# Установка переменных
USERNAME="${ARGS[0]}"

# Проверка пользователя
[[ "${USERNAME}" =~ ^[a-zA-Z0-9._-]+$ ]] || {
    log_message "error" "Invalid USERNAME: ${USERNAME}"
    exit ${EXIT_INVALID_ARG}
}
id "${USERNAME}" &>/dev/null || {
    log_message "error" "User '${USERNAME}' does not exist"
    exit "${EXIT_INVALID_ARG}"
}
groups "${USERNAME}" | grep -q "${STUDENT_GROUP}" || {
    log_message "error" "User '${USERNAME}' is not a member of the '${STUDENT_GROUP}' group"
    exit "${EXIT_INVALID_ARG}"
}

# Удаление chroot-окружения с блокировкой
with_lock "${TMP_DIR}/${LOCK_SSH_PREF}_chroot_${USERNAME}.lock" remove_chroot "${USERNAME}" || exit $?

exit "${EXIT_SUCCESS}"