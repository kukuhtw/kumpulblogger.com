# Deploy MyAdNetwork ke Zeabur

> Navigasi: [Runbook instalasi](../OPERATIONS_RUNBOOK.md#5-deploy-instalasi-pertama) · [Docker](./DOCKER_DEPLOYMENT.md) · [Scheduler](../OPERATIONS_RUNBOOK.md#7-aktifkan-scheduler) · [Backup](../OPERATIONS_RUNBOOK.md#14-backup-dan-restore)

Zeabur mendeteksi `Dockerfile` di root repository. Implementasi ini mendukung
web service, MySQL, volume `/data`, persiapan schema otomatis, sinkronisasi
`providers_data.json`, serta cron worker terpisah.

## Opsi A — deploy dari GitHub

1. Buat project Zeabur.
2. Pilih **Deploy New Service > Database > MySQL**.
3. Pilih **Deploy New Service > GitHub**, lalu pilih repository ini. Zeabur akan
   mendeteksi Dockerfile secara otomatis.
4. Tambahkan domain pada service web.
5. Salin variable MySQL yang diekspos oleh service database ke konfigurasi web:

   ```dotenv
   DB_HOST=${MYSQL_HOST}
   DB_PORT=${MYSQL_PORT}
   DB_DATABASE=${MYSQL_DATABASE}
   DB_USERNAME=${MYSQL_USER}
   DB_PASSWORD=${MYSQL_PASSWORD}
   APP_NAME=MyAdNetwork
   DOMAIN_NAME=ads.example.com
   KCE_APP_URL=https://ads.example.com/kce
   KCE_TRACKING_SECRET=GANTI_DENGAN_SECRET_ACAK
   PERSISTENT_DATA_ROOT=/data
   RUN_DB_PREPARE=1
   ```

6. Mount volume service web ke `/data`. Saat startup pertama, entrypoint akan
   menunggu MySQL, mengimpor schema, dan menghubungkan semua folder runtime ke
   volume tersebut.

## Cron worker

Deploy repository GitHub yang sama sebagai service kedua tanpa domain. Gunakan
variable database yang sama dan tambahkan:

```dotenv
PROCESS_TYPE=cron
APP_NAME=MyAdNetwork
DOMAIN_NAME=ads.example.com
KCE_APP_URL=https://ads.example.com/kce
```

`PROCESS_TYPE=cron` membuat entrypoint menjalankan loop scheduler, bukan Apache.
Scheduler memeriksa jadwal setiap menit dan menjalankan daftar job yang sama
dengan deployment Render.

## Opsi B — Template YAML

`zeabur-template.yaml.example` mendefinisikan MySQL, web, volume, dan cron worker.
Zeabur membutuhkan ID numerik repository GitHub untuk service berbasis Git.
Ganti semua `GITHUB_REPOSITORY_ID` terlebih dahulu:

```bash
gh api repos/OWNER/REPOSITORY --jq .id
```

Ubah nama file menjadi `zeabur-template.yaml`, validasi/deploy dengan Zeabur CLI,
lalu gunakan file tersebut untuk penerbitan template one-click. Branch default
di contoh adalah `main`; sesuaikan jika repository memakai branch lain.

## Catatan volume

Mount `/data` menyimpan `uploads`, `ai_images`, `banner_mini`, `voice`, `JSON`,
dan `logs`. Pemasangan volume menghapus isi awal pada mount point, tetapi
entrypoint menyalin seed dari image ketika target masih kosong. Zeabur juga
menonaktifkan zero-downtime restart untuk service yang memakai volume.

## Verifikasi

1. Runtime log web memuat status persiapan database dan enam file provider.
2. `/health.php` mengembalikan status `ok`.
3. Isi keenam `providers_data.json` menggunakan domain dan nama instalasi.
4. File upload tetap tersedia setelah restart/redeploy.
5. Log cron-worker mencetak job terjadwal dan tidak menjalankan Apache.

## Referensi resmi

- https://zeabur.com/docs/en-US/deploy/methods/dockerfile
- https://zeabur.com/docs/en-US/template/template-format
- https://zeabur.com/docs/en-US/data-management/volumes
- https://zeabur.com/docs/en-US/deploy/config/environment-variables
