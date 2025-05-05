#!/bin/bash
# disable_vhost.sh - Скрипт отключения виртуального хоста Apache2
# Расположение: bash/vhost/disable_vhost.sh

set -euo pipefail

# Подключение локального config.sh
LOCAL_CONFIG="$(dirname "${BASH_SOURCE[0]}")/config.sh"
source "$LOCAL_CONFIG" || {
    echo "Failed to source script $LOCAL_CONFIG" >&2
    exit 1
}

# Основная логика
# Проверка массива ARGS
if ! declare -p ARGS >/dev/null 2>&1; then
    echo "ARGS array is not defined" >&2
    exit ${EXIT_INVALID_ARG}
fi

# Проверка аргументов
if [[ ${#ARGS[@]} -lt 1 ]]; then
    echo "Usage: $0 <vhost-name>" >&2
    exit ${EXIT_INVALID_ARG}
fi

VHOST_NAME="${ARGS[0]}"

VHOST_FILE="${VHOST_AVAILABLE_DIR}/${VHOST_NAME}.conf"
VHOST_ENABLED_FILE="${VHOST_ENABLED_DIR}/${VHOST_NAME}.conf"

# Проверка, существует ли файл конфигурации
if [[ ! -f "$VHOST_FILE" ]]; then
    log_message "info" "Virtual host configuration '$VHOST_FILE' does not exist"
    exit ${EXIT_SUCCESS}
fi

# Проверка, активирован ли виртуальный хост
if [[ ! -f "$VHOST_ENABLED_FILE" ]]; then
    log_message "info" "Virtual host '$VHOST_NAME' is already disabled"
    exit ${EXIT_SUCCESS}
fi

log_message "info" "Disabling virtual host $VHOST_NAME"

# Отключение виртуального хоста
a2dissite "$VHOST_NAME" >/dev/null 2>>"$LOG_FILE" || {
    log_message "error" "Failed to disable virtual host $VHOST_NAME"
    exit ${EXIT_VHOST_DISABLE_FAILED}
}

# Перезагрузка Apache2
systemctl reload "apache2" >/dev/null 2>>"$LOG_FILE" || {
    log_message "error" "Failed to reload Apache2 service"
    exit ${EXIT_VHOST_DISABLE_FAILED}
}

log_message "info" "Virtual host $VHOST_NAME disabled successfully"

exit ${EXIT_SUCCESS}