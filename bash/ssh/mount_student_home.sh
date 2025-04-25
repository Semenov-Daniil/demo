#!/bin/bash

# Настройка переменных
CHROOT_DIR="/var/chroot"
LOG_FILE="${2:-logs/mount_student_home.log}"

# Настройка логирования
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
log "Beginning of the script to mount the student's home folder..."
echo "Beginning of the script to mount the student's home folder..."

if [ -z "$1" ]; then
    log "Error: username is required"
    echo "Error: username is required"
    exit 1
fi

USERNAME=$1
CHROOT_DIR="/var/chroot"
STUDENT_HOME="/var/www/demo/students/$USERNAME"
CHROOT_HOME="$CHROOT_DIR/home/$USERNAME"

# Проверка root-прав
if [[ $EUID -ne 0 ]]; then
    echo "Error: This script must be run with root privileges" >> "$LOG_FILE"
    exit 2
fi

# Проверка существования chroot
if [[ ! -d "$CHROOT_DIR" ]]; then
    log "Error: Chroot directory '$CHROOT_DIR' does not exist"
    exit 3
fi

# Проверка существования папки студента
if [[ ! -d "$STUDENT_HOME" ]]; then
    log "Error: Student home directory '$STUDENT_HOME' does not exist"
    echo "Error: Student home directory '$STUDENT_HOME' does not exist"
    exit 3
fi

# Проверка существования папки etc/passwd
if [ ! -d "$CHROOT_DIR/etc/passwd" ]; then
    mkdir -p "$CHROOT_DIR/etc/passwd"
    chown root:root "$CHROOT_DIR/etc/passwd"
    chmod 755 "$CHROOT_DIR/etc/passwd"
fi

# Добавление пользователя в chroot /etc/passwd
if ! grep -q "^$USERNAME:" "$CHROOT_DIR/etc/passwd"; then
    echo "$USERNAME:x:$(id -u $USERNAME):$(id -g $USERNAME)::/home/$USERNAME:/bin/bash" >> "$CHROOT_DIR/etc/passwd"
    log "Added $USERNAME to $CHROOT_DIR/etc/passwd"
fi

# Создание целевой папки при необходимости
if [ ! -d "$CHROOT_HOME" ]; then
    mkdir -p "$CHROOT_HOME"
fi

chown root:root "$CHROOT_HOME"
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
grep -qsF "$FSTAB_ENTRY" /etc/fstab
if [ $? -ne 0 ]; then
    echo "$FSTAB_ENTRY" >> /etc/fstab
    log "Info: Added mount entry to /etc/fstab for persistence."
    echo "Info: Added mount entry to /etc/fstab."
else
    log "Info: Mount entry already exists in /etc/fstab."
    echo "Info: Mount entry already exists in /etc/fstab."
fi

echo "Successfully mounted $STUDENT_HOME to $CHROOT_HOME for $USERNAME"
log "Successfully mounted $STUDENT_HOME to $CHROOT_HOME for $USERNAME"

exit 0