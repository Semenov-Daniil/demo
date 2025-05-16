#!/bin/bash
# create_vhost.sh - Скрипт для создания и подключения виртуального хоста Apache2
# Расположение: bash/vhost/create_vhost.sh

set -euo pipefail

# Подключение логального config.sh
source "$(dirname "${BASH_SOURCE[0]}")/config.sh" || {
    echo "Failed to source local config.sh"
    exit 1
}

# Подключение скрипта удаления виртуального хоста
source "$REMOVE_VHOST" || {
    echo "Failed to source script '$REMOVE_VHOST'"
    exit ${EXIT_GENERAL_ERROR}
}

# Очистка при ошибке
cleanup() {
    exit_code=$?
    [[ $exit_code -eq 0 || $exit_code -eq $EXIT_INVALID_ARG ]] && return

    with_lock "${TMP_DIR}/${LOCK_VHOST_PREF}_${VHOST_NAME}.lock" remove_vhost "$VHOST_NAME" || {
        log_message "error" "Failed to rollback virtual host $VHOST_NAME"
    }
}

create_vhost () {
    # Создание и настройка файла конфигурации
    printf "%s" "$VHOST_CONFIG" > "$VHOST_FILE" || {
        log_message "error" "Failed to create or setting configuration file '$VHOST_FILE'"
        exit ${EXIT_VHOST_CONFIG_FAILED}
    }

    # Установка прав и владельца
    update_permissions "$VHOST_FILE" 644 root:root || {
        log_message "error" "Failed to set permissions or owner for '$VHOST_FILE'"
        exit ${EXIT_VHOST_CONFIG_FAILED}
    }

    # Проверка синтаксиса конфигураций виртуальных хостов
    apache2ctl configtest >/dev/null || {
        log_message "error" "Invalid Apache2 configuration syntax in '$VHOST_FILE'"
        exit ${EXIT_VHOST_INVALID_CONFIG}
    }

    # Активация виртуального хоста
    a2ensite "$VHOST_NAME" >/dev/null || {
        log_message "error" "Failed to enable virtual host '$VHOST_NAME'"
        exit ${EXIT_GENERAL_ERROR}
    }

    touch "${RELOAD_NEEDED_FILE}" || {
        log_message "error" "Failed to create Apache reload flag"
        return ${EXIT_APACHE_SERVICE_FAILED}
    }

    return ${EXIT_SUCCESS}
}

# Основная логика
trap cleanup SIGINT SIGTERM EXIT

# Проверка массива ARGS
[[ -n "${ARGS+x}" ]] || { echo "ARGS array is not defined"; exit ${EXIT_INVALID_ARG}; }

# Проверка аргументов
[[ ${#ARGS[@]} -ge 2 ]] || { echo "Usage: $0 <vhost-name> <vhost-config>"; exit ${EXIT_INVALID_ARG}; }

# Установка переменных
VHOST_NAME="${ARGS[0]}"
VHOST_CONFIG="${ARGS[1]}"

# Валидация имени виртуального хоста
if [[ ! "$VHOST_NAME" =~ ^[a-zA-Z0-9._-]+$ ]]; then
    log_message "error" "Invalid virtual host name $VHOST_NAME"
    exit ${EXIT_INVALID_ARG}
fi

VHOST_FILE="${VHOST_AVAILABLE_DIR}/${VHOST_NAME}.conf"

# Проверка существующего виртуального хоста
[[ -f "$VHOST_FILE" ]] && {
    log_message "warning" "Virtual host '$VHOST_FILE' exists"
}

log_message "info" "Starting virtual host creation for '$VHOST_NAME'"

# Создание вритуального хоста с блокировкой
with_lock "${TMP_DIR}/${LOCK_VHOST_PREF}_${VHOST_NAME}.lock" create_vhost || exit $?

log_message "info" "Virtual host $VHOST_NAME created and enabled successfully"

exit ${EXIT_SUCCESS}
