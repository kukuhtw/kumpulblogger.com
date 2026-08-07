# Docker Deployment

> Navigasi: [Runbook deployment](../OPERATIONS_RUNBOOK.md#3-pilih-target-deployment) · [VPS](./VPS_INSTALLATION.md) · [Scheduler](../OPERATIONS_RUNBOOK.md#7-aktifkan-scheduler) · [Backup](../OPERATIONS_RUNBOOK.md#14-backup-dan-restore)

Docker adalah fondasi deployment MyAdNetwork untuk Railway, Render, Heroku,
dan DigitalOcean. Image menjalankan PHP 8.4 + Apache dengan document root
`public_html/` dan membaca port dari environment variable `PORT`.

Untuk instalasi produksi pertama pada server sendiri, gunakan installer dan
panduan [VPS_INSTALLATION.md](./VPS_INSTALLATION.md).

## Menjalankan secara lokal

Prasyarat: Docker Desktop atau Docker Engine dengan Compose v2.

1. Salin `.env.example` menjadi `.env` dan isi konfigurasi yang diperlukan.
2. Jalankan:

   ```bash
   docker compose up --build
   ```

3. Buka `http://localhost:8080`.
4. Health check tersedia di `http://localhost:8080/health.php`.

MySQL tersedia dari host pada port `3307`. Dari container web, hostname
database adalah `db` dan port-nya `3306`.

Nama dan port dapat diatur tanpa mengedit `docker-compose.yml`:

```dotenv
COMPOSE_PROJECT_NAME=my-blog-network
APP_NAME="My Blog Network"
APP_BIND_ADDRESS=0.0.0.0
APP_HTTP_PORT=8080
DB_DATABASE=my_blog_network
DB_USERNAME=my_blog_user
DB_PASSWORD=replace-with-a-strong-password
DB_ROOT_PASSWORD=replace-with-another-strong-password
DB_EXPOSED_PORT=127.0.0.1:3307
```

`COMPOSE_PROJECT_NAME` dipakai Docker sebagai namespace project dan prefix
resource. `APP_NAME` adalah nama tampilan aplikasi. `DB_DATABASE` harus berupa
nama database MySQL yang valid; jangan memakai spasi atau tanda hubung.

Saat volume database masih kosong, MySQL mengimpor secara berurutan:

- `sql/myadnetwork_db_hanya_structure.sql`
- `sql/kce_schema.sql`

Untuk deployment produksi, gunakan managed MySQL dan jalankan migration secara
terkontrol. Jangan gunakan password contoh dari `docker-compose.yml`.

## Environment variables wajib

- `DB_HOST`
- `DB_USERNAME`
- `DB_PASSWORD`
- `DB_DATABASE`
- `SMTP_API_KEY`
- `SMTP_API_SECRET`
- `DOMAIN_NAME`
- `PROVIDER_NAME` (opsional; default mengikuti `APP_NAME`)
- `PROVIDER_DOMAIN_URL` (opsional; default dibuat dari `DOMAIN_NAME`)
- `RECAPTCHA_SITE_KEY`
- `RECAPTCHA_SECRET`
- `PAYMENT_INFO`

Fitur KCE juga menggunakan:

KCE adalah **Knowledge Commerce Engine**: chat AI dengan semantic retrieval
artikel dan sponsored content, campaign/wallet terpisah, serta tracking
impression/click. Lihat [panduan KCE](../guides/13-knowledge-commerce-engine.md).

- `OPENROUTER_API_KEY`
- `OPENROUTER_MODEL`
- `NVIDIA_API_KEY`
- `NVIDIA_EMBEDDING_MODEL`
- `KCE_APP_URL`
- `KCE_TRACKING_SECRET`

Process environment memiliki prioritas di atas file `.env`, sehingga secret
manager milik platform cloud dapat digunakan tanpa membuat `.env` di image.

## Penyimpanan

Compose menggunakan named volume untuk folder yang berubah ketika aplikasi
berjalan: `uploads`, `ai_images`, `banner_mini`, `voice`, dan `JSON`.

Named volume ini hanya solusi lokal. Sebelum menjalankan lebih dari satu
instance atau memakai platform dengan filesystem ephemeral, pindahkan file-file
tersebut ke object storage S3-compatible.

## Validasi sebelum publish image

```bash
docker compose config
docker compose build --no-cache web
docker compose up -d
curl --fail http://localhost:8080/health.php
docker compose logs web
```

Migration runner, command cron CLI, konfigurasi Railway, manifest Heroku,
Blueprint Render, dan panduan/template Zeabur sudah tersedia. Target berikutnya
adalah `.do/app.yaml`.

Implementasi Heroku sudah tersedia. Lihat [HEROKU_DEPLOYMENT.md](./HEROKU_DEPLOYMENT.md).
Implementasi Railway tersedia di [RAILWAY_DEPLOYMENT.md](./RAILWAY_DEPLOYMENT.md).
Implementasi Render tersedia di [RENDER_DEPLOYMENT.md](./RENDER_DEPLOYMENT.md).
Implementasi Zeabur tersedia di [ZEABUR_DEPLOYMENT.md](./ZEABUR_DEPLOYMENT.md).
Implementasi DigitalOcean Marketplace tersedia di
[DIGITALOCEAN_MARKETPLACE.md](./DIGITALOCEAN_MARKETPLACE.md).
Implementasi Dokploy tersedia di [DOKPLOY_DEPLOYMENT.md](./DOKPLOY_DEPLOYMENT.md).
