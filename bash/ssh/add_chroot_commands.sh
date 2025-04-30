#!/bin/bash

# Скрипт добавления команд в chroot-окружение

# Подключение конфигурации
CONFIG_SCRIPT="$(dirname "${BASH_SOURCE[0]}")/config.sh"
if [[ ! -f "$CONFIG_SCRIPT" ]]; then
    echo "[ERROR]: Config script '$CONFIG_SCRIPT' not found" >&2
    exit 3
fi
source "$CONFIG_SCRIPT" "$@"
if [[ $? -ne 0 ]]; then
    echo "[ERROR]: Failed to source config script '$CONFIG_SCRIPT'" >&2
    exit 3
fi

# Подключение скрипта проверки команд
if [[ ! -f "$CHECK_CMDS_SCRIPT" ]]; then
    echo "[ERROR]: Script '$CHECK_CMDS_SCRIPT' not found" >&2
    return $ERR_FILE_NOT_FOUND
fi
source "$CHECK_CMDS_SCRIPT" "$@"
if [[ $? -ne 0 ]]; then
    echo "[ERROR]: Failed to source script '$CHECK_CMDS_SCRIPT'" >&2
    return $ERR_FILE_NOT_FOUND
fi

# Проверка наличия функции check_cmds
if ! declare -F check_cmds >/dev/null; then
    echo "[ERROR]: Function 'check_cmds' not defined after sourcing '$CHECK_CMDS_SCRIPT'" >&2
    return $ERR_FILE_NOT_FOUND
fi

# Проверка root-прав
check_root
if [[ $? -ne 0 ]]; then
    exit $ERR_ROOT_REQUIRED
fi

log "$LOG_INFO: Starting addition of commands to chroot environments"

# Проверка массива ARGS
if ! declare -p ARGS >/dev/null 2>&1; then
    log "$LOG_ERROR: ARGS array is not defined"
    exit $ERR_GENERAL
fi

# Проверка аргументов
if [[ ${#ARGS[@]} -lt 1 ]]; then
    log "$LOG_ERROR: At least one command is required"
    exit $ERR_GENERAL
fi

COMMANDS=("${ARGS[@]}")

# Проверка команд
check_cmds "jk_cp" "rsync" "parallel"
if [[ $? -ne 0 ]]; then
    exit $ERR_GENERAL
fi

# Проверка существования шаблона
if [[ -z "$CHROOT_TEMPLATE" || ! -d "$CHROOT_TEMPLATE" ]]; then
    log "$LOG_ERROR: chroot template is empty or does not exist: $CHROOT_TEMPLATE"
    exit $ERR_CHROOT_INIT_FAILED
fi

log "$LOG_INFO: Adding commands to chroot template $CHROOT_TEMPLATE"

# Добавление команд в шаблон
MISSING_CMDS=()
for CMD in "${COMMANDS[@]}"; do
    if ! command -v "$CMD" >/dev/null 2>&1; then
        log "$LOG_ERROR: Command not found on system: $CMD"
        MISSING_CMDS+=("$CMD")
        continue
    fi
    if jk_cp -v -j "$CHROOT_TEMPLATE" "$(which "$CMD")" 2>&1 | tee -a "$LOG_FILE"; then
        log "$LOG_INFO: Added command $CMD to chroot template $CHROOT_TEMPLATE"
    else
        log "$LOG_ERROR: Failed to add command $CMD to chroot template $CHROOT_TEMPLATE"
        MISSING_CMDS+=("$CMD")
    fi
done

if [[ ${#MISSING_CMDS[@]} -gt 0 ]]; then
    log "$LOG_ERROR: Missing command: ${MISSING_CMDS[*]}"
    exit $ERR_GENERAL
fi

# Функция синхронизации одного chroot-окружения
sync_chroot() {
    local chroot_dir="$1"
    local chroot_name=$(basename "$chroot_dir")
    local temp_log="/tmp/sync_chroot_$chroot_name.log"

    # Проверка, является ли директория chroot-окружением
    if [[ ! -d "$chroot_dir" || ! -d "$chroot_dir/bin" ]]; then
        echo "[$(date '+%Y-%m-%d %H:%M:%S')] $LOG_WARNING: $chroot_dir is not a valid chroot environment, skipping" >> "$temp_log"
        cat "$temp_log" >> "$LOG_FILE"
        rm -f "$temp_log"
        return 0
    fi

    # Проверка, что chroot_name соответствует существующему пользователю
    if ! id "$chroot_name" >/dev/null 2>&1; then
        echo "[$(date '+%Y-%m-%d %H:%M:%S')] $LOG_WARNING: $chroot_name is not a valid user, skipping $chroot_dir" >> "$temp_log"
        cat "$temp_log" >> "$LOG_FILE"
        rm -f "$temp_log"
        return 0
    fi

    # Проверка свободного места
    local MIN_SPACE_MB=100
    local FREE_SPACE=$(df -m "$chroot_dir" | awk 'NR==2 {print $4}' || echo 0)
    if [[ "$FREE_SPACE" -lt "$MIN_SPACE_MB" ]]; then
        echo "[$(date '+%Y-%m-%d %H:%M:%S')] $LOG_WARNING: Insufficient disk space for $chroot_dir ($FREE_SPACE MB available, $MIN_SPACE_MB MB required), skipping" >> "$temp_log"
        cat "$temp_log" >> "$LOG_FILE"
        rm -f "$temp_log"
        return 0
    fi

    # Синхронизация с исключением /etc/bash.bashrc, /etc/passwd, /etc/group
    echo "[$(date '+%Y-%m-%d %H:%M:%S')] $LOG_INFO: Synchronizing template with chroot for $chroot_name" >> "$temp_log"
    if rsync -av --exclude='/etc/bash.bashrc' --exclude='/etc/passwd' --exclude='/etc/group' "$CHROOT_TEMPLATE/" "$chroot_dir/" >> "$temp_log" 2>&1; then
        echo "[$(date '+%Y-%m-%d %H:%M:%S')] $LOG_INFO: Successfully synchronized chroot for $chroot_name" >> "$temp_log"
    else
        echo "[$(date '+%Y-%m-%d %H:%M:%S')] $LOG_WARNING: Failed to synchronize chroot for $chroot_name" >> "$temp_log"
    fi

    # Перенос временного лога в основной, избегая stdout
    cat "$temp_log" >> "$LOG_FILE"
    rm -f "$temp_log"
}
export -f sync_chroot
export CHROOT_TEMPLATE LOG_FILE

# Проверка содержимого CHROOT_STUDENTS
log "$LOG_INFO: Checking student chroot directories in $CHROOT_STUDENTS"
CHROOT_DIRS=($(find "$CHROOT_STUDENTS" -maxdepth 1 -type d -not -path "$CHROOT_STUDENTS"))
if [[ ${#CHROOT_DIRS[@]} -eq 0 ]]; then
    log "$LOG_WARNING: No student chroot directories found in $CHROOT_STUDENTS"
else
    log "$LOG_INFO: Found ${#CHROOT_DIRS[@]} student chroot directories: ${CHROOT_DIRS[*]}"
fi

log "$LOG_INFO: Starting parallel synchronization of chroot template with student environments in $CHROOT_STUDENTS"

# Параллельная синхронизация
find "$CHROOT_STUDENTS" -maxdepth 1 -type d -not -path "$CHROOT_STUDENTS" -exec bash -c 'sync_chroot "{}"' \; | parallel --no-notice --line-buffer >> "$LOG_FILE" 2>&1

# Проверка ошибок синхронизации
if [[ -n "$(grep "$LOG_WARNING: Failed to synchronize chroot" "$LOG_FILE")" ]]; then
    log "$LOG_WARNING: Some chroot environments failed to synchronize, check $LOG_FILE for details"
else
    log "$LOG_INFO: Successfully added commands and synchronized all chroot environments"
fi

log "$LOG_INFO: Command addition process completed for all chroot environments"
exit 0