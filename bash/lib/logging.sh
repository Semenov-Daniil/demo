#!/bin/bash
# logging.sh - Функции для логирования
# Расположение: lib/logging.sh

set -euo pipefail

# Проверка, что скрипт не запущен напрямую
if [[ "${BASH_SOURCE[0]}" == "$0" ]]; then
    echo "This script ('$0') is meant to be sourced" >&2
    return 1
fi

# Установка переменных по умолчанию
: "${LOGS_DIR:="$(dirname "$(dirname "$(realpath "${BASH_SOURCE[0]}")")")/logs"}"
: "${SITE_USER:=www-data}"
: "${SITE_GROUP:=www-data}"
: "${EXIT_SUCCESS:=0}"
: "${EXIT_GENERAL_ERROR:=1}"
: "${DEFAULT_LOG:=${LOGS_DIR}/logs.log}"
: "${LOG_RETENTION_DAYS:=30}"

# Проверка обязательного аргумента
if [[ $# -eq 0 || ($# -eq 1 && "$1" == "${BASH_SOURCE[0]}") ]]; then
    echo "Argument <filename> is required" >&2
    return ${EXIT_GENERAL_ERROR}
fi

LOG_FILE="${LOGS_DIR}/$1"

if [[ -z "$1" ]]; then
    echo "Argument <filename> is required" >&2
    return ${EXIT_GENERAL_ERROR}
fi

# Создание директории логов
make_log_dir() {
    local log_dir
    log_dir=$(dirname "$1")
    if [[ ! -d "$log_dir" ]]; then
        mkdir -p "$log_dir"
        chown "${SITE_USER}:${SITE_GROUP}" "$log_dir"
        chmod 750 "$log_dir"
    fi
}

# Очистка старых строк в логах
clean_old_log() {
    local log_file="$1"
    if [[ ! -f "$log_file" ]]; then
        return
    fi
    local cutoff_date
    cutoff_date=$(date -d "${LOG_RETENTION_DAYS} days ago" +%Y-%m-%d 2>/dev/null || date -v -${LOG_RETENTION_DAYS}d +%Y-%m-%d)
    local temp_file
    temp_file=$(mktemp)
    local keep_line=1
    while IFS= read -r line; do
        if [[ "$line" =~ ^\[[0-9]{4}-[0-9]{2}-[0-9]{2} ]]; then
            local log_date="${line:1:10}"
            if [[ "$log_date" < "$cutoff_date" ]]; then
                keep_line=0
            else
                keep_line=1
            fi
        fi
        if [[ $keep_line -eq 1 ]]; then
            echo "$line" >> "$temp_file"
        fi
    done < "$log_file"
    mv "$temp_file" "$log_file"
    if [[ -f "$log_file" ]]; then
        chown "${SITE_USER}:${SITE_GROUP}" "$log_file"
        chmod 750 "$log_file"
    fi
}

# Запись в лог-файл
write_log() {
    local log_file="$1"
    local log_entry="$2"
    echo "$log_entry" >> "$log_file"
    chown "${SITE_USER}":"${SITE_GROUP}" "$log_file"
    chmod 750 "$log_file"
}

# Вывод в консоль
print_log() {
    local level="$1"
    local message="$2"
    local level_upper
    level_upper=$(echo "$level" | tr '[:lower:]' '[:upper:]')
    echo "[$level_upper] $message"
}

# Формирование записи лога
format_log() {
    local level="$1"
    local message="$2"
    local timestamp
    timestamp=$(date '+%Y-%m-%d %H:%M:%S')
    local level_upper
    level_upper=$(echo "$level" | tr '[:lower:]' '[:upper:]')
    echo "[$timestamp] [$level_upper] $message"
}

# Проверка уровня лога
check_level() {
    local log_levels=("info" "warning" "error")
    local level="$1"
    local valid=0
    for lvl in "${log_levels[@]}"; do
        if [[ "$lvl" == "$level" ]]; then
            valid=1
            break
        fi
    done
    if [[ $valid -eq 0 ]]; then
        echo "Invalid log level: '$level'" >&2
        return ${EXIT_GENERAL_ERROR}
    fi
}

# Основная функция логирования
log_message() {
    local level="$1"
    local message="$2"
    check_level "$level" || return $?
    make_log_dir "$LOG_FILE"
    clean_old_log "$LOG_FILE"
    local log_entry
    log_entry=$(format_log "$level" "$message")
    write_log "$LOG_FILE" "$log_entry"
    print_log "$level" "$message"
    return ${EXIT_SUCCESS}
}

# Экспорт функции
export -f log_message
export -f check_level
export -f make_log_dir
export -f clean_old_log
export -f format_log
export -f write_log
export -f print_log

return ${EXIT_SUCCESS}