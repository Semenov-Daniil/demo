#!/bin/bash
# setup_config_samba.sh - Скрипт для настройки конфигурации Samba
# Расположение: bash/samba/setup_config_samba.sh

set -euo pipefail

# Проверка, что скрипт не запущен напрямую
[[ "${BASH_SOURCE[0]}" == "$0" ]] && {
    echo "This script ('$0') is meant to be sourced"
    exit 1
}

# Создание резервной копии конфигурации Samba
backup_samba_config() {
    [[ -f "$SAMBA_BACKUP_CONFIG" ]] || {
        cp "$SAMBA_CONFIG_FILE" "$SAMBA_BACKUP_CONFIG" || {
            log_message "error" "Failed to backup $SAMBA_CONFIG_FILE"
            return ${EXIT_SAMBA_CONFIG_FAILED}
        }
    }
}

# Обновление глобальной секции конфигурации Samba
update_global_config() {
    cp "$SAMBA_CONFIG_FILE" "$SAMBA_TEMP_CONFIG" || {
        log_message "error" "Failed to copy $SAMBA_CONFIG_FILE"
        return ${EXIT_SAMBA_CONFIG_FAILED}
    }
    awk -v params="${SAMBA_GLOBAL_PARAMS[*]}" '
        BEGIN { split(params, p, "\n"); for (i in p) { split(p[i], a, "="); keys[a[1]] = p[i] } }
        /\[global\]/ { print; in_global=1; next }
        /^\[/ && in_global { for (k in keys) if (!seen[k]) print keys[k]; in_global=0 }
        { print; if (in_global && $1 in keys) { seen[$1]=1 } }
        END { if (in_global) for (k in keys) if (!seen[k]) print keys[k] }
    ' "$SAMBA_TEMP_CONFIG" > "$SAMBA_TEMP_CONFIG.tmp" && mv "$SAMBA_TEMP_CONFIG.tmp" "$SAMBA_TEMP_CONFIG"
}

# Добавление пользовательской шары Samba
add_user_share() {
    grep -q "\[%U\]" "$SAMBA_TEMP_CONFIG" || {
        echo -e "$USER_SHARE" >> "$SAMBA_TEMP_CONFIG" || {
            log_message "error" "Failed to add [%U] share"
            return ${EXIT_SAMBA_SHARE_FAILED}
        }
    }
}

# Перезагрука Samba
reload_samba() {
    smbcontrol smbd reload-config >/dev/null && {
        log_message "info" "Samba configuration reloaded successfully"
    } || {
        log_message "warning" "Failed to reload Samba configuration"
    }
}

# Проверка и применение конфигурации Samba
apply_samba_config() {
    testparm -s "$SAMBA_TEMP_CONFIG" >/dev/null 2>&1 || {
        log_message "error" "Invalid Samba configuration"
        return ${EXIT_SAMBA_CONFIG_FAILED}
    }
    cmp -s "$SAMBA_TEMP_CONFIG" "$SAMBA_CONFIG_FILE" || {
        cp "$SAMBA_TEMP_CONFIG" "$SAMBA_CONFIG_FILE" || {
            log_message "error" "Failed to update $SAMBA_CONFIG_FILE"
            cp "$SAMBA_BACKUP_CONFIG" "$SAMBA_CONFIG_FILE" 2>/dev/null || true
            return ${EXIT_SAMBA_CONFIG_FAILED}
        }
        update_permissions "$SAMBA_CONFIG_FILE" "644" "root:root" || {
            log_message "error" "Failed to set permission/ownership for '$SAMBA_CONFIG_FILE'"
            cp "$SAMBA_BACKUP_CONFIG" "$SAMBA_CONFIG_FILE" 2>/dev/null || true
            return ${EXIT_SAMBA_CONFIG_FAILED}
        }
        reload_samba
    }
    get_config_hash > "${CONFIG_HASH_FILE}" || {
        log_message "error" "Failed to write hash to '$CONFIG_HASH_FILE'"
        return ${EXIT_SAMBA_CONFIG_FAILED}
    }
}

# Очистка временных файлов
cleanup() {
    rm -f "$SAMBA_TEMP_CONFIG"
}

# Проверка и настройка конфигурации Samba
backup_samba_config || return $?
update_global_config || return $?
add_user_share || return $?
apply_samba_config || return $?
cleanup
log_message "info" "Samba configuration applied successfully"

exit ${EXIT_SUCCESS}