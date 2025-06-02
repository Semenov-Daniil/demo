#!/bin/bash

# Подключение глобального config.sh
GLOBAL_CONFIG="$(realpath $(dirname "${BASH_SOURCE[0]}")/config.sh)"
source "$GLOBAL_CONFIG" || {
    echo "Failed to source global config '$GLOBAL_CONFIG'" >&2
    return 1
}

fn_source() {
    echo "scripts"
    return 0
}

with_lock "$TMP_DIR/tmp_lock_test.lock" fn_source

exit 0