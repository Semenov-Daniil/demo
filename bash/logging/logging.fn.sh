#!/bin/bash
# logging.fn.sh - Скрипт экспортирующий функции логирования
# Расположение: bash/logging/logging.fn.sh

set -euo pipefail

# Подключение локального config.sh
source "$(dirname "${BASH_SOURCE[0]}")/config.sh" || {
    echo "Failed to source local config.sh"
    exit 1
}

: "${LOGDIR:="$(dirname "$(dirname "$(realpath "${BASH_SOURCE[0]}")")")/logs"}"
: "${LOG_FILE:="common.log"}"
: "${TMP_DIR:="/tmp"}"
: "${LOCK_LOG_PREF:="lock_log"}"
: "${SITE_USER:="www-data"}"
: "${SITE_GROUP:="www-data"}"
if ! declare -p LOG_LEVELS >/dev/null 2>&1; then
    declare -rax LOG_LEVELS=("info" "warning" "error")
fi

# Создание директории логов
make_log_dir() {
    local log_dir=$(dirname "$1")
    [[ -d "$log_dir" ]] || {
        mkdir -p "$log_dir" || { echo "Cannot create '$log_dir'"; return 1; }
        chown "${SITE_USER}:${SITE_GROUP}" "$log_dir" || { echo "Failed to set ownership for '$log_dir'"; return 1; }
        chmod 755 "$log_dir" || { echo "Failed to set permissions for '$log_dir'"; return 1; }
    }
    return $?
}

# Запись в лог-файл
write_log() {
    local log_file="$1" log_entry="$2"
    echo "$log_entry" >> "$log_file" || { echo "Failed to write to '$log_file'"; return 1; }
    update_permissions "$log_file" 755 "$SITE_USER:$SITE_GROUP"
    return $?
}

# Вывод в консоль
print_log() {
    local level="$1" message="$2"
    local level_upper
    level_upper=$(echo "$level" | tr '[:lower:]' '[:upper:]')
    echo "[$level_upper] $message" >&2
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
    local log_levels=("${LOG_LEVELS[@]}") level="$1" valid=0
    for lvl in "${log_levels[@]}"; do
        [[ "$lvl" == "$level" ]] && { valid=1; break; }
    done
    [[ $valid -eq 0 ]] && { echo "Invalid log level: '$level'"; return 1; }
    return 0
}

# Основная функция логирования
log_message() {
    [ $# -ne 2 ] && {
        echo "Usage log_message: <level> <message>"
        return 1
    }
    local level="$1" message="$2"
    local log_file="${LOG_FILE//\//}"
    local path_log_file="$LOGDIR/$log_file"
    check_level "$level" || return $?
    make_log_dir "$path_log_file" || return $?
    local log_entry
    log_entry=$(format_log "$level" "$message")
    with_lock "$TMP_DIR/${LOCK_LOG_PREF}_${log_file//./_}.lock" write_log "$path_log_file" "$log_entry" || return $?
    print_log "$level" "$message"
    return 0
}

export -f log_message check_level make_log_dir write_log print_log format_log
return 0