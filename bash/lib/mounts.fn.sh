#!/bin/bash
# mounts.fn.sh - Скрипт вспомогательных функций монтирования
# Расположение: bash/chroot/mounts.fn.sh

set -euo pipefail

# Проверка, что скрипт не запущен напрямую
[[ "${BASH_SOURCE[0]}" == "$0" ]] && {
    echo "This script ('$0') is meant to be sourced"
    exit 1
}

: "${TMP_DIR:="/tmp"}"

declare -rx MOUNT_UNIT_DIR="/etc/systemd/system"
declare -x LOCK_SYSTEMD_FILE="$TMP_DIR/lock_systemd_unit.lock"
declare -x UNIT_START_TIMEOUT=15

if [[ ! -d "$MOUNT_UNIT_DIR" || ! -w "$MOUNT_UNIT_DIR" ]]; then
    log_message "error" "Directory '$MOUNT_UNIT_DIR' is not accessible or writable"
    return "$EXIT_SYSTEMD_UNIT"
fi

# Генерация названия mount unit
title_mount_unit() {
    local path="$1" unit_name
    path=$(realpath -m "$path") || {
        log_message "error" "Failed to resolve real path for '$path'"
        exit "$EXIT_SYSTEMD_UNIT"
    }
    unit_name="$(systemd-escape -p --suffix=mount "$path" 2>/dev/null)" || {
        log_message "error" "Failed to escape path '$path' for systemd unit" >&2
        exit "$EXIT_SYSTEMD_UNIT"
    }
    [[ -n "$unit_name" ]] || {
        log_message "error" "Empty unit name for path '$path'" >&2
        exit "$EXIT_SYSTEMD_UNIT"
    }
    echo "$unit_name"
}

# Генерация пути к unit файлу
path_to_unit() {
    local title="$1"
    [[ -n "$title" ]] || {
        log_message "error" "Empty unit title provided" >&2
        exit "$EXIT_SYSTEMD_UNIT"
    }
    echo "$MOUNT_UNIT_DIR/$title"
}

# Проверка активен ли unit
is_active_unit() {
    local unit="$1"
    systemctl is-active --quiet "$unit"
    return $?
}

# Создание systemd unit
# Usage: create_systemd_unit <unit-title> <unit-content>
create_systemd_unit() {
    local unit_title="$1" unit_content="$2"
    with_lock "$LOCK_SYSTEMD_FILE" _create_systemd_unit_locked "$unit_title" "$unit_content"
    return $?
}

_create_systemd_unit_locked() {
    local unit_title="$1" unit_content="$2" unit_path="$(path_to_unit "$unit_title")"
    local current_content="" desired_hash=""

    [[ -f "$unit_path" ]] && current_content=$(<"$unit_path")

    desired_hash="$(printf '%s' "$unit_content" | cksum | cut -d' ' -f1)" || {
        log_message "error" "Failed to compute hash for content '$unit_title'"
        return "$EXIT_SYSTEMD_UNIT"
    }

    [[ -n "$current_content" ]] && {
        local current_hash="$(printf '%s' "$current_content" | cksum | cut -d' ' -f1)" || {
            log_message "error" "Failed to compute hash for current content '$unit_path'"
            return "$EXIT_SYSTEMD_UNIT"
        }
        [[ "$current_hash" == "$desired_hash" ]] && {
            _start_systemd_unit_locked "$unit_title" || {
                _remove_systemd_unit_locked "$unit_title" "$unit_path"
                return "$EXIT_SYSTEMD_UNIT"
            }
            return 0
        }
    }

    printf '%s\n' "$unit_content" > "$unit_path" 2>/dev/null || {
        log_message "error" "Failed to write systemd unit file '$unit_path'" >&2
        return "$EXIT_SYSTEMD_UNIT"
    }

    chmod 664 "$unit_path" 2>/dev/null || {
        log_message "error" "Failed to set permissions on '$unit_path'" >&2
        _remove_systemd_unit_locked "$unit_title"
        return "$EXIT_SYSTEMD_UNIT"
    }

    systemctl daemon-reload >/dev/null 2>&1 || {
        log_message "error" "Failed to reload systemd daemon" >&2
        _remove_systemd_unit_locked "$unit_title"
        return "$EXIT_SYSTEMD_UNIT"
    }

    _start_systemd_unit_locked "$unit_title" || {
        _remove_systemd_unit_locked "$unit_title" "$unit_path"
        return "$EXIT_SYSTEMD_UNIT"
    }

    return 0
}

# Запуск systemd unit
# Usage: start_systemd_unit <unit-title>
start_systemd_unit() {
    local unit_title="$1"
    is_active_unit "$unit_title" && return
    with_lock "$LOCK_SYSTEMD_FILE" _start_systemd_unit_locked "$unit_title"
    return $?
}

_start_systemd_unit_locked() {
    local unit_title="$1"

    systemd-analyze verify "$unit_title" >/dev/null 2>&1 || {
        log_message "error" "Incorrect syntax unit '$unit_title'" >&2
        return "$EXIT_SYSTEMD_UNIT"
    }

    systemctl enable --now "$unit_title" >/dev/null 2>&1 || {
        log_message "error" "Failed to enable or start systemd unit '$unit_title'" >&2
        return "$EXIT_SYSTEMD_UNIT"
    }

    local start_time=$(date +%s) timeout="$UNIT_START_TIMEOUT"
    while ! $(is_active_unit "$unit_title"); do
        [[ $(( $(date +%s) - start_time )) -gt "$timeout" ]] && {
            log_message "error" "Timeout waiting for systemd unit '$unit_title' to become active"
            return "$EXIT_SYSTEMD_UNIT"
        }
        sleep 0.1
    done

    return 0
}

# Удаление systemd unit
# Usage: remove_systemd_unit <unit-title>
remove_systemd_unit() {
    local unit_title="$1" 
    with_lock "$LOCK_SYSTEMD_FILE" _remove_systemd_unit_locked "$unit_title"
    return $?
}

_remove_systemd_unit_locked() {
    local unit_title="$1"

    local related_units=($(systemctl list-dependencies --reverse --plain --no-pager "$unit_title" | grep -o '[^= ]*\.service\|[^= ]*\.mount' | grep -v '^-' | sort -u))

    local unit unit_file
    for unit in "${related_units[@]}"; do
        systemctl disable --now "$unit" >/dev/null || true
        unit_file="$(systemctl show -p FragmentPath "$unit" | cut -d= -f2)"
        [ -f "$unit_file" ] && rm -f "$unit_file" >/dev/null
    done

    systemctl daemon-reload >/dev/null

    return 0
}

# Получение содержания mount unit
# Usage: get_unit_content <what> <where> [<type>] [<options>] [<requires>]
get_unit_content() {
    local src="$1" dest="$2" type="${3:-auto}" options="${4:-"defaults"}"  requires="${5:-}"

    echo "[Unit]"
    echo "Description=Mount of source ${src} to destination path ${dest}"
    [[ -n "$requires" ]] && echo "Requires=$requires"
    [[ -n "$requires" ]] && echo "BindsTo=$requires"
    echo -n "After=local-fs-pre.target"
    [[ -n "$requires" ]] && echo " $requires" || echo
    echo "Before=local-fs.target"
    echo "AssertPathExists=${dest}"
    echo "DefaultDependencies=no"

    echo
    echo "[Mount]"
    echo "What=$src"
    echo "Where=$dest"
    echo "Type=$type"
    [[ -n "$options" ]] && echo "Options=$options"

    echo
    echo "[Install]"
    echo "WantedBy=multi-user.target"
}

# Монтирование c созданием и запуском mount unit
# Usage: mount_unit <what> <where> [<type>] [<options>] [<requires>]
mount_unit() {
    local src="$1" dest="$2" type="${3:-"auto"}" opts="${4:-}" req="${5:-}"

    if [[ ! -e "${dest}" ]]; then
        log_message "error" "Destination '${dest}' does not exist"
        return "${EXIT_MOUNT_FAILED}"
    fi

    local unit_title="$(title_mount_unit "$dest")"
    local unit_content="$(get_unit_content "$src" "$dest" "$type" "$opts" "$req")"

    create_systemd_unit "$unit_title" "$unit_content" || return $?

    return 0
}

# Монтирование bind
# Usage: mount_bind <what> <where> [<options>] [<requires>]
mount_bind() {
    local src="$1" dest="$2" opts="${3:-}" req="${4:-}"
    local bind_opts="bind"

    [[ -n "$opts" ]] && bind_opts+=",$opts"

    if [[ ! -e "${src}" ]]; then
        log_message "error" "Source '${src}' does not exist"
        return "${EXIT_MOUNT_FAILED}"
    fi

    mount_unit "$src" "$dest" "none" "$bind_opts" "$req"
    return $?
}

# Монтирование rbind
# Usage: mount_rbind <what> <where> [<options>] [<requires>]
mount_rbind() {
    local src="$1" dest="$2" opts="${3:-}" req="${4:-}"
    local rbind_opts="rbind"

    [[ -n "$opts" ]] && rbind_opts+=",$opts"

    if [[ ! -e "${src}" ]]; then
        log_message "error" "Source '${src}' does not exist"
        return "${EXIT_MOUNT_FAILED}"
    fi

    mount_unit "$src" "$dest" "none" "$rbind_opts" "$req"
    return $?
}

# Монтирование директории devtmpfs
# Usage: mount_devtmpfs <where> [<requires>]
mount_devtmpfs() {
    local dest="$1" req="${2:-}"

    mount_unit "devtmpfs" "$dest" "devtmpfs" "mode=0755,nosuid" "$req"
    return $?
}

# Монтирование директории devpts
# Usage: mount_devpts <where> [<requires>]
mount_devpts() {
    local dest="$1" req="${2:-}"

    mount_unit "devpts" "$dest" "devpts" "gid=5,mode=620,ptmxmode=666" "$req"
    return $?
}

# Монтирование tmpfs
# Usage: mount_tmpfs <where> [<options>] [<requires>]
mount_tmpfs() {
    local dest="$1" opt="${2:-}" req="${3:-}"

    mount_unit "tmpfs" "$dest" "tmpfs" "$opt" "$req"
    return $?
}

# Монтирование proc
# Usage: mount_proc <where> [<requires>]
mount_proc() {
    local dest="$1" req="${2:-}"

    mount_unit "proc" "$dest" "proc" "nosuid,noexec,nodev,hidepid=2,gid=0,noatime,nodiratime" "$req"
    return $?
}

# Монтирование overlay
# Usage: mount_overlay <lowerdir> <upperdir> <workdir> <merged> [<requires>]
mount_overlay() {
    local lowerdir="$1" upperdir="$2" workdir="$3" merged="$4" req="${5:-}"

    local dir missing=0
    for dir in "$lowerdir" "$upperdir" "$workdir" "$merged"; do
        [[ -d "$dir" ]] || { log_message "error" "Directory '$dir' does not exist"; missing=1; }
    done
    [[ $missing -eq 1 ]] && return "$EXIT_MOUNT_FAILED"

    rm -rf "$workdir/*" 2>/dev/null

    mount_unit "overlay" "$merged" "overlay" "lowerdir=$lowerdir,upperdir=$upperdir,workdir=$workdir" "$req"
    return $?
}

# Монтирование rslave
# Usage: mount_rslave <target> <requires>
mount_rslave() {
    local target="$1" requires="$2"
    [[ -z "$target" ]] && { log_message "error" "No systemd unit mount rslave service target provided"; return "$EXIT_INVALID_ARG"; }
    [[ -z "$requires" ]] && { log_message "error" "No systemd unit mount rslave requires provided"; return "$EXIT_INVALID_ARG"; }

    if [[ ! -e "$target" ]]; then
        log_message "error" "Destination '$target' does not exist"
        return "$EXIT_MOUNT_FAILED"
    fi

    local unit_title="rslave-$(systemd-escape -p --suffix=service "$target" 2>/dev/null)" || {
        log_message "error" "Failed to escape path '$target' for systemd unit" >&2
        exit "$EXIT_SYSTEMD_UNIT"
    }

    local unit_content=$(cat << EOF
[Unit]
Description=Rslave Mount Service for $target
Requires=$requires
BindsTo=$requires
After=network.target
Before=local-fs.target
AssertPathExists=$target
DefaultDependencies=no

[Service]
Type=oneshot
ExecStart=/bin/mount --make-rslave $target
RemainAfterExit=yes
StandardOutput=journal

[Install]
WantedBy=multi-user.target
EOF
)

    create_systemd_unit "$unit_title" "$unit_content" || return $?

    return 0
}

# Генерация списка mount unit внутри директории
get_mount_units() {
    local dir="$1"
    local mounts unit unit_names=()

    mapfile -t mounts < <(systemctl list-units --type=mount --no-legend --no-pager | awk '{print $1}' | grep -v '^-')

    for unit in "${mounts[@]}"; do
        mount_point=$(systemctl show -p Where --value "$unit")
        if [[ -n "$mount_point" && "$mount_point" == "$dir"* ]]; then
            unit_names+=("$unit")
        fi
    done

    echo "${unit_names[*]}"
}

export -f title_mount_unit path_to_unit is_active_unit create_systemd_unit _create_systemd_unit_locked start_systemd_unit _start_systemd_unit_locked remove_systemd_unit _remove_systemd_unit_locked get_unit_content mount_unit mount_bind mount_rbind mount_devtmpfs mount_devpts mount_tmpfs mount_proc mount_overlay get_mount_units mount_rslave
return 0