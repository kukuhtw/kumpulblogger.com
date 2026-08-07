#!/bin/sh
set -eu

: "${DB_HOST:?DB_HOST wajib diisi}"
: "${DB_USERNAME:?DB_USERNAME wajib diisi}"
: "${DB_PASSWORD:?DB_PASSWORD wajib diisi}"
: "${DB_DATABASE:?DB_DATABASE wajib diisi}"

DB_PORT_VALUE="${DB_PORT:-3306}"
export MYSQL_PWD="${DB_PASSWORD}"

mysql_cmd() {
    mysql --protocol=TCP --host="${DB_HOST}" --port="${DB_PORT_VALUE}" \
        --user="${DB_USERNAME}" --database="${DB_DATABASE}" "$@"
}

attempt=1
until mysql_cmd --execute="SELECT 1" >/dev/null 2>&1; do
    if [ "$attempt" -ge 30 ]; then
        echo "Database tidak tersedia setelah 30 percobaan." >&2
        exit 1
    fi
    echo "Menunggu database (percobaan ${attempt}/30)..."
    attempt=$((attempt + 1))
    sleep 2
done

if ! mysql_cmd --batch --skip-column-names --execute="SHOW TABLES LIKE 'articles'" | grep -qx articles; then
    echo "Database kosong; mengimpor schema utama MyAdNetwork."
    mysql_cmd < /var/www/html/sql/myadnetwork_db_hanya_structure.sql
else
    echo "Schema utama sudah tersedia; import awal dilewati."
fi

echo "Memastikan schema KCE tersedia."
mysql_cmd < /var/www/html/sql/kce_schema.sql
