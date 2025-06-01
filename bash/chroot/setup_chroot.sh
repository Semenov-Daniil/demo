#!/bin/bash
# setup_chroot.sh - Скриптов настройки chroot-окружения
# Расположение: bash/chroot/setup_chroot.sh

set -euo pipefail

# Подключение логального config.sh
LOCAL_CONFIG="$(realpath $(dirname "${BASH_SOURCE[0]}")/config.sh)"
source "$LOCAL_CONFIG" || {
    echo "Failed to source local config.sh '$LOCAL_CONFIG'" >&2
    exit 1
}

# Checked bashrc
check_bashrc() {
    local desired_hash="$(printf '%s' "$BASHRC_CNT" | cksum | cut -d' ' -f1)" || {
        log_message "error" "Failed to compute hash for content '$BASHRC'"
        return "$EXIT_BASH_FAILED"
    }

    local current_hash=""
    [[ -f "$ETC_BASHRC" ]] && current_hash="$(printf '%s' "$(<$ETC_BASHRC)" | cksum | cut -d' ' -f1)" || current_hash=""

    if [[ "$desired_hash" != "$current_hash" ]]; then
        log_message "info" "Configuration bashrc changed"
        printf '%s\n' "$BASHRC_CNT" > "$ETC_BASHRC" || {
            log_message "error" "Failed to write bashrc '$ETC_BASHRC'" >&2
            return "$EXIT_BASH_FAILED"
        }
    fi

    return 0
}

# Checked bash-preexec
check_bash_preexec() {
    local desired_hash="$(printf '%s' "$(<$BASH_PREEXEC)" | cksum | cut -d' ' -f1)" || {
        log_message "error" "Failed to compute hash for content '$BASH_PREEXEC'"
        return "$EXIT_BASH_FAILED"
    }

    local current_hash=""
    [[ -f "$ETC_BASH_PREEXEC" ]] && current_hash="$(printf '%s' "$(<$ETC_BASH_PREEXEC)" | cksum | cut -d' ' -f1)" || current_hash=""

    if [[ "$desired_hash" != "$current_hash" ]]; then
        log_message "info" "Configuration bash-preexec changed"
        printf '%s\n' "$(<$BASH_PREEXEC)" > "$ETC_BASH_PREEXEC" || {
            log_message "error" "Failed to write '$ETC_BASH_PREEXEC'" >&2
            return "$EXIT_BASH_FAILED"
        }
    fi

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