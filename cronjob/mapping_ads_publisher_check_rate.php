<?php
/*

cronjob/mapping_ads_publisher_check_rate.php 

*/
// Database connection
include("../db.php");

$mysqli = new mysqli($servername_db, $username_db, $password_db, $dbname_db);

// Check connection
if ($mysqli->connect_error) {
    die("Connection failed: " . $mysqli->connect_error);
}

echo "<!DOCTYPE html>";
echo "<html lang='en'>";
echo "<head>";
echo "<meta charset='UTF-8'>";
echo "<meta name='viewport' content='width=device-width, initial-scale=1.0'>";
echo "<title>Mapping Ads Publisher Check Rate</title>";
echo "<style>";
echo "body {font-family: Arial, sans-serif; margin: 20px;}";
echo "h1 {color: #4CAF50;}";
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

echo "<h1>Mapping Ads Publisher Check Rate Process</h1>";
echo "<div class='section'>";
echo "<h2>Tahap 1: Memeriksa Rate Iklan dari Publisher</h2>";

// Step 1: Fetch `rate_text_ads` from `publishers_site`

$sql = "SELECT id, rate_text_ads, publishers_local_id , site_domain FROM publishers_site";
$result = $mysqli->query($sql);

if ($result->num_rows > 0) {
    echo "<table>";
    echo "<tr><th>Website Publisher</th><th>Harga Publisher (Rp)</th><th>Harga Jual + Margin (Rp)</th></tr>";
    while($row = $result->fetch_assoc()) {
        $publishers_site_id = $row['id'];
        $site_domain = $row['site_domain'];
        $rate_text_ads = $row['rate_text_ads'];
        $rate_with_margin = $rate_text_ads * 1.5;

        echo "<tr><td>{$site_domain}</td><td>{$rate_text_ads}</td><td>{$rate_with_margin}</td></tr>";

        // Step 2: Check if there is data in `mapping_advertisers_ads_publishers_site`
        $sql_mapping = "SELECT id, budget_per_click_textads, title_ads, ads_providers_name, is_published, is_expired, landingpage_ads 
                        FROM mapping_advertisers_ads_publishers_site 
                        WHERE publishers_local_id = {$row['publishers_local_id']} 
                        AND site_domain = '$site_domain'";

        $result_mapping = $mysqli->query($sql_mapping);
        if ($result_mapping->num_rows > 0) {
            while ($mapping_row = $result_mapping->fetch_assoc()) {
                $budget_adv = $mapping_row['budget_per_click_textads'];
                $title_ads = $mapping_row['title_ads'];
                $ads_providers_name = $mapping_row['ads_providers_name'];
                $is_published = $mapping_row['is_published'];
                $landingpage_ads = $mapping_row['landingpage_ads'];

                echo "<div class='section'>";
                echo "<p><strong>Judul Iklan:</strong> {$title_ads} ({$ads_providers_name})</p>";
                echo "<p><strong>Website Publisher:</strong> {$site_domain}</p>";
                echo "<p><strong>Landing Page Iklan:</strong> {$landingpage_ads}</p>";
                echo "<p><strong>Alokasi Budget Advertiser:</strong> Rp {$budget_adv} <> Rp {$rate_with_margin} Harga Jual Publisher + Margin</p>";

                if ($rate_with_margin > $mapping_row['budget_per_click_textads']) {
                    echo "<p class='highlight'>Tolak Automatis, Harga Tidak Cocok.</p>";

                    // Step 4: Update `is_approved_by_advertiser` and set rejection reason
                    $sql_update = "UPDATE mapping_advertisers_ads_publishers_site 
                                   SET is_approved_by_advertiser = 0, 
                                       reasons_rejected_by_advertiser = 'out of budget',
                                       rate_text_ads = $rate_with_margin
                                   WHERE id = {$mapping_row['id']}";
                    $mysqli->query($sql_update);
                } else {
                    echo "<p class='match'>Oke, Harga Cocok.</p>";
                }
                echo "</div>";
            }
        }
    }
    echo "</table>";
}

echo "<h2>Tahap 2: Memeriksa Budget Iklan dari Advertiser</h2>";

// Step 1: Fetch `budget_per_click_textads` and `local_ads_id` from `advertisers_ads`
$sql = "SELECT local_ads_id, title_ads, landingpage_ads, budget_per_click_textads FROM advertisers_ads";
$result = $mysqli->query($sql);

if ($result->num_rows > 0) {
    echo "<table>";
    echo "<tr><th>Judul Iklan</th><th>Landing Page</th><th>Budget Advertiser (Rp)</th></tr>";
    while($row = $result->fetch_assoc()) {
        $local_ads_id = $row['local_ads_id'];
        $budget_per_click_textads = $row['budget_per_click_textads'];
        $title_ads = $row['title_ads'];
        $landingpage_ads = $row['landingpage_ads'];

        echo "<tr><td>{$title_ads}</td><td>{$landingpage_ads}</td><td>{$budget_per_click_textads}</td></tr>";

        // Step 2: Fetch `rate_text_ads` from `mapping_advertisers_ads_publishers_site`
        $sql_mapping = "SELECT id, rate_text_ads, site_domain, title_ads, ads_providers_name 
                        FROM mapping_advertisers_ads_publishers_site 
                        WHERE local_ads_id = $local_ads_id";
        $result_mapping = $mysqli->query($sql_mapping);

        if ($result_mapping->num_rows > 0) {
            while ($mapping_row = $result_mapping->fetch_assoc()) {
                $rate_text_ads = $mapping_row['rate_text_ads'];
                $site_domain = $mapping_row['site_domain'];
                $title_ads2 = $mapping_row['title_ads'];
                $ads_providers_name = $mapping_row['ads_providers_name'];

                echo "<div class='section'>";
                echo "<p><strong>Budget {$title_ads2} ({$ads_providers_name}):</strong> Rp {$budget_per_click_textads} <> Rp {$rate_text_ads} - {$site_domain}</p>";

                if ($budget_per_click_textads < $rate_text_ads) {
                    echo "<p class='highlight'>Tolak Automatis, Harga Tidak Cocok.</p>";

                    // Step 4: Update `is_approved_by_publisher` and set rejection reason
                    $sql_update = "UPDATE mapping_advertisers_ads_publishers_site 
                                   SET is_approved_by_publisher = 0, 
                                       reasons_rejected_by_publisher = 'out of budget',
                                       budget_per_click_textads = $budget_per_click_textads
                                   WHERE id = {$mapping_row['id']}";
                    $mysqli->query($sql_update);
                } else {
                    echo "<p class='match'>Oke, Harga Cocok.</p>";
                }
                echo "</div>";
            }
        }
    }
    echo "</table>";
}

// Close the database connection
$mysqli->close();

echo "</body>";
echo "</html>";
?>
