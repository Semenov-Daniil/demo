#!/bin/bash
# Скрипт для настройки chroot-окружения и конфигурации SSH
# Расположение: bash/ssh/setup_ssh.sh

set -euo pipefail

# Проверка и запуск SSH-сервиса
start_ssh_service() {
    if ! systemctl is-active --quiet sshd; then
        systemctl start sshd || {
            log_message "error" "Failed to start SSH service"
            exit ${EXIT_SSH_CONFIG_FAILED}
        }
    fi
}

# Получение SSH-порта
get_ssh_port() {
    local port
    port=$(grep -h -E "^Port\s+[0-9]+" "$SSH_CONFIG_FILE" "$SSH_CONFIGS_DIR"/*.conf 2>/dev/null | awk '{print $2}' | head -n 1)
    if [[ -z "$port" || ! "$port" =~ ^[0-9]+$ || "$port" -lt 1 || "$port" -gt 65535 ]]; then
        port=22
    fi
    echo "$port"
}

# Настройка UFW для SSH-порта
configure_ufw() {
    local port="$1"
    if ! command -v ufw >/dev/null; then
        return
    fi
    if ufw status | grep -qw "active"; then
        if ! ufw status | grep -E "${port}/tcp.*ALLOW" >/dev/null; then
            ufw allow "$port/tcp" || {
                log_message "error" "Failed to configure UFW for SSH port $port"
                exit ${EXIT_GENERAL_ERROR}
            }
        fi
    fi
}

# Проверка директории sshd_config.d
check_configs_dir() {
    if [[ ! -d "$SSH_CONFIGS_DIR" || ! -w "$SSH_CONFIGS_DIR" ]]; then
        log_message "error" "SSH config include directory not found or not writable: $SSH_CONFIGS_DIR"
        exit ${EXIT_GENERAL_ERROR}
    fi
}

# Создание/обновление конфигурации группы студентов
update_student_config() {
    local config_content
    config_content=$(cat <<EOF
Match Group ${STUDENT_GROUP}
    ChrootDirectory ${CHROOT_STUDENTS}/%u
    ForceCommand /bin/bash
    X11Forwarding no
    AllowTcpForwarding no
    PasswordAuthentication yes
EOF
)
    if [[ -f "$STUDENT_CONF_FILE" ]]; then
        local current_content
        current_content=$(cat "$STUDENT_CONF_FILE")
        if [[ "$current_content" == "$config_content" ]]; then
            return
        fi
    fi
    echo "$config_content" > "$STUDENT_CONF_FILE" || {
        log_message "error" "Failed to create/update $STUDENT_CONF_FILE"
        exit ${EXIT_SSH_CONFIG_FAILED}
    }
    update_file_permissions "$STUDENT_CONF_FILE" "644" "root:root" || {
        log_message "error" "Failed to set permissions or owner for '$STUDENT_CONF_FILE'"
        exit ${EXIT_GENERAL_ERROR}
    }
}

# Обновление владельца и прав файла
update_file_permissions() {
    local -a files=("${@:1:$#-2}") 
    local perms="${@: -2:1}"
    local owner="${@: -1:1}"

    if [[ ! "$perms" =~ ^[0-7]{3}$ ]]; then
        log_message "error" "Invalid permissions format: $perms (expected octal, e.g., 755)"
        return ${EXIT_INVALID_ARG}
    fi

    if [[ ! "$owner" =~ ^[a-zA-Z0-9._-]+(:[a-zA-Z0-9._-]+)?$ ]]; then
        log_message "error" "Invalid owner format: $owner (expected user:group or user)"
        return ${EXIT_INVALID_ARG}
    fi

    for file in "${files[@]}"; do
        current_perms=$(stat -c %a "$file")
        current_owner=$(stat -c %U:%G "$file")
        
        if [[ "$current_owner" != "$owner" ]]; then
            chown "$owner" "$file" || {
                log_message "error" "Failed to set ownership $owner for '$file'"
                return ${EXIT_GENERAL_ERROR}
            }
        fi

        if [[ "$current_perms" != "$perms" ]]; then
            chown "$perms" "$file" || {
                log_message "error" "Failed to set permissions $perms for '$file'"
                return ${EXIT_GENERAL_ERROR}
            }
        fi
    done
    return ${EXIT_SUCCESS}
}

# Проверка и обновление основного sshd_config
update_main_config() {
    if [[ ! -f "$SSH_CONFIG_FILE" || ! -w "$SSH_CONFIG_FILE" ]]; then
        log_message "error" "SSH main config file not found or not writable: $SSH_CONFIG_FILE"
        exit ${EXIT_GENERAL_ERROR}
    fi
    if ! grep -E "^\s*Include\s+${SSH_CONFIGS_DIR}/\*.conf" "$SSH_CONFIG_FILE" >/dev/null; then
        echo "Include ${SSH_CONFIGS_DIR}/*.conf" >> "$SSH_CONFIG_FILE" || {
            log_message "error" "Failed to add Include directive to $SSH_CONFIG_FILE"
            exit ${EXIT_SSH_CONFIG_FAILED}
        }
    fi
}

# Проверка синтаксиса и перезапуск SSH
restart_ssh_service() {
    if sshd -t; then
        systemctl restart sshd || {
            log_message "error" "Failed to restart SSH service"
            exit ${EXIT_SSH_CONFIG_FAILED}
        }
    else
        log_message "error" "SSH configuration syntax error"
        exit ${EXIT_SSH_CONFIG_FAILED}
    fi
}

# Основная логика
# Проверка зависимостей
check_deps "openssh-server" || {
    log_message "error" "Failed check dependencies"
    exit "${EXIT_NO_DEPENDENCY}"
}

# Запуск SSH-сервиса
start_ssh_service

# Настройка UFW для SSH-порта
local ssh_port
ssh_port=$(get_ssh_port)
configure_ufw "$ssh_port"

# Проверка директории sshd_config.d
check_configs_dir

# Создание/обновление конфигурации группы студентов
update_student_config

# Проверка и обновление основного sshd_config
update_main_config

# Проверка синтаксиса и перезапуск SSH
restart_ssh_service

# Создание и настройка chroot-директорий
create_directories "$CHROOT_DIR" "$CHROOT_STUDENTS" 755 root:root || {
    log_message "error" "Failed create directories: ${CHROOT_DIR}, ${CHROOT_STUDENTS}"
    exit ${EXIT_CHROOT_INIT_FAILED}
}

return ${EXIT_SUCCESS}