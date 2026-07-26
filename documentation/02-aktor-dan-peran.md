# Aktor dan Peran

## 1. User terdaftar (Publisher / Advertiser) — tabel `msusers`

Aplikasi ini **tidak memisahkan akun Publisher dan Advertiser**. Semua pengguna publik yang mendaftar lewat `public_html/reg.php` masuk ke satu tabel `msusers` (`sql/kumpulbl_kbc_hanya_structure.sql:451-478`). Setelah login, dashboard menu (`public_html/main_menu.php:22-23`) menampilkan dua tombol menu terpisah — **Advertiser** dan **Publisher** — yang keduanya bisa diakses oleh akun yang sama. Peran ditentukan murni oleh *tindakan* yang dilakukan akun tersebut:

- Menjadi **Publisher** begitu akun menambahkan situs lewat `add_site.php` (baris ke tabel `publishers_site`).
- Menjadi **Advertiser** begitu akun membuat iklan lewat `add_advertisement.php` (baris ke tabel `advertisers_ads`).

Field penting di `msusers` yang dipakai lintas peran:
- Identitas & keamanan login: `loginemail`, `passwords` (hash `password_hash`), `pass_phrase`, `number_last_login_attempt`, `forgot_password_key`.
- Info bank untuk pencairan dana publisher: `bank`, `account_number`, `account_name`.
- Akumulasi revenue (peran publisher): `current_revenue`, `local_revenue_paid`, `local_revenue_unpaid`, `current_revenue_from_partner`, `partner_revenue_paid`, `partner_revenue_unpaid`, `total_current_revenue`.
- Akumulasi spending (peran advertiser): `current_spending`, `current_spending_from_partner`, `total_current_spending`.

Registrasi (`public_html/reg.php:39-78`) hanya meminta email + WhatsApp, memverifikasi reCAPTCHA v3, lalu meng-generate password acak yang dikirim ke email — tidak ada pemilihan "jenis akun" saat mendaftar.

## 2. Admin — tabel `msadmin`

Superuser platform, terpisah total dari `msusers` (skema di `sql/kumpulbl_kbc_hanya_structure.sql:432-443`), login lewat `public_html/admin/login.php` dengan session key berbeda (`$_SESSION['loggedin']`, `$_SESSION['loginemail_admin']` — dipakai konsisten di semua file `public_html/admin/*.php`). Admin **tidak** menggunakan `msusers`, sehingga tidak bisa sekaligus jadi publisher/advertiser biasa lewat akun admin yang sama.

Hak akses admin (lihat `10-admin-dan-approval.md` untuk detail lengkap):
- Approve/moderasi iklan (`admin/manage_ads.php`, `admin/update_publish_status.php`).
- Kelola & lihat data publisher (`admin/manage_publishers.php`), user (`admin/manage_users.php`).
- Approve permintaan join partner network (`admin/manage_partner_request.php`, `admin/approval_join_force.php`, `admin/approval_join_force2.php`, `admin/join_force.php`).
- Catat pembayaran manual ke publisher lokal & partner (`admin/pay_pubs_local.php`, `admin/pay_pubs_partner.php`, `admin/pay_provider_partner.php`).
- Kelola aturan anti-fraud (`admin/list_setting_rule_clicks.php`, `admin/update_threshold.php`, `admin/list_setting_list_ip_banned.php`, `admin/list_setting_list_browser_banned.php`).
- Kelola kuota penulisan artikel AI publisher (`admin/manage_writer_quotas.php`).
- Forensik klik & aktivitas (`admin/publisher_click_forensics.php`, `admin/latest_recognized_clicks.php`, `admin/top_active_publishers.php`, `admin/rekap_user_local_click.php`).

## 3. Provider / Partner Network — tabel `providers`, `providers_partners`, `providers_request`, `providers_contact_person`

Bukan akun pengguna manusia, melainkan **representasi jaringan/instance lain** dari platform yang sama (lihat `05-provider-partner-network.md`):
- `providers` (`sql/...:568-580`) — identitas jaringan **milik sendiri** (baris `id=1`), berisi `hash_key`/`secret_key` untuk menandatangani hash klik/audit.
- `providers_partners` (`sql/...:622-647`) — daftar jaringan mitra eksternal yang sudah/berencana "join force", lengkap dengan `public_key`/`secret_key` untuk otentikasi API antar-server, status `isapproved`, `is_hold` (untuk menahan sementara tayangan iklan dari mitra tertentu).
- `providers_request` (`sql/...:655-668`) — log permintaan join yang masuk dari jaringan lain, menunggu approval admin.
- `providers_contact_person` / `..._sync` — data kontak & rekening bank penanggung jawab tiap provider, dipakai untuk pembayaran antar-provider.

## 4. Influencer (pemilik media) — tabel `influencer_media`, `media`

Bukan tabel akun terpisah — pemilik media adalah **user `msusers` yang sudah login** (`owner_id` di `influencer_media` merujuk ke `msusers.id`, lihat `public_html/add_media_influencer.php:15,68`), mendaftarkan kanal media sosial/kontennya sebagai slot yang bisa dibeli advertiser. `media` adalah tabel referensi jenis kanal (nama, ikon, deskripsi — mis. Instagram, TikTok, dsb.).

## Ringkasan matriks akses

| Aktor | Tabel akun | Cara masuk | Menu utama |
|---|---|---|---|
| Publisher | `msusers` | `login.php` | `publisher_menu.php`, `include_publisher_menu.php` |
| Advertiser | `msusers` (akun sama dgn publisher) | `login.php` | `advertiser_menu.php`, `include_advertiser_menu.php` |
| Influencer (media owner) | `msusers` (akun sama) | `login.php` | `add_media_influencer.php`, `mymedia.php`, `edit_media.php` |
| Admin | `msadmin` | `admin/login.php` | `admin/sidebar_menu.php`, `admin/dashboard_admin.php` |
| Provider/Partner | `providers` / `providers_partners` (bukan akun manusia) | API server-to-server (`public_html/API/*`) | — (tidak ada UI login untuk partner; interaksi lewat admin panel operator masing-masing) |
