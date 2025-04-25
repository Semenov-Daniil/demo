#!/bin/bash

# Настройка переменных
LOG_FILE="${1:-logs/setup_ssh.log}"
SSH_CONFIG_MAIN="/etc/ssh/sshd_config"
SSH_CONFIG_DIR="/etc/ssh/sshd_config.d"
STUDENT_CONF_FILE="$SSH_CONFIG_DIR/students.conf"

# Настройка логирования
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
echo "Configuring the SSH configuration file..."
log "Configuring the SSH configuration file..."

# Проверка root-прав
if [[ $EUID -ne 0 ]]; then
    log "Error: This script must be run with root privileges"
    exit 1
fi

# Проверка директории sshd_config.d
if [ ! -d "$SSH_CONFIG_DIR" ]; then
    log "Error: SSH config include directory not found: $SSH_CONFIG_DIR"
    exit 1
fi

# Создание students.conf
log "Info: Writing SSH config for 'students' group to $STUDENT_CONF_FILE"

cat > "$STUDENT_CONF_FILE" <<EOF
Match Group students
    ChrootDirectory /var/chroot
    ForceCommand /bin/bash
    X11Forwarding no
    AllowTcpForwarding no
    PasswordAuthentication yes
EOF
if [[ $? -ne 0 ]]; then
    log "Error: Failed to create $STUDENT_CONF_FILE"
    exit 3
fi
chown root:root "$STUDENT_CONF_FILE"
chmod 644 "$STUDENT_CONF_FILE"

log "Ok: SSH students.conf created"

# Проверка основного sshd_config
if ! grep -Fxq "Include $SSH_CONFIG_DIR/*.conf" "$SSH_CONFIG_MAIN"; then
    echo "Include $SSH_CONFIG_DIR/*.conf" >> "$SSH_CONFIG_MAIN"
    log "Info: Added Include directive to $SSH_CONFIG_MAIN"
else
    log "Info: Include directive already present in $SSH_CONFIG_MAIN"
fi

# Проверка синтаксиса и перезапуск SSH
log "Info: Check SSH configuration syntax and restarting..."
if sshd -t >> "$LOG_FILE" 2>&1;  then
    if systemctl restart ssh 2>/dev/null || systemctl restart sshd 2>/dev/null; then
        log "Ok: SSH service restarted"
    else
        log "Error: Failed to restart SSH service"
        exit 1
    fi
else
    log "Error: SSH configuration syntax error" >> "$LOG_FILE"
    exit 3
fi

echo "SSH configured successfully for group 'students'"
log "SSH configured successfully for group 'students'"

exit 0