# DigitalOcean Marketplace Droplet 1-Click

> Navigasi: [Runbook instalasi](../OPERATIONS_RUNBOOK.md#5-deploy-instalasi-pertama) · [VPS](./VPS_INSTALLATION.md) · [Docker](./DOCKER_DEPLOYMENT.md) · [Operasi rutin](../OPERATIONS_RUNBOOK.md#13-ritme-operasi)

Implementasi ini membangun snapshot Ubuntu 24.04 memakai Packer. Snapshot berisi
Docker, source MyAdNetwork, image aplikasi yang sudah dibangun, firewall UFW, dan
service setup pertama. Saat pelanggan membuat Droplet, service tersebut otomatis:

1. membaca IP publik dari metadata Droplet;
2. membuat seluruh secret dan password secara acak;
3. menginisialisasi MySQL dan struktur database;
4. membuat akun admin pertama;
5. menjalankan aplikasi pada port 80 dan mengaktifkan jadwal cron;
6. menyimpan kredensial dengan permission root-only.

DigitalOcean mendefinisikan Droplet 1-Click sebagai image Droplet yang sudah
dikonfigurasi. Referensi resmi:

- [Droplet 1-Click Apps](https://docs.digitalocean.com/products/marketplace/droplet-1-click-apps/)
- [Packer templates resmi DigitalOcean](https://github.com/digitalocean/droplet-1-clicks)
- [Validator Marketplace canonical](https://github.com/digitalocean/marketplace-partners)
- [Marketplace Vendor Portal](https://marketplace.digitalocean.com/vendors)

## File implementasi

- `marketplace/digitalocean/myadnetwork.pkr.hcl`: definisi build snapshot.
- `marketplace/digitalocean/plugins.pkr.hcl`: plugin Packer DigitalOcean.
- `marketplace/digitalocean/scripts/package.sh`: membuat archive hanya dari commit Git.
- `marketplace/digitalocean/scripts/install-image.sh`: memasang Docker dan aplikasi.
- `marketplace/digitalocean/first-boot.sh`: provisioning unik setiap Droplet.
- `marketplace/digitalocean/myadnetwork-first-boot.service`: systemd oneshot.
- `marketplace/digitalocean/myadnetwork.cron`: jadwal pekerjaan periodik.
- `marketplace/digitalocean/scripts/cleanup-image.sh`: membersihkan dan memvalidasi image.

## Prasyarat build

- Akun dan Team DigitalOcean untuk vendor.
- Personal access token dengan izin membuat/menghapus Droplet dan snapshot.
- Packer 1.10 atau lebih baru, Git, dan shell POSIX.
- Semua perubahan yang akan masuk image sudah di-commit. Packaging memakai
  `git archive`, sehingga `.env`, file untracked, dan `.git` tidak pernah masuk snapshot.

Jangan menaruh token dalam file variable atau repository. Export sementara:

```bash
export DIGITALOCEAN_API_TOKEN="dop_v1_xxx"
export PKR_VAR_do_api_token="$DIGITALOCEAN_API_TOKEN"
```

## Validasi dan build snapshot

Dari root repository:

```bash
packer init marketplace/digitalocean
packer fmt -check marketplace/digitalocean
packer validate marketplace/digitalocean
packer build \
  -var application_version="1.0.0" \
  -var region="sgp1" \
  marketplace/digitalocean
```

Build membuat Droplet sementara ukuran `s-2vcpu-4gb`, menginstal aplikasi,
menjalankan validator resmi terbaru, membersihkan identity image, membuat snapshot,
kemudian menghapus Droplet builder. Proses ini menimbulkan biaya selama builder
dan snapshot tersimpan.

Nama snapshot bisa diubah:

```bash
packer build \
  -var snapshot_name="myadnetwork-1-0-0-ubuntu-24-04" \
  marketplace/digitalocean
```

## Uji snapshot sebelum submission

1. Buat Droplet baru dari snapshot di Control Panel.
2. Gunakan minimal 2 vCPU, RAM 4 GB, dan disk 50 GB.
3. Tunggu setup selesai:

   ```bash
   systemctl status myadnetwork-first-boot
   journalctl -u myadnetwork-first-boot --no-pager
   ```

4. Tampilkan kredensial:

   ```bash
   sudo cat /root/.myadnetwork_credentials
   ```

5. Uji URL utama, `/health.php`, dan `/admin/login.php`.
6. Reboot Droplet dan pastikan first-boot tidak berjalan ulang.
7. Pastikan firewall hanya membuka OpenSSH dan HTTP:

   ```bash
   sudo ufw status
   docker compose -f /opt/myadnetwork/docker-compose.yml ps
   ```

Snapshot awal melayani HTTP agar aplikasi dapat diakses langsung memakai IP.
Sebelum produksi, pelanggan harus memasang domain dan HTTPS mengikuti
[VPS_INSTALLATION.md](./VPS_INSTALLATION.md). Sesudah reverse proxy aktif,
ubah `APP_BIND_ADDRESS=127.0.0.1` dan port aplikasi sesuai konfigurasi Nginx.

## Submission Marketplace

1. Daftar atau masuk sebagai vendor di Vendor Portal.
2. Buat listing **Droplet 1-Click App** dan pilih Team pemilik snapshot.
3. Pilih snapshot hasil Packer sebagai base image.
4. Isi versi aplikasi, Ubuntu 24.04, kategori, deskripsi, software list,
   minimum Droplet, support URL/email, dan instruksi getting started.
5. Gunakan langkah pada bagian berikut sebagai teks getting started.
6. Preview, deploy, dan uji listing dengan anggota Team.
7. Kirim listing untuk review DigitalOcean.

Publishing ke katalog tetap memerlukan review dan persetujuan DigitalOcean;
repository hanya mengotomatisasi pembuatan image yang diajukan.

## Teks getting started untuk listing

```text
MyAdNetwork melakukan setup otomatis ketika Droplet pertama kali boot.
SSH sebagai root, lalu jalankan:

sudo cat /root/.myadnetwork_credentials

File tersebut berisi URL aplikasi dan login admin awal. Segera ganti kredensial,
arahkan DNS domain ke Droplet, dan aktifkan HTTPS. Status setup dapat diperiksa
dengan: systemctl status myadnetwork-first-boot
```

## Membuat rilis image baru

1. Perbarui kode dan dependency, lalu jalankan pengujian aplikasi.
2. Commit perubahan dan tentukan nomor versi baru.
3. Jalankan `packer init`, `fmt`, `validate`, lalu `build`.
4. Uji Droplet baru dari snapshot, termasuk reboot dan first-boot.
5. Ajukan snapshot baru pada versi listing di Vendor Portal.
6. Simpan image lama sampai versi baru lolos review dan tersedia.

Jangan membangun ulang snapshot lama dengan nama versi yang sama; setiap rilis
harus dapat dilacak ke commit dan nomor versi tertentu.

## Troubleshooting

- Build context tidak memuat perubahan: commit perubahan terlebih dahulu karena
  packaging hanya mengambil `HEAD`.
- Packer gagal autentikasi: periksa scope token dan `PKR_VAR_do_api_token`.
- Validator gagal: perbaiki semua temuan sebelum submission; jangan menonaktifkan
  validator.
- Setup Droplet gagal: lihat `journalctl -u myadnetwork-first-boot` dan
  `docker compose -f /opt/myadnetwork/docker-compose.yml logs`.
- Kredensial tidak ada: first-boot belum selesai atau gagal sebelum tahap akhir.
