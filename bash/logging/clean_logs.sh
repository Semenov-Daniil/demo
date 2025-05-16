#!/bin/bash

# clean_logs.sh - Скрипт для очистки лог-файлов в /logs
# Расположение: bash/logging/clean_logs.sh

set -euo pipefail

# Подключение глобального config.sh
source "$(dirname "${BASH_SOURCE[0]}")/../config.sh" || {
    echo "Failed to source global config.sh"
    exit 1
}

# Очистка старых записей в лог-файлах
clean_logs() {
    local log_file lockfile temp_file cutoff_date
    cutoff_date=$(date -d "${LOG_RETENTION_DAYS} days ago" +%Y-%m-%d 2>/dev/null || date -v -${LOG_RETENTION_DAYS}d +%Y-%m-%d)

    for log_file in "$LOGS_DIR"/*.log; do
        [[ -f "$log_file" ]] || continue
        lockfile="${TMP_DIR}/${LOCK_LOG_PREF}_$(echo "$log_file" | sha256sum | cut -d' ' -f1).lock"
        (
            flock -x 200 || { log_message "error" "Failed to acquire lock for $log_file"; return ${EXIT_GENERAL_ERROR}; }
            
            temp_file=$(mktemp)
            awk -v cutoff="$cutoff_date" '
                /^\[[0-9]{4}-[0-9]{2}-[0-9]{2}/ {
                    log_date=substr($0, 2, 10);
                    if (log_date >= cutoff) print
                }
                !/^\[/ { if (last_line >= cutoff) print }
                { last_line=log_date }
            ' "$log_file" > "$temp_file"
            
            mv "$temp_file" "$log_file" || {
                log_message "error" "Failed to update $log_file"
                return ${EXIT_GENERAL_ERROR}
            }
            chown "${SITE_USER}:${SITE_GROUP}" "$log_file" && chmod 750 "$log_file" || {
                log_message "error" "Failed to set permissions for $log_file"
                return ${EXIT_GENERAL_ERROR}
            }
        ) 200>"$lockfile"
        [[ $? -eq 0 ]] || {
            log_message "error" "Failed to clean $log_file"
            exit ${EXIT_GENERAL_ERROR}
        }
    done
}

# Выполнение очистки
clean_logs

log_message "info" "Log files in '$LOGS_DIR' cleaned successfully"

exit ${EXIT_SUCCESS}