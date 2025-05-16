#!/bin/bash

# setup_cron.sh - Скрипт для настройки cron-заданий
# Расположение: bash/utils/setup_cron.sh

set -euo pipefail

# Подключение локального config.sh
source "$(dirname "${BASH_SOURCE[0]}")/config.sh" || {
    echo "Failed to source local config.sh"
    exit 1
}

# Массив cron-заданий
declare -A CRON_JOBS=(
    ["${SCRIPTS_DIR}/samba/samba_reload.sh"]="*/10 * * * * *"
    ["${SCRIPTS_DIR}/samba/check_setup_samba.sh"]="0 * * * *"
    ["${SCRIPTS_DIR}/ssh/check_setup_ssh.sh"]="0 * * * *"
    ["${SCRIPTS_DIR}/logging/clean_logs.sh"]="0 0 * * *"
)

# Проверка и установка cron-заданий
tmp_crontab=$(mktemp)
crontab -l > "$tmp_crontab" 2>/dev/null || true

# Добавление новых заданий cron
for script in "${!CRON_JOBS[@]}"; do
    [[ -f "$script" ]] || { log_message "error" "Cron script '$script' does not exist"; exit 1; }
    schedule="${CRON_JOBS[$script]}"
    awk -v s="$script" '$0 !~ s' "$tmp_crontab" > "${tmp_crontab}.new"
    mv "${tmp_crontab}.new" "$tmp_crontab"
    echo "$schedule /bin/bash $script" >> "$tmp_crontab"
done

# Применяем crontab
crontab "$tmp_crontab" || { log_message "error" "Failed to update crontab"; exit 1; }
rm -f "$tmp_crontab"

log_message "info" "Cron jobs configured successfully"

exit 0