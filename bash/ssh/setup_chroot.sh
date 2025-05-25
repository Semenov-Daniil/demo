#!/bin/bash
# setup_chroot.sh - Скрипт для настройки chroot
# Расположение: bash/ssh/setup_chroot.sh

set -euo pipefail

# Проверка, что скрипт не запущен напрямую
[[ "${BASH_SOURCE[0]}" == "$0" ]] && {
    echo "This script ('$0') is meant to be sourced" >&2
    exit 1
}

# Обновление шаблона bash_profile
update_bash_profile() {
    local profile_hash file_hash=""
    profile_hash=$(printf '%s' "${USER_BASH_PROFILE}" | cksum | cut -d' ' -f1) || {
        log_message "error" "Failed to compute hash for USER_BASH_PROFILE"
        return "${EXIT_CHROOT_INIT_FAILED}"
    }
    [[ -f "${TEMPLATE_PROFILE}" ]] && file_hash=$(cksum "${TEMPLATE_PROFILE}" | cut -d' ' -f1) || :
    
    if [[ "${profile_hash}" != "${file_hash}" ]]; then
        printf "%s" "${USER_BASH_PROFILE}" > "${TEMPLATE_PROFILE}" || {
            log_message "error" "Failed to create or write to the file bash_profile '${TEMPLATE_PROFILE}'"
            return "${EXIT_CHROOT_INIT_FAILED}"
        }
    fi
    return "$EXIT_SUCCESS"
}

# Проверка и настройка chroot
setup_chroot() {
    local dir perms owner
    for dir in "$BASE_CHROOT" "$CHROOT_SYSTEM" "$CHROOT_STUDENTS" "$CHROOT_TEMPLATE"; do
        read -r perms owner < <(stat -c '%a %U:%G' "$dir" 2>/dev/null || echo "none none:none")
        if [[ "$perms" != "755" || "$owner" != "root:root" ]]; then
            if [[ "$perms" == "none" ]]; then
                create_directories "$dir" 755 root:root || return $?
            else
                update_permissions "$dir" 755 root:root || return $?
            fi
        fi
    done
    return "$EXIT_SUCCESS"
}

# Основная логика
setup_chroot || return $?
update_bash_profile || return $?

return "$EXIT_SUCCESS"