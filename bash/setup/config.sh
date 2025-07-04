#!/bin/bash

# config.sh - Локальный конфигурационный файл для скриптов настройки
# Расположение: bash/setup/config.sh

set -euo pipefail

[[ "${BASH_SOURCE[0]}" == "$0" ]] && {
    echo "This script ('$0') is meant to be sourced"
    exit 1
}

GLOBAL_CONFIG="$(realpath $(dirname "${BASH_SOURCE[0]}")/../config.sh)"
source "$GLOBAL_CONFIG" || {
    echo "Failed to source global config '$GLOBAL_CONFIG'" >&2
    return 1
}

# Коды выхода
declare -rx EXIT_CRON_START_FAILED=50

# Scripts
declare -rx CHECK_SERVICES="$(realpath $(dirname "${BASH_SOURCE[0]}")/check_services.sh)"
declare -rx SETUP_CRON="$(realpath $(dirname "${BASH_SOURCE[0]}")/setup_cron.sh)"
declare -rx SETUP_QUEUE="$(realpath $(dirname "${BASH_SOURCE[0]}")/setup_queue.sh)"
declare -rx SETUP_CHROOT="$SCRIPTS_DIR/chroot/setup_chroot.sh"
declare -rx SETUP_SAMBA="$SCRIPTS_DIR/samba/check_setup_samba.sh"
declare -rx CONFIG_SAMBA="$SCRIPTS_DIR/samba/config_samba.sh"
declare -rx SETUP_SSH="$SCRIPTS_DIR/ssh/check_setup_ssh.sh"
declare -rx CONFIG_SSH="$SCRIPTS_DIR/ssh/config_ssh.sh"
declare -rx SETUP_APACHE="$SCRIPTS_DIR/vhost/check_setup_apache.sh"
declare -rx CLEAN_LOGS="$SCRIPTS_DIR/logging/clean_logs.sh"

return 0