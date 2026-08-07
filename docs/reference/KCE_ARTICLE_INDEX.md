# Manual Admin KCE Article Index dan Vector Embedding

> Navigasi: [KCE](../guides/13-knowledge-commerce-engine.md) · [Runbook](../OPERATIONS_RUNBOOK.md) · [Panel admin](./ADMIN_PANEL.md) · [Database](./DATABASE_ERD.md)

## Tujuan dashboard

Dashboard `/admin/kce_articles.php` menentukan artikel editorial terbit mana
yang dapat ditemukan oleh **semantic retrieval** KCE. Dashboard mengubah artikel
menjadi vector embedding 2048 dimensi, menyimpan vector tersebut, dan mengatur
apakah vector boleh dipakai dalam hasil pencarian KCE.

Embedding bukan ringkasan dan bukan kumpulan keyword. Ia adalah array angka yang
mewakili makna teks. Pertanyaan pengguna juga diubah menjadi vector. KCE lalu
menghitung **cosine similarity** antara vector pertanyaan dan setiap artikel;
semakin tinggi score, semakin dekat maknanya.

## Hak akses dan prasyarat

- Harus login sebagai admin (`$_SESSION['loggedin']`).
- `sql/kce_schema.sql` sudah diterapkan.
- `NVIDIA_API_KEY` dan `NVIDIA_EMBEDDING_MODEL` valid.
- Artikel harus `ispublished=1`; draft tidak muncul dan tidak dapat diindeks.
- Server dapat mengakses `https://integrate.api.nvidia.com/v1/embeddings`.

Semua aksi POST dilindungi CSRF admin.

## Bagian dashboard

### Pengaturan retrieval

| Kontrol | Rentang | Default | Dampak |
|---|---:|---:|---|
| Ambang relevansi artikel | 0–1 | 0.30 | Artikel dengan score di bawah nilai ini dibuang |
| Maksimum artikel per jawaban | 0–10 | 3 | Membatasi hasil setelah ranking dan threshold |

Nilai `0` pada maksimum hasil menonaktifkan tampilan artikel tanpa menghapus
embedding. Threshold lebih rendah meningkatkan recall tetapi berisiko memberi
hasil longgar; threshold lebih tinggi meningkatkan presisi tetapi dapat membuat
hasil kosong. Kalibrasikan dengan kumpulan pertanyaan nyata, bukan satu contoh.

### Pencarian dan pagination

- Pencarian memeriksa `title`, `tag`, dan `topic` dengan pencocokan SQL `LIKE`.
- Hanya artikel terbit yang dihitung.
- Urutan berdasarkan artikel terbaru diperbarui.
- Dashboard menampilkan 30 artikel per halaman.
- Checkbox header memilih seluruh artikel pada halaman saat ini, bukan semua halaman.
- Satu request indexing menerima maksimum 20 ID unik; pilihan selebihnya dipotong.

### Kolom tabel

| Kolom | Arti |
|---|---|
| Artikel | Judul, topik/tag, penulis, dan tautan melihat artikel |
| Indeks KCE | Kondisi embedding terhadap isi artikel saat ini |
| Model / waktu | Model embedding tersimpan dan waktu indexing terakhir |
| Aksi | Mengaktifkan atau menonaktifkan embedding yang sudah ada |

## Arti setiap status

### Belum diindeks

Belum ada `source_hash` pada `kce_article_embeddings`. Artikel tidak ikut
semantic retrieval dan tidak dapat menjadi sumber sponsor berbasis artikel.

### Aktif · 2048 dimensi

Embedding tersedia, hash masih cocok dengan isi sekarang, dan `is_active=1`.
Artikel dapat diranking untuk pertanyaan KCE, related articles, dan pencocokan
sponsor pada halaman artikel.

### Nonaktif

Embedding tersedia tetapi `is_active=0`. Vector tetap tersimpan agar dapat
diaktifkan kembali tanpa memanggil API embedding. Artikel dikeluarkan dari
retrieval.

### Perlu diperbarui

Artikel berubah setelah embedding terakhir dibuat. Dashboard menghitung hash
sumber terbaru dan membandingkannya dengan `source_hash` tersimpan.

Status stale adalah peringatan operasional. Implementasi retrieval saat ini
masih dapat memakai embedding stale selama `is_active=1`; karena itu operator
harus segera melakukan re-index atau menonaktifkannya sampai pembaruan selesai.

## Teks yang diubah menjadi vector

Sumber embedding dibentuk berurutan dari:

```text
title
topic
tag
isi artikel tanpa HTML (maksimum 12.000 karakter)
```

HTML entity didekode, tag HTML dibuang, whitespace berulang diringkas, kemudian
NVIDIA dipanggil dengan `input_type=passage`, `encoding_format=float`, dan
`truncate=END`. Respons wajib tepat 2048 angka; dimensi lain dianggap gagal.

`source_hash` adalah SHA-256 dari teks sumber tersebut. Perubahan judul, topic,
tag, atau bagian awal isi dapat mengubah hash. Perubahan setelah batas 12.000
karakter tidak memengaruhi sumber embedding.

## Prosedur indexing artikel baru

1. Pastikan artikel sudah dipublikasikan dan isinya final.
2. Buka **Admin → KCE Article Index**.
3. Cari berdasarkan judul, tag, atau topic.
4. Pilih maksimal 20 artikel.
5. Klik **Buat / perbarui embedding pilihan**.
6. Tunggu pesan jumlah artikel yang berhasil.
7. Pastikan status menjadi **Aktif · 2048 dimensi**.
8. Uji beberapa pertanyaan di `/kce/` dan periksa relevansinya.

Indexing memakai upsert. Re-index mengganti vector, model, hash, waktu indexing,
dan otomatis menetapkan `is_active=1`.

## Prosedur setelah artikel diedit

1. Buka dashboard dan cari artikel berstatus **Perlu diperbarui**.
2. Jika perubahan sensitif/menyesatkan, nonaktifkan embedding terlebih dahulu.
3. Pilih artikel lalu jalankan pembaruan embedding.
4. Pastikan model/waktu berubah dan status kembali aktif.
5. Uji query lama dan query yang merepresentasikan isi baru.

Tidak ada re-index otomatis ketika artikel diedit. Pemeriksaan stale dan proses
pembaruan saat ini merupakan tugas operator.

## Mengaktifkan dan menonaktifkan

Tombol aksi membalik `is_active` tanpa membuat API call:

- nonaktifkan artikel yang tidak lagi aman, relevan, atau perlu direvisi;
- aktifkan kembali bila embedding masih sesuai;
- lakukan re-index, bukan sekadar aktifkan, jika status sebelumnya stale;
- unpublish artikel sumber juga mengeluarkannya dari query retrieval.

## Cara ranking digunakan

Untuk pertanyaan chat:

1. pertanyaan dibuat menjadi query embedding;
2. seluruh embedding artikel aktif dan terbit dibaca;
3. cosine similarity dihitung di PHP untuk 2048 dimensi;
4. hasil diurutkan menurun;
5. threshold diterapkan;
6. hasil dipotong sesuai `max_article_results`.

Untuk related articles pada halaman artikel, artikel sumber dan kandidat harus
aktif. Kandidat juga difilter agar `embedding_model` sama dengan artikel sumber.

## Mengganti model embedding

Perubahan `NVIDIA_EMBEDDING_MODEL` tidak mengonversi vector lama. Vector dari
model berbeda tidak boleh diasumsikan sebanding, walaupun dimensinya sama.

Prosedur aman:

1. catat model lama dan waktu perubahan;
2. ubah environment variable dan redeploy;
3. re-index seluruh artikel terbit secara bertahap, maksimal 20/request;
4. re-index seluruh sponsored content KCE;
5. pastikan kolom model sudah seragam;
6. jalankan regression query dan kalibrasi threshold kembali.

Chat retrieval saat ini tidak memfilter kandidat berdasarkan nama model. Hindari
periode panjang dengan campuran model karena score dapat tidak bermakna.

## Biaya dan kapasitas

Setiap artikel yang dipilih memicu satu request/input embedding NVIDIA. Re-index
berulang juga memakai kuota API. Harga dan rate limit mengikuti akun/provider
NVIDIA saat deployment; dashboard tidak menghitung estimasi biaya API.

Retrieval membaca kandidat aktif dan menghitung `jumlah_artikel × 2048` operasi
di PHP untuk setiap query. Pada corpus besar, pantau latency, memory, dan biaya.
Pertimbangkan vector database/ANN index sebelum jumlah artikel membuat full scan
tidak memenuhi SLA.

## Quality assurance

Buat evaluation set berisi:

- pertanyaan yang harus menemukan artikel tertentu;
- paraphrase tanpa keyword judul;
- pertanyaan ambigu;
- pertanyaan yang seharusnya tidak menghasilkan artikel;
- konten bahasa berbeda jika didukung.

Untuk setiap perubahan model/threshold, catat expected article, hasil aktual,
score, false positive, false negative, latency, dan biaya. Jangan memilih
threshold hanya berdasarkan “terlihat bagus” pada satu pertanyaan.

## Troubleshooting

| Gejala | Penyebab/pemeriksaan |
|---|---|
| Artikel tidak muncul di dashboard | Harus `ispublished=1`; periksa filter/pagination |
| “Pilih minimal satu artikel” | Tidak ada checkbox baris yang terpilih |
| Hanya 20 yang diproses | Batas sengaja maksimum 20 per request |
| NVIDIA key belum dikonfigurasi | Isi secret dan redeploy/restart |
| Embedding harus 2048 dimensi | Model/response tidak sesuai kontrak KCE |
| Status tetap stale | Indexing gagal atau artikel berubah lagi setelah indexing |
| Artikel tidak tampil di chat | Nonaktif, unpublished, score di bawah threshold, max=0, embedding query gagal |
| Hasil kurang relevan | Audit isi sumber, model campuran, stale vector, dan threshold |
| Related article kosong | Artikel sumber belum aktif atau model kandidat berbeda |
| Request lambat/time-out | Banyak artikel, API NVIDIA lambat, atau batch terlalu besar |

## Audit database

```sql
SELECT article_id, embedding_model, is_active, indexed_at, updated_at
FROM kce_article_embeddings
ORDER BY indexed_at DESC;
```

Jangan menampilkan kolom `embedding` dalam log biasa karena ukurannya besar.
Backup tabel `kce_article_embeddings`, tetapi embedding dapat diregenerasi dari
artikel selama model/API yang sesuai masih tersedia.

## Checklist operator

- [ ] Artikel baru yang layak telah diindeks.
- [ ] Artikel stale ditangani atau dinonaktifkan.
- [ ] Artikel unpublish tidak muncul dalam retrieval.
- [ ] Model embedding konsisten.
- [ ] Threshold dan batas hasil diuji dengan evaluation set.
- [ ] Kuota, error, latency, dan biaya NVIDIA dipantau.
- [ ] Regression test dilakukan setelah model atau threshold berubah.
