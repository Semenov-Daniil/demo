#!/bin/bash

LOG_FILE="logs/setup_chroot.log"
CHROOT_DIR="/var/chroot"

# Проверка root-прав
if [[ $EUID -ne 0 ]]; then
    echo "[$(date '+%Y-%m-%d %H:%M:%S')] Error: This script must be run with root privileges" >> "$LOG_FILE"
    exit 1
fi

# Проверка входных аргументов
if [ $# -eq 0 ]; then
    echo "[$(date '+%Y-%m-%d %H:%M:%S')] Error: At least one command is required" >> "$LOG_FILE"
    exit 2
fi

# Проверка и копирование команд
MISSING_COMMANDS=""
for CMD in "$@"; do
    CMD_PATH=$(which "$CMD" 2>/dev/null)
    if [[ -n "$CMD_PATH" ]]; then
        if jk_cp -v -j "$CHROOT_DIR" "$CMD_PATH" >/dev/null 2>&1; then
            echo "[$(date '+%Y-%m-%d %H:%M:%S')] Successfully added $CMD to $CHROOT_DIR" >> "$LOG_FILE"
        else
            echo "[$(date '+%Y-%m-%d %H:%M:%S')] Warning: Failed to add $CMD to $CHROOT_DIR" >> "$LOG_FILE"
        fi
    else
        MISSING_COMMANDS="$MISSING_COMMANDS $CMD"
    fi
done

# Предупреждение о пропущенных командах
if [[ -n "$MISSING_COMMANDS" ]]; then
    echo "[$(date '+%Y-%m-%d %H:%M:%S')] Warning: Commands not found on server:$MISSING_COMMANDS" >> "$LOG_FILE"
fi

echo "[$(date '+%Y-%m-%d %H:%M:%S')] Command addition process completed" >> "$LOG_FILE"
exit 0