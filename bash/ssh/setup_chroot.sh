#!/bin/bash

# Проверка root-прав
if [[ $EUID -ne 0 ]]; then
    echo "Error: This script must be run with root privileges" >&2
    exit $ERR_ROOT_REQUIRED
fi

# Подключение конфигурации
CONFIG_SCRIPT="$(dirname "${BASH_SOURCE[0]}")/../config.sh"
if [[ ! -f "$CONFIG_SCRIPT" ]]; then
    echo "Error: Config script '$CONFIG_SCRIPT' not found" >&2
    exit $ERR_FILE_NOT_FOUND
fi
mapfile -t ARGS < <(source "$CONFIG_SCRIPT" "$@") || {
    echo "Error: Failed to source config script '$CONFIG_SCRIPT'" >&2
    exit $ERR_FILE_NOT_FOUND
}

log "Preparing chroot environment template"

# Создание директории Chroot
for dir in "$CHROOT_DIR" "$CHROOTS_HOME"; do
    if [[ ! -d "$dir" ]]; then
        mkdir -p "$dir" 2>/dev/null || {
            log "Error: Cannot create directory '$dir'"
            exit $ERR_FILE_NOT_FOUND
        }
        log "Info: Created directory '$dir'"
    fi
    if [[ "$(stat -c %a:%U:%G "$dir")" != "755:root:root" ]]; then
        chown root:root "$dir"
        chmod 755 "$dir"
        log "Info: Corrected permissions for $dir to 755 root:root"
    fi
done

log "Chroot environment template prepared"

exit 0