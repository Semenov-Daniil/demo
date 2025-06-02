#!/bin/bash
# enable_vhost.sh - Скрипт включения виртуального хоста Apache2
# Расположение: bash/vhost/enable_vhost.sh

set -euo pipefail

# Подключение логального config.sh
LOCAL_CONFIG="$(realpath $(dirname "${BASH_SOURCE[0]}")/config.sh)"
source "$LOCAL_CONFIG" || {
    echo "Failed to source local config '$LOCAL_CONFIG'" >&2
    exit 1
}

[[ ${#ARGS[@]} -ge 1 ]] || { echo "Usage: $0 <domain>"; exit "$EXIT_INVALID_ARG"; }

DOMAIN="${ARGS[0]}"

# Включение виртулального хоста
enable_vhost() {
    local domain="$1"
    [[ -z "$domain" ]] && { log_message "error" "No virtual host domain provided"; return "$EXIT_INVALID_ARG"; }

    [[ ! "$domain" =~ ^[a-zA-Z0-9._-]+$ ]] && { log_message "error" "Invalid virtual host domain '$domain'"; return "$EXIT_INVALID_ARG"; }

    log_message "info" "Enabling virtual host '$domain'"

    local vhostfile="$VHOST_AVAILABLE/$domain.conf"
    local vhost_enabled="$VHOST_ENABLED/$domain.conf"

    [[ ! -f "$vhostfile" ]] && {
        log_message "error" "Failed to find the virtual host '$domain'"
        return "$EXIT_NOT_FOUND"
    }

    [[ ! -L "$vhost_enabled" ]] && {
        with_lock "$TMP_DIR/$LOCK_GLOBAL_VHOST.lock" _enable_vhost_lock "$domain" || return $?
    }

    log_message "ok" "Virtual host '$domain' successfully disabled"
    return 0
}

_enable_vhost_lock() {
    local domain="$1"
    [[ -z "$domain" ]] && { log_message "error" "No virtual host domain provided"; return "$EXIT_INVALID_ARG"; }

    a2ensite --quiet "$domain" >/dev/null || {
        log_message "error" "Failed to enable virtual host '$domain'"
        return "$EXIT_VHOST_CONFIG_FAILED"
    }

    _reload_apache_lock "$domain" || return $?

    return 0
}

_reload_apache_lock() {
    local domain="$1"
    [[ -z "$domain" ]] && { log_message "error" "No virtual host domain provided"; return "$EXIT_INVALID_ARG"; }

    systemctl reload apache2 >/dev/null 2>&1 || {
        log_message "error" "Failed to reload Apache"
        _disable_vhost_lock "$domain" || true
        return $EXIT_RELOAD_APACHE_FAILED
    }
    return 0
}

_disable_vhost_lock() {
    local domain="$1"
    [[ -z "$domain"  ]] && { log_message "error" "No virtual host domain provided"; return "$EXIT_INVALID_ARG"; }

    a2dissite --quiet "$domain" >/dev/null || {
        log_message "error" "Failed to disable virtual host '$domain'"
        return "$EXIT_VHOST_DISABLE_FAILED"
    }

    return 0
}

# Отключение виртуального хоста с блокировкой
with_lock "${TMP_DIR}/${LOCK_VHOST_PREF}_${DOMAIN}.lock" enable_vhost "$DOMAIN"
exit $?