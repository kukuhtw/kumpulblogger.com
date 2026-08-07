#!/bin/sh
set -eu

umask 077

ROOT_DIR=$(CDPATH= cd -- "$(dirname -- "$0")/.." && pwd)
cd "$ROOT_DIR"

if [ ! -f Dockerfile ] || [ ! -f docker-compose.yml ]; then
    echo "Jalankan installer dari repository MyAdNetwork yang lengkap." >&2
    exit 1
fi

for command_name in docker openssl; do
    if ! command -v "$command_name" >/dev/null 2>&1; then
        echo "Perintah '$command_name' belum tersedia. Lihat docs/operations/VPS_INSTALLATION.md." >&2
        exit 1
    fi
done
if ! docker compose version >/dev/null 2>&1; then
    echo "Docker Compose v2 belum tersedia." >&2
    exit 1
fi
if ! docker info >/dev/null 2>&1; then
    echo "Docker daemon tidak dapat diakses. Jalankan sebagai user anggota group docker atau gunakan sudo." >&2
    exit 1
fi

if [ -f .env ] && [ "${FORCE_INSTALL:-0}" != "1" ]; then
    echo "File .env sudah ada. Installer dihentikan agar konfigurasi tidak tertimpa." >&2
    echo "Gunakan FORCE_INSTALL=1 hanya jika memang ingin membuat konfigurasi baru." >&2
    exit 2
fi
if [ -f .env ]; then
    backup_file=".env.backup.$(date +%Y%m%d%H%M%S)"
    cp .env "$backup_file"
    echo "Konfigurasi lama dicadangkan ke $backup_file"
fi

prompt_value() {
    variable_name=$1
    prompt_text=$2
    default_value=$3
    current_value=$(eval "printf '%s' \"\${$variable_name:-}\"")
    if [ -n "$current_value" ]; then
        printf '%s' "$current_value"
        return
    fi
    if [ "${NON_INTERACTIVE:-0}" = "1" ]; then
        printf '%s' "$default_value"
        return
    fi
    printf '%s [%s]: ' "$prompt_text" "$default_value" >&2
    IFS= read -r entered_value
    printf '%s' "${entered_value:-$default_value}"
}

APP_NAME_VALUE=$(prompt_value APP_NAME "Nama platform" "MyAdNetwork")
DOMAIN_NAME_VALUE=$(prompt_value DOMAIN_NAME "Domain tanpa https://" "localhost")
APP_HTTP_PORT_VALUE=$(prompt_value APP_HTTP_PORT "Port aplikasi lokal" "8080")
APP_BIND_ADDRESS_VALUE=${APP_BIND_ADDRESS:-127.0.0.1}
ADMIN_EMAIL_VALUE=$(prompt_value ADMIN_EMAIL "Email admin pertama" "admin@example.com")
ADMIN_NAME_VALUE=$(prompt_value ADMIN_NAME "Nama admin" "Administrator")
ADMIN_WHATSAPP_VALUE=$(prompt_value ADMIN_WHATSAPP "Nomor WhatsApp admin" "-")
KCE_APP_URL_VALUE=${KCE_APP_URL:-https://${DOMAIN_NAME_VALUE}/kce}

case "$ADMIN_EMAIL_VALUE" in
    *@*.*) ;;
    *) echo "Email admin tidak valid." >&2; exit 64 ;;
esac
case "$APP_HTTP_PORT_VALUE" in
    ''|*[!0-9]*) echo "Port aplikasi harus berupa angka." >&2; exit 64 ;;
esac

random_secret() {
    openssl rand -hex "$1"
}

DB_PASSWORD_VALUE=${DB_PASSWORD:-$(random_secret 24)}
DB_ROOT_PASSWORD_VALUE=${DB_ROOT_PASSWORD:-$(random_secret 32)}
ADMIN_PASSWORD_VALUE=${ADMIN_PASSWORD:-$(random_secret 12)}
KCE_TRACKING_SECRET_VALUE=${KCE_TRACKING_SECRET:-$(random_secret 32)}
COMPOSE_PROJECT_NAME_VALUE=${COMPOSE_PROJECT_NAME:-myadnetwork}
DB_DATABASE_VALUE=${DB_DATABASE:-myadnetwork_db}
DB_USERNAME_VALUE=${DB_USERNAME:-myadnetwork}

case "$DB_DATABASE_VALUE:$DB_USERNAME_VALUE:$COMPOSE_PROJECT_NAME_VALUE" in
    *[!a-zA-Z0-9_:.-]*) echo "Nama database, user, dan project hanya boleh berisi huruf, angka, _, titik, atau tanda hubung." >&2; exit 64 ;;
esac

escape_env() {
    printf '%s' "$1" | sed 's/\\/\\\\/g; s/"/\\"/g'
}

cat > .env <<EOF
COMPOSE_PROJECT_NAME="$(escape_env "$COMPOSE_PROJECT_NAME_VALUE")"
APP_NAME="$(escape_env "$APP_NAME_VALUE")"
APP_BIND_ADDRESS="$(escape_env "$APP_BIND_ADDRESS_VALUE")"
APP_HTTP_PORT="$(escape_env "$APP_HTTP_PORT_VALUE")"
DB_EXPOSED_PORT="127.0.0.1:3307"
DB_HOST="db"
DB_PORT="3306"
DB_DATABASE="$(escape_env "$DB_DATABASE_VALUE")"
DB_USERNAME="$(escape_env "$DB_USERNAME_VALUE")"
DB_PASSWORD="$(escape_env "$DB_PASSWORD_VALUE")"
DB_ROOT_PASSWORD="$(escape_env "$DB_ROOT_PASSWORD_VALUE")"
DOMAIN_NAME="$(escape_env "$DOMAIN_NAME_VALUE")"
PROVIDER_NAME="$(escape_env "${PROVIDER_NAME:-$APP_NAME_VALUE}")"
PROVIDER_DOMAIN_URL="$(escape_env "${PROVIDER_DOMAIN_URL:-}")"
PROVIDER_CONTACT_EMAIL="$(escape_env "${PROVIDER_CONTACT_EMAIL:-$ADMIN_EMAIL_VALUE}")"
PROVIDER_CONTACT_WHATSAPP="$(escape_env "${PROVIDER_CONTACT_WHATSAPP:-$ADMIN_WHATSAPP_VALUE}")"
KCE_APP_URL="$(escape_env "$KCE_APP_URL_VALUE")"
KCE_TRACKING_SECRET="$(escape_env "$KCE_TRACKING_SECRET_VALUE")"
SMTP_API_KEY=""
SMTP_API_SECRET=""
RECAPTCHA_SITE_KEY=""
RECAPTCHA_SECRET=""
PAYMENT_INFO=""
LLM_MODEL="gpt-4.1-mini"
OPENAI_API_KEY=""
REPLICATE_API_KEY=""
LLM_MAX_TOKENS="2048"
LLM_TEMPERATURE="0.70"
OPENROUTER_API_KEY=""
OPENROUTER_MODEL="nvidia/nemotron-nano-12b-v2-vl:free"
NVIDIA_API_KEY=""
NVIDIA_EMBEDDING_MODEL="nvidia/nemotron-3-embed-1b"
EOF
chmod 600 .env

echo "Membangun dan menjalankan MyAdNetwork..."
docker compose up -d --build

echo "Menunggu aplikasi siap..."
attempt=0
until docker compose exec -T web php -r 'exit(@file_get_contents("http://127.0.0.1:8080/health.php") === false ? 1 : 0);' >/dev/null 2>&1; do
    attempt=$((attempt + 1))
    if [ "$attempt" -ge 60 ]; then
        echo "Aplikasi belum sehat setelah 120 detik. Periksa: docker compose logs" >&2
        exit 1
    fi
    sleep 2
done

docker compose exec -T \
    -e ADMIN_EMAIL="$ADMIN_EMAIL_VALUE" \
    -e ADMIN_PASSWORD="$ADMIN_PASSWORD_VALUE" \
    -e ADMIN_WHATSAPP="$ADMIN_WHATSAPP_VALUE" \
    -e ADMIN_NAME="$ADMIN_NAME_VALUE" \
    web php bin/create-admin.php

cat <<EOF

Instalasi selesai.
URL lokal : http://127.0.0.1:${APP_HTTP_PORT_VALUE}
Login admin: http://127.0.0.1:${APP_HTTP_PORT_VALUE}/admin/login.php
Admin     : ${ADMIN_EMAIL_VALUE}
Password  : ${ADMIN_PASSWORD_VALUE}

Simpan password di password manager; installer tidak menyimpannya di .env.
Lanjutkan TLS, reverse proxy, dan cron mengikuti:
docs/operations/VPS_INSTALLATION.md
EOF
