#!/bin/bash

if [ -z "$1" ]; then
    echo "[$(date '+%Y-%m-%d %H:%M:%S')] Error: username is required" >> "$LOG_FILE"
    exit 1
fi

USERNAME=$1
LOG_FILE="${2:-logs/student_ssh.log}"
CHROOT_HOME="/var/chroot/home/$USERNAME"
FSTAB="/etc/fstab"

# Проверка root-прав
if [[ $EUID -ne 0 ]]; then
    echo "[$(date '+%Y-%m-%d %H:%M:%S')] Error: This script must be run with root privileges" >> "$LOG_FILE"
    exit 2
fi

# Проверка существования точки монтирования
if [[ ! -d "$CHROOT_HOME" ]]; then
    echo "[$(date '+%Y-%m-%d %H:%M:%S')] Warning: Chroot home '$CHROOT_HOME' does not exist" >> "$LOG_FILE"
    exit 0
fi

# Размонтирование
if mountpoint -q "$CHROOT_HOME"; then
    if ! umount "$CHROOT_HOME" 2>>"$LOG_FILE"; then
        echo "[$(date '+%Y-%m-%d %H:%M:%S')] Error: Failed to unmount '$CHROOT_HOME'" >> "$LOG_FILE"
        exit 3
    fi
fi

# Удаление записи из fstab
if grep -q "$CHROOT_HOME" "$FSTAB"; then
    sed -i "\|$CHROOT_HOME|d" "$FSTAB" 2>>"$LOG_FILE"
    echo "[$(date '+%Y-%m-%d %H:%M:%S')] Removed fstab entry for $CHROOT_HOME" >> "$LOG_FILE"
fi

# Удаление точки монтирования
rmdir "$CHROOT_HOME" 2>>"$LOG_FILE"

echo "[$(date '+%Y-%m-%d %H:%M:%S')] Successfully unmounted and cleaned $CHROOT_HOME for $USERNAME" >> "$LOG_FILE"
exit 0