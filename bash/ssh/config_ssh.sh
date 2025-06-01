#!/bin/bash
# setup_config_ssh.sh - Скрипт для настройки конфигурации SSH
# Расположение: bash/ssh/setup_config_ssh.sh

set -euo pipefail

# Подключение логального config.sh
LOCAL_CONFIG="$(realpath $(dirname "${BASH_SOURCE[0]}")/config.sh)"
source "$LOCAL_CONFIG" || {
    echo "Failed to source local config '$LOCAL_CONFIG'" >&2
    exit 1
}

declare -rx STUDENT_CONF="$CONFIG_DIR/student.conf"
declare -rx TEMPLATE_CONF="$(dirname "${BASH_SOURCE[0]}")/student.conf"
declare -rx LOCK_FILE="$TMP_DIR/ssh_configure.lock"
declare -rx BACKUP_DIR="/etc/ssh/backup"
declare -rx BACKUP_FILE="$BACKUP_DIR/sshd_config.bak.$(date +%Y%m%d_%H%M%S)"
declare -x TMPFILE
TMPFILE=$(mktemp -t ssh.XXXXXX) || { echo "Error: Failed to create temp file" >&2; exit "$EXIT_GENERAL_ERROR"; }

# Проверка требований
check_requirements() {
    command -v sshd >/dev/null || {
        log_message "error" "SSH (sshd) is not installed"
        return "$EXIT_SSH_NOT_INSTALLED"
    }

    [[ -f "$CONFIG_FILE" && -w "$CONFIG_FILE" ]] || {
        log_message "error" "Configuration file '$CONFIG_FILE' not found or not writable"
        return "$EXIT_GENERAL_ERROR"
    }
    [[ -f "$TEMPLATE_CONF" ]] || {
        log_message "error" "Template file '$TEMPLATE_CONF' not found"
        return "$EXIT_GENERAL_ERROR"
    }

    sshd -t -f "$CONFIG_FILE" >/dev/null 2>&1 || {
        log_message "error" "Configuration '$CONFIG_FILE' is invalid"
        return "$EXIT_SSH_CONFIG_FAILED"
    }

    create_directories "$(dirname "$BACKUP_FILE")" 755 root:root || return $?
    find "$BACKUP_DIR" -name "sshd_config.bak.*" -mtime +7 -delete 2>/dev/null || {
        log_message "warning" "Failed to delete old backups in '$BACKUP_DIR'"
    }

    return 0
}

# Перезапуск SSH
restart_ssh() {
    log_message "info" "Reloading SSH configuration"

    systemctl is-active sshd >/dev/null 2>&1 || {
        log_message "warning" "sshd service is inactive, skipping reload"
        return 0
    }

    systemctl reload sshd >/dev/null || {
        log_message "warning" "Failed to reload configuration SSH, attempting restart"
        systemctl restart sshd >/dev/null 2>&1 || {
            log_message "error" "Failed to restart SSH"
            return "$EXIT_SSH_CONFIG_FAILED"
        }
    }

    log_message "info" "Samba configuration reloaded successfully"
    return 0
}

# Проверка и обновление основной конфигурации
check_main_config() {
    local include_line="Include $CONFIG_DIR/*.conf"

    if ! grep -Fx "$include_line" "$CONFIG_FILE" >/dev/null; then
        log_message "info" "Adding include directive to '$CONFIG_FILE'"
        {
            cat "$CONFIG_FILE"
            echo ""
            echo "# Include configurations"
            echo "$include_line"
        } > "$TMPFILE" || {
            log_message "error" "Failed to append include directive to '$TMPFILE'"
            return "$EXIT_GENERAL_ERROR"
        }
    else
        cp "$CONFIG_FILE" "$TMPFILE" || {
            log_message "error" "Failed to copy '$CONFIG_FILE' to temporary file"
            return "$EXIT_GENERAL_ERROR"
        }
    fi

    return 0
}

# Проверка и обновление student.conf
check_student_template() {
    local template_content
    template_content=$(envsubst '${STUDENT_GROUP} ${CHROOT_ROOT}' < "$TEMPLATE_CONF") || {
        log_message "error" "Failed to process template '$TEMPLATE_CONF'"
        return "$EXIT_GENERAL_ERROR"
    }

    [[ -f "$STUDENT_CONF" ]] && cmp -s <(printf '%s' "$template_content") "$STUDENT_CONF" && return 0

    log_message "info" "Updating '$STUDENT_CONF' due to content change"
    printf '%s' "$template_content" > "$STUDENT_CONF" || {
        log_message "error" "Failed to write to '$STUDENT_CONF'"
        return "$EXIT_SAMBA_CONFIG_FAILED"
    }

    update_permissions "$STUDENT_CONF" 600 root:root || return $?
    return 0
}

# Применить обновления
apply_updates() {
    check_main_config || return $?
    check_student_template || return $?

    sshd -t -f "$TMPFILE" >/dev/null 2>&1 || {
        log_message "error" "Temporary configuration is invalid"
        return "$EXIT_SSH_CONFIG_FAILED"
    }

    if ! cmp -s "$TMPFILE" "$CONFIG_FILE"; then
        log_message "info" "Configuration changed, applying updates"
        cp "$CONFIG_FILE" "$BACKUP_FILE" 2>/dev/null || log_message "warning" "Failed to create backup '$BACKUP_FILE'"
        mv "$TMPFILE" "$CONFIG_FILE" || {
            log_message "error" "Failed to apply new configuration to '$CONFIG_FILE'"
            return "$EXIT_SSH_CONFIG_FAILED"
        }
        restart_ssh || return $?
        log_message "ok" "Configuration updated successfully"
    else
        log_message "info" "No configuration changes detected"
    fi

    return 0
}

# Очистка
cleanup() {
    [[ -f "$TMPFILE" ]] && rm -f "$TMPFILE" 2>/dev/null
    return 0
}

# Основной процесс
trap cleanup SIGINT SIGTERM EXIT

check_requirements || exit $?

apply_updates || exit $?

exit 0