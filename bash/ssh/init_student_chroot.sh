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

log "Setting up chroot environment for user $USERNAME"

STUDENT_HOME="${STUDENTS_HOME}/$USERNAME"
CHROOT_HOME="${CHROOTS_HOME}/$USERNAME"

# Проверка существования пользователя
if ! id "$USERNAME" >/dev/null 2>&1; then
    log "Error: User '$USERNAME' does not exist"
    exit $ERR_GENERAL
fi

# Проверка существования jailkit
if ! command -v jk_init >/dev/null 2>&1; then
    log "Error: jailkit (jk_init) is not installed"
    exit $ERR_GENERAL
fi

# Проверка существования папки студента
if [[ ! -d "$STUDENT_HOME" ]]; then
    log "Error: Student home directory '$STUDENT_HOME' does not exist"
    exit $ERR_FILE_NOT_FOUND
fi

# Проверка прав на chroot-директории
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

# Создание chroot-окружения для пользователя
if [[ ! -d "$CHROOT_HOME" ]]; then
    mkdir -p "$CHROOT_HOME" 2>/dev/null || {
        log "Error: Cannot create directory '$CHROOT_HOME'"
        exit $ERR_FILE_NOT_FOUND
    }
    log "Info: Created directory '$CHROOT_HOME'"
fi

if [[ "$(stat -c %a:%U:%G "$CHROOT_HOME")" != "755:root:root" ]]; then
    chown root:root "$CHROOT_HOME"
    chmod 755 "$CHROOT_HOME"
    log "Info: Corrected permissions for $CHROOT_HOME to 755 root:root"
fi

# Инициализация chroot с помощью jailkit
if [[ -d "$CHROOT_HOME/bin" ]]; then
    log "Info: Chroot environment for $USERNAME already initialized"
else
    if ! jk_init -v -j "$CHROOT_HOME" basicshell editors extendedshell >> "$LOG_FILE" 2>&1; then
        log "Error: Failed to initialize chroot environment for $USERNAME"
        exit $ERR_CHROOT_INIT_FAILED
    fi
    log "Info: Initialized chroot environment for $USERNAME"
fi

# Настройка bash.bashrc
cat << EOF > "$CHROOT_HOME/etc/bash.bashrc" 2>>"$LOG_FILE"
export PS1='\u@\h:\w\$ '
export HOME=/
export PATH=/bin:/usr/bin
cd /
unset CDPATH
EOF
if [[ $? -ne 0 ]]; then
    log "Error: Failed to configure bash.bashrc for $USERNAME"
    exit $ERR_GENERAL
fi
chown root:root "$CHROOT_HOME/etc/bash.bashrc"
chmod 644 "$CHROOT_HOME/etc/bash.bashrc"
log "Info: bash.bashrc configured for $USERNAME"

# Проверка монтирования домашней папки
if mountpoint -q "$CHROOT_HOME"; then
    log "Info: $CHROOT_HOME is already mounted"
else
    if mount --bind "$STUDENT_HOME" "$CHROOT_HOME" >> "$LOG_FILE" 2>&1; then
        log "Info: Mounted $STUDENT_HOME to $CHROOT_HOME"
    else
        log "Error: Failed to mount $STUDENT_HOME to $CHROOT_HOME"
        exit $ERR_MOUNT_FAILED
    fi
fi

# Добавление в /etc/fstab
FSTAB_ENTRY="$STUDENT_HOME $CHROOT_HOME none bind 0 0"
if ! grep -qsF "$FSTAB_ENTRY" /etc/fstab; then
    echo "$FSTAB_ENTRY" >> /etc/fstab 2>>"$LOG_FILE" || {
        log "Error: Failed to add mount entry to /etc/fstab"
        exit $ERR_FSTAB_FAILED
    }
    log "Info: Added mount entry to /etc/fstab"
else
    log "Info: Mount entry already exists in /etc/fstab"
fi

log "Successfully set up chroot for $USERNAME"
exit 0