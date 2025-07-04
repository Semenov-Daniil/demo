#!/bin/bash

# setup_cron.sh - Скрипт для настройки cron-заданий
# Расположение: bash/setup/setup_cron.sh

set -euo pipefail

# Подключение локального config.sh
source "$(dirname "${BASH_SOURCE[0]}")/config.sh" || {
    echo "Failed to source local config.sh"
    exit 1
}

[[ "$LOG_FILE" == "${DEFAULT_LOG_FILE:-}" ]] && LOG_FILE="cron.log"

# Массив cron-заданий
declare -A CRON_JOBS=(
    ["$CHECK_SERVICES"]="0 0 * * *"
    ["$SETUP_QUEUE"]="5 0 * * *"
    ["$SETUP_CHROOT"]="10 0 * * *"
    ["$SETUP_SAMBA"]="15 0 * * *"
    ["$CONFIG_SAMBA"]="20 0 * * *"
    ["$SETUP_SSH"]="25 0 * * *"
    ["$CONFIG_SSH"]="30 0 * * *"
    ["$SETUP_APACHE"]="35 0 * * *"
    ["$CLEAN_LOGS"]="40 0 * * *"
)

start_cron_services() {
    local service="cron"
    systemctl is-active --quiet "$service" && return 0
    systemctl enable "$service" && systemctl start "$service" >/dev/null 2>&1 || {
        log_message "error" "Failed to start service $service"
        return "$EXIT_CRON_START_FAILED"
    }

    local start_time=$SECONDS
    while ! systemctl is-active --quiet "$service"; do
        (( SECONDS - start_time > SERVICE_START_TIMEOUT )) && {
            log_message "error" "Service '$service' failed to start within ${SERVICE_START_TIMEOUT}s"
            return "$EXIT_CRON_START_FAILED"
        }
        sleep 0.01
    done

    log_message "info" "All cron services are running"
    return 0
}

setup_cron_jobs() {
    tmp_crontab=$(mktemp)
    crontab -l > "$tmp_crontab" 2>/dev/null || true

    for script in "${!CRON_JOBS[@]}"; do
        [[ -f "$script" ]] || { log_message "error" "Cron script '$script' does not exist"; return 1; }
        schedule="${CRON_JOBS[$script]}"
        awk -v s="$script" '$0 !~ s' "$tmp_crontab" > "${tmp_crontab}.new"
        mv "${tmp_crontab}.new" "$tmp_crontab"
        echo "$schedule /bin/bash $script" >> "$tmp_crontab"
    done

    crontab "$tmp_crontab" || { 
        log_message "error" "Failed to update crontab"
        rm -f "$tmp_crontab"
        return 1
    }
    rm -f "$tmp_crontab"

    log_message "info" "Cron jobs configured successfully with staggered minutes"
    return 0
}

start_cron_services || exit $?
setup_cron_jobs || exit $?

exit 0