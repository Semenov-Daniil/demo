#!/bin/bash

if [ -z "$1" ] || [ -z "$2" ]; then
    echo "Error: username and password are required" >&2
    exit 1
fi

USERNAME=$1
PASSWORD=$2
LOG_FILE="${3:-logs/setup_samba.log}"

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

if ! command -v smbpasswd >/dev/null; then
    echo "[$(date '+%Y-%m-%d %H:%M:%S')] Error: smbpasswd command not found, is Samba installed?" >> "$LOG_FILE"
    exit 1
fi

if ! id "$USERNAME" >/dev/null 2>&1; then
    echo "[$(date '+%Y-%m-%d %H:%M:%S')] Error: System user '$USERNAME' does not exist" >> "$LOG_FILE"
    exit 1
fi

echo "[$(date '+%Y-%m-%d %H:%M:%S')] Setting up Samba for $USERNAME" >> "$LOG_FILE"

echo -e "$PASSWORD\n$PASSWORD" | smbpasswd -s -a "$USERNAME" >> "$LOG_FILE" 2>&1
if [ $? -ne 0 ]; then
    echo "[$(date '+%Y-%m-%d %H:%M:%S')] Failed to setup Samba user $USERNAME" >> "$LOG_FILE"
    exit 2
fi

for service in smbd nmbd; do
    if systemctl is-enabled "$service" >/dev/null 2>&1; then
        if ! systemctl restart "$service" >> "$LOG_FILE" 2>&1; then
            echo "[$(date '+%Y-%m-%d %H:%M:%S')] Failed to restart $service for $USERNAME" >> "$LOG_FILE"
            exit 3
        fi
        if ! systemctl is-active "$service" >/dev/null 2>&1; then
            echo "[$(date '+%Y-%m-%d %H:%M:%S')] Error: $service is not active after restart" >> "$LOG_FILE"
            exit 3
        fi
    else
        echo "[$(date '+%Y-%m-%d %H:%M:%S')] Warning: $service is not enabled, skipping restart" >> "$LOG_FILE"
    fi
done

echo "[$(date '+%Y-%m-%d %H:%M:%S')] Successfully setup Samba for $USERNAME" >> "$LOG_FILE"

exit 0