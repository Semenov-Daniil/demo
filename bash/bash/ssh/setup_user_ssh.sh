#!/bin/bash

if [ -z "$1" ] || [ -z "$2" ]; then
    echo "Error: username, and home directory are required" >&2
    exit 1
fi

USERNAME=$1
HOME_DIR=$2
LOG_FILE="${3:-logs/user_ssh.log}"

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

mkdir -p "/var/chroot/home/$USERNAME" >> "$LOG_FILE" 2>&1 || {
    echo "[$(date '+%Y-%m-%d %H:%M:%S')] Failed to create chroot home for $USERNAME" >> "$LOG_FILE"
    exit 7
}
mount --bind "$HOME_DIR" "/var/chroot/home/$USERNAME" >> "$LOG_FILE" 2>&1 || {
    echo "[$(date '+%Y-%m-%d %H:%M:%S')] Failed to mount chroot home for $USERNAME" >> "$LOG_FILE"
    exit 7
}
echo "$HOME_DIR /var/chroot/home/$USERNAME none bind 0 0" >> /etc/fstab || {
    echo "[$(date '+%Y-%m-%d %H:%M:%S')] Failed to update fstab for $USERNAME" >> "$LOG_FILE"
    exit 7
}

sudo grep "^$USERNAME:" /etc/passwd >> /var/chroot/etc/passwd || {
    echo "[$(date '+%Y-%m-%d %H:%M:%S')] Failed to add $USERNAME to chroot passwd" >> "$LOG_FILE"
    exit 8
}
sudo grep "^students:" /etc/group >> /var/chroot/etc/group || {
    echo "[$(date '+%Y-%m-%d %H:%M:%S')] Failed to add students group to chroot group" >> "$LOG_FILE"
    exit 8
}

# Проверка статуса SSH
if systemctl is-active --quiet ssh; then
    echo "[$(date '+%Y-%m-%d %H:%M:%S')] SSH service is active" >> "$LOG_FILE"
else
    echo "[$(date '+%Y-%m-%d %H:%M:%S')] Error: SSH service is not active" >> "$LOG_FILE"
    exit 9
fi

# Проверка конфигурации SSH
if ! sshd -t >> "$LOG_FILE" 2>&1; then
    echo "[$(date '+%Y-%m-%d %H:%M:%S')] Error: SSH configuration test failed" >> "$LOG_FILE"
    exit 9
fi

echo "[$(date '+%Y-%m-%d %H:%M:%S')] Successfully created user $USERNAME" >> "$LOG_FILE"

exit 0