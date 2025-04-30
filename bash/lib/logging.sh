#!/bin/bash

# Скрипт логирования
# Экспортирует функцию log и массив аргументов ARGS

if [[ "${BASH_SOURCE[0]}" == "$0" ]]; then
    echo "[ERROR]: This script ('$0') is meant to be sourced, not executed directly" >&2
    exit 1
fi

# Настройка логирования
LOG_FILE="${DEFAULT_LOG:-logs/logs.log}"

# Парсинг аргументов
while [[ $# -gt 0 ]]; do
    case "$1" in
        --log=*)
            LOG_FILE="${1#--log=}"
            shift
            ;;
        *)
            ARGS+=("$1")
            shift
            ;;
    esac
done

# Создание директории логов
LOG_DIR=$(dirname "$LOG_FILE")
if [[ ! -d "$LOG_DIR" ]]; then
    mkdir -p "$LOG_DIR" 2>/dev/null || {
        echo "[ERROR]: Cannot create log directory '$LOG_DIR'" >&2
        exit 1
    }
fi

# Настройка файла логов
if [[ ! -f "$LOG_FILE" ]]; then
    touch "$LOG_FILE" || {
        echo "[ERROR]: Cannot create log file '$LOG_FILE'" >&2
        return 1
    }
    chown "${SITE_USER}:${SITE_GROUP}" "$LOG_FILE" || {
        echo "[ERROR]: Cannot change ownership of log file '$LOG_FILE'" >&2
        return 1
    }
    chmod 660 "$LOG_FILE" || {
        echo "[ERROR]: Cannot change permissions of log file '$LOG_FILE'" >&2
        return 1
    }
elif [[ ! -w "$LOG_FILE" ]]; then
    echo "[WARNING]: Log file '$LOG_FILE' is not writable, attempting to fix permissions" >&2
    chown "${SITE_USER}:${SITE_GROUP}" "$LOG_FILE" && chmod 660 "$LOG_FILE" || {
        echo "[ERROR]: Cannot fix permissions of log file '$LOG_FILE'" >&2
        return 1
    }
fi

# Функция логирования
log() {
    echo "[$(date '+%Y-%m-%d %H:%M:%S')] $1" >> "$LOG_FILE"
    echo "$1"
}

# Экспорт функции и переменных
export -f log
export ARGS

return 0