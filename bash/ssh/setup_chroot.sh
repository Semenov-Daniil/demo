#!/bin/bash

LOG_FILE="${1:-logs/setup_chroot.log}"
CHROOT_DIR="/var/chroot"

# Проверка root-прав
if [[ $EUID -ne 0 ]]; then
    echo "[$(date '+%Y-%m-%d %H:%M:%S')] Error: This script must be run with root privileges" >> "$LOG_FILE"
    exit 1
fi

# Создание директории Chroot
if [[ ! -d "$CHROOT_DIR" ]]; then
    mkdir -p "$CHROOT_DIR" || {
        echo "[$(date '+%Y-%m-%d %H:%M:%S')] Error: Cannot create $CHROOT_DIR" >> "$LOG_FILE"
        exit 2
    }
fi

# Инициализация Chroot с jailkit
if ! jk_init -v "$CHROOT_DIR" basicshell editors extendedshell >/dev/null 2>&1; then
    echo "[$(date '+%Y-%m-%d %H:%M:%S')] Error: Failed to initialize Chroot with jailkit" >> "$LOG_FILE"
    exit 3
fi

if ! mkdir -p "$CHROOT_DIR/etc" >> "$LOG_FILE" 2>&1; then
    echo "[$(date '+%Y-%m-%d %H:%M:%S')] Error: Failed to create $CHROOT_DIR/etc" >> "$LOG_FILE"
    exit 4
fi

# Настройка прав
chown root:root "$CHROOT_DIR"
chmod 755 "$CHROOT_DIR"
for dir in "$CHROOT_DIR"/{lib,lib64,usr,etc}; do
    [[ -d "$dir" ]] && chown root:root "$dir" && chmod 700 "$dir"
done
for dir in "$CHROOT_DIR"/{bin}; do
    [[ -d "$dir" ]] && chown root:root "$dir" && chmod 755 "$dir"
done

# Создание базовой структуры
mkdir -p "$CHROOT_DIR/home" 2>>"$LOG_FILE"
chown root:root "$CHROOT_DIR/home"
chmod 755 "$CHROOT_DIR/home"

cat << EOF > "$CHROOT_DIR/etc/bash.bashrc"
PS1='\u@\$(hostname):~\\$ '
export HOME=/home/\$USER
export PATH=/bin:/usr/bin
cd /home/\$USER
unset CDPATH
EOF
if [[ $? -ne 0 ]]; then
    echo "[$(date '+%Y-%m-%d %H:%M:%S')] Error: Failed to configure bash.bashrc" >> "$LOG_FILE"
    exit 4
fi
chown root:root "$CHROOT_DIR/etc/bash.bashrc"
chmod 644 "$CHROOT_DIR/etc/bash.bashrc"

echo "[$(date '+%Y-%m-%d %H:%M:%S')] Chroot environment set up in $CHROOT_DIR" >> "$LOG_FILE"
exit 0