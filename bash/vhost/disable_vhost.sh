#!/bin/bash

# Проверка аргументов
if [ -z "$1" ]; then
    echo "Error: Configuration file name is required" >&2
    exit 1
fi

CONFIG_FILE="$1"
LOG_FILE="${2:-logs/vhost.log}"
CONFIG_PATH="/etc/apache2/sites-available/$CONFIG_FILE.conf"
BACKUP_PATH="/etc/apache2/sites-available/backup/$CONFIG_FILE.conf.$(date +%Y%m%d_%H%M%S)"

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

# Проверка наличия необходимых команд
for cmd in a2dissite apachectl systemctl; do
    if ! command -v "$cmd" >/dev/null 2>&1; then
        echo "[$(date '+%Y-%m-%d %H:%M:%S')] Error: Command '$cmd' not found" >> "$LOG_FILE"
        exit 4
    fi
done

# Проверка существования файла конфигурации
if [[ ! -f "$CONFIG_PATH" ]]; then
    echo "[$(date '+%Y-%m-%d %H:%M:%S')] Error: Configuration file '$CONFIG_PATH' does not exist" >> "$LOG_FILE"
    exit 5
fi

echo "[$(date '+%Y-%m-%d %H:%M:%S')] Disabling virtual host for $CONFIG_FILE" >> "$LOG_FILE"

# Проверка, включен ли виртуальный хост
if [[ -L "/etc/apache2/sites-enabled/$CONFIG_FILE" ]]; then
    echo "[$(date '+%Y-%m-%d %H:%M:%S')] Disabling virtual host $CONFIG_FILE" >> "$LOG_FILE"
    if ! a2dissite "$CONFIG_FILE" >> "$LOG_FILE" 2>&1; then
        echo "[$(date '+%Y-%m-%d %H:%M:%S')] Error: Failed to disable virtual host $CONFIG_FILE" >> "$LOG_FILE"
        exit 6
    fi
else
    echo "[$(date '+%Y-%m-%d %H:%M:%S')] Virtual host $CONFIG_FILE is already disabled" >> "$LOG_FILE"
fi

# Проверка синтаксиса конфигурации Apache
echo "[$(date '+%Y-%m-%d %H:%M:%S')] Checking Apache configuration syntax" >> "$LOG_FILE"
if ! apachectl configtest >> "$LOG_FILE" 2>&1; then
    echo "[$(date '+%Y-%m-%d %H:%M:%S')] Error: Apache configuration test failed" >> "$LOG_FILE"
    # Попытка отката
    if [[ -L "/etc/apache2/sites-enabled/$CONFIG_FILE" ]]; then
        echo "[$(date '+%Y-%m-%d %H:%M:%S')] Attempting to restore virtual host $CONFIG_FILE" >> "$LOG_FILE"
        if ! a2ensite "$CONFIG_FILE" >> "$LOG_FILE" 2>&1; then
            echo "[$(date '+%Y-%m-%d %H:%M:%S')] Error: Failed to restore virtual host $CONFIG_FILE" >> "$LOG_FILE"
        else
            echo "[$(date '+%Y-%m-%d %H:%M:%S')] Virtual host $CONFIG_FILE restored" >> "$LOG_FILE"
        fi
    fi
    exit 7
fi

# Перезагрузка Apache
echo "[$(date '+%Y-%m-%d %H:%M:%S')] Reloading Apache" >> "$LOG_FILE"
if ! systemctl reload apache2 >> "$LOG_FILE" 2>&1; then
    echo "[$(date '+%Y-%m-%d %H:%M:%S')] Error: Failed to reload Apache" >> "$LOG_FILE"
    exit 8
fi

# Проверка статуса Apache
if ! systemctl is-active apache2 >/dev/null 2>&1; then
    echo "[$(date '+%Y-%m-%d %H:%M:%S')] Error: Apache is not active after reload" >> "$LOG_FILE"
    exit 9
fi

# Создание резервной копии
echo "[$(date '+%Y-%m-%d %H:%M:%S')] Creating backup of $CONFIG_PATH" >> "$LOG_FILE"
mkdir -p "$(dirname "$BACKUP_PATH")" 2>/dev/null
if ! cp "$CONFIG_PATH" "$BACKUP_PATH" >> "$LOG_FILE" 2>&1; then
    echo "[$(date '+%Y-%m-%d %H:%M:%S')] Error: Failed to create backup of $CONFIG_PATH" >> "$LOG_FILE"
    exit 10
fi

# Удаление файла конфигурации
echo "[$(date '+%Y-%m-%d %H:%M:%S')] Deleting configuration file $CONFIG_PATH" >> "$LOG_FILE"
if ! rm -f "$CONFIG_PATH" >> "$LOG_FILE" 2>&1; then
    echo "[$(date '+%Y-%m-%d %H:%M:%S')] Error: Failed to delete configuration file $CONFIG_PATH" >> "$LOG_FILE"
    exit 11
fi

# Повторная проверка синтаксиса
echo "[$(date '+%Y-%m-%d %H:%M:%S')] Re-checking Apache configuration syntax" >> "$LOG_FILE"
if ! apachectl configtest >> "$LOG_FILE" 2>&1; then
    echo "[$(date '+%Y-%m-%d %H:%M:%S')] Error: Apache configuration test failed after deletion" >> "$LOG_FILE"
    exit 12
fi

echo "[$(date '+%Y-%m-%d %H:%M:%S')] Successfully disabled and deleted virtual host $CONFIG_FILE" >> "$LOG_FILE"

exit 0