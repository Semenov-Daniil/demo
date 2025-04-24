#!/bin/bash

# Настройка логирования
DEFAULT_LOG_FILE="logs/mount_student.log"

LOG_FILE="${!#}" # Последний аргумент — лог-файл, если передан
if [[ "$LOG_FILE" != /* ]]; then
    LOG_FILE="$DEFAULT_LOG_FILE"
fi

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
echo "Beginning of the script adding commands to the environment..."
log "Beginning of the script adding commands to the environment..."

COMMANDS=("${@:1:$(($#-1))}")

# Проверка root-прав
if [[ $EUID -ne 0 ]]; then
    echo "Error: This script must be run with root privileges"
    log "Error: This script must be run with root privileges"
    exit 1
fi

# Проверка наличия jk_cp
if ! command -v jk_cp >/dev/null 2>&1; then
    log "Error: jk_cp command not found (jailkit not installed)"
    echo "Error: jk_cp command not found (jailkit not installed)"
    exit 1
fi

# Проверка и добавление команд
for CMD in "${COMMANDS[@]}"; do
    if ! command -v "$CMD" >/dev/null 2>&1; then
        log "Warning: Command not found on system: $CMD"
        echo "Warning: Command not found on system: $CMD"
        continue
    fi

    jk_cp -v -j "$CHROOT_DIR" "$(which $CMD)" >/dev/null 2>&1
    if [ $? -eq 0 ]; then
        log "Info: Added command to chroot: $CMD"
    else
        log "Error: Failed to add command to chroot: $CMD"
        echo "Error: Failed to add command to chroot: $CMD"
    fi
done

echo "Command addition process completed"
log "Command addition process completed"

exit 0