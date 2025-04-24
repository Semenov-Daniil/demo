#!/bin/bash

# Настройка логирования
LOG_FILE="${2:-logs/mount_student.log}"

LOG_DIR=$(dirname "$LOG_FILE")
if [[ ! -d "$LOG_DIR" ]]; then
    mkdir -p "$LOG_DIR" 2>/dev/null || {
        echo "Error: Cannot create log directory '$LOG_DIR'"
        exit 1
    }
fi

if [ ! -f "$LOG_FILE" ]; then
    touch "$LOG_FILE"
    chmod 777 "$LOG_FILE"
    chown www-data:www-data "$LOG_FILE"
fi

log() {
    echo "[$(date '+%Y-%m-%d %H:%M:%S')] $1" >> "$LOG_FILE"
}

# Начало выполнение
echo "Beginning of the script to umount the student's home folder..."
log "Beginning of the script to umount the student's home folder..."

if [ -z "$1" ]; then
    echo "Error: username is required"
    log "Error: username is required"
    exit 1
fi

USERNAME=$1
CHROOT_HOME="/var/chroot/home/$USERNAME"
STUDENT_DIR="/var/www/demo/students/$USERNAME"
FSTAB="/etc/fstab"

# Проверка root-прав
if [[ $EUID -ne 0 ]]; then
    echo "Error: This script must be run with root privileges"
    log "Error: This script must be run with root privileges"
    exit 1
fi

# Проверка существования точки монтирования
if [[ ! -d "$CHROOT_HOME" ]]; then
    echo "[$(date '+%Y-%m-%d %H:%M:%S')] Warning: Chroot home '$CHROOT_HOME' does not exist" >> "$LOG_FILE"
    exit 0
fi

# Проверка, что точка монтирования существует
if mountpoint -q "$CHROOT_HOME"; then
    umount "$CHROOT_HOME"
    if [ $? -eq 0 ]; then
        log "Info: Unmounted home directory for user: $LOGIN"
        echo "Info: Unmounted home directory for user: $LOGIN"
    else
        log "Error: Failed to unmount: $CHROOT_HOME"
        echo "Error: Failed to unmount: $CHROOT_HOME"
        exit 1
    fi
else
    log "Warning: Directory not mounted: $CHROOT_HOME"
    echo "Warning: Directory not mounted: $CHROOT_HOME"
fi

# Удаление записи из /etc/fstab
FSTAB_LINE="$STUDENT_DIR $CHROOT_HOME none bind 0 0"
grep -F -- "$FSTAB_LINE" /etc/fstab >/dev/null 2>&1
if [ $? -eq 0 ]; then
    sed -i "\|$FSTAB_LINE|d" /etc/fstab
    if [ $? -eq 0 ]; then
        log "Info: Removed fstab entry for: $LOGIN"
        echo "Info: Removed fstab entry for: $LOGIN"
    else
        log "Error: Failed to remove fstab entry for: $LOGIN"
        echo "Error: Failed to remove fstab entry for: $LOGIN"
    fi
else
    log "Warning: No fstab entry found for: $LOGIN"
    echo "Warning: No fstab entry found for: $LOGIN"
fi

# Удаление папки из chroot
if [ -d "$CHROOT_HOME" ]; then
    rm -rf "$CHROOT_HOME"
    if [ $? -eq 0 ]; then
        log "Info: Removed chroot directory: $CHROOT_HOME"
        echo "Info: Removed chroot directory: $CHROOT_HOME"
    else
        log "Error: Failed to remove chroot directory: $CHROOT_HOME"
        echo "Error: Failed to remove chroot directory: $CHROOT_HOME"
    fi
else
    log "Warning: No chroot directory to remove: $CHROOT_HOME"
    echo "Warning: No chroot directory to remove: $CHROOT_HOME"
fi

echo "Successfully unmounted and cleaned $CHROOT_HOME for $USERNAME"
log "Successfully unmounted and cleaned $CHROOT_HOME for $USERNAME"

exit 0