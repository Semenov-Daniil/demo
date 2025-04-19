#!/bin/bash

# Проверка аргументов
if [ -z "$1" ] || [ -z "$2" ]; then
    echo "Error: Log file path and command list are required" >&2
    exit 1
fi

LOG_FILE="$1"
COMMAND_LIST="$2"

# Проверка прав root
if [[ $EUID -ne 0 ]]; then
    echo "[$(date '+%Y-%m-%d %H:%M:%S')] Error: This script must be run with root privileges" >> "$LOG_FILE"
    exit 2
fi

# Создание директории для логов
LOG_DIR=$(dirname "$LOG_FILE")
if [[ ! -d "$LOG_DIR" ]]; then
    mkdir -p "$LOG_DIR" 2>/dev/null || {
        echo "[$(date '+%Y-%m-%d %H:%M:%S')] Error: Cannot create log directory '$LOG_DIR'" >> "$LOG_FILE"
        exit 3
    }
fi

echo "[$(date '+%Y-%m-%d %H:%M:%S')] Starting SSH and Jailkit setup" >> "$LOG_FILE"

# Проверка наличия openssh-server
if ! dpkg -s openssh-server >/dev/null 2>&1; then
    echo "[$(date '+%Y-%m-%d %H:%M:%S')] Error: openssh-server is not installed" >> "$LOG_FILE"
    exit 4
fi

# Проверка наличия jailkit
if ! dpkg -s jailkit >/dev/null 2>&1; then
    echo "[$(date '+%Y-%m-%d %H:%M:%S')] Error: jailkit is not installed" >> "$LOG_FILE"
    exit 5
fi

# Создание резервной копии sshd_config
SSHD_CONFIG="/etc/ssh/sshd_config"
BACKUP_CONFIG="/etc/ssh/sshd_config.bak.$(date '+%Y%m%d%H%M%S')"
echo "[$(date '+%Y-%m-%d %H:%M:%S')] Creating backup of $SSHD_CONFIG to $BACKUP_CONFIG" >> "$LOG_FILE"
if ! cp "$SSHD_CONFIG" "$BACKUP_CONFIG" >> "$LOG_FILE" 2>&1; then
    echo "[$(date '+%Y-%m-%d %H:%M:%S')] Error: Failed to create backup of $SSHD_CONFIG" >> "$LOG_FILE"
    exit 6
fi

# Проверка и добавление параметров SSH
echo "[$(date '+%Y-%m-%d %H:%M:%S')] Configuring $SSHD_CONFIG" >> "$LOG_FILE"

# Функция для обновления или добавления параметра
update_sshd_config() {
    local param="$1"
    local value="$2"
    if grep -q "^\s*$param\s" "$SSHD_CONFIG"; then
        sed -i "s/^\s*$param\s.*/$param $value/" "$SSHD_CONFIG" >> "$LOG_FILE" 2>&1 || {
            echo "[$(date '+%Y-%m-%d %H:%M:%S')] Error: Failed to update $param" >> "$LOG_FILE"
            exit 7
        }
    else
        echo "$param $value" >> "$SSHD_CONFIG" || {
            echo "[$(date '+%Y-%m-%d %H:%M:%S')] Error: Failed to add $param" >> "$LOG_FILE"
            exit 7
        }
    fi
}

# Обновление основных параметров
update_sshd_config "PermitRootLogin" "no"
update_sshd_config "PasswordAuthentication" "yes"

# Проверка и добавление блока Match Group students
if ! grep -q "Match Group students" "$SSHD_CONFIG"; then
    echo -e "\nMatch Group students\n    ChrootDirectory /var/chroot\n    ForceCommand /bin/bash" >> "$SSHD_CONFIG" || {
        echo "[$(date '+%Y-%m-%d %H:%M:%S')] Error: Failed to add Match Group students block" >> "$LOG_FILE"
        exit 8
    }
else
    echo "[$(date '+%Y-%m-%d %H:%M:%S')] Match Group students block already exists" >> "$LOG_FILE"
fi

# Создание chroot-окружения с Jailkit
CHROOT_DIR="/var/chroot"
if [[ ! -d "$CHROOT_DIR" ]]; then
    echo "[$(date '+%Y-%m-%d %H:%M:%S')] Creating chroot directory $CHROOT_DIR" >> "$LOG_FILE"
    if ! mkdir -p "$CHROOT_DIR" >> "$LOG_FILE" 2>&1; then
        echo "[$(date '+%Y-%m-%d %H:%M:%S')] Error: Failed to create $CHROOT_DIR" >> "$LOG_FILE"
        exit 9
    fi
    echo "[$(date '+%Y-%m-%d %H:%M:%S')] Initializing chroot environment in $CHROOT_DIR" >> "$LOG_FILE"
    if ! jk_init -v -j "$CHROOT_DIR" basicshell editors >> "$LOG_FILE" 2>&1; then
        echo "[$(date '+%Y-%m-%d %H:%M:%S')] Error: Failed to initialize chroot environment" >> "$LOG_FILE"
        exit 10
    fi
else
    echo "[$(date '+%Y-%m-%d %H:%M:%S')] Chroot directory $CHROOT_DIR already exists" >> "$LOG_FILE"
fi

# Настройка прав для ограничения видимости
echo "[$(date '+%Y-%m-%d %H:%M:%S')] Setting permissions for $CHROOT_DIR" >> "$LOG_FILE"
if ! chown root:root "$CHROOT_DIR" >> "$LOG_FILE" 2>&1; then
    echo "[$(date '+%Y-%m-%d %H:%M:%S')] Error: Failed to set owner for $CHROOT_DIR" >> "$LOG_FILE"
    exit 11
fi
if ! chmod 700 "$CHROOT_DIR" >> "$LOG_FILE" 2>&1; then
    echo "[$(date '+%Y-%m-%d %H:%M:%S')] Error: Failed to set permissions for $CHROOT_DIR" >> "$LOG_FILE"
    exit 12
fi
if ! mkdir -p "$CHROOT_DIR/home" >> "$LOG_FILE" 2>&1; then
    echo "[$(date '+%Y-%m-%d %H:%M:%S')] Error: Failed to create $CHROOT_DIR/home" >> "$LOG_FILE"
    exit 13
fi
if ! chown root:root "$CHROOT_DIR/home" >> "$LOG_FILE" 2>&1; then
    echo "[$(date '+%Y-%m-%d %H:%M:%S')] Error: Failed to set owner for $CHROOT_DIR/home" >> "$LOG_FILE"
    exit 14
fi
if ! chmod 755 "$CHROOT_DIR/home" >> "$LOG_FILE" 2>&1; then
    echo "[$(date '+%Y-%m-%d %H:%M:%S')] Error: Failed to set permissions for $CHROOT_DIR/home" >> "$LOG_FILE"
    exit 15
fi

# Добавление /etc/passwd и /etc/group
echo "[$(date '+%Y-%m-%d %H:%M:%S')] Setting up /etc/passwd and /etc/group in chroot" >> "$LOG_FILE"
if ! mkdir -p "$CHROOT_DIR/etc" >> "$LOG_FILE" 2>&1; then
    echo "[$(date '+%Y-%m-%d %H:%M:%S')] Error: Failed to create $CHROOT_DIR/etc" >> "$LOG_FILE"
    exit 16
fi
if [[ ! -f "$CHROOT_DIR/etc/passwd" ]]; then
    touch "$CHROOT_DIR/etc/passwd" >> "$LOG_FILE" 2>&1 || {
        echo "[$(date '+%Y-%m-%d %H:%M:%S')] Error: Failed to create $CHROOT_DIR/etc/passwd" >> "$LOG_FILE"
        exit 17
    }
fi
if [[ ! -f "$CHROOT_DIR/etc/group" ]]; then
    touch "$CHROOT_DIR/etc/group" >> "$LOG_FILE" 2>&1 || {
        echo "[$(date '+%Y-%m-%d %H:%M:%S')] Error: Failed to create $CHROOT_DIR/etc/group" >> "$LOG_FILE"
        exit 18
    }
fi

# Настройка bash.bashrc для перехода в домашнюю папку
echo "[$(date '+%Y-%m-%d %H:%M:%S')] Configuring bash.bashrc in chroot" >> "$LOG_FILE"
cat > "$CHROOT_DIR/etc/bash.bashrc" << EOL
cd /home/\$USER
export HOME=/home/\$USER
export PATH=/bin:/usr/bin
EOL
if [[ $? -ne 0 ]]; then
    echo "[$(date '+%Y-%m-%d %H:%M:%S')] Error: Failed to configure bash.bashrc" >> "$LOG_FILE"
    exit 19
fi

# Парсинг списка команд
IFS=',' read -r -a COMMANDS <<< "$COMMAND_LIST"

# Добавление команд в chroot
echo "[$(date '+%Y-%m-%d %H:%M:%S')] Adding commands to chroot" >> "$LOG_FILE"
for cmd in "${COMMANDS[@]}"; do
    cmd_path=$(which "$cmd" 2>/dev/null)
    if [[ -z "$cmd_path" ]]; then
        echo "[$(date '+%Y-%m-%d %H:%M:%S')] Error: Command $cmd not found" >> "$LOG_FILE"
        exit 20
    fi
    if ! jk_cp -v -j "$CHROOT_DIR" "$cmd_path" >> "$LOG_FILE" 2>&1; then
        echo "[$(date '+%Y-%m-%d %H:%M:%S')] Error: Failed to add $cmd to chroot" >> "$LOG_FILE"
        exit 21
    fi
    echo "[$(date '+%Y-%m-%d %H:%M:%S')] Added $cmd ($cmd_path) to chroot" >> "$LOG_FILE"
done

# Добавление команды groups
echo "[$(date '+%Y-%m-%d %H:%M:%S')] Adding groups command to chroot" >> "$LOG_FILE"
if ! jk_cp -v -j "$CHROOT_DIR" /usr/bin/groups >> "$LOG_FILE" 2>&1; then
    echo "[$(date '+%Y-%m-%d %H:%M:%S')] Error: Failed to add groups to chroot" >> "$LOG_FILE"
    exit 22
fi

# Проверка bash и библиотек
echo "[$(date '+%Y-%m-%d %H:%M:%S')] Verifying bash and libraries in chroot" >> "$LOG_FILE"
if [[ ! -x "$CHROOT_DIR/bin/bash" ]]; then
    if ! jk_cp -v -j "$CHROOT_DIR" /bin/bash >> "$LOG_FILE" 2>&1; then
        echo "[$(date '+%Y-%m-%d %H:%M:%S')] Error: Failed to copy bash to chroot" >> "$LOG_FILE"
        exit 23
    fi
fi
for lib in $(ldd /bin/bash | grep -o '/lib[^ ]*' | sort -u); do
    if [[ ! -f "$CHROOT_DIR$lib" ]]; then
        cp "$lib" "$CHROOT_DIR$lib" >> "$LOG_FILE" 2>&1 || {
            echo "[$(date '+%Y-%m-%d %H:%M:%S')] Error: Failed to copy $lib to chroot" >> "$LOG_FILE"
            exit 24
        }
    fi
done

# Перезапуск SSH
echo "[$(date '+%Y-%m-%d %H:%M:%S')] Restarting SSH service" >> "$LOG_FILE"
if ! systemctl restart ssh >> "$LOG_FILE" 2>&1; then
    echo "[$(date '+%Y-%m-%d %H:%M:%S')] Error: Failed to restart SSH service" >> "$LOG_FILE"
    exit 25
fi

# Проверка статуса SSH
if systemctl is-active --quiet ssh; then
    echo "[$(date '+%Y-%m-%d %H:%M:%S')] SSH service is active" >> "$LOG_FILE"
else
    echo "[$(date '+%Y-%m-%d %H:%M:%S')] Error: SSH service is not active" >> "$LOG_FILE"
    exit 26
fi

# Проверка конфигурации SSH
if ! sshd -t >> "$LOG_FILE" 2>&1; then
    echo "[$(date '+%Y-%m-%d %H:%M:%S')] Error: SSH configuration test failed" >> "$LOG_FILE"
    exit 27
fi

echo "[$(date '+%Y-%m-%d %H:%M:%S')] SSH and Jailkit setup completed successfully" >> "$LOG_FILE"

exit 0