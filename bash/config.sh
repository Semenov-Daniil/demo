#!/bin/bash
# config.sh - Общий конфигурационный файл для bash-скриптов проекта
# Расположение: bash/config.sh

set -euo pipefail

declare -rx CNT_MAIN_CONFIG=1

# Code exits
declare -rx EXIT_GENERAL_ERROR=1
declare -rx EXIT_INVALID_ARG=2
declare -rx EXIT_NOT_FOUND=3

# Check sourced
[[ "${BASH_SOURCE[0]}" == "$0" ]] && {
    echo "This script ('$0') is meant to be sourced" >&2
    exit "$EXIT_GENERAL_ERROR"
}

# CHeck root
[[ "$EUID" -ne 0 ]] && {
    echo "This operation requires root privileges" >&2
    return "$EXIT_GENERAL_ERROR"
}

# Parsing arguments
declare -ax ARGS=()

while [[ $# -gt 0 ]]; do
    case "$1" in
        --log=*) LOG_FILE="${1#--log=}"; shift ;;
        *) ARGS+=("$1"); shift ;;
    esac
done

# Path project
declare -rx SCRIPT_PATH="$(realpath "${BASH_SOURCE[0]}")"
declare -rx SCRIPTS_DIR="$(dirname "$SCRIPT_PATH")"
declare -rx PROJECT_ROOT="$(dirname "$SCRIPTS_DIR")"

# Parsing .env
declare -rx ENV="$PROJECT_ROOT/.env"

load_env() {
    local env_file="${1:-.env}"

    [[ ! -f "$env_file" ]] && { echo "File '$env_file' not found"; return "$EXIT_NOT_FOUND"; }

    while IFS='=' read -r key value; do
        [[ -z "$key" || "$key" =~ ^[[:space:]]*# ]] && continue

        key=$(echo "$key" | tr -d '[:space:]')
        [[ ! "$key" =~ ^[a-zA-Z_][a-zA-Z0-9_]*$ ]] && { echo "Incorrect format of the key '$key' in '$env_file'"; continue; }

        value=$(echo "$value" | sed -e 's/^[[:space:]]*//' -e 's/[[:space:]]*$//')

        declare -x "$key=$value"
    done < "$env_file"

    return 0
}

load_env "$ENV" || return $?

# Directories
declare -rx LOGS_DIR="${SCRIPTS_DIR}/logs"
declare -rx LIB_DIR="${SCRIPTS_DIR}/lib"
declare -rx TMP_DIR="${SCRIPTS_DIR}/tmp"
declare -rx ETC_DIR="/etc"

# Site variables
declare -rx SITE_USER="${SITE_USER:-"www-data"}"
declare -rx SITE_GROUP="${SITE_GROUP:-"www-data"}"

# Students
declare -rx STUDENT_GROUP="students"
declare -rx STUDENTS_DIR="${PROJECT_ROOT}/students"
declare -rx ETC_STUDENTS="${ETC_DIR}/students"

# Scripts
declare -rx LOGGING_SCRIPT="${SCRIPTS_DIR}/logging/logging.fn.sh"
declare -rx COMMON_SCRIPT="$LIB_DIR/common.sh"

# Services
declare -rax REQUIRED_SERVICES=("apache2" "openssh-server" "samba" "samba-common-bin" "redis-server")
declare -rAx REQUIRED_SERVICE_MAP=(
    ["apache2"]="apache2"
    ["openssh-server"]="ssh"
    ["samba"]="smbd nmbd"
    ["samba-common-bin"]=""
    ["redis-server"]="redis-server"
)

# Lock
declare -rx LOCK_TIMEOUT=15
declare -rx LOCK_PREF="lock"

# Chroot
declare -rx BASE_CHROOT="/srv/chroot"
declare -rx CHROOT_ROOT="$BASE_CHROOT/root"
declare -rx HOME_USERS="/home"
declare -rx CHROOT_HOME="${CHROOT_ROOT}${HOME_USERS}"
declare -rx WORKSPACE_USERS="/workspace"

chroot_workspace() {
    local username="$1"
    echo "$CHROOT_HOME/${username}${WORKSPACE_USERS}"
}

export -f chroot_workspace

# Logging
[[ -z "${LOG_FILE:-}" ]] && { declare -x DEFAULT_LOG_FILE="logs.log"; }
declare -x LOG_FILE="${LOG_FILE:-$DEFAULT_LOG_FILE}"

# Source advanced scripts
source "$COMMON_SCRIPT" || {
    echo "Failed to source script '$LIB_DIR/common.sh'"
    return "$EXIT_GENERAL_ERROR"
}

# Source logging
source "$LOGGING_SCRIPT" || {
    echo "Failed to source script '$LOGGING_SCRIPT'"
    return "$EXIT_GENERAL_ERROR"
}

# Main
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

groups "$SITE_USER" | grep -q "$STUDENT_GROUP" || {
    usermod -aG "$STUDENT_GROUP" "$SITE_USER" >/dev/null
}

[[ ! -d "$STUDENTS_DIR" ]] && { create_directories "$STUDENTS_DIR" 755 "$SITE_USER:$SITE_GROUP" || return $?; }

[[ ! -d "$TMP_DIR" ]] && { create_directories "$TMP_DIR" 1777 "$SITE_USER:$SITE_GROUP" || return $?; }

[[ ! -d "$ETC_STUDENTS" ]] && { create_directories "$ETC_STUDENTS" 755 "root:root" || return $?; }

return 0