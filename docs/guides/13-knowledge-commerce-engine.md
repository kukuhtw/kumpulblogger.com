# Knowledge Commerce Engine (KCE)

> Navigasi: [Runbook](../OPERATIONS_RUNBOOK.md) · [Konten dan AI](./08-konten-artikel-dan-ai-tools.md) · [Admin](../reference/ADMIN_PANEL.md) · [Database](../reference/DATABASE_ERD.md)

## Apa itu KCE?

**KCE** adalah singkatan dari **Knowledge Commerce Engine**. Ini adalah produk
chat AI di dalam MyAdNetwork yang menyatukan tiga pengalaman:

1. pengguna bertanya dan mendapat jawaban AI;
2. sistem menemukan artikel editorial yang relevan secara semantik;
3. sistem menampilkan sponsored content yang relevan dan berlabel jelas.

KCE tersedia di `/kce/`. KCE bukan pengganti native ads berbasis ad-tag. Ia
merupakan kanal tambahan berbasis intent: sponsor dipilih dari makna pertanyaan
atau artikel, bukan sekadar dari situs tempat widget dipasang.

## Nilai bisnis

- **Pengguna:** jawaban kontekstual, artikel terkait, dan pemisahan iklan jelas.
- **Advertiser:** menjangkau intent relevan serta mengukur impression, click,
  CTR, biaya, saldo, dan histori transaksi.
- **Publisher/operator:** artikel mendapat discovery tambahan dan platform
  memperoleh monetisasi impression/click di luar native ads.

## Perbedaan dari native ads

| Aspek | Native ads utama | KCE |
|---|---|---|
| Penempatan | Widget JavaScript publisher | Chat KCE dan halaman artikel |
| Pemilihan | Mapping situs, rate, approval | Kemiripan embedding pertanyaan/artikel |
| Campaign | `advertisers_ads` | `kce_sponsored_content` |
| Biaya | Spending/revenue klik | Impression dan/atau click |
| Dana | Budget iklan utama | Wallet prabayar dan ledger |
| Event | Tabel klik/rekap utama | `kce_ad_events` |

Akun advertiser dapat sama, tetapi campaign, saldo, event, dan charging terpisah.

## Cara kerja

```text
Pertanyaan
   +--> OpenRouter --> jawaban AI streaming
   +--> NVIDIA query embedding (2048 dimensi)
             +--> artikel relevan
             +--> sponsored content relevan
                         +--> impression/click --> debit wallet
```

1. Browser mengirim pertanyaan maksimum 4.000 karakter dan token reCAPTCHA.
2. Server memverifikasi manusia dan rate limit berdasarkan hash IP.
3. Percakapan dibuat/dipulihkan dari cookie `kce_conversation`.
4. Maksimum 20 pesan terakhir menjadi konteks model chat.
5. Jawaban dikirim secara streaming.
6. Pertanyaan dibuatkan embedding melalui NVIDIA NIM.
7. Cosine similarity meranking artikel dan sponsor.
8. Hasil di atas threshold ditampilkan dan disimpan sebagai snapshot pesan.
9. Event sponsor valid dicatat dan mendebit wallet secara transaksional.

Jika embedding gagal, jawaban dapat tetap tampil tanpa artikel/sponsor. Jika
saldo tidak cukup untuk event, campaign dipause otomatis.

## Jawaban AI dan iklan dipisahkan

Sponsored content **tidak dimasukkan ke prompt model**. Prompt meminta model
tidak menyisipkan iklan. Sponsor baru dicocokkan setelah jawaban dibuat.

- Advertiser tidak membeli isi jawaban AI.
- Jawaban bukan endorsement sponsor.
- Sponsor harus berlabel **Sponsored Content · Iklan**.
- Relevance score memilih sponsor, bukan mengubah jawaban.

## Antarmuka utama

| URL/file | Fungsi |
|---|---|
| `/kce/` | Chat publik, jawaban streaming, artikel dan sponsor |
| `/kce/historychat.php` | Riwayat percakapan browser |
| `/kce_campaigns.php` | Advertiser mengelola campaign dan wallet |
| `/admin/kce.php` | Harga, deposit, approval, campaign, laporan |
| `/admin/kce_articles.php` | Indeks embedding artikel terbit |
| `/kce/api/stream.php` | Endpoint chat streaming |
| `/kce/api/event.php` | Tracking dan charging impression/click |

Campaign advertiser masuk sebagai `pending_review`. Admin memeriksa materi,
target URL, jadwal, cap, saldo, dan biaya sebelum aktivasi.

## Model dan konfigurasi

```dotenv
OPENROUTER_API_KEY=sk-or-v1-...
OPENROUTER_MODEL=nvidia/nemotron-nano-12b-v2-vl:free
OPENROUTER_SECONDARY_MODEL=openai/gpt-oss-20b:free
NVIDIA_API_KEY=nvapi-...
NVIDIA_EMBEDDING_MODEL=nvidia/nemotron-3-embed-1b
KCE_APP_URL=https://ads.example.com/kce
KCE_TRACKING_SECRET=SECRET_ACAK_PANJANG
RECAPTCHA_SITE_KEY=...
RECAPTCHA_SECRET=...
```

`KCE_TRACKING_SECRET` menandatangani event HMAC; jangan memakai `change-me`.
Setelah mengganti embedding model, indeks ulang artikel dan campaign agar semua
vector berasal dari model yang konsisten. Schema berada di `sql/kce_schema.sql`.

## Operasi artikel

Manual lengkap setiap kontrol, status, proses re-index, model migration, biaya,
dan troubleshooting tersedia di
[Admin KCE Article Index dan Vector Embedding](../reference/KCE_ARTICLE_INDEX.md).

Di `/admin/kce_articles.php`, admin memilih artikel terbit, membuat atau
memperbarui embedding, mengaktifkan indeks, serta mengatur threshold/jumlah
hasil. `source_hash` menandai embedding stale setelah isi artikel berubah;
artikel tersebut harus diindeks ulang.

## Operasi campaign dan wallet

1. Advertiser membuat campaign di `/kce_campaigns.php`.
2. Server membuat passage embedding dan status `pending_review`.
3. Operator memverifikasi pembayaran eksternal.
4. Admin mencatat deposit dengan referensi pembayaran unik.
5. Admin meninjau dan mengaktifkan campaign.
6. Event valid membuat debit ledger; saldo kurang mem-pause campaign.

Jangan mengubah `balance` langsung lewat SQL. Gunakan transaksi wallet agar
deposit, charge, refund, dan adjustment mempunyai jejak ledger.

Default schema: impression `50`, click `500`, threshold sponsor `0.35`, maksimal
dua sponsor, threshold artikel `0.30`, maksimal tiga artikel. Itu default teknis;
operator wajib menetapkan harga dan threshold bisnis dari dashboard admin.

## Tracking, privasi, dan proteksi

- Event hanya menerima `impression`/`click` dengan token HMAC valid.
- Event key unik mencegah charging berulang dalam bucket deduplikasi.
- Impression chat dideduplikasi per materi, percakapan, dan jam.
- IP disimpan sebagai hash; percakapan/pesan disimpan untuk history/konteks.
- Advertiser menerima performa agregat, bukan isi lengkap chat.
- Chat dibatasi 10 pertanyaan/menit per hash IP dan 4.000 karakter/pertanyaan.
- Hanya campaign aktif, dalam jadwal/cap, dan relevan yang dipilih.

Kebijakan privasi harus menjelaskan cookie, history chat, AI provider eksternal,
retensi, tracking sponsor, dan hak penghapusan data. Jawaban AI dapat keliru;
pengguna harus diminta memverifikasi informasi penting.

## Checklist aktivasi

- [ ] Schema KCE berhasil diterapkan.
- [ ] OpenRouter, NVIDIA, reCAPTCHA, URL dan tracking secret valid.
- [ ] Chat membuat history dan streaming jawaban.
- [ ] Artikel uji diindeks dan muncul secara relevan.
- [ ] Deposit uji tercatat dalam wallet dan ledger.
- [ ] Campaign melewati review dan aktif.
- [ ] Sponsor terpisah serta berlabel.
- [ ] Impression/click mendebit tepat sekali.
- [ ] Campaign pause ketika saldo kurang.
- [ ] Privacy disclosure dan monitoring biaya/error tersedia.

## Troubleshooting

| Gejala | Periksa |
|---|---|
| Chat gagal | OpenRouter key/model, network, log `KCE stream [request-id]` |
| Human verification gagal | reCAPTCHA key dan domain |
| Artikel/sponsor kosong | NVIDIA, embedding aktif, threshold, status/jadwal |
| Tidak bisa aktif | Wallet cukup untuk minimal satu impression |
| Campaign langsung pause | Saldo, biaya, cap, ledger terakhir |
| Event ditolak | Tracking secret, token, conversation/placement, status |
| Artikel stale | Isi berubah; buat ulang embedding |

## Tabel KCE

- `kce_conversations`, `kce_messages`: history chat.
- `kce_article_embeddings`, `kce_message_articles`: indeks/snapshot artikel.
- `kce_sponsored_content`, `kce_message_sponsors`: campaign/snapshot sponsor.
- `kce_ad_events`: event, biaya, dan deduplikasi.
- `kce_advertiser_wallets`, `kce_wallet_transactions`: saldo dan ledger.
- `kce_settings`: harga, threshold, dan batas hasil.
