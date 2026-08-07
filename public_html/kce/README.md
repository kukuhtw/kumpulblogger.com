# KumpulBlogger Knowledge Commerce Engine

## Instalasi

1. Jalankan `sql/kce_schema.sql` pada database KumpulBlogger.
2. Tambahkan nilai KCE dari `.env.example` ke `.env`. Gunakan API key OpenRouter untuk chat dan API key NVIDIA NIM untuk embedding.
3. Buka `/kce/` untuk chat dan `/admin/kce.php` untuk dashboard.

## Model

- Chat: `nvidia/nemotron-nano-12b-v2-vl:free` melalui OpenRouter.
- Retrieval: `nvidia/nemotron-3-embed-1b` melalui NVIDIA NIM, dengan embedding passage/query 2048 dimensi.

Sponsored content tidak pernah dimasukkan ke prompt model. Aplikasi melakukan semantic matching setelah jawaban dibuat dan merender hasilnya dalam komponen berlabel **Sponsored Content · Iklan**.

Impression dideduplikasi per materi, percakapan, dan jam. Click menggunakan event unik dan redirect ke URL yang tersimpan di database. Token HMAC mencegah client mengirim event untuk materi lain.
