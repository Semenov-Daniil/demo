#!/bin/bash
# create_vhost.sh - Скрипт для создания и подключения виртуального хоста Apache2
# Расположение: bash/vhost/create_vhost.sh

set -euo pipefail

# Подключение логального config.sh
LOCAL_CONFIG="$(realpath $(dirname "${BASH_SOURCE[0]}")/config.sh)"
source "$LOCAL_CONFIG" || {
    echo "Failed to source local config '$LOCAL_CONFIG'" >&2
    exit 1
}

cleanup() {
    exit_code=$?
    [[ $exit_code -eq 0 || -z "${DOMAIN:-}" ]] && return 0
    bash "$REMOVE_VHOST" "$DOMAIN"
}

trap cleanup SIGINT SIGTERM EXIT

[[ ${#ARGS[@]} -ge 3 ]] || { echo "Usage: $0 <username> <domain> <directory>"; exit "$EXIT_INVALID_ARG"; }

VHOST_USER="${ARGS[0]}"
DOMAIN="${ARGS[1]}"
VHOST_DIR="${ARGS[2]}"

TEMPLATE_CONFIG="$(realpath $(dirname "${BASH_SOURCE[0]}")/vhost.conf)"
[[ ! -f "$TEMPLATE_CONFIG" ]] && {
    log_message "error" "Failed to find the virtual host template '$TEMPLATE_CONFIG'"
    exit "$EXIT_NOT_FOUND"
}

setup_vhost() {
    local user="$1" domain="$2" directory="$3"
    [[ -z "$user" ]] && { log_message "error" "No virtual host username provided"; return "$EXIT_INVALID_ARG"; }
    [[ -z "$domain" ]] && { log_message "error" "No virtual host domain provided"; return "$EXIT_INVALID_ARG"; }
    [[ -z "$directory" || ! -d "$directory" ]] && { log_message "error" "No virtual host directory '$directory' provided"; return "$EXIT_INVALID_ARG"; }

    [[ ! "$domain" =~ ^[a-zA-Z0-9._-]+$ ]] && { log_message "error" "Invalid virtual host domain '$domain'"; return "$EXIT_INVALID_ARG"; }

    local vhostfile="$VHOST_AVAILABLE/$domain.conf"

    [[ -f "$vhostfile" ]] && {
        log_message "warning" "Virtual host '$domain' exists"
        bash "$REMOVE_VHOST" "$domain" || return $?
    }

    log_message "info" "Starting to configure the virtual host '$domain'"

    create_vhost "$vhostfile" "$user" "$domain" "$directory" || return $?

    configtest "$domain" "$vhostfile" || return $?

    bash "$ENABLE_VHOST" "$domain" || return $?

    log_message "ok" "Virtual host '$domain' created successfully"
    return 0
}

create_vhost() {
    local vhostfile="$1" user="$2" domain="$3" directory="$4"
    [[ -z "$vhostfile" ]] && { log_message "error" "No virtual host filename provided"; return "$EXIT_INVALID_ARG"; }
    [[ -z "$user" ]] && { log_message "error" "No virtual host username provided"; return "$EXIT_INVALID_ARG"; }
    [[ -z "$domain" ]] && { log_message "error" "No virtual host domain provided"; return "$EXIT_INVALID_ARG"; }
    [[ -z "$directory" || ! -d "$directory" ]] && { log_message "error" "No virtual host directory provided"; return "$EXIT_INVALID_ARG"; }

    touch "$vhostfile" >/dev/null || {
        log_message "error" "Failed to create configuration file '$vhostfile'"
        return "$EXIT_VHOST_CONFIG_FAILED"
    }

    update_permissions "$vhostfile" 644 root:root || return $?

    declare -x DOMAIN="$domain"
    declare -x VHOST_DIR="$directory"
    declare -x VHOST_USER="$user"
    declare -x VHOST_GROUP=$(id -gn $user 2>/dev/null || echo "$SITE_GROUP")

    local template_content
    template_content=$(envsubst '${DOMAIN} ${VHOST_DIR} ${VHOST_USER} ${VHOST_GROUP}' < "$TEMPLATE_CONFIG") || {
        log_message "error" "Failed to process template '$TEMPLATE_CONFIG'"
        return "$EXIT_GENERAL_ERROR"
    }

    printf '%s\n' "$template_content" > "$vhostfile" || {
        log_message "error" "Failed to write to '$vhostfile'"
        return "$EXIT_VHOST_CONFIG_FAILED"
    }

    return 0
}

configtest() {
    local domain="$1" vhostfile="$2" 
    [[ -z "$domain" ]] && { log_message "error" "No virtual host domain provided"; return "$EXIT_INVALID_ARG"; }
    [[ -z "$vhostfile" || ! -f "$vhostfile" ]] && { log_message "error" "No virtual host filename provided"; return "$EXIT_INVALID_ARG"; }

    local tmpconfig=$(mktemp -u "$TMP_DIR/$domain.XXXXX.conf") || return $?
    touch "$tmpconfig" >/dev/null || {
        log_message "error" "Failed to create a configuration validation file '$tmpconfig'"
    }

    local config_main="/etc/apache2/apache2.conf"
    {
        echo "Include $config_main"
        echo "Include $vhostfile"
    } > "$tmpconfig" || {
        log_message "error" "Failed to write test configuration file '$tmpconfig'"
        rm -f "$tmpconfig" >/dev/null || true
        return "$EXIT_GENERAL_ERROR"
    }

    apache2ctl -t -f "$tmpconfig" 2>/dev/null || {
        log_message "error" "Invalid Apache2 configuration syntax in '$vhostfile'"
        rm -f "$tmpconfig" >/dev/null || true
        return "$EXIT_VHOST_INVALID_CONFIG"
    }

    rm -f "$tmpconfig" >/dev/null || true
    return 0
}

# Настройка вритуального хоста с блокировкой
with_lock "$TMP_DIR/${LOCK_VHOST_PREF}_${DOMAIN}.lock" setup_vhost "$VHOST_USER" "$DOMAIN" "$VHOST_DIR"
exit $?