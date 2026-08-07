# Deploy MyAdNetwork ke Render

> Navigasi: [Runbook instalasi](../OPERATIONS_RUNBOOK.md#5-deploy-instalasi-pertama) · [Docker](./DOCKER_DEPLOYMENT.md) · [Scheduler](../OPERATIONS_RUNBOOK.md#7-aktifkan-scheduler) · [Backup](../OPERATIONS_RUNBOOK.md#14-backup-dan-restore)

Repository menyediakan `render.yaml` untuk membuat web service dan cron job
dari Dockerfile yang sama. Render tidak menyediakan managed MySQL melalui
Blueprint, sehingga deployment membutuhkan MySQL 8 yang kompatibel dari
provider eksternal.

## Arsitektur

```text
Render Web + Persistent Disk /data ----> MySQL eksternal
Render Cron (setiap menit) ------------> MySQL eksternal
```

Web service menjalankan `myadnetwork-db-prepare` sebelum setiap deployment,
kemudian entrypoint membuat seluruh salinan `providers_data.json`. Persistent
disk `/data` menyimpan `uploads`, `ai_images`, `banner_mini`, `voice`, `JSON`,
dan `logs`. Cron job tidak mengakses disk karena Render tidak mendukung disk
pada cron job.

## Deploy Blueprint

1. Siapkan database MySQL 8 kosong yang dapat diakses dari Render.
2. Push repository ke GitHub atau GitLab.
3. Di Render Dashboard pilih **New > Blueprint** dan hubungkan repository.
4. Render membaca `render.yaml` dan meminta variable bertanda `sync: false`.
5. Isi minimal:

   - `DB_HOST`, `DB_PORT`, `DB_DATABASE`, `DB_USERNAME`, `DB_PASSWORD`
   - `DOMAIN_NAME`, tanpa path atau skema
   - `KCE_APP_URL`, misalnya `https://nama-service.onrender.com/kce`

6. Variable integrasi SMTP, reCAPTCHA, OpenRouter, NVIDIA, dan pembayaran boleh
   dibiarkan kosong jika fiturnya belum digunakan.
7. Terapkan Blueprint dan tunggu pre-deploy database serta health check selesai.

Jika hostname `onrender.com` baru diketahui setelah resource dibuat, isi
`DOMAIN_NAME` dan `KCE_APP_URL` dari halaman Environment lalu deploy ulang.

## Jadwal cron

`myadnetwork-cron` dipicu setiap menit dalam UTC dan menjalankan
`bin/run-scheduled-jobs.php`. Runner tersebut mempertahankan interval operasional:

- tiap menit: audit klik dan metadata klik;
- tiap 7 menit: mapping iklan lokal;
- tiap 8 menit: rekap publisher;
- tiap 9 menit: pemeriksaan rate dan rekap lokal;
- tiap 30 menit: total publisher, kalkulasi budget, dan JSON publisher terbaru.

Render menagih cron job secara terpisah. Hapus resource cron dari Blueprint
jika jadwal dijalankan oleh scheduler eksternal.

## Penyimpanan dan scaling

Manifest memakai instance `starter`, disk 10 GB, dan region Singapore. Ubah
nilai tersebut sebelum deploy bila diperlukan. Service dengan persistent disk
tidak dapat memakai beberapa instance; untuk horizontal scaling, pindahkan file
runtime ke object storage terlebih dahulu.

## Verifikasi

1. Deployment log memuat proses import schema atau pesan schema sudah tersedia.
2. Buka `/health.php` dan pastikan status `ok`.
3. Periksa log startup untuk enam baris `Provider data:`.
4. Upload file, lakukan redeploy, lalu pastikan file tetap tersedia.
5. Trigger cron secara manual dan pastikan setiap job selesai dengan exit code 0.

## Referensi resmi

- https://render.com/docs/blueprint-spec
- https://render.com/docs/docker
- https://render.com/docs/disks
- https://render.com/docs/cronjobs
