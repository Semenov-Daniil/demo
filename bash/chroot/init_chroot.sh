#!/bin/bash
# init_chroot.sh - Скрипт инициализации chroot-окружения пользователя
# Расположение: bash/chroot/init_chroot.sh

set -euo pipefail

# Подключение локального config.sh
LOCAL_CONFIG="$(realpath $(dirname "${BASH_SOURCE[0]}")/config.sh)"
source "$LOCAL_CONFIG" || {
    echo "Failed to source local config '$LOCAL_CONFIG'" >&2
    exit 1
}

cleanup() {
    local exit_code=$?
    [[ $exit_code -eq 0 ]] && return
    bash "$REMOVE_CHROOT"
}

trap cleanup SIGINT SIGTERM EXIT

# Функция инициализации chroot-окружения
init_chroot() {
    [[ -d "$BASE_CHROOT" ]] && {
        log_message "warning" "Chroot '$BASE_CHROOT' already exists"
        bash "$REMOVE_CHROOT" || return $?
    }

    log_message "info" "Starting chroot initialization '$BASE_CHROOT'"

    create_directories "$BASE_CHROOT" 755 root:root || return $?

    local CHROOT_OVERLAY="$BASE_CHROOT/overlay" 
    local OVERLAY_UPPER="$CHROOT_OVERLAY/upper" OVERLAY_WORK="$CHROOT_OVERLAY/work"

    create_directories "${SYSTEM_DIRS[@]/#/$OVERLAY_UPPER}" "${SYSTEM_DIRS[@]/#/$OVERLAY_WORK}" "${SYSTEM_DIRS[@]/#/$CHROOT_ROOT}" "$CHROOT_ROOT/dev" "$CHROOT_ROOT/proc" "$CHROOT_ROOT/etc" 755 root:root || return $?

    local path
    for path in "${SYSTEM_DIRS[@]}"; do
        mount_overlay "$path" "${OVERLAY_UPPER}${path}" "${OVERLAY_WORK}${path}" "${CHROOT_ROOT}${path}" || return $?
    done

    mount_bind "/etc" "$CHROOT_ROOT/etc" "ro" || return $?

    local dev="$CHROOT_ROOT/dev"
    mount_devtmpfs "$dev" || return $?
    local unit_dev="$(title_mount_unit "$dev")"

    create_directories "$dev/pts" "$dev/shm" 755 root:root || return $?
    mount_devpts "$dev/pts" "$unit_dev" || return $?
    mount_tmpfs "$dev/shm" "mode=1777" "$unit_dev" || return $?

    mount_proc "$CHROOT_ROOT/proc" || return $?

    create_directories "$CHROOT_ROOT/tmp" 1777 root:root || return $?

    restrict_binaries "$CHROOT_ROOT" "${RESTRICTED_CMDS[@]}" || return $?

    log_message "ok" "Chroot '$BASE_CHROOT' initialized successfully"
    return 0
}

# Инициализация chroot-окружения с временной блокировкой
with_lock "$TMP_DIR/$LOCK_CHROOT_PREF.lock" init_chroot
exit $?