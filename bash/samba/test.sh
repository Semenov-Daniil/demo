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

# Основная логика
exit ${EXIT_SUCCESS}
