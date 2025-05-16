#!/bin/bash

# setup_config_ssh.sh - Скрипт для настройки конфигурации SSH
# Расположение: bash/ssh/setup_config_ssh.sh

set -euo pipefail

# Проверка, что скрипт не запущен напрямую
[[ "${BASH_SOURCE[0]}" == "$0" ]] && {
    echo "This script ('$0') is meant to be sourced"
    exit 1
}

# Проверка фалов/директорий конфигурации
check_configs() {
    [[ -f "$SSH_CONFIG_FILE" && -w "$SSH_CONFIG_FILE" ]] || {
        log_message "error" "SSH main config file not found or not writable '$SSH_CONFIG_FILE'"
        exit ${EXIT_SSH_CONFIG_FAILED}
    }
    [[ -d "$SSH_CONFIGS_DIR" && -w "$SSH_CONFIGS_DIR" ]] || {
        log_message "error" "SSH config directory not found or not writable '$SSH_CONFIGS_DIR'"
        return ${EXIT_SSH_CONFIG_FAILED}
    }
}

# Создание резервной копии конфигураций SSH
backup_samba_config() {
    [[ -f "$SSH_BACKUP_CONFIG" ]] || {
        cp "$SSH_CONFIG_FILE" "$SSH_BACKUP_CONFIG" || {
            log_message "error" "Failed to backup '$SSH_CONFIG_FILE'"
            return ${EXIT_SSH_CONFIG_FAILED}
        }
    }
    [[ -f "$STUDENT_CONF_FILE" ]] && [[ -f "$SSH_BACKUP_STUDENT_CONFIG" ]] || {
        cp "$STUDENT_CONF_FILE" "$SSH_BACKUP_STUDENT_CONFIG" || {
            log_message "error" "Failed to backup '$STUDENT_CONF_FILE'"
            return ${EXIT_SSH_CONFIG_FAILED}
        }
    }
}

# Проверка и обновление основного sshd_config
update_main_config() {
    if ! grep -E "^\s*Include\s+${SSH_CONFIGS_DIR}/\*.conf" "$SSH_CONFIG_FILE" >/dev/null; then
        echo "Include ${SSH_CONFIGS_DIR}/*.conf" >> "$SSH_CONFIG_FILE" || {
            log_message "error" "Failed to add Include directive to $SSH_CONFIG_FILE"
            exit ${EXIT_SSH_CONFIG_FAILED}
        }
    fi
}

update_student_config() {
    local config_hash=$(echo -n "$MATCH_STUDENT" | md5sum | cut -d' ' -f1)

    [[ -f "$SSH_STUDENT_CONFIG_HASH" && "$(cat "$SSH_STUDENT_CONFIG_HASH")" == "$config_hash" && -f "$STUDENT_CONF_FILE" ]] && return
    echo -e "$MATCH_STUDENT" > "$STUDENT_CONF_FILE" && update_permissions "$STUDENT_CONF_FILE" 644 root:root || {
        log_message "error" "Failed to update '$STUDENT_CONF_FILE'"
        return ${EXIT_SSH_CONFIG_FAILED}
    }
    echo "$config_hash" > "$SSH_STUDENT_CONFIG_HASH" 2>/dev/null || log_message "warning" "Failed to cache student config hash '$SSH_STUDENT_CONFIG_HASH'"
}

update_main_config() {
    local include_line="Include ${SSH_CONFIGS_DIR}/*.conf"
    local config_hash=$(md5sum "$SSH_CONFIG_FILE" 2>/dev/null | cut -d' ' -f1)
    [[ -f "$SSH_MAIN_CONFIG_HASH" && "$(cat "$SSH_MAIN_CONFIG_HASH")" == "$config_hash" && -f "$SSH_CONFIG_FILE" ]] && return
    grep -qE "^\s*${include_line}" "$SSH_CONFIG_FILE" || {
        echo "$include_line" >> "$SSH_CONFIG_FILE" || {
            log_message "error" "Failed to update '$SSH_CONFIG_FILE'"
            return ${EXIT_SSH_CONFIG_FAILED}
        }
    }
    echo "$config_hash" > "$SSH_MAIN_CONFIG_HASH" 2>/dev/null || log_message "warning" "Failed to cache main config hash '$SSH_MAIN_CONFIG_HASH'"
}

restart_ssh_service() {
    sshd -t >/dev/null || { log_message "error" "SSH configuration syntax error"; return ${EXIT_SSH_CONFIG_FAILED}; }
    systemctl restart sshd || { log_message "error" "Failed to restart SSH service"; return ${EXIT_SSH_CONFIG_FAILED}; }

    local config_hash=$(md5sum "$SSH_CONFIG_FILE" "$SSH_CONFIGS_DIR"/*.conf 2>/dev/null | md5sum | cut -d' ' -f1)
    echo "$config_hash" > "$CONFIG_HASH_FILE" 2>/dev/null || log_message "warning" "Failed to cache SSH config hash '$CONFIG_HASH_FILE'"
}

# Основная логика
setup_config_ssh () {
    check_configs || return $?
    update_student_config || return $?
    update_main_config || return $?
    restart_ssh_service || return $?
}

# Установка блокировки
with_lock "${TMP_DIR}/${LOCK_SSH_PREF}_config.lock" setup_config_ssh

log_message "info" "SSH configuration applied successfully"

return ${EXIT_SUCCESS}