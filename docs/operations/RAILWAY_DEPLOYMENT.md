# Deploy dan Host MyAdNetwork dengan Railway

> Navigasi: [Runbook instalasi](../OPERATIONS_RUNBOOK.md#5-deploy-instalasi-pertama) · [Docker](./DOCKER_DEPLOYMENT.md) · [Scheduler](../OPERATIONS_RUNBOOK.md#7-aktifkan-scheduler) · [Verifikasi bisnis](../OPERATIONS_RUNBOOK.md#8-uji-advertiser)

MyAdNetwork menggunakan Docker untuk menjalankan PHP 8.4 + Apache. Implementasi
Railway dalam repository ini mencakup:

- `railway.toml` untuk build, database prepare, healthcheck, dan restart policy.
- `railway.cron.toml` untuk service cron yang harus selesai lalu berhenti.
- `railway-template.env.example` sebagai variable mapping Template Composer.
- `docker/db-prepare.sh` untuk menunggu MySQL dan mengimpor schema awal.
- `docker/entrypoint.sh` untuk `$PORT` dan Railway Volume.
- `bin/cron.php` untuk menjalankan cronjob yang sudah di-whitelist.

## Arsitektur project Railway

Template produksi terdiri dari:

```text
MyAdNetwork Web ──private network──> MySQL
       │
       └── Railway Volume: /data

Cron services ───private network──> MySQL
```

MySQL Railway menyediakan `MYSQLHOST`, `MYSQLPORT`, `MYSQLUSER`,
`MYSQLPASSWORD`, dan `MYSQLDATABASE`. Service aplikasi memetakannya ke variable
`DB_*` yang digunakan source code.

## Opsi A — deploy manual dari GitHub

### 1. Buat project dan database

1. Buat project kosong di Railway.
2. Klik **+ New** → **Database** → **MySQL**.
3. Ubah nama service database menjadi persis `MySQL`.
4. Tunggu sampai deployment database aktif.

### 2. Tambahkan web service

1. Klik **+ New** → **GitHub Repo**.
2. Pilih repository MyAdNetwork.
3. Railway mendeteksi `Dockerfile` dan membaca `railway.toml`.
4. Pada **Variables → Raw Editor**, masukkan:

```dotenv
DB_HOST=${{MySQL.MYSQLHOST}}
DB_PORT=${{MySQL.MYSQLPORT}}
DB_DATABASE=${{MySQL.MYSQLDATABASE}}
DB_USERNAME=${{MySQL.MYSQLUSER}}
DB_PASSWORD=${{MySQL.MYSQLPASSWORD}}
APP_NAME=MyAdNetwork
DOMAIN_NAME=${{RAILWAY_PUBLIC_DOMAIN}}
KCE_APP_URL=https://${{RAILWAY_PUBLIC_DOMAIN}}/kce
KCE_TRACKING_SECRET=GANTI_DENGAN_SECRET_ACAK_PANJANG
```

Tambahkan API key opsional sesuai `.env.example`. Jangan memasukkan `.env`
produksi ke repository.

Pada startup, seluruh lokasi `providers_data.json` otomatis dibuat dari
`APP_NAME` dan domain publik tersebut. Tambahkan `PROVIDER_NAME` dan
`PROVIDER_DOMAIN_URL` bila identitas provider perlu dioverride.

### 3. Aktifkan public domain

Pada web service buka **Settings → Networking → Generate Domain**. Setelah
domain tersedia, deploy ulang agar `DOMAIN_NAME` dan `KCE_APP_URL` ter-resolve.

Railway mengirim `PORT` secara otomatis. Entrypoint mengubah port Apache pada
saat container boot. Healthcheck memakai `/health.php` dengan timeout 300
detik. Pre-deploy command `myadnetwork-db-prepare` akan:

1. Menunggu MySQL maksimal sekitar 60 detik.
2. Memeriksa tabel `articles`.
3. Mengimpor `sql/myadnetwork_db_hanya_structure.sql` jika database kosong.
4. Memastikan seluruh tabel KCE tersedia.

### 4. Tambahkan persistent volume

Pada web service pilih **Attach Volume** dan gunakan mount path:

```text
/data
```

Railway mengisi `RAILWAY_VOLUME_MOUNT_PATH`. Entrypoint kemudian mengarahkan
folder berikut ke volume:

- `uploads`
- `ai_images`
- `banner_mini`
- `voice`
- `JSON`
- `logs`

Gunakan satu replica web selama direktori tersebut masih memakai satu volume.

## Opsi B — membuat one-click Railway Template

Config-as-Code Railway hanya mengatur satu deployment/service. Infrastruktur
multi-service dibuat melalui **Template Composer**:

1. Deploy project manual sampai web dan MySQL sehat.
2. Buka **Project Settings**.
3. Pilih **Generate Template from Project** → **Create Template**.
4. Pastikan template mempunyai service `MySQL` dan web `MyAdNetwork`.
5. Salin variable web dari `railway-template.env.example`.
6. Ganti secret dengan template function `${{secret(64)}}`.
7. Tambahkan volume web dengan mount path `/data`.
8. Aktifkan HTTP public networking dan healthcheck `/health.php`.
9. Tambahkan service cron sebagaimana bagian berikut.
10. Deploy template ke project uji yang benar-benar baru.
11. Setelah berhasil, pilih **Publish**, lengkapi deskripsi dan support URL.

Setelah Railway memberikan template code, tombol README menggunakan bentuk:

```markdown
[![Deploy on Railway](https://railway.com/button.svg)](https://railway.com/deploy/TEMPLATE_CODE?referralCode=YOUR_CODE&utm_medium=integration&utm_source=template&utm_campaign=myadnetwork)
```

`TEMPLATE_CODE` baru tersedia setelah template dibuat di Railway; nilai itu
tidak dapat dihasilkan hanya dari file repository.

## Cron services

Railway menetapkan minimum interval cron lima menit dan menggunakan UTC. Untuk
setiap job di bawah, tambahkan service dari repository yang sama dan atur:

- **Config File Path**: `/railway.cron.toml`
- Variable `DB_*`: referensi yang sama ke service `MySQL`
- Tidak perlu public domain atau volume
- Start Command dan Cron Schedule sesuai tabel

| Service | Start Command | Cron Schedule |
|---|---|---|
| `cron-click-audit` | `php bin/cron.php click-audit` | `*/5 * * * *` |
| `cron-click-metadata` | `php bin/cron.php update-click-metadata` | `*/5 * * * *` |
| `cron-mapping-local` | `php bin/cron.php mapping-local` | `*/10 * * * *` |
| `cron-mapping-rate` | `php bin/cron.php mapping-rate` | `5-59/10 * * * *` |
| `cron-recap-local` | `php bin/cron.php recap-local` | `*/10 * * * *` |
| `cron-recap-publisher` | `php bin/cron.php recap-publisher` | `5-59/10 * * * *` |
| `cron-recap-total` | `php bin/cron.php recap-total-publisher` | `0,30 * * * *` |
| `cron-budget` | `php bin/cron.php calculate-budget` | `0,30 * * * *` |

Interval 1, 7, 8, dan 9 menit dari deployment lama disesuaikan menjadi 5 atau
10 menit karena batas minimum Railway. Jangan menggunakan `railway.toml` untuk
cron service karena file itu memiliki healthcheck web dan pre-deploy migration.

## Deploy dengan Railway CLI

CLI berguna untuk web service setelah project dan variables dibuat:

```bash
railway login
railway link
railway service MyAdNetwork
railway up
railway logs
```

Untuk menjalankan pemeriksaan dengan variables Railway:

```bash
railway run php -v
railway run myadnetwork-db-prepare
railway run php bin/cron.php mapping-local
```

## Verifikasi

1. Deployment log memuat `Schema utama sudah tersedia` atau proses import awal.
2. `/health.php` mengembalikan status `ok`.
3. Halaman utama dan `/blogs/` dapat dibuka.
4. Upload tetap tersedia setelah redeploy.
5. Cron service berakhir dengan exit code 0 dan tidak terus berjalan.
6. MySQL menggunakan private reference variables, bukan TCP proxy publik.

## Troubleshooting

### Pre-deploy tidak dapat terhubung ke database

Pastikan nama service database tepat `MySQL` dan nilai variable web memakai
`${{MySQL.MYSQL...}}`. Periksa juga bahwa database deployment sudah aktif.

### Healthcheck gagal

Periksa log web, pastikan tidak ada Start Command dashboard yang menimpa CMD
Dockerfile, dan pastikan `/health.php` merespons tanpa koneksi database.

### Upload hilang setelah deployment

Pastikan volume terpasang di `/data`. Log startup dan variable
`RAILWAY_VOLUME_MOUNT_PATH` membantu memastikan symlink storage dibuat.

### Cron terus restart

Pastikan cron memakai `/railway.cron.toml`, restart policy `NEVER`, dan Start
Command adalah `php bin/cron.php ...`, bukan `apache2-foreground`.

## Referensi resmi

- https://docs.railway.com/config-as-code/reference
- https://docs.railway.com/builds/dockerfiles
- https://docs.railway.com/databases/mysql
- https://docs.railway.com/variables
- https://docs.railway.com/cron-jobs
- https://docs.railway.com/templates/create
- https://docs.railway.com/templates/publish-and-share
