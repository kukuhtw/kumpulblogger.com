# Instalasi Pertama di VPS

> Navigasi: [Runbook instalasi](../OPERATIONS_RUNBOOK.md#5-deploy-instalasi-pertama) · [Docker](./DOCKER_DEPLOYMENT.md) · [Cron](./CRON_SETUP.md) · [Bootstrap admin](../OPERATIONS_RUNBOOK.md#6-bootstrap-operator)

Installer `install/vps-install.sh` menyiapkan `.env`, membuat secret acak,
menjalankan Docker Compose, menunggu aplikasi sehat, dan membuat admin pertama.
Data MySQL dan file upload disimpan pada Docker named volumes.

## Prasyarat

- VPS Ubuntu 22.04/24.04 atau Debian 12, minimal 2 GB RAM.
- Domain dengan DNS A/AAAA mengarah ke IP VPS.
- Git, OpenSSL, Docker Engine, dan plugin Docker Compose v2.
- Port 22, 80, dan 443 dibuka pada firewall. Port MySQL tidak perlu dibuka.

Pasang Docker memakai [panduan resmi Docker Engine](https://docs.docker.com/engine/install/).
Pastikan perintah berikut berhasil:

```bash
docker info
docker compose version
```

## Instalasi interaktif

```bash
git clone URL_REPOSITORY myadnetwork
cd myadnetwork
chmod +x install/vps-install.sh
./install/vps-install.sh
```

Installer meminta nama platform, domain, port lokal, dan identitas admin. Password
database, root database, tracking, dan admin dibuat secara acak. Password admin
hanya ditampilkan sekali dan tidak ditulis ke `.env`; segera simpan ke password
manager. Pada konfigurasi hasil installer, port aplikasi dan MySQL hanya bind ke
`127.0.0.1`, sehingga akses publik harus melalui reverse proxy HTTPS.

Saat container dimulai, identitas provider dibuat dari `APP_NAME` dan
`DOMAIN_NAME`, lalu disinkronkan ke seluruh file `providers_data.json` yang
digunakan aplikasi. Gunakan `PROVIDER_NAME` atau `PROVIDER_DOMAIN_URL` sebelum
menjalankan installer bila identitas provider perlu berbeda.

Jika `.env` sudah ada, installer berhenti agar secret tidak tertimpa. Untuk
instalasi ulang yang disengaja:

```bash
FORCE_INSTALL=1 ./install/vps-install.sh
```

Konfigurasi lama akan dicadangkan sebagai `.env.backup.TIMESTAMP`. Instalasi
ulang tidak menghapus volume atau database yang sudah ada. Karena itu akun admin
yang sama juga tidak akan diduplikasi.

## Instalasi noninteraktif

Cocok untuk cloud-init atau provisioning automation. Semua nilai yang sensitif
sebaiknya dikirim dari secret manager:

```bash
NON_INTERACTIVE=1 \
APP_NAME="Nama Jaringan Saya" \
DOMAIN_NAME="ads.example.com" \
ADMIN_EMAIL="owner@example.com" \
ADMIN_NAME="Owner" \
ADMIN_WHATSAPP="628123456789" \
ADMIN_PASSWORD="password-acak-minimal-12-karakter" \
./install/vps-install.sh
```

Nilai opsional: `APP_HTTP_PORT`, `COMPOSE_PROJECT_NAME`, `DB_DATABASE`,
`DB_USERNAME`, `DB_PASSWORD`, `DB_ROOT_PASSWORD`, `KCE_TRACKING_SECRET`,
`PROVIDER_NAME`, dan `PROVIDER_DOMAIN_URL`.

## Reverse proxy Nginx dan HTTPS

Container aplikasi tersedia pada port 8080 secara default. Salin template lalu
ganti `example.com` dengan domain Anda:

```bash
sudo apt-get update
sudo apt-get install -y nginx certbot python3-certbot-nginx
sudo cp install/nginx-myadnetwork.conf /etc/nginx/sites-available/myadnetwork
sudo sed -i 's/example.com/ads.example.com/g' /etc/nginx/sites-available/myadnetwork
sudo ln -s /etc/nginx/sites-available/myadnetwork /etc/nginx/sites-enabled/myadnetwork
sudo nginx -t
sudo systemctl reload nginx
sudo certbot --nginx -d ads.example.com
```

Jika `APP_HTTP_PORT` bukan 8080, sesuaikan `proxy_pass` pada konfigurasi Nginx.
Setelah HTTPS aktif, pastikan `DOMAIN_NAME` dan `KCE_APP_URL` pada `.env` benar,
lalu jalankan `docker compose up -d`.

Panel admin kemudian tersedia di `https://ads.example.com/admin/login.php`.

## Menjadwalkan cronjob

Gunakan `docker compose exec` dari cron milik host. Ganti
`/opt/myadnetwork` dengan lokasi repository sebenarnya:

```cron
*/10 * * * * cd /opt/myadnetwork && docker compose exec -T web php bin/cron.php mapping-local >> /var/log/myadnetwork-cron.log 2>&1
15 0 * * * cd /opt/myadnetwork && docker compose exec -T web php bin/cron.php recap-local >> /var/log/myadnetwork-cron.log 2>&1
30 0 * * * cd /opt/myadnetwork && docker compose exec -T web php bin/cron.php recap-publisher >> /var/log/myadnetwork-cron.log 2>&1
45 0 * * * cd /opt/myadnetwork && docker compose exec -T web php bin/cron.php calculate-budget >> /var/log/myadnetwork-cron.log 2>&1
```

Daftar job dan frekuensi lain dijelaskan di [CRON_SETUP.md](./CRON_SETUP.md).
Pastikan user cron memiliki akses ke Docker.

## Operasional rutin

Status, log, dan health check:

```bash
docker compose ps
docker compose logs --tail=200 web db
curl --fail http://127.0.0.1:8080/health.php
```

Memperbarui aplikasi:

```bash
git pull --ff-only
docker compose up -d --build
```

Backup database:

```bash
docker compose exec -T db sh -c 'mysqldump -uroot -p"$MYSQL_ROOT_PASSWORD" "$MYSQL_DATABASE"' > backup.sql
```

Backup juga named volumes upload. `docker compose down` menghentikan stack tanpa
menghapus data. Jangan gunakan `docker compose down -v` di produksi karena opsi
`-v` menghapus database dan seluruh volume file aplikasi.

## Mengganti atau membuat admin

Jalankan perintah berikut dari repository. Opsi `--update` diperlukan jika email
sudah ada:

```bash
docker compose exec -T \
  -e ADMIN_EMAIL="owner@example.com" \
  -e ADMIN_PASSWORD="password-baru-minimal-12-karakter" \
  -e ADMIN_NAME="Owner" \
  -e ADMIN_WHATSAPP="628123456789" \
  web php bin/create-admin.php --update
```

## Pemecahan masalah

- `docker daemon tidak dapat diakses`: jalankan dengan `sudo`, atau tambahkan user
  deployment ke group `docker`, lalu login ulang.
- Database belum siap: lihat `docker compose logs db`; import awal dapat memakan
  beberapa menit pada VPS lambat.
- HTTP 502 dari Nginx: pastikan `docker compose ps` menunjukkan service `web`
  aktif dan port `proxy_pass` sama dengan `APP_HTTP_PORT`.
- Admin tidak dapat login: reset password memakai `bin/create-admin.php --update`.
