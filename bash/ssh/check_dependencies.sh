#!/bin/bash

# Скрипт для проверки зависимостей и настройки SSH

# Подключение конфигурации
CONFIG_SCRIPT="$(dirname "${BASH_SOURCE[0]}")/config.sh"
if [[ ! -f "$CONFIG_SCRIPT" ]]; then
    echo "[ERROR]: Config script '$CONFIG_SCRIPT' not found" >&2
    exit 1
fi
source "$CONFIG_SCRIPT" "$@"
if [[ $? -ne 0 ]]; then
    echo "[ERROR]: Failed to source config script '$CONFIG_SCRIPT'" >&2
    exit 1
fi

# Подключение проверки зависимостей
source_script "$CHECK_DEPS_SCRIPT" "$@"
if [[ $? -ne 0 ]]; then
    log "$LOG_ERROR: Failed to source script '$CHECK_DEPS_SCRIPT'"
    return $ERR_FILE_NOT_FOUND
fi

# Проверка наличия функции check_deps
if ! declare -F check_deps >/dev/null; then
    log "[ERROR]: Function 'check_deps' not defined after sourcing '$CHECK_DEPS_SCRIPT'"
    return $ERR_FILE_NOT_FOUND
fi

# Проверка root-прав
check_root
if [[ $? -ne 0 ]]; then
    log "$LOG_ERROR: Failed check root privileges"
    exit $ERR_ROOT_REQUIRED
fi

log "$LOG_INFO: Starting ssh dependency checking"

# Список зависимостей
DEPENDENCIES=("openssh-server" "jailkit" "parallel")
check_deps "${DEPENDENCIES[@]}"
if [[ $? -ne 0 ]]; then
    log "$LOG_ERROR: Failed check dependencies"
    exit $ERR_GENERAL
fi

# Проверка SSH-сервиса
SSH_SERVICE="sshd"
if ! systemctl is-active --quiet "$SSH_SERVICE"; then
    log "$LOG_INFO: SSH service is not active, attempting to start"
    if systemctl start "$SSH_SERVICE" 2>&1 | tee -a "$LOG_FILE"; then
        log "$LOG_INFO: SSH service started successfully"
    else
        log "$LOG_ERROR: Failed to start SSH service"
        exit $ERR_SSH_CONFIG_FAILED
    fi
else
    log "$LOG_INFO:  SSH service is active"
fi

# Проверка SSH-порта
if [[ ! -f "$SSH_CONFIG_FILE" ]]; then
    log "$LOG_ERROR:  SSH configuration file '$SSH_CONFIG_FILE' not found"
    exit $ERR_FILE_NOT_FOUND
fi

SSH_PORT=$(grep -h -E "^Port\s+[0-9]+" "$SSH_CONFIG_FILE" "$SSH_CONFIG_DIR"/*.conf 2>/dev/null | awk '{print $2}' | head -n 1)
if [[ -z "$SSH_PORT" || ! "$SSH_PORT" =~ ^[0-9]+$ || "$SSH_PORT" -lt 1 || "$SSH_PORT" -gt 65535 ]]; then
    log "$LOG_WARNING: Invalid or missing SSH port, defaulting to 22"
    SSH_PORT=22
fi
log "$LOG_INFO: Detected SSH port: $SSH_PORT"

# Проверка UFW
if command -v ufw >/dev/null 2>&1; then
    if ufw status | grep -qw "active"; then
        log "$LOG_INFO:  UFW is active"
        if ! ufw status | grep -E "$SSH_PORT/tcp.*ALLOW" >/dev/null; then
            if ufw allow "$SSH_PORT/tcp" >> "$LOG_FILE" 2>&1; then
                log "$LOG_INFO: UFW configured to allow SSH on port $SSH_PORT"
            else
                log "ERROR" "Failed to configure UFW for SSH port $SSH_PORT"
                exit $ERR_GENERAL
            fi
        else
            log "$LOG_INFO: UFW already allows SSH on port $SSH_PORT"
        fi
    else
        log "$LOG_WARNING: UFW is installed but not active"
    fi
else
    log "$LOG_WARNING: UFW is not installed"
fi

log "$LOG_INFO: All dependencies are available and SSH is configured"

exit 0