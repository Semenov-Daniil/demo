#!/bin/bash
# create_vhost.sh - Скрипт для создания и подключения виртуального хоста Apache2
# Расположение: bash/vhost/create_vhost.sh

set -euo pipefail

# Подключение логального config.sh
LOCAL_CONFIG="$(dirname "${BASH_SOURCE[0]}")/config.sh"
source "$LOCAL_CONFIG" || {
    echo "Failed to source script $LOCAL_CONFIG" >&2
    exit 1
}

# Подключение скрипта удаления виртуального хоста
source "$REMOVE_VHOST_SCRIPT" || {
    echo "Failed to source script $REMOVE_VHOST_SCRIPT" >&2
    exit ${EXIT_GENERAL_ERROR}
}

# Очистка при ошибке
cleanup() {
    exit_code=$?
    
    if [[ $exit_code -eq 0 ]] || [[ $exit_code -eq $EXIT_INVALID_ARG ]]; then
        return
    fi

    remove_vhost "${VHOST_NAME}" || {
        log_message "error" "Failed to rollback virtual host $VHOST_NAME"
    }
}

# Основная логика
trap cleanup SIGINT SIGTERM EXIT

# Проверка массива ARGS
if ! declare -p ARGS >/dev/null 2>&1; then
    echo "ARGS array is not defined" >&2
    exit ${EXIT_INVALID_ARG}
fi

# Проверка аргументов
if [[ ${#ARGS[@]} -lt 2 ]]; then
    echo "Usage: $0 <vhost-name> <config-content>" >&2
    exit ${EXIT_INVALID_ARG}
fi

VHOST_NAME="${ARGS[0]}"
VHOST_CONFIG="${ARGS[1]}"

# Валидация имени виртуального хоста
if [[ ! "$VHOST_NAME" =~ ^[a-zA-Z0-9._-]+$ ]]; then
    log_message "error" "Invalid virtual host name: $VHOST_NAME"
    exit ${EXIT_INVALID_ARG}
fi

VHOST_FILE="${VHOST_AVAILABLE_DIR}/${VHOST_NAME}.conf"

# Проверка, не существует ли файл конфигурации
if [[ -f "$VHOST_FILE" ]]; then
    log_message "warning" "Virtual host configuration '$VHOST_FILE' already exists"
    remove_vhost "${VHOST_NAME}" || {
        log_message "error" "Failed to rollback virtual host $VHOST_NAME"
    }
fi

log_message "info" "Starting virtual host creation for $VHOST_NAME"

# Создание директорий
create_directories "$VHOST_LOG_DIR" "755" "root:root" || {
    log_message "error" "Failed to create directory: ${VHOST_LOG_DIR}"
    exit ${EXIT_GENERAL_ERROR}
}

# Создание файла конфигурации
echo "$VHOST_CONFIG" > "$VHOST_FILE" || {
    log_message "error" "Failed to create configuration file $VHOST_FILE"
    exit ${EXIT_VHOST_CONFIG_FAILED}
}

# Установка прав и владельца
update_permissions "$VHOST_FILE" "$VHOST_PERMS" "$VHOST_OWNER" || {
    log_message "error" "Failed to set permissions or owner for '$VHOST_FILE'"
    exit ${EXIT_VHOST_CONFIG_FAILED}
}

# Проверка синтаксиса конфи alcalde
if ! apache2ctl configtest >/dev/null 2>>"$LOG_FILE"; then
    log_message "error" "Invalid Apache2 configuration syntax in $VHOST_FILE"
    rm -f "$VHOST_FILE"
    exit ${EXIT_VHOST_INVALID_CONFIG}
fi

# Активация виртуального хоста
a2ensite "$VHOST_NAME" >/dev/null 2>>"$LOG_FILE" || {
    log_message "error" "Failed to enable virtual host $VHOST_NAME"
    rm -f "$VHOST_FILE"
    exit ${EXIT_GENERAL_ERROR}
}

# Перезапуска Apache
systemctl reload apache2 >/dev/null 2>>"$LOG_FILE" || {
    log_message "error" "Failed to reload Apache2 service"
    exit ${EXIT_VHOST_DISABLE_FAILED}
}

log_message "info" "Virtual host $VHOST_NAME created and enabled successfully"

exit ${EXIT_SUCCESS}
