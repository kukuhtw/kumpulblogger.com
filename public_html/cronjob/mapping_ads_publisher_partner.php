<?php
/*

cronjob/mapping_ads_publisher_partner.php 

*/

echo "<!DOCTYPE html>";
echo "<html lang='en'>";
echo "<head>";
echo "<meta charset='UTF-8'>";
echo "<meta name='viewport' content='width=device-width, initial-scale=1.0'>";
echo "<title>Mapping Ads Publisher Partner</title>";
echo "<style>";
echo "body {font-family: Arial, sans-serif; margin: 20px;}";
echo "h1 {color: #4CAF50;}";
echo "h2, h3 {color: #333;}";
echo "h4 {color: #FF5733;}";
echo ".section {background-color: #f9f9f9; border-radius: 5px; padding: 20px; margin-bottom: 20px; box-shadow: 0 0 10px rgba(0, 0, 0, 0.1);}";
echo ".highlight {color: red; font-weight: bold;}";
echo ".match {color: green; font-weight: bold;}";
echo ".title {font-size: 1.5em; margin-bottom: 10px;}";
echo "table {width: 100%; border-collapse: collapse; margin: 20px 0;}";
echo "table, th, td {border: 1px solid #ddd;}";
echo "th, td {padding: 10px; text-align: left;}";
echo "</style>";
echo "</head>";
echo "<body>";

echo "<h1>Mapping Publisher Local dengan Iklan dari Partner AdNetwork Lain</h1>";

// Database connection
include("../db.php");

$mysqli = new mysqli($servername_db, $username_db, $password_db, $dbname_db);

// Check connection
if ($mysqli->connect_error) {
    die("Connection failed: " . $mysqli->connect_error);
}

// Named lock MySQL untuk mencegah dua eksekusi skrip ini tumpang tindih
// (mis. cron sebelumnya belum selesai saat yang berikutnya mulai). Tabel
// mapping_advertisers_ads_publishers_site tidak punya UNIQUE KEY untuk
// (local_ads_id, publishers_site_local_id, ads_providers_domain_url), jadi
// pola check-then-insert di bawah bisa membuat baris duplikat kalau dua
// proses jalan bersamaan tanpa lock ini. Timeout 0 = tidak menunggu, kalau
// instance lain masih memegang lock, skrip ini langsung berhenti tanpa
// melakukan apa pun (aman, tidak menumpuk antrean).
$lock_name = 'mapping_ads_publisher_partner';
$lock_stmt = $mysqli->query("SELECT GET_LOCK('" . $mysqli->real_escape_string($lock_name) . "', 0) AS got_lock");
$got_lock = $lock_stmt ? (int) $lock_stmt->fetch_assoc()['got_lock'] : 0;
if ($got_lock !== 1) {
    die("<p style='color:red;'>Instance lain dari mapping_ads_publisher_partner.php sedang berjalan (lock '$lock_name' sedang dipegang) — dilewati untuk mencegah baris mapping duplikat.</p></body></html>");
}
// Tidak perlu RELEASE_LOCK manual: named lock MySQL terikat ke koneksi ini
// dan otomatis dilepas begitu koneksi ditutup ($mysqli->close() di akhir
// skrip) atau proses PHP berakhir (mis. kalau ada die()/fatal error di
// tengah jalan).

// Ambil data dari tabel advertisers_ads yang ispublished = 1 dan is_expired = 0
$sql_ads = "SELECT * FROM advertisers_ads_partners WHERE ispublished = 1 AND is_expired = 0";
echo "<br><h2>Menampilkan Data Iklan dari Partner AdNetwork</h2>";
echo "<p>Query: $sql_ads</p>";

$result_ads = $mysqli->query($sql_ads);

if ($result_ads->num_rows > 0) {
    while($row_ads = $result_ads->fetch_assoc()) {
        $local_ads_id = $row_ads['local_ads_id'];
        $ads_providers_name = $row_ads['providers_name'];
        $ads_providers_domain_url = $row_ads['providers_domain_url'];
        $advertisers_id = $row_ads['advertisers_id'];
        $title_ads = $row_ads['title_ads'];
        $description_ads = $row_ads['description_ads'];
        $landingpage_ads = $row_ads['landingpage_ads'];
        $image_url = $row_ads['image_url'];
        $budget_per_click_textads = $row_ads['budget_per_click_textads'];
        // Status master iklan partner saat ini — dipakai untuk menyegarkan
        // cache is_published/is_paused/is_expired di baris mapping yang SUDAH
        // ADA (lihat cabang UPDATE di bawah), bukan untuk memaksa ulang status
        // approval publisher/advertiser.
        $ispublished = $row_ads['ispublished'];
        $is_expired = $row_ads['is_expired'];
        $expired_date = $row_ads['expired_date'];
        $is_paused = $row_ads['is_paused'];

        echo "<div class='section'>";
        echo "<h3>Judul Iklan: $title_ads</h3>";
        echo "<p>Landing Page: $landingpage_ads</p>";
        echo "<p>Budget Per Click Text Ads: Rp $budget_per_click_textads</p>";
        echo "<p>Penyedia Iklan: $ads_providers_name ($ads_providers_domain_url)</p>";
        echo "</div>";

        // Ambil data dari tabel publishers_site
        $sql_site = "SELECT * FROM publishers_site";
        echo "<h2>Menampilkan Data Publishers Site</h2>";
        echo "<p>Query: $sql_site</p>";

        $result_site = $mysqli->query($sql_site);

        if ($result_site->num_rows > 0) {
            while($row_site = $result_site->fetch_assoc()) {
                $publishers_site_local_id = $row_site['id'];
                $rate_text_ads = $row_site['rate_text_ads'];
                $publishers_local_id = $row_site['publishers_local_id'];
                $site_name = $row_site['site_name'];
                $site_domain = $row_site['site_domain'];
                $site_desc = $row_site['site_desc'];
                $pubs_providers_name = $row_site['providers_name'];
                $pubs_providers_domain_url = $row_site['providers_domain_url'];

                echo "<div class='section'>";
                echo "<p>Publisher Site: $site_name ($site_domain)</p>";
                echo "<p>Rate Text Ads: Rp $rate_text_ads</p>";
                echo "<p>Penyedia Publisher: $pubs_providers_name ($pubs_providers_domain_url)</p>";

                // Tambahkan markup 50% pada rate_text_ads
                $rate_text_ads_with_markup = $rate_text_ads * 2;
                $revenue_publishers = $rate_text_ads;
                echo "<p>Rate dengan Markup: Rp $rate_text_ads_with_markup</p>";

                // Cek apakah budget_per_click_textads memenuhi syarat
                if ($budget_per_click_textads >= $rate_text_ads_with_markup) {

                    // Cek apakah data sudah ada berdasarkan local_ads_id dan publishers_local_id
                    $check_stmt = $mysqli->prepare(
                        "SELECT id FROM mapping_advertisers_ads_publishers_site
                         WHERE local_ads_id = ?
                           AND publishers_site_local_id = ?
                           AND ads_providers_domain_url = ?"
                    );
                    $check_stmt->bind_param("iis", $local_ads_id, $publishers_site_local_id, $ads_providers_domain_url);
                    $check_stmt->execute();
                    $check_result = $check_stmt->get_result();
                    $check_stmt->close();

                    $jml_data = $check_result->num_rows;
                    echo "<h4>Jumlah Data Ditemukan: $jml_data (local_ads_id=$local_ads_id, publishers_site_local_id=$publishers_site_local_id, ads_providers_domain_url=$ads_providers_domain_url)</h4>";

                    if ($check_result->num_rows > 0) {
                        // Update data jika sudah ada
                        echo "<p><strong>Data ditemukan, memperbarui data...</strong></p>";

                        // Catatan: cabang ini SENGAJA tidak menyentuh
                        // is_approved_by_publisher/is_approved_by_advertiser,
                        // approval_date_*, maupun reasons_rejected_* — dulu
                        // kolom-kolom itu ikut di-reset paksa ke "disetujui"
                        // setiap kali skrip ini jalan, sehingga menimpa reject
                        // manual (publisher/advertiser lewat dashboard) maupun
                        // auto-reject dari mapping_ads_publisher_check_rate_partner.php.
                        // Status approval sekarang murni tanggung jawab
                        // mapping_ads_publisher_check_rate_partner.php, sama
                        // seperti pembagian tugas di versi lokal
                        // (mapping_ads_publisher.php vs
                        // mapping_ads_publisher_check_rate.php). is_published/
                        // is_paused/is_expired tetap disegarkan di sini, tapi
                        // dari status master iklan yang sebenarnya (bukan
                        // hardcode 1/0/0).
                        $update_stmt = $mysqli->prepare(
                            "UPDATE mapping_advertisers_ads_publishers_site
                             SET rate_text_ads = ?,
                                 budget_per_click_textads = ?,
                                 owner_advertisers_id = ?,
                                 title_ads = ?,
                                 description_ads = ?,
                                 landingpage_ads = ?,
                                 image_url = ?,
                                 site_name = ?,
                                 site_domain = ?,
                                 site_desc = ?,
                                 is_published = ?,
                                 is_paused = ?,
                                 is_expired = ?,
                                 expired_date = ?,
                                 revenue_publishers = ?
                             WHERE local_ads_id = ?
                               AND publishers_site_local_id = ?
                               AND ads_providers_domain_url = ?"
                        );
                        $update_stmt->bind_param(
                            "ddisssssssiiisdiis",
                            $rate_text_ads_with_markup, $budget_per_click_textads, $advertisers_id,
                            $title_ads, $description_ads, $landingpage_ads, $image_url,
                            $site_name, $site_domain, $site_desc,
                            $ispublished, $is_paused, $is_expired, $expired_date, $revenue_publishers,
                            $local_ads_id, $publishers_site_local_id, $ads_providers_domain_url
                        );
                        $update_stmt->execute();
                        $update_stmt->close();
                    } else {
                        // Insert data baru jika belum ada
                        echo "<p><strong>Data tidak ditemukan, memasukkan data baru...</strong></p>";

                        $insert_stmt = $mysqli->prepare(
                            "INSERT INTO mapping_advertisers_ads_publishers_site
                                (rate_text_ads, budget_per_click_textads, local_ads_id, publishers_site_local_id,
                                 pubs_providers_name, pubs_providers_domain_url,
                                 ads_providers_name, ads_providers_domain_url, owner_advertisers_id, title_ads,
                                 description_ads, landingpage_ads, image_url, publishers_local_id, site_name,
                                 site_domain, site_desc, is_published, is_paused, is_expired,
                                 is_approved_by_publisher, is_approved_by_advertiser, approval_date_publisher,
                                 approval_date_advertiser, revenue_publishers)
                             VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, 1, ?, 0, 1, 1, NOW(), NOW(), ?)"
                        );
                        $insert_stmt->bind_param(
                            "ddiissssissssisssid",
                            $rate_text_ads_with_markup, $budget_per_click_textads,
                            $local_ads_id, $publishers_site_local_id,
                            $pubs_providers_name, $pubs_providers_domain_url,
                            $ads_providers_name, $ads_providers_domain_url, $advertisers_id,
                            $title_ads, $description_ads, $landingpage_ads, $image_url,
                            $publishers_local_id, $site_name, $site_domain, $site_desc,
                            $is_paused, $revenue_publishers
                        );
                        $insert_stmt->execute();
                        $insert_stmt->close();
                    }
                } else {
                    echo "<p class='highlight'>Budget Per Click dari Advertiser tidak memenuhi syarat.</p>";
                }
                echo "</div>";
            }
        }
    }
}

// Close connection
$mysqli->close();

echo "</body>";
echo "</html>";
?>
