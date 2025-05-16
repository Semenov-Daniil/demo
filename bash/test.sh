#!/bin/bash

LOGGING_SCRIPT="$(dirname "${BASH_SOURCE[0]}")/logging/logging.fn.sh"
LOG_FILE="$(basename "${BASH_SOURCE[0]}" .sh).log"

TEST_ARR=("1" "2" "3" "4" "5")

asd=${TEST_ARR[@]}

echo "TEST_ARR: ${TEST_ARR[*]}"
echo "asd: ${asd[*]}"

exit 0