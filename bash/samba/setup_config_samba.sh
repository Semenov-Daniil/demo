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
            exit ${EXIT_SAMBA_CONFIG_FAILED}
        }
    }
}

# Обновление глобальной секции конфигурации Samba
update_global_config() {
    cp "$SAMBA_CONFIG_FILE" "$SAMBA_TEMP_CONFIG" || {
        log_message "error" "Failed to copy $SAMBA_CONFIG_FILE"
        exit ${EXIT_SAMBA_CONFIG_FAILED}
    }
    grep -q "\[global\]" "$SAMBA_TEMP_CONFIG" || echo "[global]" >> "$SAMBA_TEMP_CONFIG"
    for param in "${SAMBA_GLOBAL_PARAMS[@]}"; do
        key=$(echo "$param" | cut -d'=' -f1 | xargs)
        awk -v k="$key" '/\[global\]/,/^\[/ {if ($1 == k) exit 1}' "$SAMBA_TEMP_CONFIG" >/dev/null || {
            sed -i "/\[global\]/a $param" "$SAMBA_TEMP_CONFIG" || {
                log_message "error" "Failed to add $param to [global]"
                exit ${EXIT_SAMBA_CONFIG_FAILED}
            }
        }
    done
}

# Добавление пользовательской шары Samba
add_user_share() {
    grep -q "\[%U\]" "$SAMBA_TEMP_CONFIG" || {
        echo -e "$USER_SHARE" >> "$SAMBA_TEMP_CONFIG" || {
            log_message "error" "Failed to add [%U] share"
            exit ${EXIT_SAMBA_SHARE_FAILED}
        }
    }
}

# Проверка и применение конфигурации Samba
apply_samba_config() {
    testparm -s "$SAMBA_TEMP_CONFIG" >/dev/null 2>&1 || {
        log_message "error" "Invalid Samba configuration"
        exit ${EXIT_SAMBA_TEST_FAILED}
    }
    cmp -s "$SAMBA_TEMP_CONFIG" "$SAMBA_CONFIG_FILE" || {
        cp "$SAMBA_TEMP_CONFIG" "$SAMBA_CONFIG_FILE" || {
            log_message "error" "Failed to update $SAMBA_CONFIG_FILE"
            exit ${EXIT_SAMBA_UPDATE_FAILED}
        }
        update_permissions "$SAMBA_CONFIG_FILE" 644 root:root || {
            log_message "error" "Failed to set permissions for $SAMBA_CONFIG_FILE"
            exit ${EXIT_SAMBA_UPDATE_FAILED}
        }
        touch ${RELOAD_NEEDED_FILE} || {
            log_message "error" "Failed to create Samba reload flag"
            exit ${EXIT_SAMBA_UPDATE_FAILED}
        }
        md5sum "$SAMBA_CONFIG_FILE" | cut -d' ' -f1 > ${CONFIG_HASH_FILE}
    }
}

# Очистка временных файлов
cleanup() {
    rm -f "$SAMBA_TEMP_CONFIG"
}

# Основная логика
# Создание директорий
create_directories "$SAMBA_LOG_DIR" 755 root:root || {
    log_message "error" "Failed to create '$SAMBA_LOG_DIR'"
    exit ${EXIT_GENERAL_ERROR}
}

setup_config_samba () {
    backup_samba_config
    update_global_config
    add_user_share
    apply_samba_config
    cleanup
}

# Установка блокировки
with_lock "${TMP_DIR}/${LOCK_SAMBA_PREF}_config.lock" setup_config_samba

log_message "info" "Samba configuration applied successfully"

return ${EXIT_SUCCESS}