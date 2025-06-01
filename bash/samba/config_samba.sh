#!/bin/bash
# config_samba.sh - Скрипт для настройки конфигурации Samba
# Расположение: bash/samba/config_samba.sh

set -euo pipefail

# Подключение логального config.sh
LOCAL_CONFIG="$(realpath $(dirname "${BASH_SOURCE[0]}")/config.sh)"
source "$LOCAL_CONFIG" || {
    echo "Failed to source local config '$LOCAL_CONFIG'" >&2
    exit 1
}

declare -rx CONFIG_FILE="/etc/samba/smb.conf"
declare -rx STUDENT_CONF="/etc/samba/student.conf"
declare -rx TEMPLATE_CONF="$(dirname "${BASH_SOURCE[0]}")/student.conf"
declare -rx LOCK_FILE="$TMP_DIR/samba_configure.lock"
declare -rx BACKUP_DIR="/etc/samba/backup"
declare -rx BACKUP_FILE="$BACKUP_DIR/smb.conf.bak.$(date +%Y%m%d_%H%M%S)"
declare -x TMPFILE
TMPFILE=$(mktemp -t samba.XXXXXX) || { echo "Error: Failed to create temp file" >&2; exit "$EXIT_GENERAL_ERROR"; }
declare -x WORKSPACE_USER
WORKSPACE_USER=$(chroot_workspace "%U" 2>/dev/null) || WORKSPACE_USER=""

# Проверка требований
check_requirements() {
    command -v smbd >/dev/null || {
        log_message "error" "Samba (smbd) is not installed"
        return "$EXIT_SAMBA_NOT_INSTALLED"
    }

    [[ -f "$CONFIG_FILE" && -w "$CONFIG_FILE" ]] || {
        log_message "error" "Configuration file '$CONFIG_FILE' not found or not writable"
        return "$EXIT_GENERAL_ERROR"
    }
    [[ -f "$TEMPLATE_CONF" ]] || {
        log_message "error" "Template file '$TEMPLATE_CONF' not found"
        return "$EXIT_GENERAL_ERROR"
    }

    testparm -s "$CONFIG_FILE" >/dev/null 2>&1 || {
        log_message "error" "Configuration '$CONFIG_FILE' is invalid"
        return "$EXIT_SAMBA_CONFIG_FAILED"
    }

    create_directories "$(dirname "$BACKUP_FILE")" 755 root:root || return $?
    find "$BACKUP_DIR" -name "smb.conf.bak.*" -mtime +7 -delete 2>/dev/null || {
        log_message "warning" "Failed to delete old backups in '$BACKUP_DIR'"
    }

    return 0
}

# Перезапуск smbd
restart_smbd() {
    log_message "info" "Reloading Samba configuration"

    systemctl is-active smbd >/dev/null 2>&1 || {
        log_message "warning" "smbd service is inactive, skipping reload"
        return 0
    }

    smbcontrol smbd reload-config >/dev/null 2>&1 || {
        log_message "warning" "Failed to reload configuration Samba, attempting restart"
        systemctl restart smbd >/dev/null 2>&1 || {
            log_message "error" "Failed to restart Samba"
            return "$EXIT_SAMBA_SERVICE_FAILED"
        }
    }

    log_message "info" "Samba configuration reloaded successfully"
    return 0
}

# Проверка и обновление секции [global]
check_global_section() {
    local include_line="include = $STUDENT_CONF"

    if ! grep -Fx "$include_line" "$CONFIG_FILE" >/dev/null; then
        log_message "info" "Adding include directive to '$CONFIG_FILE'"
        {
            cat "$CONFIG_FILE"
            echo ""
            echo "# Include student configuration $STUDENT_CONF"
            echo "    $include_line"
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
    template_content=$(envsubst '${WORKSPACE_USER} ${SITE_GROUP}' < "$TEMPLATE_CONF") || {
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
    check_global_section || return $?
    check_student_template || return $?

    testparm -s "$TMPFILE" >/dev/null 2>&1 || {
        log_message "error" "Temporary configuration is invalid"
        return "$EXIT_SAMBA_CONFIG_FAILED"
    }

    if ! cmp -s "$TMPFILE" "$CONFIG_FILE"; then
        log_message "info" "Configuration changed, applying updates"
        cp "$CONFIG_FILE" "$BACKUP_FILE" 2>/dev/null || log_message "warning" "Failed to create backup '$BACKUP_FILE'"
        mv "$TMPFILE" "$CONFIG_FILE" || {
            log_message "error" "Failed to apply new configuration to '$CONFIG_FILE'"
            return "$EXIT_SAMBA_CONFIG_FAILED"
        }
        restart_smbd || return $?
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

with_lock "$LOCK_FILE" apply_updates
exit $?