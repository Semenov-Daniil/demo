#!/bin/bash

# disable_vhost.sh - Скрипт отключения виртуального хоста Apache2
# Расположение: bash/vhost/disable_vhost.sh

set -euo pipefail

# Подключение логального config.sh
source "$(dirname "${BASH_SOURCE[0]}")/config.sh" || {
    echo "Failed to source local config.sh"
    exit 1
}

# Отключение виртулального хоста
disable_vhost () {
    a2dissite "$VHOST_NAME" >/dev/null || {
        log_message "error" "Failed to disable virtual host '$VHOST_NAME'"
        exit ${EXIT_VHOST_DISABLE_FAILED}
    }
    touch "${RELOAD_NEEDED_FILE}" || {
        log_message "error" "Failed to create Apache reload flag"
        return ${EXIT_APACHE_SERVICE_FAILED}
    }
}

# Основная логика
# Проверка массива ARGS
[[ -n "${ARGS+x}" ]] || { echo "ARGS array is not defined"; exit ${EXIT_INVALID_ARG}; }

# Проверка аргументов
[[ ${#ARGS[@]} -ge 1 ]] || { echo "Usage: $0 <vhost-name>"; exit ${EXIT_INVALID_ARG}; }

# Установка переменных
VHOST_NAME="${ARGS[0]}"
VHOST_FILE="${VHOST_AVAILABLE_DIR}/${VHOST_NAME}.conf"
VHOST_ENABLED_FILE="${VHOST_ENABLED_DIR}/${VHOST_NAME}.conf"

# Проверка существования конфигурации
[[ -f "$VHOST_FILE" ]] || {
    log_message "warning" "Virtual host configuration '$VHOST_FILE' does not exist"
    exit ${EXIT_SUCCESS}
}

# Проверка активации виртуального хоста
[[ -f "$VHOST_ENABLED_FILE" ]] || {
    log_message "info" "Virtual host '$VHOST_NAME' is already disabled"
    exit ${EXIT_SUCCESS}
}

log_message "info" "Disabling virtual host '$VHOST_NAME'"

# Отключение виртуального хоста с блокировкой
with_lock "${TMP_DIR}/${LOCK_VHOST_PREF}_${VHOST_NAME}.lock" disable_vhost || exit $?

log_message "info" "Virtual host '$VHOST_NAME' disabled successfully"

exit ${EXIT_SUCCESS}