#!/bin/sh
set -eu

APP_PORT="${PORT:-8080}"

sed -i "s/^Listen .*/Listen ${APP_PORT}/" /etc/apache2/ports.conf
sed -i "s/<VirtualHost \*:[0-9][0-9]*>/<VirtualHost *:${APP_PORT}>/" /etc/apache2/sites-available/000-default.conf

for directory in uploads ai_images banner_mini voice JSON logs; do
    mkdir -p "/var/www/html/public_html/${directory}"
done

# A single persistent mount is shared by Railway, Render, Zeabur, and other
# Docker platforms. Railway supplies its mount path automatically; elsewhere
# set PERSISTENT_DATA_ROOT to the mounted directory (normally /data).
DATA_ROOT="${RAILWAY_VOLUME_MOUNT_PATH:-${PERSISTENT_DATA_ROOT:-}}"
if [ -n "${DATA_ROOT}" ]; then
    mkdir -p "${DATA_ROOT}"
    for directory in uploads ai_images banner_mini voice JSON logs; do
        target="${DATA_ROOT}/${directory}"
        source="/var/www/html/public_html/${directory}"
        mkdir -p "${target}"
        if [ -d "${source}" ] && [ ! -L "${source}" ] && [ -z "$(ls -A "${target}" 2>/dev/null)" ]; then
            cp -a "${source}/." "${target}/" 2>/dev/null || true
        fi
        rm -rf "${source}"
        ln -s "${target}" "${source}"
    done
fi

if [ "${RUN_DB_PREPARE:-0}" = "1" ]; then
    /usr/local/bin/myadnetwork-db-prepare
fi

# Generate all legacy provider identity files after persistent volumes have
# been mounted. This runs for every Docker-based one-click deployment.
php /var/www/html/bin/sync-provider-data.php

if [ "${PROCESS_TYPE:-web}" = "cron" ]; then
    exec /usr/local/bin/myadnetwork-cron-loop
fi

exec "$@"
