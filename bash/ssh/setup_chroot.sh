#!/bin/bash

# Настройка переменных
CHROOT_DIR="/var/chroot"

# Настройка логирования
LOG_FILE="${1:-logs/setup_chroot.log}"

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
echo "Creating and configuring a chroot environment..."
log "Creating and configuring a chroot environment..."

# Проверка root-прав
if [[ $EUID -ne 0 ]]; then
    log "Error: This script must be run with root privileges."
    exit 1
fi

# Проверка зависимости: jailkit
if ! command -v jk_init >/dev/null 2>&1; then
    log "Error: jailkit (jk_init) is not installed."
    exit 1
fi

# Создание директории Chroot
if [ -d "$CHROOT_DIR" ]; then
    log "Info: Chroot directory $CHROOT_DIR already exists. Skipping creation."
else
    mkdir -p "$CHROOT_DIR"
    log "Info: Chroot directory $CHROOT_DIR created."
fi

chown root:root "$CHROOT_DIR"
chmod 755 "$CHROOT_DIR"

# Базовая структура внутри chroot
mkdir -p "$CHROOT_DIR/home" "$CHROOT_DIR/etc" "$CHROOT_DIR/etc/passwd"
chmod 755 "$CHROOT_DIR/home" "$CHROOT_DIR/etc" "$CHROOT_DIR/etc/passwd"
chown root:root "$CHROOT_DIR/home" "$CHROOT_DIR/etc" "$CHROOT_DIR/etc/passwd"
log "Info: Basic chroot structure created (home, etc, etc/passwd)."

# Настройка bash.bashrc внутри chroot
cat << EOF > "$CHROOT_DIR/etc/bash.bashrc"
export PS1='\u@\h:~\\$ '
export HOME=/home/\$USER
export PATH=/bin:/usr/bin
cd /home/\$USER
unset CDPATH
EOF
if [[ $? -ne 0 ]]; then
    echo "Error: Failed to configure bash.bashrc"
    exit 4
fi
chown root:root "$CHROOT_DIR/etc/bash.bashrc"
chmod 644 "$CHROOT_DIR/etc/bash.bashrc"
log "Info: bash.bashrc configured inside chroot."

# Инициализация chroot окружения с минимальными модулями
jk_init -v -j "$CHROOT_DIR" basicshell editors extendedshell netutils ssh sftp scp >> "$LOG_FILE" 2>&1;
if [ $? -eq 0 ]; then
    log "Info: Chroot environment initialized with jailkit (basicshell, editors, extendedshell, netutils, ssh, sftp, scp)."
else
    log "Error: Failed to initialize chroot environment."
    exit 1
fi

echo "Chroot environment set up in $CHROOT_DIR"
log "Chroot environment set up in $CHROOT_DIR"

exit 0