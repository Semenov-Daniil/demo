#!/bin/bash
# setup_samba.sh - Скрипт для настройки Samba-сервера
# Расположение: bash/samba/setup_samba.sh

set -euo pipefail

# Проверка, что скрипт не запущен напрямую
if [[ "${BASH_SOURCE[0]}" == "$0" ]]; then
    echo "This script ('$0') is meant to be sourced" >&2
    return 1
fi

# Установка переменных по умолчанию
: "${SAMBA_CONFIG_FILE:=/etc/samba/smb.conf}"
: "${SAMBA_BACKUP_CONFIG:=/etc/samba/smb.conf.bak}"
: "${SAMBA_LOG_DIR:=/var/log/samba}"
: "${SAMBA_LOG_FILE:=${SAMBA_LOG_DIR}/samba.log}"
: "${SAMBA_TEMP_CONFIG:=/tmp/smb.conf.tmp}"
: "${EXIT_SUCCESS:=0}"
: "${EXIT_GENERAL_ERROR:=1}"
: "${EXIT_SAMBA_NOT_INSTALLED:=20}"
: "${EXIT_SAMBA_CONFIG_FAILED:=21}"
: "${EXIT_SAMBA_SHARE_FAILED:=22}"
: "${EXIT_SAMBA_TEST_FAILED:=23}"
: "${EXIT_SAMBA_UPDATE_FAILED:=24}"

# Проверка и запуск Samba-сервисов
start_samba_services() {
    for service in "${SAMBA_SERVICES[@]}"; do
        if ! systemctl is-active --quiet "$service"; then
            systemctl enable "$service" && systemctl start "$service" || {
                log_message "error" "Failed to start $service service"
                exit ${EXIT_SAMBA_NOT_INSTALLED}
            }
        fi
    done
}

# Настройка UFW для Samba-портов
configure_ufw() {
    if ! command -v ufw >/dev/null; then
        return
    fi
    if ufw status | grep -qw "active"; then
        for port in "${SAMBA_PORTS[@]}"; do
            if ! ufw status numbered | grep -E "${port}\s+ALLOW" >/dev/null; then
                ufw allow "$port" || {
                    log_message "error" "Failed to open port $port in UFW"
                    exit ${EXIT_GENERAL_ERROR}
                }
            fi
        done
    fi
}

# Создание резервной копии конфигурации Samba
backup_samba_config() {
    if [[ ! -f "$SAMBA_BACKUP_CONFIG" ]]; then
        cp "$SAMBA_CONFIG_FILE" "$SAMBA_BACKUP_CONFIG" || {
            log_message "error" "Failed to create backup of $SAMBA_CONFIG_FILE"
            exit ${EXIT_SAMBA_CONFIG_FAILED}
        }
    fi
}

# Обновление глобальной секции конфигурации Samba
update_global_config() {
    cp "$SAMBA_CONFIG_FILE" "$SAMBA_TEMP_CONFIG" || {
        log_message "error" "Failed to copy $SAMBA_CONFIG_FILE to temporary file"
        exit ${EXIT_SAMBA_CONFIG_FAILED}
    }

    if ! grep -q "\[global\]" "$SAMBA_TEMP_CONFIG"; then
        echo "[global]" >> "$SAMBA_TEMP_CONFIG"
    fi

    for param in "${SAMBA_GLOBAL_PARAMS[@]}"; do
        key=$(echo "$param" | cut -d'=' -f1 | xargs)
        if ! grep -q "^\s*$key\s*=" "$SAMBA_TEMP_CONFIG"; then
            sed -i "/\[global\]/a $param" "$SAMBA_TEMP_CONFIG" || {
                log_message "error" "Failed to add $param to [global] section"
                exit ${EXIT_SAMBA_CONFIG_FAILED}
            }
        fi
    done
}

# Добавление пользовательской шары Samba
add_user_share() {
    local user_share
    user_share=$(cat <<EOF
[%U]
   path = ${STUDENTS_DIR}/%U
   valid users = %U
   read only = no
   browsable = yes
   create mask = 0775
   force create mode = 0775
   directory mask = 2775
   force directory mode = 2775
   force user = %U
   force group = ${SITE_GROUP}
EOF
)
    if ! grep -q "\[%U\]" "$SAMBA_TEMP_CONFIG"; then
        echo -e "$user_share" >> "$SAMBA_TEMP_CONFIG" || {
            log_message "error" "Failed to add [%U] share to $SAMBA_TEMP_CONFIG"
            exit ${EXIT_SAMBA_SHARE_FAILED}
        }
    fi
}

# Проверка и применение конфигурации Samba
apply_samba_config() {
    if ! testparm -s "$SAMBA_TEMP_CONFIG" >/dev/null 2>&1; then
        log_message "error" "Invalid Samba configuration in $SAMBA_TEMP_CONFIG"
        exit ${EXIT_SAMBA_TEST_FAILED}
    fi

    if ! cmp -s "$SAMBA_TEMP_CONFIG" "$SAMBA_CONFIG_FILE"; then
        cp "$SAMBA_TEMP_CONFIG" "$SAMBA_CONFIG_FILE" || {
            log_message "error" "Failed to update $SAMBA_CONFIG_FILE"
            exit ${EXIT_SAMBA_UPDATE_FAILED}
        }
        update_permissions "$SAMBA_CONFIG_FILE" "644" "root:root" || {
            log_message "error" "Failed to set permissions or owner for '$SAMBA_CONFIG_FILE'"
            exit ${EXIT_SAMBA_UPDATE_FAILED}
        }
        smbcontrol smbd reload-config || {
            log_message "error" "Failed to reload Samba configuration"
            exit ${EXIT_SAMBA_UPDATE_FAILED}
        }
    fi
}

# Очистка временных файлов
cleanup() {
    rm -f "$SAMBA_TEMP_CONFIG"
}

# Основная логика
# Проверка зависимостей
check_deps "samba" "samba-common-bin" || {
    log_message "error" "Failed check dependencies"
    exit "${EXIT_SAMBA_NOT_INSTALLED}"
}

# Создание директорий
create_directories "$SAMBA_LOG_DIR" "$STUDENTS_DIR" 755 root:root || {
    log_message "error" "Failed create directories: ${SAMBA_LOG_DIR}, ${STUDENTS_DIR}"
    exit ${EXIT_GENERAL_ERROR}
}

# Запуск Samba-сервисов
start_samba_services

# Настройка UFW для Samba-портов
configure_ufw

# Создание резервной копии конфигурации
backup_samba_config

# Обновление глобальной секции конфигурации
update_global_config

# Добавление пользовательской шары
add_user_share

# Проверка и применение конфигурации
apply_samba_config

# Очистка
cleanup

log_message "info" "Samba configuration successfully checked and applied"

return ${EXIT_SUCCESS}