#!/bin/bash

if [ -z "$1" ]; then
    echo "Error: students directory path are required" >&2
    exit 1
fi

STUDENTS_DIR=$1
LOG_FILE="${2:-logs/samba.log}"
CONFIG_FILE="/etc/samba/smb.conf"
BACKUP_CONFIG="/etc/samba/smb.conf.bak"

if [[ $EUID -ne 0 ]]; then
    echo "[$(date '+%Y-%m-%d %H:%M:%S')] Error: This script must be run with root privileges" >> "$LOG_FILE"
    exit 1
fi

LOG_DIR=$(dirname "$LOG_FILE")
if [[ ! -d "$LOG_DIR" ]]; then
    mkdir -p "$LOG_DIR" 2>/dev/null || {
        echo "[$(date '+%Y-%m-%d %H:%M:%S')] Error: Cannot create log directory '$LOG_DIR'" >> "$LOG_FILE"
        exit 1
    }
fi

if [[ ! -d "$STUDENTS_DIR" ]]; then
    mkdir -p "$STUDENTS_DIR" 2>/dev/null || {
        echo "[$(date '+%Y-%m-%d %H:%M:%S')] Error: Cannot create log directory '$STUDENTS_DIR'" >> "$LOG_FILE"
        exit 1
    }
fi

if ! command -v smbd >/dev/null 2>&1 || ! command -v nmbd >/dev/null 2>&1; then
    echo "[$(date '+%Y-%m-%d %H:%M:%S')] Error: Samba is not installed. Please install samba and samba-common-bin" >> "$LOG_FILE"
    exit 1
fi

for service in smbd nmbd; do
    if ! systemctl is-active --quiet "$service"; then
        echo "[$(date '+%Y-%m-%d %H:%M:%S')] Starting $service service" >> "$LOG_FILE"
        if ! systemctl enable "$service" >> "$LOG_FILE" 2>&1 || ! systemctl start "$service" >> "$LOG_FILE" 2>&1; then
            echo "[$(date '+%Y-%m-%d %H:%M:%S')] Error: Failed to start $service service" >> "$LOG_FILE"
            exit 2
        fi
    fi
done

if command -v ufw >/dev/null 2>&1 && ufw status | grep -q "Status: active"; then
    echo "[$(date '+%Y-%m-%d %H:%M:%S')] UFW is active, checking Samba ports" >> "$LOG_FILE"
    for port in 137/udp 138/udp 139/tcp 445/tcp; do
        if ! ufw status | grep -q "$port"; then
            if ! ufw allow "$port" >> "$LOG_FILE" 2>&1; then
                echo "[$(date '+%Y-%m-%d %H:%M:%S')] Error: Failed to open port $port in UFW" >> "$LOG_FILE"
                exit 1
            fi
        fi
    done
fi

if [[ ! -f "$BACKUP_CONFIG" ]]; then
    cp "$CONFIG_FILE" "$BACKUP_CONFIG" 2>/dev/null || {
        echo "[$(date '+%Y-%m-%d %H:%M:%S')] Error: Failed to create backup of $CONFIG_FILE" >> "$LOG_FILE"
        exit 1
    }
fi

echo "[$(date '+%Y-%m-%d %H:%M:%S')] Checking [global] section in $CONFIG_FILE" >> "$LOG_FILE"
GLOBAL_PARAMS=(
    "workgroup = WORKGROUP"
    "server string = %h server (Samba, Ubuntu)"
    "server role = standalone server"
    "security = user"
    "map to guest = never"
    "smb encrypt = required"
    "min protocol = SMB3"
    "log file = /var/log/samba.log"
    "max log size = 1000"
)

TEMP_CONFIG=$(mktemp)
cp "$CONFIG_FILE" "$TEMP_CONFIG" 2>/dev/null || {
    echo "[$(date '+%Y-%m-%d %H:%M:%S')] Error: Failed to copy $CONFIG_FILE to temporary file" >> "$LOG_FILE"
    exit 1
}

if ! grep -q "\[global\]" "$TEMP_CONFIG"; then
    echo "[global]" >> "$TEMP_CONFIG"
fi

for param in "${GLOBAL_PARAMS[@]}"; do
    key=$(echo "$param" | cut -d'=' -f1 | xargs)
    if ! grep -q "^\s*$key\s*=" "$TEMP_CONFIG"; then
        sed -i "/\[global\]/a $param" "$TEMP_CONFIG" || {
            echo "[$(date '+%Y-%m-%d %H:%M:%S')] Error: Failed to add $param to [global] section" >> "$LOG_FILE"
            exit 3
        }
    fi
done

USER_SHARE="\n[%U]\n   path = $STUDENTS_DIR/%U\n   valid users = %U\n   read only = no\n   browsable = yes\n   create mask = 0660\n   directory mask = 0770\n   force user = %U\n   force group = www-data\n"
if ! grep -q "\[%U\]" "$TEMP_CONFIG"; then
    echo -e "$USER_SHARE" >> "$TEMP_CONFIG" || {
        echo "[$(date '+%Y-%m-%d %H:%M:%S')] Error: Failed to add [%U] share to $TEMP_CONFIG" >> "$LOG_FILE"
        exit 4
    }
fi

if ! testparm -s "$TEMP_CONFIG" >/dev/null 2>&1; then
    echo "[$(date '+%Y-%m-%d %H:%M:%S')] Error: Invalid Samba configuration in $TEMP_CONFIG" >> "$LOG_FILE"
    exit 5
fi

if ! cmp -s "$TEMP_CONFIG" "$CONFIG_FILE"; then
    cp "$TEMP_CONFIG" "$CONFIG_FILE" || {
        echo "[$(date '+%Y-%m-%d %H:%M:%S')] Error: Failed to update $CONFIG_FILE" >> "$LOG_FILE"
        exit 6
    }
    echo "[$(date '+%Y-%m-%d %H:%M:%S')] Updated $CONFIG_FILE with required parameters" >> "$LOG_FILE"
    if ! smbcontrol smbd reload-config >> "$LOG_FILE" 2>&1; then
        echo "[$(date '+%Y-%m-%d %H:%M:%S')] Error: Failed to reload Samba configuration" >> "$LOG_FILE"
        exit 6
    fi
fi

rm -f "$TEMP_CONFIG"

echo "[$(date '+%Y-%m-%d %H:%M:%S')] Samba configuration successfully checked and applied" >> "$LOG_FILE"

exit 0