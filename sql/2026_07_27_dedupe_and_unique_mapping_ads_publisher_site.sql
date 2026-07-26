-- Migrasi: tambahkan UNIQUE KEY ke mapping_advertisers_ads_publishers_site
-- untuk kunci bisnis (local_ads_id, publishers_site_local_id, ads_providers_domain_url).
--
-- Latar belakang: sql/kumpulbl_kbc_hanya_structure.sql sebelumnya hanya
-- punya index biasa (non-unique) untuk kombinasi ini, sehingga pola
-- check-then-insert di public_html/cronjob/mapping_ads_publisher.php dan
-- mapping_ads_publisher_partner.php murni mengandalkan logika aplikasi
-- untuk mencegah baris duplikat per pasangan iklan x situs. Kedua skrip
-- itu sekarang juga sudah dilindungi named-lock (GET_LOCK) supaya dua
-- eksekusi tidak saling balapan, tapi UNIQUE KEY tetap dibutuhkan sebagai
-- jaring pengaman di level database (mis. kalau suatu saat ada jalur
-- insert lain yang tidak melalui named-lock tsb).
--
-- PENTING — jalankan migrasi ini secara manual terhadap database produksi
-- Anda sendiri setelah meninjau isinya. Migrasi ini TIDAK dijalankan
-- otomatis oleh aplikasi (tidak ada migration runner di codebase ini).
-- Backup database Anda dulu sebelum menjalankan ini.
--
-- Langkah 1 WAJIB dijalankan lebih dulu: kalau saat ini sudah ada baris
-- duplikat untuk kombinasi (local_ads_id, publishers_site_local_id,
-- ads_providers_domain_url) yang sama, ALTER TABLE ADD UNIQUE KEY di
-- Langkah 2 akan GAGAL (error duplicate entry). Langkah 1 membersihkan
-- duplikat tsb dengan menyisakan HANYA baris ber-id TERBESAR (diasumsikan
-- paling baru/paling lengkap) per kombinasi, menghapus sisanya.

-- Langkah 1: hapus baris duplikat, sisakan id terbesar per kombinasi kunci bisnis.
DELETE m1 FROM mapping_advertisers_ads_publishers_site m1
INNER JOIN mapping_advertisers_ads_publishers_site m2
  ON m1.local_ads_id = m2.local_ads_id
 AND m1.publishers_site_local_id = m2.publishers_site_local_id
 AND m1.ads_providers_domain_url = m2.ads_providers_domain_url
 AND m1.id < m2.id;

-- Langkah 2: tambahkan UNIQUE KEY (aman dijalankan berulang — kalau index
-- dengan nama ini sudah ada, MySQL akan menolak dengan error "Duplicate key
-- name", yang berarti migrasi ini sudah pernah diterapkan sebelumnya).
ALTER TABLE `mapping_advertisers_ads_publishers_site`
  ADD UNIQUE KEY `uniq_ads_site_domain` (`local_ads_id`, `publishers_site_local_id`, `ads_providers_domain_url`);
