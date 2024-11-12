<?php

include("function.php");
$local_ads_id = isset($_GET['local_ads_id']) ? intval($_GET['local_ads_id']) : 1; // Default to 1 if not provided

$pubs_providers_domain_url = get_providers_domain_url_json("providers_data.json", 1);

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>KumpulBlogger Adnetwork News</title>

    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.1/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        .article-title {
            font-size: 2rem;
            font-weight: bold;
            margin-bottom: 20px;
        }
        .article-body {
            font-size: 1.1rem;
            line-height: 1.7;
        }
        .intro-section {
            background-color: #f8f9fa;
            padding: 20px;
            border-radius: 8px;
        }
        .related-links h5 {
            margin-top: 20px;
        }
        .footer {
            background-color: #343a40;
            color: white;
            padding: 1rem;
        }
    </style>
    
</head>
<body>

<div class="container mt-5">
    <div class="row">
        <!-- Left Column - Introduction or Sidebar -->
        <div class="col-md-4 mb-4 mb-md-0">
            <div class="intro-section">
                <h5>Quick Facts</h5>
                <p>KumpulBlogger.com adalah platform adnetwork terdesentralisasi yang inovatif, dirancang untuk memberdayakan publisher dan advertiser dengan berbagai keunggulan.</p>
                <h5>Recent Developments</h5>
                <ul class="list-group">
                    <li class="list-group-item">Fleksibilitas publisher untuk menentukan rate harga sendiri</li>
                    <li class="list-group-item">Fitur blokir iklan yang tidak disukai oleh publisher</li>
                    <li class="list-group-item">Fitur take-down iklan oleh advertiser</li>
                    <li class="list-group-item">Payout cepat setiap weekend (Jumat, Sabtu, Minggu) dengan minimal 5000 rupiah</li>
                    <li class="list-group-item">Format iklan dalam bentuk native ads</li>
                    <li class="list-group-item">Fitur alternate code untuk menjaga pendapatan dengan menggunakan adnetwork lain saat tidak ada iklan dari KumpulBlogger</li>
                </ul>
                <p>&nbsp;</p>
                <h5>Sponsor</h5>
                <p><script type='text/javascript' src='<?php echo $pubs_providers_domain_url ?>//preview_vertical.js.php?local_ads_id=<?php echo $local_ads_id ?>&pubProvName=adnetworkB&maxads=2&column=1'></script></p>
            </div>
        </div>
        <!-- Right Column - Main Article -->
        <div class="col-md-8">
            <div class="article-title">
                KumpulBlogger.com: v3.0 Iklan Digital dengan Fleksibilitas harga, White label dan Pembayaran Cepat
            </div>
            <div class="article-body">
                <p>
                    KumpulBlogger.com siap meramaikan kembali lanskap periklanan digital dengan platform adnetwork terdesentralisasi yang menawarkan fleksibilitas luar biasa bagi publisher dan keamanan bagi advertiser. Dengan fitur unggulan yang memungkinkan publisher menentukan rate harga sendiri dan memblokir iklan yang tidak disukai, KumpulBlogger.com memberikan kebebasan penuh kepada publisher untuk mengatur pendapatan mereka sesuai dengan kebutuhan.
                </p>
                <p><img class="img-responsive" src="kb6.png" width="700px" height="100%"></p>
                <p>
                    Tidak hanya itu, advertiser juga diberikan kontrol penuh dengan fitur take-down iklan dari suatu website jika dirasa tidak sesuai atau performa tidak memuaskan. KumpulBlogger.com juga menawarkan proses payout yang sangat cepat, dilakukan setiap weekend (Jumat, Sabtu, Minggu), dengan minimal payout hanya 5000 rupiah, memberikan keuntungan likuiditas yang lebih baik dibandingkan platform lain.
                </p>
                <p>Dalam 3-4 bulan ke depan, kumpulblogger.com akan memberikan kesempatan bagi siapa saja memiliki bisnis pay per click, dengan menggunakan brand anda sendiri. semacam white label. Uniknya, setiap white label dapat saling terintegrasi, dapat berbagi resource publisher dan advertiser. dengan membuka platform yang saling terintegrasi, akan tercipta peluang synergi saling menguntungkan. Adnetwork / White label dapat menerima iklan dari adnetwork lain, publisher pada Adnetwork A dapat menerima iklan dari advertiser di Adnetwork B.
                </p>
                <p>
                    "Visi kami adalah menciptakan ekosistem kolaboratif di mana ad network dapat bekerja sama untuk meningkatkan jangkauan dan efektivitas periklanan digital," kata Kukuh TW, pendiri KumpulBlogger.com. "Platform ini memberdayakan publisher dan advertiser dengan memberikan alat yang mereka butuhkan untuk berhasil di pasar yang kompetitif."
                </p>
                <p>
                    Selain itu, format iklan dalam bentuk native ads memastikan integrasi yang mulus dengan konten situs, memberikan pengalaman yang lebih natural bagi pengguna. Dengan pendekatan ini, publisher dapat menampilkan iklan yang relevan tanpa mengganggu pengalaman browsing pengunjung.
                </p>
                <p>
                    KumpulBlogger.com juga menawarkan fitur alternate code, yang memungkinkan publisher untuk menempatkan script dari adnetwork lain, seperti Adsense, ketika tidak ada iklan yang tersedia dari KumpulBlogger. Dengan fitur ini, publisher tidak akan kehilangan peluang untuk memonetisasi ruang iklan di blog mereka, sehingga pendapatan tetap terjaga.
                </p>
                <p><img class="img-responsive" src="kb7.png" width="700px" height="100%"></p>
                <p>
                <p>
                    KumpulBlogger.com terus berkembang dengan memperkenalkan fitur-fitur tambahan seperti analitik kinerja real-time, serta langkah-langkah keamanan yang ditingkatkan untuk melindungi data pengguna. Masa depan periklanan digital ada di sini, dan KumpulBlogger.com berada di garis depan inovasi ini.
                </p>
                <p>
                    Publisher dan advertiser yang tertarik bergabung dengan platform KumpulBlogger.com dapat mendaftar hari ini untuk mulai merasakan manfaat dari jaringan iklan yang inovatif ini. Dengan pendekatan fleksibilitas, sinergi sesama platform ini, dan kemampuan payout yang cepat, KumpulBlogger.com siap menjadi pemain kembalidalam industri periklanan digital.
                </p>
                <p><script type='text/javascript' src='<?php echo $pubs_providers_domain_url ?>//preview.js.php?local_ads_id=<?php echo $local_ads_id ?>&pubProvName=adnetworkB&maxads=2&column=1'></script></p>
            </div>
            
            <div class="my-4">
                <a href="reg.php" class="btn btn-primary">Daftar</a>
                <a href="login.php" class="btn btn-secondary">Login</a>
                <a href="forgot_password.php" class="btn btn-link">Lupa Password?</a>
                <a href="index.php" class="btn btn-link">Home</a>
            </div>
        </div>
    </div>
</div>

<!-- Footer -->
<footer class="footer text-center">
    <p>&copy; 2024 KumpulBlogger. All rights reserved.</p>
</footer>

<!-- Bootstrap JS and dependencies -->
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.1/dist/js/bootstrap.bundle.min.js"></script>

</body>
</html>
