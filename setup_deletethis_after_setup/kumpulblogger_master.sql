-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Generation Time: Nov 12, 2024 at 10:14 AM
-- Server version: 10.4.32-MariaDB
-- PHP Version: 8.0.30

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Database: `kumpulblogger_master`
--

-- --------------------------------------------------------

--
-- Table structure for table `advertisers_ads`
--

CREATE TABLE `advertisers_ads` (
  `id` int(11) NOT NULL,
  `local_ads_id` int(11) NOT NULL,
  `providers_name` varchar(255) NOT NULL,
  `providers_domain_url` varchar(255) NOT NULL,
  `advertisers_id` int(11) NOT NULL,
  `title_ads` text NOT NULL,
  `description_ads` text NOT NULL,
  `landingpage_ads` text NOT NULL,
  `image_url` varchar(255) NOT NULL,
  `regdate` datetime NOT NULL,
  `budget_per_click_textads` int(11) NOT NULL DEFAULT 100,
  `budget_allocation` int(11) DEFAULT 0,
  `current_spending` int(11) DEFAULT 0,
  `current_spending_from_partner` int(11) DEFAULT 0,
  `last_updated_spending` datetime DEFAULT NULL,
  `ispublished` tinyint(1) NOT NULL DEFAULT 0,
  `published_date` datetime NOT NULL,
  `is_paid` tinyint(1) DEFAULT 0,
  `paid_date` datetime DEFAULT NULL,
  `paid_desc` text DEFAULT NULL,
  `total_click` int(11) NOT NULL DEFAULT 0,
  `current_click` int(11) NOT NULL DEFAULT 0,
  `current_click_partner` int(11) DEFAULT 0,
  `is_expired` tinyint(1) NOT NULL DEFAULT 0,
  `expired_date` datetime NOT NULL,
  `is_paused` tinyint(1) DEFAULT 0,
  `paused_date` datetime DEFAULT NULL,
  `last_update` datetime DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `advertisers_ads_partners`
--

CREATE TABLE `advertisers_ads_partners` (
  `id` int(11) NOT NULL,
  `local_ads_id` int(11) NOT NULL,
  `providers_name` varchar(255) NOT NULL,
  `providers_domain_url` varchar(255) NOT NULL,
  `advertisers_id` int(11) NOT NULL,
  `title_ads` text NOT NULL,
  `description_ads` text NOT NULL,
  `landingpage_ads` text NOT NULL,
  `image_url` text NOT NULL,
  `regdate` datetime NOT NULL,
  `budget_per_click_textads` int(11) DEFAULT NULL,
  `budget_allocation` int(11) DEFAULT NULL,
  `current_spending` int(11) DEFAULT NULL,
  `last_updated_spending` datetime DEFAULT NULL,
  `ispublished` tinyint(1) NOT NULL DEFAULT 0,
  `published_date` datetime NOT NULL,
  `total_click` int(11) NOT NULL DEFAULT 0,
  `current_click` int(11) NOT NULL DEFAULT 0,
  `is_expired` tinyint(1) NOT NULL DEFAULT 0,
  `expired_date` datetime NOT NULL,
  `is_paused` tinyint(1) NOT NULL DEFAULT 0,
  `paused_date` datetime DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `ad_clicks`
--

CREATE TABLE `ad_clicks` (
  `id` bigint(20) NOT NULL,
  `local_ads_id` int(11) NOT NULL,
  `rate_text_ads` decimal(15,2) DEFAULT NULL,
  `budget_per_click_textads` int(11) DEFAULT NULL,
  `ad_id` int(11) NOT NULL,
  `pub_id` int(11) NOT NULL,
  `pub_provider` varchar(255) NOT NULL,
  `user_cookies` varchar(255) NOT NULL,
  `ip_address` varchar(45) DEFAULT NULL,
  `browser_agent` text DEFAULT NULL,
  `referrer` text DEFAULT NULL,
  `site_name` varchar(255) DEFAULT NULL,
  `site_domain` varchar(255) DEFAULT NULL,
  `title_ads` text DEFAULT NULL,
  `landingpage_ads` text NOT NULL,
  `click_time` datetime DEFAULT current_timestamp(),
  `time_epoch_click` bigint(20) DEFAULT NULL,
  `isaudit` tinyint(1) NOT NULL DEFAULT 0,
  `audit_date` datetime DEFAULT NULL,
  `is_reject` tinyint(1) NOT NULL DEFAULT 0,
  `reason_rejection` text NOT NULL,
  `ads_providers_name` varchar(255) NOT NULL,
  `ads_providers_domain_url` varchar(255) NOT NULL,
  `pubs_providers_name` varchar(255) DEFAULT NULL,
  `pubs_providers_domain_url` varchar(255) DEFAULT NULL,
  `revenue_publishers` decimal(15,2) DEFAULT NULL,
  `revenue_adnetwork_local` decimal(15,2) DEFAULT NULL,
  `revenue_adnetwork_partner` decimal(15,2) DEFAULT NULL,
  `hash_click` varchar(255) NOT NULL,
  `hash_audit` varchar(255) DEFAULT NULL,
  `is_sync` int(11) NOT NULL DEFAULT 0,
  `syncdate` datetime DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `ad_clicks_partner`
--

CREATE TABLE `ad_clicks_partner` (
  `id` int(11) NOT NULL,
  `local_click_id` int(11) NOT NULL,
  `local_ads_id` int(11) NOT NULL,
  `rate_text_ads` int(11) DEFAULT NULL,
  `budget_per_click_textads` int(11) DEFAULT NULL,
  `ad_id` int(11) NOT NULL,
  `pub_id` int(11) NOT NULL,
  `pub_provider` varchar(255) NOT NULL,
  `user_cookies` varchar(255) NOT NULL,
  `ip_address` varchar(45) DEFAULT NULL,
  `browser_agent` text DEFAULT NULL,
  `referrer` text DEFAULT NULL,
  `site_name` varchar(255) DEFAULT NULL,
  `site_domain` varchar(255) DEFAULT NULL,
  `title_ads` text DEFAULT NULL,
  `landingpage_ads` text NOT NULL,
  `click_time` datetime NOT NULL DEFAULT current_timestamp(),
  `time_epoch_click` bigint(20) NOT NULL,
  `isaudit` tinyint(1) NOT NULL DEFAULT 0,
  `audit_date` datetime DEFAULT NULL,
  `is_reject` tinyint(1) NOT NULL DEFAULT 0,
  `reason_rejection` text NOT NULL,
  `ads_providers_name` varchar(255) NOT NULL,
  `ads_providers_domain_url` varchar(255) NOT NULL,
  `pubs_providers_name` varchar(255) DEFAULT NULL,
  `pubs_providers_domain_url` varchar(255) DEFAULT NULL,
  `revenue_publishers` int(11) DEFAULT NULL,
  `revenue_adnetwork_local` int(11) DEFAULT NULL,
  `revenue_adnetwork_partner` int(11) DEFAULT NULL,
  `hash_click` varchar(128) NOT NULL,
  `hash_audit` varchar(128) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci
PARTITION BY RANGE (year(`click_time`))
(
PARTITION p_before_2020 VALUES LESS THAN (2020) ENGINE=InnoDB,
PARTITION p_2020 VALUES LESS THAN (2021) ENGINE=InnoDB,
PARTITION p_2021 VALUES LESS THAN (2022) ENGINE=InnoDB,
PARTITION p_2022 VALUES LESS THAN (2023) ENGINE=InnoDB,
PARTITION p_future VALUES LESS THAN MAXVALUE ENGINE=InnoDB
);

-- --------------------------------------------------------

--
-- Table structure for table `document_technical`
--

CREATE TABLE `document_technical` (
  `id` int(11) NOT NULL,
  `filename` varchar(255) NOT NULL,
  `function_name` text NOT NULL,
  `description` text NOT NULL,
  `last_update` datetime DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `document_technical`
--

INSERT INTO `document_technical` (`id`, `filename`, `function_name`, `description`, `last_update`) VALUES
(1, 'show_ads_native.js.php', 'Kode PHP ini berfungsi untuk menampilkan iklan berbasis JavaScript di halaman web dalam bentuk grid yang responsif. Fungsi utamanya adalah mengambil data iklan dari database berdasarkan ID penerbit (`pubId`) dan menampilkan sejumlah iklan yang telah ditentukan oleh parameter `maxAds` dan `column` yang diterima melalui URL.\r\n\r\nProsesnya dimulai dengan memvalidasi dan membatasi jumlah iklan yang ditampilkan (antara 1 hingga 50) serta jumlah kolom dalam grid (antara 1 hingga 12). Kode kemudian membuat koneksi ke database dan menjalankan query SQL untuk mengambil iklan yang memenuhi beberapa syarat, seperti telah dipublikasikan, belum kadaluarsa, dan telah disetujui oleh penerbit dan pengiklan.\r\n\r\nSetiap iklan ditampilkan dalam elemen HTML `<div>` yang disusun dalam grid, dengan informasi yang mencakup gambar, judul, deskripsi, dan nama jaringan iklan. URL klik untuk setiap iklan dibuat secara dinamis untuk memungkinkan pelacakan klik, dengan parameter tambahan seperti alamat IP pengguna dan agen browser.\r\n\r\nJika tidak ada iklan yang ditemukan, kode menampilkan pesan bahwa tidak ada iklan yang tersedia untuk penerbit tersebut. Dengan demikian, kode ini memungkinkan penayang iklan untuk secara dinamis dan responsif menampilkan iklan di situs web mereka, sambil memastikan bahwa setiap klik dapat dilacak dan dianalisis.', '<!DOCTYPE html>\r\n<html lang=\"id\">\r\n<head>\r\n    <meta charset=\"UTF-8\">\r\n    <meta name=\"viewport\" content=\"width=device-width, initial-scale=1.0\">\r\n    <title>Penjelasan Detail Kode PHP</title>\r\n    <style>\r\n        body {\r\n            font-family: Arial, sans-serif;\r\n            line-height: 1.6;\r\n            background-color: #f4f4f4;\r\n            color: #333;\r\n            margin: 0;\r\n            padding: 20px;\r\n        }\r\n        .container {\r\n            max-width: 900px;\r\n            margin: auto;\r\n            background: #fff;\r\n            padding: 20px;\r\n            border-radius: 10px;\r\n            box-shadow: 0 0 10px rgba(0, 0, 0, 0.1);\r\n        }\r\n        h1 {\r\n            text-align: center;\r\n            color: #333;\r\n        }\r\n        h2 {\r\n            color: #555;\r\n            margin-top: 20px;\r\n            border-bottom: 2px solid #e4e4e4;\r\n            padding-bottom: 10px;\r\n        }\r\n        p {\r\n            margin: 10px 0;\r\n        }\r\n        code {\r\n            background-color: #f4f4f4;\r\n            padding: 2px 4px;\r\n            border-radius: 4px;\r\n            color: #d63384;\r\n            font-family: \"Courier New\", Courier, monospace;\r\n        }\r\n    </style>\r\n</head>\r\n<body>\r\n\r\n<div class=\"container\">\r\n    <h1>Penjelasan Detail Kode PHP</h1>\r\n\r\n    <h2>1. Pendahuluan</h2>\r\n    <p>Kode ini adalah file PHP yang menghasilkan konten JavaScript untuk menampilkan iklan dalam bentuk grid di situs web. Kode ini menggunakan parameter yang diterima dari URL untuk menentukan jumlah iklan yang akan ditampilkan, ID penerbit, nama penyedia iklan, dan jumlah kolom dalam grid.</p>\r\n\r\n    <h2>2. Header dan Parameter</h2>\r\n    <p>Kode dimulai dengan mengatur <code>header(\'Content-Type: application/javascript\');</code> untuk memastikan bahwa output diperlakukan sebagai JavaScript. Parameter yang diterima melalui <code>GET</code> seperti <code>pubId</code>, <code>pubProvName</code>, <code>maxAds</code>, dan <code>column</code> digunakan untuk mengatur preferensi tampilan iklan.</p>\r\n\r\n    <h2>3. Koneksi ke Database</h2>\r\n    <p>Koneksi ke database dibuat menggunakan <code>MySQLi</code> untuk mengambil data iklan yang akan ditampilkan. Jika koneksi gagal, kode akan berhenti dengan pesan error.</p>\r\n\r\n    <h2>4. Validasi dan Pembatasan Parameter</h2>\r\n    <p>Kode memvalidasi nilai <code>maxAds</code> untuk memastikan bahwa jumlah iklan yang ditampilkan berada dalam batas yang aman (antara 1 hingga 50). Begitu juga dengan jumlah kolom <code>column</code> yang dibatasi antara 1 hingga 12 kolom.</p>\r\n\r\n    <h2>5. Pengambilan Data Iklan</h2>\r\n    <p>Kode menyiapkan pernyataan SQL untuk mengambil iklan yang sesuai dengan ID penerbit yang diberikan. Iklan yang diambil harus memenuhi beberapa kondisi, seperti telah dipublikasikan, belum kadaluarsa, dan disetujui oleh penerbit serta pengiklan.</p>\r\n\r\n    <h2>6. Pembuatan Struktur HTML untuk Iklan</h2>\r\n    <p>Kode kemudian menghasilkan HTML dan CSS dinamis untuk menampilkan iklan dalam grid. Setiap iklan ditampilkan dalam elemen <code>&lt;div class=\"ads-item\"&gt;</code> dengan informasi seperti gambar, judul iklan, deskripsi, dan nama jaringan iklan.</p>\r\n\r\n    <h2>7. URL Klik Iklan</h2>\r\n    <p>Untuk setiap iklan, URL klik yang dilacak dibuat dengan menyertakan berbagai parameter seperti ID iklan, ID penerbit, nama penyedia iklan, alamat IP pengguna, dan agen browser. Ini memungkinkan pelacakan klik dan pencatatan dari mana pengguna datang.</p>\r\n\r\n    <h2>8. Tampilan Akhir</h2>\r\n    <p>Kode akhirnya menutup <code>&lt;div&gt;</code> container yang memuat semua iklan. Jika tidak ada iklan yang ditemukan, pesan \"No ads found for this publisher\" ditampilkan.</p>\r\n\r\n    <h2>Kesimpulan</h2>\r\n    <p>Kode ini digunakan untuk menampilkan iklan berbasis JavaScript di halaman web dengan mengatur beberapa parameter seperti jumlah iklan dan jumlah kolom tampilan. Data iklan diambil dari database dan dihasilkan dalam bentuk HTML dan CSS yang di-embed ke dalam JavaScript.</p>\r\n</div>\r\n\r\n</body>\r\n</html>\r\n', '2024-08-14 10:11:25'),
(2, 'function.php', 'function getSignatureByDomainUrl($conn, $domain_url)\r\n\r\nFungsi `getSignatureByDomainUrl` digunakan untuk mengambil nilai `signature` dari tabel `providers_partners` berdasarkan URL domain yang diberikan. \r\n\r\nFungsi ini bekerja dengan menyiapkan pernyataan SQL yang mencari `signature` di mana kolom `providers_domain_url` sesuai dengan parameter yang diberikan. Parameter `domain_url` diikat ke pernyataan SQL untuk mencegah SQL injection. \r\n\r\nSetelah pernyataan dieksekusi, hasilnya diikat ke variabel `$signature`, dan jika ditemukan, nilai `signature` tersebut dikembalikan. Jika tidak ada hasil yang ditemukan, fungsi mengembalikan `null`. Fungsi ini memastikan bahwa data `signature` diambil dengan aman dan efisien dari database berdasarkan URL domain yang diberikan.', '<!DOCTYPE html>\r\n<html lang=\"id\">\r\n<head>\r\n    <meta charset=\"UTF-8\">\r\n    <meta name=\"viewport\" content=\"width=device-width, initial-scale=1.0\">\r\n    <title>Penjelasan Fungsi PHP</title>\r\n    <style>\r\n        body {\r\n            font-family: Arial, sans-serif;\r\n            line-height: 1.6;\r\n            background-color: #f4f4f4;\r\n            color: #333;\r\n            margin: 20px;\r\n            padding: 20px;\r\n            max-width: 800px;\r\n        }\r\n        h1 {\r\n            color: #444;\r\n        }\r\n        pre {\r\n            background-color: #eee;\r\n            padding: 10px;\r\n            border-radius: 5px;\r\n            overflow-x: auto;\r\n        }\r\n    </style>\r\n</head>\r\n<body>\r\n\r\n<h1>Penjelasan Fungsi <code>getSignatureByDomainUrl</code> dalam PHP</h1>\r\n\r\n<p>Fungsi <code>getSignatureByDomainUrl</code> bertujuan untuk mengambil nilai <code>signature</code> dari tabel <code>providers_partners</code> berdasarkan URL domain yang diberikan. Fungsi ini melakukan langkah-langkah sebagai berikut:</p>\r\n\r\n<ol>\r\n    <li>\r\n        <strong>Menyiapkan Statement SQL:</strong>\r\n        <p>Fungsi ini memulai dengan menyiapkan pernyataan SQL yang akan digunakan untuk mengambil kolom <code>signature</code> dari tabel <code>providers_partners</code> dengan mencocokkan kolom <code>providers_domain_url</code> dengan parameter yang diberikan.</p>\r\n        <pre><code>$sql = \"SELECT `signature` FROM `providers_partners` WHERE `providers_domain_url` = ?\";</code></pre>\r\n    </li>\r\n    \r\n    <li>\r\n        <strong>Mengikat Parameter:</strong>\r\n        <p>Parameter <code>domain_url</code> yang diterima oleh fungsi kemudian diikatkan ke pernyataan SQL menggunakan metode <code>bind_param</code>.</p>\r\n        <pre><code>$stmt->bind_param(\"s\", $domain_url);</code></pre>\r\n    </li>\r\n    \r\n    <li>\r\n        <strong>Menjalankan Statement:</strong>\r\n        <p>Setelah parameter diikat, fungsi ini menjalankan pernyataan SQL menggunakan metode <code>execute</code>.</p>\r\n        <pre><code>$stmt->execute();</code></pre>\r\n    </li>\r\n    \r\n    <li>\r\n        <strong>Mengikat Hasil:</strong>\r\n        <p>Hasil dari eksekusi query diikatkan ke variabel <code>$signature</code> menggunakan metode <code>bind_result</code>.</p>\r\n        <pre><code>$stmt->bind_result($signature);</code></pre>\r\n    </li>\r\n    \r\n    <li>\r\n        <strong>Mengambil dan Mengembalikan Hasil:</strong>\r\n        <p>Fungsi ini kemudian mencoba untuk mengambil hasil query. Jika hasil ditemukan, maka nilai <code>signature</code> dikembalikan. Jika tidak ada hasil yang ditemukan, fungsi ini akan mengembalikan <code>null</code>.</p>\r\n        <pre><code>if ($stmt->fetch()) {\r\n    return $signature;\r\n} else {\r\n    return null;\r\n}</code></pre>\r\n    </li>\r\n    \r\n    <li>\r\n        <strong>Menutup Statement:</strong>\r\n        <p>Terakhir, fungsi ini menutup pernyataan SQL untuk membersihkan sumber daya yang digunakan.</p>\r\n        <pre><code>$stmt->close();</code></pre>\r\n    </li>\r\n</ol>\r\n\r\n<p>Fungsi ini sangat berguna ketika Anda perlu mengambil nilai <code>signature</code> dari database berdasarkan URL domain yang diberikan, misalnya untuk tujuan verifikasi atau otentikasi.</p>\r\n\r\n</body>\r\n</html>\r\n', '2024-08-14 10:32:17'),
(3, '{BASE_END_POINT}API/approve_request_partnership/index.php', '{BASE_END_POINT}API/approve_request_partnership/index.php\r\n\r\nKode PHP ini memproses permintaan persetujuan kemitraan dengan memvalidasi `providers_code`, `providers_domain_url`, dan `signature` yang diterima melalui permintaan JSON. Pertama, kode ini membuka koneksi ke database menggunakan PDO dan MySQLi. Selanjutnya, kode memverifikasi `signature` yang diberikan oleh pengguna dengan yang ada di database. Jika validasi berhasil, kode menghasilkan kunci publik dan rahasia baru yang digunakan untuk memperbarui data mitra di database. Jika terjadi kesalahan dalam validasi, seperti `signature` yang salah atau kosong, kode ini mengembalikan pesan kesalahan. Akhirnya, respon dikirimkan dalam format JSON, menandakan apakah proses berhasil atau tidak. Kode ini memastikan bahwa hanya permintaan valid yang dapat memodifikasi data kemitraan di database.\r\n', '<!DOCTYPE html>\r\n<html lang=\"id\">\r\n<head>\r\n    <meta charset=\"UTF-8\">\r\n    <meta name=\"viewport\" content=\"width=device-width, initial-scale=1.0\">\r\n    <title>Penjelasan Fungsi PHP</title>\r\n    <style>\r\n        body {\r\n            font-family: Arial, sans-serif;\r\n            line-height: 1.6;\r\n            background-color: #f4f4f4;\r\n            color: #333;\r\n            margin: 20px;\r\n            padding: 20px;\r\n            max-width: 800px;\r\n        }\r\n        h1 {\r\n            color: #444;\r\n        }\r\n        pre {\r\n            background-color: #eee;\r\n            padding: 10px;\r\n            border-radius: 5px;\r\n            overflow-x: auto;\r\n        }\r\n    </style>\r\n</head>\r\n<body>\r\n\r\n<h1>Penjelasan Fungsi PHP untuk <code>approve_request_partnership</code></h1>\r\n\r\n<p>Kode PHP ini digunakan untuk memproses permintaan persetujuan kemitraan dengan memvalidasi <code>providers_code</code> dan <code>providers_domain_url</code> serta membandingkan <code>signature</code> yang dikirim oleh klien dengan yang ada di database. Proses ini dilakukan melalui beberapa langkah utama:</p>\r\n\r\n<ol>\r\n    <li>\r\n        <strong>Koneksi ke Database:</strong>\r\n        <p>Kode ini membuka dua koneksi ke database, satu menggunakan PDO dan satu lagi menggunakan MySQLi. PDO digunakan untuk eksekusi aman dari query database, sedangkan MySQLi digunakan untuk beberapa fungsi spesifik lainnya seperti mengambil <code>signature</code>.</p>\r\n        <pre><code>try {\r\n    $pdo = new PDO(...);\r\n    ...\r\n} catch (PDOException $e) {\r\n    ...\r\n}\r\n\r\n$conn = new mysqli(...);\r\nif ($conn->connect_error) {\r\n    ...\r\n}</code></pre>\r\n    </li>\r\n    \r\n    <li>\r\n        <strong>Validasi Data Masukan:</strong>\r\n        <p>Setelah menerima data JSON dari permintaan, kode ini memvalidasi apakah <code>providers_code</code>, <code>providers_domain_url</code>, dan <code>signature</code> ada. Jika salah satu tidak ada, permintaan dianggap tidak valid.</p>\r\n        <pre><code>if (isset($data[\'providers_code\']) && isset($data[\'providers_domain_url\'])) {\r\n    ...\r\n}</code></pre>\r\n    </li>\r\n    \r\n    <li>\r\n        <strong>Validasi <code>Signature</code>:</strong>\r\n        <p>Kode ini membandingkan <code>signature</code> yang diberikan dengan yang ada di database. Jika <code>signature</code> tidak cocok atau kosong, proses akan dihentikan dan pesan kesalahan dikembalikan.</p>\r\n        <pre><code>$verifying_signature = getSignatureByDomainUrl($conn, $providers_domain_url);\r\nif ($signature != $verifying_signature || $signature == \'\') {\r\n    ...\r\n}</code></pre>\r\n    </li>\r\n    \r\n    <li>\r\n        <strong>Generasi Kunci Publik dan Rahasia:</strong>\r\n        <p>Jika validasi berhasil, kode ini menghasilkan kunci publik dan kunci rahasia menggunakan fungsi <code>sha1</code> yang digabungkan dengan nilai acak dan data dari masukan.</p>\r\n        <pre><code>$public_key = sha1($number_random);\r\n$secret_key = sha1($number_random2);</code></pre>\r\n    </li>\r\n    \r\n    <li>\r\n        <strong>Pembaruan Data di Database:</strong>\r\n        <p>Jika semua validasi lolos, kode ini akan memperbarui data mitra di database menggunakan kunci publik dan rahasia yang baru dibuat.</p>\r\n        <pre><code>$rt = UpdateProviderPartner($pdo, $providers_domain_url, $public_key, $secret_key, $isapproved);</code></pre>\r\n    </li>\r\n    \r\n    <li>\r\n        <strong>Respon JSON:</strong>\r\n        <p>Setelah semua proses selesai, kode ini mengirimkan respon dalam format JSON yang berisi status permintaan (berhasil atau gagal) dan pesan terkait.</p>\r\n        <pre><code>header(\'Content-Type: application/json\');\r\necho json_encode($response);</code></pre>\r\n    </li>\r\n</ol>\r\n\r\n<p>Kode ini memastikan bahwa hanya permintaan yang valid yang dapat memodifikasi data kemitraan di database, memberikan keamanan tambahan dengan menggunakan <code>signature</code> dan kunci-kunci rahasia.</p>\r\n\r\n</body>\r\n</html>\r\n', '2024-08-14 10:35:25'),
(4, '// {BASE_END_POINT}API/sync_clicks/index.php\r\n', '// {BASE_END_POINT}API/sync_clicks/index.php\r\n\r\nKode PHP ini menerima data klik iklan dari sumber eksternal melalui JSON, memvalidasi kredensial penyedia menggunakan kunci publik dan rahasia yang diterima dalam header HTTP, dan kemudian menyimpan atau memperbarui data klik tersebut dalam database. \r\n\r\nKode ini menggunakan MySQLi untuk beberapa operasi dan PDO untuk interaksi yang lebih aman dengan database. Jika data klik iklan yang diterima memiliki `hash_audit` yang belum ada dalam database, data tersebut akan disisipkan; jika sudah ada, data diperbarui sesuai dengan nilai baru yang diterima. \r\n\r\nJika validasi kredensial atau data klik gagal, kode akan mengembalikan pesan kesalahan dalam format JSON. Kode ini memastikan bahwa hanya data klik yang valid dan dari penyedia yang sah yang disimpan dalam database, menjaga keamanan dan integritas data.\r\n\r\nDalam kode PHP yang Anda berikan, tabel yang terlibat adalah:\r\n\r\n1. **`ad_clicks_partner`** - Tabel ini digunakan untuk menyimpan data klik iklan yang disinkronkan dari sumber eksternal. Kode PHP menambahkan atau memperbarui data dalam tabel ini berdasarkan `hash_audit`.\r\n\r\nSelain itu, meskipun tidak disebutkan secara langsung dalam query SQL, ada kemungkinan tabel lain juga digunakan dalam fungsi atau prosedur lain yang disebutkan dalam kode, seperti `get_providers_domain_url` dan `checkProviderCredentials`, tetapi tabel-tabel tersebut tidak dapat diidentifikasi langsung dari kode yang diberikan.\r\n', '// {BASE_END_POINT}API/sync_clicks/index.php\r\n<!DOCTYPE html>\r\n<html lang=\"id\">\r\n<head>\r\n    <meta charset=\"UTF-8\">\r\n    <meta name=\"viewport\" content=\"width=device-width, initial-scale=1.0\">\r\n    <title>Penjelasan Fungsi PHP</title>\r\n    <style>\r\n        body {\r\n            font-family: Arial, sans-serif;\r\n            line-height: 1.6;\r\n            background-color: #f4f4f4;\r\n            color: #333;\r\n            margin: 20px;\r\n            padding: 20px;\r\n            max-width: 800px;\r\n        }\r\n        h1 {\r\n            color: #444;\r\n        }\r\n        pre {\r\n            background-color: #eee;\r\n            padding: 10px;\r\n            border-radius: 5px;\r\n            overflow-x: auto;\r\n        }\r\n    </style>\r\n</head>\r\n<body>\r\n\r\n<h1>Penjelasan Fungsi PHP untuk <code>sync_clicks</code></h1>\r\n\r\n<p>Kode PHP ini digunakan untuk menerima data klik iklan dari sumber eksternal, memvalidasinya, dan menyinkronkannya ke dalam database. Prosesnya terdiri dari beberapa langkah utama:</p>\r\n\r\n<ol>\r\n    <li>\r\n        <strong>Koneksi ke Database:</strong>\r\n        <p>Kode ini membuka koneksi ke database menggunakan MySQLi untuk beberapa operasi dan menggunakan PDO untuk interaksi yang lebih aman dengan database.</p>\r\n        <pre><code>$conn = new mysqli(...);\r\n$pdo = new PDO(...);</code></pre>\r\n    </li>\r\n    \r\n    <li>\r\n        <strong>Mengambil dan Mendekode Data JSON:</strong>\r\n        <p>Kode ini mengambil input JSON yang dikirimkan oleh klien, mendekodenya, dan mengekstrak data yang relevan seperti <code>providers_domain_url</code> dan data klik iklan.</p>\r\n        <pre><code>$json = file_get_contents(\'php://input\');\r\n$data = json_decode($json, true);</code></pre>\r\n    </li>\r\n    \r\n    <li>\r\n        <strong>Validasi Kredensial Penyedia:</strong>\r\n        <p>Header HTTP untuk <code>public_key</code> dan <code>secret_key</code> diekstrak dan divalidasi untuk memastikan bahwa permintaan berasal dari penyedia yang sah.</p>\r\n        <pre><code>if (!checkProviderCredentials(...)) {\r\n    ...\r\n}</code></pre>\r\n    </li>\r\n    \r\n    <li>\r\n        <strong>Menyisipkan atau Memperbarui Data Klik Iklan:</strong>\r\n        <p>Kode ini memproses setiap data klik iklan yang diterima. Jika <code>hash_audit</code> tidak ada dalam database, data baru akan disisipkan. Jika sudah ada, data tersebut diperbarui sesuai dengan kriteria yang ditentukan.</p>\r\n        <pre><code>$stmt = $pdo->prepare(...);\r\n$stmt->execute([...]);</code></pre>\r\n    </li>\r\n    \r\n    <li>\r\n        <strong>Respon JSON:</strong>\r\n        <p>Setelah semua data diproses, kode ini mengirimkan respon dalam format JSON yang menunjukkan apakah operasi berhasil.</p>\r\n        <pre><code>header(\'Content-Type: application/json\');\r\necho json_encode($response);</code></pre>\r\n    </li>\r\n</ol>\r\n\r\n<p>Kode ini memastikan bahwa data klik iklan dari penyedia yang sah diproses dengan benar dan aman sebelum disimpan atau diperbarui dalam database. Jika ada kesalahan atau ketidakcocokan, kode ini akan mengembalikan pesan kesalahan yang sesuai.</p>\r\n\r\n</body>\r\n</html>\r\n', '2024-08-14 10:24:43'),
(5, '// {BASE_END_POINT}API/sync_ads/index.php\r\n', '// {BASE_END_POINT}API/sync_ads/index.php\r\n\r\nKode PHP ini berfungsi sebagai endpoint API untuk menyinkronkan data iklan dari penyedia jaringan iklan ke sistem lokal. Kode menerima data JSON, memvalidasi kredensial penyedia menggunakan fungsi `checkProviderCredentials`, dan kemudian memasukkan atau memperbarui data iklan dalam tabel `advertisers_ads_partners` melalui fungsi `insertOrUpdateAdvertisersAdsPartner`. Jika iklan dengan `local_ads_id` dan `providers_domain_url` yang sama sudah ada, data tersebut diperbarui; jika tidak, data baru akan dimasukkan. Jika validasi kredensial atau data gagal, kode ini mengembalikan pesan kesalahan dalam format JSON. Fungsi `insertOrUpdateAdvertisersAdsPartner` memastikan bahwa data iklan selalu sinkron dan terupdate di database.', '// {BASE_END_POINT}API/sync_ads/index.php\r\n\r\n<!DOCTYPE html>\r\n<html lang=\"id\">\r\n<head>\r\n    <meta charset=\"UTF-8\">\r\n    <meta name=\"viewport\" content=\"width=device-width, initial-scale=1.0\">\r\n    <title>Penjelasan Fungsi PHP</title>\r\n    <style>\r\n        body {\r\n            font-family: Arial, sans-serif;\r\n            line-height: 1.6;\r\n            background-color: #f4f4f4;\r\n            color: #333;\r\n            margin: 20px;\r\n            padding: 20px;\r\n            max-width: 900px;\r\n        }\r\n        h1 {\r\n            color: #444;\r\n        }\r\n        pre {\r\n            background-color: #eee;\r\n            padding: 10px;\r\n            border-radius: 5px;\r\n            overflow-x: auto;\r\n        }\r\n    </style>\r\n</head>\r\n<body>\r\n\r\n<h1>Penjelasan Fungsi PHP untuk <code>sync_ads</code></h1>\r\n\r\n<p>Kode PHP ini berfungsi sebagai endpoint API yang digunakan untuk menyinkronkan data iklan dari penyedia jaringan iklan (ad network partner) dengan sistem lokal. Berikut adalah langkah-langkah utama yang dilakukan oleh kode ini:</p>\r\n\r\n<ol>\r\n    <li>\r\n        <strong>Inisialisasi dan Koneksi ke Database:</strong>\r\n        <p>Kode ini memulai dengan menyertakan file <code>db.php</code> dan <code>function.php</code>, yang berisi konfigurasi database dan fungsi tambahan. Kemudian, koneksi ke database dibuat menggunakan PDO untuk interaksi yang aman dan MySQLi untuk beberapa operasi tambahan.</p>\r\n    </li>\r\n\r\n    <li>\r\n        <strong>Mengambil dan Mendekode Data JSON:</strong>\r\n        <p>Kode ini membaca input JSON dari request body dan mendekodenya menjadi array PHP. Data ini digunakan untuk memproses informasi iklan yang diterima dari penyedia jaringan iklan.</p>\r\n    </li>\r\n\r\n    <li>\r\n        <strong>Validasi Header untuk Kunci Autentikasi:</strong>\r\n        <p>Kode memeriksa apakah header <code>public_key</code> dan <code>secret_key</code> ada dalam request. Jika salah satu tidak ada, kode ini akan mengirimkan respons kesalahan dalam format JSON dan menghentikan proses.</p>\r\n    </li>\r\n\r\n    <li>\r\n        <strong>Validasi Kredensial Penyedia:</strong>\r\n        <p>Kode menggunakan fungsi <code>checkProviderCredentials</code> untuk memvalidasi apakah <code>public_key</code> dan <code>secret_key</code> yang diberikan sesuai dengan <code>providers_domain_url</code>. Jika kredensial tidak valid, kode ini akan mengembalikan pesan kesalahan.</p>\r\n    </li>\r\n\r\n    <li>\r\n        <strong>Memproses Data Iklan:</strong>\r\n        <p>Setelah kredensial divalidasi, kode ini mengekstrak data iklan seperti <code>title_ads</code>, <code>description_ads</code>, <code>landingpage_ads</code>, dan informasi lainnya dari input JSON. Data ini digunakan untuk memasukkan atau memperbarui informasi iklan di dalam database.</p>\r\n    </li>\r\n\r\n    <li>\r\n        <strong>Memasukkan atau Memperbarui Data Iklan:</strong>\r\n        <p>Kode ini menggunakan fungsi <code>insertOrUpdateAdvertisersAdsPartner</code> untuk memutuskan apakah data iklan akan dimasukkan sebagai entri baru atau diperbarui j\r\n', '2024-08-14 10:29:30'),
(6, 'cronjob/calculate_budgetspentads.php\r\n', 'cronjob/calculate_budgetspentads.php\r\n\r\nKode PHP ini menjalankan tugas otomatis (cron job) untuk memperbarui data iklan di sistem. \r\n\r\nKode pertama-tama mengambil `providers_domain_url` dari tabel `providers` menggunakan fungsi `get_providers_domain_url`. \r\n\r\nKemudian, fungsi `updateEmptyTitleAds` memperbarui entri di tabel `rekap_click_ads` yang memiliki `title_ads` kosong dengan data dari tabel `mapping_advertisers_ads_publishers_site`. \r\n\r\nSetelah itu, fungsi `updateSpendingData` memperbarui data pengeluaran (`current_spending` dan `last_updated_spending`) di tabel `advertisers_ads` dan `advertisers_ads_partners` berdasarkan total anggaran yang dihabiskan, yang diambil dari tabel `rekap_click_ads`. Fungsi utama yang terlibat adalah `get_providers_domain_url`, `updateEmptyTitleAds`, `updateRekapClickAds`, dan `updateSpendingData`.\r\n', '<!DOCTYPE html>\r\n<html lang=\"id\">\r\n<head>\r\n    <meta charset=\"UTF-8\">\r\n    <meta name=\"viewport\" content=\"width=device-width, initial-scale=1.0\">\r\n    <title>Penjelasan Fungsi PHP</title>\r\n    <style>\r\n        body {\r\n            font-family: Arial, sans-serif;\r\n            line-height: 1.6;\r\n            background-color: #f4f4f4;\r\n            color: #333;\r\n            margin: 20px;\r\n            padding: 20px;\r\n            max-width: 900px;\r\n        }\r\n        h1 {\r\n            color: #444;\r\n            text-align: center;\r\n        }\r\n        h2 {\r\n            color: #333;\r\n            margin-top: 20px;\r\n            border-bottom: 2px solid #e4e4e4;\r\n            padding-bottom: 10px;\r\n        }\r\n        p {\r\n            margin: 10px 0;\r\n        }\r\n        code {\r\n            background-color: #f4f4f4;\r\n            padding: 2px 4px;\r\n            border-radius: 4px;\r\n            color: #d63384;\r\n            font-family: \"Courier New\", Courier, monospace;\r\n        }\r\n    </style>\r\n</head>\r\n<body>\r\n\r\n<h1>Penjelasan Fungsi PHP untuk <code>calculate_budgetspentads.php</code></h1>\r\n\r\n<h2>Inisialisasi dan Koneksi ke Database</h2>\r\n<p>Kode ini dimulai dengan menyertakan file <code>db.php</code>, yang berisi informasi koneksi database. Dua koneksi database dibuat: satu menggunakan PDO untuk operasi yang aman, dan satu lagi menggunakan MySQLi untuk operasi lain yang memerlukan metode ini.</p>\r\n\r\n<h2>Mengambil <code>providers_domain_url</code></h2>\r\n<p>Fungsi <code>get_providers_domain_url($conn, $id)</code> digunakan untuk mengambil <code>providers_domain_url</code> dari tabel <code>providers</code> berdasarkan <code>id</code>. Hasilnya digunakan dalam operasi pembaruan data iklan berikutnya.</p>\r\n\r\n<h2>Memperbarui Data Iklan yang Kosong</h2>\r\n<p>Fungsi <code>updateEmptyTitleAds($pdo, $pubs_providers_domain_url)</code> memperbarui baris di tabel <code>rekap_click_ads</code> yang masih memiliki kolom <code>title_ads</code> kosong. Data yang diperlukan diambil dari tabel <code>mapping_advertisers_ads_publishers_site</code> dan kemudian digunakan untuk memperbarui tabel <code>rekap_click_ads</code>.</p>\r\n\r\n<h2>Memperbarui Data Pengeluaran Iklan</h2>\r\n<p>Fungsi <code>updateSpendingData($pdo, $pubs_providers_domain_url)</code> memperbarui data pengeluaran iklan di tabel <code>advertisers_ads</code> dan <code>advertisers_ads_partners</code> berdasarkan informasi dari tabel <code>rekap_click_ads</code>. Zona waktu disetel ke GMT+7 untuk memastikan waktu pembaruan sesuai dengan waktu lokal.</p>\r\n\r\n<h2>Ringkasan</h2>\r\n<p>Kode ini bertujuan untuk memastikan bahwa data iklan yang tersimpan dalam sistem tetap konsisten dan mutakhir. Pertama, kode memperbarui entri di <code>rekap_click_ads</code> yang belum memiliki judul iklan. Kemudian, kode memperbarui data pengeluaran dan waktu pembaruan terakhir di tabel <code>advertisers_ads</code> dan <code>advertisers_ads_partners</code> sesuai dengan kondisi yang berlaku.</p>\r\n\r\n</div>\r\n\r\n</body>\r\n</html>\r\n', '2024-08-14 10:35:20'),
(7, 'cronjob/calculate_budgetspentads_partner.php\r\n', 'cronjob/calculate_budgetspentads_partner.php\r\n\r\nKode PHP ini menghitung dan memperbarui data anggaran yang dihabiskan untuk iklan yang diklik di jaringan partner adNetwork. \r\n\r\nKode mengambil data klik dari tabel `ad_clicks_partner`, menghitung total anggaran yang dihabiskan oleh provider lokal, dan memperbarui data di tabel `rekap_click_ads_partner` serta `advertisers_ads`. \r\n\r\nKode juga menghitung total klik yang sah dan memperbarui entri iklan yang belum memiliki judul (`title_ads`). Fungsi utama yang terlibat adalah `calculate_budgetspentads_partner`, `calculateTotalClicks`, `updateEmptyTitleAds`, dan `updateRekapClickAds_partner`. Tabel yang terlibat meliputi `ad_clicks_partner`, `rekap_click_ads_partner`, `advertisers_ads`, dan `mapping_advertisers_ads_publishers_site`. Kode ini memastikan data iklan yang diklik tetap konsisten dan akurat di seluruh jaringan.\r\n', 'cronjob/calculate_budgetspentads_partner.php\r\n<!DOCTYPE html>\r\n<html lang=\"id\">\r\n<head>\r\n    <meta charset=\"UTF-8\">\r\n    <meta name=\"viewport\" content=\"width=device-width, initial-scale=1.0\">\r\n    <title>Penjelasan Fungsi PHP</title>\r\n    <style>\r\n        body {\r\n            font-family: Arial, sans-serif;\r\n            background-color: #f4f4f4;\r\n            line-height: 1.6;\r\n            color: #333;\r\n            margin: 0;\r\n            padding: 20px;\r\n        }\r\n        .container {\r\n            max-width: 900px;\r\n            margin: auto;\r\n            background: #fff;\r\n            padding: 20px;\r\n            border-radius: 10px;\r\n            box-shadow: 0 0 10px rgba(0, 0, 0, 0.1);\r\n        }\r\n        h1, h2, h3 {\r\n            color: #333;\r\n        }\r\n        h1 {\r\n            text-align: center;\r\n            margin-bottom: 20px;\r\n        }\r\n        h2 {\r\n            margin-top: 20px;\r\n            border-bottom: 2px solid #e4e4e4;\r\n            padding-bottom: 10px;\r\n        }\r\n        h3 {\r\n            margin-top: 15px;\r\n            margin-bottom: 10px;\r\n        }\r\n        p {\r\n            margin: 10px 0;\r\n        }\r\n        code {\r\n            background-color: #f4f4f4;\r\n            padding: 2px 4px;\r\n            border-radius: 4px;\r\n            color: #d63384;\r\n            font-family: \"Courier New\", Courier, monospace;\r\n        }\r\n    </style>\r\n</head>\r\n<body>\r\n\r\n<div class=\"container\">\r\n    <h1>Penjelasan Fungsi PHP untuk <code>calculate_budgetspentads_partner.php</code></h1>\r\n\r\n    <h2>Inisialisasi dan Koneksi ke Database</h2>\r\n    <p>Kode ini dimulai dengan menyertakan file <code>db.php</code> dan <code>function.php</code> yang menyediakan informasi koneksi ke database dan fungsi tambahan yang diperlukan. Dua koneksi ke database diinisialisasi, satu menggunakan <code>PDO</code> untuk operasi yang aman, dan satu lagi menggunakan <code>MySQLi</code> untuk operasi lain yang membutuhkan metode ini.</p>\r\n\r\n    <h2>Fungsi Utama dalam Kode</h2>\r\n\r\n    <h3>1. <code>calculate_budgetspentads_partner($conn, $local_ads_id, $ads_providers_domain_url)</code></h3>\r\n    <p>Fungsi ini menghitung total anggaran yang dihabiskan oleh provider lokal untuk iklan yang diklik di jaringan partner adNetwork. Hasil perhitungan disimpan atau diperbarui dalam tabel <code>rekap_click_ads_partner</code>, dan data ini juga diperbarui dalam tabel <code>advertisers_ads</code>.</p>\r\n\r\n    <h3>2. <code>calculateTotalClicks($pdo)</code></h3>\r\n    <p>Fungsi ini menghitung total klik yang sah untuk setiap iklan dalam tabel <code>rekap_click_ads_partner</code>. Klik dianggap sah jika telah melalui proses audit dan tidak ditolak. Hasil perhitungan kemudian disimpan kembali dalam tabel yang sama.</p>\r\n\r\n    <h3>3. <code>updateEmptyTitleAds($pdo, $pubs_providers_domain_url)</code></h3>\r\n    <p>Fungsi ini memperbarui entri dalam tabel <code>rekap_click_ads_partner</code> yang belum memiliki <code>title_ads</code>. Data ini diambil dari tabel <code>mapping_advertisers_ads_publishers_site</code> dan digunakan untuk memperbarui informasi iklan.</p>\r\n\r\n    <h3>4. <code>updateRekapClickAds_partner($pdo, $local_ads_id, $ads_providers_domain_url, $pubs_providers_domain_url)</code></h3>\r\n    <p>Fungsi ini memperbarui data iklan dalam tabel <code>rekap_click_ads_partner</code> dengan informasi tambahan seperti <code>title_ads</code>, <code>description_ads</code>, dan <code>landingpage_ads</code>. Data diperoleh dari tabel <code>mapping_advertisers_ads_publishers_site</code>.</p>\r\n\r\n    <h2>Alur Kerja Utama</h2>\r\n    <p>Berikut adalah alur kerja utama dari kode ini:</p>\r\n    <ol>\r\n        <li>Kode memanggil fungsi <code>get_providers_domain_url($conn, $id)</code> untuk mendapatkan domain URL dari provider.</li>\r\n        <li>Data klik diambil dari tabel <code>ad_clicks_partner</code> dan diproses menggunakan fungsi <code>calculate_budgetspentads_partner</code>.</li>\r\n        <li>Jika ditemukan iklan dengan <code>title_ads</code> yang kosong, fungsi <code>updateEmptyTitleAds($pdo, $this_providers_domain_url)</code> dipanggil untuk memperbarui data iklan tersebut.</li>\r\n        <li>Kode memanggil fungsi <code>calculateTotalClicks($pdo)</code> untuk menghitung dan memperbarui jumlah total klik pada tabel <code>rekap_click_ads_partner</code>.</li>\r\n    </ol>\r\n\r\n    <h2>Ringkasan</h2>\r\n    <p>Kode ini dirancang untuk menghitung dan memperbarui data transaksi klik iklan yang terjadi di jaringan partner adNetwork. Proses ini melibatkan pengambilan dan perhitungan data klik, serta pembaruan data iklan yang tidak lengkap, sehingga data iklan yang tersimpan tetap konsisten, akurat, dan mutakhir.</p>\r\n</div>\r\n\r\n</body>\r\n</html>\r\n', '2024-08-14 10:37:51'),
(8, 'cronjob/mapping_ads_publisher.php ', 'cronjob/mapping_ads_publisher.php \r\n\r\nKode ini memetakan iklan yang aktif dari tabel `advertisers_ads` dengan situs penerbit dari tabel `publishers_site`. Iklan yang memenuhi syarat anggaran (`budget_per_click_textads`) akan dipetakan ke situs penerbit, dan tarif iklan penerbit diberi markup 50%. \r\n\r\nJika data iklan sudah ada di tabel `mapping_advertisers_ads_publishers_site`, maka data tersebut diperbarui; jika belum ada, data baru disisipkan. Proses ini memastikan bahwa iklan hanya ditampilkan di situs penerbit yang memenuhi syarat anggaran. \r\n\r\nTabel yang terlibat adalah `advertisers_ads`, `publishers_site`, dan `mapping_advertisers_ads_publishers_site`. Tidak ada fungsi terpisah yang digunakan, semua operasi dilakukan langsung di dalam skrip utama.\r\n', '<!DOCTYPE html>\r\n<html lang=\"id\">\r\n<head>\r\n    <meta charset=\"UTF-8\">\r\n    <meta name=\"viewport\" content=\"width=device-width, initial-scale=1.0\">\r\n    <title>Penjelasan Fungsi PHP</title>\r\n    <style>\r\n        body {\r\n            font-family: Arial, sans-serif;\r\n            background-color: #f4f4f4;\r\n            line-height: 1.6;\r\n            color: #333;\r\n            margin: 0;\r\n            padding: 20px;\r\n        }\r\n        .container {\r\n            max-width: 900px;\r\n            margin: auto;\r\n            background: #fff;\r\n            padding: 20px;\r\n            border-radius: 10px;\r\n            box-shadow: 0 0 10px rgba(0, 0, 0, 0.1);\r\n        }\r\n        h1, h2 {\r\n            color: #333;\r\n        }\r\n        h1 {\r\n            text-align: center;\r\n        }\r\n        h2 {\r\n            margin-top: 20px;\r\n            border-bottom: 2px solid #e4e4e4;\r\n            padding-bottom: 10px;\r\n        }\r\n        p {\r\n            margin: 10px 0;\r\n        }\r\n        code {\r\n            background-color: #f4f4f4;\r\n            padding: 2px 4px;\r\n            border-radius: 4px;\r\n            color: #d63384;\r\n            font-family: \"Courier New\", Courier, monospace;\r\n        }\r\n    </style>\r\n</head>\r\n<body>\r\n\r\n<div class=\"container\">\r\n    <h1>Penjelasan Fungsi PHP untuk <code>mapping_ads_publisher.php</code></h1>\r\n\r\n    <h2>Inisialisasi dan Koneksi ke Database</h2>\r\n    <p>Kode dimulai dengan menampilkan judul di halaman web dan menginisialisasi koneksi ke database menggunakan <code>MySQLi</code>. Koneksi ini digunakan untuk mengakses database dan menjalankan berbagai operasi SQL terkait pemetaan iklan dengan situs penerbit.</p>\r\n\r\n    <h2>Mengambil Data Iklan dari Tabel <code>advertisers_ads</code></h2>\r\n    <p>Kode ini mengeksekusi query SQL untuk mengambil semua iklan yang aktif (di mana <code>ispublished = 1</code> dan <code>is_expired = 0</code>) dari tabel <code>advertisers_ads</code>. Iklan-iklan ini kemudian akan diproses lebih lanjut untuk dipetakan dengan situs penerbit.</p>\r\n\r\n    <h2>Memproses Setiap Iklan Aktif</h2>\r\n    <p>Setiap iklan yang aktif diproses dalam sebuah loop. Data penting seperti <code>local_ads_id</code>, <code>title_ads</code>, <code>description_ads</code>, <code>landingpage_ads</code>, dan <code>budget_per_click_textads</code> diambil untuk digunakan dalam pemetaan dengan situs penerbit.</p>\r\n\r\n    <h2>Mengambil Data dari Tabel <code>publishers_site</code></h2>\r\n    <p>Kode ini kemudian mengambil data dari tabel <code>publishers_site</code> untuk memproses setiap situs penerbit yang ada. Data yang diambil mencakup <code>publishers_site_local_id</code>, <code>rate_text_ads</code>, <code>site_name</code>, dan <code>site_domain</code>.</p>\r\n\r\n    <h2>Perhitungan dan Validasi Anggaran Iklan</h2>\r\n    <p>Kode menambahkan markup sebesar 50% pada <code>rate_text_ads</code> untuk menghitung <code>rate_text_ads_with_markup</code>. Kode kemudian memeriksa apakah <code>budget_per_click_textads</code> dari iklan memenuhi syarat, yaitu lebih besar atau sama dengan <code>rate_text_ads_with_markup</code>.</p>\r\n\r\n    <h2>Cek Eksistensi dan Pembaruan Data</h2>\r\n    <p>Jika syarat anggaran terpenuhi, kode ini mengecek apakah data pemetaan iklan dengan situs penerbit sudah ada di tabel <code>mapping_advertisers_ads_publishers_site</code>. Jika data sudah ada, maka akan diperbarui; jika tidak, data baru akan disisipkan.</p>\r\n\r\n    <h2>Menutup Koneksi ke Database</h2>\r\n    <p>Setelah semua proses selesai, koneksi ke database ditutup untuk memastikan tidak ada kebocoran sumber daya.</p>\r\n\r\n    <h2>Ringkasan</h2>\r\n    <p>Kode ini berfungsi untuk memetakan iklan yang aktif dengan situs penerb\r\n', NULL),
(9, 'cronjob/mapping_ads_publisher_partner.php', 'cronjob/mapping_ads_publisher_partner.php\r\n\r\nKode ini berfungsi untuk memetakan iklan dari tabel `advertisers_ads_partners` ke situs penerbit dari tabel `publishers_site` dalam tabel `mapping_advertisers_ads_publishers_site`. Kode mengambil iklan yang aktif dan belum kedaluwarsa dari tabel `advertisers_ads_partners`, kemudian mencocokkannya dengan situs penerbit.\r\n\r\n Jika `budget_per_click_textads` iklan memenuhi syarat (lebih besar atau sama dengan `rate_text_ads` yang telah diberi markup 50%), kode akan memeriksa apakah data sudah ada di tabel `mapping_advertisers_ads_publishers_site`. \r\n\r\nJika ada, data diperbarui; jika tidak, data baru dimasukkan. Function yang terlibat adalah `mysqli->query` untuk menjalankan query SQL, `fetch_assoc()` untuk mengambil hasil query, dan `close()` untuk menutup koneksi database.', 'cronjob/mapping_ads_publisher_partner.php\r\n\r\n<!DOCTYPE html>\r\n<html lang=\"id\">\r\n<head>\r\n    <meta charset=\"UTF-8\">\r\n    <meta name=\"viewport\" content=\"width=device-width, initial-scale=1.0\">\r\n    <title>Penjelasan Fungsi Kode PHP</title>\r\n    <style>\r\n        body {\r\n            font-family: Arial, sans-serif;\r\n            background-color: #f4f4f4;\r\n            line-height: 1.6;\r\n            color: #333;\r\n            margin: 0;\r\n            padding: 20px;\r\n        }\r\n        .container {\r\n            max-width: 900px;\r\n            margin: auto;\r\n            background: #fff;\r\n            padding: 20px;\r\n            border-radius: 10px;\r\n            box-shadow: 0 0 10px rgba(0, 0, 0, 0.1);\r\n        }\r\n        h1, h2 {\r\n            color: #333;\r\n        }\r\n        h1 {\r\n            text-align: center;\r\n        }\r\n        h2 {\r\n            margin-top: 20px;\r\n            border-bottom: 2px solid #e4e4e4;\r\n            padding-bottom: 10px;\r\n        }\r\n        p {\r\n            margin: 10px 0;\r\n        }\r\n        code {\r\n            background-color: #f4f4f4;\r\n            padding: 2px 4px;\r\n            border-radius: 4px;\r\n            color: #d63384;\r\n            font-family: \"Courier New\", Courier, monospace;\r\n        }\r\n    </style>\r\n</head>\r\n<body>\r\n\r\n<div class=\"container\">\r\n    <h1>Penjelasan Fungsi Kode PHP</h1>\r\n\r\n    <h2>1. Inisialisasi dan Koneksi ke Database</h2>\r\n    <p>Kode dimulai dengan menampilkan judul di halaman web menggunakan <code>echo</code>. Selanjutnya, dilakukan koneksi ke database menggunakan <code>MySQLi</code> dengan parameter yang diambil dari file <code>db.php</code>. Jika koneksi gagal, proses akan dihentikan dan pesan kesalahan ditampilkan.</p>\r\n\r\n    <h2>2. Mengambil Data Iklan dari Tabel <code>advertisers_ads_partners</code></h2>\r\n    <p>Kode ini mengambil data iklan dari tabel <code>advertisers_ads_partners</code> di mana <code>ispublished = 1</code> dan <code>is_expired = 0</code>. Data yang diambil mencakup informasi iklan seperti <code>local_ads_id</code>, <code>providers_name</code>, dan <code>budget_per_click_textads</code>. Hasil query ini disimpan dalam variabel <code>$result_ads</code> dan ditampilkan untuk debugging.</p>\r\n\r\n    <h2>3. Memproses Setiap Iklan</h2>\r\n    <p>Jika terdapat hasil dari query, kode akan memproses setiap baris data iklan menggunakan loop <code>while</code>. Informasi dari setiap iklan, seperti <code>title_ads</code> dan <code>landingpage_ads</code>, akan ditampilkan di halaman untuk tujuan debugging.</p>\r\n\r\n    <h2>4. Mengambil Data dari Tabel <code>publishers_site</code></h2>\r\n    <p>Kode kemudian menjalankan query untuk mengambil data dari tabel <code>publishers_site</code>. Data yang diambil mencakup informasi seperti <code>site_name</code> dan <code>rate_text_ads</code>. Hasil query ini disimpan dalam variabel <code>$result_site</code> dan ditampilkan untuk debugging.</p>\r\n\r\n    <h2>5. Memproses Setiap Situs Penerbit (Publisher Site)</h2>\r\n    <p>Jika terdapat hasil dari query, kode akan memproses setiap situs penerbit menggunakan loop <code>while</code>. Kode ini menghitung nilai <code>rate_text_ads_with_markup</code> dengan menambahkan markup 50% pada <code>rate_text_ads</code>. Jika <code>budget_per_click_textads</code> memenuhi syarat, yaitu lebih besar atau sama dengan <code>rate_text_ads_with_markup</code>, kode akan melakukan pengecekan eksistensi data.</p>\r\n\r\n    <h2>6. Cek Eksistensi dan Pembaruan Data</h2>\r\n    <p>Kode memeriksa apakah data iklan dengan <code>local_ads_id</code> dan <code>publishers_site_local_id</code> tertentu sudah ada di tabel <code>mapping_advertisers_ads_publishers_site</code>. Jika data sudah ada, kode akan memperbarui data tersebut dengan informasi terbaru seperti <code>title_ads</code> dan <code>description_ads</code>. Jika data belum ada, kode akan menyisipkan data baru ke dalam tabel tersebut.</p>\r\n\r\n    <h2>7. Penyisipan Data Baru</h2>\r\n    <p>Jika data belum ada, kode akan menyisipkan data baru ke dalam tabel <code>mapping_advertisers_ads_publishers_site</code> dengan nilai-nilai yang telah dihitung sebelumnya, termasuk <code>rate_text_ads_with_markup</code> dan <code>revenue_publishers</code>.</p>\r\n\r\n    <h2>8. Menampilkan Query SQL</h2>\r\n    <p>Kode ini juga menampilkan beberapa query SQL yang dieksekusi untuk debugging atau pemantauan selama proses pemetaan data.</p>\r\n\r\n    <h2>9. Menutup Koneksi ke Database</h2>\r\n    <p>Setelah semua operasi selesai, koneksi ke database ditutup menggunakan <code>$mysqli->close()</code> untuk mengakhiri sesi dengan benar.</p>\r\n\r\n    <h2>Ringkasan</h2>\r\n    <p>Kode ini berfungsi untuk memetakan iklan dari jaringan AdNetwork lain dengan situs penerbit lokal. Proses ini melibatkan pengambilan data iklan yang aktif, pengecekan syarat anggaran, serta pembaruan atau penyisipan data yang sesuai ke dalam tabel <code>mapping_advertisers_ads_publishers_site</code>.</p>\r\n\r\n</div>\r\n\r\n</body>\r\n</html>\r\n', NULL),
(10, 'cronjob/mapping_ads_publisher_check_rate.php \r\n', 'cronjob/mapping_ads_publisher_check_rate.php \r\n\r\nKode ini berfungsi untuk memeriksa kesesuaian tarif iklan (`rate_text_ads`) dan anggaran iklan (`budget_per_click_textads`) di antara tabel `publishers_site`, `advertisers_ads`, dan `mapping_advertisers_ads_publishers_site`. \r\n\r\nKode pertama-tama menambahkan margin 50% pada `rate_text_ads` dari tabel `publishers_site`, kemudian membandingkannya dengan `budget_per_click_textads` di tabel `mapping_advertisers_ads_publishers_site`. \r\n\r\nJika tarif dengan margin lebih tinggi dari anggaran, data di `mapping_advertisers_ads_publishers_site` diperbarui dengan penolakan. Kode ini menggunakan fungsi `mysqli->query()` untuk menjalankan query SQL, `fetch_assoc()` untuk mengambil data hasil query, dan `close()` untuk menutup koneksi database.\r\n', 'cronjob/mapping_ads_publisher_check_rate.php \r\n<!DOCTYPE html>\r\n<html lang=\"id\">\r\n<head>\r\n    <meta charset=\"UTF-8\">\r\n    <meta name=\"viewport\" content=\"width=device-width, initial-scale=1.0\">\r\n    <title>Penjelasan Fungsi Kode PHP</title>\r\n    <style>\r\n        body {\r\n            font-family: Arial, sans-serif;\r\n            background-color: #f4f4f4;\r\n            line-height: 1.6;\r\n            color: #333;\r\n            margin: 0;\r\n            padding: 20px;\r\n        }\r\n        .container {\r\n            max-width: 900px;\r\n            margin: auto;\r\n            background: #fff;\r\n            padding: 20px;\r\n            border-radius: 10px;\r\n            box-shadow: 0 0 10px rgba(0, 0, 0, 0.1);\r\n        }\r\n        h1, h2 {\r\n            color: #333;\r\n        }\r\n        h1 {\r\n            text-align: center;\r\n        }\r\n        h2 {\r\n            margin-top: 20px;\r\n            border-bottom: 2px solid #e4e4e4;\r\n            padding-bottom: 10px;\r\n        }\r\n        p {\r\n            margin: 10px 0;\r\n        }\r\n        code {\r\n            background-color: #f4f4f4;\r\n            padding: 2px 4px;\r\n            border-radius: 4px;\r\n            color: #d63384;\r\n            font-family: \"Courier New\", Courier, monospace;\r\n        }\r\n    </style>\r\n</head>\r\n<body>\r\n\r\n<div class=\"container\">\r\n    <h1>Penjelasan Fungsi Kode PHP</h1>\r\n\r\n    <h2>1. Inisialisasi dan Koneksi ke Database</h2>\r\n    <p>Kode dimulai dengan membuat koneksi ke database menggunakan <code>MySQLi</code>. Jika koneksi gagal, proses dihentikan dan pesan kesalahan ditampilkan.</p>\r\n\r\n    <h2>2. Tahap 1: Pengecekan <code>rate_text_ads</code> pada Tabel <code>publishers_site</code></h2>\r\n    <p>Pada tahap ini, kode melakukan pengecekan tarif iklan dari tabel <code>publishers_site</code>. Langkah-langkahnya adalah sebagai berikut:</p>\r\n    <ul>\r\n        <li><strong>Step 1:</strong> Mengambil data <code>rate_text_ads</code>, <code>publishers_local_id</code>, dan <code>site_domain</code>.</li>\r\n        <li><strong>Step 2:</strong> Menambahkan margin 50% pada <code>rate_text_ads</code> untuk menghasilkan <code>rate_with_margin</code>.</li>\r\n        <li><strong>Step 3:</strong> Memeriksa apakah ada data yang cocok di tabel <code>mapping_advertisers_ads_publishers_site</code>.</li>\r\n        <li><strong>Step 4:</strong> Jika tarif iklan dengan margin lebih besar dari anggaran iklan, data diperbarui dengan alasan penolakan \"out of budget\".</li>\r\n    </ul>\r\n\r\n    <h2>3. Tahap 2: Pengecekan <code>budget_per_click_textads</code> pada Tabel <code>advertisers_ads</code></h2>\r\n    <p>Pada tahap ini, kode melakukan pengecekan anggaran iklan dari tabel <code>advertisers_ads</code>. Langkah-langkahnya adalah sebagai berikut:</p>\r\n    <ul>\r\n        <li><strong>Step 1:</strong> Mengambil data <code>budget_per_click_textads</code> dan <code>local_ads_id</code>.</li>\r\n        <li><strong>Step 2:</strong> Memeriksa data terkait di tabel <code>mapping_advertisers_ads_publishers_site</code>.</li>\r\n        <li><strong>Step 3:</strong> Jika anggaran iklan lebih kecil dari tarif iklan, data diperbarui dengan alasan penolakan \"out of budget\".</li>\r\n    </ul>\r\n\r\n    <h2>4. Menutup Koneksi ke Database</h2>\r\n    <p>Setelah semua operasi selesai, koneksi ke database ditutup untuk mengakhiri sesi dengan benar.</p>\r\n\r\n    <h2>Ringkasan</h2>\r\n    <p>Kode ini berfungsi untuk memastikan bahwa iklan hanya disetujui jika anggarannya sesuai dengan tarif iklan yang telah ditambah margin. Jika tidak sesuai, iklan akan ditolak dan alasan penolakan akan dicatat.</p>\r\n\r\n</div>\r\n\r\n</body>\r\n</html>\r\n', NULL);
INSERT INTO `document_technical` (`id`, `filename`, `function_name`, `description`, `last_update`) VALUES
(11, 'cronjob/mapping_ads_publisher_check_rate_partner.php \r\n', 'cronjob/mapping_ads_publisher_check_rate_partner.php \r\n\r\nKode ini berfungsi untuk memeriksa dan memvalidasi tarif iklan (`rate_text_ads`) dan anggaran iklan (`budget_per_click_textads`) yang terdapat dalam tabel `publishers_site`, `advertisers_ads_partners`, dan `mapping_advertisers_ads_publishers_site`. Pertama, kode menambahkan margin 50% pada `rate_text_ads` dari tabel `publishers_site` dan membandingkannya dengan anggaran di `mapping_advertisers_ads_publishers_site`. \r\n\r\nJika tarif dengan margin lebih tinggi dari anggaran, iklan ditolak dan alasan penolakan dicatat. Kedua, kode memeriksa apakah anggaran iklan dari tabel `advertisers_ads_partners` memenuhi tarif di `mapping_advertisers_ads_publishers_site`. Jika tidak, iklan juga ditolak. Fungsi yang digunakan meliputi `mysqli->query()`, `fetch_assoc()`, dan `close()`.', 'cronjob/mapping_ads_publisher_check_rate_partner.php \r\n\r\n<!DOCTYPE html>\r\n<html lang=\"id\">\r\n<head>\r\n    <meta charset=\"UTF-8\">\r\n    <meta name=\"viewport\" content=\"width=device-width, initial-scale=1.0\">\r\n    <title>Penjelasan Fungsi Kode PHP</title>\r\n    <style>\r\n        body {\r\n            font-family: Arial, sans-serif;\r\n            background-color: #f4f4f4;\r\n            line-height: 1.6;\r\n            color: #333;\r\n            margin: 0;\r\n            padding: 20px;\r\n        }\r\n        .container {\r\n            max-width: 900px;\r\n            margin: auto;\r\n            background: #fff;\r\n            padding: 20px;\r\n            border-radius: 10px;\r\n            box-shadow: 0 0 10px rgba(0, 0, 0, 0.1);\r\n        }\r\n        h1, h2 {\r\n            color: #333;\r\n        }\r\n        h1 {\r\n            text-align: center;\r\n        }\r\n        h2 {\r\n            margin-top: 20px;\r\n            border-bottom: 2px solid #e4e4e4;\r\n            padding-bottom: 10px;\r\n        }\r\n        p {\r\n            margin: 10px 0;\r\n        }\r\n        code {\r\n            background-color: #f4f4f4;\r\n            padding: 2px 4px;\r\n            border-radius: 4px;\r\n            color: #d63384;\r\n            font-family: \"Courier New\", Courier, monospace;\r\n        }\r\n    </style>\r\n</head>\r\n<body>\r\n\r\n<div class=\"container\">\r\n    <h1>Penjelasan Fungsi Kode PHP</h1>\r\n\r\n    <h2>1. Inisialisasi dan Koneksi ke Database</h2>\r\n    <p>Kode dimulai dengan menampilkan judul di halaman web menggunakan <code>echo</code>. Setelah itu, koneksi ke database dibuat menggunakan <code>MySQLi</code> dengan parameter yang diambil dari file <code>db.php</code>. Jika koneksi gagal, proses akan dihentikan dan pesan kesalahan akan ditampilkan.</p>\r\n\r\n    <h2>2. Tahap 1: Pengecekan <code>rate_text_ads</code> pada Tabel <code>publishers_site</code></h2>\r\n    <p>Pada tahap ini, kode melakukan pengecekan tarif iklan dari tabel <code>publishers_site</code> dan menambahkan margin 50%. Langkah-langkahnya adalah sebagai berikut:</p>\r\n    <ul>\r\n        <li><strong>Step 1:</strong> Mengambil data <code>rate_text_ads</code>, <code>publishers_local_id</code>, dan <code>site_domain</code>.</li>\r\n        <li><strong>Step 2:</strong> Menghitung nilai <code>rate_with_margin</code> dengan menambahkan margin 50% pada <code>rate_text_ads</code>.</li>\r\n        <li><strong>Step 3:</strong> Memeriksa tabel <code>mapping_advertisers_ads_publishers_site</code> untuk data yang cocok dengan <code>publishers_local_id</code> dan <code>site_domain</code>.</li>\r\n        <li><strong>Step 4:</strong> Jika <code>rate_with_margin</code> lebih besar dari anggaran, data diperbarui dengan menolak iklan dan memberikan alasan \"out of budget\".</li>\r\n    </ul>\r\n\r\n    <h2>3. Tahap 2: Pengecekan <code>budget_per_click_textads</code> pada Tabel <code>advertisers_ads_partners</code></h2>\r\n    <p>Pada tahap ini, kode memeriksa anggaran iklan dari tabel <code>advertisers_ads_partners</code>. Langkah-langkahnya adalah sebagai berikut:</p>\r\n    <ul>\r\n        <li><strong>Step 1:</strong> Mengambil data <code>budget_per_click_textads</code> dan <code>local_ads_id</code>.</li>\r\n        <li><strong>Step 2:</strong> Memeriksa tabel <code>mapping_advertisers_ads_publishers_site</code> untuk data yang cocok dengan <code>local_ads_id</code>.</li>\r\n        <li><strong>Step 3:</strong> Jika anggaran lebih kecil dari tarif, data diperbarui dengan menolak iklan dan memberikan alasan \"out of budget\".</li>\r\n    </ul>\r\n\r\n    <h2>4. Menutup Koneksi ke Database</h2>\r\n    <p>Setelah semua operasi selesai, koneksi ke database ditutup menggunakan <code>$mysqli->close()</code> untuk mengakhiri sesi dengan benar.</p>\r\n\r\n    <h2>Ringkasan</h2>\r\n    <p>Kode ini memastikan bahwa iklan dari jaringan AdNetwork lain hanya disetujui jika anggaran iklan memenuhi tarif yang telah ditambah margin. Jika tidak, iklan akan ditolak dan alasannya dicatat.</p>\r\n\r\n</div>\r\n\r\n</body>\r\n</html>\r\n', NULL),
(12, 'cronjob/push_sync_ads.php', 'cronjob/push_sync_ads.php\r\n\r\nKode ini berfungsi untuk menyinkronkan data iklan dari tabel `advertisers_ads` ke server eksternal yang informasinya terdapat di tabel `providers_partners`. Kode pertama-tama mengambil data `api_endpoint`, `public_key`, dan `secret_key` dari tabel `providers_partners`. Kemudian, kode mengambil data iklan yang aktif dan belum kadaluarsa dari tabel `advertisers_ads`, mengemasnya dalam format JSON, dan mengirimkannya ke API server eksternal menggunakan cURL. \r\n\r\nRespons dari API diperiksa untuk memastikan keberhasilan sinkronisasi atau menangani kesalahan. Fungsi yang terlibat termasuk `PDO::query()` untuk menjalankan query SQL, `fetchAll()` untuk mengambil data, `json_encode()` untuk mengonversi array ke JSON, `curl_init()` untuk inisialisasi cURL, `curl_setopt()` untuk pengaturan cURL, `curl_exec()` untuk menjalankan cURL, dan `curl_close()` untuk menutup sesi cURL.', 'cronjob/push_sync_ads.php\r\n\r\n<!DOCTYPE html>\r\n<html lang=\"id\">\r\n<head>\r\n    <meta charset=\"UTF-8\">\r\n    <meta name=\"viewport\" content=\"width=device-width, initial-scale=1.0\">\r\n    <title>Penjelasan Fungsi Kode PHP</title>\r\n    <style>\r\n        body {\r\n            font-family: Arial, sans-serif;\r\n            background-color: #f4f4f4;\r\n            line-height: 1.6;\r\n            color: #333;\r\n            margin: 0;\r\n            padding: 20px;\r\n        }\r\n        .container {\r\n            max-width: 900px;\r\n            margin: auto;\r\n            background: #fff;\r\n            padding: 20px;\r\n            border-radius: 10px;\r\n            box-shadow: 0 0 10px rgba(0, 0, 0, 0.1);\r\n        }\r\n        h1, h2 {\r\n            color: #333;\r\n        }\r\n        h1 {\r\n            text-align: center;\r\n        }\r\n        h2 {\r\n            margin-top: 20px;\r\n            border-bottom: 2px solid #e4e4e4;\r\n            padding-bottom: 10px;\r\n        }\r\n        p {\r\n            margin: 10px 0;\r\n        }\r\n        code {\r\n            background-color: #f4f4f4;\r\n            padding: 2px 4px;\r\n            border-radius: 4px;\r\n            color: #d63384;\r\n            font-family: \"Courier New\", Courier, monospace;\r\n        }\r\n    </style>\r\n</head>\r\n<body>\r\n\r\n<div class=\"container\">\r\n    <h1>Penjelasan Fungsi Kode PHP: <code>push_sync_ads.php</code></h1>\r\n\r\n    <h2>1. Inklusi dan Inisialisasi Koneksi Database</h2>\r\n    <p>Kode ini dimulai dengan menyertakan file <code>db.php</code> yang berisi informasi koneksi ke database. Koneksi ke database dibuat menggunakan <code>PDO</code> dan pengaturan <code>ERRMODE_EXCEPTION</code> untuk menangani kesalahan. Jika terjadi kesalahan koneksi, pesan kesalahan akan ditampilkan.</p>\r\n\r\n    <h2>2. Mengambil Data dari Tabel <code>providers_partners</code></h2>\r\n    <p>Kode ini menjalankan query untuk mengambil data dari tabel <code>providers_partners</code>, termasuk <code>api_endpoint</code>, <code>public_key</code>, dan <code>secret_key</code>. Data ini digunakan untuk sinkronisasi iklan dengan server eksternal.</p>\r\n\r\n    <h2>3. Sinkronisasi Iklan</h2>\r\n    <p>Kode ini mengambil data iklan dari tabel <code>advertisers_ads</code> yang dipublikasikan dan belum kadaluarsa. Data ini dikemas dalam format JSON dan dikirim ke API endpoint yang diperoleh dari tabel <code>providers_partners</code>.</p>\r\n\r\n    <h2>4. Mengirim Data ke API Menggunakan cURL</h2>\r\n    <p>Kode ini menggunakan cURL untuk mengirim data iklan ke API. Header HTTP disertakan dengan <code>public_key</code> dan <code>secret_key</code> dari <code>providers_partners</code>. Data JSON dikirim melalui POST request, dan respons dari API diperiksa untuk memastikan keberhasilan atau menampilkan pesan kesalahan.</p>\r\n\r\n    <h2>5. Penanganan Kesalahan Database</h2>\r\n    <p>Jika terjadi kesalahan database, pesan dari <code>PDOException</code> akan ditangkap dan ditampilkan untuk identifikasi kesalahan.</p>\r\n\r\n    <h2>Ringkasan</h2>\r\n    <p>Kode ini bertujuan untuk melakukan sinkronisasi iklan dari sistem lokal ke server eksternal melalui API. Data iklan diambil dari tabel <code>advertisers_ads</code> dan disinkronkan menggunakan API endpoint dari tabel <code>providers_partners</code>. Kode ini menggunakan cURL untuk mengirim data dan memastikan sinkronisasi berjalan dengan baik.</p>\r\n\r\n</div>\r\n\r\n</body>\r\n</html>\r\n', NULL),
(13, '// cronjob/push_sync_ads_expired.php', '// cronjob/push_sync_ads_expired.php\r\n\r\nKode ini berfungsi untuk menyinkronkan data iklan yang telah kadaluarsa dalam 12 jam terakhir dari tabel `advertisers_ads` dengan server eksternal menggunakan data dari tabel `providers_partners`. Kode mengambil data `api_endpoint`, `public_key`, dan `secret_key` dari tabel `providers_partners`, kemudian mengambil iklan yang memenuhi kriteria dari `advertisers_ads`, mengemasnya dalam format JSON, dan mengirimkannya ke API menggunakan cURL. Fungsi utama yang terlibat adalah `PDO::query()` untuk menjalankan query SQL, `fetchAll()` untuk mengambil data, `json_encode()` untuk mengonversi array ke JSON, `curl_init()` untuk inisialisasi cURL, `curl_setopt()` untuk pengaturan cURL, `curl_exec()` untuk menjalankan cURL, dan `curl_close()` untuk menutup sesi cURL.', '// cronjob/push_sync_ads_expired.php\r\n\r\n<!DOCTYPE html>\r\n<html lang=\"id\">\r\n<head>\r\n    <meta charset=\"UTF-8\">\r\n    <meta name=\"viewport\" content=\"width=device-width, initial-scale=1.0\">\r\n    <title>Penjelasan Fungsi Kode PHP</title>\r\n    <style>\r\n        body {\r\n            font-family: Arial, sans-serif;\r\n            background-color: #f4f4f4;\r\n            line-height: 1.6;\r\n            color: #333;\r\n            margin: 0;\r\n            padding: 20px;\r\n        }\r\n        .container {\r\n            max-width: 900px;\r\n            margin: auto;\r\n            background: #fff;\r\n            padding: 20px;\r\n            border-radius: 10px;\r\n            box-shadow: 0 0 10px rgba(0, 0, 0, 0.1);\r\n        }\r\n        h1, h2 {\r\n            color: #333;\r\n        }\r\n        h1 {\r\n            text-align: center;\r\n        }\r\n        h2 {\r\n            margin-top: 20px;\r\n            border-bottom: 2px solid #e4e4e4;\r\n            padding-bottom: 10px;\r\n        }\r\n        p {\r\n            margin: 10px 0;\r\n        }\r\n        code {\r\n            background-color: #f4f4f4;\r\n            padding: 2px 4px;\r\n            border-radius: 4px;\r\n            color: #d63384;\r\n            font-family: \"Courier New\", Courier, monospace;\r\n        }\r\n    </style>\r\n</head>\r\n<body>\r\n\r\n<div class=\"container\">\r\n    <h1>Penjelasan Fungsi Kode PHP: <code>push_sync_ads.php</code></h1>\r\n\r\n    <h2>1. Inklusi dan Inisialisasi Koneksi Database</h2>\r\n    <p>Kode ini dimulai dengan menyertakan file <code>db.php</code> yang berisi informasi koneksi ke database. Setelah itu, koneksi ke database dibuat menggunakan <code>PDO</code> dan diatur untuk menangani kesalahan dengan mode <code>ERRMODE_EXCEPTION</code>. Jika terjadi kesalahan koneksi, pesan kesalahan akan ditampilkan.</p>\r\n\r\n    <h2>2. Mengambil Data dari Tabel <code>providers_partners</code></h2>\r\n    <p>Setelah koneksi berhasil, kode ini mengambil data dari tabel <code>providers_partners</code>, termasuk <code>api_endpoint</code>, <code>public_key</code>, dan <code>secret_key</code>. Data ini digunakan untuk melakukan sinkronisasi iklan dengan server eksternal.</p>\r\n\r\n    <h2>3. Sinkronisasi Iklan dengan API</h2>\r\n    <p>Kode ini mengambil data iklan yang kadaluarsa dalam 12 jam terakhir dari tabel <code>advertisers_ads</code>. Data ini dikemas menjadi array, kemudian dikonversi ke format JSON, dan dikirim ke API server eksternal menggunakan cURL.</p>\r\n\r\n    <h2>4. Mengirim Data ke API Menggunakan cURL</h2>\r\n    <p>Setiap iklan dikirim ke API dengan langkah-langkah berikut:</p>\r\n    <ul>\r\n        <li>Inisialisasi cURL dengan URL API yang dibangun.</li>\r\n        <li>Menentukan opsi cURL, termasuk header dengan <code>public_key</code> dan <code>secret_key</code> dari <code>providers_partners</code>.</li>\r\n        <li>Menjalankan cURL untuk mengirim data JSON ke API.</li>\r\n        <li>Memeriksa hasil cURL untuk kesalahan, dan jika tidak ada, menampilkan respons dari API.</li>\r\n    </ul>\r\n\r\n    <h2>5. Penanganan Kesalahan Database</h2>\r\n    <p>Jika terjadi kesalahan dalam koneksi atau eksekusi query, pesan kesalahan dari <code>PDOException</code> akan ditampilkan untuk identifikasi masalah.</p>\r\n\r\n    <h2>Ringkasan</h2>\r\n    <p>Kode ini bertujuan untuk menyinkronkan data iklan dari tabel <code>advertisers_ads</code> dengan server eksternal berdasarkan data dari tabel <code>providers_partners</code>. Proses ini menggunakan cURL untuk mengirim data iklan yang kadaluarsa dalam 12 jam terakhir ke API dan memastikan semua data sinkronisasi tercatat dengan baik.</p>\r\n\r\n</div>\r\n\r\n</body>\r\n</html>\r\n', '2024-08-14 11:01:41'),
(14, 'cronjob/push_sync_click_ads.php', 'cronjob/push_sync_click_ads.php\r\n\r\nKode PHP ini digunakan untuk mensinkronkan data klik iklan yang telah diaudit tetapi belum disinkronkan dengan penyedia iklan lain dalam sistem adNetwork terdesentralisasi. Kode ini melibatkan tabel providers, providers_partners, dan ad_clicks.\r\n\r\nFungsi utama yang digunakan adalah:\r\n\r\nget_providers_domain_url(): Mengambil URL domain penyedia iklan dari tabel providers.\r\ngetProvidersDetails(): Mengambil detail penyedia iklan dari tabel providers_partners.\r\ngetPendingClicks(): Mengambil data klik iklan yang belum disinkronkan dari tabel ad_clicks.\r\nupdateSyncStatus(): Memperbarui status sinkronisasi klik iklan di tabel ad_clicks.\r\nsyncClicksToApi(): Mengirim data klik iklan ke API penyedia lain.', 'cronjob/push_sync_click_ads.php\r\n<!DOCTYPE html>\r\n<html lang=\"en\">\r\n<head>\r\n    <meta charset=\"UTF-8\">\r\n    <meta name=\"viewport\" content=\"width=device-width, initial-scale=1.0\">\r\n    <title>Penjelasan Detail Kode PHP</title>\r\n    <style>\r\n        body {\r\n            font-family: Arial, sans-serif;\r\n            background-color: #f4f4f4;\r\n            line-height: 1.6;\r\n            color: #333;\r\n            margin: 0;\r\n            padding: 20px;\r\n        }\r\n        .container {\r\n            max-width: 900px;\r\n            margin: auto;\r\n            background: #fff;\r\n            padding: 20px;\r\n            border-radius: 10px;\r\n            box-shadow: 0 0 10px rgba(0, 0, 0, 0.1);\r\n        }\r\n        h1, h2 {\r\n            color: #333;\r\n        }\r\n        h1 {\r\n            text-align: center;\r\n        }\r\n        h2 {\r\n            margin-top: 20px;\r\n            border-bottom: 2px solid #e4e4e4;\r\n            padding-bottom: 10px;\r\n        }\r\n        p {\r\n            margin: 10px 0;\r\n        }\r\n        code {\r\n            background-color: #f4f4f4;\r\n            padding: 2px 4px;\r\n            border-radius: 4px;\r\n            color: #d63384;\r\n            font-family: \"Courier New\", Courier, monospace;\r\n        }\r\n    </style>\r\n</head>\r\n<body>\r\n    <div class=\"container\">\r\n        <h1>Penjelasan Detail Kode PHP</h1>\r\n        \r\n        <h2>Fungsi Utama Kode PHP</h2>\r\n        <p>Kode PHP ini merupakan skrip yang digunakan untuk mensinkronkan data klik iklan yang telah diaudit tetapi belum disinkronkan dengan penyedia iklan lain dalam sistem jaringan iklan terdesentralisasi.</p>\r\n        \r\n        <h2>Penjelasan Fungsi-Fungsi</h2>\r\n        \r\n        <h3>Fungsi <code>get_providers_domain_url()</code></h3>\r\n        <p>Fungsi ini mengambil URL domain penyedia iklan dari tabel <code>providers</code> berdasarkan ID yang diberikan. ID ini digunakan untuk menentukan penyedia yang datanya akan diambil.</p>\r\n        \r\n        <h3>Fungsi <code>getProvidersDetails()</code></h3>\r\n        <p>Fungsi ini mengambil detail penyedia iklan dari tabel <code>providers_partners</code> yang telah disetujui (ditandai dengan <code>isapproved = 1</code>). Data ini meliputi URL domain penyedia, endpoint API, dan kunci API (public_key dan secret_key) yang digunakan untuk autentikasi.</p>\r\n        \r\n        <h3>Fungsi <code>getPendingClicks()</code></h3>\r\n        <p>Fungsi ini mengambil data klik iklan dari tabel <code>ad_clicks</code> yang telah diaudit (<code>isaudit = 1</code>), belum disinkronkan (<code>is_sync = 0</code>), dan tidak ditolak (<code>is_reject = 0</code>). Hasilnya adalah daftar klik iklan yang siap disinkronkan ke penyedia lain.</p>\r\n        \r\n        <h3>Fungsi <code>updateSyncStatus()</code></h3>\r\n        <p>Fungsi ini mengupdate status sinkronisasi dari klik iklan yang telah berhasil disinkronkan ke penyedia lain. Status <code>is_sync</code> diubah menjadi <code>1</code> dan <code>syncdate</code> diatur ke tanggal saat ini.</p>\r\n        \r\n        <h3>Fungsi <code>syncClicksToApi()</code></h3>\r\n        <p>Fungsi ini mengirim data klik iklan ke endpoint API penyedia lain. Data yang dikirim mencakup URL domain penyedia saat ini dan data klik iklan. Fungsi ini menggunakan cURL untuk mengirim permintaan POST ke API penyedia lain dan memeriksa apakah sinkronisasi berhasil.</p>\r\n        \r\n        <h2>Proses Utama Kode</h2>\r\n        <p>Kode ini pertama-tama mengambil URL domain penyedia saat ini, kemudian mengambil daftar penyedia iklan yang telah disetujui. Untuk setiap penyedia yang diambil, data klik iklan yang belum disinkronkan diambil dari database dan dikirim ke API penyedia tersebut. Jika sinkronisasi berhasil, status sinkronisasi untuk setiap klik iklan diupdate di database.</p>\r\n    </div>\r\n</body>\r\n</html>\r\n\r\n', '2024-08-14 14:35:43');

-- --------------------------------------------------------

--
-- Table structure for table `hasil_belanja_influencer`
--

CREATE TABLE `hasil_belanja_influencer` (
  `id` int(11) NOT NULL,
  `order_id` char(40) NOT NULL,
  `advertiser_id` int(11) NOT NULL,
  `owner_id` int(11) NOT NULL,
  `media_id` int(11) NOT NULL,
  `media_name` varchar(255) NOT NULL,
  `media_url` text NOT NULL,
  `harga` decimal(15,2) NOT NULL,
  `quantity` int(11) NOT NULL DEFAULT 1,
  `total_price` decimal(15,2) NOT NULL,
  `checkout_date` datetime NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `influencer_media`
--

CREATE TABLE `influencer_media` (
  `id` int(11) NOT NULL,
  `owner_id` int(11) NOT NULL,
  `owner_provider_domain_url` varchar(255) NOT NULL,
  `media_id` int(11) NOT NULL,
  `media_name` char(50) NOT NULL,
  `media_url` text NOT NULL,
  `owner_media_desc` text NOT NULL,
  `rate_owner` decimal(15,2) NOT NULL,
  `rate_markup_provider` decimal(15,2) NOT NULL,
  `rate_partner` decimal(15,2) NOT NULL,
  `regdate` datetime NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `list_browser_banned`
--

CREATE TABLE `list_browser_banned` (
  `id` int(11) NOT NULL,
  `browser_agent` text NOT NULL,
  `reason` varchar(255) NOT NULL,
  `date_banned` datetime NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `list_browser_banned`
--

INSERT INTO `list_browser_banned` (`id`, `browser_agent`, `reason`, `date_banned`) VALUES
(1, 'Mozilla/5.0 (compatible; Googlebot/2.1; +http://www.google.com/bot.html)', 'Googlebot', '2024-08-13 19:25:51'),
(2, 'Mozilla/5.0 (compatible; bingbot/2.0; +http://www.bing.com/bingbot.htm)', 'Bingbot', '2024-08-13 19:25:51'),
(3, 'Mozilla/5.0 (compatible; Yahoo! Slurp; http://help.yahoo.com/help/us/ysearch/slurp)', 'Yahoo Slurp Bot', '2024-08-13 19:25:51'),
(4, 'DuckDuckBot/1.0; (+http://duckduckgo.com/duckduckbot.html)', 'DuckDuckBot', '2024-08-13 19:25:51'),
(5, 'Mozilla/5.0 (compatible; Baiduspider/2.0; +http://www.baidu.com/search/spider.html)', 'Baiduspider', '2024-08-13 19:25:51'),
(6, 'Mozilla/5.0 (Linux; Android 8.0.0; SM-G960F Build/R16NW) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/73.0.3683.75 Mobile Safari/537.36 (compatible; AhrefsBot/7.0; +http://ahrefs.com/robot/)', 'AhrefsBot', '2024-08-13 19:25:51'),
(7, 'Mozilla/5.0 (compatible; YandexBot/3.0; +http://yandex.com/bots)', 'YandexBot', '2024-08-13 19:25:51'),
(8, 'Mozilla/5.0 (compatible; Sogou web spider/4.0; +http://www.sogou.com/docs/help/webmasters.htm#07)', 'Sogou Spider', '2024-08-13 19:25:51'),
(9, 'Mozilla/5.0 (compatible; Exabot/3.0; +http://www.exabot.com/go/robot)', 'Exabot', '2024-08-13 19:25:51'),
(10, 'Mozilla/5.0 (compatible; Facebot/1.0; +http://www.facebook.com/externalhit_uatext.php)', 'Facebot', '2024-08-13 19:25:51'),
(11, 'Mozilla/5.0 (compatible; ia_archiver; +http://www.alexa.com/site/help/webmasters; crawler@alexa.com)', 'Alexa Crawler', '2024-08-13 19:25:51'),
(12, 'Mozilla/5.0 (compatible; MJ12bot/v1.4.8; http://mj12bot.com/)', 'MJ12bot', '2024-08-13 19:25:51'),
(13, 'Mozilla/5.0 (compatible; rogerbot/1.0; http://www.seomoz.org/dp/rogerbot)', 'Rogerbot', '2024-08-13 19:25:51'),
(14, 'Mozilla/5.0 (compatible; SemrushBot/2~bl; +http://www.semrush.com/bot.html)', 'SemrushBot', '2024-08-13 19:25:51'),
(15, 'Mozilla/5.0 (compatible; DotBot/1.1; http://www.opensiteexplorer.org/dotbot)', 'DotBot', '2024-08-13 19:25:51'),
(16, 'Mozilla/5.0 (Linux; Android 7.1.1; Nexus 6P Build/N6F26Q) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/60.0.3112.107 Mobile Safari/537.36 (compatible; PetalBot; +https://webmaster.petalsearch.com/site/petalbot)', 'PetalBot', '2024-08-13 19:25:51'),
(17, 'Mozilla/5.0 (compatible; SeznamBot/3.2-test1; +http://napoveda.seznam.cz/cz/seznambot-intro/)', 'SeznamBot', '2024-08-13 19:25:51'),
(18, 'Mozilla/5.0 (compatible; AdsBot-Google; +http://www.google.com/adsbot.html)', 'AdsBot-Google', '2024-08-13 19:25:51'),
(19, 'Mozilla/5.0 (Linux; Android 6.0.1; Nexus 5X Build/MMB29P) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/73.0.3683.90 Mobile Safari/537.36 (compatible; BLEXBot/1.0; +http://webmeup-crawler.com/)', 'BLEXBot', '2024-08-13 19:25:51'),
(20, 'Mozilla/5.0 (compatible; MegaIndex.ru/2.0; +http://megaindex.com/crawler)', 'MegaIndex.ru', '2024-08-13 19:25:51'),
(21, 'Mozilla/5.0 (compatible; NaverBot/1.0; +http://naver.me/bot)', 'NaverBot', '2024-08-13 19:25:51'),
(22, 'Mozilla/5.0 (compatible; NutchCVS/2.0; http://lucene.apache.org/nutch/bot.html)', 'Nutch Bot', '2024-08-13 19:25:51'),
(23, 'Mozilla/5.0 (compatible; Seekport Crawler; http://seekport.com/)', 'Seekport Crawler', '2024-08-13 19:25:51'),
(24, 'Mozilla/5.0 (compatible; 360Spider; +http://webscan.360.cn)', '360Spider', '2024-08-13 19:25:51'),
(25, 'Mozilla/5.0 (compatible; DeuSu/0.1; +http://deusu.org/)', 'DeuSu', '2024-08-13 19:25:51'),
(26, 'Mozilla/5.0 (compatible; archive.org_bot +http://www.archive.org/details/archive.org_bot)', 'Archive.org Bot', '2024-08-13 19:25:51'),
(27, 'Mozilla/5.0 (compatible; istellabot/1.0; +http://www.t-online.de/istellabot.html)', 'IstellaBot', '2024-08-13 19:25:51'),
(28, 'Mozilla/5.0 (compatible; XoviBot/2.0; +http://www.xovi.com/)', 'XoviBot', '2024-08-13 19:25:51'),
(29, 'Mozilla/5.0 (compatible; woriobot +https://worio.com/)', 'WorioBot', '2024-08-13 19:25:51'),
(30, 'Mozilla/5.0 (compatible; UptimeRobot/2.0; http://www.uptimerobot.com/)', 'UptimeRobot', '2024-08-13 19:25:51'),
(31, 'Mozilla/5.0 (compatible; VoilaBot BETA 1.2; http://voila.com/)', 'VoilaBot', '2024-08-13 19:25:51'),
(32, 'Mozilla/5.0 (compatible; YodaoBot/1.0; http://www.yodao.com/help/webmaster.html)', 'YodaoBot', '2024-08-13 19:25:51'),
(33, 'Mozilla/5.0 (compatible; adbeat.com crawler; +http://www.adbeat.com)', 'Adbeat Crawler', '2024-08-13 19:25:51'),
(34, 'Mozilla/5.0 (compatible; LinkpadBot/1.0; +http://www.linkpad.ru)', 'LinkpadBot', '2024-08-13 19:25:51'),
(35, 'Mozilla/5.0 (compatible; TurnitinBot/3.0; +http://www.turnitin.com/robot/crawlerinfo.html)', 'TurnitinBot', '2024-08-13 19:25:51'),
(36, 'Mozilla/5.0 (compatible; magpie-crawler/1.1; +http://www.magpie-crawler.com/)', 'Magpie Crawler', '2024-08-13 19:25:51'),
(37, 'Mozilla/5.0 (compatible; ScoutJet; +http://www.scoutjet.com/)', 'ScoutJet', '2024-08-13 19:25:51'),
(38, 'Mozilla/5.0 (compatible; CCBot/2.0; +http://commoncrawl.org/faq/)', 'Common Crawl Bot', '2024-08-13 19:25:51'),
(39, 'Mozilla/5.0 (compatible; YisouSpider; +http://help.yisou.com)', 'YisouSpider', '2024-08-13 19:25:51'),
(40, 'Mozilla/5.0 (compatible; Ezooms/1.0; +http://www.ezanga.com/crawl.php)', 'Ezooms Bot', '2024-08-13 19:25:51'),
(41, 'Mozilla/5.0 (compatible; FemtosearchBot/1.0; +http://www.femtosearch.com/)', 'FemtosearchBot', '2024-08-13 19:25:51'),
(42, 'Mozilla/5.0 (compatible; Barkrowler/0.9; +http://www.exensa.com/bot.html)', 'Barkrowler', '2024-08-13 19:25:51'),
(43, 'Mozilla/5.0 (compatible; Twitterbot/1.0)', 'Twitterbot', '2024-08-13 19:25:51'),
(44, 'Mozilla/5.0 (compatible; Applebot/1.0; +http://www.apple.com/go/applebot)', 'Applebot', '2024-08-13 19:25:51'),
(45, 'Mozilla/5.0 (compatible; LinkedInBot/1.0)', 'LinkedInBot', '2024-08-13 19:25:51'),
(46, 'Mozilla/5.0 (Linux; Android 4.4.2; Nexus 4 Build/KOT49H) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/40.0.2214.89 Mobile Safari/537.36 (compatible; Updownerbot/1.0; +http://www.updowner.com/bot.html)', 'Updownerbot', '2024-08-13 19:25:51'),
(47, 'Mozilla/5.0 (compatible; PiplBot; +http://www.pipl.com/bot/)', 'PiplBot', '2024-08-13 19:25:51');

-- --------------------------------------------------------

--
-- Table structure for table `list_ip_banned`
--

CREATE TABLE `list_ip_banned` (
  `id` int(11) NOT NULL,
  `ip_address` varchar(45) NOT NULL,
  `reason` varchar(255) NOT NULL,
  `date_banned` datetime NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `list_ip_banned`
--

INSERT INTO `list_ip_banned` (`id`, `ip_address`, `reason`, `date_banned`) VALUES
(1, '66.249.66.1', 'Googlebot', '2024-08-13 19:23:01'),
(2, '207.46.13.5', 'Bingbot', '2024-08-13 19:23:01'),
(3, '74.6.231.21', 'Yahoo Slurp Bot', '2024-08-13 19:23:01'),
(4, '40.77.167.5', 'Bingbot', '2024-08-13 19:23:01'),
(5, '180.76.15.5', 'Baiduspider', '2024-08-13 19:23:01'),
(6, '54.36.148.0', 'DuckDuckBot', '2024-08-13 19:23:01'),
(7, '192.168.1.100', 'Brute force attack', '2024-08-13 20:15:32'),
(8, '10.0.0.5', 'SQL injection attempt', '2024-08-13 21:30:45'),
(9, '172.16.0.10', 'DDoS attack', '2024-08-13 22:45:18'),
(10, '203.0.113.42', 'Spam bot', '2024-08-14 01:12:03'),
(11, '198.51.100.75', 'Content scraping', '2024-08-14 03:27:56'),
(12, '45.33.22.11', 'Vulnerability scanning', '2024-08-14 05:41:39'),
(13, '91.234.56.78', 'Unauthorized access attempt', '2024-08-14 07:55:22'),
(14, '104.198.14.52', 'Comment spam', '2024-08-14 10:09:05'),
(15, '185.73.144.217', 'Malware distribution', '2024-08-14 12:22:48'),
(16, '23.45.67.89', 'Phishing attempt', '2024-08-14 14:36:31'),
(17, '79.125.0.21', 'Cross-site scripting (XSS)', '2024-08-14 16:50:14'),
(18, '150.10.20.30', 'Directory traversal attack', '2024-08-14 19:03:57'),
(19, '209.85.128.12', 'Email harvesting', '2024-08-14 21:17:40'),
(20, '64.233.160.0', 'Excessive API requests', '2024-08-14 23:31:23'),
(21, '157.240.2.35', 'Session hijacking attempt', '2024-08-15 01:45:06'),
(22, '93.184.216.34', 'XML external entity (XXE) attack', '2024-08-15 03:58:49'),
(23, '000', 'DNS amplification attack', '2024-08-15 06:12:32'),
(24, '130.211.1.42', 'Remote code execution attempt', '2024-08-15 08:26:15'),
(25, '35.190.247.0', 'Server-side request forgery (SSRF)', '2024-08-15 10:39:58'),
(26, '52.8.191.254', 'Credential stuffing attack', '2024-08-15 12:53:41'),
(27, '13.32.99.166', 'Man-in-the-middle attack', '2024-08-15 15:07:24'),
(28, '104.16.125.175', 'Web application firewall evasion', '2024-08-15 17:21:07'),
(29, '192.0.2.1', 'Zero-day exploit attempt', '2024-08-15 19:34:50'),
(30, '198.35.26.96', 'Tor exit node', '2024-08-15 21:48:33'),
(31, '2001:db8::1', 'IPv6 tunnel broker abuse', '2024-08-15 23:02:16'),
(32, '169.254.169.254', 'Cloud metadata API abuse', '2024-08-16 01:16:59'),
(33, '100.64.0.1', 'CGN IP range abuse', '2024-08-16 03:30:42'),
(34, '224.0.0.1', 'Multicast address abuse', '2024-08-16 05:44:25'),
(35, '239.255.255.250', 'SSDP reflection attack', '2024-08-16 07:58:08'),
(36, '192.88.99.1', '6to4 relay abuse', '2024-08-16 10:11:51'),
(37, '192.168.0.1', 'Router exploit attempt', '2024-08-16 12:25:34'),
(38, '10.1.1.1', 'Internal network scan', '2024-08-16 14:39:17'),
(39, '172.31.255.255', 'AWS metadata service abuse', '2024-08-16 16:53:00'),
(40, '127.0.0.10', 'Localhost abuse attempt', '2024-08-16 19:06:43'),
(41, '0.0.0.0', 'Malformed packet injection', '2024-08-16 21:20:26'),
(42, '255.255.255.255', 'Broadcast address abuse', '2024-08-16 23:34:09'),
(43, '192.168.1.1', 'Default gateway attack', '2024-08-17 01:47:52'),
(44, '8.8.4.4', 'DNS server abuse', '2024-08-17 04:01:35'),
(45, '192.168.2.1', 'Home router exploit', '2024-08-17 06:15:18'),
(46, '169.254.1.1', 'Link-local address abuse', '2024-08-17 08:29:01'),
(47, '198.18.0.1', 'Benchmark testing network abuse', '2024-08-17 10:42:44'),
(48, '192.168.3.1', 'IoT device exploit', '2024-08-17 12:56:27'),
(49, '172.17.0.1', 'Docker bridge network abuse', '2024-08-17 15:10:10'),
(50, '100.100.100.100', 'Alibaba Cloud metadata API abuse', '2024-08-17 17:23:53'),
(51, '192.168.4.1', 'Smart home device exploit', '2024-08-17 19:37:36'),
(52, '10.0.0.1', 'NAT gateway abuse', '2024-08-17 21:51:19'),
(53, '192.168.5.1', 'Network printer exploit', '2024-08-18 00:05:02'),
(54, '172.18.0.1', 'Kubernetes cluster abuse', '2024-08-18 02:18:45'),
(55, '192.168.6.1', 'IP camera exploit', '2024-08-18 04:32:28'),
(56, '10.10.34.0', 'VPN endpoint abuse', '2024-08-18 06:46:11'),
(57, '192.168.7.1', 'NAS device exploit', '2024-08-18 08:59:54'),
(58, '172.19.0.1', 'Virtual machine network abuse', '2024-08-18 11:13:37'),
(59, '192.168.8.1', 'Smart TV exploit', '2024-08-18 13:27:20'),
(60, '10.20.30.40', 'Load balancer abuse', '2024-08-18 15:41:03'),
(61, '192.168.9.1', 'Game console exploit', '2024-08-18 17:54:46'),
(62, '172.20.0.1', 'Container orchestration abuse', '2024-08-18 20:08:29'),
(63, '192.168.10.1', 'Voice assistant exploit', '2024-08-18 22:22:12'),
(64, '10.30.40.50', 'Proxy server abuse', '2024-08-19 00:35:55'),
(65, '192.168.11.1', 'Smart thermostat exploit', '2024-08-19 02:49:38'),
(66, '172.21.0.1', 'Microservices network abuse', '2024-08-19 05:03:21'),
(67, '192.168.12.1', 'Smart lock exploit', '2024-08-19 07:17:04'),
(68, '10.40.50.60', 'CDN edge node abuse', '2024-08-19 09:30:47'),
(69, '192.168.13.1', 'Smart refrigerator exploit', '2024-08-19 11:44:30'),
(70, '172.22.0.1', 'Serverless function abuse', '2024-08-19 13:58:13'),
(71, '192.168.14.1', 'Smart doorbell exploit', '2024-08-19 16:11:56'),
(72, '10.50.60.70', 'Database cluster abuse', '2024-08-19 18:25:39'),
(73, '192.168.15.1', 'Smart light bulb exploit', '2024-08-19 20:39:22'),
(74, '172.23.0.1', 'Edge computing node abuse', '2024-08-19 22:53:05'),
(75, '192.168.16.1', 'Smart speaker exploit', '2024-08-20 01:06:48'),
(76, '10.60.70.80', 'Caching server abuse', '2024-08-20 03:20:31'),
(77, '192.168.17.1', 'Smart smoke detector exploit', '2024-08-20 05:34:14'),
(78, '172.24.0.1', 'NFV infrastructure abuse', '2024-08-20 07:47:57'),
(79, '192.168.18.1', 'Smart window blinds exploit', '2024-08-20 10:01:40'),
(80, '10.70.80.90', 'Message queue abuse', '2024-08-20 12:15:23'),
(81, '192.168.19.1', 'Smart garage door exploit', '2024-08-20 14:29:06'),
(82, '172.25.0.1', 'SDN controller abuse', '2024-08-20 16:42:49'),
(83, '192.168.20.1', 'Smart doormat exploit', '2024-08-20 18:56:32'),
(84, '10.80.90.100', 'Log aggregation server abuse', '2024-08-20 21:10:15'),
(85, '192.168.21.1', 'Smart trash can exploit', '2024-08-20 23:23:58'),
(86, '172.26.0.1', '5G network slice abuse', '2024-08-21 01:37:41'),
(87, '192.168.22.1', 'Smart mirror exploit', '2024-08-21 03:51:24'),
(88, '10.90.100.110', 'Time server abuse', '2024-08-21 06:05:07'),
(89, '192.168.23.1', 'Smart toaster exploit', '2024-08-21 08:18:50'),
(90, '172.27.0.1', 'Quantum network node abuse', '2024-08-21 10:32:33'),
(91, '192.168.24.1', 'Smart vacuum cleaner exploit', '2024-08-21 12:46:16'),
(92, '10.100.110.120', 'Monitoring system abuse', '2024-08-21 14:59:59'),
(93, '192.168.25.1', 'Smart pet feeder exploit', '2024-08-21 17:13:42'),
(94, '172.28.0.1', 'Blockchain node abuse', '2024-08-21 19:27:25'),
(95, '192.168.26.1', 'Smart air purifier exploit', '2024-08-21 21:41:08'),
(96, '10.110.120.130', 'CI/CD pipeline abuse', '2024-08-21 23:54:51'),
(97, '192.168.27.1', 'Smart coffee maker exploit', '2024-08-22 02:08:34'),
(98, '172.29.0.1', 'ML model serving abuse', '2024-08-22 04:22:17'),
(99, '192.168.28.1', 'Smart water meter exploit', '2024-08-22 06:36:00'),
(100, '10.120.130.140', 'Backup server abuse', '2024-08-22 08:49:43');

-- --------------------------------------------------------

--
-- Table structure for table `log_payment_order_influencer`
--

CREATE TABLE `log_payment_order_influencer` (
  `id` int(11) NOT NULL,
  `advertiser_id` int(11) NOT NULL,
  `order_id` char(40) NOT NULL,
  `payment_message` text NOT NULL,
  `payment_date` datetime NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `mapping_advertisers_ads_publishers_site`
--

CREATE TABLE `mapping_advertisers_ads_publishers_site` (
  `id` bigint(20) NOT NULL,
  `rate_text_ads` decimal(15,2) NOT NULL,
  `budget_per_click_textads` decimal(15,2) DEFAULT NULL,
  `local_ads_id` int(11) NOT NULL,
  `publishers_site_local_id` int(11) NOT NULL,
  `owner_advertisers_id` int(11) NOT NULL,
  `title_ads` text NOT NULL,
  `description_ads` text NOT NULL,
  `landingpage_ads` text NOT NULL,
  `publishers_local_id` int(11) NOT NULL,
  `site_name` varchar(255) NOT NULL,
  `site_domain` varchar(255) NOT NULL,
  `site_desc` text NOT NULL,
  `image_url` text DEFAULT NULL,
  `is_published` tinyint(1) NOT NULL DEFAULT 1,
  `is_paused` tinyint(1) NOT NULL DEFAULT 0,
  `is_expired` tinyint(1) NOT NULL DEFAULT 0,
  `is_approved_by_publisher` tinyint(1) NOT NULL DEFAULT 0,
  `is_approved_by_advertiser` tinyint(1) NOT NULL DEFAULT 0,
  `published_date` date DEFAULT NULL,
  `paused_date` datetime DEFAULT NULL,
  `expired_date` datetime DEFAULT NULL,
  `approval_date_publisher` datetime DEFAULT NULL,
  `approval_date_advertiser` datetime DEFAULT NULL,
  `pubs_providers_name` varchar(255) DEFAULT NULL,
  `pubs_providers_domain_url` varchar(255) DEFAULT NULL,
  `ads_providers_name` varchar(255) NOT NULL,
  `ads_providers_domain_url` varchar(255) NOT NULL,
  `reasons_rejected_by_advertiser` text DEFAULT NULL,
  `reasons_rejected_by_publisher` text DEFAULT NULL,
  `revenue_publishers` int(11) DEFAULT NULL,
  `last_updated` timestamp NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `mapping_advertisers_ads_publishers_site_from_partners`
--

CREATE TABLE `mapping_advertisers_ads_publishers_site_from_partners` (
  `id` bigint(20) NOT NULL,
  `local_mapping_id` bigint(20) NOT NULL,
  `rate_text_ads` int(11) NOT NULL,
  `budget_per_click_textads` int(11) DEFAULT NULL,
  `local_ads_id` int(11) NOT NULL,
  `publishers_site_local_id` int(11) NOT NULL,
  `owner_advertisers_id` int(11) NOT NULL,
  `title_ads` text NOT NULL,
  `description_ads` text NOT NULL,
  `landingpage_ads` text NOT NULL,
  `publishers_local_id` int(11) NOT NULL,
  `site_name` varchar(255) NOT NULL,
  `site_domain` varchar(255) NOT NULL,
  `site_desc` text NOT NULL,
  `image_url` text DEFAULT NULL,
  `is_published` tinyint(1) NOT NULL DEFAULT 1,
  `is_paused` tinyint(1) NOT NULL DEFAULT 0,
  `is_expired` tinyint(1) NOT NULL DEFAULT 0,
  `is_approved_by_publisher` tinyint(1) NOT NULL DEFAULT 0,
  `is_approved_by_advertiser` tinyint(1) NOT NULL DEFAULT 0,
  `published_date` date DEFAULT NULL,
  `paused_date` datetime DEFAULT NULL,
  `expired_date` datetime DEFAULT NULL,
  `approval_date_publisher` datetime DEFAULT NULL,
  `approval_date_advertiser` datetime DEFAULT NULL,
  `pubs_providers_name` varchar(255) DEFAULT NULL,
  `pubs_providers_domain_url` varchar(255) DEFAULT NULL,
  `ads_providers_name` varchar(255) NOT NULL,
  `ads_providers_domain_url` varchar(255) NOT NULL,
  `reasons_rejected_by_advertiser` text DEFAULT NULL,
  `reasons_rejected_by_publisher` text DEFAULT NULL,
  `revenue_publishers` int(11) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `media`
--

CREATE TABLE `media` (
  `id` int(11) NOT NULL,
  `media` varchar(255) NOT NULL,
  `desc` text NOT NULL,
  `icon` varchar(255) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `media`
--

INSERT INTO `media` (`id`, `media`, `desc`, `icon`) VALUES
(1, 'Blog', 'Posting 500 kata tentang produk barang / jasa milik brand', ''),
(2, 'Instagram', 'Posting konten text dan video/photo  produk barang / jasa milik brand. Durasi 1 menit', ''),
(3, 'Tiktok', 'Posting konten text dan video/photo  produk barang / jasa milik brand. Durasi 1 menit', ''),
(4, 'X.com', 'Posting konten text dan video/photo  produk barang / jasa milik brand. Durasi 1 menit', ''),
(5, 'Youtube', 'Mention produk barang/jasa suatu brand pada video youtube selama 1 menit, berisikan penjelasan produk tersebut', ''),
(6, 'Threads', 'Posting konten text dan video/photo  produk barang / jasa milik brand. Durasi 1 menit', ''),
(7, 'Facebook', 'Posting konten text dan video/photo  produk barang / jasa milik brand. Durasi 1 menit', ''),
(8, 'Linkedin', 'Posting konten text dan video/photo  produk barang / jasa milik brand. Durasi 1 menit', '');

-- --------------------------------------------------------

--
-- Table structure for table `msadmin`
--

CREATE TABLE `msadmin` (
  `id` smallint(6) NOT NULL,
  `loginemail` varchar(255) NOT NULL,
  `passwords` varchar(255) NOT NULL,
  `whatsapp` varchar(255) NOT NULL,
  `last_login` datetime DEFAULT NULL,
  `last_login_attempt` datetime DEFAULT NULL,
  `pass_phrase` varchar(255) DEFAULT NULL,
  `number_last_login_attempt` int(11) NOT NULL DEFAULT 0,
  `forgot_password_key` varchar(255) NOT NULL,
  `realname` varchar(255) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `msadmin`
--

INSERT INTO `msadmin` (`id`, `loginemail`, `passwords`, `whatsapp`, `last_login`, `last_login_attempt`, `pass_phrase`, `number_last_login_attempt`, `forgot_password_key`, `realname`) VALUES
(1, 'kukuhtw@gmail.com', '$2y$10$ZgdQMygyavX9rdpNUXOn/e18WVHhm2MtA38ylb/URUA7aHliLU93C', '', '2024-11-12 15:28:47', NULL, NULL, 0, '', NULL);

-- --------------------------------------------------------

--
-- Table structure for table `msusers`
--

CREATE TABLE `msusers` (
  `id` int(11) NOT NULL,
  `loginemail` varchar(255) NOT NULL,
  `passwords` varchar(255) NOT NULL,
  `whatsapp` varchar(20) NOT NULL,
  `last_login` datetime DEFAULT NULL,
  `last_login_attempt` datetime DEFAULT NULL,
  `pass_phrase` varchar(255) DEFAULT NULL,
  `number_last_login_attempt` int(11) NOT NULL DEFAULT 0,
  `forgot_password_key` varchar(255) NOT NULL,
  `realname` varchar(255) DEFAULT NULL,
  `bank` varchar(255) DEFAULT NULL,
  `account_number` varchar(50) DEFAULT NULL,
  `account_name` varchar(100) DEFAULT NULL,
  `regdate` datetime NOT NULL,
  `current_revenue` decimal(15,2) NOT NULL DEFAULT 0.00,
  `local_revenue_paid` decimal(15,2) NOT NULL DEFAULT 0.00,
  `local_revenue_unpaid` decimal(15,2) NOT NULL DEFAULT 0.00,
  `current_revenue_from_partner` decimal(15,2) NOT NULL DEFAULT 0.00,
  `partner_revenue_paid` decimal(15,2) DEFAULT 0.00,
  `partner_revenue_unpaid` decimal(15,2) DEFAULT 0.00,
  `total_current_revenue` decimal(15,2) NOT NULL DEFAULT 0.00,
  `last_updated_revenue` datetime DEFAULT NULL,
  `current_spending` decimal(15,2) NOT NULL DEFAULT 0.00,
  `current_spending_from_partner` decimal(15,2) NOT NULL DEFAULT 0.00,
  `total_current_spending` decimal(15,2) NOT NULL DEFAULT 0.00,
  `last_updated_spending` datetime DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `payment_local_pubs`
--

CREATE TABLE `payment_local_pubs` (
  `id` int(11) NOT NULL,
  `email_pubs` varchar(255) DEFAULT NULL,
  `nominal` decimal(15,2) DEFAULT NULL,
  `payment_description` text DEFAULT NULL,
  `payment_date` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `payment_partner_providers`
--

CREATE TABLE `payment_partner_providers` (
  `id` int(11) NOT NULL,
  `partner_providers_domain_url` varchar(255) NOT NULL,
  `email_provider` varchar(255) DEFAULT NULL,
  `nominal` decimal(15,2) DEFAULT NULL,
  `payment_description` text DEFAULT NULL,
  `payment_date` datetime DEFAULT NULL,
  `payment_by` varchar(255) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `payment_partner_providers_sync`
--

CREATE TABLE `payment_partner_providers_sync` (
  `id` int(11) NOT NULL,
  `local_id` int(11) NOT NULL,
  `partner_providers_domain_url` varchar(255) NOT NULL,
  `email_provider` varchar(255) DEFAULT NULL,
  `nominal` decimal(15,2) DEFAULT NULL,
  `payment_description` text DEFAULT NULL,
  `payment_date` datetime DEFAULT NULL,
  `payment_by` varchar(255) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `payment_partner_pubs`
--

CREATE TABLE `payment_partner_pubs` (
  `id` int(11) NOT NULL,
  `publisher_local_id` int(11) NOT NULL,
  `pubs_providers_domain_url` varchar(255) NOT NULL,
  `email_pubs` varchar(255) DEFAULT NULL,
  `nominal` decimal(15,2) DEFAULT NULL,
  `payment_description` text DEFAULT NULL,
  `payment_date` timestamp NOT NULL DEFAULT current_timestamp(),
  `payment_by` varchar(255) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `payment_partner_pubs_sync`
--

CREATE TABLE `payment_partner_pubs_sync` (
  `id` int(11) NOT NULL,
  `local_id` int(11) NOT NULL,
  `publisher_local_id` int(11) NOT NULL,
  `pubs_providers_domain_url` varchar(255) NOT NULL,
  `email_pubs` varchar(255) DEFAULT NULL,
  `nominal` decimal(15,2) DEFAULT NULL,
  `payment_description` text DEFAULT NULL,
  `payment_date` timestamp NOT NULL DEFAULT current_timestamp(),
  `payment_by` varchar(255) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `providers`
--

CREATE TABLE `providers` (
  `id` smallint(6) NOT NULL,
  `providers_code` varchar(255) NOT NULL,
  `providers_name` varchar(255) NOT NULL,
  `providers_domain_url` varchar(255) NOT NULL,
  `hash_key` varchar(255) NOT NULL,
  `secret_key` varchar(255) NOT NULL,
  `api_endpoint` varchar(255) NOT NULL,
  `my_revenue` decimal(15,2) NOT NULL DEFAULT 0.00,
  `my_revenue_paid` decimal(15,2) NOT NULL DEFAULT 0.00,
  `my_revenue_unpaid` decimal(15,2) NOT NULL DEFAULT 0.00,
  `regdate` datetime NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `providers`
--

INSERT INTO `providers` (`id`, `providers_code`, `providers_name`, `providers_domain_url`, `hash_key`, `secret_key`, `api_endpoint`, `my_revenue`, `my_revenue_paid`, `my_revenue_unpaid`, `regdate`) VALUES
(1, 'DBD76415', 'My Adnetwork 2', 'https://Myadnetwork2.com', '1740f1d4a00d945d54505f02b145b03c', '3837b250ced56bf7448db0197b4ed6ddbb80d3dca504beb38e808c04b8785435', 'https://Myadnetwork2.com/API', 0.00, 0.00, 0.00, '2024-11-12 16:05:59');

-- --------------------------------------------------------

--
-- Table structure for table `providers_contact_person`
--

CREATE TABLE `providers_contact_person` (
  `id` smallint(6) NOT NULL,
  `providers_domain_url` varchar(255) NOT NULL,
  `email` varchar(255) DEFAULT NULL,
  `whatsapp` varchar(25) DEFAULT NULL,
  `account_name` varchar(255) DEFAULT NULL,
  `account_bank` varchar(255) DEFAULT NULL,
  `account_number` varchar(255) DEFAULT NULL,
  `last_update` datetime DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `providers_contact_person_sync`
--

CREATE TABLE `providers_contact_person_sync` (
  `id` smallint(6) NOT NULL,
  `providers_domain_url` varchar(255) NOT NULL,
  `email` varchar(255) DEFAULT NULL,
  `whatsapp` varchar(25) DEFAULT NULL,
  `account_name` varchar(255) DEFAULT NULL,
  `account_bank` varchar(255) DEFAULT NULL,
  `account_number` varchar(255) DEFAULT NULL,
  `last_update` datetime DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `providers_partners`
--

CREATE TABLE `providers_partners` (
  `id` int(11) NOT NULL,
  `signature` text NOT NULL,
  `providers_name` varchar(255) NOT NULL,
  `providers_domain_url` varchar(255) NOT NULL,
  `target_providers_domain_url` varchar(255) NOT NULL,
  `api_endpoint` varchar(255) NOT NULL,
  `requestdate` datetime NOT NULL,
  `time_epoch_requestdate` bigint(20) NOT NULL,
  `is_followup` tinyint(1) DEFAULT 0,
  `public_key` varchar(255) NOT NULL,
  `secret_key` varchar(255) NOT NULL,
  `isapproved` tinyint(1) NOT NULL DEFAULT 0,
  `approved_date` datetime NOT NULL,
  `time_epoch_approveddate` bigint(20) NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `is_hold` tinyint(1) NOT NULL DEFAULT 0,
  `hold_date` datetime NOT NULL,
  `ipaddress` varchar(255) NOT NULL,
  `source_url` text NOT NULL,
  `browser_agent` text NOT NULL,
  `partner_revenue` decimal(15,2) DEFAULT NULL,
  `partner_revenue_paid` decimal(15,2) DEFAULT NULL,
  `partner_revenue_unpaid` decimal(15,2) DEFAULT NULL,
  `last_updated_revenue` datetime NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `providers_request`
--

CREATE TABLE `providers_request` (
  `id` int(11) NOT NULL,
  `request_from` varchar(255) NOT NULL,
  `signature` varchar(255) NOT NULL,
  `providers_domain_url` varchar(255) NOT NULL,
  `target_providers_domain_url` varchar(255) NOT NULL,
  `api_endpoint` varchar(255) NOT NULL,
  `request_date` datetime NOT NULL,
  `is_followup` tinyint(1) NOT NULL DEFAULT 0,
  `time_epoch_requestdate` bigint(20) NOT NULL,
  `ipaddress` varchar(255) DEFAULT NULL,
  `source_url` text DEFAULT NULL,
  `browser_agent` text NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `publishers_site`
--

CREATE TABLE `publishers_site` (
  `id` int(11) NOT NULL,
  `providers_name` varchar(255) NOT NULL,
  `providers_domain_url` varchar(255) NOT NULL,
  `publishers_local_id` int(11) NOT NULL,
  `site_name` varchar(255) NOT NULL,
  `site_domain` varchar(255) NOT NULL,
  `site_desc` text NOT NULL,
  `alternate_code` text DEFAULT NULL,
  `public_key` varchar(255) NOT NULL,
  `secret_key` varchar(255) NOT NULL,
  `rate_text_ads` decimal(15,2) NOT NULL DEFAULT 50.00,
  `advertiser_allowed` text DEFAULT NULL,
  `advertiser_rejected` text DEFAULT NULL,
  `regdate` datetime NOT NULL,
  `current_site_revenue` decimal(15,2) DEFAULT NULL,
  `current_site_revenue_from_partner` decimal(15,2) DEFAULT NULL,
  `isbanned` tinyint(1) NOT NULL DEFAULT 0,
  `banned_date` datetime DEFAULT NULL,
  `banned_reason` text NOT NULL,
  `ulasan` text DEFAULT NULL,
  `last_updated` datetime DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `publishers_site_partners`
--

CREATE TABLE `publishers_site_partners` (
  `id` int(11) NOT NULL,
  `local_id` int(11) NOT NULL,
  `providers_name` varchar(255) NOT NULL,
  `providers_domain_url` varchar(255) NOT NULL,
  `publishers_local_id` int(11) NOT NULL,
  `site_name` varchar(255) NOT NULL,
  `site_domain` varchar(255) NOT NULL,
  `site_desc` text NOT NULL,
  `rate_text_ads` decimal(15,2) UNSIGNED DEFAULT 10.00,
  `advertiser_allowed` text DEFAULT NULL,
  `advertiser_rejected` text DEFAULT NULL,
  `regdate` datetime NOT NULL,
  `isbanned` tinyint(1) NOT NULL DEFAULT 0,
  `banned_date` datetime DEFAULT NULL,
  `banned_reason` text NOT NULL,
  `last_updated` datetime DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `publisher_partner`
--

CREATE TABLE `publisher_partner` (
  `id` int(11) NOT NULL,
  `loginemail` varchar(255) NOT NULL,
  `whatsapp` varchar(20) DEFAULT NULL,
  `bank` varchar(255) DEFAULT NULL,
  `account_name` varchar(255) DEFAULT NULL,
  `account_number` varchar(55) DEFAULT NULL,
  `pubs_providers_domain_url` varchar(255) NOT NULL,
  `publishers_local_id` int(11) NOT NULL,
  `revenue_total` decimal(15,2) NOT NULL DEFAULT 0.00,
  `revenue_paid` decimal(15,2) NOT NULL DEFAULT 0.00,
  `revenue_unpaid` decimal(15,2) NOT NULL DEFAULT 0.00,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `rekap_harian`
--

CREATE TABLE `rekap_harian` (
  `id` bigint(20) NOT NULL,
  `tanggal_klik` date DEFAULT NULL,
  `local_ads_id` int(11) DEFAULT NULL,
  `ads_providers_domain_url` varchar(255) DEFAULT NULL,
  `sumber_data` varchar(20) DEFAULT NULL,
  `sumber_data_url` varchar(255) DEFAULT NULL,
  `revenue_publishers` int(11) DEFAULT NULL,
  `revenue_adnetwork_local` int(11) DEFAULT NULL,
  `revenue_adnetwork_partner` int(11) DEFAULT NULL,
  `total_spending` int(11) DEFAULT NULL,
  `jumlah_klik` int(11) DEFAULT NULL,
  `title_ads` text DEFAULT NULL,
  `landingpage_ads` text DEFAULT NULL,
  `budget_allocation` int(11) DEFAULT NULL,
  `rekap_date` datetime DEFAULT NULL,
  `last_updated` timestamp NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `rekap_harian_provider_partner`
--

CREATE TABLE `rekap_harian_provider_partner` (
  `id` bigint(20) NOT NULL,
  `rekap_date` date NOT NULL,
  `ads_providers_domain_url` varchar(255) NOT NULL,
  `total_clicks` int(11) NOT NULL DEFAULT 0,
  `total_revenue_partner` decimal(15,2) NOT NULL DEFAULT 0.00,
  `created_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `rekap_harian_publishers`
--

CREATE TABLE `rekap_harian_publishers` (
  `rekap_id` bigint(20) NOT NULL,
  `rekap_date` date NOT NULL,
  `pub_id` int(11) NOT NULL,
  `pubs_providers_domain_url` varchar(255) NOT NULL,
  `ads_providers_domain_url` varchar(255) NOT NULL,
  `total_revenue_publishers` int(11) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `rekap_publisher_revenue_harian_partner`
--

CREATE TABLE `rekap_publisher_revenue_harian_partner` (
  `id` int(11) NOT NULL,
  `pub_id` int(11) NOT NULL,
  `site_name` varchar(255) DEFAULT NULL,
  `site_domain` varchar(255) DEFAULT NULL,
  `pubs_providers_domain_url` varchar(255) NOT NULL,
  `date_click` date NOT NULL,
  `total_revenue_publishers` int(11) NOT NULL DEFAULT 0,
  `total_clicks` int(11) NOT NULL DEFAULT 0
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `rekap_pubs_revenue`
--

CREATE TABLE `rekap_pubs_revenue` (
  `pub_id` int(11) NOT NULL,
  `pubs_providers_domain_url` varchar(255) NOT NULL,
  `partner_revenue` int(11) DEFAULT 0,
  `local_revenue` int(11) DEFAULT 0,
  `total_revenue` int(11) DEFAULT 0,
  `total_click` int(11) DEFAULT NULL,
  `calculation_date` datetime NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `rekap_total_publisher_partner`
--

CREATE TABLE `rekap_total_publisher_partner` (
  `id` int(11) NOT NULL,
  `pub_id` int(11) NOT NULL,
  `owner_id` int(11) DEFAULT 0,
  `site_name` varchar(255) DEFAULT NULL,
  `site_domain` varchar(255) DEFAULT NULL,
  `pubs_providers_domain_url` varchar(255) NOT NULL,
  `total_revenue_publishers` int(11) NOT NULL DEFAULT 0,
  `total_clicks` int(11) NOT NULL DEFAULT 0,
  `rekap_date` datetime DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `setting_rule_clicks`
--

CREATE TABLE `setting_rule_clicks` (
  `id` int(11) NOT NULL,
  `rule_name` varchar(10) NOT NULL,
  `threshold` int(11) NOT NULL,
  `description` varchar(255) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `setting_rule_clicks`
--

INSERT INTO `setting_rule_clicks` (`id`, `rule_name`, `threshold`, `description`) VALUES
(1, 'aa', 2, 'Max clicks by same IP and user cookie in 1 minute / Jumlah klik maksimum oleh IP dan cookie pengguna yang sama dalam 1 menit'),
(2, 'ab', 2, 'Max clicks by same IP and browser in 2 minutes / Jumlah klik maksimum oleh IP dan browser yang sama dalam 2 menit'),
(3, 'ac', 3, 'Max clicks by same IP and browser in 5 minutes / Jumlah klik maksimum oleh IP dan browser yang sama dalam 5 menit'),
(4, 'ad', 3, 'Max clicks by same IP and user cookie in 10 minutes / Jumlah klik maksimum oleh IP dan cookie pengguna yang sama dalam 10 menit'),
(5, 'ae', 4, 'Max clicks by same IP and browser in 15 minutes / Jumlah klik maksimum oleh IP dan browser yang sama dalam 15 menit'),
(6, 'af', 4, 'Max clicks by same IP and browser in 20 minutes / Jumlah klik maksimum oleh IP dan browser yang sama dalam 20 menit'),
(7, 'ag', 4, 'Max clicks by same IP and user cookie in 25 minutes / Jumlah klik maksimum oleh IP dan cookie pengguna yang sama dalam 25 menit'),
(8, 'ah', 5, 'Max clicks by same IP and browser in 30 minutes / Jumlah klik maksimum oleh IP dan browser yang sama dalam 30 menit'),
(9, 'ai', 5, 'Max clicks by same IP and user cookie in 35 minutes / Jumlah klik maksimum oleh IP dan cookie pengguna yang sama dalam 35 menit'),
(10, 'aj', 1, 'Max clicks by same IP and user cookie in 20 seconds / Jumlah klik maksimum oleh IP dan cookie pengguna yang sama dalam 20 detik'),
(11, 'ak', 5, 'Max clicks by same IP and browser in 1 hour / Jumlah klik maksimum oleh IP dan browser yang sama dalam 1 jam'),
(12, 'al', 6, 'Max clicks by same IP and browser in 2 hours / Jumlah klik maksimum oleh IP dan browser yang sama dalam 2 jam'),
(13, 'am', 6, 'Max clicks by same IP and browser in 4 hours / Jumlah klik maksimum oleh IP dan browser yang sama dalam 4 jam'),
(14, 'an', 5, 'Max clicks by same IP and browser in 6 hours / Jumlah klik maksimum oleh IP dan browser yang sama dalam 6 jam'),
(15, 'ao', 2, 'Max clicks by same IP and browser in 12 hours / Jumlah klik maksimum oleh IP dan browser yang sama dalam 12 jam'),
(16, 'ap', 5, 'Max clicks by same IP and browser in 24 hours / Jumlah klik maksimum oleh IP dan browser yang sama dalam 24 jam');

-- --------------------------------------------------------

--
-- Table structure for table `video_watch_logs`
--

CREATE TABLE `video_watch_logs` (
  `id` bigint(20) NOT NULL,
  `pubid` int(11) NOT NULL,
  `startTime` varchar(35) DEFAULT NULL,
  `videoId` varchar(16) DEFAULT NULL,
  `duration` int(11) NOT NULL,
  `ip` varchar(45) DEFAULT NULL,
  `useragent` text DEFAULT NULL,
  `referrer` text DEFAULT NULL,
  `url` text DEFAULT NULL,
  `token` varchar(255) DEFAULT NULL,
  `viewed_at` datetime DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Indexes for dumped tables
--

--
-- Indexes for table `advertisers_ads`
--
ALTER TABLE `advertisers_ads`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_local_ads_id` (`local_ads_id`),
  ADD KEY `idx_providers_domain_url` (`providers_domain_url`(100)),
  ADD KEY `idx_advertisers_id` (`advertisers_id`),
  ADD KEY `idx_ispublished_isexpired_ispaused` (`ispublished`,`is_expired`,`is_paused`),
  ADD KEY `idx_title_ads` (`title_ads`(100));

--
-- Indexes for table `advertisers_ads_partners`
--
ALTER TABLE `advertisers_ads_partners`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_local_ads_id` (`local_ads_id`),
  ADD KEY `idx_providers_domain_url` (`providers_domain_url`),
  ADD KEY `idx_advertisers_id` (`advertisers_id`),
  ADD KEY `idx_status_flags` (`ispublished`,`is_expired`,`is_paused`),
  ADD KEY `idx_budget_spending` (`budget_per_click_textads`,`budget_allocation`,`current_spending`),
  ADD KEY `idx_dates` (`regdate`,`published_date`,`expired_date`,`paused_date`);

--
-- Indexes for table `ad_clicks`
--
ALTER TABLE `ad_clicks`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_local_ads_id` (`local_ads_id`),
  ADD KEY `idx_ads_providers_domain_url` (`ads_providers_domain_url`(100)),
  ADD KEY `idx_pub_id` (`pub_id`),
  ADD KEY `idx_ip_user_agent` (`ip_address`,`user_cookies`(100),`browser_agent`(100)),
  ADD KEY `idx_time_epoch_click` (`time_epoch_click`),
  ADD KEY `idx_isaudit_is_reject` (`isaudit`,`is_reject`),
  ADD KEY `idx_hash_click` (`hash_click`),
  ADD KEY `idx_hash_audit` (`hash_audit`),
  ADD KEY `idx_is_sync` (`is_sync`),
  ADD KEY `idx_click_time` (`click_time`),
  ADD KEY `idx_ad_id` (`ad_id`),
  ADD KEY `idx_pub_provider` (`pub_provider`),
  ADD KEY `idx_site_domain` (`site_domain`),
  ADD KEY `idx_ads_providers_name` (`ads_providers_name`),
  ADD KEY `idx_pubs_providers_name` (`pubs_providers_name`),
  ADD KEY `idx_revenue` (`revenue_publishers`,`revenue_adnetwork_local`,`revenue_adnetwork_partner`);

--
-- Indexes for table `ad_clicks_partner`
--
ALTER TABLE `ad_clicks_partner`
  ADD PRIMARY KEY (`id`,`click_time`),
  ADD KEY `idx_local_click_id` (`local_click_id`),
  ADD KEY `idx_local_ads_id` (`local_ads_id`),
  ADD KEY `idx_pub_id` (`pub_id`),
  ADD KEY `idx_click_time` (`click_time`),
  ADD KEY `idx_hash_click` (`hash_click`);

--
-- Indexes for table `document_technical`
--
ALTER TABLE `document_technical`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `hasil_belanja_influencer`
--
ALTER TABLE `hasil_belanja_influencer`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `influencer_media`
--
ALTER TABLE `influencer_media`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `list_browser_banned`
--
ALTER TABLE `list_browser_banned`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_browser_agent` (`browser_agent`(100)),
  ADD KEY `idx_date_banned` (`date_banned`);

--
-- Indexes for table `list_ip_banned`
--
ALTER TABLE `list_ip_banned`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `unique_ip` (`ip_address`);

--
-- Indexes for table `log_payment_order_influencer`
--
ALTER TABLE `log_payment_order_influencer`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `mapping_advertisers_ads_publishers_site`
--
ALTER TABLE `mapping_advertisers_ads_publishers_site`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_local_ads_id` (`local_ads_id`),
  ADD KEY `idx_publishers_site_local_id` (`publishers_site_local_id`),
  ADD KEY `idx_ads_providers_domain_url` (`ads_providers_domain_url`),
  ADD KEY `idx_pubs_providers_domain_url` (`pubs_providers_domain_url`),
  ADD KEY `idx_rate_budget` (`rate_text_ads`,`budget_per_click_textads`,`local_ads_id`),
  ADD KEY `idx_published_date` (`published_date`),
  ADD KEY `idx_paused_date` (`paused_date`),
  ADD KEY `idx_expired_date` (`expired_date`);

--
-- Indexes for table `mapping_advertisers_ads_publishers_site_from_partners`
--
ALTER TABLE `mapping_advertisers_ads_publishers_site_from_partners`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `unique_mapping` (`local_mapping_id`,`local_ads_id`,`publishers_site_local_id`,`ads_providers_domain_url`,`pubs_providers_domain_url`),
  ADD KEY `idx_published_date` (`published_date`),
  ADD KEY `idx_is_published` (`is_published`);

--
-- Indexes for table `media`
--
ALTER TABLE `media`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `msadmin`
--
ALTER TABLE `msadmin`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `msusers`
--
ALTER TABLE `msusers`
  ADD PRIMARY KEY (`id`),
  ADD KEY `loginemail` (`loginemail`),
  ADD KEY `last_login` (`last_login`),
  ADD KEY `whatsapp` (`whatsapp`),
  ADD KEY `last_login_attempt` (`last_login_attempt`),
  ADD KEY `current_revenue` (`current_revenue`),
  ADD KEY `current_revenue_from_partner` (`current_revenue_from_partner`),
  ADD KEY `total_current_revenue` (`total_current_revenue`),
  ADD KEY `current_spending` (`current_spending`),
  ADD KEY `total_current_spending` (`total_current_spending`);

--
-- Indexes for table `payment_local_pubs`
--
ALTER TABLE `payment_local_pubs`
  ADD PRIMARY KEY (`id`),
  ADD KEY `email_pubs` (`email_pubs`),
  ADD KEY `payment_date` (`payment_date`);

--
-- Indexes for table `payment_partner_providers`
--
ALTER TABLE `payment_partner_providers`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_partner_providers_domain_url` (`partner_providers_domain_url`),
  ADD KEY `idx_email_provider` (`email_provider`);

--
-- Indexes for table `payment_partner_providers_sync`
--
ALTER TABLE `payment_partner_providers_sync`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_partner_providers_domain_url` (`partner_providers_domain_url`),
  ADD KEY `idx_email_provider` (`email_provider`),
  ADD KEY `idx_payment_date` (`payment_date`),
  ADD KEY `idx_email_provider_domain_url` (`email_provider`,`partner_providers_domain_url`),
  ADD KEY `idx_local_id` (`local_id`);

--
-- Indexes for table `payment_partner_pubs`
--
ALTER TABLE `payment_partner_pubs`
  ADD PRIMARY KEY (`id`),
  ADD KEY `publisher_local_id` (`publisher_local_id`),
  ADD KEY `pubs_providers_domain_url` (`pubs_providers_domain_url`),
  ADD KEY `email_pubs` (`email_pubs`);

--
-- Indexes for table `payment_partner_pubs_sync`
--
ALTER TABLE `payment_partner_pubs_sync`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_publisher_local_id` (`publisher_local_id`),
  ADD KEY `idx_pubs_providers_domain_url` (`pubs_providers_domain_url`),
  ADD KEY `idx_email_pubs` (`email_pubs`),
  ADD KEY `idx_composite` (`publisher_local_id`,`pubs_providers_domain_url`,`email_pubs`);

--
-- Indexes for table `providers`
--
ALTER TABLE `providers`
  ADD PRIMARY KEY (`id`) USING BTREE;

--
-- Indexes for table `providers_contact_person`
--
ALTER TABLE `providers_contact_person`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `providers_contact_person_sync`
--
ALTER TABLE `providers_contact_person_sync`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `providers_partners`
--
ALTER TABLE `providers_partners`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_providers_name` (`providers_name`),
  ADD KEY `idx_providers_domain_url` (`providers_domain_url`),
  ADD KEY `idx_target_providers_domain_url` (`target_providers_domain_url`),
  ADD KEY `idx_is_hold` (`is_hold`),
  ADD KEY `idx_secret_key` (`secret_key`),
  ADD KEY `idx_public_key` (`public_key`);

--
-- Indexes for table `providers_request`
--
ALTER TABLE `providers_request`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `publishers_site`
--
ALTER TABLE `publishers_site`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_providers_domain_url` (`providers_domain_url`),
  ADD KEY `idx_publishers_local_id` (`publishers_local_id`),
  ADD KEY `idx_site_name_domain` (`site_name`,`site_domain`),
  ADD KEY `idx_rate_revenue` (`rate_text_ads`,`current_site_revenue`),
  ADD KEY `idx_isbanned` (`isbanned`,`banned_date`),
  ADD KEY `idx_last_updated` (`last_updated`);

--
-- Indexes for table `publishers_site_partners`
--
ALTER TABLE `publishers_site_partners`
  ADD PRIMARY KEY (`id`),
  ADD KEY `local_id` (`local_id`),
  ADD KEY `providers_name` (`providers_name`),
  ADD KEY `providers_domain_url` (`providers_domain_url`),
  ADD KEY `site_domain` (`site_domain`),
  ADD KEY `isbanned` (`isbanned`),
  ADD KEY `regdate` (`regdate`);

--
-- Indexes for table `publisher_partner`
--
ALTER TABLE `publisher_partner`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `unique_key` (`loginemail`,`pubs_providers_domain_url`,`publishers_local_id`);

--
-- Indexes for table `rekap_harian`
--
ALTER TABLE `rekap_harian`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_tanggal_klik` (`tanggal_klik`),
  ADD KEY `idx_ads_providers_domain_url` (`ads_providers_domain_url`),
  ADD KEY `idx_local_ads_id` (`local_ads_id`),
  ADD KEY `idx_sumber_data` (`sumber_data`),
  ADD KEY `idx_total_spending` (`total_spending`),
  ADD KEY `idx_jumlah_klik` (`jumlah_klik`),
  ADD KEY `idx_composite` (`tanggal_klik`,`local_ads_id`,`ads_providers_domain_url`);

--
-- Indexes for table `rekap_harian_provider_partner`
--
ALTER TABLE `rekap_harian_provider_partner`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `unique_rekap` (`rekap_date`,`ads_providers_domain_url`);

--
-- Indexes for table `rekap_harian_publishers`
--
ALTER TABLE `rekap_harian_publishers`
  ADD PRIMARY KEY (`rekap_id`);

--
-- Indexes for table `rekap_publisher_revenue_harian_partner`
--
ALTER TABLE `rekap_publisher_revenue_harian_partner`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `unique_rekap` (`pub_id`,`pubs_providers_domain_url`,`date_click`);

--
-- Indexes for table `rekap_pubs_revenue`
--
ALTER TABLE `rekap_pubs_revenue`
  ADD PRIMARY KEY (`pub_id`,`pubs_providers_domain_url`);

--
-- Indexes for table `rekap_total_publisher_partner`
--
ALTER TABLE `rekap_total_publisher_partner`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `unique_rekap` (`pub_id`,`pubs_providers_domain_url`);

--
-- Indexes for table `setting_rule_clicks`
--
ALTER TABLE `setting_rule_clicks`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `unique_rule_name` (`rule_name`);

--
-- Indexes for table `video_watch_logs`
--
ALTER TABLE `video_watch_logs`
  ADD PRIMARY KEY (`id`),
  ADD KEY `pubid` (`pubid`),
  ADD KEY `viewed_at` (`viewed_at`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `advertisers_ads`
--
ALTER TABLE `advertisers_ads`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `advertisers_ads_partners`
--
ALTER TABLE `advertisers_ads_partners`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `ad_clicks`
--
ALTER TABLE `ad_clicks`
  MODIFY `id` bigint(20) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `ad_clicks_partner`
--
ALTER TABLE `ad_clicks_partner`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `document_technical`
--
ALTER TABLE `document_technical`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=15;

--
-- AUTO_INCREMENT for table `hasil_belanja_influencer`
--
ALTER TABLE `hasil_belanja_influencer`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT for table `influencer_media`
--
ALTER TABLE `influencer_media`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `list_browser_banned`
--
ALTER TABLE `list_browser_banned`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=49;

--
-- AUTO_INCREMENT for table `list_ip_banned`
--
ALTER TABLE `list_ip_banned`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=101;

--
-- AUTO_INCREMENT for table `log_payment_order_influencer`
--
ALTER TABLE `log_payment_order_influencer`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `mapping_advertisers_ads_publishers_site`
--
ALTER TABLE `mapping_advertisers_ads_publishers_site`
  MODIFY `id` bigint(20) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `mapping_advertisers_ads_publishers_site_from_partners`
--
ALTER TABLE `mapping_advertisers_ads_publishers_site_from_partners`
  MODIFY `id` bigint(20) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `media`
--
ALTER TABLE `media`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=9;

--
-- AUTO_INCREMENT for table `msadmin`
--
ALTER TABLE `msadmin`
  MODIFY `id` smallint(6) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT for table `msusers`
--
ALTER TABLE `msusers`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `payment_local_pubs`
--
ALTER TABLE `payment_local_pubs`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `payment_partner_providers`
--
ALTER TABLE `payment_partner_providers`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `payment_partner_providers_sync`
--
ALTER TABLE `payment_partner_providers_sync`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- AUTO_INCREMENT for table `payment_partner_pubs`
--
ALTER TABLE `payment_partner_pubs`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `payment_partner_pubs_sync`
--
ALTER TABLE `payment_partner_pubs_sync`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `providers`
--
ALTER TABLE `providers`
  MODIFY `id` smallint(6) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT for table `providers_contact_person`
--
ALTER TABLE `providers_contact_person`
  MODIFY `id` smallint(6) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `providers_contact_person_sync`
--
ALTER TABLE `providers_contact_person_sync`
  MODIFY `id` smallint(6) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT for table `providers_partners`
--
ALTER TABLE `providers_partners`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `providers_request`
--
ALTER TABLE `providers_request`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `publishers_site`
--
ALTER TABLE `publishers_site`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `publishers_site_partners`
--
ALTER TABLE `publishers_site_partners`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `publisher_partner`
--
ALTER TABLE `publisher_partner`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `rekap_harian`
--
ALTER TABLE `rekap_harian`
  MODIFY `id` bigint(20) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `rekap_harian_provider_partner`
--
ALTER TABLE `rekap_harian_provider_partner`
  MODIFY `id` bigint(20) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `rekap_harian_publishers`
--
ALTER TABLE `rekap_harian_publishers`
  MODIFY `rekap_id` bigint(20) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `rekap_publisher_revenue_harian_partner`
--
ALTER TABLE `rekap_publisher_revenue_harian_partner`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `rekap_total_publisher_partner`
--
ALTER TABLE `rekap_total_publisher_partner`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `setting_rule_clicks`
--
ALTER TABLE `setting_rule_clicks`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=17;

--
-- AUTO_INCREMENT for table `video_watch_logs`
--
ALTER TABLE `video_watch_logs`
  MODIFY `id` bigint(20) NOT NULL AUTO_INCREMENT;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
