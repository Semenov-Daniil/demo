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
mapfile -t COMMANDS < <(source "$CONFIG_SCRIPT" "$@") || {
    echo "Error: Failed to source config script '$CONFIG_SCRIPT'" >&2
    exit $ERR_FILE_NOT_FOUND
}

log "Starting addition of commands to chroot environments"

# Проверка аргументов
if [[ ${#COMMANDS[@]} -lt 1 ]]; then
    log "Error: At least one command is required"
    exit $ERR_GENERAL
fi

# Проверка наличия jk_cp
if ! command -v jk_cp >/dev/null 2>&1; then
    log "Error: jk_cp command not found"
    exit $ERR_GENERAL
fi

# Проверка наличия parallel
if ! command -v parallel >/dev/null 2>&1; then
    log "Error: parallel command not found"
    exit $ERR_GENERAL
fi

# Проверка существования /var/chroot/home
if [[ ! -d "$CHROOTS_HOME" ]]; then
    log "Error: Chroot base directory '$CHROOTS_HOME' does not exist"
    exit $ERR_FILE_NOT_FOUND
fi

log "Adding commands to all chroot environments in $CHROOTS_HOME"

# Функция для добавления команд в одно chroot-окружение
add_commands_to_chroot() {
    local CHROOT_HOME="$1"
    local USERNAME=$(basename "$CHROOT_HOME")
    local LOG_FILE="$2"
    shift 2
    if ! id "$USERNAME" >/dev/null 2>&1; then
        echo "[$(date '+%Y-%m-%d %H:%M:%S')] Warning: User '$USERNAME' does not exist, skipping" | tee -a "$LOG_FILE"
        return
    fi
    local CMD
    for CMD in "$@"; do
        if ! command -v "$CMD" >/dev/null 2>&1; then
            echo "[$(date '+%Y-%m-%d %H:%M:%S')] Warning: Command not found on system: $CMD for $USERNAME" | tee -a "$LOG_FILE"
            continue
        fi
        if [[ -f "$CHROOT_HOME/bin/$CMD" ]]; then
            echo "[$(date '+%Y-%m-%d %H:%M:%S')] Info: Command $CMD already exists in chroot for $USERNAME" | tee -a "$LOG_FILE"
            continue
        fi
        if jk_cp -v -j "$CHROOT_HOME" "$(which "$CMD")" >> "$LOG_FILE" 2>&1; then
            echo "[$(date '+%Y-%m-%d %H:%M:%S')] Info: Added command $CMD to chroot for $USERNAME" | tee -a "$LOG_FILE"
        else
            echo "[$(date '+%Y-%m-%d %H:%M:%S')] Error: Failed to add command $CMD to chroot for $USERNAME" | tee -a "$LOG_FILE"
            exit $ERR_CHROOT_INIT_FAILED
        fi
    done
}
export -f add_commands_to_chroot

# Сбор списка chroot-окружений
CHROOT_DIRS=()
while IFS= read -r dir; do
    if [[ -d "$dir/bin" ]]; then
        CHROOT_DIRS+=("$dir")
    else
        log "Warning: Directory '$dir' is not a valid chroot environment"
    fi
done < <(find "$CHROOTS_HOME" -maxdepth 1 -type d -not -path "$CHROOTS_HOME")

if [[ ${#CHROOT_DIRS[@]} -eq 0 ]]; then
    log "Warning: No valid chroot environments found in $CHROOTS_HOME"
    exit 0
fi

# Параллельное добавление команд
printf '%s\n' "${CHROOT_DIRS[@]}" | parallel --halt now,fail=1 --will-cite add_commands_to_chroot {} "$LOG_FILE" "${COMMANDS[@]}" 2>>"$LOG_FILE" || {
    log "Error: Parallel command execution failed, check $LOG_FILE for details"
    exit $ERR_GENERAL
}

log "Command addition process completed for all chroot environments"
exit 0