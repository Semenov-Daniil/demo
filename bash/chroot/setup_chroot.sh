#!/bin/bash
# setup_chroot.sh - Скриптов настройки chroot-окружения
# Расположение: bash/chroot/setup_chroot.sh

set -euo pipefail

# Подключение локального config.sh
LOCAL_CONFIG="$(realpath $(dirname "${BASH_SOURCE[0]}")/config.sh)"
source "$LOCAL_CONFIG" || {
    echo "Failed to source local config '$LOCAL_CONFIG'" >&2
    exit 1
}

declare -xr BASHRC="$(dirname "${BASH_SOURCE[0]}")/.bashrc"
declare -xr BASH_PREEXEC="$(dirname "${BASH_SOURCE[0]}")/.bash-preexec.sh"

# Checked bashrc
check_bashrc() {
    local bashrc_cnt=$(envsubst '${WORKSPACE_USERS}'  < "$BASHRC") || {
        log_message "error" "Failed to process template '$BASHRC'"
        return "$EXIT_GENERAL_ERROR"
    }

    [[ -f "$ETC_BASHRC" ]] && {
        cmp -s <(printf '%s' "$bashrc_cnt") "$ETC_BASHRC" && {
            update_permissions "$ETC_BASHRC" 755 root:root || return $?
            return 0
        }
    }

    log_message "info" "Configuration bashrc changed"
    printf '%s' "$bashrc_cnt" > "$ETC_BASHRC" || {
        log_message "error" "Failed to write bashrc '$ETC_BASHRC'"
        return "$EXIT_BASH_FAILED"
    }

    update_permissions "$ETC_BASHRC" 755 root:root || return $?
    return 0
}

# Checked bash-preexec
check_bash_preexec() {
    [[ -f "$ETC_BASH_PREEXEC" ]] && {
        update_permissions "$ETC_BASH_PREEXEC" 755 root:root || return $?
        cmp -s "$BASH_PREEXEC" "$ETC_BASH_PREEXEC" && return 0
    }

    log_message "info" "Configuration bash-preexec changed"
    printf '%s' $(<"$BASH_PREEXEC") > "$ETC_BASH_PREEXEC" || {
        log_message "error" "Failed to write '$ETC_BASH_PREEXEC'" >&2
        return "$EXIT_BASH_FAILED"
    }

    update_permissions "$ETC_BASH_PREEXEC" 755 root:root || return $?
    return 0
}

# Checked chroot
check_chroot() {
    [[ ! -d "$BASE_CHROOT" || ! -d "$CHROOT_ROOT" ]] && {
        bash "$INIT_CHROOT" || return $?
    }
    return 0
}

check_bashrc || exit $?
check_bash_preexec || exit $?
check_chroot || exit $?
restrict_binaries "$CHROOT_ROOT" "${RESTRICTED_CMDS[@]}" || exit $?

exit 0