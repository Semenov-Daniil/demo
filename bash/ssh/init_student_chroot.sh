#!/bin/bash
# init_student_chroot.sh - Скрипт инициализации chroot-окружения студента
# Расположение: bash/ssh/init_student_chroot.sh

set -euo pipefail

# Подключение логального config.sh
LOCAL_CONFIG="$(dirname "${BASH_SOURCE[0]}")/config.sh"
source "$LOCAL_CONFIG" || {
    echo "Failed to source script $LOCAL_CONFIG" >&2
    exit 1
}

# Подключение скрипта удаления chroot-окружения
source "$REMOVE_CHROOT" || {
    echo "Failed to source script $REMOVE_CHROOT" >&2
    exit ${EXIT_GENERAL_ERROR}
}

# Очистка
cleanup() {
    exit_code=$?
    
    if [[ $exit_code -eq 0 ]] || [[ $exit_code -eq $EXIT_INVALID_ARG ]]; then
        return
    fi

    remove_chroot ${USERNAME}
}

# Создание базовых директорий
create_basic_dirs() {
    local chroot_dir="$1"
    local chroot_base_dirs=()
    if declare -p CHROOT_BASE_DIRS &>/dev/null; then
        chroot_base_dirs=(${CHROOT_BASE_DIRS[@]})
    fi

    for dir in "${chroot_base_dirs[@]}"; do
        [[ -z "$dir" ]] && continue
        install -d -m 755 -o root -g root "$chroot_dir/$dir" || {
            log_message "error" "Failed to create basic directories in $chroot_dir/$dir"
            exit ${EXIT_CHROOT_INIT_FAILED}
        }
    done

    [[ ! -d "$chroot_dir/tmp" ]] && {
        log_message "error" "Failed to create basic directories in $chroot_dir/tmp"
        exit ${EXIT_CHROOT_INIT_FAILED}
    }

    chmod 1777 "$chroot_dir/tmp" || {
        log_message "error" "Failed to set permissions for $chroot_dir/tmp"
        exit ${EXIT_CHROOT_INIT_FAILED}
    }
}

# Монтирование read-only
mount_bind_ro() {
    local src="$1"
    local dest="$2"

    mount --bind "$src" "$dest" 2>/dev/null 2>/dev/null || {
        log_message "error" "Failed to bind mount $src to $dest"
        exit ${EXIT_MOUNT_FAILED}
    }

    mount -o remount,ro,bind "$dest" 2>/dev/null 2>/dev/null || {
        log_message "error" "Failed to set read-only mount for $dest"
        exit ${EXIT_MOUNT_FAILED}
    }

    FSTAB_ENTRY+=("$src $dest none bind,ro 0 0")
}

# Создание и монтирование фалов
mount_chroot_files() {
    local chroot_dir="$1"
    local mount_files=()
    if declare -p MOUNT_FILES &>/dev/null; then
        mount_files=(${MOUNT_FILES[@]})
    fi

    for path in "${mount_files[@]}"; do
        [[ -z "$path" ]] && continue

        [[ ! -e "$path" ]] && {
            log_message "error" "Path '$path' does not exist"
            exit ${EXIT_CHROOT_INIT_FAILED}
        }

        touch "$chroot_dir$path" || {
            log_message "error" "Failed to create $chroot_dir/$path"
            exit ${EXIT_CHROOT_INIT_FAILED}
        }

        local real_path=$path

        [[ -L "$path" ]] && {
            real_path=$(readlink -f "$path")
            if [ -z "$real_path" ]; then
                log_message "error" "Failed to resolve symbolic link '$path'"
                exit ${EXIT_CHROOT_INIT_FAILED}
            fi
        }

        mount_bind_ro "$real_path" "$chroot_dir$real_path"
    done
}

# Синхронизация файлов /etc
sync_etc_files() {
    local username="$1"
    local target_etc="$2/etc"

    install -d -m 755 -o root -g root "$target_etc" || {
        log_message "error" "Failed to create $target_etc"
        exit ${EXIT_CHROOT_INIT_FAILED}
    }

    getent passwd "$username" > "$target_etc/passwd" || {
        log_message "error" "Failed to create $target_etc/passwd"
        exit ${EXIT_CHROOT_INIT_FAILED}
    }

    getent group "$STUDENT_GROUP" > "$target_etc/group" || {
        log_message "error" "Failed to create $target_etc/group"
        exit ${EXIT_CHROOT_INIT_FAILED}
    }

    config_content=$(cat <<EOF
export PS1='\u@\h:\w\\\$ '
export HOME=/tmp
export PATH=/bin:/usr/bin:/usr/local/bin
export PAGER=cat
cd /home
unset CDPATH
EOF
)

    echo "$config_content" > "$target_etc/bash.bashrc" || {
        log_message "error" "Failed to create $target_etc/bash.bashrc"
        exit ${EXIT_CHROOT_INIT_FAILED}
    }
}

# Настройка устройств
create_devices() {
    local chroot_dev="$1/dev"

    if [[ -d "$chroot_dev" ]]; then
        install -d -m 755 -o root -g root "$chroot_dev" || {
            log_message "error" "Failed to create $chroot_dev"
            exit ${EXIT_CHROOT_INIT_FAILED}
        }
    fi

    mount --bind /dev "$chroot_dev" 2>/dev/null || {
        log_message "error" "Failed to bind mount /dev to $chroot_dev"
        exit ${EXIT_MOUNT_FAILED}
    }

    mount -o remount,bind "$chroot_dev" 2>/dev/null || {
        log_message "error" "Failed to remount $chroot_dev"
        exit ${EXIT_MOUNT_FAILED}
    }

    FSTAB_ENTRY+=("/dev $chroot_dev none bind 0 0")
}

# Настройка /proc с ограничениями
mount_proc() {
    local chroot_proc="$1/proc"
    mount -t proc proc "$chroot_proc" -o hidepid=2,noexec,nosuid 2>/dev/null || {
        log_message "error" "Failed to mount proc to $chroot_proc with hidepid=2,noexec,nosuid"
        exit ${EXIT_MOUNT_FAILED}
    }

    FSTAB_ENTRY+=("proc $chroot_proc proc defaults,hidepid=2,noexec,nosuid 0 0")
}

# Монтирование домашней директории
mount_bind() {
    local src="$1"
    local dest="$2"
    mount --bind "$src" "$dest" 2>/dev/null || {
        log_message "error" "Failed to bind mount $src to $dest"
        exit ${EXIT_MOUNT_FAILED}
    }

    FSTAB_ENTRY+=("$src $dest none bind 0 0")
}

# Настройка /etc/fstab
setup_fstab_entries() {
    local chroot_dir="$1"
    local home_dir="$2"

    for entry in "${FSTAB_ENTRY[@]}"; do
        if ! grep -qs "^${entry%% *} ${entry#* }" /etc/fstab; then
            echo "$entry" >> /etc/fstab || {
                log_message "error" "Failed to add fstab entry: $entry"
                exit ${EXIT_CHROOT_INIT_FAILED}
            }
        fi
    done

    systemctl daemon-reload || {
        log_message "warning" "Failed to execute systemctl daemon-reload"
    }
}

# Основная логика
trap cleanup SIGINT SIGTERM EXIT

# Проверка массива ARGS
if ! declare -p ARGS >/dev/null 2>&1; then
    echo "ARGS array is not defined"
    exit ${EXIT_INVALID_ARG}
fi

# Проверка аргументов
if [[ ${#ARGS[@]} -lt 1 ]]; then
    echo "Usage: $0 <username>"
    exit ${EXIT_INVALID_ARG}
fi

USERNAME="${ARGS[0]}"

if [[ ! "$USERNAME" =~ ^[a-zA-Z0-9._-]+$ ]]; then
    echo "Invalid username: $USERNAME"
    exit ${EXIT_INVALID_ARG}
fi

if ! id "$USERNAME" &>/dev/null; then
    log_message "error" "User '$USERNAME' does not exist"
    exit ${EXIT_INVALID_ARG}
fi

HOME_DIR="$(getent passwd "$USERNAME" | cut -d: -f6)"
STUDENT_CHROOT="${CHROOT_STUDENTS}/${USERNAME}"
FSTAB_ENTRY=()

log_message "info" "Starting chroot initialization for $USERNAME"

# Проверка существования chroot
if [[ -d "$STUDENT_CHROOT" && -d "$STUDENT_CHROOT/etc" ]]; then
    log_message "info" "Chroot directory $STUDENT_CHROOT already exists, checking mounts"
    remove_chroot ${USERNAME}
fi

# Создание chroot-директории
create_directories "$STUDENT_CHROOT" "755" "root:root" || {
    log_message "error" "Failed create directory: ${STUDENT_CHROOT}"
    exit ${EXIT_CHROOT_INIT_FAILED}
}

create_basic_dirs "$STUDENT_CHROOT"
sync_etc_files "$USERNAME" "$STUDENT_CHROOT"
create_devices "$STUDENT_CHROOT"

mount_bind_ro /usr "$STUDENT_CHROOT/usr"
mount_bind_ro /bin "$STUDENT_CHROOT/bin"
mount_bind_ro /lib "$STUDENT_CHROOT/lib"
mount_bind_ro /lib64 "$STUDENT_CHROOT/lib64"
mount_bind "$HOME_DIR" "$STUDENT_CHROOT/home"
mount_proc "$STUDENT_CHROOT"
mount_chroot_files "$STUDENT_CHROOT"

setup_fstab_entries "$STUDENT_CHROOT" "$HOME_DIR"

log_message "info" "Chroot for $USERNAME initialized successfully"

exit ${EXIT_SUCCESS}