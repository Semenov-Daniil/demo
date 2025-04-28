#!/bin/bash

# Проверка root-прав
if [[ $EUID -ne 0 ]]; then
    echo "Error: This script must be run with root privileges" >&2
    exit $ERR_ROOT_REQUIRED
fi

# Подключение конфигурации
CONFIG_SCRIPT="$(dirname "${BASH_SOURCE[0]}")/config.sh"

if [[ ! -f "$CONFIG_SCRIPT" ]]; then
    echo "Error: Config script '$CONFIG_SCRIPT' not found" >&2
    exit 3
fi

ret_file=$(mktemp)
mapfile -t ARGS < <(source "$CONFIG_SCRIPT" "$@"; echo $? > "$ret_file")
ret=$(cat "$ret_file")
rm -f "$ret_file"
if [[ $ret -ne 0 ]]; then
    echo "Error: Failed to source config script '$CONFIG_SCRIPT'" >&2
    exit $ERR_FILE_NOT_FOUND
fi

log "Starting dependency checking"

# Список зависимостей
DEPENDENCIES=("openssh-server" "jailkit" "parallel")

# Проверка зависимостей
for dep in "${DEPENDENCIES[@]}"; do
    if dpkg -l | grep -qw "$dep"; then
        log "Info: $dep is installed"
    else
        log "Error: $dep is not installed"
        exit $ERR_GENERAL
    fi
done

# Проверка статуса SSH
if ! systemctl is-active --quiet ssh && ! systemctl is-active --quiet sshd; then
    log "Info: SSH service is not active, trying to start it"
    if systemctl start ssh 2>>"$LOG_FILE" || systemctl start sshd 2>>"$LOG_FILE"; then
        log "Info: SSH service started successfully"
    else
        log "Error: failed to start SSH service"
        exit $ERR_GENERAL
    fi
else
    log "Info: SSH service is active"
fi

# Проверка порта SSH
if [[ ! -f /etc/ssh/sshd_config ]]; then
    log "Error: SSH configuration file '/etc/ssh/sshd_config' not found"
    exit $ERR_FILE_NOT_FOUND
fi

SSH_PORT=$(grep -h -E "^Port " /etc/ssh/sshd_config /etc/ssh/sshd_config.d/*.conf 2>/dev/null | awk '{print $2}' | head -n 1 || echo "22")

if [[ -z "$SSH_PORT" ]]; then
    log "Warning: Could not detect SSH port, defaulting to 22"
    SSH_PORT="22"
fi

log "Info: Detected SSH port: $SSH_PORT"

# Проверка UFW
if command -v ufw >/dev/null 2>&1; then
    if ufw status | grep -qw "active"; then
        log "Info: UFW is active"
        if ! ufw status | grep -E "$SSH_PORT/tcp.*ALLOW" >/dev/null; then
            ufw allow "$SSH_PORT/tcp" >> "$LOG_FILE" 2>&1 || {
                log "Error: Failed to configure UFW for SSH port $SSH_PORT"
                exit $ERR_GENERAL
            }
            log "Info: UFW configured to allow SSH on port $SSH_PORT"
        else
            log "Info: UFW already allows SSH on port $SSH_PORT"
        fi
    else
        log "Warning: UFW is installed but not active"
        ufw enable >> "$LOG_FILE" 2>&1 || {
            log "Error: Failed to enable UFW"
            exit $ERR_GENERAL
        }
        ufw allow "$SSH_PORT/tcp" >> "$LOG_FILE" 2>&1 || {
            log "Error: Failed to configure UFW for SSH port $SSH_PORT"
            exit $ERR_GENERAL
        }
        log "Info: UFW enabled and configured for SSH on port $SSH_PORT"
    fi
else
    log "Warning: UFW is not installed"
fi

log "All dependencies are available and SSH is active"

exit 0