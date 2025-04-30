#!/bin/bash

# Скрипт для создания директорий
# Предназначен для подключения через source

# Проверка на прямой запуск
if [[ "${BASH_SOURCE[0]}" == "$0" ]]; then
    echo "[ERROR]: This script ('$0') is meant to be sourced, not executed directly" >&2
    exit 1
fi

# Проверка переменных
if ! declare -p ERR_FILE_NOT_FOUND >/dev/null 2>&1; then
    echo "[ERROR]: ERR_FILE_NOT_FOUND is not defined" >&2
fi

if ! declare -p ERR_GENERAL >/dev/null 2>&1; then
    echo "[ERROR]: ERR_GENERAL is not defined" >&2
fi

if ! declare -p LOG_INFO >/dev/null 2>&1; then
    echo "[ERROR]: LOG_INFO is not defined" >&2
fi

if ! declare -p LOG_ERROR >/dev/null 2>&1; then
    echo "[ERROR]: LOG_ERROR is not defined" >&2
fi

# Проверка наличия функции log
if ! declare -F log >/dev/null; then
    echo "[ERROR]: Logging function 'log' not defined after sourcing '$LOGGING_SCRIPT'" >&2
    return $ERR_FILE_NOT_FOUND
fi

create_directories() {
    local -a dirs=("${@:1:$#-2}") # Все аргументы, кроме последних двух
    local perms="${@: -2:1}"      # Предпоследний аргумент — права (например, 755)
    local owner="${@: -1:1}"      # Последний аргумент — владелец (например, root:root)
    local dir

    # Проверка валидности perms (например, 755, 700)
    if [[ ! "$perms" =~ ^[0-7]{3}$ ]]; then
        log "$LOG_ERROR: Invalid permissions format: $perms (expected octal, e.g., 755)"
        return 1
    fi

    # Проверка валидности owner (формат user:group или user)
    if [[ ! "$owner" =~ ^[a-zA-Z0-9._-]+(:[a-zA-Z0-9._-]+)?$ ]]; then
        log "$LOG_ERROR: Invalid owner format: $owner (expected user:group or user)"
        return 1
    fi

    for dir in "${dirs[@]}"; do
        if [[ -z "$dir" ]]; then
            log "$LOG_ERROR: Directory path is empty"
            return 1
        fi
        if [[ ! -d "$dir" ]]; then
            if ! mkdir -p "$dir" 2>>"$LOG_FILE"; then
                log "$LOG_ERROR: Cannot create directory '$dir'"
                return 1
            fi
            log "$LOG_INFO: Created directory '$dir'"
        fi
        # Проверка и коррекция прав
        current_perms=$(stat -c %a "$dir")
        current_owner=$(stat -c %U:%G "$dir")
        if [[ "$current_perms" != "$perms" || "$current_owner" != "$owner" ]]; then
            if ! chown "$owner" "$dir" 2>>"$LOG_FILE" || ! chmod "$perms" "$dir" 2>>"$LOG_FILE"; then
                log "$LOG_ERROR: Failed to set permissions $perms or owner $owner for '$dir'"
                exit $ERR_GENERAL
            fi
            log "$LOG_INFO: Set permissions $perms and owner $owner for '$dir'"
        fi
    done
    return 0
}

# Экспорт функции
export -f create_directories

return 0