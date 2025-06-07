#!/bin/bash

# Подключение глобального config.sh
GLOBAL_CONFIG="$(realpath $(dirname "${BASH_SOURCE[0]}")/logging/logging.fn.sh)"
source "$(realpath $(dirname "${BASH_SOURCE[0]}")/lib/with_lock.fn.sh)" || {
    echo "Failed to source global config '$GLOBAL_CONFIG'" >&2
    return 1
}
source "$(realpath $(dirname "${BASH_SOURCE[0]}")/lib/update_permissions.fn.sh)" || {
    echo "Failed to source global config '$GLOBAL_CONFIG'" >&2
    return 1
}
source "$(realpath $(dirname "${BASH_SOURCE[0]}")/logging/logging.fn.sh)" || {
    echo "Failed to source global config '$GLOBAL_CONFIG'" >&2
    return 1
}

log_message "info" "Test1"
log_message "info" "Test1"
log_message "info" "Test1"
log_message "info" "Test1"
log_message "info" "Test1"
log_message "info" "Test1"
log_message "info" "Test1"
log_message "info" "Test1"

echo "test"
echo "test"
echo "test"
echo "test"

exit 0