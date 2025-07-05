#!/bin/bash
# clean_logs.sh - Скрипт очистки лог-файлов в /logs
# Расположение: bash/logging/clean_logs.sh

set -euo pipefail

# Подключение глобального config.sh
source "$(dirname "${BASH_SOURCE[0]}")/../config.sh" || {
    echo "Failed to source global config.sh"
    exit 1
}

# Функция очистки старых записей в лог-файлах
clean_logs() {
    local log_file lockfile temp_file cutoff_date
    cutoff_date=$(date -d "${LOG_RETENTION_DAYS} days ago" +%Y-%m-%d 2>/dev/null || date -v -${LOG_RETENTION_DAYS}d +%Y-%m-%d)

    local log_name
    for log_file in "$LOGDIR"/*.log; do
        [[ -f "$log_file" ]] || continue
        log_name="$(basename $log_file)"
        with_lock "$TMP_DIR/${LOCK_LOG_PREF}_${log_name//./_}.lock" clean_log "$log_file" "$cutoff_date" || return $?
    done

    return 0
}

clean_log() {
    local log_file=$1 cutoff_date=$2
    temp_file=$(mktemp)

    awk -v cutoff="$cutoff_date" '
        /^\[[0-9]{4}-[0-9]{2}-[0-9]{2}/ {
            log_date=substr($0, 2, 10);
            if (log_date >= cutoff) print
        }
        !/^\[/ { if (last_line >= cutoff) print }
        { last_line=log_date }
    ' "$log_file" > "$temp_file"
    
    mv "$temp_file" "$log_file" || return 1
    chown "${SITE_USER}:${SITE_GROUP}" "$log_file" && chmod 750 "$log_file" || return 1

    return 0
}

# Выполнение очистки
clean_logs || exit $?