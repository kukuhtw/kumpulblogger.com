# Deploy MyAdNetwork ke Heroku

> Navigasi: [Runbook instalasi](../OPERATIONS_RUNBOOK.md#5-deploy-instalasi-pertama) · [Docker](./DOCKER_DEPLOYMENT.md) · [Cron](./CRON_SETUP.md) · [Verifikasi bisnis](../OPERATIONS_RUNBOOK.md#8-uji-advertiser)

Implementasi Heroku tersedia di root repository:

- `Dockerfile`: PHP 8.4, Apache, extension aplikasi, dan MySQL client.
- `heroku.yml`: build image web dan release phase.
- `app.json`: formulir konfigurasi Deploy to Heroku.
- `docker/db-prepare.sh`: instalasi schema database kosong lintas-platform.
- `bin/cron.php`: runner aman berbasis whitelist untuk Heroku Scheduler.

Implementasi ini memakai Heroku Cedar container stack. Heroku Button tidak
mendukung Fir.

## Prasyarat database

Sediakan MySQL 8 yang kompatibel, baik dari Heroku Marketplace maupun provider
eksternal. Buat database kosong lalu catat host, port, nama database, username,
dan password. Release phase akan mengimpor schema utama satu kali dan selalu
memastikan schema KCE tersedia.

## Deploy satu klik

Repository harus tersedia di GitHub. Untuk tombol di README repository:

```markdown
[![Deploy](https://www.herokucdn.com/deploy/button.svg)](https://www.heroku.com/deploy)
```

Untuk repository privat atau tombol yang ditempatkan di luar GitHub:

```text
https://www.heroku.com/deploy?template=https://github.com/OWNER/REPOSITORY
```

Isi variabel wajib pada form:

- `DB_HOST`, `DB_PORT`, `DB_DATABASE`, `DB_USERNAME`, `DB_PASSWORD`
- `DOMAIN_NAME`, misalnya `myadnetwork.example.com`
- `KCE_APP_URL`, misalnya `https://myadnetwork.example.com/kce`

Saat web dyno dimulai, seluruh lokasi `providers_data.json` dibuat otomatis.
`PROVIDER_NAME` dan `PROVIDER_DOMAIN_URL` tersedia sebagai override opsional;
default-nya mengikuti `APP_NAME` dan `DOMAIN_NAME`.

`KCE_TRACKING_SECRET` dibuat otomatis. SMTP, reCAPTCHA, OpenRouter, NVIDIA, dan
informasi pembayaran dapat diisi kemudian; fitur terkait tidak akan lengkap
sebelum konfigurasinya tersedia.

Setelah selesai, cek `/health.php`. Respons yang sehat:

```json
{"status":"ok","service":"MyAdNetwork"}
```

## Deploy melalui CLI

```bash
heroku login
heroku create myadnetwork --stack container
heroku config:set APP_NAME=MyAdNetwork
heroku config:set DB_HOST=mysql.example.com DB_PORT=3306
heroku config:set DB_DATABASE=myadnetwork DB_USERNAME=myadnetwork DB_PASSWORD='replace-me'
heroku config:set DOMAIN_NAME=myadnetwork.example.com
heroku config:set KCE_APP_URL=https://myadnetwork.example.com/kce
heroku config:set KCE_TRACKING_SECRET='replace-with-a-long-random-secret'
git push heroku main
heroku open
```

Verifikasi deployment dan release phase:

```bash
heroku logs --tail
heroku run php -v
heroku run php bin/cron.php mapping-local
curl --fail https://YOUR-APP.herokuapp.com/health.php
```

## Cronjob

Heroku Scheduler dapat menjalankan command dari image `web`. Daftarkan sesuai
jadwal operasi aplikasi:

```text
php bin/cron.php click-audit
php bin/cron.php mapping-local
php bin/cron.php mapping-rate
php bin/cron.php recap-local
php bin/cron.php recap-publisher
php bin/cron.php recap-total-publisher
php bin/cron.php update-click-metadata
php bin/cron.php calculate-budget
```

Scheduler standar cocok untuk jadwal kasar seperti tiap 10 menit, per jam, atau
harian. Untuk interval satu menit dan ekspresi cron presisi, gunakan scheduler
add-on yang mendukung cron expression.

## Batasan filesystem

Filesystem dyno bersifat ephemeral. File dalam `uploads`, `ai_images`,
`banner_mini`, `voice`, dan `JSON` dapat hilang saat restart atau deploy.
Gunakan hanya satu web dyno sampai penyimpanan tersebut dipindahkan ke object
storage S3-compatible. Database tidak boleh ditempatkan di filesystem dyno.

## Troubleshooting

Jika release gagal, periksa koneksi database:

```bash
heroku run myadnetwork-db-prepare
```

Jika aplikasi gagal boot, pastikan `app.json` memakai `"stack": "container"`
dan `heroku.yml` ada di root repository. Apache otomatis mendengarkan `$PORT`
yang diberikan Heroku.

## Referensi resmi

- https://devcenter.heroku.com/articles/heroku-button
- https://devcenter.heroku.com/articles/app-json-schema
- https://devcenter.heroku.com/articles/build-docker-images-heroku-yml
- https://devcenter.heroku.com/articles/container-registry-and-runtime
- https://devcenter.heroku.com/articles/scheduled-jobs-custom-clock-processes
