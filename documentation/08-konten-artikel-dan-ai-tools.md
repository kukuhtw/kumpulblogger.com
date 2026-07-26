# Konten Artikel dan AI Tools

## Latar belakang bisnis

Fitur ini melayani publisher yang **tidak punya website sendiri**: mereka bisa membuat "situs" berbentuk blog terhosting di platform (`add_site_internal.php`, lihat `03-alur-publisher.md` §2b) lalu mengisi kontennya dengan bantuan AI, supaya tetap bisa menjadi penayang iklan tanpa keahlian teknis atau menulis manual. Ini disebut "AI Blog Engine" dalam materi pemasaran (`public_html/white_label/index.php:103,171-173`). Konten artikel bukan sekadar SEO — setiap halaman artikel adalah inventori tambahan tempat ad-tag bisa dipasang, sehingga fitur ini secara langsung memperbesar jumlah slot iklan yang tersedia di jaringan.

## Kuota penulisan — `publisher_quota`

Setiap publisher (internal blog) punya jatah artikel yang bisa dibuat, dikontrol lewat tabel `publisher_quota` (`sql/...:754-765`): `daily_free_quota`, `free_quota_articles` (default 5), `paid_quota` (bisa ditambah admin/pembelian — mekanisme pembelian kuota berbayar tidak ditemukan eksplisit di eksplorasi ini, **perlu konfirmasi**), `quota_valid_until`.

Pengecekan kuota dilakukan di dua tempat dengan logika identik:
- `article_api.php` action `check_quota` (baris 104-153) dan saat generate (`handleArticleGeneration()`, baris 169-260 dst).
- `total_quota = free_quota_articles + paid_quota`; `used_quota = COUNT(articles WHERE publishers_local_id = user_id)` (dihitung **sepanjang waktu**, bukan per hari, meski nama kolom `daily_free_quota` mengisyaratkan reset harian — **perlu konfirmasi** apakah reset harian benar-benar diimplementasikan).
- Jika `used_quota >= total_quota` → generate ditolak.
- Ada guard **anti double-submit**: generate artikel ditolak (`HTTP 429`) jika artikel terakhir publisher tsb dibuat kurang dari 60 detik lalu (`article_api.php:172-192`).

Admin mengelola kuota lewat `admin/manage_writer_quotas.php`.

## Alur pembuatan artikel dengan AI

1. Publisher membuka `add_article.php` — form berbasis CKEditor, memuat status kuota secara async (`article_api.php` action `check_quota`).
2. Publisher bisa meminta **ide topik** dari `get_ideas.php:31-46` — mengambil 500 baris acak dari tabel referensi `idea_article` (`topik`, `deskripsi`) sebagai starter/inspirasi, bukan hasil AI real-time.
3. Publisher submit topik/fokus/nada/bahasa → `article_api.php` action `generate_article` (`handleArticleGeneration()`) memanggil LLM (konfigurasi model & API key diambil dari tabel `llm_settings` — lihat di bawah) untuk menghasilkan `html_content`, disimpan ke tabel `articles` beserta metadata (`tag`, `language`, `tone`, `topic`, `input_token`, `output_token`, `json_response`).
4. Artikel yang sudah dibuat bisa dikelola di `view_edit_articles.php`, diedit lewat `edit_article.php`/`edit_article2.php` (ada juga versi `_backup`/`_tm`, kemungkinan file eksperimen/lama, **perlu konfirmasi**), dan diterbitkan agar tampil publik di `blog/{username}` (path yang sama yang di-generate saat `add_site_internal.php` membuat situs, lihat `03-alur-publisher.md`).
5. Gambar artikel diunggah lewat `upload_image_article.php` (dan varian `_tm`).

## Konfigurasi LLM — tabel `llm_settings`

Baris tunggal (atau diambil `ORDER BY id DESC LIMIT 1`, lihat `generate_quiz.php:75-80`) menyimpan `llm_model`, `openai_key`, `replicate_key`, `max_tokens` (default 2048), `temperature` (default 0.70). Dikelola lewat panel admin (nama file admin spesifiknya tidak teridentifikasi dalam eksplorasi; **perlu konfirmasi** — kemungkinan bagian dari `admin/manage_writer_quotas.php` atau halaman pengaturan terpisah).

Komentar di kode (`article_api.php:4`, `generate_ai_images.php:82`) menyebut penggunaan **OpenRouter/OpenAI** untuk teks dan **Replicate** (dengan catatan migrasi ke "GPT Image 2") untuk gambar — ini mengindikasikan platform sempat berganti provider gambar AI.

## Tools AI turunan (per artikel)

Semua endpoint berikut memakai pola yang sama: request JSON via `fetch`, autentikasi session, ambil konfigurasi dari `llm_settings`, panggil API eksternal, simpan hasil ke kolom terkait di tabel `articles`:

| Tool | Endpoint | Kolom target di `articles` | Halaman tampil |
|---|---|---|---|
| Generate gambar AI | `generate_ai_images.php` | (disimpan terpisah, direferensikan lewat `prediction_id`/folder `ai_images/`) | `view_ai_images_articles.php` |
| Ringkasan audio (text-to-speech) | `generate_audio_summary.php` | `wav` | `view_summary_audio_articles.php` |
| Kuis otomatis | `generate_quiz.php` | `json_quiz` | `view_quiz_articles.php` |
| Ide topik | `get_ideas.php` | — (sumber `idea_article`, bukan tulis ke `articles`) | dipakai di form `add_article.php` |

`generate_ai_images.php` juga menyimpan kompatibilitas untuk membaca ulang hasil prediksi Replicate lama (`replicateGetResult()`, baris 83-99) — mengindikasikan ada artikel lama yang gambarnya masih diproses lewat provider sebelumnya sebelum migrasi.

## Video & engagement tambahan

Tabel `video_watch_logs` (`sql/...:892-904`) mencatat aktivitas menonton video per publisher (`pubid`, `videoId`, `duration`, IP, user agent, referrer) — kemungkinan terkait fitur video ads/konten video yang direferensikan oleh `public_html/videojs.php` dan `voice/` folder. **Perlu konfirmasi**: tidak ada file yang jelas menulis ke tabel ini dalam eksplorasi; kemungkinan endpoint tracking video terpisah yang belum tercakup di daftar file yang diminta.

## Tabel database yang terlibat

`articles`, `idea_article`, `publisher_quota`, `llm_settings`, `media` (tidak — itu untuk influencer), `video_watch_logs`.
