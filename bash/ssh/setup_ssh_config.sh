#!/bin/bash

LOG_FILE="${1:-logs/setup_ssh.log}"
SSH_CONFIG_DIR="/etc/ssh/sshd_config.d"
SSH_CONFIG_FILE="$SSH_CONFIG_DIR/students_chroot.conf"

# Проверка root-прав
if [[ $EUID -ne 0 ]]; then
    echo "[$(date '+%Y-%m-%d %H:%M:%S')] Error: This script must be run with root privileges" >> "$LOG_FILE"
    exit 1
fi

# Проверка подключения sshd_config.d
if ! grep -q "Include /etc/ssh/sshd_config.d/*.conf" /etc/ssh/sshd_config; then
    echo "Include /etc/ssh/sshd_config.d/*.conf" | sudo tee -a /etc/ssh/sshd_config
    echo "[$(date '+%Y-%m-%d %H:%M:%S')] Added Include directive to /etc/ssh/sshd_config" >> "$LOG_FILE"
fi

# Создание конфигурации SSH
mkdir -p "$SSH_CONFIG_DIR" 2>>"$LOG_FILE"
cat << EOF > "$SSH_CONFIG_FILE"
Match Group students
    ChrootDirectory /var/chroot
    AllowTcpForwarding no
    X11Forwarding no
EOF

# Проверка синтаксиса и перезапуск SSH
if sshd -t 2>>"$LOG_FILE"; then
    systemctl restart ssh 2>>"$LOG_FILE" || {
        echo "[$(date '+%Y-%m-%d %H:%M:%S')] Error: Failed to restart SSH" >> "$LOG_FILE"
        exit 2
    }
else
    echo "[$(date '+%Y-%m-%d %H:%M:%S')] Error: SSH configuration syntax error" >> "$LOG_FILE"
    exit 3
fi

echo "[$(date '+%Y-%m-%d %H:%M:%S')] SSH configuration set up for group students" >> "$LOG_FILE"
exit 0