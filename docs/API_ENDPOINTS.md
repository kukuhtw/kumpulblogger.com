# Dokumentasi API — `public_html/API/`

> Terkait: [DATABASE_ERD.md](./DATABASE_ERD.md) — terutama bagian 4.1 "Federasi Provider". API di folder ini **adalah** implementasi jaringan federasi itu.

## 1. Ringkasan

Folder `public_html/API/` bukan API publik untuk browser pengguna akhir — ini adalah API **machine-to-machine antar-provider ad-network** (federasi). Provider lain yang sudah/ingin jadi partner memanggil endpoint-endpoint ini untuk gabung jaringan, sinkronisasi katalog iklan/publisher, dan melaporkan klik & pembayaran.

Karakteristik yang sama di semua 14 endpoint:

- **1 folder = 1 endpoint = 1 file `index.php`.** Tidak ada router pusat; tiap file berdiri sendiri dan meng-`include("../../db.php")` + `include("../../function.php")` masing-masing.
- **Request**: selalu raw JSON di body (`file_get_contents('php://input')` → `json_decode`), bukan form-urlencoded/query string.
- **Response**: selalu JSON `{"status": "success"|"error"|"info", "message": "...", ...}`.
- **Koneksi DB ganda**: hampir semua file membuka koneksi PDO *dan* MySQLi sekaligus di awal file — PDO dipakai untuk query prepared-statement, MySQLi seringkali dibuka tapi tidak dipakai (sisa refactor).
- **Baris komentar pertama** tiap file mencantumkan path lengkapnya sendiri, contoh `// {BASE_END_POINT}API/insert_ads/index.php` — ini konvensi placeholder untuk base URL yang dipakai peserta federasi lain saat menyusun endpoint tujuan.

## 2. Tabel Ringkas 14 Endpoint

| Endpoint | Dipanggil oleh | Cara autentikasi | Tabel utama yang disentuh |
|---|---|---|---|
| `request_join` | Provider **lain** (pemohon) | `providers_code` tujuan cocok | INSERT `providers_request`, `providers_partners` (pending) |
| `approve_request_partnership` | Sistem provider **ini** (setelah admin approve) | `signature` tersimpan + `providers_code` sendiri | UPDATE `providers_partners` (approve + terbitkan key) |
| `update_key` | Partner (rotasi kredensial) | ⚠️ dihitung tapi **tidak divalidasi** | UPDATE `providers_partners` |
| `insert_advertiser` | Front-end provider sendiri | `secret_key_provider` = secret_key sendiri | ⚠️ INSERT `advertisers` (tabel tidak ada di skema saat ini) |
| `insert_ads` | Front-end provider sendiri | `secret_key` = secret_key sendiri | INSERT/UPDATE `advertisers_ads` |
| `insert_pubs` | Front-end provider sendiri | `secret_key_provider` = secret_key sendiri | ⚠️ INSERT `publishers` (tabel tidak ada di skema saat ini) |
| `getOwnerPublisher` | Partner | header `public_key`+`secret_key` | SELECT `publishers_site` → `msusers` |
| `approval_advertiser_partner` | Partner | header `public_key`+`secret_key` | UPDATE `mapping_advertisers_ads_publishers_site` |
| `pushInfoAccountBankProvider` | Partner | header `public_key`+`secret_key` | UPSERT `providers_contact_person_sync` |
| `getinfoPaymentProviderPartner` | Partner | header `public_key`+`secret_key` | UPSERT (dedup) `payment_partner_providers_sync` |
| `getinfoPaymentPubsPartner` | Partner | ⚠️ **tidak ada validasi key** | UPSERT (dedup) `payment_partner_pubs_sync` |
| `sync_ads` | Partner | header `public_key`+`secret_key` | UPSERT `advertisers_ads_partners` |
| `sync_publisher` | Partner | header `public_key`+`secret_key` | UPSERT `publishers_site_partners` |
| `sync_clicks` | Partner | header `public_key`+`secret_key` | UPSERT massal `ad_clicks_partner` |
| `sync_mapping_advertisers_ads_publishers_site_from_partners` | Partner | header `public_key`+`secret_key` | UPSERT massal `mapping_advertisers_ads_publishers_site_from_partners` |

## 3. Pola Autentikasi

Ada tiga pola berbeda yang dipakai bergantian di 14 endpoint ini — penting untuk dipahami sebelum mengintegrasikan partner baru.

### 3.1 Header `public_key` + `secret_key` (pola paling umum — 8 endpoint)

Dipakai oleh semua endpoint **sinkronisasi rutin** (`sync_*`, `getinfo*`, `getOwnerPublisher`, `approval_advertiser_partner`, `pushInfoAccountBankProvider`). Kredensial dikirim lewat **HTTP header**, bukan body, lalu divalidasi via `checkProviderCredentials()` (`function_provider.php:228`):

```php
$headers = getallheaders();
$Header_public_key = $headers['public_key'] ?? null;
$Header_secret_key = $headers['secret_key'] ?? null;
checkProviderCredentials($providers_domain_url, $Header_public_key, $Header_secret_key, $pdo);
```

Fungsi ini cuma mengecek apakah ada baris di `providers_partners` dengan kombinasi `providers_domain_url` + `public_key` + `secret_key` yang persis sama — key ini didapat partner dari hasil `approve_request_partnership` (lihat §5).

### 3.2 Body `secret_key`/`secret_key_provider` dibandingkan ke kunci provider sendiri (3 endpoint)

Dipakai oleh `insert_advertiser`, `insert_ads`, `insert_pubs` — mengecek `secret_key` yang dikirim di body sama dengan `providers.secret_key` milik **provider ini sendiri** (id=1, via `getSecretKeyById()`). Ini bukan kredensial partner, jadi endpoint-endpoint ini kemungkinan dimaksudkan untuk dipanggil oleh sistem/form internal sendiri (yang sudah tahu secret key-nya sendiri), bukan oleh provider federasi lain.

### 3.3 Signature + kode provider (2 endpoint, khusus alur "join")

Dipakai hanya oleh `request_join` dan `approve_request_partnership` — lihat detail alurnya di §5. Menggunakan `providers_code` (kode publik yang dibagikan manual ke calon partner) dan `signature` yang disimpan-balik dari permintaan sebelumnya, bukan `public_key`/`secret_key`.

## 4. Detail per Endpoint

### `request_join/index.php`
**Fungsi**: Titik masuk permintaan bergabung ke jaringan federasi. Dipanggil oleh sistem provider **pemohon**, ke server provider **tujuan**.
**Body**: `request_from`, `signature`, `providers_domain_url` (domain pemohon), `target_providers_domain_url`, `providers_code` (kode milik provider tujuan — dibagikan out-of-band), `providers_api_url`, `ipaddress`, `source_url`, `browser_agent`.
**Auth**: `providers_code` di body harus sama dengan `providers.providers_code` milik provider tujuan (id=1).
**Efek**: `insertProvidersRequest()` → log ke `providers_request`; `insertProviderPartner_preApproval()` → buat baris baru di `providers_partners` dengan `isapproved=0`, `public_key`/`secret_key` kosong (status "menunggu approval").
**Catatan**: `$expected_secret_key` dihitung (`sha1($providers_code)`) tapi tidak pernah dipakai untuk pengecekan apa pun — dead code.

### `approve_request_partnership/index.php`
**Fungsi**: Menyetujui permintaan yang sudah tercatat lewat `request_join`, lalu menerbitkan `public_key` + `secret_key` baru untuk partner tersebut.
**Body**: `providers_domain_url`, `providers_code`, `signature`.
**Auth**: (a) `signature` harus sama dengan yang tersimpan di `providers_partners` untuk `providers_domain_url` itu (`getSignatureByDomainUrl()`), DAN (b) `providers_code` harus sama dengan kode provider **ini sendiri** (id=1). Karena syarat (b) mengharuskan pemanggil tahu kode provider ini sendiri, endpoint ini realistisnya dipicu oleh sistem/admin provider ini sendiri, bukan langsung oleh pemohon.
**Efek**: generate `public_key`/`secret_key` acak (`sha1(rand() . kode . domain)`), lalu `UpdateProviderPartner()` → UPDATE `providers_partners` (`isapproved=1`, `is_followup=1`, key baru tersimpan).
**Response sukses**: `{status:"success", public_key, secret_key, message}` — kunci baru dikembalikan langsung di body respons, harus diteruskan ke partner lewat jalur lain.

### `update_key/index.php`
**Fungsi**: Rotasi `public_key`/`secret_key` sebuah partner yang sudah disetujui.
**Body**: `providers_domain_url`, `signature`, `newPublicKey`, `newSecretKey`.
**Efek**: `updateKeysByDomainAndSignature()` → UPDATE `providers_partners` berdasarkan kecocokan `providers_domain_url` + `signature`.
**⚠️ Catatan penting**: kode menghitung `$expected_secret_key = sha1($signature.$providers_domain_url.$newPublicKey.$newSecretKey)` tapi percabangannya `if (1==1) { ...proses... }` — kondisi selalu benar, sehingga **validasi signature saat ini tidak aktif**. Endpoint ini efektif hanya mensyaratkan `providers_domain_url` dan `signature` yang cocok di `WHERE` UPDATE (kalau tidak cocok baris manapun, `rowCount()==0` dan pesannya "No matching provider found").

### `insert_advertiser/index.php`
**Fungsi**: Mendaftarkan advertiser baru.
**Body**: `providers_name`, `providers_domain_url`, `advertisers_name`, `advertisers_email`, `advertisers_whatsapp`, `secret_key_provider`.
**Auth**: `secret_key_provider` == `providers.secret_key` milik provider ini (id=1).
**Efek**: generate password acak (`sha1(rand+nama+email)` dipotong 8 karakter, di-hash lagi `sha1()` sebelum dikirim ke fungsi — lalu `insertAdvertiser()` mem-`password_hash()`-kannya sekali lagi dengan BCRYPT sebelum INSERT), cek duplikasi by `advertisers_email`, lalu **INSERT ke tabel `advertisers`**.
**⚠️ Catatan**: tabel `advertisers` **tidak ada** di `sql/kumpulbl_kbc_hanya_structure.sql` — hanya ada `advertisers_ads`/`advertisers_ads_partners`. Endpoint ini kemungkinan sisa kode lama dari sebelum skema advertiser disederhanakan, dan akan gagal (`Undefined table`) kalau dipanggil terhadap skema saat ini.

### `insert_ads/index.php`
**Fungsi**: Membuat iklan baru untuk seorang advertiser.
**Body**: `providers_name`, `providers_domain_url`, `advertisers_id`, `title_ads`, `description_ads`, `landingpage_ads`, `total_click`, `secret_key`.
**Auth**: `secret_key` == `providers.secret_key` milik provider ini (id=1).
**Efek**: `insertAdvertisersAds()` → INSERT ke `advertisers_ads`, lalu langsung `updateAdvertisersAds($lastInsertId, 0)` → UPDATE baris yang sama untuk set `current_click=0` **dan** `local_ads_id = id` (menyalin id auto-increment ke kolom `local_ads_id` miliknya sendiri — pola "local_ads_id == id sendiri" untuk baris lokal, berbeda dari baris `_partners` yang `local_ads_id`-nya menunjuk ke id di server asal).
**Catatan**: menulis file debug `tra46.txt`, `tra47.txt`, `tra52.txt` di direktori kerja lewat `debug_text()` (lihat §6).

### `insert_pubs/index.php`
**Fungsi**: Mendaftarkan publisher baru.
**Body**: `publishers_name`, `publishers_email`, `publishers_whatsapp`, `publishers_bank`, `publishers_account_name`, `publishers_account_number`, `secret_key_provider`.
**Auth**: `secret_key_provider` == `providers.secret_key` milik provider ini (id=1).
**Efek**: INSERT langsung (SQL ditulis inline di file ini, bukan lewat fungsi di `function_*.php`) ke **tabel `publishers`**, lalu UPDATE baris itu supaya `publishers_local_id = id` (pola sama seperti `insert_ads`).
**⚠️ Catatan**: sama seperti `insert_advertiser`, tabel `publishers` **tidak ada** di skema saat ini (yang ada `publishers_site`/`publishers_site_partners`/`publisher_partner`) — endpoint ini kemungkinan besar tidak berfungsi terhadap database sekarang.

### `getOwnerPublisher/index.php`
**Fungsi**: Partner meminta info kontak pemilik sebuah situs publisher lokal (untuk keperluan pembayaran lintas-jaringan).
**Body**: `providers_domain_url`, `pub_id` (id situs di `publishers_site`), `pubs_providers_domain_url`.
**Auth**: header `public_key`+`secret_key`.
**Alur query**: `pub_id` → `publishers_site.publishers_local_id` → `msusers.id` → ambil `loginemail`, `whatsapp`, `bank`, `account_name`, `account_number`.
**Response sukses**: `{status:"success", data:{loginemail, whatsapp, bank, account_name, account_number, pubs_providers_domain_url, publishers_local_id, pub_id}}`.
**Catatan**: query kedua menyisipkan `$pubs_providers_domain_url`, `$publishers_local_id`, `$pub_id` langsung sebagai literal SQL (bukan parameter terikat) — nilai-nilai ini berasal dari body request/hasil query pertama, bukan input bebas pengguna, tapi tetap pola yang lebih rapuh dibanding query pertama yang sudah pakai `:pub_id` terikat.

### `approval_advertiser_partner/index.php`
**Fungsi**: Partner memberi tahu bahwa seorang advertiser di sisi mereka sudah menyetujui/menolak sebuah mapping iklan↔situs (federasi ke arah sebaliknya dari `sync_mapping_*`).
**Body**: `id`, `local_ads_id`, `pubs_providers_domain_url`, `providers_domain_url`, `ads_providers_domain_url`, `is_approved_by_advertiser`.
**Auth**: header `public_key`+`secret_key`.
**Efek**: UPDATE `mapping_advertisers_ads_publishers_site` (set `is_approved_by_advertiser`, dan `approval_date_advertiser` jika nilainya bukan 0), dicocokkan lewat `id` + `local_ads_id` + `pubs_providers_domain_url` + `ads_providers_domain_url`.

### `pushInfoAccountBankProvider/index.php`
**Fungsi**: Partner mendorong (push) info kontak & rekening bank mereka ke provider ini.
**Body**: `providers_domain_url`, `email`, `whatsapp`, `account_name`, `account_bank`, `account_number`, `last_update`.
**Auth**: header `public_key`+`secret_key`.
**Efek**: upsert manual (SELECT COUNT dulu, lalu UPDATE atau INSERT) ke `providers_contact_person_sync`, dikunci oleh `providers_domain_url`.

### `getinfoPaymentProviderPartner/index.php`
**Fungsi**: Sinkron catatan pembayaran **ke provider partner** (level jaringan, bukan publisher perorangan).
**Body**: `id` (local_id di sisi pengirim), `partner_providers_domain_url`, `email_provider`, `nominal`, `payment_description`, `payment_date`, `payment_by`.
**Auth**: header `public_key`+`secret_key`.
**Efek**: cek duplikasi via kombinasi `local_id` + `partner_providers_domain_url`; kalau belum ada, INSERT ke `payment_partner_providers_sync`. Kalau sudah ada, balas `status:"info"` tanpa perubahan (idempotent).

### `getinfoPaymentPubsPartner/index.php`
**Fungsi**: Sinkron catatan pembayaran **ke publisher milik partner** (analog dengan endpoint di atas, tapi level publisher perorangan).
**Body**: `id`, `publisher_local_id`, `providers_domain_url`, `email_pubs`, `nominal`, `payment_description`, `payment_date`, `payment_by`.
**Efek**: cek duplikasi via `local_id` + `publisher_local_id` + `pubs_providers_domain_url`; kalau belum ada, INSERT ke `payment_partner_pubs_sync`.
**⚠️ Catatan**: endpoint ini **tidak memanggil `checkProviderCredentials()` sama sekali** — tidak ada pengecekan header `public_key`/`secret_key`, berbeda dari endpoint kembarannya (`getinfoPaymentProviderPartner`) yang strukturnya nyaris identik tapi memvalidasi kredensial. Ini kemungkinan celah otorisasi yang tidak disengaja, bukan desain yang disengaja.

### `sync_ads/index.php`
**Fungsi**: Partner mendorong (push) katalog iklan mereka supaya di-cache lokal.
**Body**: `providers_name`, `providers_domain_url`, `advertisers_id`, `local_ads_id`, `ispublished`, `title_ads`, `description_ads`, `landingpage_ads`, `image_url`, `total_click`, `current_click`, `budget_per_click_textads`, `is_expired`, `expired_date`, `is_paused`, `paused_date`, `budget_allocation`, `current_spending`.
**Auth**: header `public_key`+`secret_key`.
**Efek**: `insertOrUpdateAdvertisersAdsPartner()` (didefinisikan di file ini sendiri, bukan di `function_*.php`) — cek existing by `local_ads_id` + `providers_domain_url`, lalu UPDATE atau INSERT ke `advertisers_ads_partners`.
**Catatan**: `$expected_secret_key` dihitung tapi kondisi eksekusinya `if (true)` — dead code yang sama seperti di `request_join`.

### `sync_publisher/index.php`
**Fungsi**: Partner mendorong katalog situs publisher mereka.
**Body**: `id`, `providers_name`, `providers_domain_url`, `publishers_local_id`, `site_name`, `site_domain`, `site_desc`, `rate_text_ads`, `advertiser_allowed`, `advertiser_rejected`, `regdate`, `isbanned`, `banned_date`, `banned_reason`.
**Auth**: header `public_key`+`secret_key`.
**Efek**: `insertOrUpdatePublisherPartner()` (didefinisikan di file ini sendiri) — cek existing by `local_id` + `publishers_local_id` + `providers_domain_url`, lalu UPDATE atau INSERT ke `publishers_site_partners`.
**Catatan**: nama file di komentar baris pertama (`sync_publishers/index.php`, jamak) tidak sama dengan nama folder sebenarnya (`sync_publisher`, tunggal) — komentar sudah usang.

### `sync_clicks/index.php`
**Fungsi**: Partner mengirim **batch** data klik iklan federasi (array `ad_clicks`) untuk disalin ke sisi lokal.
**Body**: `providers_domain_url`, `ad_clicks: [...]` (array objek klik dengan struktur mengikuti kolom `ad_clicks_partner`).
**Auth**: header `public_key`+`secret_key`.
**Efek**: untuk tiap item di `ad_clicks` yang `pubs_providers_domain_url`-nya **bukan** domain provider ini sendiri, cek `hash_audit` sudah ada atau belum di `ad_clicks_partner`, lalu INSERT (dengan `ON DUPLICATE KEY UPDATE` sebagai jaring pengaman kedua) ke `ad_clicks_partner`.
**Catatan**: filter `pubs_providers_domain_url !== $this_providers_domain_url` artinya endpoint ini secara desain menolak menyimpan klik yang situsnya justru milik provider ini sendiri (klik semacam itu seharusnya masuk `ad_clicks`, bukan `ad_clicks_partner`).

### `sync_mapping_advertisers_ads_publishers_site_from_partners/index.php`
**Fungsi**: Partner mengirim **batch** data mapping approval iklan↔situs (array `ad_data`) versi mereka.
**Body**: `providers_domain_url`, `ad_data: [...]` (array objek mengikuti kolom `mapping_advertisers_ads_publishers_site_from_partners`).
**Auth**: header `public_key`+`secret_key`.
**Efek**: untuk tiap item, INSERT ... `ON DUPLICATE KEY UPDATE` ke `mapping_advertisers_ads_publishers_site_from_partners` (id disamakan dengan `local_mapping_id` dari pengirim).
**Catatan**: variabel `$exists` dihitung (SELECT COUNT by id) tapi hasilnya tidak pernah dipakai untuk bercabang — kondisi berikutnya `if (true)` selalu jalan ke INSERT/UPDATE; logikanya sebenarnya sudah cukup lewat klausa `ON DUPLICATE KEY UPDATE` di SQL-nya sendiri, jadi pengecekan manual ini redundan (bukan bug, tapi kode mati).

## 5. Alur Federasi End-to-End

```mermaid
sequenceDiagram
    participant A as Provider Pemohon
    participant B as Provider Ini (id=1)
    participant AdminB as Admin Provider Ini

    A->>B: POST /API/request_join<br/>{providers_code B, domain A, signature, ...}
    B->>B: cocokkan providers_code milik sendiri
    B-->>B: INSERT providers_request (log)
    B-->>B: INSERT providers_partners<br/>(isapproved=0, key kosong)
    B-->>A: {status: success}

    Note over AdminB: Review manual permintaan federasi

    AdminB->>B: POST /API/approve_request_partnership<br/>{domain A, signature, providers_code B}
    B->>B: cocokkan signature tersimpan + providers_code sendiri
    B-->>B: UPDATE providers_partners<br/>(isapproved=1, generate public_key+secret_key)
    B-->>AdminB: {public_key, secret_key}

    Note over AdminB,A: public_key/secret_key diteruskan ke A di luar sistem ini

    loop Sinkronisasi rutin (partner sudah approved)
        A->>B: POST /API/sync_ads, sync_publisher,<br/>sync_clicks, sync_mapping_...<br/>header: public_key + secret_key
        B->>B: checkProviderCredentials()
        B-->>B: upsert ke tabel *_partners / *_sync
        B-->>A: {status: success}
    end

    opt Rotasi kredensial
        A->>B: POST /API/update_key {domain, signature, newKeys}
        B-->>B: UPDATE providers_partners
    end
```

## 6. Temuan & Catatan Kualitas Kode

Ringkasan hal-hal yang ditemukan saat menyusun dokumentasi ini — bukan perbaikan, murni observasi supaya diketahui:

1. **Dua endpoint menunjuk tabel yang tidak ada di skema saat ini**: `insert_advertiser` → tabel `advertisers`, `insert_pubs` → tabel `publishers`. Keduanya tidak ada di `sql/kumpulbl_kbc_hanya_structure.sql`.
2. **`getinfoPaymentPubsPartner` tidak memvalidasi `public_key`/`secret_key`** sama sekali, berbeda dari endpoint sejenis lainnya.
3. **Tiga tempat validasi signature/secret-key sudah dihitung tapi dilewati** oleh kondisi yang selalu benar: `update_key` (`if (1==1)`), `sync_ads` (`if (true)`), dan pengecekan `$exists` yang tidak dipakai di `sync_mapping_advertisers_ads_publishers_site_from_partners`.
4. **`debug_text()`** (`function.php:367`) menulis isi mentah request (kadang termasuk seluruh JSON payload) ke file `.txt` di direktori kerja endpoint yang memanggilnya (mis. `API/insert_ads/tra46.txt`) — dipakai secara tidak konsisten (aktif di beberapa endpoint, dikomentari di endpoint lain). Karena file ini ditulis langsung di dalam folder `public_html/API/<endpoint>/`, filenya berpotensi bisa diakses langsung lewat URL kalau nama filenya diketahui.
5. Query kedua di `getOwnerPublisher` menyisipkan variabel langsung ke string SQL alih-alih parameter terikat (lihat catatan di bagian endpoint tsb).

Semua koneksi PDO/MySQLi dibuka ulang di tiap file tanpa connection pooling — wajar untuk API request-per-invocation seperti ini, tapi berarti tiap panggilan endpoint = minimal 1–2 koneksi DB baru.
