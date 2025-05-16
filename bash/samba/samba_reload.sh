#!/bin/bash

# samba_reload.sh - Скрипт для отложенной перезагрузки Samba
# Расположение: bash/samba/samba_reload.sh

set -euo pipefail

# Подключение локального config.sh
source "$(dirname "${BASH_SOURCE[0]}")/config.sh" "--log=samba.log"  || {
    echo "Failed to source local config.sh"
    exit 1
}

# Установка переменных
LOCKFILE="/tmp/lock_samba_reload.lock"

[[ -f "$RELOAD_NEEDED_FILE" ]] || exit 0

# Ждать 5 секунд для объединения изменений
sleep 5

(
    flock -x 200 || { log_message "error" "Failed to acquire lock"; exit 1; }

    [[ -f "$RELOAD_NEEDED_FILE" ]] || exit 0
    
    # Перезагрузка Samba
    smbcontrol smbd reload-config >/dev/null 2>>"$LOG_FILE" && {
        log_message "info" "Samba configuration reloaded successfully"
        rm -f "$RELOAD_NEEDED_FILE"
    } || {
        log_message "warning" "Failed to reload Samba configuration"
    }
) 200>"$LOCKFILE" &

exit 0