#!/bin/bash
# setup_queue.sh - Скрипт для настройки yii-queue
# Расположение: bash/utils/setup_queue.sh

set -euo pipefail

# Подключение логального config.sh
LOCAL_CONFIG="$(realpath $(dirname "${BASH_SOURCE[0]}")/config.sh)"
source "$LOCAL_CONFIG" || {
    echo "Failed to source local config '$LOCAL_CONFIG'" >&2
    exit 1
}

declare -xr SERVICE_NAME="yii-queue-worker"
declare -xr SERVICE_PATH="/etc/systemd/system/$SERVICE_NAME.service"
declare -xr YII_PATH="$PROJECT_ROOT/yii"
declare -xr PHP_BIN=$(command -v php)

systemctl list-units --type=service --all | grep -q "$SERVICE_NAME.service" || {
    cat > "$SERVICE_PATH" <<EOF
[Unit]
Description=Yii2 Queue Worker
After=network.target

[Service]
User=$SITE_USER
Group=$SITE_GROUP
ExecStart=$PHP_BIN $YII_PATH queue/listen --verbose=1 --isolate=1
Restart=always
RestartSec=5s
WorkingDirectory=$(dirname $YII_PATH)
StandardOutput=journal
StandardError=journal

[Install]
WantedBy=multi-user.target
EOF || {
    log_message "error" "Failed to write systemd unit file '$SERVICE_NAME.service'"
    exit "$EXIT_GENERAL_ERROR"
}

}

systemctl is-active --quiet "$SERVICE_NAME.service" || {
    systemd-analyze verify "$SERVICE_NAME.service" >/dev/null 2>&1 || {
        log_message "error" "Incorrect syntax unit '$SERVICE_NAME.service'" >&2
        exit "$EXIT_GENERAL_ERROR"
    }

    systemctl enable --now "$SERVICE_NAME.service" >/dev/null 2>&1 || {
        log_message "error" "Failed to enable or start systemd unit '$SERVICE_NAME.service'" >&2
        exit "$EXIT_GENERAL_ERROR"
    }

    local start_time=$(date +%s) timeout="15"
    while ! $(is_active_unit "$SERVICE_NAME.service"); do
        [[ $(( $(date +%s) - start_time )) -gt "$timeout" ]] && {
            log_message "error" "Timeout waiting for systemd unit '$SERVICE_NAME.service' to become active"
            exit "$EXIT_GENERAL_ERROR"
        }
        sleep 0.1
    done
}

exit 0