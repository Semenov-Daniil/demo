#!/bin/bash

# apache_reload.sh - Скрипт для отложенной перезагрузки Apache2
# Расположение: bash/vhost/apache_reload.sh

set -euo pipefail

# Подключение локального config.sh
source "$(dirname "${BASH_SOURCE[0]}")/config.sh"  || {
    echo "Failed to source local config.sh"
    exit 1
}

# Установка переменных
LOCKFILE="/tmp/lock_apache_reload.lock"

[[ -f "$RELOAD_NEEDED_FILE" ]] || exit 0

# Ждать 5 секунд для объединения изменений
sleep 5

(
    flock -x 200 || { log_message "error" "Failed to acquire lock"; exit 1; }

    [[ -f "$RELOAD_NEEDED_FILE" ]] || exit 0
    
    # Перезагрузка Apache
    systemctl reload apache2 >/dev/null 2>>"$LOG_FILE" && {
        log_message "info" "Apache configuration reloaded successfully"
        rm -f "$RELOAD_NEEDED_FILE"
    } || {
        log_message "warning" "Failed to reload Apache configuration"
    }
) 200>"$LOCKFILE" &

exit 0