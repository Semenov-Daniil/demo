#!/bin/bash

# Скрипт для настройки chroot-окружения и конфигурации SSH

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

# Подключение скрипта создания директорий
source_script "$CREATE_DIRS_SCRIPT" "$@"
if [[ $? -ne 0 ]]; then
    log "$LOG_ERROR: Failed to source script '$CREATE_DIRS_SCRIPT'"
    return $ERR_FILE_NOT_FOUND
fi

# Проверка наличия функции create_directories
if ! declare -F create_directories >/dev/null; then
    log "$LOG_ERROR: Function 'create_directories' not defined after sourcing '$CREATE_DIRS_SCRIPT'"
    return $ERR_FILE_NOT_FOUND
fi

# Подключение скрипта проверки команд
source_script "$CHECK_CMDS_SCRIPT" "$@"
if [[ $? -ne 0 ]]; then
    log "$LOG_ERROR: Failed to source script '$CHECK_CMDS_SCRIPT'"
    return $ERR_FILE_NOT_FOUND
fi

# Проверка наличия функции check_cmds
if ! declare -F check_cmds >/dev/null; then
    log "$LOG_ERROR: Function 'check_cmds' not defined after sourcing '$CHECK_CMDS_SCRIPT'"
    return $ERR_FILE_NOT_FOUND
fi

# Проверка root-прав
check_root
if [[ $? -ne 0 ]]; then
 log "$LOG_ERROR: Function 'check_cmds' not defined after sourcing '$CHECK_CMDS_SCRIPT'"
    exit $ERR_ROOT_REQUIRED
fi

log "$LOG_INFO: Preparing chroot environment and configuring SSH"

# Проверка команд
check_cmds "sshd"
if [[ $? -ne 0 ]]; then
    exit $ERR_GENERAL
fi

# Создание и настройка chroot-директорий
create_directories "$CHROOT_DIR" "$CHROOT_TEMPLATE" "$CHROOT_STUDENTS" "755" "root:root"
if [[ $? -ne 0 ]]; then
    log "$LOG_ERROR: Failed create directories"
    exit $ERR_GENERAL
fi

# Инифиализация chroot-шаблона
log "$LOG_INFO: Initializing chroot template in $CHROOT_TEMPLATE"
if ! jk_init -v -j "$CHROOT_TEMPLATE" basicshell 2>&1 | tee -a "$LOG_FILE"; then
    log "$LOG_ERROR: Failed to initialize chroot template $CHROOT_TEMPLATE"
    exit $ERR_CHROOT_INIT_FAILED
fi
log "$LOG_INFO: Chroot template initialized"

# Проверка директории sshd_config.d
if [[ ! -d "$SSH_CONFIGS_DIR" || ! -w "$SSH_CONFIGS_DIR" ]]; then
    log "$LOG_ERROR: SSH config include directory not found or not writable: $SSH_CONFIGS_DIR"
    exit $ERR_FILE_NOT_FOUND
fi

# Создание конфигурации группы стулентов
log "$LOG_INFO: Writing SSH config for '$STUDENTS_GROUP' group to $STUDENT_CONF_FILE"
if [[ -f "$STUDENT_CONF_FILE" ]]; then
    log "$LOG_WARNING: Overwriting existing $STUDENT_CONF_FILE"
fi
cat > "$STUDENT_CONF_FILE" <<EOF
Match Group students
    ChrootDirectory ${CHROOT_STUDENTS}/%u
    ForceCommand /bin/bash
    X11Forwarding no
    AllowTcpForwarding no
    PasswordAuthentication yes
EOF
if [[ $? -ne 0 ]]; then
    log "$LOG_ERROR: Failed to create $STUDENT_CONF_FILE"
    exit $ERR_SSH_CONFIG_FAILED
fi
if ! chown root:root "$STUDENT_CONF_FILE" 2>>"$LOG_FILE" || ! chmod 644 "$STUDENT_CONF_FILE" 2>>"$LOG_FILE"; then
    log "$LOG_ERROR: Failed to set permissions for '$STUDENT_CONF_FILE'"
    exit $ERR_GENERAL
fi
log "$LOG_INFO: SSH students.conf created"

# Проверка основного sshd_config
if [[ ! -f "$SSH_CONFIG_FILE" || ! -w "$SSH_CONFIG_FILE" ]]; then
    log "$LOG_ERROR: SSH main config file not found or not writable: $SSH_CONFIG_FILE"
    exit $ERR_FILE_NOT_FOUND
fi
if ! grep -E "^\s*Include\s+${SSH_CONFIGS_DIR}/\*.conf" "$SSH_CONFIG_FILE" >/dev/null; then
    echo "Include ${SSH_CONFIGS_DIR}/*.conf" >> "$SSH_CONFIG_FILE" 2>>"$LOG_FILE" || {
        log "$LOG_ERROR: Failed to add Include directive to $SSH_CONFIG_FILE"
        exit $ERR_SSH_CONFIG_FAILED
    }
    log "$LOG_INFO: Added Include directive to $SSH_CONFIG_FILE"
else
    log "$LOG_INFO: Include directive already present in $SSH_CONFIG_FILE"
fi

# Проверка синтаксиса и перезапуск SSH
log "$LOG_INFO: Checking SSH configuration syntax and restarting"
if sshd -t >> "$LOG_FILE" 2>&1; then
    if systemctl restart sshd 2>&1 | tee -a "$LOG_FILE"; then
        log "$LOG_INFO: SSH service restarted"
    else
        log "$LOG_ERROR: Failed to restart SSH service"
        exit $ERR_SSH_CONFIG_FAILED
    fi
else
    log "$LOG_ERROR: SSH configuration syntax error"
    exit $ERR_SSH_CONFIG_FAILED
fi

log "$LOG_INFO: Preparation of the chroot environment and SSH configuration successfully"

exit 0