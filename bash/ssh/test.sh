#!/bin/bash

# Тестовый скрипт для проверки конфигурации и логирования

source "$(dirname "${BASH_SOURCE[0]}")/config.sh" "$@"
if [[ $? -ne 0 ]]; then
    echo "Failed to source config.sh"
    exit 1
fi

log "$LOG_DEBUG: This is a debug message"
log "$LOG_INFO: This is an info message"
log "$LOG_ERROR: This is an error message"

echo "$ARGS"