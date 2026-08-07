# MyAdNetwork / KumpulBlogger

[![Deploy to Heroku](https://www.herokucdn.com/deploy/button.svg)](https://www.heroku.com/deploy?template=https://github.com/kukuhtw/kumpulblogger.com)

Self-hosted advertising network berbasis PHP dan MySQL untuk menghubungkan
advertiser, publisher, serta provider partner. Aplikasi menyediakan native ads
PPC, federasi antarnetwork, blog internal dan AI tools, influencer marketplace,
serta **Knowledge Commerce Engine (KCE)**.

> Untuk menjalankan bisnis dan sistem dari instalasi kosong sampai go-live,
> mulai dari [Runbook Operasional End-to-End](docs/OPERATIONS_RUNBOOK.md).

## Fitur utama

- **Advertiser:** membuat native-ad campaign, budget, bid per click, pause/resume,
  mapping publisher, laporan klik, dan konfirmasi pembayaran.
- **Publisher:** mendaftarkan situs atau blog internal, memasang JavaScript
  ad-tag, melihat performa, dan menerima revenue dari klik valid.
- **Admin:** moderasi user/situs/iklan, approval pembayaran, fraud rules,
  payout publisher, settlement provider, dan laporan.
- **Federasi provider:** pertukaran advertiser, publisher, mapping, klik, dan
  settlement antar-instance melalui API partner.
- **KCE:** chat AI yang menemukan artikel serta sponsored content berdasarkan
  vector embedding, dengan campaign, wallet, impression/click charging, dan
  dashboard Article Index tersendiri.
- **Konten dan influencer:** artikel/blog internal, AI tools, dan katalog media
  influencer.
- **Otomatisasi:** mapping, audit klik, rekap revenue, budget auto-expire, dan
  sinkronisasi partner melalui scheduler.

Penjelasan bisnis lengkap tersedia di [indeks dokumentasi](docs/README.md).

## Arsitektur

- PHP 8.4 + Apache, document root `public_html/`.
- MySQL 8.4 untuk aplikasi utama dan KCE.
- Dockerfile tunggal untuk web dan cron worker.
- Persistent storage untuk `uploads`, `ai_images`, `banner_mini`, `voice`,
  `JSON`, dan `logs`.
- Environment variables/secret manager untuk database, SMTP, reCAPTCHA, AI,
  domain, dan tracking secret.

Lihat [Docker deployment](docs/operations/DOCKER_DEPLOYMENT.md),
[database ERD](docs/reference/DATABASE_ERD.md), dan
[referensi API](docs/reference/API_ENDPOINTS.md).

## Persyaratan

Cara yang direkomendasikan:

- Docker Engine atau Docker Desktop;
- Docker Compose v2;
- OpenSSL untuk installer VPS;
- minimal 2 GB RAM untuk VPS produksi;
- domain dan HTTPS untuk deployment publik.

Tanpa Docker, aplikasi memerlukan PHP 8.4 dengan extension `curl`, `gd`,
`mbstring`, `mysqli`, `pdo_mysql`, dan `zip`, Apache `mod_rewrite`, serta MySQL.

## Quick start lokal dengan Docker

1. Salin konfigurasi contoh:

   ```bash
   cp .env.example .env
   ```

2. Isi minimal `DB_*`, `APP_NAME`, `DOMAIN_NAME`, dan secret yang digunakan.

3. Build dan jalankan:

   ```bash
   docker compose up -d --build
   ```

   Pada volume database kosong, Compose mengimpor:

   - `sql/myadnetwork_db_hanya_structure.sql`
   - `sql/kce_schema.sql`

4. Buat admin pertama:

   ```bash
   docker compose exec -T \
     -e ADMIN_EMAIL=owner@example.com \
     -e ADMIN_PASSWORD='ganti-password-minimal-12-karakter' \
     -e ADMIN_NAME='Owner' \
     -e ADMIN_WHATSAPP='628123456789' \
     web php bin/create-admin.php
   ```

5. Verifikasi:

   ```bash
   curl --fail http://localhost:8080/health.php
   docker compose logs web
   ```

6. Buka:

   - aplikasi: `http://localhost:8080/`
   - login user: `http://localhost:8080/login.php`
   - login admin: `http://localhost:8080/admin/login.php`
   - KCE: `http://localhost:8080/kce/`

Saat container mulai, identitas provider dibuat otomatis dari `APP_NAME` dan
`DOMAIN_NAME`, lalu disinkronkan ke seluruh `providers_data.json`. Override
opsional tersedia melalui `PROVIDER_NAME` dan `PROVIDER_DOMAIN_URL`.

Panduan lengkap: [Docker deployment](docs/operations/DOCKER_DEPLOYMENT.md).

## Instalasi produksi

Pilih satu target:

- [VPS dengan installer interaktif/noninteraktif](docs/operations/VPS_INSTALLATION.md)
- [DigitalOcean Marketplace Droplet 1-Click](docs/operations/DIGITALOCEAN_MARKETPLACE.md)
- [Railway](docs/operations/RAILWAY_DEPLOYMENT.md)
- [Render](docs/operations/RENDER_DEPLOYMENT.md)
- [Zeabur](docs/operations/ZEABUR_DEPLOYMENT.md)
- [Heroku](docs/operations/HEROKU_DEPLOYMENT.md)

Render membutuhkan MySQL eksternal. Template Zeabur memerlukan penggantian
`GITHUB_REPOSITORY_ID` sebelum diterbitkan. Coolify dan Dokploy belum mempunyai
manifest/panduan khusus, tetapi dapat menggunakan Dockerfile dengan konfigurasi
database, volume `/data`, health check, dan environment variables yang sama.

## Konfigurasi

Gunakan [.env.example](.env.example) sebagai referensi. Kelompok utamanya:

| Kelompok | Variable |
|---|---|
| Database | `DB_HOST`, `DB_PORT`, `DB_DATABASE`, `DB_USERNAME`, `DB_PASSWORD` |
| Identitas | `APP_NAME`, `DOMAIN_NAME`, opsional `PROVIDER_NAME`, `PROVIDER_DOMAIN_URL` |
| Email/captcha | `SMTP_API_KEY`, `SMTP_API_SECRET`, `RECAPTCHA_SITE_KEY`, `RECAPTCHA_SECRET` |
| Pembayaran | `PAYMENT_INFO` |
| KCE/chat | `OPENROUTER_API_KEY`, `OPENROUTER_MODEL`, `KCE_APP_URL` |
| Embedding | `NVIDIA_API_KEY`, `NVIDIA_EMBEDDING_MODEL` |
| Keamanan KCE | `KCE_TRACKING_SECRET` |

Pada startup pertama, installer otomatis membuat data awal `providers`,
`providers_contact_person`, dan `llm_settings` pada `id=1`. Proses ini
idempotent: restart atau deploy ulang tidak menimpa data yang sudah ada. Nilai
opsional `PROVIDER_*`, `OPENAI_API_KEY`, `REPLICATE_API_KEY`, dan `LLM_*` di
`.env.example` dapat digunakan untuk mengatur seed awal tersebut.

Email transaksional saat ini dikirim melalui API [kirim.email](https://kirim.email/).
Gunakan API key akun kirim.email pada `SMTP_API_KEY`. `DOMAIN_NAME` tetap berisi
domain publik AdNetwork tanpa skema atau path (misalnya `ads.example.com`) dan
domain tersebut harus sudah diotorisasi sebagai sending domain di kirim.email.

Process environment memiliki prioritas atas `.env`. Jangan commit `.env`, API
key, password database, atau secret produksi.

## Scheduler wajib

Aplikasi web dapat hidup tanpa scheduler, tetapi proses bisnis tidak akan
lengkap: mapping, fraud audit, metadata, rekap revenue, dan budget expiry tidak
berjalan dengan benar.

- [Cara memasang jadwal cron](docs/operations/CRON_SETUP.md)
- [Fungsi setiap cronjob](docs/operations/CRONJOB_JOBS.md)
- [Konsep pipeline otomatis](docs/guides/11-cronjob-dan-otomatisasi.md)

Render menyediakan cron lewat `render.yaml`; Zeabur memakai cron worker dengan
`PROCESS_TYPE=cron`; platform lain mengikuti panduan deployment masing-masing.

## Menjalankan bisnis

Urutan minimum setelah deployment:

1. amankan admin dan isi identitas/rekening provider;
2. tetapkan harga, payout, moderasi, fraud, dan SLA;
3. aktifkan scheduler;
4. uji advertiser hingga iklan paid/published;
5. uji publisher, mapping, dan ad-tag;
6. validasi klik hingga spending/revenue;
7. simulasi payout dan rekonsiliasi;
8. uji backup/restore sebelum go-live;
9. aktifkan federasi hanya setelah operasi lokal stabil.

Ikuti langkah dan exit criteria di [runbook operasional](docs/OPERATIONS_RUNBOOK.md).

## Knowledge Commerce Engine

KCE berbeda dari native ads. Pertanyaan pengguna dijawab melalui OpenRouter,
lalu NVIDIA embedding mencari artikel dan sponsor relevan. Sponsored content
tidak dimasukkan ke prompt dan ditampilkan terpisah dengan label iklan.

- [Konsep, bisnis, konfigurasi, wallet, dan privasi KCE](docs/guides/13-knowledge-commerce-engine.md)
- [Manual Admin Article Index dan vector embedding](docs/reference/KCE_ARTICLE_INDEX.md)
- [README modul KCE](public_html/kce/README.md)

## Pembayaran dan tanggung jawab finansial

Tidak ada payment gateway otomatis. Operator harus memverifikasi transfer di
luar sistem sebelum mencatat deposit, paid status, payout, refund, adjustment,
atau settlement.

### Komitmen Finansial Penyedia Ad Network

Sebelum mengoperasikan atau menghubungkan ad network ke jaringan federasi
KumpulBlogger, setiap penyedia ad network harus memahami dan menyetujui
tanggung jawab finansial berikut.

#### 1. Membayar publisher pada jaringan sendiri

Setiap penyedia ad network bertanggung jawab membayar publisher yang terdaftar
pada jaringannya sesuai jumlah klik valid, tarif, jadwal pembayaran, dan
ketentuan yang berlaku pada jaringan tersebut. Kewajiban ini tetap berlaku
untuk revenue yang berasal dari iklan lokal maupun iklan partner.

#### 2. Membayar jaringan partner ketika advertiser lokal memperoleh klik dari publisher partner

Jika advertiser yang terdaftar pada suatu ad network memperoleh klik dari
publisher milik ad network partner, maka ad network asal advertiser
berkewajiban membayar bagian revenue kepada:

- admin atau pemilik ad network partner; dan
- publisher partner yang menampilkan iklan.

Sistem menyediakan catatan nominal pembayaran kepada admin ad network partner
dan publisher partner pada dashboard admin. Catatan tersebut digunakan sebagai
dasar rekonsiliasi, verifikasi, dan penyelesaian pembayaran antarpihak.

#### 3. Berhak menerima pembayaran dari ad network partner

Penyedia ad network juga berkesempatan mendapatkan pembayaran dari admin ad
network partner apabila iklan milik advertiser partner mendapatkan klik pada
publisher di jaringannya. Dalam kondisi ini, penyedia ad network bertindak
sebagai pemilik jaringan publisher dan berhak menerima bagian revenue jaringan,
sedangkan publisher yang menghasilkan klik berhak menerima bagian revenue
publisher.

Contoh: advertiser dari **BudiAdnetwork** memperoleh klik pada publisher milik
**AmirAdnetwork**. BudiAdnetwork sebagai jaringan asal advertiser berkewajiban
membayar bagian revenue kepada admin AmirAdnetwork dan publisher AmirAdnetwork
yang menghasilkan klik. Sebaliknya, apabila advertiser AmirAdnetwork memperoleh
klik pada publisher BudiAdnetwork, AmirAdnetwork memiliki kewajiban pembayaran
yang sama kepada BudiAdnetwork.

Federasi bukan hanya mekanisme pertukaran iklan dan publisher, tetapi juga
komitmen pembayaran antarpelaku jaringan. Setiap penyedia wajib memastikan
saldo, pencatatan klik, audit, laporan revenue, dan pembayaran partner dikelola
secara transparan serta dapat dipertanggungjawabkan.

Baca [pembayaran dan revenue share](docs/guides/07-pembayaran-dan-revenue-share.md)
serta [provider partner network](docs/guides/05-provider-partner-network.md).

### Artikel dan latar belakang proyek

- [Kumpulblogger.com Dirilis Sebagai Open Source dengan Lisensi Apache 2.0: Membuka Potensi Ad Network Terdesentralisasi di Indonesia](https://kukuhtw.medium.com/kumpulblogger-com-542f2b01347e)
- [Membangun Masa Depan Bisnis Tanpa Batas: Sinergi Digital di KumpulBlogger.com](https://kukuhtw.medium.com/membangun-masa-depan-bisnis-tanpa-batas-sinergi-digital-di-kumpulblogger-com-4507cc922fac)
- [KumpulBlogger.com](https://kukuhtw.medium.com/kumpulblogger-com-1f492838054d)

## Struktur repository

```text
bin/             CLI admin, cron runner, provider sync
docker/          entrypoint, database prepare, Apache/PHP, cron loop
docs/            panduan bisnis, referensi teknis, operasi, deployment
install/         installer VPS
marketplace/     build DigitalOcean Marketplace
public_html/     document root aplikasi
sql/             schema utama dan KCE
```

Manifest deployment berada di `app.json`, `heroku.yml`, `railway*.toml`,
`render.yaml`, dan `zeabur-template.yaml.example`.

## Dokumentasi

Mulai dari:

- [Indeks dokumentasi](docs/README.md)
- [Runbook operasional end-to-end](docs/OPERATIONS_RUNBOOK.md)

Alur bisnis:

- [Publisher](docs/guides/03-alur-publisher.md)
- [Advertiser](docs/guides/04-alur-advertiser.md)
- [Ad serving dan tracking klik](docs/guides/06-ad-serving-dan-tracking-klik.md)
- [Admin dan approval](docs/guides/10-admin-dan-approval.md)

Referensi teknis:

- [User dashboard](docs/reference/USER_DASHBOARD.md)
- [Admin panel](docs/reference/ADMIN_PANEL.md)
- [Database ERD](docs/reference/DATABASE_ERD.md)
- [API endpoints](docs/reference/API_ENDPOINTS.md)

## Keamanan dan operasi

- Gunakan HTTPS dan secret manager.
- Jangan mengekspos MySQL secara publik tanpa allowlist.
- Ganti kredensial bootstrap segera.
- Pantau health check, error log, cron, kapasitas disk, biaya API, serta fraud.
- Backup database dan persistent storage; lakukan uji restore berkala.
- Tinjau temuan kualitas/keamanan pada dokumen referensi sebelum produksi.

## Lisensi

Project ini menggunakan **Apache License 2.0**. Lihat [LICENSE](LICENSE) untuk
syarat penggunaan, modifikasi, dan distribusi. Pendistribusian ulang atau karya
turunan tetap harus mematuhi kewajiban notice, atribusi, serta ketentuan lain
dalam lisensi tersebut.
