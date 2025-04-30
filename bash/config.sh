#!/bin/bash

# Главный файл конфигурации для скриптов
# Определяет константы, пути и коды выхода

# Проверка, что скрипт не вызывается напрямую
if [[ "${BASH_SOURCE[0]}" == "$0" ]]; then
    echo "[ERROR]: This script ('$0') is meant to be sourced, not executed directly" >&2
    exit 1
fi

# Константы
declare -r SITE_DIR="/var/www/demo"
declare -r SITE_USER="www-data"
declare -r SITE_GROUP="www-data"
declare -r STUDENTS_DIR="students"
declare -r STUDENTS_HOME="${SITE_DIR}/${STUDENTS_DIR}"
declare -r STUDENTS_GROUP="students"

# Переменныe
export LOG_DEBUG="[DEBUG]"
export LOG_INFO="[INFO]"
export LOG_WARNING="[WARNING]"
export LOG_ERROR="[ERROR]"
export LOGGING_SCRIPT="${SITE_DIR}/bash/lib/logging.sh"
export CHECK_ROOT_SCRIPT="${SITE_DIR}/bash/lib/check_root.sh"
export CHECK_DEPS_SCRIPT="${SITE_DIR}/bash/lib/check_deps.sh"
export CREATE_DIRS_SCRIPT="${SITE_DIR}/bash/lib/create_dirs.sh"
export CHECK_CMDS_SCRIPT="${SITE_DIR}/bash/lib/check_cmds.sh"

# Стандартные коды выхода
declare -r ERR_GENERAL=1
declare -r ERR_ROOT_REQUIRED=2
declare -r ERR_FILE_NOT_FOUND=3

#Функция подключения
source_script() {
    local path="$1"
    local args="${@:2}"

    if [[ ! -f "$path" ]]; then
        echo "$LOG_ERROR: Script '$path' not found" >&2
        return 1
    fi
    source "$path" "$args"
    if [[ $? -ne 0 ]]; then
        echo "$LOG_ERROR: Failed to source script '$path'" >&2
        return 1
    fi

    return 0
}

export -f source_script

# Успешное завершение
return 0