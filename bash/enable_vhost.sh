#!/bin/bash

if [ -z "$1" ]; then
    echo "Error: uniqueCode is required" >&2
    exit 1
fi

UNIQUE_CODE=$1
LOG_FILE="${2:-/tmp/vhost_setup.log}"

echo "[$(date '+%Y-%m-%d %H:%M:%S')] Enabling virtual host for $UNIQUE_CODE" >> "$LOG_FILE"

a2ensite "$UNIQUE_CODE.conf" >> "$LOG_FILE" 2>&1
if [ $? -ne 0 ]; then
    echo "[$(date '+%Y-%m-%d %H:%M:%S')] Failed to enable virtual host for $UNIQUE_CODE" >> "$LOG_FILE"
    exit 2
fi

systemctl reload apache2 >> "$LOG_FILE" 2>&1
if [ $? -ne 0 ]; then
    echo "[$(date '+%Y-%m-%d %H:%M:%S')] Failed to reload Apache for $UNIQUE_CODE" >> "$LOG_FILE"
    exit 3
fi

echo "[$(date '+%Y-%m-%d %H:%M:%S')] Successfully enabled virtual host for $UNIQUE_CODE" >> "$LOG_FILE"

exit 0