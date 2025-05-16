#!/bin/bash
# config.sh - Общий конфигурационный файл для bash-скриптов проекта
# Расположение: bash/config.sh

set -euo pipefail

# Коды выхода
declare -rx EXIT_SUCCESS=0
declare -rx EXIT_GENERAL_ERROR=1
declare -rx EXIT_INVALID_ARG=2
declare -rx EXIT_NOT_FOUND=3

# Путь к проекту
declare -rx PROJECT_ROOT="$(dirname "$(dirname "$(realpath "${BASH_SOURCE[0]}")")")"

# Переменные из .env
declare -rx ENV="${PROJECT_ROOT}/.env"

# Чтение переменных из .env
if [[ -f "${ENV}" ]]; then
    while IFS='=' read -r line || [[ -n "$line" ]]; do
        [[ -z "$line" || "$line" =~ ^[[:space:]]*# ]] && continue

        key=${line%%=*}
        key=$(echo "$key" | sed -e 's/^[[:space:]]*//' -e 's/[[:space:]]*$//' -e "s/^['\"]//" -e "s/['\"]$//")

        value=${line#*=}
        value=$(echo "$value" | sed -e 's/^[[:space:]]*//' -e 's/[[:space:]]*$//' -e "s/^['\"]//" -e "s/['\"]$//")

        [[ -z "$key" ]] && continue
        [[ "$key" =~ ^[a-zA-Z_][a-zA-Z0-9_]*$ ]] || continue

        declare "$key=$value"
    done < "${ENV}"
else
    echo "File '$ENV' not found"
fi

# Установка переменных
declare -rx SCRIPTS_DIR="${PROJECT_ROOT}/bash"
declare -rx LOGS_DIR="${SCRIPTS_DIR}/logs"
declare -rx LIB_DIR="${SCRIPTS_DIR}/lib"
declare -rx TMP_DIR="/tmp"
declare -rx LOCK_PREF="lock"
declare -rx SITE_USER="${SITE_USER:-"www-data"}"
declare -rx SITE_GROUP="${SITE_GROUP:-"www-data"}"
declare -rx STUDENT_GROUP="students"
declare -rx STUDENTS_DIR="${PROJECT_ROOT}/students"
declare -rx LOGGING_SCRIPT="${LOGGING_DIR}/logging/logging.fn.sh"
declare -rax REQUIRED_SERVICES=("apache2" "openssh-server" "samba" "samba-common-bin")
declare -rax REQUIRED_SERVICE_MAP=(
    ["apache2"]="apache2"
    ["openssh-server"]="ssh"
    ["samba"]="smbd nmbd"
    ["samba-common-bin"]=""
)

# Функция подключения скриптов\
source_script() {
    if [ -z "$1" ]; then
        echo "Usage source_script: <script-path> [<args> ...]"
        return ${EXIT_INVALID_ARG}
    fi

    local script_path="$1"
    shift

    [[ -f "$script_path" ]] || {
        echo "Script '$script_path' not found"
        return ${EXIT_NOT_FOUND}
    }

    [[ -x "$script_path" ]] || {
        echo "Script '$script_path' is not executable"
        return ${EXIT_GENERAL_ERROR}
    }

    source "$script_path" "$@" || {
        echo "Failed to source script '$script_path'"
        return ${EXIT_GENERAL_ERROR}
    }

    return ${EXIT_SUCCESS}
}

# Основная часть
[[ $EUID -ne 0 ]] || {
    echo "This operation requires root privileges"
    exit ${EXIT_GENERAL_ERROR}
}

id "$SITE_USER" >/dev/null || {
    echo "User '$SITE_USER' does not exist"
    exit ${EXIT_GENERAL_ERROR}
}

getent group "$SITE_GROUP" >/dev/null || {
    echo "Group '$SITE_GROUP' does not exist"
    exit ${EXIT_GENERAL_ERROR}
}

getent group "$STUDENT_GROUP" >/dev/null || {
    groupadd "$STUDENT_GROUP" || true
}

[[ ! -d "$STUDENTS_DIR" ]] || {
    mkdir -p "$STUDENTS_DIR" || true
    chown "$SITE_USER:$SITE_GROUP" "$STUDENTS_DIR" || true
    chmod 755 "$STUDENTS_DIR" || true
}

export -f source_script

return ${EXIT_SUCCESS}