#!/bin/bash

set -euo pipefail

# config.sh - Общий конфигурационный файл для bash-скриптов проекта
# Расположение: bash/config.sh

# Проверка, что скрипт не запущен напрямую
if [[ "${BASH_SOURCE[0]}" == "$0" ]]; then
    echo "This script ('$0') is meant to be sourced, not executed directly" >&2
    exit 1
fi

# Путь к проекту
export PROJECT_ROOT="$(dirname "$(dirname "$(realpath "${BASH_SOURCE[0]}")")")"

# --- Переменные из .env ---
ENV="${PROJECT_ROOT}/.env"

# Чтение переменных из .env
if [[ -f "${ENV}" ]]; then
    while IFS='=' read -r key value || [[ -n "$key" ]]; do
        [[ -z "$key" || "$key" =~ ^# ]] && continue
        key=$(echo "$key" | tr -d '[:space:]' | sed "s/^['\"]//; s/['\"]$//")
        value=$(echo "$value" | tr -d '[:space:]' | sed "s/^['\"]//; s/['\"]$//")
        export "$key=$value"
    done < "${ENV}"
else
    echo "File '$ENV' not found" >&2
    return 1
fi

# --- Основные переменные ---
# Путь к папке со скриптами
export SCRIPTS_DIR="${PROJECT_ROOT}/bash"
# Путь к папке с логами
export LOGS_DIR="${SCRIPTS_DIR}/logs"
# Путь к папке lib
export LIB_DIR="${SCRIPTS_DIR}/lib"
# Путь к папке utils
export UTILS_DIR="${SCRIPTS_DIR}/utils"

# Пользователь и группа сайта
export SITE_USER="${SITE_USER:-"www-data"}"
export SITE_GROUP="${SITE_GROUP:-"www-data"}"

# Названия групп и директорий студентов
export STUDENT_GROUP="students"
export STUDENT_DIR="${PROJECT_ROOT}/students"

# Зависимости для утилит
export REQUIRED_SERVICES=("openssh-server" "samba")

# --- Коды выхода ---
export EXIT_SUCCESS=0        # Успешное выполнение
export EXIT_GENERAL_ERROR=1  # Общая ошибка
export EXIT_NO_ROOT=2        # Нет прав root
export EXIT_NO_DEPENDENCY=3  # Отсутствует зависимость
export EXIT_NO_COMMAND=4     # Отсутствует команда
export EXIT_INVALID_ARG=5    # Неверный аргумент

# --- Функция для подключения скриптов ---
source_script() {
    local script_path="$1"
    local script_args="${2:-}"

    if [[ ! -f "$script_path" ]]; then
        echo "Script '$script_path' not found" >&2
        return ${EXIT_GENERAL_ERROR}
    fi

    source "$script_path" $script_args || {
        echo "Failed to source script '$script_path'" >&2
        return ${EXIT_GENERAL_ERROR}
    }

    return ${EXIT_SUCCESS}
}

# Пути к скриптам
export LOGGING_SCRIPT="${LIB_DIR}/logging.sh"
export CHECK_DEPS_SCRIPT="${LIB_DIR}/check_deps.sh"
export CHECK_CMDS_SCRIPT="${LIB_DIR}/check_cmds.sh"
export CREATE_DIRS_SCRIPT="${LIB_DIR}/create_dirs.sh"

export -f source_script

# Проверка наличия группы
if ! getent group "$STUDENT_GROUP" >/dev/null; then
    groupadd "$STUDENT_GROUP" || {
        echo "Failed to create a group '$STUDENT_GROUP'"
        exit ${EXIT_GENERAL_ERROR}
    }
fi

# Успешное завершение
return ${EXIT_SUCCESS}