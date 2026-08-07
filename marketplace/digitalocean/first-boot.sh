#!/bin/sh
set -eu

state_dir=/var/lib/myadnetwork
credentials_file=/root/.myadnetwork_credentials
install -m 0700 -d "$state_dir"

get_public_ip() {
    curl -fsS --max-time 10 \
      http://169.254.169.254/metadata/v1/interfaces/public/0/ipv4/address \
      || hostname -I | awk '{print $1}'
}

public_ip=$(get_public_ip)
if [ -z "$public_ip" ]; then
    echo "Alamat IP publik tidak dapat ditemukan." >&2
    exit 1
fi

admin_password=$(openssl rand -hex 12)

cd /opt/myadnetwork
NON_INTERACTIVE=1 \
FORCE_INSTALL=1 \
APP_NAME=MyAdNetwork \
APP_BIND_ADDRESS=0.0.0.0 \
APP_HTTP_PORT=80 \
DOMAIN_NAME="$public_ip" \
KCE_APP_URL="http://${public_ip}/kce" \
ADMIN_EMAIL=admin@example.com \
ADMIN_NAME=Administrator \
ADMIN_WHATSAPP=- \
ADMIN_PASSWORD="$admin_password" \
./install/vps-install.sh

cat > "$credentials_file" <<EOF
MyAdNetwork URL: http://${public_ip}/
Admin URL: http://${public_ip}/admin/login.php
Admin email: admin@example.com
Admin password: ${admin_password}

Segera ganti email/password admin dan pasang domain dengan HTTPS.
Panduan: /opt/myadnetwork/docs/operations/DIGITALOCEAN_MARKETPLACE.md
EOF
chmod 0600 "$credentials_file"
touch "$state_dir/first-boot-complete"
