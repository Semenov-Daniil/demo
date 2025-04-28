#!/bin/bash

# Проверка root-прав
if [[ $EUID -ne 0 ]]; then
    echo "Error: This script must be run with root privileges" >&2
    exit $ERR_ROOT_REQUIRED
fi

# Подключение конфигурации
source "$(dirname "${BASH_SOURCE[0]}")/../config.sh"

# Подключение конфигурации
CONFIG_SCRIPT="$(dirname "${BASH_SOURCE[0]}")/../config.sh"
if [[ ! -f "$CONFIG_SCRIPT" ]]; then
    echo "Error: Config script '$CONFIG_SCRIPT' not found" >&2
    exit $ERR_FILE_NOT_FOUND
fi
mapfile -t ARGS < <(source "$CONFIG_SCRIPT" "$@") || {
    echo "Error: Failed to source config script '$CONFIG_SCRIPT'" >&2
    exit $ERR_FILE_NOT_FOUND
}exit $ERR_FILE_NOT_FOUND
fi

# Проверка аргументов
if [[ ${#ARGS[@]} -lt 1 ]]; then
    log "Error: username is required"
    exit $ERR_GENERAL
fi

USERNAME="${ARGS[0]}"
if [[ ! "$USERNAME" =~ ^[a-zA-Z0-9_-]+$ ]]; then
    log "Error: Invalid username: $USERNAME"
    exit $ERR_INVALID_USERNAME
fi

log "Starting deletion of chroot environment for user $USERNAME"

STUDENT_HOME="${STUDENTS_HOME}/$USERNAME"
CHROOT_HOME="${CHROOTS_HOME}/$USERNAME"

# Проверка существования пользователя
if ! id "$USERNAME" >/dev/null 2>&1; then
    log "Error: User '$USERNAME' does not exist"
    exit $ERR_GENERAL
fi

# Проверка существования окружения студента
if [[ ! -d "$CHROOT_HOME" ]]; then
    log "Warning: Chroot home '$CHROOT_HOME' does not exist"
    exit 0
fi

# Размонтирование домашней папки
if mountpoint -q "$CHROOT_HOME"; then
    umount "$CHROOT_HOME" >> "$LOG_FILE" 2>&1 || {
        log "Error: Failed to unmount $CHROOT_HOME"
        exit $ERR_MOUNT_FAILED
    }
    log "Info: Unmounted home directory for user: $USERNAME"
else
    log "Warning: Directory not mounted: $CHROOT_HOME"
fi

# Удаление записи из /etc/fstab
FSTAB_LINE="$STUDENT_HOME $CHROOT_HOME none bind 0 0"
if grep -F -- "$FSTAB_LINE" /etc/fstab >/dev/null 2>&1; then
    sed -i "\|$FSTAB_LINE|d" /etc/fstab 2>>"$LOG_FILE" || {
        log "Error: Failed to remove fstab entry for: $USERNAME"
        exit $ERR_FSTAB_FAILED
    }
    log "Info: Removed fstab entry for: $USERNAME"
else
    log "Warning: No fstab entry found for: $USERNAME"
fi

# Удаление chroot-директории
if [[ -d "$CHROOT_HOME" ]]; then
    rm -rf "$CHROOT_HOME" 2>>"$LOG_FILE" || {
        log "Error: Failed to remove chroot directory: $CHROOT_HOME"
        exit $ERR_GENERAL
    }
    log "Info: Removed chroot directory: $CHROOT_HOME"
fi

log "Successfully processed chroot environment for $USERNAME"

exit 0