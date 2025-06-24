#!/bin/bash

STUDENTS=('aehzbedy' 'fpigdmma' 'indmbyyk' 'urylvxpu' 'zxrvbchr')
 
for student in "${STUDENTS[@]}"; do
        # bash "/var/www/demo/bash/system/delete_user.sh" "$student"
        # bash "/var/www/demo/bash/utils/umount.sh" "/var/www/demo/students/$student/_assets"
        # rm -rf "/var/www/demo/students/$student/"
        # sleep 1
        rm /etc/apache2/sites-available/$student-*
    # (
    # ) &
done

# for i in {1..50}; do ./script.sh & done
wait
