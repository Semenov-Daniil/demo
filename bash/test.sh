#!/bin/bash

set -euo pipefail

source "$(dirname "${BASH_SOURCE[0]}")/lib/logging.sh" "logtest.log"
for i in {1..50}; do
    log_message "info" "Test message $i from $$" &
done
wait