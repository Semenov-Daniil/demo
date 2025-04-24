#!/bin/bash

if [ -z "$1" ]; then
    echo "Error: uniqueCode is required" >&2
    exit 1
fi

UNIQUE_CODE=$1
LOG_FILE="${2:-logs/vhost.log}"

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

if [[ ! "$UNIQUE_CODE" =~ ^[a-zA-Z0-9_-]+$ ]]; then
    echo "[$(date '+%Y-%m-%d %H:%M:%S')] Error: Invalid uniqueCode '$UNIQUE_CODE'. Use only letters, numbers, underscores, or hyphens" >> "$LOG_FILE"
    exit 1
fi

if ! command -v a2ensite >/dev/null; then
    echo "[$(date '+%Y-%m-%d %H:%M:%S')] Error: a2ensite command not found, is Apache installed?" >> "$LOG_FILE"
    exit 1
fi

CONFIG_FILE="/etc/apache2/sites-available/$UNIQUE_CODE.conf"
if [[ ! -f "$CONFIG_FILE" ]]; then
    echo "[$(date '+%Y-%m-%d %H:%M:%S')] Error: Configuration file '$CONFIG_FILE' does not exist" >> "$LOG_FILE"
    exit 1
fi

echo "[$(date '+%Y-%m-%d %H:%M:%S')] Enabling virtual host for $UNIQUE_CODE" >> "$LOG_FILE"

if ! apachectl configtest >> "$LOG_FILE" 2>&1; then
    echo "[$(date '+%Y-%m-%d %H:%M:%S')] Error: Apache configuration test failed for $UNIQUE_CODE" >> "$LOG_FILE"
    exit 2
fi

if ! a2ensite "$UNIQUE_CODE.conf" >> "$LOG_FILE" 2>&1; then
    echo "[$(date '+%Y-%m-%d %H:%M:%S')] Failed to enable virtual host for $UNIQUE_CODE" >> "$LOG_FILE"
    exit 2
fi

if ! systemctl reload apache2 >> "$LOG_FILE" 2>&1; then
    echo "[$(date '+%Y-%m-%d %H:%M:%S')] Failed to reload Apache for $UNIQUE_CODE" >> "$LOG_FILE"
    exit 3
fi

if ! systemctl is-active apache2 >/dev/null 2>&1; then
    echo "[$(date '+%Y-%m-%d %H:%M:%S')] Error: Apache is not active after reload for $UNIQUE_CODE" >> "$LOG_FILE"
    exit 3
fi

echo "[$(date '+%Y-%m-%d %H:%M:%S')] Successfully enabled virtual host for $UNIQUE_CODE" >> "$LOG_FILE"

exit 0