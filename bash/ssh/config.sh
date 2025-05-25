#!/bin/bash
# config.sh - Локальный конфигурационный файл для скриптов настройки ssh и создания/удаления chroot-окружения
# Расположение: bash/ssh/config.sh

set -euo pipefail

# Проверкa подключения скрипта
[[ "${BASH_SOURCE[0]}" == "$0" ]] && {
    echo "This script ('$0') is meant to be sourced" >&2
    exit 1
}

# Подключение глобального config.sh
source "$(dirname "${BASH_SOURCE[0]}")/../config.sh" || {
    echo "Failed to source global config.sh" >&2
    return 1
}

# Коды выхода
declare -rx EXIT_MOUNT_FAILED=10
declare -rx EXIT_CHROOT_INIT_FAILED=11
declare -rx EXIT_SSH_CONFIG_FAILED=12
declare -rx EXIT_SSH_SERVICE=13
declare -rx EXIT_SYSTEMD_UNIT=14

# Установка переменных
declare -x DEFAULT_LOG_FILE="ssh.log"

declare -rx SSH_CONFIG_FILE="/etc/ssh/sshd_config"
declare -rx SSH_BACKUP_CONFIG="/etc/ssh/sshd_config.conf.bak"
declare -rx SSH_CONFIGS_DIR="/etc/ssh/sshd_config.d"
declare -rx SSH_CONFIG_FILE_STUDENTS="${SSH_CONFIGS_DIR}/students.conf"
declare -rx SSH_BACKUP_CONFIG_STUDENTS="${SSH_CONFIGS_DIR}/students.conf.bak"

declare -rx REMOVE_CHROOT_FN="$(dirname "${BASH_SOURCE[0]}")/remove_chroot.fn.sh"
declare -rx SETUP_CONFIG_SSH="$(dirname "${BASH_SOURCE[0]}")/setup_config_ssh.sh"
declare -rx SETUP_CHROOT="$(dirname "${BASH_SOURCE[0]}")/setup_chroot.sh"
declare -rx MOUNTS_FN="$(dirname "${BASH_SOURCE[0]}")/mounts.fn.sh"

declare -rx LOCK_SSH_PREF="lock_ssh"
declare -rx LOCK_SYSTEMD_FILE="${TMP_DIR}/${LOCK_SSH_PREF}_systemd_chroot.lock"
declare -rx LOCK_CHROOT_STUDENTS_FILE="${TMP_DIR}/${LOCK_SSH_PREF}_students_chroot.lock"

declare -rx CONFIG_HASH_FILE="${TMP_DIR}/ssh_config_hash"
declare -rx SSH_CONFIG_HASH_MAIN="${TMP_DIR}/ssh_config_hash_main"
declare -rx SSH_CONFIG_HASH_STUDENTS="${TMP_DIR}/ssh_config_hash_student"

declare -rx BASE_CHROOT="/srv/chroot"
declare -rx CHROOT_SYSTEM="${BASE_CHROOT}/system"
declare -rx CHROOT_STUDENTS="${BASE_CHROOT}/students"
declare -rx CHROOT_TEMPLATE="${BASE_CHROOT}/templates"
declare -rx TEMPLATE_PROFILE="$CHROOT_TEMPLATE/bash_profile"
declare -axr SYSTEM_DIRS=("/bin" "/lib" "/lib64" "/usr" "/etc")
declare -rx LOG_USER_ACTIVE="/user_active.log"

declare -rx MOUNT_UNIT_DIR="/etc/systemd/system"
declare -x UNITS_SYSTEM=""
declare -x UNIT_START_TIMEOUT=15

generate_user_bash_profile() {
    cat <<EOF
declare -rx PS1='\u@\h:\w\$ '
declare -rx HOME=/home/$USER
declare -rx PATH=/usr/bin:/bin
declare -rx PAGER=cat
declare -rx HISTFILE="$HOME$LOG_USER_ACTIVE"
declare -rx HISTSIZE=1000
declare -rx HISTFILESIZE=2000
declare -rx HISTCONTROL=ignoredups
declare -rx BASH_ENV=/etc/bash_profile

LOGFILE="\$HISTFILE"
echo "[LOGIN] \$(date '+%F %T') | User: $USER | IP: \$(who | awk '{print \$5}')" >> "\$LOGFILE"
declare -rx PROMPT_COMMAND='RETRN_VAL=\$?; echo "[CMD] \$(date "+%F %T") | $USER | \$(whoami) | PWD: \$PWD | CMD: \$BASH_COMMAND" >> "\$LOGFILE";'

# Blocking exit from $HOME/$USER
declare -rx WORK="$HOME/$USER"
cd_func() {
    local target
    if [[ -z "\$1" ]]; then
        target="\$WORK"
    else
        target=\$(realpath -m "\$WORK/\$1" 2>/dev/null || echo "\$1")
    fi
    if [[ "\$target" == "\$WORK" || "\$target" == "\$WORK/"* ]]; then
        builtin cd "\$target"
    else
        echo "Forbidden path: \$1"
        builtin cd "\$WORK"
    fi
}
alias cd=cd_func

restrict_navigation() {
    echo "Forbidden path: \$1"
}
alias /='restrict_navigation'
alias pushd='restrict_navigation'
alias popd='restrict_navigation'
alias builtin='restrict_navigation'
alias vi='vi "\$WORK"/*'
alias vim='vim "\$WORK"/*'
alias nano='nano "\$WORK"/*'

# Welcome message
cat << 'INNER_EOF'

██████╗ ███████╗███╗   ███╗ ██████╗    ██████╗ ██╗   ██╗
██╔══██╗██╔════╝████╗ ████║██╔═══██╗   ██╔══██╗██║   ██║
██║  ██║█████╗  ██╔████╔██║██║   ██║   ██████╔╝██║   ██║
██║  ██║██╔══╝  ██║╚██╔╝██║██║   ██║   ██╔══██╗██║   ██║
██████╔╝███████╗██║ ╚═╝ ██║╚██████╔╝██╗██║  ██║╚██████╔╝
╚═════╝ ╚══════╝╚═╝     ╚═╝ ╚═════╝ ╚═╝╚═╝  ╚═╝ ╚═════╝ 
               
INNER_EOF
echo "Last login: \$(grep "\[LOGIN\]" "\$LOGFILE" | tail -n 2 | head -n 1 | sed 's/\[LOGIN\] //g' || echo "No previous login info available")"
echo "Добро пожаловать $USER! Ваша рабочая директория: \$WORK"

cd "\$WORK"

EOF
}
declare -rx USER_BASH_PROFILE=$(generate_user_bash_profile)

generate_match_group_student() {
    cat <<EOF
Match Group ${STUDENT_GROUP}
    ChrootDirectory ${CHROOT_STUDENTS}/%u/root
    ForceCommand /bin/rbash --norc --noprofile --rcfile /etc/bash_profile
    X11Forwarding no
    AllowTcpForwarding no
    PasswordAuthentication yes
EOF
}
declare -rx MATCH_GROUP_STUDENT=$(generate_match_group_student)

get_config_hash() {
    local input=""
    [[ -f "${SSH_CONFIG_FILE}" ]] && input+=$(cat "${SSH_CONFIG_FILE}")
    [[ -f "${SSH_CONFIG_FILE_STUDENTS}" ]] && input+=$(cat "${SSH_CONFIG_FILE_STUDENTS}")
    input+="${MATCH_GROUP_STUDENT}"
    printf '%s' "${input}" | cksum | cut -d' ' -f1
}

# Настройка конфигурации SSH
setup_config_ssh() {
    local current_hash=$(get_config_hash) || {
        log_message "error" "Failed to compute config hash SSH"
        return ${EXIT_SSH_CONFIG_FAILED}
    }
    local saved_hash=""
    [[ -f "$CONFIG_HASH_FILE" ]] && saved_hash=$(cat "$CONFIG_HASH_FILE") || saved_hash=""
    if [[ "$current_hash" != "$saved_hash" ]]; then
        log_message "info" "Configuration SSH changed or hash file missing"
        source_script "$SETUP_CONFIG_SSH" || return $?
    fi
    return ${EXIT_SUCCESS}
}

with_lock "${TMP_DIR}/${LOCK_SSH_PREF}_setup_config.lock" setup_config_ssh || return $?

export -f get_config_hash
return ${EXIT_SUCCESS}