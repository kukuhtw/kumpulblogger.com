<?php 

ini_set('display_errors', 1);
error_reporting(E_ALL);
include("function.php");
$pubs_providers_domain_url = get_providers_domain_url_json("providers_data.json", 1);
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>KumpulBlogger.com - Monetisasi Media Sosial dan Blog Anda dengan KumpulBlogger.com</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.1/dist/css/bootstrap.min.css" rel="stylesheet">

    <?php  include("gtag.js.php"); ?>

    <style>
        :root {
            --brand-dark: #004d40;
            --brand: #00796b;
            --brand-light: #26a69a;
            --ink: #1c2b2a;
            --muted: #5b6f6d;
            --bg: #f4f9f8;
            --card-bg: #ffffff;
        }

        body {
            font-family: -apple-system, "Segoe UI", Roboto, Arial, sans-serif;
            background-color: var(--bg);
            color: var(--ink);
            margin: 0;
        }

        .section-heading {
            position: relative;
            margin-bottom: 1.75rem;
            padding-bottom: .6rem;
            font-weight: 800;
            font-size: 1.9rem;
            color: var(--brand-dark);
        }

        .section-heading::after {
            content: "";
            position: absolute;
            left: 50%;
            bottom: 0;
            transform: translateX(-50%);
            width: 64px;
            height: 4px;
            border-radius: 2px;
            background: linear-gradient(90deg, var(--brand), var(--brand-light));
        }

        h4 { color: var(--brand-dark); font-weight: 750; }

        .navbar {
            background-color: #ffffff;
            box-shadow: 0 2px 12px rgba(0, 77, 64, .08);
        }

        .navbar-brand {
            color: var(--brand-dark) !important;
            font-weight: 800;
        }

        .nav-link {
            color: var(--ink) !important;
        }

        .nav-link:hover {
            color: var(--brand) !important;
        }

        .hero-section {
            position: relative;
            background: linear-gradient(135deg, rgba(0, 34, 30, .82), rgba(0, 121, 107, .55)), url('kb5.png') no-repeat center center;
            background-size: cover;
            color: #ffffff;
            padding: 6rem 0;
            text-shadow: 0 2px 10px rgba(0, 0, 0, .35);
        }

        .hero-section .lead {
            color: rgba(255, 255, 255, .9);
        }

        .card {
            border: none;
            border-radius: 1rem;
            box-shadow: 0 4px 16px rgba(0, 77, 64, .1);
            transition: transform .2s ease, box-shadow .2s ease;
            background-color: var(--card-bg);
        }

        .card:hover {
            transform: translateY(-6px);
            box-shadow: 0 10px 24px rgba(0, 77, 64, .16);
        }

        .card-title {
            color: var(--brand-dark);
            font-weight: 750;
        }

        .card-text {
            color: var(--muted);
        }

        .text-muted {
            font-size: 1rem;
            color: var(--muted) !important;
        }

        .bg-primary {
            background-color: var(--brand) !important;
        }

        .step-card {
            display: flex;
            gap: 1rem;
            padding: 1.25rem 1.5rem;
            margin-bottom: 1.25rem;
            border-radius: 1rem;
            background: var(--card-bg);
            box-shadow: 0 3px 14px rgba(0, 77, 64, .08);
        }

        .step-number {
            flex: 0 0 auto;
            display: flex;
            align-items: center;
            justify-content: center;
            width: 42px;
            height: 42px;
            border-radius: 50%;
            background: linear-gradient(135deg, var(--brand), var(--brand-light));
            color: #fff;
            font-weight: 800;
        }

        .step-card h4 {
            margin: 0 0 .4rem;
            text-align: left !important;
        }

        .link-brand {
            color: var(--brand);
            font-weight: 700;
            text-decoration: none;
        }

        .link-brand:hover {
            color: var(--brand-dark);
            text-decoration: underline;
        }

        footer {
            background-color: var(--brand-dark);
            padding: 2rem 0;
            color: #e0f2f1;
        }

        footer a {
            color: #b2dfdb;
            text-decoration: none;
        }

        footer a:hover {
            color: #ffffff;
        }

        .content-container {
            display: grid;
            grid-template-columns: 68% 32%;
            gap: 2rem;
        }

        .main-content {
            padding-right: .5rem;
        }

        .sidebar-content {
            padding-left: .5rem;
        }

        @media (max-width: 768px) {
            .content-container {
                grid-template-columns: 1fr;
            }
            .hero-section {
                padding: 3.5rem 0;
            }
        }
    </style>
</head>

<body>

    <!-- Navbar -->
    <nav class="navbar navbar-expand-lg navbar-light bg-light">
        <div class="container">
            <a class="navbar-brand" href="#">KumpulBlogger</a>
            <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav"
                aria-controls="navbarNav" aria-expanded="false" aria-label="Toggle navigation">
                <span class="navbar-toggler-icon"></span>
            </button>
            <div class="collapse navbar-collapse" id="navbarNav">
                <ul class="navbar-nav ms-auto">
                    <li class="nav-item">
                        <a class="nav-link active" aria-current="page" href="#">Home</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="#carakerja">How it works?</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="reg.php">Daftar</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="login.php">Login</a>
                    </li>
                </ul>
            </div>
        </div>
    </nav>

    <!-- Hero Section -->
    <header class="hero-section text-center">
        <div class="container">
            <h1 class="display-4">Selamat Datang di KumpulBlogger v3.0</h1>
            <p class="lead">Menghadirkan Konsep baru<br> dalam dunia jaringan iklan digital</p>
            <a href="reg.php" class="btn btn-light btn-lg">Mulai Sekarang</a>
        </div>
    </header>



    <!-- Main Content with Sidebar -->
    <div class="container py-5">
        <div class="content-container">
            <!-- Main Content -->
            <div class="main-content">
                <!-- Features Section -->
                <section>
                    <div class="text-center">
                        <h2 class="section-heading text-uppercase">Apa yang  Baru dari KumpulBlogger ?</h2>
                        
<p class="text-muted">
        KumpulBlogger.com adalah platform <b>pay per click</b> yang menggunakan format <b>native ads</b>, di mana iklan tampil secara alami dan selaras dengan konten situs penerbit. Kini hadir fitur terbaru: <b>Blog Engine AI</b> — sebuah sistem cerdas yang membantu publisher membuat artikel dengan lebih mudah dan cepat.

        Dengan Blog Engine AI, publisher dapat:
        <ul class="text-start">
            <li>Memilih <b>topik</b> dari ribuan ide yang tersedia, seperti teknologi, sejarah, ekonomi, lifestyle, parenting, wisata, hingga opini sosial.</li>
            <li>Menentukan <b>deskripsi pembahasan</b> untuk memperjelas arah tulisan.</li>
            <li>Memilih <b>bahasa</b> (Indonesia, Inggris) dan <b>gaya penulisan</b> (formal, santai, akademis, satiris, atau SEO-friendly).</li>
        </ul>

        Setelah itu, AI akan menuliskan artikelnya secara otomatis sesuai preferensi publisher. Artikel ini bisa langsung diterbitkan di blog milik publisher dalam platform KumpulBlogger, lengkap dengan iklan yang bisa menghasilkan pendapatan dari setiap klik pengunjung.
    </p>


<p>
<a target="_2" href="https://github.com/kukuhtw/kumpulblogger.com/tree/master">Bikin bisnis PPC sendiri, ambil code nya disini - https://github.com/kukuhtw/kumpulblogger.com/tree/master</a>
</p>
<p class="text-muted">
KumpulBlogger.com - Monetisasi Media Sosial dan Blog Anda dengan KumpulBlogger.com
</p>
                    </div>
                     <div class="row g-4">
                        <div class="col-md-12">
                            <div class="card">
                                <div class="card-body text-center">
                                    <h5 class="card-title">Blog Engine AI – Tulis Artikel dengan Bantuan Kecerdasan Buatan</h5>
                                    <p class="card-text">
Perkenalkan fitur terbaru dari KumpulBlogger: <b>Blog Engine AI</b>! Kini, publisher bisa dengan mudah membuat artikel yang menarik dan siap tayang hanya dengan beberapa klik.

AI akan menuliskan artikel secara otomatis sesuai dengan instruksi Anda. Artikel bisa langsung Anda terbitkan di blog Anda di KumpulBlogger, lengkap dengan iklan native yang siap menghasilkan pendapatan dari setiap klik pengunjung.

Let’s get started! <br>
<a href="reg.php">Daftar segera di KumpulBlogger.com!</a>
</p>
                                </div>
                            </div>
                        </div>

                        <div class="col-md-12">
                            <div class="card">
                                <div class="card-body text-center">

<h5 class="card-title">Punya Blog Sendiri di KumpulBlogger.com</h5>
<p class="card-text">
Kini Anda bisa memiliki blog pribadi di <b>KumpulBlogger.com</b> tanpa ribet! Dilengkapi dengan fitur <b>Blog Engine AI</b>, Anda dapat membuat artikel secara otomatis hanya dengan memilih topik, deskripsi singkat, bahasa, dan gaya penulisan.

Manfaat memiliki blog di KumpulBlogger:
<ul>
    <li><b>Tulis artikel dibantu AI</b> – Tak perlu bingung kehabisan ide atau repot menulis panjang, cukup berikan arahannya, dan AI akan menulis untuk Anda.</li>
    <li><b>Monetisasi langsung</b> – Blog Anda akan otomatis menampilkan <b>native ads</b> yang menghasilkan uang setiap kali diklik pengunjung.</li>
    <li><b>Bangun personal brand</b> – Cocok untuk penulis, pebisnis, kreator, atau siapa pun yang ingin membangun reputasi online.</li>
    <li><b>Gratis & mudah digunakan</b> – Tidak perlu keahlian teknis. Semua tersedia dalam satu dashboard praktis.</li>
</ul>

Tulis. Terbitkan. Hasilkan uang. <br>
<a href="reg.php">Daftar segera di KumpulBlogger.com!</a>
</p>



                                </div>
                            </div>
                        </div>

                        <div class="col-md-12">
                            <div class="card">
                                <div class="card-body text-center">

<h5 class="card-title">Bikin Blog di KumpulBlogger – Tulis, Sebarkan, Dapat Gaji!</h5>
<p class="card-text">
Saatnya punya blog sendiri di <b>KumpulBlogger.com</b>! Tulis artikel menarik dengan bantuan AI, lalu sebarkan ke teman-teman dan media sosial Anda. Semakin banyak yang membaca dan klik iklan di blog Anda, semakin besar penghasilan yang Anda dapatkan.

✨ <b>Keuntungannya:</b>
<ul>
    <li><b>Tulis artikel otomatis</b> dengan bantuan AI, cukup pilih topik dan gaya penulisan.</li>
    <li><b>Sebarkan link blog Anda</b> ke teman atau komunitas.</li>
    <li><b>Dapatkan gaji mingguan</b> dari klik iklan yang tampil di blog Anda.</li>
</ul>

Mudah, gratis, dan bisa jadi sumber penghasilan tambahan! <br>
<a href="reg.php">Daftar sekarang dan mulai blogging di KumpulBlogger.com!</a>
</p>



                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="row g-4 mt-1">
                        <div class="col-md-4">
                            <div class="card">
                                <div class="card-body text-center">
                                    <h5 class="card-title">White Label </h5>
                                    <p class="card-text">Miliki, atur, dan bangun platform iklan dengan nama bisnis Anda sendiri. Kontrol penuh ada di tangan Anda.</p>
                                    <a class="link-brand" href="https://kumpulblogger.com/white_label/">Info detail</a>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="card">
                                <div class="card-body text-center">
                                    <h5 class="card-title">Desentralisasi </h5>
                                    <p class="card-text">Sistem periklanan yang dinamis dengan distribusi iklan terdesentralisasi untuk jangkauan yang lebih luas.</p>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="card">
                                <div class="card-body text-center">
                                    <h5 class="card-title">Penetapan Harga Fleksibel</h5>
                                    <p class="card-text">Penerbit dapat menetapkan harga iklan mereka sendiri untuk memaksimalkan pendapatan.</p>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="row g-4 mt-4">
                        <div class="col-md-4">
                            <div class="card">
                                <div class="card-body text-center">
                                    <h5 class="card-title">Fleksibilitas Pengiklan</h5>
                                    <p class="card-text">Pengiklan dapat menyesuaikan harga sesuai anggaran dan memilih penerbit untuk kampanye iklan mereka.</p>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="card">
                                <div class="card-body text-center">
                                    <h5 class="card-title">Kendali Penuh</h5>
                                    <p class="card-text">Penerbit dan pengiklan memiliki kendali penuh untuk menolak iklan yang tidak sesuai dengan kebijakan mereka.</p>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="card">
                                <div class="card-body text-center">
                                    <h5 class="card-title">Transparansi dalam Klik</h5>
                                    <p class="card-text">Transparansi dalam transaksi klik dengan proses audit yang dapat dilihat oleh penerbit dan pengiklan.</p>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="row g-4 mt-4">
                        <div class="col-md-4">
                            <div class="card">
                                <div class="card-body text-center">
                                    <h5 class="card-title">Payout Lebih Cepat</h5>
                                    <p class="card-text">Gajian setiap akhir pekan Jumat, Sabtu, Minggu. Minimal payout Rp 5,000.</p>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="card">
                                <div class="card-body text-center">
                                    <h5 class="card-title">Alternate Code</h5>
                                    <p class="card-text">Belum ada iklan masuk? Penerbit dapat menyisipkan script dari adnetwork lain bila sedang tidak ada iklan masuk.</p>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="card">
                                <div class="card-body text-center">
                                    <h5 class="card-title">Satu Dashboard</h5>
                                    <p class="card-text">Publisher dan pemasang iklan disediakan satu dashboard untuk mengelola blog, website, ataupun iklan.</p>
                                </div>
                            </div>
                        </div>
                    </div>
                </section>






                <!-- How It Works Section -->
                <a name="carakerja"></a>
                <section class="bg-light py-5">
                    <div class="container">
                        <div class="text-center mb-4">
                            <h2 class="section-heading text-uppercase">Cara Kerja KumpulBlogger.com</h2>
                        </div>
                        <div class="row">
                            <div class="col-md-12">
                                <div class="step-card">
                                    <span class="step-number">1</span>
                                    <div>
                                        <h4>Pendaftaran Pengguna Sebagai Publisher dan Advertiser di Satu Dashboard</h4>
                                        <p>KumpulBlogger.com memberikan kemudahan bagi pengguna untuk mendaftar sebagai <strong>publisher</strong> (penerbit) atau <strong>advertiser</strong> (pengiklan) dalam satu dashboard yang terintegrasi. Dengan satu akun, pengguna dapat beralih peran sesuai kebutuhan, baik untuk memasang iklan maupun menampilkan iklan di blog atau media online mereka.</p>
                                    </div>
                                </div>

                                <div class="step-card">
                                    <span class="step-number">2</span>
                                    <div>
                                        <h4>Pendaftaran Publisher dan Penentuan Harga Iklan</h4>
                                        <p>Sebagai publisher, pengguna dapat mendaftarkan blog atau media online mereka di KumpulBlogger.com. Dalam proses ini, publisher perlu memberikan informasi penting, seperti:</p>

                                        <ul>
                                            <li><strong>Rate harga per klik:</strong> Publisher bebas menentukan berapa biaya per klik yang ingin mereka tetapkan untuk iklan yang tayang di situs mereka.</li>
                                            <li><strong>Deskripsi blog:</strong> Menyertakan deskripsi blog atau media online untuk memberikan gambaran kepada advertiser tentang konten dan audiens yang dituju.</li>
                                            <li><strong>Jenis iklan yang disetujui:</strong> Publisher dapat menentukan jenis iklan apa saja yang dapat tayang di situs mereka, seperti iklan gambar, teks, atau video.</li>
                                            <li><strong>Jenis iklan yang tidak disetujui:</strong> Publisher juga memiliki hak untuk menolak jenis iklan tertentu yang tidak sesuai dengan kebijakan atau audiens blog.</li>
                                        </ul>
                                        <p><a target="_blank" href="https://kumpulblogger.com/data/list_publisher_site.php">Daftar Blog/Website/Media yang sudah bergabung</a></p>
                                    </div>
                                </div>

                                <div class="step-card">
                                    <span class="step-number">3</span>
                                    <div>
                                        <h4>Pendaftaran Advertiser dan Pengaturan Kampanye Iklan</h4>
                                        <p>Sebagai advertiser, pengguna dapat mendaftarkan iklan mereka yang akan didistribusikan ke jaringan KumpulBlogger.com. Dalam pengaturan iklan, advertiser diminta untuk mengisi beberapa detail berikut:</p>
                                        <ul>
                                            <li><strong>Judul iklan:</strong> Nama atau topik iklan yang akan tayang.</li>
                                            <li><strong>Deskripsi iklan:</strong> Informasi singkat tentang produk atau layanan yang diiklankan.</li>
                                            <li><strong>Alokasi budget:</strong> Total dana yang ingin dialokasikan untuk kampanye iklan.</li>
                                            <li><strong>Biaya per klik:</strong> Besaran rupiah yang dialokasikan untuk setiap klik yang didapatkan dari iklan tersebut.</li>
                                        </ul>

                                        <p><a target="_blank" href="https://kumpulblogger.com/data/rekap_ads_harian.php">Daftar Iklan yang sedang berjalan</a></p>

                                        <p><a target="_blank" href="https://kumpulblogger.com/preview.php">Contoh Tampilan Iklan</a></p>
                                    </div>
                                </div>

                                <div class="step-card">
                                    <span class="step-number">4</span>
                                    <div>
                                        <h4>Kontrol Penuh Terhadap Konten Iklan</h4>
                                        <p>Baik publisher maupun advertiser memiliki kontrol penuh atas iklan yang akan tayang. Publisher dapat menolak atau menyetujui iklan yang akan muncul di situs mereka, sementara advertiser juga dapat memilih situs atau blog tempat iklan mereka akan tampil.</p>
                                    </div>
                                </div>

                                <div class="step-card">
                                    <span class="step-number">5</span>
                                    <div>
                                        <h4>Laporan dan Penyesuaian Iklan Secara Real-Time</h4>
                                        <p>KumpulBlogger.com menyediakan laporan secara real-time yang mencakup <strong>biaya iklan yang berjalan</strong> dan <strong>jumlah klik yang terjadi</strong>. Fitur ini memungkinkan advertiser untuk:</p>
                                        <ul>
                                            <li>Menyesuaikan harga iklan dengan menaikkan atau menurunkan biaya per klik sesuai situasi pasar.</li>
                                            <li>Melakukan optimalisasi kampanye berdasarkan performa iklan.</li>
                                        </ul>
                                        <p>Di sisi lain, publisher juga dapat memantau jumlah klik yang dihasilkan dan menyesuaikan rate harga iklan mereka untuk memaksimalkan pendapatan.</p>
                                    </div>
                                </div>

                                <div class="step-card">
                                    <span class="step-number">6</span>
                                    <div>
                                        <h4>Markup Harga Iklan oleh Provider dan Partner AdNetwork</h4>
                                        <p>Harga iklan yang ditentukan oleh publisher akan secara otomatis dimarkup oleh <strong>provider adnetwork lokal</strong> sebesar 50% dan oleh <strong>partner adnetwork</strong> juga sebesar 50%. Hal ini memungkinkan adanya distribusi pendapatan yang adil di antara berbagai pihak yang terlibat dalam ekosistem iklan KumpulBlogger.com.</p>
                                    </div>
                                </div>

                                <div class="step-card">
                                    <span class="step-number">7</span>
                                    <div>
                                        <h4>Payout yang Mudah dan Fleksibel</h4>
                                        <p>Publisher dapat melakukan payout atau penarikan pendapatan setiap hari Jumat, Sabtu, dan Minggu dengan syarat minimum payout sebagai berikut:</p>
                                        <ul>
                                            <li><strong>Rp 5.000</strong> untuk transfer melalui BCA dan GoPay.</li>
                                            <li><strong>Rp 10.000</strong> untuk bank lain seperti BNI dan Mandiri.</li>
                                        </ul>
                                        <p>Fleksibilitas ini memberikan kemudahan bagi publisher untuk menarik pendapatan mereka sesuai jadwal yang telah ditentukan.</p>

                                        <p><script type='text/javascript' src='<?php echo $pubs_providers_domain_url ?>//sample_landscape.js.php?maxads=5&column=1'></script></p>
                                    </div>
                                </div>

                                <div class="step-card">
                                    <span class="step-number">8</span>
                                    <div>
                                        <h4>Masa Uji Coba dan Rencana Implementasi Desentralisasi</h4>
                                        <p>Selama 3 bulan pertama, KumpulBlogger.com akan menjalani masa uji coba untuk memastikan efektivitas platform. Setelah masa uji coba selesai, KumpulBlogger.com akan mulai menjalin kemitraan dengan adnetwork lain yang menggunakan platform serupa. Jika kolaborasi ini berhasil, konsep <strong>desentralized</strong>, <strong>distributed</strong>, dan <strong>federated</strong> akan diterapkan secara penuh di KumpulBlogger.com, menciptakan ekosistem iklan yang lebih luas dan terdesentralisasi.</p>
                                        <p>KumpulBlogger.com bertujuan untuk menciptakan lingkungan iklan yang adil dan transparan bagi publisher dan advertiser, sekaligus meningkatkan jangkauan iklan secara lebih luas melalui jaringan yang terintegrasi.</p>
                                    </div>
                                </div>

<?php
// Define the URL to fetch the JSON data
$json_url = "https://kumpulblogger.com/JSON/last10publishers.json";

// Fetch the JSON data
$json_data = file_get_contents($json_url);

// Decode the JSON data into a PHP array
$publishers = json_decode($json_data, true);

// Check if the data was successfully fetched and decoded
if (is_array($publishers)) {
    echo '<h4 class="mt-4">Daftar Publisher Terbaru</h4>';
    echo '<div class="row row-cols-1 row-cols-md-2 g-3">';

    // Loop through each publisher and display their details in two columns
    foreach ($publishers as $publisher) {
        // Format the rate_text_ads as currency
        $formatted_rate = "Rp " . number_format($publisher['rate_text_ads'], 2, ',', '.');

        echo '<div class="col">';
        echo '<div class="card h-100"><div class="card-body">';
        echo '<strong>Nama Situs:</strong> ' . htmlspecialchars($publisher['site_name']) . '<br>';
        echo '<strong>Domain Situs:</strong> <a href="' . htmlspecialchars($publisher['site_domain']) . '" target="_blank" rel="noopener">' . htmlspecialchars($publisher['site_domain']) . '</a><br>';
        echo '<strong>Deskripsi:</strong> ' . htmlspecialchars($publisher['site_desc']) . '<br>';
        echo '<strong>Tarif Iklan Teks:</strong> ' . $formatted_rate . '<br>';
        echo '<strong>Tanggal Registrasi:</strong> ' . htmlspecialchars($publisher['regdate']) . '<br>';
        echo '</div></div></div>';
    }

    echo '</div>';
} else {
    // If there was an error fetching or decoding the data
    echo '<p class="text-muted">Gagal mengambil data publisher.</p>';
}
?>
 <p class="mt-3"><a target="_blank" href="https://kumpulblogger.com/data/list_publisher_site.php">Daftar Blog/Website/Media yang sudah bergabung</a></p>




<!-- =========================================
     LATEST 5 ARTICLES
========================================== -->
<section class="py-5">
    <h2 class="section-heading text-uppercase text-center mb-4">Artikel Random</h2>

    <?php 
//echo "<br>step 1";
require_once("db.php");

//echo "<br>step 2";


$config = [
    'database' => [
        'host' => $servername_db,
        'username' => $username_db,
        'password' => $password_db,
        'dbname' => $dbname_db
    ],
    'app' => [
        'debug' => true,
        'log_path' => 'logs/'
    ]
];


// db_connection.php - Database connection class
class Database {
    private $conn;
    
    public function __construct($config) {
        $this->conn = new mysqli(
            $config['host'],
            $config['username'],
            $config['password'],
            $config['dbname']
        );
        
        if ($this->conn->connect_error) {
            throw new Exception("Connection failed: " . $this->conn->connect_error);
        }
        
        $this->conn->set_charset("utf8mb4");
    }
    
    public function getConnection() {
        return $this->conn;
    }
    
    public function __destruct() {
        if ($this->conn) {
            $this->conn->close();
        }
    }
}


//echo "<br>step 3";

// Koneksi ke database
try {
  //  echo "<br>step 4a";
    $db   = new Database($config['database']);
  //  echo "<br>step 4b";
    $conn = $db->getConnection();
  //   echo "<br>step 4c";
    
} catch (Exception $e) {
    //echo "<br>step 4b";
    die("Gagal koneksi database: " . $e->getMessage());
}


//echo "<br>step 5";
$limit  = 10;
$offset = 0;

$fetchSql = "
    SELECT  a.id,
            a.title,
            a.html_content,
            a.tag,
            a.created_at,
            a.wav,
            a.images,
            pq.username,
            pq.description AS author_description
    FROM    articles a
    LEFT JOIN publisher_quota pq ON a.pub_id = pq.pub_id
    WHERE   a.ispublished = 1
    ORDER BY rand()
    LIMIT ?, ?";

$stmt = $conn->prepare($fetchSql);
$stmt->bind_param("ii", $offset, $limit);
$stmt->execute();
$result   = $stmt->get_result();
$articles = $result->fetch_all(MYSQLI_ASSOC);
$stmt->close();

    foreach ($articles as $row): ?>
        <?php
            $username   = $row['username'] ?? 'unknown';
            $authorDesc = $row['author_description'] ?? '';
            $slug       = preg_replace('/[^A-Za-z0-9 ]/', '', $row['title']);
            $slug       = str_replace(' ', '_', $slug);
            $text       = strip_tags($row['html_content']);
            $words      = preg_split('/\s+/', $text);
            $snippet    = count($words) > 25
                          ? implode(' ', array_slice($words, 0, 25)) . '…'
                          : $text;

            /* cari thumbnail */
            $thumbnailUrl = '';
            if (preg_match('/<img\s+[^>]*src=[\'"]([^\'"]+)[\'"]/i', $row['html_content'], $m)) {
                $src          = $m[1];
                $thumbnailUrl = preg_match('#^https?://#i', $src) ? $src : "../" . ltrim($src, '/');
            }
            if ($thumbnailUrl === '') $thumbnailUrl = "../" . $row['images'];
        ?>
        <div class="card mb-3">
            <?php if ($thumbnailUrl !== "../"): ?>
                <img src="<?= htmlspecialchars($thumbnailUrl) ?>"
                     class="card-img-top img-fluid img-thumbnail"
                     style="max-height:200px;object-fit:cover"
                     alt="Thumbnail">
            <?php endif; ?>

            <div class="card-body">
                <h5 class="card-title">
                    <a href="/blog/<?= urlencode($username) ?>/<?= $row['id'] ?>/<?= urlencode($slug) ?>"
                       class="text-decoration-none">
                       <?= htmlspecialchars($row['title']) ?>
                    </a>
                </h5>
                <p class="card-text text-secondary">
                    <small>Penulis: <strong><?= htmlspecialchars($username) ?></strong><?= $authorDesc ? " — " . htmlspecialchars($authorDesc) : "" ?></small>
                </p>
                <p class="card-text"><?= htmlspecialchars($snippet) ?></p>
                <p class="card-text"><small class="text-muted">Diterbitkan: <?= $row['created_at'] ?></small></p>

                <?php if (!empty($row['wav'])): ?>
                    <div class="mb-2">
                        <audio controls style="width:100%">
                            <source src="<?= htmlspecialchars("../../../{$row['wav']}") ?>" type="audio/wav">
                            Browser Anda tidak mendukung audio.
                        </audio>
                    </div>
                <?php endif; ?>

                <a target="_blank" href="/blog/<?= urlencode($username) ?>/<?= $row['id'] ?>/<?= urlencode($slug) ?>"
                   class="btn btn-primary btn-sm">Read more</a>
            </div>
        </div>
    <?php endforeach; ?>
</section>
<!-- ========================================= -->




                            </div>
                        </div>
                    </div>
                </section>
            </div>

            <!-- Sidebar Content -->
            <div class="sidebar-content">
                <!-- White Label Section -->
                <a name="whitelabel"></a>
                <section class="py-5">
                    <div class="container">
                        <div class="text-center">
                            <h2 class="section-heading text-uppercase">White Label dalam KumpulBlogger</h2>
                            <p class="text-muted">White label dalam jaringan periklanan KumpulBlogger adalah solusi bagi mereka yang ingin memiliki platform pay-per-click dengan merek sendiri, tetapi tetap menggunakan kode dari platform KumpulBlogger.</p>
                            <p><a class="link-brand" href="https://kumpulblogger.com/white_label/">Info detail</a></p>
                        </div>
                        <div class="row text-center">
                            <div class="col-md-12">
                                <p class="text-muted">Dalam konsep ini, jaringan white label tidak akan saling bersaing, melainkan saling berbagi publisher dan advertiser, sehingga eksposur bagi publisher dan advertiser menjadi lebih luas.</p>
                                <p class="text-muted">Publisher dapat menerima iklan dari jaringan white label mana pun yang menggunakan platform KumpulBlogger, dan advertiser dapat menampilkan iklan mereka di seluruh jaringan white label tersebut. Dengan demikian, tercipta kolaborasi dan sinergi tanpa adanya kompetisi.</p>
                                <p class="text-muted">Keuntungan dari usaha ini berkisar antara 25% hingga 33%. Pemilik white label wajib melakukan pembayaran kepada blogger dalam jaringannya, maupun kepada blogger di luar jaringannya.</p>
                                <p class="text-muted">Selain itu, setiap white label juga bisa mendapatkan revenue tambahan, tidak hanya dari advertiser tetapi juga dari partner white label lainnya. Hal ini terjadi apabila ada transaksi klik dari advertiser di luar jaringannya. Sebaliknya, jika iklan dari advertiser lokal diklik oleh jaringan white label lain, maka white label tersebut juga wajib melakukan pembayaran kepada partner providernya.</p>
                                <p>
<a target="_2" href="https://github.com/kukuhtw/kumpulblogger.com/tree/master">Bikin bisnis PPC sendiri,<br> ambil code nya disini <br>https://github.com/kukuhtw/kumpulblogger.com/tree/master</a>
</p>
                                
                              

                                <p><script type='text/javascript' src='<?php echo $pubs_providers_domain_url ?>//sample.js.php?maxads=5&column=1'></script></p>

                            </div>
                        </div>
                    </div>
                </section>
            </div>
        </div>
    </div>

    <!-- Add this before the Footer section -->
<section class="ads-section py-5">
    <div class="container text-center">
        <script type='text/javascript' src='<?php echo $pubs_providers_domain_url ?>//sample_landscape.js.php?maxads=5&column=1'></script>
    </div>
</section>


    <!-- Footer -->
    <footer>
        <div class="container text-center">
            <p>&copy; 2024 KumpulBlogger. All rights reserved.</p>
            <p><a href="reg.php">Daftar</a> | <a href="login.php">Login</a></p>
        </div>
    </footer>

    <script src="https://cdn.jsdelivr.net/npm/@popperjs/core@2.11.8/dist/umd/popper.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.1/dist/js/bootstrap.min.js"></script>
</body>

</html>
