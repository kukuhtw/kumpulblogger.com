<?php 
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
    <style>
     body {
    font-family: Arial, sans-serif;
    background-color: #e0f7fa; /* Latar belakang biru laut muda */
    color: #004d40; /* Warna teks hijau gelap */
    margin: 0;
    padding: 20px;
}

h2, h4 {
    color: #006064; /* Warna heading biru laut tua */
    border-bottom: 2px solid #006064;
    padding-bottom: 10px;
}

.navbar {
    background-color: #00796b; /* Navbar biru laut */
}

.navbar-brand, .nav-link {
    color: #e0f7fa !important; /* Warna teks navbar biru laut muda */
}

.nav-link:hover {
    color: #004d40 !important; /* Warna teks navbar saat hover hijau gelap */
}

.hero-section {
    background: url('kb5.png') no-repeat center center;
    background-size: cover;
    color: #e0f7fa;
    height: 700px;
    padding: 4rem 0;
    text-shadow: 12px 12px 15px rgba(0, 0, 0, 0.5);
}

.card {
    border: none;
    border-radius: 15px;
    box-shadow: 0 4px 8px rgba(0, 0, 0, 0.1);
    transition: all 0.3s ease-in-out;
    background-color: #b2ebf2; /* Warna kartu biru laut muda */
}

.card:hover {
    transform: translateY(-10px);
    box-shadow: 0 6px 12px rgba(0, 0, 0, 0.2);
}

.card-title {
    color: #004d40; /* Warna judul kartu hijau gelap */
}

.card-text {
    color: #004d40; /* Warna teks kartu hijau gelap */
}

.section-heading {
    margin-bottom: 2rem;
    font-weight: bold;
    font-size: 2rem;
    color: #004d40; /* Warna heading biru laut */
}

.text-muted {
    font-size: 1rem;
    color: #004d40; /* Warna teks muted hijau gelap */
}

.bg-primary {
    background-color: #00796b !important; /* Warna latar belakang utama biru laut */
}

footer {
    background-color: #004d40; /* Latar belakang footer hijau gelap */
    padding: 2rem 0;
    color: #e0f7fa; /* Warna teks footer biru laut muda */
}

footer a {
    color: #b2dfdb; /* Warna link footer hijau muda */
    text-decoration: none;
}

footer a:hover {
    color: #e0f7fa; /* Warna link saat hover biru laut muda */
}

.content-container {
    display: grid;
    grid-template-columns: 70% 30%;
    gap: 1rem;
}

.main-content {
    padding-right: 1rem;
}

.sidebar-content {
    padding-left: 1rem;
}

@media (max-width: 768px) {
    .content-container {
        grid-template-columns: 1fr;
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
                        <div class="col-md-4">
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

                        <div class="col-md-4">
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

                        <div class="col-md-4">
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


                    <div class="row g-4">
                        <div class="col-md-4">
                            <div class="card">
                                <div class="card-body text-center">
                                    <h5 class="card-title">White Label (Coming soon)</h5>
                                    <p class="card-text">Miliki, atur, dan bangun platform iklan dengan nama bisnis Anda sendiri. Kontrol penuh ada di tangan Anda.</p>
                                    <a class="nav-link" href="#whitelabel">Info detail</a>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="card">
                                <div class="card-body text-center">
                                    <h5 class="card-title">Desentralisasi (Coming soon)</h5>
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
                                <h4 class="text-center">1. Pendaftaran Pengguna Sebagai Publisher dan Advertiser di Satu Dashboard</h4>
                                <p>KumpulBlogger.com memberikan kemudahan bagi pengguna untuk mendaftar sebagai <strong>publisher</strong> (penerbit) atau <strong>advertiser</strong> (pengiklan) dalam satu dashboard yang terintegrasi. Dengan satu akun, pengguna dapat beralih peran sesuai kebutuhan, baik untuk memasang iklan maupun menampilkan iklan di blog atau media online mereka.</p>

                                <h4 class="text-center">2. Pendaftaran Publisher dan Penentuan Harga Iklan</h4>
                                <p>Sebagai publisher, pengguna dapat mendaftarkan blog atau media online mereka di KumpulBlogger.com. Dalam proses ini, publisher perlu memberikan informasi penting, seperti:</p>

                                <ul>
                                    <li><strong>Rate harga per klik:</strong> Publisher bebas menentukan berapa biaya per klik yang ingin mereka tetapkan untuk iklan yang tayang di situs mereka.</li>
                                    <li><strong>Deskripsi blog:</strong> Menyertakan deskripsi blog atau media online untuk memberikan gambaran kepada advertiser tentang konten dan audiens yang dituju.</li>
                                    <li><strong>Jenis iklan yang disetujui:</strong> Publisher dapat menentukan jenis iklan apa saja yang dapat tayang di situs mereka, seperti iklan gambar, teks, atau video.</li>
                                    <li><strong>Jenis iklan yang tidak disetujui:</strong> Publisher juga memiliki hak untuk menolak jenis iklan tertentu yang tidak sesuai dengan kebijakan atau audiens blog.</li>
                                </ul>
                                <p><a target="_blank" href="https://kumpulblogger.com/data/list_publisher_site.php">Daftar Blog/Website/Media yang sudah bergabung</a></p>

                                <h4 class="text-center">3. Pendaftaran Advertiser dan Pengaturan Kampanye Iklan</h4>
                                <p>Sebagai advertiser, pengguna dapat mendaftarkan iklan mereka yang akan didistribusikan ke jaringan KumpulBlogger.com. Dalam pengaturan iklan, advertiser diminta untuk mengisi beberapa detail berikut:</p>
                                <ul>
                                    <li><strong>Judul iklan:</strong> Nama atau topik iklan yang akan tayang.</li>
                                    <li><strong>Deskripsi iklan:</strong> Informasi singkat tentang produk atau layanan yang diiklankan.</li>
                                    <li><strong>Alokasi budget:</strong> Total dana yang ingin dialokasikan untuk kampanye iklan.</li>
                                    <li><strong>Biaya per klik:</strong> Besaran rupiah yang dialokasikan untuk setiap klik yang didapatkan dari iklan tersebut.</li>
                                </ul>

                                <p><a target="_blank" href="https://kumpulblogger.com/data/rekap_ads_harian.php">Daftar Iklan yang sedang berjalan</a></p>

                                <p><a target="_blank" href="https://kumpulblogger.com/preview.php">Contoh Tampilan Iklan</a></p>

                                <h4 class="text-center">4. Kontrol Penuh Terhadap Konten Iklan</h4>
                                <p>Baik publisher maupun advertiser memiliki kontrol penuh atas iklan yang akan tayang. Publisher dapat menolak atau menyetujui iklan yang akan muncul di situs mereka, sementara advertiser juga dapat memilih situs atau blog tempat iklan mereka akan tampil.</p>

                                <h4 class="text-center">5. Laporan dan Penyesuaian Iklan Secara Real-Time</h4>
                                <p>KumpulBlogger.com menyediakan laporan secara real-time yang mencakup <strong>biaya iklan yang berjalan</strong> dan <strong>jumlah klik yang terjadi</strong>. Fitur ini memungkinkan advertiser untuk:</p>
                                <ul>
                                    <li>Menyesuaikan harga iklan dengan menaikkan atau menurunkan biaya per klik sesuai situasi pasar.</li>
                                    <li>Melakukan optimalisasi kampanye berdasarkan performa iklan.</li>
                                </ul>
                                <p>Di sisi lain, publisher juga dapat memantau jumlah klik yang dihasilkan dan menyesuaikan rate harga iklan mereka untuk memaksimalkan pendapatan.</p>

                                <h4 class="text-center">6. Markup Harga Iklan oleh Provider dan Partner AdNetwork</h4>
                                <p>Harga iklan yang ditentukan oleh publisher akan secara otomatis dimarkup oleh <strong>provider adnetwork lokal</strong> sebesar 50% dan oleh <strong>partner adnetwork</strong> juga sebesar 50%. Hal ini memungkinkan adanya distribusi pendapatan yang adil di antara berbagai pihak yang terlibat dalam ekosistem iklan KumpulBlogger.com.</p>

                                <h4 class="text-center">7. Payout yang Mudah dan Fleksibel</h4>
                                <p>Publisher dapat melakukan payout atau penarikan pendapatan setiap hari Jumat, Sabtu, dan Minggu dengan syarat minimum payout sebagai berikut:</p>
                                <ul>
                                    <li><strong>Rp 5.000</strong> untuk transfer melalui BCA dan GoPay.</li>
                                    <li><strong>Rp 10.000</strong> untuk bank lain seperti BNI dan Mandiri.</li>
                                </ul>
                                <p>Fleksibilitas ini memberikan kemudahan bagi publisher untuk menarik pendapatan mereka sesuai jadwal yang telah ditentukan.</p>

                                <p><script type='text/javascript' src='<?php echo $pubs_providers_domain_url ?>//sample_landscape.js.php?maxads=5&column=1'></script></p>

                                <h4 class="text-center">8. Masa Uji Coba dan Rencana Implementasi Desentralisasi</h4>
                                <p>Selama 3 bulan pertama, KumpulBlogger.com akan menjalani masa uji coba untuk memastikan efektivitas platform. Setelah masa uji coba selesai, KumpulBlogger.com akan mulai menjalin kemitraan dengan adnetwork lain yang menggunakan platform serupa. Jika kolaborasi ini berhasil, konsep <strong>desentralized</strong>, <strong>distributed</strong>, dan <strong>federated</strong> akan diterapkan secara penuh di KumpulBlogger.com, menciptakan ekosistem iklan yang lebih luas dan terdesentralisasi.</p>
                                <p>KumpulBlogger.com bertujuan untuk menciptakan lingkungan iklan yang adil dan transparan bagi publisher dan advertiser, sekaligus meningkatkan jangkauan iklan secara lebih luas melalui jaringan yang terintegrasi.</p>

                                <h2 class="section-heading text-uppercase">Iklan youtube video</h2>

                                <p>Iklan youtube video sedang masa development</p>
                                 <div id="youtube-video-container"></div>


<?php
// Define the URL to fetch the JSON data
$json_url = "https://kumpulblogger.com/JSON/last10publishers.json";

// Fetch the JSON data
$json_data = file_get_contents($json_url);

// Decode the JSON data into a PHP array
$publishers = json_decode($json_data, true);

// Check if the data was successfully fetched and decoded
if (is_array($publishers)) {
    echo "<style>
            body {
                font-family: Arial, sans-serif;
                background-color: #f0f8ff; /* Light blue background */
                color: #333;
                margin: 0;
                padding: 20px;
            }
            h2 {
                color: #0073e6; /* Dark blue heading */
                border-bottom: 2px solid #0073e6;
                padding-bottom: 10px;
            }
            .publisher-container {
                display: flex;
                flex-wrap: wrap;
                gap: 20px;
            }
            .publisher {
                width: calc(50% - 20px);
                background-color: #e6f7ff; /* Light blue card background */
                border: 1px solid #0073e6;
                border-radius: 8px;
                padding: 15px;
                box-shadow: 0 2px 5px rgba(0, 115, 230, 0.3);
            }
            .publisher a {
                color: #0073e6;
                text-decoration: none;
                font-weight: bold;
            }
            .publisher a:hover {
                text-decoration: underline;
            }
            .publisher strong {
                color: #005bb5;
            }
        </style>";

    echo "<h2>Daftar Publisher Terbaru</h2>";
    echo "<div class='publisher-container'>";

    // Loop through each publisher and display their details in two columns
    foreach ($publishers as $publisher) {
        // Format the rate_text_ads as currency
        $formatted_rate = "Rp " . number_format($publisher['rate_text_ads'], 2, ',', '.');

        echo "<div class='publisher'>";
        echo "<strong>Nama Situs:</strong> " . htmlspecialchars($publisher['site_name']) . "<br>";
        echo "<strong>Domain Situs:</strong> <a href='" . htmlspecialchars($publisher['site_domain']) . "' target='_blank'>" . htmlspecialchars($publisher['site_domain']) . "</a><br>";
        echo "<strong>Deskripsi:</strong> " . htmlspecialchars($publisher['site_desc']) . "<br>";
        echo "<strong>Tarif Iklan Teks:</strong> " . $formatted_rate . "<br>";
        echo "<strong>Tanggal Registrasi:</strong> " . htmlspecialchars($publisher['regdate']) . "<br>";
        echo "</div>";
    }

    echo "</div>";
} else {
    // If there was an error fetching or decoding the data
    echo "Gagal mengambil data publisher.";
}
?>
 <p><a target="_blank" href="https://kumpulblogger.com/data/list_publisher_site.php">Daftar Blog/Website/Media yang sudah bergabung</a></p>


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

<script>
    (function() {
        let startTime, videoId, token, player;
          let lastRecordedTime = 0;
         let timer; // Timer variable to track the 30 seconds
         let isPlaying = false; // Flag to check if the video is playing

      function loadYouTubeVideo(videoId, containerId) {
        const container = document.getElementById(containerId);
        if (!container) {
            console.error('Container not found:', containerId);
            return;
        }


            // Clear the container
            container.innerHTML = '';

            // Create a div to host the YouTube player
            const playerDiv = document.createElement('div');
            playerDiv.id = 'youtube-player';
            container.appendChild(playerDiv);

            // Initialize YouTube API
            if (typeof YT === 'undefined' || !YT.loaded) {
                const tag = document.createElement('script');
                tag.src = "https://www.youtube.com/iframe_api";
                const firstScriptTag = document.getElementsByTagName('script')[0];
                firstScriptTag.parentNode.insertBefore(tag, firstScriptTag);

                window.onYouTubeIframeAPIReady = createYouTubePlayer;
            } else {
                createYouTubePlayer();
            }
        }

        function createYouTubePlayer() {
            player = new YT.Player('youtube-player', {
                height: '315',
                width: '100%',
                videoId: videoId,
                playerVars: {
                    'autoplay': 1,
                    'controls': 1,
                    'rel': 0,
                    'modestbranding': 1,
                     'mute': 1 // Menambahkan mute pada parameter
                },
                events: {
                    'onReady': onPlayerReady,
                    'onStateChange': onPlayerStateChange
                }
            });
        }



       
    function onPlayerReady(event) {
        startTime = new Date();
        event.target.playVideo();
    }


    function recordCurrentTime() {
        if (player && player.getCurrentTime) {
            lastRecordedTime = Math.round(player.getCurrentTime());
        }
    }


      function onPlayerStateChange(event) {
        if (event.data == YT.PlayerState.PLAYING && !isPlaying) {
            isPlaying = true;
            startTime = new Date();
            // Start the timer to send duration after 30 seconds of uninterrupted play
            timer = setTimeout(sendDurationToServer, 30000);
        } else if (event.data == YT.PlayerState.PAUSED || event.data == YT.PlayerState.BUFFERING) {
            clearTimeout(timer); // Stop the timer if the video is paused or buffering
            isPlaying = false;
        } else if (event.data == YT.PlayerState.ENDED) {
            sendDurationToServer(); // Send duration when the video ends
        }
    }



               function fetchVideoId(pubid, callback) {
            const xhr = new XMLHttpRequest();
            xhr.open('GET', `<?php echo $pubs_providers_domain_url ?>/videojs.php?pubid=${pubid}`, true);
            xhr.onreadystatechange = function() {
                if (xhr.readyState === 4 && xhr.status === 200) {
                    const response = JSON.parse(xhr.responseText);
                    videoId = response.videoId;
                    token = response.token;
                    callback(videoId);
                }
            };
            xhr.send();
        }


     function getReferrerInfo() {
            let referrer = document.referrer;
            if (!referrer) {
                referrer = 'direct';
            }
            return referrer;
        }

    

      function sendDurationToServer() {
        alert('sendDurationToServer to');

            const duration = Math.round((new Date() - startTime) / 1000);
            const formData = new FormData();
            formData.append('startTime', startTime);
             formData.append('duration', duration);
            formData.append('pubid', '2');
            formData.append('token', token);
            formData.append('referrer', getReferrerInfo());
            formData.append('url', window.location.href);

            fetch('<?php echo $pubs_providers_domain_url ?>/videojs.php', {
                method: 'POST',
                body: formData
            })
            .then(response => response.json())
            .then(result => console.log('Server Response:', result))
            .catch(error => console.error('Error:', error));
        }

        const pubid = 2; // Replace with the appropriate public ID

        fetchVideoId(pubid, function(videoId) {
            if (videoId) {
                loadYouTubeVideo(videoId, 'youtube-video-container');
            } else {
                console.error('No video ID received.');
            }
        });

        window.addEventListener('beforeunload', sendDurationToServer);
    })();
    </script>

    <!-- Add this before the Footer section -->
<section class="carousel-section py-5">
    <div class="container">
        <div id="carouselExampleIndicators" class="carousel slide" data-bs-ride="carousel">
            <ol class="carousel-indicators">
                <li data-bs-target="#carouselExampleIndicators" data-bs-slide-to="0" class="active"></li>
                <li data-bs-target="#carouselExampleIndicators" data-bs-slide-to="1"></li>
                <li data-bs-target="#carouselExampleIndicators" data-bs-slide-to="2"></li>
                <li data-bs-target="#carouselExampleIndicators" data-bs-slide-to="3"></li>
                <li data-bs-target="#carouselExampleIndicators" data-bs-slide-to="4"></li>
                <li data-bs-target="#carouselExampleIndicators" data-bs-slide-to="5"></li>
                <li data-bs-target="#carouselExampleIndicators" data-bs-slide-to="6"></li>
                <li data-bs-target="#carouselExampleIndicators" data-bs-slide-to="7"></li>
            </ol>
            <div class="carousel-inner">
                <div class="carousel-item active">
                    <img src="kb7.png" class="d-block w-100" alt="Slide 1">
                </div>
                <div class="carousel-item">
                    <img src="kb6.png" class="d-block w-100" alt="Slide 2">
                </div>
                <div class="carousel-item">
                    <img src="kb5.png" class="d-block w-100" alt="Slide 3">
                </div>
                <div class="carousel-item">
                    <img src="kb4.png" class="d-block w-100" alt="Slide 4">
                </div>
                <div class="carousel-item">
                    <img src="kb8.png" class="d-block w-100" alt="Slide 5">
                </div>
                <div class="carousel-item">
                    <img src="kb9.png" class="d-block w-100" alt="Slide 6">
                </div>
                <div class="carousel-item">
                    <img src="kb10.png" class="d-block w-100" alt="Slide 7">
                </div>
                <div class="carousel-item">
                    <img src="kb11.png" class="d-block w-100" alt="Slide 8">
                </div>
            </div>
            <a class="carousel-control-prev" href="#carouselExampleIndicators" role="button" data-bs-slide="prev">
                <span class="carousel-control-prev-icon" aria-hidden="true"></span>
                <span class="visually-hidden">Previous</span>
            </a>
            <a class="carousel-control-next" href="#carouselExampleIndicators" role="button" data-bs-slide="next">
                <span class="carousel-control-next-icon" aria-hidden="true"></span>
                <span class="visually-hidden">Next</span>
            </a>
        </div>
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
