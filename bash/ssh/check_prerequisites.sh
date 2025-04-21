#!/bin/bash

LOG_FILE="${1:-logs/setup_ssh.log}"

# Проверка прав root
if [[ $EUID -ne 0 ]]; then
    echo "[$(date '+%Y-%m-%d %H:%M:%S')] Error: This script must be run with root privileges" >> "$LOG_FILE"
    exit 1
fi

# Создание директории для логов
LOG_DIR=$(dirname "$LOG_FILE")
if [[ ! -d "$LOG_DIR" ]]; then
    mkdir -p "$LOG_DIR" 2>/dev/null || {
        echo "[$(date '+%Y-%m-%d %H:%M:%S')] Error: Cannot create log directory '$LOG_DIR'" >> "$LOG_FILE"
        exit 1
    }
fi

# Проверка наличия openssh-server
if ! dpkg -l | grep -q openssh-server; then
    echo "[$(date '+%Y-%m-%d %H:%M:%S')] Error: openssh-server is not installed" >> "$LOG_FILE"
    exit 2
fi

# Проверка наличия jailkit
if ! dpkg -l | grep -q jailkit; then
    echo "[$(date '+%Y-%m-%d %H:%M:%S')] Error: jailkit is not installed" >> "$LOG_FILE"
    exit 3
fi

# Проверка статуса SSH
if ! systemctl is-active --quiet ssh; then
    echo "[$(date '+%Y-%m-%d %H:%M:%S')] Error: SSH service is not active" >> "$LOG_FILE"
    exit 4
fi

echo "[$(date '+%Y-%m-%d %H:%M:%S')] Prerequisites check passed: SSH and jailkit are installed and active" >> "$LOG_FILE"

exit 0