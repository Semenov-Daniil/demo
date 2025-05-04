#!/bin/bash
# remove_chroot.sh - Функция для удаления chroot-окружения
# Расположение: bash/ssh/remove_chroot.sh

set -euo pipefail

# Проверка, что скрипт не запущен напрямую
if [[ "${BASH_SOURCE[0]}" == "$0" ]]; then
    echo "This script ('$0') is meant to be sourced" >&2
    return 1
fi

# Установка переменных по умолчанию
: "${MOUNT_DIRS:=()}"
: "${MOUNT_FILES:=()}"
: "${CHROOT_STUDENTS:=/var/chroot/students}"
: "${EXIT_SUCCESS:=0}"
: "${EXIT_GENERAL_ERROR:=1}"
: "${EXIT_INVALID_ARG:=5}"
: "${EXIT_MOUNT_FAILED:=6}"
: "${EXIT_CHROOT_INIT_FAILED:=7}"

# Очистка монтирований
cleanup_mounts() {
    local student_chroot="$1"
    local failed_mounts=()

    for dir in "${MOUNT_DIRS[@]}"; do
        if mountpoint -q "$student_chroot/$dir" 2>/dev/null; then
            if ! umount "$student_chroot/$dir" 2>/dev/null; then
                failed_mounts+=("$student_chroot/$dir")
                umount -l "$student_chroot/$dir" 2>/dev/null || true
            fi
        fi
    done

    for file in "${MOUNT_FILES[@]}"; do
        if mountpoint -q "$student_chroot/$file" 2>/dev/null; then
            if ! umount "$student_chroot/$file" 2>/dev/null; then
                failed_mounts+=("$student_chroot/$file")
                umount -l "$student_chroot/$file" 2>/dev/null || true
            fi
        fi
    done

    if [[ ${#failed_mounts[@]} -gt 0 ]]; then
        log_message "error" "Failed to unmount: ${failed_mounts[*]}"
        return ${EXIT_MOUNT_FAILED}
    fi
}

# Удаление записей из /etc/fstab
clean_fstab() {
    local chroot_dir="$1"
    local fstab_file="/etc/fstab"
    local temp_fstab="/tmp/fstab.$$"

    # Создаем временный файл без строк, содержащих chroot_dir
    cp "$fstab_file" "$temp_fstab" || {
        log_message "error" "Failed to copy $fstab_file to $temp_fstab"
        return ${EXIT_GENERAL_ERROR}
    }

    # Экранируем специальные символы в chroot_dir для sed
    chroot_dir_escaped=$(echo "$chroot_dir" | sed 's/[\/&]/\\&/g')
    sed -i "/${chroot_dir_escaped}/d" "$temp_fstab" || {
        log_message "error" "Failed to remove fstab entries containing $chroot_dir"
        return ${EXIT_GENERAL_ERROR}
    }

    # Заменяем оригинальный fstab
    mv "$temp_fstab" "$fstab_file" || {
        log_message "error" "Failed to update $fstab_file"
        return ${EXIT_GENERAL_ERROR}
    }
}

# Удаление chroot-директории
remove_chroot_dir() {
    local student_chroot="$1"

    if [[ -d "$student_chroot" ]]; then
        rm -rf "$student_chroot" || {
            log_message "error" "Failed to remove $chroot_dir"
            return ${EXIT_GENERAL_ERROR}
        }
    fi
}

# Проверка активности пользователя и завершение его сеансов
check_and_terminate_user() {
    local username="$1"
    local user_processes

    # Проверяем, есть ли активные процессы пользователя
    if user_processes=$(pgrep -u "$username" 2>/dev/null); then
        if ! pkill -u "$username" 2>/dev/null; then
            log_message "warning" "Some processes for $username could not be terminated gracefully"
            pkill -9 -u "$username" 2>/dev/null || {
                log_message "error" "Failed to terminate processes for $username"
                return ${EXIT_GENERAL_ERROR}
            }
        fi

        sleep 1

        if pgrep -u "$username" >/dev/null 2>&1; then
            log_message "error" "Some processes for $username are still running after termination attempt"
            return ${EXIT_GENERAL_ERROR}
        fi
    fi
}

# Основная функция удаления chroot-окружения
# remove_chroot username
remove_chroot() {
    local username="$1"

    if [[ ! "$username" =~ ^[a-zA-Z0-9._-]+$ ]]; then
        log_message "error" "Invalid username: $username"
        return ${EXIT_INVALID_ARG}
    fi

    if ! id "$username" &>/dev/null; then
        log_message "error" "User '$username' does not exist"
        return ${EXIT_INVALID_ARG}
    fi

    local student_chroot="${CHROOT_STUDENTS}/${username}"

    log_message "info" "Starting chroot removal for $username"

    # Проверка активности пользователя и завершение его сеансов
    check_and_terminate_user "$username" || return $?

    # Проверка существования chroot
    if [[ ! -d "$student_chroot" ]]; then
        log_message "info" "Chroot directory $student_chroot does not exist"
        return ${EXIT_SUCCESS}
    fi

    # Размонтирование всех директорий
    cleanup_mounts "$student_chroot" || {
        log_message "error" "Failed to unmount directories in $student_chroot"
        return ${EXIT_MOUNT_FAILED}
    }

    # Удаление записей из /etc/fstab
    clean_fstab "$student_chroot" || {
        log_message "error" "Failed to remove fstab entries for $student_chroot"
        return ${EXIT_CHROOT_INIT_FAILED}
    }

    # Удаление chroot-директории
    remove_chroot_dir "$student_chroot" || {
        log_message "error" "Failed to remove chroot directory $student_chroot"
        return ${EXIT_GENERAL_ERROR}
    }

    log_message "info" "Chroot for $username removed successfully"
    return ${EXIT_SUCCESS}
}

# Экспорт функций
export -f remove_chroot
export -f check_and_terminate_user
export -f remove_chroot_dir
export -f clean_fstab
export -f cleanup_mounts

return ${EXIT_SUCCESS}
