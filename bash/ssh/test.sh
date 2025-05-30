#!/bin/bash
# add_samba_user.sh - Скрипт добавления пользователя Samba
# Расположение: bash/samba/add_samba_user.sh

set -euo pipefail

# Подключение локального config.sh
source "$(dirname "${BASH_SOURCE[0]}")/config.sh" || {
    echo "Failed to source local config.sh"
    exit 1
}

# Подключение скрипта настройки chroot
source "$SETUP_CHROOT" || {
    echo "Failed to source script '$SETUP_CHROOT'" >&2
    exit "$EXIT_GENERAL_ERROR"
}

exit 0