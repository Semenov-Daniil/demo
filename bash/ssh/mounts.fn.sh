#!/bin/bash
# mounts.fn.sh - Скрипт вспомогательных функций монтирования
# Расположение: bash/ssh/mounts.fn.sh

set -euo pipefail

# Проверка, что скрипт не запущен напрямую
[[ "${BASH_SOURCE[0]}" == "$0" ]] && {
    echo "This script ('$0') is meant to be sourced"
    exit 1
}

: "${MOUNT_UNIT_DIR:="/etc/systemd/system"}"
: "${UNIT_START_TIMEOUT:=15}"
: "${LOCK_SYSTEMD_FILE:="/tmp/lock_systemd_unit.lock"}"

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
    unit_name=$(systemd-escape -p --suffix=mount "$path" 2>/dev/null) || {
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
        [[ "$current_hash" == "$desired_hash" ]] && return "$EXIT_SUCCESS"
    }

    printf '%s\n' "$unit_content" > "$unit_path" 2>/dev/null || {
        log_message "error" "Failed to write systemd unit file '$unit_path'" >&2
        return "$EXIT_SYSTEMD_UNIT"
    }

    chmod 664 "$unit_path" 2>/dev/null || {
        log_message "error" "Failed to set permissions on '$unit_path'" >&2
        delete_systemd_unit "$unit_title"
        return "$EXIT_SYSTEMD_UNIT"
    }

    systemctl daemon-reload >/dev/null 2>&1 || {
        log_message "error" "Failed to reload systemd daemon" >&2
        delete_systemd_unit "$unit_title"
        return "$EXIT_SYSTEMD_UNIT"
    }

    _start_systemd_unit_locked "$unit_title" || {
        _remove_systemd_unit_locked "$unit_title" "$unit_path"
        return "$EXIT_SYSTEMD_UNIT"
    }

    return "$EXIT_SUCCESS"
}

# Запуск systemd unit
# Usage: start_systemd_unit <unit-title>
start_systemd_unit() {
    local unit_title="$1"

    is_active_unit "$unit_title" && return "${EXIT_SUCCESS}"

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

    return "$EXIT_SUCCESS"
}

# Удаление systemd unit
# Usage: remove_systemd_unit <unit-title>
remove_systemd_unit() {
    local unit_title="$1" 
    with_lock "$LOCK_SYSTEMD_FILE" _remove_systemd_unit_locked "$unit_title"
    return $?
}

_remove_systemd_unit_locked() {
    local unit_title="$1" unit_path="$(path_to_unit "$unit_title")"

    systemctl disable --now "$unit_title" >/dev/null 2>&1 || true

    [[ -f "$unit_path" ]] && {
        rm -f "$unit_path" >/dev/null 2>&1
        systemctl daemon-reload >/dev/null 2>&1
    }

    return "$EXIT_SUCCESS"
}

# Получение содержания mount unit
# Usage: get_unit_content <what> <where> [<type>] [<options>] [<requires>]
get_unit_content() {
    local src="$1" dest="$2" type="${3:-auto}" options="${4:-}"  requires="${5:-}"
    local after_line="" requires_line="" binds_to_line="" options_line=""

    [[ -n "$options" ]]  && options_line="Options=$options" 
    [[ -n "$requires" ]] && requires_line="Requires=$requires" && binds_to_line="BindsTo=$requires" && after_line+=" $requires"

    echo "[Unit]"
    echo "Description=Mount of source ${src} to destination path ${dest}"
    [[ -n "$requires" ]] && echo "Requires=$requires"
    [[ -n "$requires" ]] && echo "BindsTo=$requires"
    echo -n "After=local-fs.target"
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

    return "${EXIT_SUCCESS}"
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

# Монтирование директории /dev
# Usage: mount_devtmpfs <where> [<requires>]
mount_devtmpfs() {
    local dest="$1" req="${2:-}"

    mount_unit "devtmpfs" "$dest" "devtmpfs" "mode=0700,nosuid,noexec,gid=0" "$req"
    return $?
}

# Монтирование директории /dev/pts
# Usage: mount_devpts <where> [<requires>]
mount_devpts() {
    local dest="$1" req="${2:-}"

    mount_unit "devpts" "$dest" "devpts" "nosuid,noexec,newinstance,ptmxmode=0666,mode=0620" "$req"
    return $?
}

# Монтирование proc
# Usage: mount_proc <where> [<requires>]
mount_proc() {
    local dest="$1" req="${2:-}"

    mount_unit "proc" "$dest" "proc" "nosuid,noexec,nodev,hidepid=2,gid=0,noatime,nodiratime" "$req"
    return $?
}

# Монтирование sys
# Usage: mount_sys <where> [<requires>]
mount_sys() {
    local dest="$1" req="${2:-}"

    mount_unit "sysfs" "$dest" "sysfs" "nosuid,noexec,nodev,ro,noatime,nodiratime" "$req"
    return $?
}

# Монтирование run
# Usage: mount_run <where> [<requires>]
mount_run() {
    local dest="$1" req="${2:-}"

    mount_unit "tmpfs" "$dest" "tmpfs" "nosuid,noexec,nodev,mode=0755" "$req"
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

    rm -rf "$merged/*" "$workdir/*" 2>/dev/null

    mount_unit "overlay" "$merged" "overlay" "lowerdir=$lowerdir,upperdir=$upperdir,workdir=$workdir" "$req"
    return $?
}

# Генерация списка mount unit внутри директории
get_mount_units_in_dir() {
    local dir="$1" mounts unit unit_names=()

    mapfile -t mounts < <(systemctl list-units --type=mount --no-legend --no-pager | awk '{print $1}' | grep -v '^-')

    for unit in "${mounts[@]}"; do
        mount_point=$(systemctl show -p Where --value "$unit")
        if [[ -n "$mount_point" && "$mount_point" == "$dir"* ]]; then
            unit_names+=("$unit")
        fi
    done

    echo "${unit_names[*]}"
}

export -f title_mount_unit path_to_unit is_active_unit create_systemd_unit _create_systemd_unit_locked start_systemd_unit _start_systemd_unit_locked remove_systemd_unit _remove_systemd_unit_locked get_unit_content mount_unit mount_bind mount_rbind mount_devtmpfs mount_devpts mount_proc mount_sys mount_overlay get_mount_units_in_dir
return 0