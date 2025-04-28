#!/bin/bash

# Проверка root-прав
if [[ $EUID -ne 0 ]]; then
    echo "Error: This script must be run with root privileges" >&2
    exit $ERR_ROOT_REQUIRED
fi

# Подключение конфигурации
CONFIG_SCRIPT="$(dirname "${BASH_SOURCE[0]}")/../config.sh"
if [[ ! -f "$CONFIG_SCRIPT" ]]; then
    echo "Error: Config script '$CONFIG_SCRIPT' not found" >&2
    exit $ERR_FILE_NOT_FOUND
fi
mapfile -t ARGS < <(source "$CONFIG_SCRIPT" "$@") || {
    echo "Error: Failed to source config script '$CONFIG_SCRIPT'" >&2
    exit $ERR_FILE_NOT_FOUND
}

log "Configuring SSH for '$STUDENTS_GROUP' group"

# Проверка OpenSSH
if ! sshd -V 2>&1 | grep -q "OpenSSH"; then
    log "Error: OpenSSH not installed or unsupported version"
    exit $ERR_SSH_CONFIG_FAILED
fi

# Проверка директории sshd_config.d
if [[ ! -d "$SSH_CONFIG_DIR" ]]; then
    log "Error: SSH config include directory not found: $SSH_CONFIG_DIR"
    exit $ERR_FILE_NOT_FOUND
fi

# Создание конфигурации группы стулентов
log "Info: Writing SSH config for '$STUDENTS_GROUP' group to $STUDENT_CONF_FILE"
cat > "$STUDENT_CONF_FILE" <<EOF
Match Group students
    ChrootDirectory ${CHROOT_DIR}/home/%u
    ForceCommand /bin/bash
    X11Forwarding no
    AllowTcpForwarding no
    PasswordAuthentication yes
EOF
if [[ $? -ne 0 ]]; then
    log "Error: Failed to create $STUDENT_CONF_FILE"
    exit $ERR_SSH_CONFIG_FAILED
fi
chown root:root "$STUDENT_CONF_FILE"
chmod 644 "$STUDENT_CONF_FILE"
log "Info: SSH students.conf created"

# Проверка основного sshd_config
if ! grep -Fxq "Include ${SSH_CONFIG_DIR}/*.conf" "$SSH_CONFIG_MAIN"; then
    echo "Include ${SSH_CONFIG_DIR}/*.conf" >> "$SSH_CONFIG_MAIN" 2>>"$LOG_FILE" || {
        log "Error: Failed to add Include directive to $SSH_CONFIG_MAIN"
        exit $ERR_SSH_CONFIG_FAILED
    }
    log "Info: Added Include directive to $SSH_CONFIG_MAIN"
else
    log "Info: Include directive already present in $SSH_CONFIG_MAIN"
fi

# Проверка синтаксиса и перезапуск SSH
log "Info: Checking SSH configuration syntax and restarting"
if sshd -t >> "$LOG_FILE" 2>&1; then
    systemctl restart sshd 2>>"$LOG_FILE" || systemctl restart ssh 2>>"$LOG_FILE" || {
        log "Error: Failed to restart SSH service"
        exit $ERR_SSH_CONFIG_FAILED
    }
    log "Info: SSH service restarted"
else
    log "Error: SSH configuration syntax error"
    exit $ERR_SSH_CONFIG_FAILED
fi

log "SSH configured successfully for group '$STUDENTS_GROUP'"

exit 0