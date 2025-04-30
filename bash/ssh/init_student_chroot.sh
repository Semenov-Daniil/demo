#!/bin/bash

# Скрипт инициализации chroot-окружения пользователя

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

# Подключение скрипта создания директорий
if [[ ! -f "$CREATE_DIRS_SCRIPT" ]]; then
    echo "[ERROR]: Script '$CREATE_DIRS_SCRIPT' not found" >&2
    return $ERR_FILE_NOT_FOUND
fi
source "$CREATE_DIRS_SCRIPT" "$@"
if [[ $? -ne 0 ]]; then
    echo "[ERROR]: Failed to source script '$CREATE_DIRS_SCRIPT'" >&2
    return $ERR_FILE_NOT_FOUND
fi

# Проверка наличия функции create_directories
if ! declare -F create_directories >/dev/null; then
    echo "[ERROR]: Function 'create_directories' not defined after sourcing '$CREATE_DIRS_SCRIPT'" >&2
    return $ERR_FILE_NOT_FOUND
fi

# Проверка root-прав
check_root
if [[ $? -ne 0 ]]; then
    exit $ERR_ROOT_REQUIRED
fi

# Проверка массива ARGS
if ! declare -p ARGS >/dev/null 2>&1; then
    log "$LOG_ERROR: ARGS array is not defined"
    exit $ERR_GENERAL
fi

# Проверка аргументов
if [[ ${#ARGS[@]} -lt 1 ]]; then
    log "$LOG_ERROR: Username is required"
    exit $ERR_GENERAL
fi

USERNAME="${ARGS[0]}"
if [[ ! "$USERNAME" =~ ^[a-zA-Z0-9._-]+$ ]]; then
    log "$LOG_ERROR: Invalid username: $USERNAME"
    exit $ERR_INVALID_USERNAME
fi

log "$LOG_INFO: Setting up chroot environment for user $USERNAME"

STUDENT_HOME="${STUDENTS_HOME}/$USERNAME"
STUDENT_CHROOT="${CHROOT_STUDENTS}/$USERNAME"
STUDENT_CHROOT_HOME="${STUDENT_CHROOT}/home/$USERNAME"

# Проверка существования пользователя
if ! id "$USERNAME" >/dev/null 2>&1; then
    log "$LOG_ERROR: User '$USERNAME' does not exist"
    exit $ERR_GENERAL
fi

# Проверка команд
check_cmds "jk_init"
if [[ $? -ne 0 ]]; then
    exit $ERR_GENERAL
fi

# Проверка существования папки студента
if [[ ! -d "$STUDENT_HOME" ]]; then
    log "$LOG_ERROR: Student home directory '$STUDENT_HOME' does not exist"
    exit $ERR_FILE_NOT_FOUND
fi

# Создание и настройка chroot-директорий
create_directories "$STUDENT_CHROOT" "$STUDENT_CHROOT_HOME" 755 "root:root"
if [[ $? -ne 0 ]]; then
    exit $ERR_GENERAL
fi

# Проверка свободного места
MIN_SPACE_MB=100
FREE_SPACE=$(df -m "$STUDENT_CHROOT" | awk 'NR==2 {print $4}' || echo 0)
if [[ "$FREE_SPACE" -lt "$MIN_SPACE_MB" ]]; then
    log "$LOG_ERROR: Insufficient disk space for $STUDENT_CHROOT ($FREE_SPACE MB available, $MIN_SPACE_MB MB required)"
    exit $ERR_CHROOT_INIT_FAILED
fi

# Копирование шаблона
if [[ ! -d  "$CHROOT_TEMPLATE" ]]; then
    log "$LOG_ERROR: Template chroot directory '$CHROOT_TEMPLATE' does not exist"
    exit $ERR_CHROOT_INIT_FAILED
fi
log "$LOG_INFO: Copying chroot template from $CHROOT_TEMPLATE to $STUDENT_CHROOT"
if ! cp -a "$CHROOT_TEMPLATE"/* "$STUDENT_CHROOT/" 2>&1 | tee -a "$LOG_FILE"; then
    log "$LOG_ERROR: Failed to copy chroot template to $STUDENT_CHROOT"
    exit $ERR_CHROOT_INIT_FAILED
fi
log "$LOG_INFO: Copied chroot template to $STUDENT_CHROOT"

# Создание /etc/passwd и /etc/group в chroot
ETC_DIR="$STUDENT_CHROOT/etc"
if [[ ! -d "$ETC_DIR" ]]; then
    if ! mkdir -p "$ETC_DIR" 2>>"$LOG_FILE"; then
        log "$LOG_ERROR: Cannot create directory '$ETC_DIR'"
        exit $ERR_CHROOT_INIT_FAILED
    fi
    log "$LOG_INFO: Created directory '$ETC_DIR'"
fi

# Получение UID, GID и домашней директории пользователя
USER_INFO=$(getent passwd "$USERNAME")
if [[ -z "$USER_INFO" ]]; then
    log "$LOG_ERROR: Failed to retrieve user info for $USERNAME"
    exit $ERR_GENERAL
fi
USER_UID=$(echo "$USER_INFO" | cut -d: -f3)
USER_GID=$(echo "$USER_INFO" | cut -d: -f4)
USER_GECOS=$(echo "$USER_INFO" | cut -d: -f5)
USER_SHELL=$(echo "$USER_INFO" | cut -d: -f7)
GROUP_INFO=$(getent group "$USER_GID")
GROUP_NAME=$(echo "$GROUP_INFO" | cut -d: -f1)

# Создание минимального /etc/passwd
echo "$USERNAME:x:$USER_UID:$USER_GID:$USER_GECOS:/home/$USERNAME:$USER_SHELL" > "$STUDENT_CHROOT/etc/passwd" 2>>"$LOG_FILE"
if [[ $? -ne 0 ]]; then
    log "$LOG_ERROR: Failed to create /etc/passwd for $USERNAME"
    exit $ERR_CHROOT_INIT_FAILED
fi
if ! chown root:root "$STUDENT_CHROOT/etc/passwd" 2>>"$LOG_FILE" || ! chmod 644 "$STUDENT_CHROOT/etc/passwd" 2>>"$LOG_FILE"; then
    log "$LOG_ERROR: Failed to set permissions for /etc/passwd"
    exit $ERR_CHROOT_INIT_FAILED
fi
log "$LOG_INFO: Created /etc/passwd for $USERNAME"

# Создание минимального /etc/group
echo "$GROUP_NAME:x:$USER_GID:" > "$STUDENT_CHROOT/etc/group" 2>>"$LOG_FILE"
if [[ $? -ne 0 ]]; then
    log "$LOG_ERROR: Failed to create /etc/group for $USERNAME"
    exit $ERR_CHROOT_INIT_FAILED
fi
if ! chown root:root "$STUDENT_CHROOT/etc/group" 2>>"$LOG_FILE" || ! chmod 644 "$STUDENT_CHROOT/etc/group" 2>>"$LOG_FILE"; then
    log "$LOG_ERROR: Failed to set permissions for /etc/group"
    exit $ERR_CHROOT_INIT_FAILED
fi
log "$LOG_INFO: Created /etc/group for $USERNAME"

# Настройка bash.bashrc
BASHRC_DIR="$STUDENT_CHROOT/etc"
if [[ ! -d "$BASHRC_DIR" ]]; then
    if ! mkdir -p "$BASHRC_DIR" 2>>"$LOG_FILE"; then
        log "$LOG_ERROR: Cannot create directory '$BASHRC_DIR'"
        exit $ERR_CHROOT_INIT_FAILED
    fi
    log "$LOG_INFO: Created directory '$BASHRC_DIR'"
fi
cat << EOF > "$BASHRC_DIR/bash.bashrc" 2>>"$LOG_FILE"
export PS1='\u@\h:\w\\\$ '
export HOME=/home/$USERNAME
export PATH=/bin:/usr/bin
if [[ -d "\$HOME" ]]; then
    cd "\$HOME"
else
    cd /
fi
unset CDPATH
EOF
if [[ $? -ne 0 ]]; then
    log "$LOG_ERROR: Failed to configure bash.bashrc for $USERNAME"
    exit $ERR_CHROOT_INIT_FAILED
fi
if ! chown root:root "$BASHRC_DIR/bash.bashrc" 2>>"$LOG_FILE" || ! chmod 644 "$BASHRC_DIR/bash.bashrc" 2>>"$LOG_FILE"; then
    log "$LOG_ERROR: Failed to set permissions for bash.bashrc"
    exit $ERR_CHROOT_INIT_FAILED
fi
log "$LOG_INFO: bash.bashrc configured for $USERNAME"

# Проверка монтирования домашней папки
if mountpoint -q "$STUDENT_CHROOT_HOME"; then
    log "$LOG_INFO: $STUDENT_CHROOT_HOME is already mounted"
else
    if ! mount --bind "$STUDENT_HOME" "$STUDENT_CHROOT_HOME" 2>&1 | tee -a "$LOG_FILE"; then
        log "$LOG_ERROR: Failed to mount $STUDENT_HOME to $STUDENT_CHROOT_HOME"
        exit $ERR_MOUNT_FAILED
    fi
    log "$LOG_INFO: Mounted $STUDENT_HOME to $STUDENT_CHROOT_HOME"
fi

# Добавление в /etc/fstab
FSTAB_ENTRY="$STUDENT_HOME $STUDENT_CHROOT_HOME none bind 0 0"
if ! grep -qsF "$FSTAB_ENTRY" /etc/fstab; then
    cp /etc/fstab /etc/fstab.bak 2>>"$LOG_FILE" || {
        log "$LOG_ERROR: Failed to backup /etc/fstab"
        exit $ERR_FSTAB_FAILED
    }
    if ! echo "$FSTAB_ENTRY" >> /etc/fstab 2>>"$LOG_FILE"; then
        log "$LOG_ERROR: Failed to add mount entry to /etc/fstab"
        exit $ERR_FSTAB_FAILED
    fi
    log "$LOG_INFO: Added mount entry to /etc/fstab"
else
    log "$LOG_INFO: Mount entry already exists in /etc/fstab"
fi

log "$LOG_INFO: Successfully set up chroot for $USERNAME"
exit 0