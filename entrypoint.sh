#!/bin/bash
set -e 

if [ -z "$(ls -A /var/template 2>/dev/null)" ]; then
    echo "Initing base template..."
    cp -rv /var/template_init/* /var/template
fi

php /var/www/html/init.php

exec apache2-foreground