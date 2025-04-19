#!/bin/bash

# Проверка аргументов
if [ -z "$1" ]; then
    echo "Error: Username is required" >&2
    exit 1
fi

USERNAME="$1"
LOG_FILE="${2:-logs/samba.log}"

# Проверка прав root
if [[ $EUID -ne 0 ]]; then
    echo "[$(date '+%Y-%m-%d %H:%M:%S')] Error: This script must be run with root privileges" >> "$LOG_FILE"
    exit 2
fi

# Проверка директории для лога
LOG_DIR=$(dirname "$LOG_FILE")
if [[ ! -d "$LOG_DIR" ]]; then
    mkdir -p "$LOG_DIR" 2>/dev/null || {
        echo "Error: Cannot create log directory '$LOG_DIR'" >&2
        exit 3
    }
fi

# Проверка наличия Samba
if ! command -v smbpasswd >/dev/null 2>&1; then
    echo "[$(date '+%Y-%m-%d %H:%M:%S')] Error: smbpasswd command not found, is Samba installed?" >> "$LOG_FILE"
    exit 5
fi

# Проверка существования системного пользователя
if ! id "$USERNAME" >/dev/null 2>&1; then
    echo "[$(date '+%Y-%m-%d %H:%M:%S')] Error: System user '$USERNAME' does not exist" >> "$LOG_FILE"
    exit 6
fi

# Проверка принадлежности к группе students
if ! groups "$USERNAME" | grep -q "\bstudents\b"; then
    echo "[$(date '+%Y-%m-%d %H:%M:%S')] Error: User '$USERNAME' is not a member of the 'students' group" >> "$LOG_FILE"
    exit 5
fi

# Проверка, существует ли пользователь в базе Samba
if ! pdbedit -L | grep -q "^$USERNAME:"; then
    echo "[$(date '+%Y-%m-%d %H:%M:%S')] Warning: Samba user '$USERNAME' does not exist, nothing to delete" >> "$LOG_FILE"
    exit 0
fi

echo "[$(date '+%Y-%m-%d %H:%M:%S')] Deleting Samba user $USERNAME" >> "$LOG_FILE"

# Удаление пользователя Samba
if ! smbpasswd -x "$USERNAME" >> "$LOG_FILE" 2>&1; then
    echo "[$(date '+%Y-%m-%d %H:%M:%S')] Error: Failed to delete Samba user $USERNAME" >> "$LOG_FILE"
    exit 7
fi

echo "[$(date '+%Y-%m-%d %H:%M:%S')] Successfully deleted Samba user $USERNAME" >> "$LOG_FILE"

exit 0