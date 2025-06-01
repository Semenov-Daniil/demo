#!/bin/bash
# config.sh - Локальный конфигурационный файл для скриптов настройки ssh и создания/удаления chroot-окружения
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
    echo "Failed to source global config.sh '$GLOBAL_CONFIG'"
    return 1
}

# Code exit
declare -rx EXIT_CHROOT_INIT_FAILED=10
declare -rx EXIT_MOUNT_FAILED=11
declare -rx EXIT_SYSTEMD_UNIT_FAILED=12
declare -rx EXIT_BASH_FAILED=13

# Logging
[[ "$LOG_FILE" == "$DEFAULT_LOG_FILE" ]] && LOG_FILE="chroot.log"

# Scripts
declare -rx MOUNTS_FN="$(dirname "${BASH_SOURCE[0]}")/mounts.fn.sh"
declare -rx SETUP_CHROOT="$(dirname "${BASH_SOURCE[0]}")/setup_chroot.sh"
declare -rx INIT_CHROOT="$(dirname "${BASH_SOURCE[0]}")/init_chroot.sh"
declare -rx REMOVE_CHROOT_FN="$(dirname "${BASH_SOURCE[0]}")/remove_chroot.fn.sh"
declare -rx REMOVE_WORKSPACE_FN="$(dirname "${BASH_SOURCE[0]}")/remove_workspace.fn.sh"
declare -rx RESTRICT_BINARIES_FN="$(dirname "${BASH_SOURCE[0]}")/restrict_binaries.fn.sh"

declare -axr SYSTEM_DIRS=("/bin" "/lib" "/lib64" "/usr" "/etc")
declare -ar RESTRICTED_CMDS=("sudo" "su" "cron" "sshfs" "mount" "umount")

# Lock
declare -rx LOCK_CHROOT_PREF="lock_chroot"

# Bashrc
declare -xr BASHRC="$(dirname "${BASH_SOURCE[0]}")/.bashrc"
declare -xr ETC_BASHRC="$ETC_STUDENTS/.bashrc"
declare -xr BASH_PREEXEC="$(dirname "${BASH_SOURCE[0]}")/.bash-preexec.sh"
declare -xr ETC_BASH_PREEXEC="$ETC_STUDENTS/.bash-preexec.sh"
declare -xr BASHRC_CNT="$(envsubst '${WORKSPACE_USERS}'  < "$BASHRC")"

# Подключение скрипта с функциями монтирования
source "$MOUNTS_FN" || { log_message "error" "Failed to source '$MOUNTS_FN'" >&2; return "$EXIT_GENERAL_ERROR"; }

source "$RESTRICT_BINARIES_FN" || { log_message "error" "Failed to source '$RESTRICT_BINARIES_FN'" >&2; return "$EXIT_GENERAL_ERROR"; }

return 0