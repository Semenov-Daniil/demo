#!/bin/bash

# Файл конфигурации для скриптов ssh
# Определяет константы, пути и коды выхода

# Проверка, что скрипт не вызывается напрямую
if [[ "${BASH_SOURCE[0]}" == "$0" ]]; then
    echo "Error: This script ('$0') is meant to be sourced, not executed directly" >&2
    exit 1
fi

# Подключение общей конфигурации
CONFIG_SCRIPT="$(dirname "${BASH_SOURCE[0]}")/../config.sh"
if [[ ! -f "$CONFIG_SCRIPT" ]]; then
    echo "Error: Config script '$CONFIG_SCRIPT' not found" >&2
    return 3
fi
source "$CONFIG_SCRIPT" "$@"
if [[ $? -ne 0 ]]; then
    echo "Error: Failed to source config script '$CONFIG_SCRIPT'" >&2
    return 3
fi

# Подключение логирования
if [[ ! -f "$LOGGING_SCRIPT" ]]; then
    echo "Error: Logging script '$LOGGING_SCRIPT' not found" >&2
    return $ERR_FILE_NOT_FOUND
fi
if [[ ! -r "$LOGGING_SCRIPT" || ! -x "$LOGGING_SCRIPT" ]]; then
    echo "Error: Logging script '$LOGGING_SCRIPT' is not readable or executable" >&2
    return $ERR_FILE_NOT_FOUND
fi
ret_file=$(mktemp)
mapfile -t ARGS < <(source "$LOGGING_SCRIPT" "$@"; echo $? > "$ret_file")
ret=$(cat "$ret_file")
rm -f "$ret_file"
if [[ $ret -ne 0 ]]; then
    echo "Error: Failed to source logging script '$LOGGING_SCRIPT'" >&2
    return $ERR_FILE_NOT_FOUND
fi

if ! declare -F log >/dev/null; then
    echo "Error: Logging function 'log' not defined after sourcing '$LOGGING_SCRIPT'" >&2
    return $ERR_FILE_NOT_FOUND
fi

# Сбор аргументов, исключая --log
ARGS=()
for arg in "$@"; do
    if [[ "$arg" != --log=* ]]; then
        ARGS+=("$arg")
    fi
done

export -f log

printf '%s\n' "${ARGS[@]}"