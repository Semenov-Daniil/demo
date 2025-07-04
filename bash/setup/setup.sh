#!/bin/bash

# setup.sh - Утилита для базовой настройки проекта
# Расположение: bash/setup/setup.sh

set -euo pipefail

# Подключение локального config.sh
source "$(dirname "${BASH_SOURCE[0]}")/config.sh" || {
    echo "Failed to source local config.sh"
    exit 1
}

bash "$CHECK_SERVICES" || exit $?
bash "$SETUP_CRON" || exit $?
bash "$SETUP_QUEUE" || exit $?
bash "$SETUP_CHROOT" || exit $?
bash "$SETUP_SAMBA" || exit $?
bash "$CONFIG_SAMBA" || exit $?
bash "$SETUP_SSH" || exit $?
bash "$CONFIG_SSH" || exit $?
bash "$SETUP_APACHE" || exit $?

exit 0