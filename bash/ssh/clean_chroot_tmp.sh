#!/bin/bash

# clean_chroot_tmp.sh - Скрипт очистки /tmp в chroot-окружении пользователя
# Расположение: bash/ssh/clean_chroot_tmp.sh

set -euo pipefail

# Подключение логального config.sh
source "$(dirname "${BASH_SOURCE[0]}")/config.sh" || {
    echo "Failed to source local config.sh"
    exit 1
}

# Очистка tmp директории
clean_tmp() {
    local tmp_dir="$1"
    local lockfile="${TMP_DIR}/${LOCK_SSH_PREF}_tmp_${USERNAME}.lock"
    (
        flock -x 200 || { log_message "error" "Failed to acquire lock for '$tmp_dir'"; return ${EXIT_GENERAL_ERROR}; }
        find "$tmp_dir" -mindepth 1 -delete 2>/dev/null || {
            log_message "error" "Failed to clean '$tmp_dir'"
            return ${EXIT_GENERAL_ERROR}
        }
        chmod 1777 "$tmp_dir" || {
            log_message "error" "Failed to set permissions for '$tmp_dir'"
            return ${EXIT_GENERAL_ERROR}
        }
    ) 200>"$lockfile"
}

# Основная логика
# Проверка массива ARGS
[[ -n "${ARGS+x}" ]] || { echo "ARGS array is not defined"; exit ${EXIT_INVALID_ARG}; }

# Проверка аргументов
[[ ${#ARGS[@]} -ge 1 ]] || { echo "Usage: $0 <username>"; exit ${EXIT_INVALID_ARG}; }

# Установка переменных
USERNAME="${ARGS[0]}"
STUDENT_CHROOT="${CHROOT_STUDENTS}/${USERNAME}"
TMP_DIR="${STUDENT_CHROOT}/tmp"

# Проверка chroot-окружения
[[ -d "$STUDENT_CHROOT" ]] || { 
    log_message "error" "Chroot '$STUDENT_CHROOT' does not exist"
    exit ${EXIT_CHROOT_INIT_FAILED}
}

[[ -d "$TMP_DIR" ]] || { 
    log_message "error" "Temporary directory '$TMP_DIR' does not exist"
    exit ${EXIT_CHROOT_INIT_FAILED}
}

log_message "info" "Starting /tmp cleanup for '$USERNAME'"

with_lock ${LOCK_CHROOT_FILE} clean_tmp "$TMP_DIR" || exit $?

log_message "info" "Temporary directory for '$USERNAME' cleaned successfully"

exit ${EXIT_SUCCESS}