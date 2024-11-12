<?php
/*
cronjob/mapping_ads_publisher_check_rate_partner.php 
*/
// Database connection
echo "<!DOCTYPE html>";
echo "<html lang='en'>";
echo "<head>";
echo "<meta charset='UTF-8'>";
echo "<meta name='viewport' content='width=device-width, initial-scale=1.0'>";
echo "<title>Mapping Publisher Local dengan Iklan Partner - Check Rate</title>";
echo "<style>";
echo "body {font-family: 'Open Sans', sans-serif; margin: 0; padding: 0; background-color: #f4f4f9; color: #333;}";
echo ".container {max-width: 1100px; margin: 0 auto; padding: 20px;}";
echo "h1 {color: #4CAF50; font-size: 2em; text-align: center; margin-bottom: 30px;}";
echo ".section {background-color: #fff; border-radius: 10px; padding: 20px; margin-bottom: 30px; box-shadow: 0 2px 10px rgba(0, 0, 0, 0.1);}";
echo ".highlight {color: #d32f2f; font-weight: bold;}";
echo ".match {color: #388e3c; font-weight: bold;}";
echo ".title {font-size: 1.8em; margin-bottom: 10px; color: #0288d1;}";
echo ".info {margin-bottom: 15px; font-size: 1.1em; color: #555;}";
echo ".box {background-color: #f9f9f9; padding: 15px; margin-bottom: 15px; border-left: 5px solid #03a9f4; border-radius: 8px;}";
echo ".box-header {font-weight: bold; margin-bottom: 5px;}";
echo ".log {background-color: #fffde7; padding: 20px; border-radius: 8px; font-size: 1em; color: #f57f17; margin-bottom: 20px;}";
echo "footer {text-align: center; padding: 20px; margin-top: 30px; background-color: #0288d1; color: #fff; font-size: 0.9em;}";
echo "footer p {margin: 0;}";
echo "@media (max-width: 768px) { body {padding: 10px;} .section {padding: 15px;} }";
echo "</style>";
echo "</head>";
echo "<body>";

echo "<div class='container'>";
echo "<h1>Mapping Publisher Local dengan Iklan Partner AdNetwork - Check Rate</h1>";
echo "<div class='info'>Proses ini akan mencocokkan harga iklan dari publisher lokal dengan budget yang diberikan oleh partner AdNetwork. Jika harga tidak cocok, iklan akan ditolak otomatis.</div>";

include("../db.php");

$mysqli = new mysqli($servername_db, $username_db, $password_db, $dbname_db);

// Check connection
if ($mysqli->connect_error) {
    die("Connection failed: " . $mysqli->connect_error);
}

// 1. Check `rate_text_ads` in `publishers_site`, add 50% margin, then check `mapping_advertisers_ads_publishers_site`
echo "<div class='section'>";
echo "<h2 class='title'>Tahap 1: Cek Rate Iklan Publisher Lokal</h2>";
echo "<div class='log'>Sistem akan menambahkan margin 50% pada rate yang diberikan oleh publisher dan dibandingkan dengan budget dari partner.</div>";

// Step 1: Fetch `rate_text_ads` from `publishers_site`
$sql = "SELECT id, rate_text_ads, publishers_local_id, site_domain FROM publishers_site";
$result = $mysqli->query($sql);

if ($result->num_rows > 0) {
    while ($row = $result->fetch_assoc()) {
        $publishers_site_id = $row['id'];
        $site_domain = $row['site_domain'];
        $rate_text_ads = $row['rate_text_ads'];
        $rate_with_margin = $rate_text_ads * 1.5;

        echo "<div class='box'>";
        echo "<div class='box-header'>Website Publisher: <strong>$site_domain</strong></div>";
        echo "<p>Harga Publisher: Rp <strong>$rate_text_ads</strong></p>";
        echo "<p>Harga Jual dengan Margin: Rp <strong>$rate_with_margin</strong></p>";

        // Step 2: Check if there is data in `mapping_advertisers_ads_publishers_site`
        $sql_mapping = "SELECT id, budget_per_click_textads, title_ads, ads_providers_name
                        FROM mapping_advertisers_ads_publishers_site 
                        WHERE publishers_local_id = {$row['publishers_local_id']} 
                        AND site_domain = '$site_domain'";

        $result_mapping = $mysqli->query($sql_mapping);

        if ($result_mapping->num_rows > 0) {
            while ($mapping_row = $result_mapping->fetch_assoc()) {
                $budget_adv = $mapping_row['budget_per_click_textads'];
                $title_ads = $mapping_row['title_ads'];
                $ads_providers_name = $mapping_row['ads_providers_name'];

                echo "<p>Judul Iklan: <strong>$title_ads</strong> ($ads_providers_name)</p>";
                echo "<p>Alokasi Budget Advertiser: Rp <strong>$budget_adv</strong> vs Harga Jual Publisher + Margin: Rp <strong>$rate_with_margin</strong></p>";

                if ($rate_with_margin > $budget_adv) {
                    echo "<p class='highlight'>Tolak Automatis: Harga Tidak Cocok</p>";

                    // Step 4: Update `is_approved_by_advertiser` and set rejection reason
                    $sql_update = "UPDATE mapping_advertisers_ads_publishers_site 
                                   SET is_approved_by_advertiser = 0,
                                       reasons_rejected_by_advertiser = 'out of budget',
                                       rate_text_ads = $rate_with_margin
                                   WHERE id = {$mapping_row['id']}";
                    $mysqli->query($sql_update);
                } else {
                    echo "<p class='match'>Oke: Harga Cocok</p>";
                }
            }
        } else {
            echo "<p>Data mapping tidak ditemukan untuk publisher ini.</p>";
        }
        echo "</div>";
    }
}
echo "</div>";

// 2. Check `budget_per_click_textads` in `advertisers_ads_partners`
echo "<div class='section'>";
echo "<h2 class='title'>Tahap 2: Cek Budget per Click dari Partner</h2>";
echo "<div class='log'>Sistem memeriksa budget per click yang diberikan oleh partner AdNetwork, kemudian mencocokkannya dengan rate iklan dari publisher lokal.</div>";

$sql = "SELECT local_ads_id, budget_per_click_textads, title_ads, landingpage_ads, providers_domain_url
        FROM advertisers_ads_partners";
$result = $mysqli->query($sql);

if ($result->num_rows > 0) {
    while ($row = $result->fetch_assoc()) {
        $local_ads_id = $row['local_ads_id'];
        $budget_per_click_textads = $row['budget_per_click_textads'];
        $title_ads = $row['title_ads'];
        $landingpage_ads = $row['landingpage_ads'];

        echo "<div class='box'>";
        echo "<div class='box-header'>Judul Iklan: <strong>$title_ads</strong></div>";
        echo "<p>Budget per Click: Rp <strong>$budget_per_click_textads</strong></p>";
        echo "<p>Landing Page: <a href='$landingpage_ads' target='_blank'>$landingpage_ads</a></p>";

        $sql_mapping = "SELECT id, rate_text_ads, site_domain
                        FROM mapping_advertisers_ads_publishers_site 
                        WHERE local_ads_id = $local_ads_id";

        $result_mapping = $mysqli->query($sql_mapping);

        if ($result_mapping->num_rows > 0) {
            while ($mapping_row = $result_mapping->fetch_assoc()) {
                $rate_text_ads = $mapping_row['rate_text_ads'];
                $site_domain = $mapping_row['site_domain'];

                echo "<p>Rate Text Ads Publisher $site_domain: Rp <strong>$rate_text_ads</strong></p>";

                if ($budget_per_click_textads < $rate_text_ads) {
                    echo "<p class='highlight'>Tolak Automatis: Budget Tidak Memenuhi Syarat</p>";

                    // Step 4: Update `is_approved_by_publisher` and set rejection reason
                    $sql_update = "UPDATE mapping_advertisers_ads_publishers_site 
                                   SET is_approved_by_publisher = 0,
                                       reasons_rejected_by_publisher = 'out of budget',
                                       budget_per_click_textads = $budget_per_click_textads
                                   WHERE id = {$mapping_row['id']}";
                    $mysqli->query($sql_update);
                } else {
                    echo "<p class='match'>Oke: Harga Cocok</p>";
                }
            }
        } else {
            echo "<p>Data mapping tidak ditemukan untuk iklan ini.</p>";
        }
        echo "</div>";
    }
}
echo "</div>";

// Close the database connection
$mysqli->close();

echo "<footer><p>&copy; 2024</p></footer>";
echo "</div>";
echo "</body>";
echo "</html>";
?>
