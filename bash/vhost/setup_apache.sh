#!/bin/bash
# setup_apache.sh - Скрипт для настройки Apache
# Расположение: bash/samba/setup_apache.sh

set -euo pipefail

# Подключение логального config.sh
LOCAL_CONFIG="$(realpath $(dirname "${BASH_SOURCE[0]}")/config.sh)"
source "$LOCAL_CONFIG" || {
    echo "Failed to source local config '$LOCAL_CONFIG'" >&2
    exit 1
}

declare -rx MAIN_CONFIG="/etc/apache2/apache2.conf"
declare -rx BACKUP_FILE="/etc/apache2/apache2.conf.bak.$(date +%Y%m%d_%H%M%S)"

check_requirements() {
    command -v apache2ctl &> /dev/null || {
        log_message "error" "Apache2 (apache2ctl) is not installed"
        return "$EXIT_APACHE_NOT_INSTALLED"
    }

    [[ ! -f "$MAIN_CONFIG" ]] && {
        log_message "error" "Failed to find the main configuration file '$MAIN_CONFIG'"
        return "$EXIT_APACHE_SERVICE_FAILED"
    }

    apache2ctl configtest &>/dev/null || {
        log_message "error" "There is a syntax error in the virtual hosts configuration"
        return "$EXIT_APACHE_SERVICE_FAILED"
    }

    return 0
}

check_mpm_itk() {
    dpkg -l | grep libapache2-mpm-itk >/dev/null || {
        log_message "warning" "Module mpm_itk is not installed"
        apt install libapache2-mpm-itk >/dev/null || {
            log_message "error" "Failed to install the mpm_itk module"
            return "$EXIT_APACHE_SERVICE_FAILED"
        }
        log_message "info" "Module mpm_itk is installed"
    }

    apache2ctl -M 2>/dev/null | grep mpm_itk >/dev/null || {
        log_message "warning" "Module mpm_itk is not enabled"
        if a2enmod mpm_itk >/dev/null; then
            log_message "info" "Module mpm_itk enabled successfully"
        else
            log_message "error" "Failed to enable the mpm_itk module"
            return "$EXIT_APACHE_SERVICE_FAILED"
        fi
    }

    local setting_range="$(cat <<EOF
<IfModule mpm_itk_module>
    LimitUIDRange 0 4294496296
    LimitGIDRange 0 4294496296
</IfModule>
EOF
)"
    local current_range="$(grep -F "$setting_range" "$MAIN_CONFIG")" || return $?

    [[ "$current_range" != "$setting_range" ]] && {
        cp "$MAIN_CONFIG" "$BACKUP_FILE" 2>/dev/null || log_message "warning" "Failed to create backup '$BACKUP_FILE'"
        printf "%s\n" "$setting_range" >> "$MAIN_CONFIG" && {
            log_message "info" "Added mpm_itk module settings block"
        } || {
            log_message "error" "Failed to write mpm_itk module settings to main config '$MAIN_CONFIG'"
            return "$EXIT_APACHE_SERVICE_FAILED"
        }
    }

    return 0
}

restart_apache() {
    systemctl restart apache2 &>/dev/null || {
        log_message "error" "Failed to restart Apache2"
        return "$EXIT_APACHE_SERVICE_FAILED"
    }
    return 0
}

setup_apache() {
    check_mpm_itk || return $?

    restart_apache || return $?
    return 0
}

check_requirements || exit $?

with_lock "$LOCK_GLOBAL_VHOST" setup_apache
exit $?