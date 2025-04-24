#!/bin/bash

# Настройка логирования
LOG_FILE="${1:-logs/setup_ssh.log}"

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
echo "Checking for dependencies..."
log "Checking for dependencies..."

# Проверка прав root
if [[ $EUID -ne 0 ]]; then
    log "Error: This script must be run with root privileges"
    exit 1
fi

# Проверка наличия openssh-server
if ! command -v sshd >/dev/null 2>&1; then
    log "Error: openssh-server is not installed"
    exit 2
fi

# Проверка наличия jailkit
if ! command -v jk_init >/dev/null 2>&1; then
    log "Error: jailkit is not installed"
    exit 3
fi

# Проверка статуса SSH
if ! systemctl is-active --quiet ssh && ! systemctl is-active --quiet sshd; then
    log "Info: ssh service is not active, trying to start it..."
    if systemctl start ssh 2>/dev/null || systemctl start sshd 2>/dev/null; then
        log "Ok: ssh service started successfully"
    else
        log "Error: failed to start ssh service"
        exit 4
    fi
fi

echo "All dependencies are available and ssh is active"
log "All dependencies are available and ssh is active"

exit 0