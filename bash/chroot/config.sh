#!/bin/bash
# config.sh - Локальный конфигурационный файл для скриптов настройки chroot-окружения
# Расположение: bash/chroot/config.sh

set -euo pipefail

# Проверкa подключения скрипта
[[ "${BASH_SOURCE[0]}" == "$0" ]] && {
    echo "This script ('$0') is meant to be sourced" >&2
    exit 1
}

# Подключение глобального config.sh
GLOBAL_CONFIG="$(realpath $(dirname "${BASH_SOURCE[0]}")/../config.sh)"
source "$GLOBAL_CONFIG" || {
    echo "Failed to source global config '$GLOBAL_CONFIG'"
    return 1
}

# Коды exit
declare -rx EXIT_CHROOT_INIT_FAILED=10
declare -rx EXIT_CHROOT_WORKSPACE_FAILED=11
declare -rx EXIT_MOUNT_FAILED=12
declare -rx EXIT_SYSTEMD_UNIT_FAILED=13
declare -rx EXIT_SETUP_BASH_FAILED=13

# Лог
[[ "$LOG_FILE" == "${DEFAULT_LOG_FILE:-}" ]] && LOG_FILE="chroot.log"

# Скрипты
declare -rx MOUNTS_FN="$LIB_DIR/mounts.fn.sh"
declare -rx RESTRICT_BINARIES_FN="$LIB_DIR/restrict_binaries.fn.sh"
declare -rx SETUP_CHROOT="$(dirname "${BASH_SOURCE[0]}")/setup_chroot.sh"
declare -rx INIT_CHROOT="$(dirname "${BASH_SOURCE[0]}")/init_chroot.sh"
declare -rx REMOVE_CHROOT="$(dirname "${BASH_SOURCE[0]}")/remove_chroot.sh"
declare -rx REMOVE_WORKSPACE="$(dirname "${BASH_SOURCE[0]}")/remove_workspace.sh"

# Chroot константы
declare -axr SYSTEM_DIRS=("/bin" "/lib" "/lib64" "/usr")
declare -ar RESTRICTED_CMDS=("sudo" "su" "cron" "sshfs" "mount" "umount")

# Bash шаблоны
declare -xr ETC_BASHRC="$ETC_STUDENTS/.bashrc"
declare -xr ETC_BASH_PREEXEC="$ETC_STUDENTS/.bash-preexec.sh"

# Lock константы
declare -rx LOCK_CHROOT_PREF="lock_chroot"

# Подключение скрипта с функциями монтирования
source "$MOUNTS_FN" || { log_message "error" "Failed to source '$MOUNTS_FN'"; return "$EXIT_GENERAL_ERROR"; }

# Подключение скрипта с функцией блокировкой бинанрных файлов
source "$RESTRICT_BINARIES_FN" || { log_message "error" "Failed to source '$RESTRICT_BINARIES_FN'"; return "$EXIT_GENERAL_ERROR"; }

return 0