#!/bin/bash
# setup_config_ssh.sh - Скрипт для настройки конфигурации SSH
# Расположение: bash/ssh/setup_config_ssh.sh

set -euo pipefail

# Проверка, что скрипт не запущен напрямую
[[ "${BASH_SOURCE[0]}" == "$0" ]] && {
    echo "This script ('$0') is meant to be sourced"
    exit 1
}

# Создание резервной копии конфигураций SSH
backup_ssh_config() {
    [[ ! -f "$SSH_BACKUP_CONFIG" ]] && {
        cp "$SSH_CONFIG_FILE" "$SSH_BACKUP_CONFIG" || {
            log_message "error" "Failed to backup '$SSH_CONFIG_FILE'"
            return ${EXIT_SSH_CONFIG_FAILED}
        }
    }
    [[ -f "$SSH_CONFIG_FILE_STUDENTS" ]] && [[ ! -f "$SSH_BACKUP_CONFIG_STUDENTS" ]] && {
        cp "$SSH_CONFIG_FILE_STUDENTS" "$SSH_BACKUP_CONFIG_STUDENTS" || {
            log_message "error" "Failed to backup '$SSH_CONFIG_FILE_STUDENTS'"
            return ${EXIT_SSH_CONFIG_FAILED}
        }
    }
    return ${EXIT_SUCCESS}
}

# Проверка и обновление основного sshd_config
update_main_config() {
    local include_line="Include ${SSH_CONFIGS_DIR}/*.conf"
    grep -qE "^\s*${include_line}" "$SSH_CONFIG_FILE" || {
        echo "$include_line" >> "$SSH_CONFIG_FILE" || {
            log_message "error" "Failed to update '$SSH_CONFIG_FILE'"
            return ${EXIT_SSH_CONFIG_FAILED}
        }
    }
    return ${EXIT_SUCCESS}
}

update_student_config() {
    echo -e "$MATCH_GROUP_STUDENT" >> "$SSH_CONFIG_FILE_STUDENTS" && update_permissions "$SSH_CONFIG_FILE_STUDENTS" 644 root:root || {
        log_message "error" "Failed to update '$SSH_CONFIG_FILE_STUDENTS'"
        return ${EXIT_SSH_CONFIG_FAILED}
    }
    return ${EXIT_SUCCESS}
}

restart_ssh_service() {
    systemctl restart sshd >/dev/null && {
        log_message "info" "SSH service reloaded successfully"
        return ${EXIT_SUCCESS}
    } || {
        log_message "error" "Failed to reload SSH service"
        return ${EXIT_SSH_CONFIG_FAILED}; 
    }
}

apply_ssh_config () {
    sshd -t >/dev/null || { 
        log_message "error" "SSH configuration syntax error"
        cp "$SSH_BACKUP_CONFIG" "$SSH_CONFIG_FILE" || true
        rm -f "$SSH_CONFIG_FILE_STUDENTS" || true
        return ${EXIT_SSH_CONFIG_FAILED}
    }
    restart_ssh_service || return $?
    get_config_hash > "${CONFIG_HASH_FILE}" || {
        log_message "error" "Failed to write hash to '$CONFIG_HASH_FILE'"
        return ${EXIT_SSH_CONFIG_FAILED}
    }
    return ${EXIT_SUCCESS}
}

# Проверка и настройка конфигурации SSH
# Создание backup
backup_ssh_config || exit $?

# Обновление главного файла конфигурации
update_main_config &
pid1=$!

# Обновление файла конфигурации students.conf
update_student_config &
pid2=$!

# Ожидание завершения обновления файлов
wait $pid1 || exit $?
wait $pid2 || exit $?

apply_ssh_config || exit $?

log_message "info" "SSH configuration applied successfully"

exit ${EXIT_SUCCESS}