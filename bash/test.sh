#!/bin/bash

LOGGING_SCRIPT="$(dirname "${BASH_SOURCE[0]}")/logging/logging.fn.sh"
LOG_FILE="$(basename "${BASH_SOURCE[0]}" .sh).log"

TEST_ARR=("1" "2" "3" "4" "5")

asd=${TEST_ARR[@]}

echo "TEST_ARR: ${TEST_ARR[*]}"
echo "asd: ${asd[*]}"

exit 0

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