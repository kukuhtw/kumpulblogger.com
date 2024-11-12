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
                    $check_sql = "SELECT * FROM mapping_advertisers_ads_publishers_site 
                                  WHERE local_ads_id = $local_ads_id 
                                  AND publishers_site_local_id = $publishers_site_local_id
                                  AND ads_providers_domain_url = '$ads_providers_domain_url'";

                    echo "<h3>Query Pengecekan: $check_sql</h3>";

                    $check_result = $mysqli->query($check_sql);
                    $jml_data = $check_result->num_rows;
                    echo "<h4>Jumlah Data Ditemukan: $jml_data</h4>";

                    if ($check_result->num_rows > 0) {
                        // Update data jika sudah ada
                        echo "<p><strong>Data ditemukan, memperbarui data...</strong></p>";

                        $update_sql = "UPDATE mapping_advertisers_ads_publishers_site 
                                       SET rate_text_ads = $rate_text_ads_with_markup, 
                                           budget_per_click_textads = $budget_per_click_textads, 
                                           owner_advertisers_id = $advertisers_id, 
                                           title_ads = '$title_ads', 
                                           description_ads = '$description_ads', 
                                           landingpage_ads = '$landingpage_ads', 
                                           image_url = '$image_url',
                                           site_name = '$site_name', 
                                           site_domain = '$site_domain', 
                                           site_desc = '$site_desc', 
                                           is_published = 1, 
                                           is_paused = 0, 
                                           is_expired = 0, 
                                           is_approved_by_publisher = 1, 
                                           is_approved_by_advertiser = 1, 
                                           approval_date_publisher = NOW(), 
                                           approval_date_advertiser = NOW(),
                                           reasons_rejected_by_advertiser = '', 
                                           reasons_rejected_by_publisher = '', 
                                           revenue_publishers = $revenue_publishers
                                       WHERE local_ads_id = $local_ads_id 
                                       AND publishers_site_local_id = $publishers_site_local_id
                                       AND ads_providers_domain_url = '$ads_providers_domain_url'";

                        echo "<br><strong>Query Update: </strong>$update_sql";
                        $mysqli->query($update_sql);
                    } else {
                        // Insert data baru jika belum ada
                        echo "<p><strong>Data tidak ditemukan, memasukkan data baru...</strong></p>";

                        $insert_sql = "INSERT INTO mapping_advertisers_ads_publishers_site 
                                       (rate_text_ads, budget_per_click_textads, local_ads_id, publishers_site_local_id, 
                                        pubs_providers_name, pubs_providers_domain_url,
                                        ads_providers_name, ads_providers_domain_url, owner_advertisers_id, title_ads, 
                                        description_ads, landingpage_ads, image_url, publishers_local_id, site_name, 
                                        site_domain, site_desc, is_published, is_paused, is_expired, 
                                        is_approved_by_publisher, is_approved_by_advertiser, approval_date_publisher, 
                                        approval_date_advertiser, revenue_publishers) 
                                       VALUES ($rate_text_ads_with_markup, $budget_per_click_textads, 
                                               $local_ads_id, $publishers_site_local_id, 
                                               '$pubs_providers_name', '$pubs_providers_domain_url',
                                               '$ads_providers_name', '$ads_providers_domain_url', $advertisers_id, 
                                               '$title_ads', '$description_ads', '$landingpage_ads', '$image_url',
                                               $publishers_local_id, '$site_name', '$site_domain', '$site_desc', 1, 0, 0, 1, 1, 
                                               NOW(), NOW(), $revenue_publishers)";

                        echo "<br><strong>Query Insert: </strong>$insert_sql";
                        $mysqli->query($insert_sql);
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
