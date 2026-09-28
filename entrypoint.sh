#!/bin/bash
set -e 

if [ -z "$(ls -A /var/template 2>/dev/null)" ]; then
    echo "Initing base template..."
    cp -rv /var/template_init/* /var/template
fi

php /var/www/html/init.php

# Zeitgesteuerte Aufgaben (Wochenbericht): Der Container hat keinen cron -
# cron.php prüft selbst, ob etwas fällig ist, und darf beliebig oft laufen.
(
    while true; do
        php /var/www/html/cron.php || true
        sleep 900
    done
) &

exec apache2-foreground