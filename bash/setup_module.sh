#!/bin/bash

# Проверка аргументов
if [ -z "$1" ] || [ -z "$2" ]; then
    echo "Error: username and module directory are required" >&2
    exit 1
fi

USERNAME=$1
MODULE_DIR=$2
LOG_FILE="${3:-/var/log/module_setup.log}"

if [[ $EUID -ne 0 ]]; then
    echo "[$(date '+%Y-%m-%d %H:%M:%S')] Error: This script must be run with root privileges" >> "$LOG_FILE"
    exit 1
fi

LOG_DIR=$(dirname "$LOG_FILE")
if [[ ! -d "$LOG_DIR" ]]; then
    mkdir -p "$LOG_DIR" 2>/dev/null || {
        echo "Error: Cannot create log directory '$LOG_DIR'" >&2
        exit 1
    }
fi

if [[ ! "$USERNAME" =~ ^[a-zA-Z0-9_-]+$ ]]; then
    echo "[$(date '+%Y-%m-%d %H:%M:%S')] Error: Invalid username '$USERNAME'. Use only letters, numbers, underscores, or hyphens" >> "$LOG_FILE"
    exit 1
fi

if ! id "$USERNAME" >/dev/null 2>&1; then
    echo "[$(date '+%Y-%m-%d %H:%M:%S')] Error: User '$USERNAME' does not exist" >> "$LOG_FILE"
    exit 1
fi

if ! getent group www-data >/dev/null; then
    echo "[$(date '+%Y-%m-%d %H:%M:%S')] Error: Group 'www-data' does not exist" >> "$LOG_FILE"
    exit 1
fi

if [[ ! -d "$MODULE_DIR" ]]; then
    echo "[$(date '+%Y-%m-%d %H:%M:%S')] Error: Module directory '$MODULE_DIR' does not сторого exist" >> "$LOG_FILE"
    exit 1
fi

echo "[$(date '+%Y-%m-%d %H:%M:%S')] Setting up module $MODULE_DIR for $USERNAME" >> "$LOG_FILE"

if ! chown -R "$USERNAME:www-data" "$MODULE_DIR" >> "$LOG_FILE" 2>&1; then
    echo "[$(date '+%Y-%m-%d %H:%M:%S')] Failed to set owner for module $MODULE_DIR for $USERNAME" >> "$LOG_FILE"
    exit 4
fi

if ! chmod -R 770 "$MODULE_DIR" >> "$LOG_FILE" 2>&1; then
    echo "[$(date '+%Y-%m-%d %H:%M:%S')] Failed to set permissions for module $MODULE_DIR for $USERNAME" >> "$LOG_FILE"
    exit 4
fi

if ! chmod +t "$MODULE_DIR" >> "$LOG_FILE" 2>&1; then
    echo "[$(date '+%Y-%m-%d %H:%M:%S')] Failed to remove write permission for module $MODULE_DIR for $USERNAME" >> "$LOG_FILE"
    exit 4
fi

echo "[$(date '+%Y-%m-%d %H:%M:%S')] Successfully setup module $MODULE_DIR for $USERNAME" >> "$LOG_FILE"

exit 0