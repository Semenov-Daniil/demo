#!/bin/bash
# config.sh - Общий конфигурационный файл для bash-скриптов проекта
# Расположение: bash/config.sh

set -euo pipefail

# Коды выхода
declare -rx EXIT_SUCCESS=0
declare -rx EXIT_GENERAL_ERROR=1
declare -rx EXIT_INVALID_ARG=2
declare -rx EXIT_NOT_FOUND=3

# Проверкa подключения скрипта
[[ "${BASH_SOURCE[0]}" == "$0" ]] && {
    echo "This script ('$0') is meant to be sourced" >&2
    exit ${EXIT_GENERAL_ERROR}
}

# Проверкa root-прав
[[ "$EUID" -ne 0 ]] && {
    echo "This operation requires root privileges" >&2
    return ${EXIT_GENERAL_ERROR}
}

# Парсинг аргументов
declare -ax ARGS=()

while [[ $# -gt 0 ]]; do
    case "$1" in
        --log=*) LOG_FILE="${1#--log=}"; shift ;;
        *) ARGS+=("$1"); shift ;;
    esac
done

# Путь к проекту
declare -rx SCRIPT_PATH="$(realpath "${BASH_SOURCE[0]}")"
declare -rx SCRIPTS_DIR="$(dirname "$SCRIPT_PATH")"
declare -rx PROJECT_ROOT="$(dirname "$SCRIPTS_DIR")"

# Переменные из .env
declare -rx ENV="${PROJECT_ROOT}/.env"

# Получение переменных из .env
if [[ -f "${ENV}" ]]; then
    (
        set -a
        if ! source "${ENV}"; then
            echo "Failed to source '$ENV'" >&2
            exit 1
        fi
        set +a

        while read -r var; do
            if [[ ! "$var" =~ ^[a-zA-Z_][a-zA-Z0-9_]*$ ]]; then
                echo "Invalid variable name '$var' in '$ENV', unsetting" >&2
                unset "$var"
            fi
        done < <(compgen -A variable)
    ) || {
        echo "Failed to process '$ENV'" >&2
        return 1
    }
else
    echo "File '$ENV' not found"
    return ${EXIT_NOT_FOUND}
fi

# Установка переменных
declare -rx LOGS_DIR="${SCRIPTS_DIR}/logs"
declare -rx LIB_DIR="${SCRIPTS_DIR}/lib"
declare -rx TMP_DIR="${SCRIPTS_DIR}/tmp"
declare -x DEFAULT_LOG_FILE="logs.log"
declare -x LOG_FILE="${LOG_FILE:-"$DEFAULT_LOG_FILE"}"
declare -rx LOCK_PREF="lock"
declare -rx SITE_USER="${SITE_USER:-"www-data"}"
declare -rx SITE_GROUP="${SITE_GROUP:-"www-data"}"
declare -rx STUDENT_GROUP="students"
declare -rx STUDENTS_DIR="${PROJECT_ROOT}/students"
declare -rx LOGGING_SCRIPT="${SCRIPTS_DIR}/logging/logging.fn.sh"
declare -rax REQUIRED_SERVICES=("apache2" "openssh-server" "samba" "samba-common-bin")
declare -rAx REQUIRED_SERVICE_MAP=(
    ["apache2"]="apache2"
    ["openssh-server"]="ssh"
    ["samba"]="smbd nmbd"
    ["samba-common-bin"]=""
)
declare -x LOG_RETENTION_DAYS=30
declare -x LOCK_LOG_PREF="lock_log"
declare -rax LOG_LEVELS=("info" "warning" "error")

# Функция подключения скриптов
source_script() {
    if [ -z "$1" ]; then
        echo "Usage source_script: <script-path> [<args> ...]"
        return "$EXIT_INVALID_ARG"
    fi

    local script_path="$1"
    shift

    [[ -f "$script_path" ]] || {
        echo "Script '$script_path' not found"
        return "$EXIT_NOT_FOUND"
    }

    source "$script_path" $@ || {
        echo "Failed to source script '$script_path'"
        return "$EXIT_GENERAL_ERROR"
    }

    return "$EXIT_SUCCESS"
}

# Подключение вспомогательных скриптов/функций
source_script "${LIB_DIR}/common.sh" || return $?

# Подключение логирования
source_script "$LOGGING_SCRIPT" || return $?

id "$SITE_USER" >/dev/null || {
    log_message "error" "User '$SITE_USER' does not exist"
    return ${EXIT_GENERAL_ERROR}
}

getent group "$SITE_GROUP" >/dev/null || {
    log_message "error" "Group '$SITE_GROUP' does not exist"
    return ${EXIT_GENERAL_ERROR}
}

getent group "$STUDENT_GROUP" >/dev/null || {
    log_message "error" "Group '$STUDENT_GROUP' does not exist"
    return ${EXIT_GENERAL_ERROR}
}

[[ -d "$STUDENTS_DIR" ]] || {
    mkdir -p "$STUDENTS_DIR" || {
        log_message "error" "Cannot create directory '$STUDENTS_DIR'"
        return ${EXIT_GENERAL_ERROR} 
    }
    chown "$SITE_USER:$SITE_GROUP" "$STUDENTS_DIR" || {
        log_message "error" "Missing set ownership directory ${STUDENTS_DIR}"
        return ${EXIT_GENERAL_ERROR} 
    }
    chmod 755 "$STUDENTS_DIR" || {
        log_message "error" "Missing set permissions directory ${STUDENTS_DIR}"
        return ${EXIT_GENERAL_ERROR} 
    }
}

[[ -d "$TMP_DIR" ]] || {
    mkdir -p "$TMP_DIR" || {
        log_message "error" "Cannot create directory '$TMP_DIR'"
        return ${EXIT_GENERAL_ERROR} 
    }
    chown "root:root" "$TMP_DIR" || {
        log_message "error" "Missing set ownership directory ${TMP_DIR}"
        return ${EXIT_GENERAL_ERROR} 
    }
    chmod 1777 "$TMP_DIR" || {
        log_message "error" "Missing set permissions directory ${TMP_DIR}"
        return ${EXIT_GENERAL_ERROR} 
    }
}

export -f source_script

return ${EXIT_SUCCESS}