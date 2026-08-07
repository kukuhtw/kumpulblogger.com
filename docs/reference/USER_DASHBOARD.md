# Dokumentasi Dashboard User — `public_html/` (root) — Publisher (Blogger) & Advertiser

> Navigasi: [Runbook advertiser](../OPERATIONS_RUNBOOK.md#8-uji-advertiser) · [Runbook publisher](../OPERATIONS_RUNBOOK.md#9-uji-publisher-dan-penayangan) · [Alur publisher](../guides/03-alur-publisher.md) · [Alur advertiser](../guides/04-alur-advertiser.md)

> Terkait: [ADMIN_PANEL.md](./ADMIN_PANEL.md) (panel admin terpisah, `public_html/admin/`), [API_ENDPOINTS.md](./API_ENDPOINTS.md) (endpoint federasi server-to-server yang dipanggil dari beberapa file di sini, mis. `update_approval_advertiser_partner.php`), [DATABASE_ERD.md](./DATABASE_ERD.md) (skema tabel `msusers`, `advertisers_ads*`, `publishers_site*`, `mapping_advertisers_ads_publishers_site*`, `articles`, `influencer_media`, dll.), [CRONJOB_JOBS.md](../operations/CRONJOB_JOBS.md) (rekalkulasi terjadwal yang beririsan dengan beberapa halaman di sini).
>
> Ada dokumen alur bisnis level tinggi yang sudah ada lebih dulu di `../guides/` (`02-aktor-dan-peran.md`, `03-alur-publisher.md`, `04-alur-advertiser.md`, `08-konten-artikel-dan-ai-tools.md`, `09-influencer-marketing.md`) — dokumen ini melengkapinya dengan **detail per-file** teknis, mengikuti format yang sama seperti [ADMIN_PANEL.md](./ADMIN_PANEL.md).

## 1. Ringkasan

`public_html/` (root, di luar `admin/`, `API/`, `cronjob/`, `blog/`, dll. yang sudah/akan didokumentasikan terpisah) berisi **98 file PHP**. Dokumen ini mencakup **70 di antaranya** — semua halaman yang benar-benar menjadi bagian dari *dashboard user setelah login* (memakai satu sesi `msusers` yang sama untuk peran Publisher/Blogger, Advertiser, dan Influencer/pemilik media). **28 file sisanya sengaja tidak dibahas** karena bukan bagian dashboard — lihat §6.

Karakteristik umum:

- **Satu akun, banyak peran.** Sesuai `docs/guides/02-aktor-dan-peran.md`: tidak ada pemilihan "jenis akun" saat registrasi (`reg.php`). Setelah login, `dashboard.php` menampilkan `main_menu.php` dengan tombol **Advertiser** dan **Publisher** yang sama-sama bisa diakses akun manapun. Peran ditentukan oleh tindakan: menambah situs (`add_site.php`) → jadi publisher; membuat iklan (`add_advertisement.php`) → jadi advertiser. Sub-fitur **Influencer Media** (`add_media_influencer.php` dkk.) adalah lapisan tambahan di atas peran publisher (pemilik media menjual slot, advertiser membelinya).
- **Guard sesi standar**: `session_start(); if (!isset($_SESSION['user_id'])) { header("Location: login.php"); exit(); }` di baris atas — dipakai di halaman dashboard dan endpoint AJAX internal; dua variabel session (`user_id`, `email`) yang di-set oleh `login.php` menjadi kontrak yang diandalkan seluruh dashboard.
- **Guard sesi dan guard kepemilikan.** Endpoint yang menerima ID objek harus memverifikasi bahwa baris tersebut terkait dengan `$_SESSION['user_id']`; laporan klik dan approval mapping utama kini menerapkan pola ini.
- **Koneksi DB dibuka ulang di tiap file** (pola sama seperti `admin/`), umumnya lewat `include("db.php")` lalu `new mysqli(...)` manual — kecuali klaster artikel/AI yang memakai `class Database` dari `config.php`.
- **Dua sistem shared-library berdampingan**: mayoritas halaman meng-include `function.php` (yang berantai meng-include `function_provider.php`, `function_ads.php`, `function_publisher.php`), sementara klaster artikel/AI memakai `config.php` (class `Database`, class `Logger`, helper provider-JSON versi sendiri). Keduanya punya implementasi independen untuk fungsi serupa (lihat temuan §5).
- **File backup/yatim modul artikel telah dibersihkan.** Alur aktif memakai satu editor (`edit_article.php`), satu API (`article_api.php`), dan satu uploader (`upload_image_article.php`).

## 2. Tabel Ringkas & Pengelompokan

| Modul | Halaman | Fungsi singkat | Guard sesi |
|---|---|---|---|
| **A. Auth & Registrasi** | `reg.php` | Registrasi publik: reCAPTCHA v3 → email unik → password acak via email | — (pra-login) |
| | `login.php` | Login `msusers`, set `$_SESSION['user_id']`/`['email']` | — (ini halaman login) |
| | `logout.php` | `session_destroy()` + redirect login | — |
| | `forgot_password.php` | Form lupa password → email link reset | — (pra-login) |
| | `reset_password.php` | Set password baru dari link email | — (pra-login) |
| | `check_username.php` | AJAX cek ketersediaan username (`publisher_quota`) | ✅ |
| **A. Layout Dashboard Bersama** | `dashboard.php` | Landing pasca-login, render `main_menu.php` | ✅ |
| | `main_menu.php` | Partial: sapaan, link Profil/Advertiser/Publisher/Settings/Logout | — (include) |
| | `publisher_menu.php` | Halaman antara → `include_publisher_menu.php` | ✅ |
| | `advertiser_menu.php` | Halaman antara → `include_advertiser_menu.php` | ✅ |
| | `include_publisher_menu.php` | Partial grid 7 tombol menu publisher | — (include) |
| | `include_advertiser_menu.php` | Partial grid 4 tombol menu advertiser | — (include) |
| | `profile.php` | Edit nama/rekening bank (`msusers`) | ✅ |
| | `settings.php` | Ganti password sendiri | ✅ |
| **A. Shared Library** | `config.php` | Config + class `Database`/`Logger` (khusus klaster artikel/AI) | — (library) |
| | `db.php` | Baca `.env`, isi variabel kredensial legacy | — (library) |
| | `function.php` | Entry point library utama (di-include 48+ halaman) | — (library) |
| | `function_ads.php` | Helper insert advertiser/iklan (jalur API federasi) | — (library) |
| | `function_provider.php` | Rekonsiliasi revenue provider/partner | — (library) |
| | `function_provider_request_join.php` | INSERT/UPDATE federasi join provider | — (library) |
| | `function_publisher.php` | Rekalkulasi revenue paid/unpaid publisher | — (library) |
| | `function_send_email.php` | `sendmail()` — kirim email transaksional | — (library) |
| **B. Publisher — Situs & Slot Iklan** | `add_site.php` | Tambah situs publisher eksternal | ✅ |
| | `add_site_internal.php` | Daftar blog internal + kuota artikel AI awal | ✅ |
| | `mysite.php` | Daftar situs milik user, edit, ambil script embed | ✅ |
| | `mysite_ads.php` | Detail 1 situs: iklan yang tayang di situs itu | ✅ |
| **C. Advertiser — Riset Publisher & Approval Mapping** | `view_ads_publishers_mapping.php` | Publisher lokal yang menayangkan 1 iklan + approve/reject | ✅ |
| | `view_ads_publishers_partner_mapping.php` | Sama, untuk mapping jaringan partner | ✅ |
| | `view_rate_publisher.php` | Directory rate situs publisher lokal | ✅ |
| | `view_rate_publisher_partner.php` | Directory rate situs publisher partner | ✅ |
| **D. Publisher — Directory Iklan Tersedia** | `view_advertiser_list.php` | Directory iklan lokal published | ✅ |
| | `view_advertiser_list_partner.php` | Directory iklan partner published | ✅ |
| | `view_ads_sort_by_highest_bid_per_click.php` | Leaderboard iklan by bid per klik | ✅ |
| **E. Laporan Klik** | `clicks_ads_local_detail.php` | Detail klik 1 iklan lokal (advertiser) | ✅ + kepemilikan |
| | `clicks_ads_partner_detail.php` | Detail klik partner dengan guard dan validasi pemilik iklan | ✅ + kepemilikan |
| | `clicks_publisher_ads_partner_detail.php` | Rekap klik semua situs milik user per provider | ✅ + kepemilikan |
| | `clicks_publisher_detail.php` | Detail klik 1 situs — ⚠️ tanpa cek kepemilikan `pub_id` | ✅ (IDOR) |
| | `process_clicks_report_csv.php` | Export CSV klik iklan lokal | ✅ + kepemilikan |
| | `process_clicks_report_csv_partner.php` | Export CSV klik iklan partner | ✅ + kepemilikan |
| **F. Publisher — Konten Artikel & AI Tools** | `add_article.php` | Generate artikel via AI (2 tahap) | ✅ |
| | `edit_article.php` | Edit artikel (editor Quill) | ✅ |
| | `view_edit_articles.php` | Daftar artikel milik publisher | ✅ |
| | `article_api.php` | API JSON: cek kuota, generate (riset web+OpenAI), ambil artikel | ✅ |
| | `upload_image_article.php` | Upload gambar inline editor Quill | ✅ |
| | `generate_ai_images.php` | Generate gambar artikel (OpenAI Images / Replicate lama) | ✅ |
| | `view_ai_images_articles.php` | Daftar artikel + tombol generate/get AI image | ✅ |
| | `generate_audio_summary.php` | Ringkas + TTS artikel jadi MP3 | ✅ |
| | `view_summary_audio_articles.php` | Daftar artikel + tombol generate audio | ✅ |
| | `generate_quiz.php` | Generate Q&A ("Summary FAQ") dari artikel | ✅ |
| | `view_quiz_articles.php` | Daftar artikel + tombol generate FAQ | ✅ |
| | `get_ideas.php` | AJAX 500 ide artikel acak | ✅ |
| **G. Advertiser — Manajemen Iklan** | `add_advertisement.php` | Form buat iklan baru + upload banner | ✅ |
| | `edit_ads.php` | Edit iklan (judul/desk/gambar/budget-per-click) | ✅ |
| | `update_ad.php` | *Nama menyesatkan* — approval publisher atas mapping miliknya | ✅ + kepemilikan |
| | `delete_ads.php` | Hapus iklan milik user | ✅ + kepemilikan |
| | `pause_resume_ads.php` | Toggle pause iklan milik user | ✅ + kepemilikan |
| | `view_ads.php` | List+filter iklan milik sendiri, modal aksi | ✅ |
| | `update_approval_advertiser.php` | Approval advertiser atas mapping lokal | ✅ |
| | `update_approval_advertiser_partner.php` | Approval mapping partner dan sinkronisasi aman server-to-server | ✅ + kepemilikan |
| **H. Influencer Media** | `add_media_influencer.php` | Daftarkan slot media (publisher/pemilik media) | ✅ |
| | `mymedia.php` | List media milik sendiri | ✅ |
| | `edit_media.php` | Edit media milik sendiri (kepemilikan tervalidasi) | ✅ |
| | `delete_media.php` | Hapus media milik sendiri via POST+CSRF | ✅ + kepemilikan |
| | `listmedia.php` | Katalog semua media (advertiser) + keranjang + checkout | ✅ |
| **I. Pembayaran & Invoice** | `mypayment.php` | Riwayat pembayaran yang sudah dicairkan admin | ✅ |
| | `myrevenue.php` | Akumulasi revenue per situs (include lintas-folder ke `admin/function_admin.php`) | ✅ |
| | `list_invoice_payment.php` | Daftar order pembelian influencer media | ✅ |
| | `delete_invoice.php` | Hapus order milik sendiri | ✅ |
| | `confirm_payment.php` | Catat notifikasi "sudah transfer" (bukan payment gateway) | ✅ |
| | `update_paid_desc.php` | Catat notifikasi bukti transfer budget iklan | ✅ |

## 3. Diagram Navigasi & Alur Data

```mermaid
flowchart TD
    LOGIN[login.php] -->|session user_id+email| DASH[dashboard.php]
    DASH --> MENU[main_menu.php]
    MENU --> PROF[profile.php]
    MENU --> SET[settings.php]
    MENU --> PM[publisher_menu.php]
    MENU --> AM[advertiser_menu.php]

    subgraph PUB[Publisher/Blogger]
        PM --> IPM[include_publisher_menu.php]
        IPM --> VAL[view_advertiser_list.php]
        IPM --> VALP[view_advertiser_list_partner.php]
        IPM --> VSORT[view_ads_sort_by_highest_bid_per_click.php]
        IPM --> ASITE[add_site.php]
        IPM --> MSITE[mysite.php]
        IPM --> MREV[myrevenue.php]
        IPM --> MPAY[mypayment.php]
        ASITE -->|redirect| MSITE
        MSITE --> MSADS[mysite_ads.php]
        MSITE -.script embed.-> JSPUB[["show_ads_native*.js.php (luar cakupan)"]]
        MSADS -->|approve mapping| UPDAD[update_ad.php]
        MSITE --> CPD[clicks_publisher_detail.php]
        MSITE --> CPAPD[clicks_publisher_ads_partner_detail.php]
    end

    subgraph ADV[Advertiser]
        AM --> IAM[include_advertiser_menu.php]
        IAM --> VADS[view_ads.php]
        IAM --> AADV[add_advertisement.php]
        IAM --> VRP[view_rate_publisher.php]
        IAM --> VRPP[view_rate_publisher_partner.php]
        AADV -->|redirect| VADS
        VADS --> EADS[edit_ads.php]
        VADS --> PRA[pause_resume_ads.php]
        VADS --> DADS[delete_ads.php]
        VADS --> UPD[update_paid_desc.php]
        VADS --> VAPM[view_ads_publishers_mapping.php]
        VADS --> VAPMP[view_ads_publishers_partner_mapping.php]
        VADS --> CAL[clicks_ads_local_detail.php]
        VADS --> CAP[clicks_ads_partner_detail.php]
        VAPM --> UAA[update_approval_advertiser.php]
        VAPMP --> UAAP[update_approval_advertiser_partner.php]
        UAAP -.POST public_key/secret_key.-> APIPART[["API/approval_advertiser_partner (luar cakupan)"]]
    end

    subgraph CONTENT[Publisher - Konten Artikel and AI]
        MSADS -.tautan dari add_site_internal.-> AART[add_article.php]
        AART -->|AJAX generate+publish| AAPI[article_api.php]
        AART -->|AJAX ide| GIDEAS[get_ideas.php]
        AART -.redirect setelah publish.-> BLOG[["blog/{username}/{pub_id}/{slug} (luar cakupan)"]]
        VEA[view_edit_articles.php] --> EART[edit_article.php]
        EART -->|AJAX upload gambar| UIA[upload_image_article.php]
        VAI[view_ai_images_articles.php] --> GAI[generate_ai_images.php]
        VSUM[view_summary_audio_articles.php] --> GAS[generate_audio_summary.php]
        VQZ[view_quiz_articles.php] --> GQ[generate_quiz.php]
    end

    subgraph MEDIA[Influencer Media]
        AMI[add_media_influencer.php] --> MYM[mymedia.php]
        MYM --> EM[edit_media.php]
        MYM --> DM[delete_media.php]
        LM[listmedia.php] -.session cart, checkout.-> HBI[(hasil_belanja_influencer)]
        HBI --> LIP[list_invoice_payment.php]
        LIP --> CP[confirm_payment.php]
        LIP --> DI[delete_invoice.php]
    end

    MPAY -.->|recalculate publisher revenue| MSU[(msusers)]
    MREV -.->|recalculate local and global revenue| MSU
```

## 4. Detail per Modul

### Modul A — Auth, Registrasi, Layout Dashboard & Shared Library

#### `reg.php`
Form registrasi publik. Verifikasi Google reCAPTCHA v3 (score ≥ 0.5), cek email unik (`SELECT COUNT(*) FROM msusers WHERE loginemail = ?`, prepared), generate password acak 10 karakter, `password_hash()`, INSERT ke `msusers` (`loginemail`, `passwords`, `whatsapp`, `forgot_password_key`, `regdate`) — **tidak ada pemilihan jenis akun**, sesuai `docs/guides/02-aktor-dan-peran.md`. Kirim email berisi password plaintext via `sendmail()`. Tombol "Lanjut ke login" mem-POST email+password ke `login.php` untuk prefill form.

#### `login.php`
Form login `msusers`. Mode `prefill` (dari tombol di `reg.php`) hanya mengisi ulang nilai form, tidak menyentuh DB. Login sungguhan: `SELECT ... WHERE loginemail = ?` (prepared) + `password_verify()`. Sukses → `session_regenerate_id(true)`, set `$_SESSION['user_id']` dan `$_SESSION['email']`, lalu UPDATE `last_login` + reset hitungan gagal. Lima kegagalan berturut-turut mengunci akun selama 15 menit; setelah masa kunci berakhir hitungan dimulai kembali dari nol.

#### `logout.php`
`session_start()` + `session_destroy()` + redirect `login.php`.

#### `forgot_password.php`
reCAPTCHA v3, cek email dengan prepared statement, generate `forgot_password_key` acak dari `random_bytes()`, simpan token melalui query terikat, lalu kirim link `reset_password.php?key=...`.

#### `reset_password.php`
Terima `?key=`, cocokkan ke `msusers.forgot_password_key` memakai prepared statement, validasi password minimal 8 karakter, lalu UPDATE berdasarkan ID+token dan kosongkan key untuk mencegah reuse. Tidak ada pengecekan kedaluwarsa waktu.

#### `check_username.php`
AJAX dengan guard sesi. Cek `username` di **`publisher_quota`** (bukan `msusers`), dipakai `add_site_internal.php`.

#### `dashboard.php`
Landing pasca-login. Guard standar. Render `main_menu.php`.

#### `main_menu.php`
Partial (tanpa guard sendiri). Sapaan email, link Profil/Advertiser/Publisher/Pengaturan/Logout — hub navigasi yang mewujudkan model "satu akun, dua peran".

#### `publisher_menu.php` / `advertiser_menu.php`
Halaman antara identik strukturnya: guard sesi, include `main_menu.php`, lalu include partial menu perannya (`include_publisher_menu.php`/`include_advertiser_menu.php`).

#### `include_publisher_menu.php`
Partial, grid 7 tombol: Advertiser Lokal, Advertiser Partner, Iklan Berjalan, Tambah Site, Site Saya, Pendapatan, Pembayaran — peta navigasi resmi peran Publisher.

#### `include_advertiser_menu.php`
Partial, grid 4 tombol: Iklan Saya, Tambah Iklan, Rate Publisher Lokal, Rate Publisher Partner — peta navigasi resmi peran Advertiser.

#### `profile.php`
Edit `realname`/`bank`/`account_name`/`account_number` di `msusers` — data ini dipakai admin untuk pencairan dana publisher (`admin/fetch_bank_details.php`).

#### `settings.php`
Ganti password sendiri (verifikasi lama, validasi panjang ≥8, `password_hash()`).

#### `config.php`
Bukan halaman — dipakai khusus klaster artikel/AI. Berisi array `$config`, class `Database` (wrapper mysqli), class `Logger`, `get_providers_domain_url_json()`/`getProvidersNameById_JSON()` versi sendiri, `sanitize_input()`/`validate_input()`. Modul lain (Auth/Layout, Publisher situs, Advertiser iklan) tidak meng-include file ini — mereka pakai `function.php`.

#### `db.php`
Baca kredensial dari `.env` **di luar web root**, validasi semua key wajib, isi variabel legacy (`$servername_db` dkk.) untuk kompatibilitas. Setiap halaman tetap harus bikin koneksi `mysqli` sendiri setelahnya.

#### `function.php`
Entry point library utama untuk halaman dashboard. Meng-include `function_provider.php`, `function_ads.php`, `function_publisher.php` — jadi satu `include` memuat seluruh rantai. Isi langsung: `CLICK_SKEY_SECRET`/`build_click_skey()` (HMAC tanda tangan klik, dipakai skrip ad-serving publik di luar cakupan), `is_probable_bot_user_agent()`/`count_recent_clicks()` (anti-fraud), `updateRevenueForUser()` (dipakai `mypayment.php`), berbagai `get_providers_*` (lookup tabel `providers`).

#### `function_ads.php`
`insertAdvertiser()`, `insertAdvertisersAds()`/`updateAdvertisersAds()` — dipakai jalur federasi `API/insert_advertiser`/`API/insert_ads`, bukan dipanggil langsung dari halaman dashboard.

#### `function_provider.php`
`updateProviderRevenue()`, `getProvidersNameById_JSON()` (lihat temuan duplikasi §5), `checkProviderCredentials()` — mekanisme federasi provider/partner, ikut ter-load tiap kali `function.php` di-include meski tidak relevan untuk halaman dashboard biasa.

#### `function_provider_request_join.php`
`insertProvidersRequest()`, `UpdateProviderPartner()` — mendukung alur `API/request_join`. Berisi banyak `echo` debug polos.

#### `function_publisher.php`
`updatePublisherRevenuePaid_unPaid()`, `updateRevenueTotal()` (dipanggil `pay_pubs_partner.php`/admin setelah pembayaran), `rekapTotalPublisherPartner()`, `calculateTotalRevenue()` — rekalkulasi revenue publisher.

#### `function_send_email.php`
`sendmail()` — POST ke API pihak ketiga `aplikasi.kirim.email`. Dipakai `reg.php` dan `forgot_password.php`.

### Modul B — Publisher (Blogger): Kelola Situs & Slot Iklan

#### `add_site.php`
Form tambah situs publisher "eksternal" (situs sudah ada di luar platform). Validasi `rate_text_ads` 10–10.000, generate `public_key`/`secret_key`, INSERT `publishers_site` (`internal_blog` default, tidak diisi eksplisit — beda dari `add_site_internal.php`). Redirect ke `mysite.php`.

#### `add_site_internal.php`
Alur pendaftaran "blog internal" (`{provider_domain}/blog/{username}`, fitur di `public_html/blog/` — luar cakupan). Validasi username via AJAX ke `check_username.php`. Mendaftar sekaligus membuat baris `publishers_site` (`internal_blog=1`) **dan** baris `publisher_quota` (`daily_free_quota=1`) — jadi blog internal otomatis dapat kuota artikel AI gratis. Kalau sudah terdaftar, tampil link langsung ke `add_article.php?pub_id=...`.

#### `mysite.php`
"Site Publisher Saya" — kartu per situs dengan pagination. Tiga aksi POST ke dirinya sendiri: **Update** (ubah rate/deskripsi/kebijakan iklan, sinkron `rate_text_ads` baru ke `mapping_advertisers_ads_publishers_site`), **Delete** (diblokir manual jika situs masih dipakai mapping aktif — dan tombolnya di UI **sengaja dinonaktifkan/disembunyikan** untuk semua situs saat ini, jadi handler backend-nya praktis tak terpicu dari UI manapun), **Ambil Script** (modal kode embed `show_ads_native.js.php`/`show_ads_native_landscape.js.php` — endpoint publik, luar cakupan). Link ke `mysite_ads.php` (iklan per situs) dan `clicks_publisher_detail.php` (revenue).

#### `mysite_ads.php`
Detail satu situs: info rate, lalu iklan yang sudah dipetakan+published+belum-expired (`mapping_advertisers_ads_publishers_site`). Modal "Ubah" per iklan submit ke `update_ad.php` untuk approve/reject (`is_approved_by_publisher`).

### Modul C — Advertiser: Riset Publisher & Approval Mapping Iklan

> Catatan: `view_ads_publishers_mapping.php`/`_partner_mapping.php` dipicu dari alur advertiser (approval mapping iklan miliknya), sedangkan `view_rate_publisher*.php` adalah riset harga sebelum memasang iklan — keduanya termasuk aksi advertiser meski beberapa halaman "_partner"-nya justru meng-include `include_publisher_menu.php` (kemungkinan sisa penataan menu yang belum konsisten).

#### `view_ads_publishers_mapping.php`
Untuk satu iklan lokal (`local_ads_id`), tampilkan ringkasan budget/spending lalu daftar publisher lokal yang menayangkannya (`mapping_advertisers_ads_publishers_site`), sort by rate/budget per klik. Modal "Ubah Persetujuan" submit ke `update_approval_advertiser.php`. Link ke `clicks_ads_local_detail.php`/`clicks_ads_partner_detail.php`.

#### `view_ads_publishers_partner_mapping.php`
Versi lebih lama (layout polos) untuk `mapping_advertisers_ads_publishers_site_from_partners`. Form approve submit ke `update_approval_advertiser_partner.php`. **Bug navigasi**: form pencarian/sort/pagination mengarah ke `view_ads_publishers_mapping.php` (bukan dirinya sendiri) — sisa copy-paste. Juga ada link `{domain}/data/clicks_local_detail.php` yang tidak match nama file klik yang sebenarnya ada — kemungkinan tautan usang.

#### `view_rate_publisher.php`
Directory seluruh `publishers_site` (rate, harga jual ke advertiser lokal = rate × 1.5, kebijakan iklan), search + sort, tanpa filter kepemilikan (memang untuk dilihat siapa saja yang login, sebagai riset harga).

#### `view_rate_publisher_partner.php`
Sama, sumber `publishers_site_partners`, markup ×2 (margin partner lebih besar). **Bug navigasi sama**: search/sort/pagination mengarah ke `view_rate_publisher.php`, bukan dirinya sendiri.

### Modul D — Publisher: Directory Iklan yang Tersedia

#### `view_advertiser_list.php`
Directory iklan lokal `ispublished=1`, search+pagination, read-only (approval dilakukan lewat `mysite_ads.php`).

#### `view_advertiser_list_partner.php`
Sama, sumber `advertisers_ads_partners`. **Bug navigasi**: pagination mengarah ke `view_rate_publisher.php` — pola bug copy-paste yang sama berulang di file "_partner" lama.

#### `view_ads_sort_by_highest_bid_per_click.php`
Leaderboard gabungan (`UNION`) iklan lokal+partner `ispublished=1 AND is_paused=0`, urut `budget_per_click_textads` tertinggi, limit 100 — membantu publisher memilih iklan paling menguntungkan.

### Modul E — Laporan Klik

#### `clicks_ads_local_detail.php`
Detail klik tervalidasi (`isaudit=1 AND is_reject=0`) untuk satu iklan lokal. Kepemilikan **diverifikasi dengan benar** (`advertisers_id = $user_id`, 404 kalau tidak cocok).

#### `clicks_ads_partner_detail.php`
Kembaran laporan di atas untuk `ad_clicks_partner`. Guard login wajib dan iklan diverifikasi melalui `advertisers_ads.advertisers_id = $_SESSION['user_id']` sebelum detail klik dibaca. Filter domain diterapkan juga pada query jumlah baris pagination.

#### `clicks_publisher_ads_partner_detail.php`
Rekap klik semua situs milik user pada satu provider (toggle `?local=`). Kepemilikan **diverifikasi dengan benar** via `JOIN publishers_site ... JOIN msusers`.

#### `clicks_publisher_detail.php`
Detail klik satu situs (`pub_id`). Guard login ada, **tapi tidak ada verifikasi bahwa `pub_id` milik user yang login** — IDOR: user A bisa melihat data klik situs user B lain hanya dengan mengganti `pub_id` di URL.

#### `process_clicks_report_csv.php` / `process_clicks_report_csv_partner.php`
Export CSV klik tervalidasi (`ad_clicks`/`ad_clicks_partner`). Keduanya mewajibkan login, memvalidasi parameter, dan memastikan `local_ads_id`+domain berasal dari iklan milik user sebelum mengeluarkan data sensitif.

### Modul F — Publisher (Blogger): Manajemen Konten Artikel & AI Tools

Semua file di modul ini dilindungi guard sesi standar. Tabel utama: `articles`, `publisher_quota`, `llm_settings` (satu baris config aktif, lihat `docs/reference/ADMIN_PANEL.md` Modul B), `idea_article`.

#### `add_article.php`
Halaman utama pembuatan artikel — form 2 tahap: tahap 1 kirim `fetch('article_api.php', {action:'generate_article'})`; hasil mengisi form tahap 2 (editor CKEditor untuk review). Tombol "View Article" langsung redirect ke `/blog/{username}/{pub_id}/{slug}` — **artikel sudah ter-INSERT saat generate** (oleh `article_api.php`), tombol publish di tahap 2 tidak mengirim apa pun ke server, hanya redirect. Tombol "Get an Idea" membuka modal berisi hasil `get_ideas.php`.

#### `edit_article.php`
Edit `articles` milik user (`WHERE id=? AND publishers_local_id=?`). Editor Quill, upload gambar via `upload_image_article.php`, video handler parsing URL YouTube/Instagram/TikTok. **Tidak ada token CSRF**.

#### `view_edit_articles.php`
Daftar artikel publisher, pagination windowed, link Edit → `edit_article.php`, tombol "View Article" ke `blog/{username}`.

#### `article_api.php`
API JSON internal, tiga action: `check_quota`, `generate_article` (utama), `get_article`. `generate_article`: cegah double-submit (<60 detik), validasi field wajib, ambil config `llm_settings`, **riset web** via OpenAI Responses API (tool `web_search`, minta ≥2 sumber; kalau gagal tetap lanjut generate dengan pagar prompt anti-mengarang), panggil `callOpenAiApi()` (menangani `gpt-5*` dengan `max_completion_tokens`/`reasoning_effort` vs model lain dengan `max_tokens`/`temperature`) yang wajib mengembalikan `{title, html_content, tag}`. Sumber referensi dibangun server sendiri (bukan disalin mentah dari model) lalu ditambahkan ke `html_content`. Generate = langsung publish (`ispublished=1`), tidak ada draft terpisah.

#### `upload_image_article.php`
Upload gambar untuk editor Quill, validasi hanya ekstensi file, simpan ke `uploads/`, balas `{url}`.

#### `generate_ai_images.php`
Dua mode: `action=get` (ambil hasil prediksi Replicate lama, jalur kompatibilitas), atau default (generate prompt via OpenAI Chat lalu panggil OpenAI Images `gpt-image-2`, 1536×1024, gaya "3D cartoon editorial illustration"). UPDATE `articles.images`. Menolak generate ulang kalau sudah terisi.

#### `view_ai_images_articles.php`
Daftar artikel + tombol Generate/Get AI Image atau thumbnail.

#### `generate_audio_summary.php`
Ringkas artikel (OpenAI Chat, **hardcode `gpt-4.1-mini`** — sengaja, karena `llm_settings` sekarang berisi model reasoning `gpt-5` yang tidak kompatibel dengan pemanggilan sederhana di file ini), lalu TTS (`gpt-4o-mini-tts`) ke MP3, simpan `voice/{articleId}.mp3`.

#### `view_summary_audio_articles.php`
Daftar artikel + tombol Generate Audio Summary atau `<audio>` player.

#### `generate_quiz.php`
Generate Q&A esai dari artikel (OpenAI Chat, juga hardcode `gpt-4.1-mini` dengan alasan sama). Tandai `articles.json_quiz` dengan `{"status":"processing"}` sebelum panggil API (cegah generate ganda) — tanpa locking DB eksplisit, ada celah race condition kecil (dampak: double API call, bukan korupsi data).

#### `view_quiz_articles.php`
Daftar artikel + tombol "Generate Summary FAQ" (label UI berbeda dari nama backend `json_quiz`/"quiz" — kosmetik, tidak berdampak fungsional).

#### `get_ideas.php`
AJAX ambil 500 ide acak dari `idea_article`; menolak request tanpa sesi user aktif dengan HTTP 401.

### Modul G — Advertiser: Manajemen Iklan

#### `add_advertisement.php`
Form buat iklan baru. Rate limit 5 iklan/jam per `advertisers_id`. Validasi `budget_per_click_textads` (Rp 30–3.000) dan `budget_allocation` (Rp 5.000–60.000.000). Upload banner wajib ke `banner_mini/`. INSERT `advertisers_ads`, lalu UPDATE `local_ads_id = id` (identitas unik iklan lintas provider). Redirect ke `view_ads.php`.

#### `edit_ads.php`
Handler POST dari modal edit di `view_ads.php`. Update judul/deskripsi/landing/gambar/`budget_per_click_textads` — **`budget_allocation` sengaja tidak bisa diedit**. Dua UPDATE dalam satu transaksi: `advertisers_ads` dan `mapping_advertisers_ads_publishers_site` (menjaga data mapping tetap sinkron).

#### `update_ad.php`
⚠️ **Nama menyesatkan** — bukan "update iklan" secara umum, melainkan endpoint approval **publisher** (dipanggil dari `mysite_ads.php`). Mapping diverifikasi melalui `publishers_site.publishers_local_id = $_SESSION['user_id']` sebelum UPDATE.

#### `delete_ads.php`
Hapus permanen `advertisers_ads` by `id`, dibatasi dengan `advertisers_id = $_SESSION['user_id']`. Request terhadap iklan yang tidak dimiliki ditolak tanpa mengubah data.

#### `pause_resume_ads.php`
Toggle `is_paused`; query SELECT dan UPDATE sama-sama dibatasi ke `advertisers_id = $_SESSION['user_id']`.

#### `view_ads.php`
Halaman "Iklan Saya" — filter+pagination aman (prepared statement dinamis). Modal aksi: Konfirmasi Bayar (→ `update_paid_desc.php`), Pause/Resume, Edit, Hapus (dengan pengecekan UI-only sebelum submit). Link ke laporan klik dan mapping publisher. Murni SELECT, tidak ada side-effect recalculation saat render.

#### `update_approval_advertiser.php`
Approval advertiser untuk mapping **lokal**: UPDATE `is_approved_by_advertiser`+`approval_date_advertiser`. **Berbeda total** dari approval admin (`admin/update_publish_status.php`, mengubah `ispublished`/`is_paid`) — lihat penjelasan 3-lapis approval di §5. Berisi 5 baris `echo` debug sebelum redirect.

#### `update_approval_advertiser_partner.php`
Approval mapping **partner**, lalu meneruskan status ke server provider mitra via POST. Mapping dan iklan diverifikasi terhadap user login; domain provider diambil ulang dari database, bukan dipercaya dari hidden input. `public_key`/`secret_key` hanya dipakai pada header request server-to-server dan tidak dicetak ke respons.

### Modul H — Influencer Media

#### `add_media_influencer.php`
Publisher/pemilik media mendaftarkan slot media sosial/blog untuk dijual ke advertiser (lihat `docs/guides/09-influencer-marketing.md`). Markup dihitung sekali saat insert: `rate_markup_provider = rate_partner = rate_owner / 6` (dibulatkan kelipatan 50), disimpan sebagai kolom terpisah.

#### `mymedia.php`
List `influencer_media WHERE owner_id = user_id`. Hitung ulang `harga_jual_lokal`/`harga_jual_partner` saat render (read-only, tidak ditulis balik ke DB).

#### `edit_media.php`
Edit satu baris `influencer_media` — SELECT & UPDATE keduanya menyertakan `AND owner_id = ?`. Proteksi kepemilikan yang benar.

#### `delete_media.php`
Hapus `influencer_media WHERE id=? AND owner_id=?` melalui form POST dengan token CSRF. Endpoint menolak method selain POST, token yang tidak valid, ID invalid, dan media yang bukan milik user.

#### `listmedia.php`
Halaman advertiser: katalog semua media lintas owner, harga jual dihitung ulang saat tampil. Keranjang di `$_SESSION['cart']`, aksi add/remove/clear/checkout lewat satu form POST. Checkout → INSERT tiap item ke `hasil_belanja_influencer`.

### Modul I — Pembayaran & Invoice

#### `mypayment.php`
Saldo dari `msusers` + riwayat pembayaran yang **sudah dicairkan admin** (`payment_local_pubs`/`payment_partner_pubs_sync`, diisi lewat `admin/pay_pubs_local.php`/`admin/pay_pubs_partner.php`). Memanggil `updateRevenueForUser()` (`function.php`) di awal — **side-effect tulis-ke-DB saat halaman dibuka**.

#### `myrevenue.php`
Include langsung `admin/function_admin.php` — **satu-satunya file di scope ini yang menembus batas folder `admin/`**, memakai `updateLocalRevenue()`/`updateGlobalRevenue()` yang sama dipakai `admin/manage_ads.php`. Menampilkan **akumulasi revenue** (termasuk yang belum cair) per situs, beda konsep dari `mypayment.php` yang fokus **riwayat pencairan**. Side-effect tulis-ke-DB saat dibuka, dengan fungsi recalc yang **berbeda dan independen** dari yang dipakai `mypayment.php` — potensi drift.

#### `list_invoice_payment.php`
Daftar order pembelian influencer media milik advertiser, grouped per `order_id`. Form "Confirm Payment" → `confirm_payment.php`, tombol "Delete Invoice" → `delete_invoice.php`.

#### `delete_invoice.php`
Hapus `hasil_belanja_influencer WHERE order_id=? AND advertiser_id=?` — kepemilikan tervalidasi, dipicu form POST.

#### `confirm_payment.php`
INSERT `log_payment_order_influencer` — murni **notifikasi manual** ("saya sudah transfer"), tidak ada kolom status yang ikut ter-update otomatis. Verifikasi sesungguhnya manual di luar aplikasi.

#### `update_paid_desc.php`
UPDATE `advertisers_ads.paid_desc` (dengan `AND advertisers_id=?`, kepemilikan tervalidasi). Bug kecil: `$stmt->execute()` dipanggil dua kali (idempotent, dampak minor). Sama seperti di atas, hanya deskripsi teks bebas — status `is_paid` sesungguhnya diubah admin lewat `admin/update_publish_status.php`.

## 5. Temuan & Catatan Kualitas Kode

1. ✅ **Akses laporan klik partner diperketat.** `clicks_ads_partner_detail.php` kini mewajibkan sesi aktif dan memverifikasi pemilik iklan sebelum membaca `ad_clicks_partner`.
2. ✅ **Ekspor CSV diperketat.** Endpoint lokal dan partner kini mewajibkan login serta ownership `local_ads_id`+domain sebelum mengirim data klik.
3. ✅ **Kredensial approval partner tidak lagi bocor.** Semua debug sensitif dihapus; mapping/domain diverifikasi dari database dan kredensial hanya dipakai server-to-server.
4. ✅ **Aksi hapus dan pause/resume kini terikat pemilik.** Query SELECT/DELETE/UPDATE menyertakan `advertisers_id = $_SESSION['user_id']`.
5. ✅ **IDOR laporan publisher dan approval mapping ditutup.** `clicks_publisher_detail.php` memverifikasi pemilik situs; `update_ad.php` memverifikasi mapping melalui situs milik user.
6. ✅ **Endpoint AJAX internal kini gated.** `get_ideas.php` dan `check_username.php` mengembalikan HTTP 401 untuk sesi anonim.
7. ✅ **Klaster file yatim/duplikat dibersihkan.** Duplikat forgot-password, backup/editor/API artikel lama, uploader TinyMCE yatim, dan logger duplikat telah dihapus setelah audit referensi.
8. ✅ **Lockout login `msusers` diterapkan.** Lima kegagalan berturut-turut mengunci akun selama 15 menit dan hitungan di-reset setelah masa kunci atau login berhasil.
9. **Tiga lapis "approval" dengan penamaan mirip tapi tabel/kolom beda**: admin (`ispublished`/`is_paid` global di `advertisers_ads`, `admin/update_publish_status.php`), publisher per-mapping (`is_approved_by_publisher`, lewat file bernama `update_ad.php` — namanya menyesatkan), advertiser per-mapping (`is_approved_by_advertiser`, lewat `update_approval_advertiser.php`/`_partner.php`). Ketiganya independen, tidak tumpang tindih secara fungsi, tapi penamaan berpotensi membingungkan pengembang baru.
10. **Bug navigasi copy-paste berulang** di halaman "_partner"/versi lama: `view_ads_publishers_partner_mapping.php`, `view_rate_publisher_partner.php`, `view_advertiser_list_partner.php` — form pencarian/sort/link pagination-nya mengarah ke file saudara non-partner, bukan ke dirinya sendiri.
11. **Duplikasi logika rekalkulasi revenue**: `mypayment.php` (`function.php::updateRevenueForUser`) dan `myrevenue.php` (`admin/function_admin.php::updateLocalRevenue`/`updateGlobalRevenue`, di-include lintas-folder dari halaman publik ke `admin/`) sama-sama menyegarkan kolom revenue `msusers` dari sumber data yang sama, dengan implementasi terpisah — potensi drift kalau salah satu diubah tanpa yang lain. Ditambah tiga implementasi paralel independen untuk `get_providers_domain_url_json()`/`getProvidersNameById_JSON()` di `config.php`/`function.php`/`function_provider.php` (beda penanganan error: kembalikan string kosong vs `die()`).
12. **Tidak ada payment gateway nyata** — baik `confirm_payment.php` (pembelian influencer media) maupun `update_paid_desc.php` (budget iklan) hanya menyimpan teks notifikasi manual "sudah transfer"; status paid sesungguhnya diubah admin secara terpisah setelah verifikasi manual di luar sistem.
13. ✅ **Alur lupa/reset password memakai prepared statements.** Lookup email/token dan UPDATE password/token seluruhnya memakai parameter binding; token reset dibuat dengan `random_bytes()`.
14. ✅ **Penghapusan media dipindahkan ke POST+CSRF.** `mymedia.php` mengirim form bertoken dan `delete_media.php` tetap membatasi DELETE berdasarkan `owner_id` sesi.
15. **`generate_audio_summary.php`/`generate_quiz.php` sengaja hardcode `gpt-4.1-mini`** alih-alih memakai model dari `llm_settings` — bukan bug, tapi keputusan desain yang perlu diingat: kalau admin mengganti `llm_settings.llm_model` ke model reasoning lain lewat `admin/llm_settings.php`, dua fitur ini **tidak ikut berubah** karena model-nya di-hardcode terpisah.

## 6. Di Luar Cakupan Dokumen Ini

28 file PHP lain di `public_html/` root **bukan** bagian dashboard login (tidak memakai guard `$_SESSION['user_id']`, dan secara fungsi adalah endpoint publik/landing/tooling, bukan halaman yang dinavigasi user dari menu dashboard):

- **Landing & dokumen publik**: `index.php`, `index2.php` (halaman depan marketing), `doc.php` (halaman dokumen teknis terpisah), `test.php` (skrip uji coba mysqlnd, sisa development).
- **Ad-serving & tracking publik** (dipanggil dari situs milik publisher via `<script>` embed yang dihasilkan `mysite.php`/`add_site.php`, bukan dibuka user lewat dashboard): `sample.js.php`, `sample_landscape.js.php`, `show_ads_native.js.php`, `show_ads_native.js2.php`, `show_ads_native.js3.php`, `show_ads_native.js4.php`, `show_ads_native_landscape.js.php`, `show_ads_native_portrait.js.php`, `preview.php`, `preview.js.php`, `preview_vertical.js.php`, `videojs.php`, `track_click.php`, `verify_captcha.php` (captcha anti-bot sebelum `track_click.php`, sesi `captcha_result`-nya independen dari sesi dashboard), `dam.php`, `dambs.php`, `flbanner.php`, `sca.php`, `scahor.php` (lima file terakhir ini semuanya berisi skrip placeholder identik "This script has expired").
- **Analitik & feed publik**: `gtag.js.php` (snippet Google Analytics statis), `tiktok.php` (tracking endpoint TikTok pixel), `sitemap.php` (XML sitemap publik), `get_ads.php` (AJAX daftar iklan published untuk widget publik, tanpa guard sesi).
- **Helper murni**: `saatini.php` (potongan kode set timezone + format tanggal, di-include beberapa file di atas).

File-file ini bisa didokumentasikan terpisah (mis. sebagai "AD_SERVING.md") kalau dibutuhkan — di luar ruang lingkup dokumen dashboard user ini.
