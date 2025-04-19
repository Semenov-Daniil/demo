#!/bin/bash

if [ -z "$1" ] || [ -z "$2" ] || [ -z "$3" ]; then
    echo "Error: directory path and  are required" >&2
    exit 1
fi

DIR_PATH="$1"
OWNER="$2"
GROUP="$3"

mkdir -p "$DIR_PATH"
if [ $? -ne 0 ]; then
    echo "Error: Failed to create directory $DIR_PATH"
    exit 2
fi

chmod 755 "$DIR_PATH"
chown "$OWNER:$GROUP" "$DIR_PATH"
if [ $? -ne 0 ]; then
    echo "Error: Failed to set owner $OWNER and group $GROUP for $DIR_PATH"
    exit 3
fi

exit 0