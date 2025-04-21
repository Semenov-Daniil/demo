#!/bin/bash

# Проверка аргументов
if [ -z "$1" ]; then
    echo "Error: Username is required" >&2
    exit 1
fi

USERNAME="$1"
LOG_FILE="${2:-logs/students.log}"

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

# Проверка существования пользователя
if ! id "$USERNAME" >/dev/null 2>&1; then
    echo "[$(date '+%Y-%m-%d %H:%M:%S')] Warning: System user '$USERNAME' does not exist, nothing to delete" >> "$LOG_FILE"
    exit 0
fi

# Проверка принадлежности к группе students
if ! groups "$USERNAME" | grep -q "\bstudents\b"; then
    echo "[$(date '+%Y-%m-%d %H:%M:%S')] Error: User '$USERNAME' is not a member of the 'students' group" >> "$LOG_FILE"
    exit 5
fi

# Проверка наличия команды userdel
if ! command -v userdel >/dev/null 2>&1; then
    echo "[$(date '+%Y-%m-%d %H:%M:%S')] Error: userdel command not found" >> "$LOG_FILE"
    exit 6
fi

# Проверка активных процессов пользователя
if pgrep -u "$USERNAME" >/dev/null; then
    echo "[$(date '+%Y-%m-%d %H:%M:%S')] Warning: User $USERNAME has active processes, attempting to terminate" >> "$LOG_FILE"
    if ! pkill -u "$USERNAME" >> "$LOG_FILE" 2>&1; then
        echo "[$(date '+%Y-%m-%d %H:%M:%S')] Error: Failed to terminate processes for user $USERNAME" >> "$LOG_FILE"
        exit 7
    fi
    # Даём небольшую паузу для завершения процессов
    sleep 1
    if pgrep -u "$USERNAME" >/dev/null; then
        echo "[$(date '+%Y-%m-%d %H:%M:%S')] Error: Some processes for user $USERNAME could not be terminated" >> "$LOG_FILE"
        exit 7
    fi
fi

# Удаление пользователя
if ! userdel $USERDEL_OPT "$USERNAME" >> "$LOG_FILE" 2>&1; then
    echo "[$(date '+%Y-%m-%d %H:%M:%S')] Error: Failed to delete system user $USERNAME" >> "$LOG_FILE"
    exit 8
fi

echo "[$(date '+%Y-%m-%d %H:%M:%S')] Successfully deleted system user $USERNAME" >> "$LOG_FILE"

exit 0