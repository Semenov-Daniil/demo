#!/bin/bash

# logging.fn.sh - Функции для логирования
# Расположение: bash/logging/logging.fn.sh

set -euo pipefail

# Проверка обязательного аргумента
[[ $# -eq 0 || ($# -eq 1 && "$1" == "${BASH_SOURCE[0]}") ]] || {
    echo "Argument <filename> is required"
    return ${EXIT_GENERAL_ERROR}
}

LOG_FILE="${LOGS_DIR}/$1"

[[ -z "$1" ]] || {
    echo "Argument <filename> is required"
    return ${EXIT_GENERAL_ERROR}
}

# Создание директории логов
make_log_dir() {
    local log_dir
    log_dir=$(dirname "$1")
    local lockfile="${TMP_DIR}/${LOCK_LOG_PREF}_$(echo "$log_dir" | sha256sum | cut -d' ' -f1).lock"
    (
        flock -x 200 || { echo "Failed to acquire lock for '$log_dir'"; return ${EXIT_GENERAL_ERROR}; }
        [[ -d "$log_dir" ]] || {
            mkdir -p "$log_dir" || { echo "Cannot create '$log_dir'"; return ${EXIT_GENERAL_ERROR}; }
            chown "${SITE_USER}:${SITE_GROUP}" "$log_dir" || { echo "Failed to set ownership for '$log_dir'"; return ${EXIT_GENERAL_ERROR}; }
            chmod 750 "$log_dir" || { echo "Failed to set permissions for '$log_dir'"; return ${EXIT_GENERAL_ERROR}; }
        }
    ) 200>"$lockfile"
    return $?
}

# Запись в лог-файл
write_log() {
    local log_file="$1" log_entry="$2"
    local lockfile="${TMP_DIR}/${LOCK_LOG_PREF}_$(echo "$log_file" | sha256sum | cut -d' ' -f1).lock"
    (
        flock -x 200 || { echo "Failed to acquire lock for '$log_file'"; return ${EXIT_GENERAL_ERROR}; }
        echo "$log_entry" >> "$log_file" || { echo "Failed to write to '$log_file'"; return ${EXIT_GENERAL_ERROR}; }
        chown "${SITE_USER}:${SITE_GROUP}" "$log_file" || { echo "Failed to set ownership for '$log_file'"; return ${EXIT_GENERAL_ERROR}; }
        chmod 750 "$log_file" || { echo "Failed to set permissions for '$log_file'"; return ${EXIT_GENERAL_ERROR}; }
    ) 200>"$lockfile"
    return $?
}

# Вывод в консоль
print_log() {
    local level="$1" message="$2"
    local level_upper
    level_upper=$(echo "$level" | tr '[:lower:]' '[:upper:]')
    echo "[$level_upper] $message"
}

# Формирование записи лога
format_log() {
    local level="$1" message="$2"
    local timestamp
    timestamp=$(date '+%Y-%m-%d %H:%M:%S')
    local level_upper
    level_upper=$(echo "$level" | tr '[:lower:]' '[:upper:]')
    echo "[$timestamp] [$level_upper] $message"
}

# Проверка уровня лога
check_level() {
    local log_levels=("info" "warning" "error") level="$1" valid=0
    for lvl in "${log_levels[@]}"; do
        [[ "$lvl" == "$level" ]] && { valid=1; break; }
    done
    [[ $valid -eq 0 ]] && { echo "Invalid log level: '$level'"; return ${EXIT_GENERAL_ERROR}; }
}

# Основная функция логирования
log_message() {
    local level="$1" message="$2"
    check_level "$level" || return $?
    make_log_dir "$LOG_FILE"
    local log_entry
    log_entry=$(format_log "$level" "$message")
    write_log "$LOG_FILE" "$log_entry"
    print_log "$level" "$message"
    return ${EXIT_SUCCESS}
}

export -f log_message check_level make_log_dir write_log print_log format_log
return ${EXIT_SUCCESS}