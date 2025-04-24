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
echo "Beginning of the script to mount the student's home folder..."
log "Beginning of the script to mount the student's home folder..."

if [ -z "$1" ]; then
    log "Error: username is required"
    echo "Error: username is required"
    exit 1
fi

USERNAME=$1
STUDENT_HOME="/var/www/demo/students/$USERNAME"
CHROOT_DIR="/var/chroot"
CHROOT_HOME="$CHROOT_DIR/home/$USERNAME"

# Проверка root-прав
if [[ $EUID -ne 0 ]]; then
    echo "[$(date '+%Y-%m-%d %H:%M:%S')] Error: This script must be run with root privileges" >> "$LOG_FILE"
    exit 2
fi

# Проверка существования папки студента
if [[ ! -d "$STUDENT_HOME" ]]; then
    log "Error: Student home directory '$STUDENT_HOME' does not exist"
    echo "Error: Student home directory '$STUDENT_HOME' does not exist"
    exit 3
fi

# Создание целевой папки при необходимости
if [ ! -d "$CHROOT_HOME" ]; then
    mkdir -p "$CHROOT_HOME"
fi

chown www-data:www-data "$CHROOT_HOME"
chmod 755 "$CHROOT_HOME"

# Проверка, уже смонтировано или нет
mountpoint -q "$CHROOT_HOME"
if [ $? -eq 0 ]; then
    log "Info: $CHROOT_HOME is already mounted."
    echo "Info: $CHROOT_HOME is already mounted."
else
    mount --bind "$STUDENT_HOME" "$CHROOT_HOME"
    if [ $? -eq 0 ]; then
        log "Info: Mounted $STUDENT_HOME to $CHROOT_HOME"
        echo "Info: Mounted $STUDENT_HOME to $CHROOT_HOME"
    else
        log "Error: Failed to mount $STUDENT_HOME to $CHROOT_HOME"
        echo "Error: Failed to mount $STUDENT_HOME to $CHROOT_HOME"
        exit 1
    fi
fi

# Добавление записи в /etc/fstab (если её ещё нет)
FSTAB_ENTRY="$STUDENT_HOME $CHROOT_HOME none bind 0 0"
FSTAB_FILE="/etc/fstab"
grep -qsF "$FSTAB_ENTRY" "$FSTAB_FILE"
if [ $? -ne 0 ]; then
    echo "$FSTAB_ENTRY" >> "$FSTAB_FILE"
    log "Info: Added mount entry to $FSTAB_FILE for persistence."
    echo "Info: Added mount entry to $FSTAB_FILE."
else
    log "Info: Mount entry already exists in $FSTAB_FILE."
    echo "Info: Mount entry already exists in $FSTAB_FILE."
fi

echo "Successfully mounted $STUDENT_HOME to $CHROOT_HOME for $USERNAME"
log "Successfully mounted $STUDENT_HOME to $CHROOT_HOME for $USERNAME"

exit 0