#!/bin/bash

if [[ "${BASH_SOURCE[0]}" == "$0" ]]; then
    echo "Error: This script ('$0') is meant to be sourced, not executed directly" >&2
    exit 1
fi

# Настройка логирования
CALLER_SCRIPT=$(basename "${BASH_SOURCE[1]}" .sh)
DEFAULT_LOG="logs/${CALLER_SCRIPT}.log"
LOG_FILE="$DEFAULT_LOG"

# Парсинг флага --log
for arg in "$@"; do
    if [[ "$arg" =~ ^--log=(.*)$ ]]; then
        LOG_FILE="${BASH_REMATCH[1]}"
        break
    fi
done

LOG_DIR=$(dirname "$LOG_FILE")
if [[ ! -d "$LOG_DIR" ]]; then
    mkdir -p "$LOG_DIR" 2>/dev/null || {
        echo "Error: Cannot create log directory '$LOG_DIR'" >&2
        exit 1
    }
fi

if [[ -e "$LOG_FILE" && ! -f "$LOG_FILE" ]]; then
    echo "Error: Log file '$LOG_FILE' exists but is not a regular file" >&2
    exit 1
fi

if [[ ! -f "$LOG_FILE" ]]; then
    touch "$LOG_FILE" 2>/dev/null || {
        echo "Error: Cannot create log file '$LOG_FILE'" >&2
        exit 1
    }
    chown $SITE_USER:$SITE_GROUP "$LOG_FILE"
    chmod 660 "$LOG_FILE"
elif [[ ! -w "$LOG_FILE" ]]; then
    chown $SITE_USER:$SITE_GROUP "$LOG_FILE" 2>/dev/null || {
        echo "Error: Cannot change ownership of log file '$LOG_FILE'" >&2
        exit 1
    }
    chmod 660 "$LOG_FILE" 2>/dev/null || {
        echo "Error: Cannot change permissions of log file '$LOG_FILE'" >&2
        exit 1
    }
    echo "Info: Corrected permissions for log file '$LOG_FILE' to 660 $SITE_USER:$SITE_GROUP"
fi

# Определение функции log
log() {
    echo "[$(date '+%Y-%m-%d %H:%M:%S')] $1" | tee -a "$LOG_FILE"
}

export -f log