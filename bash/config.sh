#!/bin/bash
# config.sh - Общий конфигурационный файл для bash-скриптов проекта
# Расположение: bash/config.sh

set -euo pipefail

# Проверка, что скрипт не запущен напрямую
if [[ "${BASH_SOURCE[0]}" == "$0" ]]; then
    echo "This script ('$0') is meant to be sourced, not executed directly" >&2
    exit 1
fi

# Коды выхода
export EXIT_SUCCESS=0
export EXIT_GENERAL_ERROR=1
export EXIT_NO_ROOT=2
export EXIT_NO_DEPENDENCY=3
export EXIT_NO_COMMAND=4 
export EXIT_INVALID_ARG=5
export EXIT_NOT_FOUND=6
export EXIT_USER_NOT_FOUND=7
export EXIT_GROUP_NOT_FOUND=8

# Путь к проекту
export PROJECT_ROOT="$(dirname "$(dirname "$(realpath "${BASH_SOURCE[0]}")")")"

# Переменные из .env
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

# Основные переменные
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

if ! id "$SITE_USER" >/dev/null; then
    echo "User '$SITE_USER' does not exist"
    exit ${EXIT_USER_NOT_FOUND}
fi

if ! getent group "$SITE_GROUP" >/dev/null; then
    echo "Group '$SITE_GROUP' does not exist"
    exit ${EXIT_GROUP_NOT_FOUND}
fi

# Названия групп и директорий студентов
export STUDENT_GROUP="students"
export STUDENTS_DIR="${PROJECT_ROOT}/students"

# Проверка наличия группы
if ! getent group "$STUDENT_GROUP" >/dev/null; then
    groupadd "$STUDENT_GROUP" || {
        echo "Failed to create a group '$STUDENT_GROUP'"
        exit ${EXIT_GENERAL_ERROR}
    }
fi

if [[ ! -d "$STUDENTS_DIR" ]]; then
    echo "Directory '$STUDENTS_DIR' not found"
    exit ${EXIT_NOT_FOUND}
fi

# Зависимости для утилит
export REQUIRED_SERVICES=("apache2" "openssh-server" "samba" "samba-common-bin")

# Функция для подключения скриптов
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

export -f source_script

# Пути к скриптам
export LOGGING_SCRIPT="${LIB_DIR}/logging.sh"
export CHECK_DEPS_SCRIPT="${LIB_DIR}/check_deps.sh"
export CHECK_CMDS_SCRIPT="${LIB_DIR}/check_cmds.sh"
export CREATE_DIRS_SCRIPT="${LIB_DIR}/create_dirs.sh"
export UPDATE_PERMS_SCRIPT="${LIB_DIR}/update_perms.sh"

# Успешное завершение
return ${EXIT_SUCCESS}