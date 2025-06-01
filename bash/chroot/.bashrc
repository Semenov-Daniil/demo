set -o functrace > /dev/null 2>&1

# Checking for interactive mode
[[ $- != *i* ]] && return

# Re-execution check .bashrc
[ -z "${BASHRC_LOADED:-}" ] && export BASHRC_LOADED=1 || return

# Environment variables
declare -rx HOME="/home/$USER"
declare -rx WORKSPACE="$HOME$WORKSPACE_USERS"
declare -rx PATH="/usr/bin:/bin"
declare -rx SHELL="/bin/bash"
declare -rx PAGER="cat"

# History variables
declare -x HISTSIZE=1000
declare -x HISTFILESIZE=2000
declare -x HISTTIMEFORMAT="%Y-%m-%d %H:%M:%S "
declare -x HISTCONTROL="ignoredups"

# Resource constraints
ulimit -t 10
ulimit -f 100000
ulimit -u 50

# Switching to the workspace
cd "$WORKSPACE" || {
    echo "Failed to change to $WORKSPACE"
    exit 1
}

# PS1
declare -rx PS1="\[\033[01;32m\]\u@\h\[\033[00m\]:\[\033[01;34m\]\w\[\033[00m\]\$ "

# Welcome message
cat << 'INNER_EOF'

██████╗ ███████╗███╗   ███╗ ██████╗    ██████╗ ██╗   ██╗
██╔══██╗██╔════╝████╗ ████║██╔═══██╗   ██╔══██╗██║   ██║
██║  ██║█████╗  ██╔████╔██║██║   ██║   ██████╔╝██║   ██║
██║  ██║██╔══╝  ██║╚██╔╝██║██║   ██║   ██╔══██╗██║   ██║
██████╔╝███████╗██║ ╚═╝ ██║╚██████╔╝██╗██║  ██║╚██████╔╝
╚═════╝ ╚══════╝╚═╝     ╚═╝ ╚═════╝ ╚═╝╚═╝  ╚═╝ ╚═════╝

INNER_EOF

# Logging
USER_NAME=${USER:-$(id -u)}
declare -rx LOGFILE="$HOME/$USER_NAME.log"

[ ! -f "$LOGFILE" ] && touch "$LOGFILE" 2>/dev/null

if [ -n "$SSH_CLIENT" ]; then
    IP=${SSH_CLIENT%% *}
else
    IP="local"
fi
IP=${IP:-"unknown"}

# Last login
last_login="$(grep "\[LOGIN\]" "$LOGFILE" | tail -n 2 | head -n 1 | sed 's/\[LOGIN\] //g')"
[ ! -z "$last_login" ] && echo "Last login: $last_login"

# Logging login
if [ -z "${LOGIN_LOGGED:-}" ]; then
    echo "[LOGIN] $(date '+%Y-%m-%d %H:%M:%S') | User: $USER_NAME | IP: $IP" >> "$LOGFILE"
    export LOGIN_LOGGED=1
fi

# Logging logout
trap 'echo "[LOGOUT] $(date "+%Y-%m-%d %H:%M:%S") | User: $USER_NAME | IP: $IP" >> "$LOGFILE"' EXIT

# Clear history
history -c
> "$HOME/.bash_history"

command_not_found_handle() (
    echo "Command not found '$1'"
)

[[ -f "$HOME/.bash-preexec.sh" ]] || {
    echo "Failed to source to preexec"
    exit 1
}

BLACKLIST_CHAR=(';' '&' '|' '`' '$(' '${' '>' '<' '!' '*' '?' '\\' '"' "'" '&&' '||' '(' '{')
BLACKLIST_COMMAND=('exec' 'bash' 'sh' 'sudo' 'su' 'mount' 'umount')

source "$HOME/.bash-preexec.sh" || {
    echo "Failed to source to preexec"
    exit 1
}

is_subpath() {
    local path=$1
    local abs_path
    abs_path=$(realpath -m "$path" 2>/dev/null) || return 1
    [[ "$abs_path" == "$WORKSPACE"* ]] && return 0 || return 1
}

# Checked command
preexec() {
    local cmd=$1

    read -ra cmd_a <<< "$cmd"
    local cmd_name="${cmd_a[0]}"
    local args="${cmd_a[@]:1}"

    for char in "${BLACKLIST_CHAR[@]}"; do
        if [[ "$cmd" =~ "$char" ]]; then
            echo "Forbidden character: $char"
            echo "[DENY] $(date "+%F %T") | $USER_NAME | PWD: $PWD | CMD: $cmd" >> "$LOGFILE";
            kill -SIGINT $$
        fi
    done

    for black_cmd in "${BLACKLIST_COMMAND[@]}"; do
        if [[ "$cmd_name" == "$black_cmd" || "$cmd_name" == $(command -v "$black_cmd") ]]; then
            echo "Forbidden command: $cmd_name"
            echo "[DENY] $(date "+%F %T") | $USER_NAME | PWD: $PWD | CMD: $cmd" >> "$LOGFILE";
            kill -SIGINT $$
        fi
    done

    is_subpath "$cmd_name" || {
        echo "Forbidden path: $cmd_name"
        echo "[DENY] $(date "+%F %T") | $USER_NAME | PWD: $PWD | CMD: $cmd" >> "$LOGFILE";
        kill -SIGINT $$
    }

    for arg in $args; do
        [[ "$arg" =~ ^- ]] && continue

        [[ "$arg" =~ ^~ ]] && arg="${arg/#~/$HOME}"
        [[ "$arg" =~ "\$HOME" ]] && arg="${arg//"\$HOME"/$HOME}"

        if ! is_subpath "$arg"; then
            arg=$(realpath -m "$arg" 2>/dev/null)
            echo "Forbidden path: $arg"
            echo "[DENY] $(date "+%F %T") | $USER_NAME | PWD: $PWD | CMD: $cmd" >> "$LOGFILE";
            kill -SIGINT $$
        fi
    done

    echo "[CMD] $(date "+%F %T") | $USER_NAME | PWD: $PWD | CMD: $cmd" >> "$LOGFILE";

    return 0
}

# Checked pwd
check_pwd() {
    is_subpath "$PWD" || cd "$WORKSPACE" 2>/dev/null
}

precmd_functions+=(check_pwd)
