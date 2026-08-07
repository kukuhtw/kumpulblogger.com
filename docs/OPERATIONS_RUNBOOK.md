# Runbook Operasional End-to-End MyAdNetwork

> Panduan induk dari instalasi kosong hingga ad network menerima advertiser,
> menayangkan iklan publisher, merekap revenue, dan memproses payout. Gunakan
> [indeks dokumentasi](./README.md) dan tautan pada setiap fase untuk detail.

## 1. Pahami model operasinya

MyAdNetwork adalah native ad network PPC. Satu akun dapat menjadi advertiser
sekaligus publisher. Operator mengelola deployment, moderasi, fraud control,
rekonsiliasi, dan pembayaran. Sistem tidak mempunyai payment gateway otomatis:
dana advertiser dan payout diverifikasi di luar sistem lalu dicatat admin.

Baca sebelum mulai:

- [Model bisnis dan siklus revenue](./guides/01-gambaran-umum-bisnis.md)
- [Aktor dan pembagian peran](./guides/02-aktor-dan-peran.md)
- [Pembayaran dan revenue share](./guides/07-pembayaran-dan-revenue-share.md)
- [Referensi panel admin](./reference/ADMIN_PANEL.md)

## 2. Putuskan kebijakan bisnis

Tetapkan secara tertulis:

| Keputusan | Keluaran minimum |
|---|---|
| Identitas | Nama provider, domain, badan/operator, kontak support |
| Harga | Mata uang, rate publisher, bid minimum, margin/markup |
| Dana masuk | Minimum deposit, rekening, bukti dan SLA verifikasi |
| Payout | Minimum payout, jadwal, biaya, proses koreksi |
| Moderasi | Konten/landing page/publisher terlarang dan SLA approval |
| Fraud | Threshold, blokir IP/browser, review dan proses banding |
| Data | Retensi, RPO/RTO, backup, akses dan respons insiden |

Perilaku aktual kode dijelaskan dalam [alur advertiser](./guides/04-alur-advertiser.md),
[ad serving](./guides/06-ad-serving-dan-tracking-klik.md), dan
[cronjob](./operations/CRONJOB_JOBS.md). Jangan menganggap nilai bawaan kode
sebagai keputusan bisnis yang sudah disetujui.

**Lulus fase:** kebijakan mempunyai nilai, pemilik, dan mekanisme approval.

## 3. Pilih target deployment

- [VPS/Docker](./operations/VPS_INSTALLATION.md)
- [DigitalOcean Marketplace](./operations/DIGITALOCEAN_MARKETPLACE.md)
- [Railway](./operations/RAILWAY_DEPLOYMENT.md)
- [Render](./operations/RENDER_DEPLOYMENT.md)
- [Zeabur](./operations/ZEABUR_DEPLOYMENT.md)
- [Heroku](./operations/HEROKU_DEPLOYMENT.md)

Semua menggunakan [fondasi Docker](./operations/DOCKER_DEPLOYMENT.md). Produksi
memerlukan MySQL 8, HTTPS, persistent storage, scheduler, monitoring, dan backup.
Untuk lebih dari satu web instance, pindahkan file runtime ke object storage.

**Lulus fase:** platform, region, database, storage, domain, biaya dan backup dipilih.

## 4. Siapkan konfigurasi dan secret

Minimal:

```dotenv
APP_NAME=MyAdNetwork
DOMAIN_NAME=ads.example.com
KCE_APP_URL=https://ads.example.com/kce
DB_HOST=mysql.internal
DB_PORT=3306
DB_DATABASE=myadnetwork
DB_USERNAME=myadnetwork
DB_PASSWORD=SECRET
KCE_TRACKING_SECRET=SECRET_ACAK_PANJANG
```

`PROVIDER_NAME` dan `PROVIDER_DOMAIN_URL` bersifat opsional; default mengikuti
nama/domain aplikasi. Siapkan SMTP untuk registrasi/reset password, reCAPTCHA
untuk form publik, serta OpenRouter/NVIDIA bila fitur AI dipakai. Simpan secret
di secret manager platform, tidak di Git atau dokumentasi.

OpenRouter/NVIDIA juga mengaktifkan **Knowledge Commerce Engine (KCE)**: chat AI
yang menemukan artikel dan sponsored content berdasarkan relevansi semantik.
KCE memiliki campaign, wallet, event, dan dashboard tersendiri. Baca
[panduan lengkap KCE](./guides/13-knowledge-commerce-engine.md) sebelum aktivasi.

**Lulus fase:** tidak ada password contoh, secret tersimpan aman, dan database
hanya terbuka bagi jaringan yang diperlukan.

## 5. Deploy instalasi pertama

Ikuti panduan platform sampai health check. Startup container menyiapkan port,
volume, schema (sesuai target), enam `providers_data.json`, lalu proses web/cron.

Installer VPS membuat admin pertama. Pada target lain, jalankan di container:

```bash
ADMIN_EMAIL=owner@example.com \
ADMIN_PASSWORD='PASSWORD_ACAK_PANJANG' \
ADMIN_NAME='Owner' \
ADMIN_WHATSAPP='628123456789' \
php bin/create-admin.php
```

Sesuaikan injeksi environment dengan console platform dan hapus password admin
dari environment setelah command selesai.

### Pemeriksaan pascadeploy

- `/health.php` sehat dan `/admin/login.php` dapat dibuka.
- Log menunjukkan schema tersedia dan enam baris `Provider data:`.
- Seluruh provider JSON memakai identitas instalasi, bukan domain contoh.
- Upload tetap tersedia setelah restart/redeploy.
- TLS valid; database tidak terbuka bebas ke internet.
- Password admin tersimpan di password manager organisasi.

## 6. Bootstrap operator

Masuk sebagai admin, kemudian:

1. Ganti password bootstrap.
2. Isi rekening/kontak provider di `admin/entry_bank_account.php`.
3. Periksa identitas/kode provider melalui `admin/change_code_provider.php`.
4. Tinjau threshold klik dan daftar IP/browser terlarang.
5. Isi `admin/llm_settings.php` hanya jika fitur AI dipakai.
6. Uji menu user, publisher, iklan, pembayaran, dan laporan.

Gunakan [panduan admin](./guides/10-admin-dan-approval.md) dan
[referensi admin per halaman](./reference/ADMIN_PANEL.md). Jangan aktifkan
federasi partner sebelum alur transaksi lokal stabil.

**Lulus fase:** bootstrap diamankan, rekening benar, fraud policy aktif, dan
halaman operasi utama tidak error.

## 7. Aktifkan scheduler

Tanpa scheduler, mapping, audit klik, rekap revenue, dan auto-expire tidak selesai.

- VPS/cPanel: [setup cron](./operations/CRON_SETUP.md)
- Railway: cron services pada panduan Railway
- Render: cron dari `render.yaml`
- Zeabur: service kedua dengan `PROCESS_TYPE=cron`
- Heroku: Heroku Scheduler

Pahami [urutan pipeline](./guides/11-cronjob-dan-otomatisasi.md) dan
[fungsi tiap job](./operations/CRONJOB_JOBS.md). Scheduler cloud umumnya UTC.

**Lulus fase:** log baru, semua job sukses, dan hanya ada satu scheduler aktif.

## 8. Uji advertiser

Gunakan data staging, bukan uang/kampanye pelanggan:

1. Daftar di `/reg.php` dan pastikan email password diterima.
2. Login melalui `/login.php`.
3. Buat iklan: materi, landing page, budget, dan bid per klik.
4. Jalankan konfirmasi pembayaran.
5. Admin mencocokkan bukti eksternal sebelum menyatakan paid/published.
6. Pastikan iklan aktif, tidak pause/expired, dan dapat dimapping.

Ikuti [alur advertiser](./guides/04-alur-advertiser.md) dan
[referensi dashboard](./reference/USER_DASHBOARD.md).

**Lulus fase:** satu iklan uji paid/published dengan bid yang memenuhi rate situs uji.

## 9. Uji publisher dan penayangan

1. Lengkapi profil dan rekening payout.
2. Tambahkan situs eksternal atau blog internal.
3. Periksa domain, deskripsi, dan rate.
4. Tunggu/jalankan mapping scheduler.
5. Periksa mapping serta approval iklan pada situs.
6. Tempel ad-tag JavaScript dengan `pubId` yang benar pada halaman uji.
7. Pastikan kartu iklan tampil tanpa mixed-content atau JavaScript error.

Ikuti [alur publisher](./guides/03-alur-publisher.md) serta
[ad serving dan tracking](./guides/06-ad-serving-dan-tracking-klik.md).

**Lulus fase:** situs uji memiliki mapping aktif dan menampilkan iklan uji.

## 10. Validasi klik hingga revenue

Lakukan satu klik uji wajar, bukan trafik otomatis. Periksa:

1. request diterima `track_click.php` dan baris klik tercatat;
2. job audit mengubah klik pending menjadi valid/rejected;
3. metadata iklan/situs terisi;
4. rekap harian diperbarui;
5. revenue publisher dan spending advertiser berubah;
6. laporan advertiser, publisher, dan admin konsisten.

Gunakan [alur klik sampai payout](./reference/DATABASE_ERD.md#6-alur-data-kunci-dari-klik-sampai-payout)
dan [referensi cron](./operations/CRONJOB_JOBS.md) untuk diagnosis.

**Lulus fase:** klik uji melewati pipeline dan angka dashboard dapat direkonsiliasi.

## 11. Simulasikan payout

Pisahkan langkah keuangan:

1. hitung kewajiban dari klik valid dan saldo unpaid;
2. cocokkan rekening dan lakukan transfer melalui kanal yang disetujui;
3. setelah transfer terverifikasi, catat pembayaran di panel admin;
4. cocokkan paid, unpaid, riwayat, tanggal, nominal dan referensi transfer.

Jangan menandai pembayaran selesai hanya berdasarkan permintaan pengguna. Baca
[alur pembayaran](./guides/07-pembayaran-dan-revenue-share.md) dan
[modul pembayaran admin](./reference/ADMIN_PANEL.md).

**Lulus fase:** bukti bank, catatan admin, dan hasil rekonsiliasi sama.

## 12. Checklist go-live

- [ ] Data uji dihapus atau ditandai jelas.
- [ ] Registrasi, email, reset password, reCAPTCHA dan upload diuji.
- [ ] Kebijakan privasi, syarat, tracking/cookie, pajak dan konten ditinjau.
- [ ] Uptime, error, disk, database dan cron dimonitor.
- [ ] Operator on-call dan eskalasi finance/security ditetapkan.
- [ ] Backup berhasil dan satu restore diuji.
- [ ] Versi aplikasi serta konfigurasi rilis dicatat.
- [ ] Pemilik bisnis, teknis, keuangan dan compliance menyetujui go-live.

## 13. Ritme operasi

### Harian

- Health, error log, cron, kapasitas, antrean moderasi dan alert fraud.
- Deposit baru, status kampanye, budget, iklan paused/expired.

### Mingguan

- Rekonsiliasi spending, revenue, paid/unpaid dan mutasi bank.
- Audit admin, threshold fraud, kualitas landing page/publisher.
- Uji sampel backup dan tinjau biaya platform/API.

### Bulanan

- Tutup buku, arsip laporan dan review margin/fraud loss/SLA.
- Rotasi secret sesuai kebijakan dan patch base image/dependency.
- Regression test dan review partner yang sedang di-hold.

## 14. Backup dan restore

Backup minimal:

- seluruh database MySQL secara konsisten;
- `uploads`, `ai_images`, `banner_mini`, `voice`, dan `JSON`;
- konfigurasi platform (secret disimpan terpisah dan terenkripsi);
- bukti pembayaran sesuai kebijakan retensi.

Simpan salinan terenkripsi/immutable di akun berbeda. Uji restore di lingkungan
terisolasi: restore database/file, gunakan domain staging dan secret baru,
jalankan database prepare, lalu validasi login, health, iklan, laporan, serta
revenue. Catat durasi aktual sebagai RTO/RPO.

## 15. Diagnosis insiden

| Gejala | Pemeriksaan awal |
|---|---|
| Web tidak sehat | Startup log, DB, port, disk, health endpoint, env terbaru |
| Iklan tidak tampil | paid/published/paused/expired, budget-rate, mapping, `pubId`, HTTPS, console |
| Revenue tidak berubah | audit/reject, cron, rekap, domain provider, provider JSON |
| Dugaan fraud | Simpan bukti, tahan payout, review aturan dan sumber trafik |
| Secret bocor | Batasi akses, rotasi, redeploy, cabut credential lama, audit log |

Jangan menghapus bukti/data mentah sebelum investigasi selesai.

## 16. Aktifkan federasi terakhir

Federasi hanya setelah advertiser→publisher→klik→payout lokal lulus. Pelajari:

- [Provider/partner network](./guides/05-provider-partner-network.md)
- [API partner](./reference/API_ENDPOINTS.md)
- [Cron sinkronisasi partner](./operations/CRONJOB_JOBS.md)
- [Settlement provider](./guides/07-pembayaran-dan-revenue-share.md)

Uji dua instance staging: join, rotasi key, sync iklan/publisher, klik silang,
hold partner, dan settlement sebelum menghubungkan produksi.

## 17. Peta dokumentasi

| Kebutuhan | Dokumen |
|---|---|
| Indeks | [README](./README.md) |
| Bisnis/aktor | [Bisnis](./guides/01-gambaran-umum-bisnis.md), [aktor](./guides/02-aktor-dan-peran.md) |
| Pengguna | [Publisher](./guides/03-alur-publisher.md), [advertiser](./guides/04-alur-advertiser.md), [dashboard](./reference/USER_DASHBOARD.md) |
| Klik/fraud | [Ad serving](./guides/06-ad-serving-dan-tracking-klik.md) |
| Admin/payout | [Admin](./guides/10-admin-dan-approval.md), [panel](./reference/ADMIN_PANEL.md), [revenue](./guides/07-pembayaran-dan-revenue-share.md) |
| Cron | [Konsep](./guides/11-cronjob-dan-otomatisasi.md), [setup](./operations/CRON_SETUP.md), [job](./operations/CRONJOB_JOBS.md) |
| Data/API | [Tabel](./guides/12-skema-database.md), [ERD](./reference/DATABASE_ERD.md), [API](./reference/API_ENDPOINTS.md) |
| KCE | [Knowledge Commerce Engine](./guides/13-knowledge-commerce-engine.md), [Admin Article Index](./reference/KCE_ARTICLE_INDEX.md) |
| Deployment | [Docker](./operations/DOCKER_DEPLOYMENT.md) dan panduan platform pada fase 3 |

## 18. Serah terima

- [ ] Kebijakan dan owner tiap proses terdokumentasi.
- [ ] Deployment, TLS, database, storage, scheduler sehat.
- [ ] Secret aman dan akun bootstrap diganti.
- [ ] Identitas provider benar pada enam JSON.
- [ ] Uji advertiser, publisher, klik, revenue, dan payout lulus.
- [ ] Monitoring, alert, backup, restore dan respons insiden diuji.
- [ ] SOP moderasi, fraud, finance dan support tersedia.
- [ ] Partner tetap nonaktif sampai operasi lokal stabil.
