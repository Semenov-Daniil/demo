#!/bin/bash
# remove_vhost.sh - Скрипт удаления виртуального хоста Apache2
# Расположение: bash/vhost/remove_vhost.sh

set -euo pipefail

# Подключение логального config.sh
LOCAL_CONFIG="$(realpath $(dirname "${BASH_SOURCE[0]}")/config.sh)"
source "$LOCAL_CONFIG" || {
    echo "Failed to source local config '$LOCAL_CONFIG'" >&2
    exit 1
}

# Проверка аргументов
[[ ${#ARGS[@]} -ge 1 ]] || { echo "Usage: $0 <domain>"; exit "$EXIT_INVALID_ARG"; }

DOMAIN="${ARGS[0]}"

remove_vhost() {
    local domain="$1"
    [[ -z "$domain" ]] && { log_message "error" "No virtual host domain provided"; return "$EXIT_INVALID_ARG"; }

    [[ ! "$domain" =~ ^[a-zA-Z0-9._-]+$ ]] && { log_message "error" "Invalid virtual host domain '$domain'"; return "$EXIT_INVALID_ARG"; }

    local vhostfile="$VHOST_AVAILABLE/$domain.conf"
    local vhost_enabled="$VHOST_ENABLED/$domain.conf"

    log_message "info" "Starting to remove the virtual host '$domain'"

    [[ ! -f "$vhostfile" ]] && {
        log_message "warning" "Failed to find the virtual host '$domain'"
        return 0
    }

    bash "$DISABLE_VHOST" "$domain" || return $?

    local tmpfile=$(mktemp -u "$TMP_DIR/$domain.XXXXX.conf.del") || return $?
    mv "$vhostfile" "$tmpfile" && rm -f "$tmpfile" || {
        log_message "error" "Failed to delete virtual host configuration file '$tmpfile'"
        exit 1
    }

    log_message "ok" "Virtual host '$domain' was successfully deleted"
    return 0
}

# Удаление виртуального хоста с блокировкой
with_lock "$TMP_DIR/${LOCK_VHOST_PREF}_${DOMAIN}.lock" remove_vhost "$DOMAIN"
exit $?