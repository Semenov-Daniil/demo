#!/bin/bash
# logging.fn.sh - Скрипт экспортирующий функции логирования
# Расположение: bash/logging/logging.fn.sh

set -euo pipefail

: "${LOGS_DIR:="$(dirname "$(dirname "$(realpath "${BASH_SOURCE[0]}")")")/logs"}"
: "${TMP_DIR:="/tmp"}"
: "${LOCK_LOG_PREF:="lock_log"}"
: "${SITE_USER:="www-data"}"
: "${SITE_GROUP:="www-data"}"
declare -p LOG_LEVELS || {
    declare -ax LOG_LEVELS=("info" "warning" "error")
}

[ $# -ne 1 ] && {
    echo "Usage logging.fn.sh: <logfile> - required argument"
    return 1
}

PATH_LOG_FILE="${LOGS_DIR}/$(echo "$1" | sed 's/[\\\/]//g')"

# Создание директории логов
make_log_dir() {
    local log_dir=$(dirname "$1")
    local lockfile="${TMP_DIR}/${LOCK_LOG_PREF}_$(echo "$log_dir" | sha256sum | cut -d' ' -f1).lock"
    (
        flock -x 200 || { echo "Failed to acquire lock for '$log_dir'"; return 1; }
        [[ -d "$log_dir" ]] || {
            mkdir -p "$log_dir" || { echo "Cannot create '$log_dir'"; return 1; }
            chown "${SITE_USER}:${SITE_GROUP}" "$log_dir" || { echo "Failed to set ownership for '$log_dir'"; return 1; }
            chmod 750 "$log_dir" || { echo "Failed to set permissions for '$log_dir'"; return 1; }
        }
    ) 200>"$lockfile"
    return $?
}

# Запись в лог-файл
write_log() {
    local log_file="$1" log_entry="$2"
    local lockfile="${TMP_DIR}/${LOCK_LOG_PREF}_$(echo "$log_file" | sha256sum | cut -d' ' -f1).lock"
    (
        flock -x 200 || { echo "Failed to acquire lock for '$log_file'"; return 1; }
        echo "$log_entry" >> "$log_file" || { echo "Failed to write to '$log_file'"; return 1; }
        local current_perms=$(stat -c %a "$log_file" 2>/dev/null || echo "unknown")
        local current_owner=$(stat -c %U:%G "$log_file" 2>/dev/null || echo "unknown")
        [[ "$current_owner" != "$SITE_USER:$SITE_GROUP" ]] && chown "$SITE_USER:$SITE_GROUP" "$log_file" 2>/dev/null || { echo "Failed to set ownership for '$log_file'"; return 1; }
        [[ "$current_perms" != "750" ]] && chmod 750 "$log_file" 2>/dev/null || { echo "Failed to set permissions for '$log_file'"; return 1; }
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
    local log_levels=${LOG_LEVELS[@]} level="$1" valid=0
    for lvl in "${log_levels[@]}"; do
        [[ "$lvl" == "$level" ]] && { valid=1; break; }
    done
    [[ $valid -eq 0 ]] && { echo "Invalid log level: '$level'"; return 1; }
}

# Основная функция логирования
log_message() {
    local level="$1" message="$2"
    check_level "$level" || return $?
    make_log_dir "$PATH_LOG_FILE"
    local log_entry
    log_entry=$(format_log "$level" "$message")
    write_log "$PATH_LOG_FILE" "$log_entry"
    print_log "$level" "$message"
    return ${EXIT_SUCCESS}
}

export -f log_message check_level make_log_dir write_log print_log format_log
return ${EXIT_SUCCESS}