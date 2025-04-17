#!/bin/bash

if [ -z "$1" ] || [ -z "$2" ] || [ -z "$3" ]; then
    echo "Error: username, password, and home directory are required" >&2
    exit 1
fi

USERNAME=$1
PASSWORD=$2
HOME_DIR=$3
LOG_FILE="${4:-logs/create_user.log}"
GROUP_NAME="students"

if [[ $EUID -ne 0 ]]; then
    echo "Error: This script must be run with root privileges" >&2
    exit 2
fi

LOG_DIR=$(dirname "$LOG_FILE")
if [[ ! -d "$LOG_DIR" ]]; then
    mkdir -p "$LOG_DIR" 2>/dev/null || {
        echo "Error: Cannot create log directory '$LOG_DIR'" >&2
        exit 1
    }
fi

if [[ ! -d "$HOME_DIR" ]]; then
    echo "[$(date '+%Y-%m-%d %H:%M:%S')] Error: Home directory '$HOME_DIR' does not exist" >> "$LOG_FILE"
    exit 1
fi

echo "[$(date '+%Y-%m-%d %H:%M:%S')] Checking the $GROUP_NAME group" >> "$LOG_FILE"
if getent group "$GROUP_NAME" >/dev/null; then
    echo "[$(date '+%Y-%m-%d %H:%M:%S')] Group $GROUP_NAME already exists" >> "$LOG_FILE"
else
    if groupadd "$GROUP_NAME" 2>>"$LOG_FILE"; then
        echo "[$(date '+%Y-%m-%d %H:%M:%S')] Group $GROUP_NAME successfully created" >> "$LOG_FILE"
    else
        echo "[$(date '+%Y-%m-%d %H:%M:%S')] Failed to create group $GROUP_NAME" >> "$LOG_FILE"
        exit 3
    fi
fi

echo "[$(date '+%Y-%m-%d %H:%M:%S')] Creating user $USERNAME" >> "$LOG_FILE"
if ! useradd -d "$HOME_DIR" -s /bin/bash -g "$GROUP_NAME" "$USERNAME" >> "$LOG_FILE" 2>&1; then
    echo "[$(date '+%Y-%m-%d %H:%M:%S')] Failed to create user $USERNAME" >> "$LOG_FILE"
    exit 4
fi

if ! echo "$USERNAME:$PASSWORD" | chpasswd >> "$LOG_FILE" 2>&1; then
    echo "[$(date '+%Y-%m-%d %H:%M:%S')] Failed to set password for $USERNAME" >> "$LOG_FILE"
    exit 5
fi

if ! chown root:root "$HOME_DIR" >> "$LOG_FILE" 2>&1; then
    echo "[$(date '+%Y-%m-%d %H:%M:%S')] Failed to set owner for $HOME_DIR" >> "$LOG_FILE"
    exit 6
fi

if ! chmod 755 "$HOME_DIR" >> "$LOG_FILE" 2>&1; then
    echo "[$(date '+%Y-%m-%d %H:%M:%S')] Failed to set permissions for $HOME_DIR" >> "$LOG_FILE"
    exit 6
fi

if getent passwd www-data >/dev/null; then
    if ! setfacl -m u:www-data:rwx "$HOME_DIR" >> "$LOG_FILE" 2>&1; then
        echo "[$(date '+%Y-%m-%d %H:%M:%S')] Failed to set ACL for $HOME_DIR" >> "$LOG_FILE"
        exit 6
    fi
else
    echo "[$(date '+%Y-%m-%d %H:%M:%S')] Warning: User www-data does not exist, skipping ACL" >> "$LOG_FILE"
fi

echo "[$(date '+%Y-%m-%d %H:%M:%S')] Successfully created user $USERNAME" >> "$LOG_FILE"

exit 0