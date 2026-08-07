# Admin dan Approval

> Navigasi: [Runbook bootstrap admin](../OPERATIONS_RUNBOOK.md#6-bootstrap-operator) · [Referensi panel](../reference/ADMIN_PANEL.md) · [Fraud](./06-ad-serving-dan-tracking-klik.md) · [Pembayaran](./07-pembayaran-dan-revenue-share.md)

Panel admin (`public_html/admin/`) adalah aplikasi terpisah dengan sesi login sendiri (`msadmin`, lihat `02-aktor-dan-peran.md`). Struktur menu lengkapnya terlihat di `admin/sidebar_menu.php:1-92`, dikelompokkan berikut.

## 1. Kelola jaringan mitra (Manage Partner)

| Halaman | Fungsi |
|---|---|
| `join_force.php` | Mengirim permintaan kemitraan ke provider lain (lihat `05-provider-partner-network.md`). |
| `manage_partner.php` | Lihat/kelola daftar `providers_partners` yang sudah terhubung. |
| `manage_partner_request.php` | Lihat daftar permintaan masuk (`providers_request`) dari provider lain yang ingin join. |
| `approval_join_force.php` / `approval_join_force2.php` | Menyetujui permintaan masuk, memanggil API `approve_request_partnership` di server pemohon dan menerima kredensial. |
| `change_code_provider.php` | Mengubah `providers_code` milik jaringan sendiri (kode rahasia yang dipertukarkan saat proses join). |
| `pay_provider_partner.php` / `list_payment_provider_partner.php` | Catat & lihat settlement pembayaran B2B antar-provider. |
| `sync_databank.php` | Sinkronisasi info rekening bank kontak provider mitra. |

## 2. Kelola user & publisher

| Halaman | Fungsi |
|---|---|
| `manage_users.php` | Daftar semua `msusers` dengan ringkasan revenue (paid/unpaid, lokal/partner), bisa dicari & diurutkan (`admin/manage_users.php:32-88`). Tautan "Detail" mengarah ke `rekap_user_local_click.php`. |
| `manage_publishers.php` | Daftar seluruh `publishers_site`, join ke pemiliknya, dengan rate & revenue per situs, bisa dicari/diurutkan. |
| `entry_bank_account.php` / `fetch_bank_details.php` | Kelola data rekening bank (untuk keperluan pembayaran). |
| `manage_writer_quotas.php` | Kelola kuota artikel AI per publisher (`publisher_quota`, lihat `08-konten-artikel-dan-ai-tools.md`). |

## 3. Approval & moderasi iklan

| Halaman | Fungsi |
|---|---|
| `manage_ads.php` | Daftar semua `advertisers_ads` dengan filter status (paid, published, paused, expired) dan pencarian judul. |
| `update_publish_status.php` | Endpoint yang benar-benar mengubah `ispublished`, `published_date`, `is_paid`, `paid_date` — **inilah titik approval iklan** setelah admin memverifikasi bukti transfer advertiser (lihat `04-alur-advertiser.md` §2). |
| `manage_ads_partner.php` | Versi untuk iklan yang direplikasi dari jaringan partner (`advertisers_ads_partners`). |

> Catatan penting: mapping iklan↔publisher (`mapping_advertisers_ads_publishers_site`) **tidak** memerlukan approval admin — itu sepenuhnya otomatis lewat cronjob (lihat `03-alur-publisher.md` §3) begitu iklan sudah `ispublished=1`. Admin hanya menjadi gerbang di level **iklan** (apakah boleh masuk ke sistem sama sekali) dan **kualitas jaringan** (ban IP/browser, hold provider), bukan gerbang per-pasangan iklan-situs.

## 4. Pembayaran

| Halaman | Fungsi |
|---|---|
| `pay_pubs_local.php` | Catat pembayaran manual ke publisher untuk revenue lokal (`payment_local_pubs`). |
| `pay_pubs_partner.php` | Catat pembayaran manual untuk revenue dari jaringan partner (`payment_partner_pubs`), sekaligus menyegarkan `publisher_partner.revenue_paid/unpaid`. |
| `list_payment_pubs_local.php` / `list_payment_pubs_partner.php` | Riwayat pembayaran publisher. |
| `list_pubs_partner_revenue.php` / `list_owner_pubs_partner_revenue.php` | Laporan revenue publisher dari trafik partner, dari dua sudut pandang (per situs vs. per pemilik akun). |

Detail penuh alur dana ada di `07-pembayaran-dan-revenue-share.md`.

## 5. Anti-fraud & keamanan klik

| Halaman | Fungsi |
|---|---|
| `list_setting_rule_clicks.php` + `update_threshold.php` | Lihat/ubah ambang batas 16 aturan velocity klik (`setting_rule_clicks`, kode `aa`–`ap`). |
| `list_setting_list_ip_banned.php` | Kelola daftar IP yang diblokir (manual maupun hasil auto-ban cronjob). |
| `list_setting_list_browser_banned.php` | Kelola daftar user-agent yang diblokir. |
| `latest_recognized_clicks.php` | Klik terbaru yang lolos audit ("diakui" valid). |
| `top_active_publishers.php` | Peringkat situs publisher paling aktif menampilkan iklan. |
| `publisher_click_forensics.php` | Alat investigasi mendalam pola klik per publisher (indikasi fraud). |
| `rekap_user_local_click.php` | Rincian klik per user, dibuka dari `manage_users.php`. |

Detail mekanisme deteksi lihat `06-ad-serving-dan-tracking-klik.md`.

## 6. Pengaturan akun admin

`change_password.php` — ubah password admin sendiri (`msadmin.passwords`).

## 7. Login admin

`admin/login.php` memakai session terpisah (`$_SESSION['loggedin']`, `$_SESSION['loginemail_admin']`) terhadap tabel `msadmin` — tidak berbagi sesi dengan login `msusers` di `login.php` level root. Setiap halaman admin memvalidasi `$_SESSION['loggedin']` di baris paling atas sebelum memproses apapun (pola konsisten di semua file `admin/*.php` yang dibaca).

## Ringkasan: apa yang butuh approval admin vs. otomatis

| Hal | Approval admin? | Mekanisme |
|---|---|---|
| Registrasi user baru | Tidak | Langsung aktif setelah reCAPTCHA lolos |
| Situs publisher baru | Tidak | Langsung aktif, tapi bisa di-ban admin (`publishers_site.isbanned`) belakangan |
| Iklan baru tayang (`ispublished`) | **Ya** | `admin/update_publish_status.php` setelah verifikasi pembayaran |
| Mapping iklan↔situs | Tidak (auto) | Cronjob `mapping_ads_publisher.php`, auto-approved kedua sisi |
| Klik valid/tidak | Tidak (otomatis sistem) | Cronjob `click_audit.php` berbasis aturan, admin hanya mengatur threshold |
| Kemitraan provider (join force) | **Ya** | `admin/approval_join_force.php` |
| Pencairan dana publisher/provider | **Ya (manual penuh)** | `admin/pay_*.php` setelah transfer bank di luar sistem |

## Tabel database yang terlibat

`msadmin`, `msusers`, `publishers_site`, `advertisers_ads`, `advertisers_ads_partners`, `providers_partners`, `providers_request`, `setting_rule_clicks`, `list_ip_banned`, `list_browser_banned`, `payment_local_pubs`, `payment_partner_pubs`, `payment_partner_providers`, `publisher_quota`.
