# Deployment Dokploy

Konfigurasi ini menjalankan tiga service dalam satu Docker Compose:

- `web`: PHP 8.4 + Apache pada container port `8080`.
- `db`: MySQL 8.4 pada network internal, tanpa port database publik.
- `cron`: scheduler aplikasi yang menjalankan seluruh job setiap menit.

Data MySQL dan file aplikasi yang berubah disimpan dalam named volume. Startup
pertama mengimpor schema dan membuat seed `providers`,
`providers_contact_person`, serta `llm_settings` secara idempotent.

## 1. Buat Compose dari GitHub

1. Di Dokploy, buat **Project** dan **Environment**.
2. Tambahkan service **Docker Compose**.
3. Pilih provider GitHub dan repository
   `https://github.com/kukuhtw/kumpulblogger.com`.
4. Pilih branch `main`.
5. Atur **Compose Path** menjadi `./dokploy-compose.yml`.
6. Aktifkan **Auto Deploy** jika setiap push ke `main` harus diterapkan.

## 2. Isi environment

Salin isi `dokploy.env.example` ke bagian **Environment** Compose di Dokploy,
lalu ganti semua nilai `change-me`. Nilai minimum yang wajib benar:

```dotenv
DOMAIN_NAME=ads.example.com
KCE_APP_URL=https://ads.example.com/kce
KCE_TRACKING_SECRET=secret-acak-yang-panjang
DB_PASSWORD=password-database-yang-kuat
DB_ROOT_PASSWORD=password-root-yang-berbeda
```

Gunakan domain tanpa `https://` untuk `DOMAIN_NAME`. Jangan commit environment
produksi atau secret ke repository.

## 3. Deploy dan pasang domain

1. Klik **Deploy** dan tunggu `db`, `web`, serta `cron` berstatus berjalan.
2. Buka bagian **Domains** pada Compose.
3. Tambahkan domain `ads.example.com` untuk service `web`.
4. Pilih container port `8080`, path `/`, dan aktifkan HTTPS/Let's Encrypt.
5. Pastikan DNS A/AAAA domain sudah mengarah ke server Dokploy.
6. Buka `https://ads.example.com/health.php`; respons harus menunjukkan status
   sehat.

Dokploy/Traefik menangani HTTPS di depan container. Jangan menambahkan mapping
host port untuk `web` atau `db` pada manifest ini.

## 4. Buat admin pertama

Setelah deployment sehat, buka terminal service `web` di Dokploy dan jalankan:

```sh
ADMIN_EMAIL=owner@example.com \
ADMIN_PASSWORD='ganti-dengan-password-minimal-12-karakter' \
ADMIN_NAME='Administrator' \
ADMIN_WHATSAPP='-' \
php bin/create-admin.php
```

Login melalui `https://ads.example.com/admin/login.php`. Konfigurasi LLM dan
rekening provider dapat dilengkapi dari panel admin; baris awalnya sudah dibuat
oleh bootstrap instalasi.

## 5. Scheduler dan backup

Service `cron` sudah menjalankan `bin/run-scheduled-jobs.php` setiap menit, jadi
tidak perlu membuat Dokploy Schedule Job tambahan. Jika service ini sengaja
dihapus, buat Compose Schedule Job untuk service `web` dengan command berikut
dan ekspresi cron `* * * * *`:

```sh
php /var/www/html/bin/run-scheduled-jobs.php
```

Jangan menjalankan kedua mekanisme bersamaan karena job dapat dieksekusi dua
kali. Jangan mengubah `COMPOSE_PROJECT_NAME` dari Dokploy karena nama tersebut
dipakai untuk menemukan service Compose pada scheduled jobs.

Aktifkan backup untuk named volume database dan aplikasi:

- volume `mysql_data`: database utama;
- volume `app_data`: upload, gambar AI, banner, audio, JSON, dan log.

Simpan backup ke destination S3-compatible dan lakukan uji restore sebelum
produksi.

## 6. Pemeriksaan dan pembaruan

Periksa log ketiga service setelah deployment. Pesan bootstrap yang normal:

```text
Bootstrap instalasi: providers=dibuat, providers_contact_person=dibuat, llm_settings=dibuat.
```

Pada restart berikutnya status berubah menjadi `sudah-ada`; data lama tidak
ditimpa. Untuk pembaruan aplikasi, push ke branch yang terhubung lalu deploy
ulang atau gunakan Auto Deploy.

Referensi resmi:

- https://docs.dokploy.com/docs/core/auto-deploy
- https://docs.dokploy.com/docs/core/schedule-jobs
- https://docs.dokploy.com/docs/core/volume-backups
