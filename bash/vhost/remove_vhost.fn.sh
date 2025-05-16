#!/bin/bash

# remove_vhost.fn.sh - Скрипт экспортирующий функцию удаления виртуального хоста Apache2
# Расположение: bash/vhost/remove_vhost.fn.sh

set -euo pipefail

# Проверка, что скрипт не запущен напрямую
[[ "${BASH_SOURCE[0]}" == "$0" ]] && { 
    echo "This script ('$0') is meant to be sourced"
    exit 1
}

# Функция удаления виртуального хоста
# remove_vhost <virtual-host-name>
remove_vhost() {
    local vhost_name="$1"
    local vhost_file="${VHOST_AVAILABLE_DIR}/${vhost_name}.conf"
    local vhost_enabled_file="${VHOST_ENABLED_DIR}/${vhost_name}.conf"
    local backup_file="${TMP_DIR}/vhost_${vhost_name}_backup.conf"

    [[ "$vhost_name" =~ ^[a-zA-Z0-9._-]+$ ]] || {
        log_message "error" "Invalid virtual host name: '$vhost_name'"
        exit 1
    }

    cp "$vhost_name" "$backup_file" 2>/dev/null || {
        log_message "warning" "Failed to backup '$vhost_name'"
    }

    [[ -f "$vhost_enabled_file" ]] && {
        a2dissite "$vhost_name" >/dev/null || {
            log_message "error" "Failed to disable virtual host '$vhost_name'"
            mv "$backup_file" "$vhost_file" 2>/dev/null || true
            exit 1
        }
    }

    rm -f "$vhost_file" || {
        log_message "error" "Failed to delete configuration file '$vhost_file'"
        mv "$backup_file" "$vhost_file" 2>/dev/null || true
        a2ensite "$vhost_name" >/dev/null || true
        exit 1
    }

    touch "${RELOAD_NEEDED_FILE}" || {
        log_message "error" "Failed to create Apache reload flag"
        return ${EXIT_APACHE_SERVICE_FAILED}
    }

    rm -f "$backup_file" 2>/dev/null

    return 0
}

export -f remove_vhost
return 0