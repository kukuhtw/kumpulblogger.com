#!/bin/sh
set -eu

while true; do
    php /var/www/html/bin/run-scheduled-jobs.php
    sleep 60
done
