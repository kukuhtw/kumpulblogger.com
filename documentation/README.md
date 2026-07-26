# Dokumentasi Proses Bisnis — KumpulBlogger (kbc)

> Dokumentasi ini disusun berdasarkan pembacaan langsung kode sumber PHP di `public_html/` dan skema database di `sql/kumpulbl_kbc_hanya_structure.sql`. Semua klaim bisnis merujuk ke file & baris kode nyata (format `path:line`) supaya bisa ditelusuri ulang. Bagian yang tidak sepenuhnya jelas dari kode ditandai eksplisit sebagai **"perlu konfirmasi"**.

## Apa aplikasi ini

Repo ini adalah source code **KumpulBlogger.com** — platform *native ads / content monetization* asal Indonesia yang menghubungkan **Publisher** (pemilik website/blog) dengan **Advertiser** (pengiklan) melalui model PPC (pay-per-click) berbasis native ads (kartu iklan bergambar + judul + deskripsi), mirip konsep MGID/Taboola versi lokal. Selain itu terdapat:

- Jaringan **Provider/Partner** (B2B) — mekanisme white-label & syndication yang memungkinkan beberapa instance KumpulBlogger yang berbeda domain saling berbagi inventori iklan dan inventori publisher ("join force"), lihat `public_html/white_label/index.php`.
- Fitur **konten & AI** untuk publisher: generator artikel AI, gambar AI, ringkasan audio, kuis, dan ide topik — bagian dari "Blog Internal" agar publisher yang tidak punya website sendiri tetap bisa menjadi penerbit konten dan menayangkan iklan.
- **Influencer marketing** — katalog media sosial influencer yang bisa "dibeli slot"-nya oleh advertiser.
- Panel **Admin** terpisah (`public_html/admin/`) untuk approval, moderasi, dan pencatatan pembayaran manual.
- **Cronjob** (`public_html/cronjob/`) untuk pencocokan iklan↔situs, audit anti-fraud, rekap harian, dan sinkronisasi data dengan jaringan partner.

## Arsitektur singkat

- **PHP monolith prosedural** (bukan framework MVC) — setiap halaman adalah file `.php` mandiri yang meng-`include` file utilitas bersama (`db.php`, `function.php`, `function_ads.php`, `function_publisher.php`, `function_provider*.php`).
- **MySQL** (MySQLi + PDO dipakai campur di berbagai file) — skema di `sql/kumpulbl_kbc_hanya_structure.sql`.
- **Sesi PHP native** (`$_SESSION['user_id']` untuk user biasa, `$_SESSION['loggedin']` + `$_SESSION['loginemail_admin']` untuk admin) — tidak ada JWT/OAuth.
- **Ad-tag JavaScript** (`show_ads_native.js.php` dan varian-nya) — publisher menempelkan `<script src=".../show_ads_native.js.php?pubId=...">` di situsnya; skrip men-generate HTML iklan via `document.write`.
- **Cronjob PHP** dipanggil terjadwal (cron OS, bukan queue) untuk pencocokan iklan, audit klik, rekap, dan sinkronisasi antar-provider via HTTP API (`public_html/API/`).
- **Konfigurasi identitas provider** disimpan di `public_html/providers_data.json` (dibaca via `get_providers_domain_url_json()` / `getProvidersNameById_JSON()` di `public_html/function.php:280-307` dan `public_html/config.php:92-114`) — bukan query DB langsung, untuk performa.
- Tidak ada payment gateway terintegrasi — pembayaran ke publisher/provider dicatat **manual oleh admin** setelah verifikasi transfer bank di luar sistem (lihat `07-pembayaran-dan-revenue-share.md`).

## Daftar aktor

| Aktor | Tabel utama | Ringkasan |
|---|---|---|
| **Publisher** | `msusers`, `publishers_site`, `publishers_site_partners` | Pemilik situs yang menayangkan iklan dan mendapat revenue share per klik. |
| **Advertiser** | `msusers`, `advertisers_ads`, `advertisers_ads_partners` | Pengiklan yang membuat iklan native, menentukan budget & bid per klik. |
| **Provider/Partner** | `providers`, `providers_partners`, `providers_request`, `providers_contact_person` | Instance/white-label KumpulBlogger lain yang terhubung sebagai mitra syndication B2B. |
| **Influencer (media owner)** | `influencer_media`, `hasil_belanja_influencer` | Publisher yang mendaftarkan akun media sosial/kanal sebagai slot promosi berbayar. |
| **Admin** | `msadmin` | Superuser yang meng-approve iklan, situs, partner, dan mencatat pembayaran. |

Catatan penting: **Publisher dan Advertiser bukan tabel akun terpisah** — keduanya adalah *peran* dari satu jenis akun `msusers` (satu email bisa sekaligus menjadi publisher dan advertiser), lihat `02-aktor-dan-peran.md`.

## Indeks dokumen

1. [01-gambaran-umum-bisnis.md](01-gambaran-umum-bisnis.md) — model bisnis inti & value proposition.
2. [02-aktor-dan-peran.md](02-aktor-dan-peran.md) — aktor, hak akses, tabel terkait.
3. [03-alur-publisher.md](03-alur-publisher.md) — end-to-end perjalanan publisher.
4. [04-alur-advertiser.md](04-alur-advertiser.md) — end-to-end perjalanan advertiser.
5. [05-provider-partner-network.md](05-provider-partner-network.md) — jaringan provider/partner B2B & white label.
6. [06-ad-serving-dan-tracking-klik.md](06-ad-serving-dan-tracking-klik.md) — ad-tag JS, tracking klik, anti-fraud.
7. [07-pembayaran-dan-revenue-share.md](07-pembayaran-dan-revenue-share.md) — perhitungan revenue, rekap, payout.
8. [08-konten-artikel-dan-ai-tools.md](08-konten-artikel-dan-ai-tools.md) — blog internal & tools AI.
9. [09-influencer-marketing.md](09-influencer-marketing.md) — katalog media influencer & checkout.
10. [10-admin-dan-approval.md](10-admin-dan-approval.md) — panel admin & moderasi.
11. [11-cronjob-dan-otomatisasi.md](11-cronjob-dan-otomatisasi.md) — daftar & fungsi cronjob.
12. [12-skema-database.md](12-skema-database.md) — peran bisnis tiap tabel.

## Hal yang ditandai "perlu konfirmasi"

Lihat masing-masing dokumen untuk detail; ringkasannya:
- Mekanisme pemicu (trigger) cronjob — apakah dijalankan via cPanel cron, systemd timer, atau manual — tidak ada file konfigurasi cron di repo ini.
- Beberapa file legacy (`dam.php`, `dambs.php`) sudah dinonaktifkan ("This script has expired") — kemungkinan versi lama dari engine mapping yang sudah digantikan oleh `cronjob/mapping_ads_publisher*.php`.
- Alur approval `providers_request` → `providers_partners` (`admin/approval_join_force.php`, `approval_join_force2.php`) hanya dibaca sebagian; detail penuh ada di `05-provider-partner-network.md`.
