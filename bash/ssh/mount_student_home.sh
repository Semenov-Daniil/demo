#!/bin/bash

if [ -z "$1" ] || [ -z "$2" ]; then
    echo "Error: username and home directory is required" >&2
    exit 1
fi

USERNAME=$1
STUDENT_HOME=$2
LOG_FILE="${3:-logs/setup_ssh.log}"
CHROOT_DIR="/var/chroot"
CHROOT_HOME="/var/chroot/home/$USERNAME"
FSTAB="/etc/fstab"

# Проверка root-прав
if [[ $EUID -ne 0 ]]; then
    echo "[$(date '+%Y-%m-%d %H:%M:%S')] Error: This script must be run with root privileges" >> "$LOG_FILE"
    exit 2
fi

# Проверка существования папки студента
if [[ ! -d "$STUDENT_HOME" ]]; then
    echo "[$(date '+%Y-%m-%d %H:%M:%S')] Error: Student home directory '$STUDENT_HOME' does not exist" >> "$LOG_FILE"
    exit 3
fi

# Создание точки монтирования
mkdir -p "$CHROOT_HOME" 2>>"$LOG_FILE"
chown "$USERNAME":www-data "$CHROOT_HOME"
chmod 755 "$CHROOT_HOME"

# Проверка, существует ли запись в fstab
if grep -q "$CHROOT_HOME" "$FSTAB"; then
    echo "[$(date '+%Y-%m-%d %H:%M:%S')] Warning: fstab entry for $CHROOT_HOME already exists" >> "$LOG_FILE"
else
    # Добавление записи в fstab
    echo "$STUDENT_HOME $CHROOT_HOME none bind 0 0" >> "$FSTAB"
    echo "[$(date '+%Y-%m-%d %H:%M:%S')] Added fstab entry for $CHROOT_HOME" >> "$LOG_FILE"
fi

# Выполнение монтирования
if ! mount "$CHROOT_HOME" 2>>"$LOG_FILE"; then
    echo "[$(date '+%Y-%m-%d %H:%M:%S')] Error: Failed to mount '$CHROOT_HOME'" >> "$LOG_FILE"
    exit 4
fi

echo "[$(date '+%Y-%m-%d %H:%M:%S')] Successfully mounted $STUDENT_HOME to $CHROOT_HOME for $USERNAME" >> "$LOG_FILE"
exit 0