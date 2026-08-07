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


 echo "<div class='alert alert-success'>sql: ". $sql."</div>";

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
        $mapping_stmt = $mysqli->prepare(
            "SELECT id, budget_per_click_textads, title_ads, ads_providers_name, is_published, is_expired, is_paused, landingpage_ads
             FROM mapping_advertisers_ads_publishers_site
             WHERE publishers_local_id = ?
               AND site_domain = ?"
        );
        $mapping_stmt->bind_param("is", $row['publishers_local_id'], $site_domain);
        $mapping_stmt->execute();
        $result_mapping = $mapping_stmt->get_result();

        echo "<div class='alert alert-success'>Cek mapping untuk publishers_local_id={$row['publishers_local_id']}, site_domain=$site_domain</div>";

        if ($result_mapping->num_rows > 0) {
            while ($mapping_row = $result_mapping->fetch_assoc()) {
                $budget_adv = $mapping_row['budget_per_click_textads'];
                $title_ads = $mapping_row['title_ads'];
                $ads_providers_name = $mapping_row['ads_providers_name'];
                $is_published = $mapping_row['is_published'];
                $landingpage_ads = $mapping_row['landingpage_ads'];

                // Status dari cache tabel mapping (bukan `is_paid`, itu flag
                // level advertiser yang tidak disalin ke mapping).
                $status_published = ((int) $mapping_row['is_published'] === 1) ? "<span class='match'>Published</span>" : "<span class='highlight'>Unpublished</span>";
                $status_expired = ((int) $mapping_row['is_expired'] === 1) ? "<span class='highlight'>Expired</span>" : "<span class='match'>Active</span>";
                $status_paused = ((int) $mapping_row['is_paused'] === 1) ? "<span class='highlight'>Paused</span>" : "<span class='match'>Resumed</span>";
                $status_summary = "$status_published | $status_expired | $status_paused";
                $status_ok = ((int) $mapping_row['is_published'] === 1)
                          && ((int) $mapping_row['is_expired'] === 0)
                          && ((int) $mapping_row['is_paused'] === 0);

                echo "<div class='section'>";
                echo "<p><strong>Judul Iklan:</strong> {$title_ads} ({$ads_providers_name})</p>";
                echo "<p><strong>Website Publisher:</strong> {$site_domain}</p>";
                echo "<p><strong>Landing Page Iklan:</strong> {$landingpage_ads}</p>";
                echo "<p><strong>Status Iklan (dari table <code>mapping_advertisers_ads_publishers_site</code>, cache — bukan status live di <code>advertisers_ads</code>):</strong> {$status_summary}</p>";
                if (!$status_ok) {
                    echo "<div class='alert alert-warning' style='background:#fff3cd;border:1px solid #ffe69c;color:#664d03;padding:10px;border-radius:5px;margin:10px 0;'>
                            <strong>&#9888; Peringatan:</strong> Status iklan ini tidak aktif ({$status_summary}) di cache mapping, tapi pengecekan rate di bawah tetap berjalan.
                          </div>";
                }
                echo "<p><strong>Alokasi Budget Advertiser:</strong> Rp {$budget_adv} <> Rp {$rate_with_margin} Harga Jual Publisher + Margin</p>";

                if ($rate_with_margin > $mapping_row['budget_per_click_textads']) {
                    echo "<p class='highlight'>Tolak Automatis, Harga Tidak Cocok.</p>";

                    // Step 4: Update `is_approved_by_advertiser` and set rejection reason
                    $update_stmt = $mysqli->prepare(
                        "UPDATE mapping_advertisers_ads_publishers_site
                         SET reasons_rejected_by_advertiser = CASE
                                 WHEN is_approved_by_advertiser = 1 OR reasons_rejected_by_advertiser = 'out of budget'
                                 THEN 'out of budget' ELSE reasons_rejected_by_advertiser
                             END,
                             is_approved_by_advertiser = 0,
                             rate_text_ads = ?
                         WHERE id = ?"
                    );
                    $update_stmt->bind_param("di", $rate_with_margin, $mapping_row['id']);
                    $update_stmt->execute();
                    $update_stmt->close();
                } else {
                    echo "<p class='match'>Oke, Harga Cocok.</p>";

                    // Harga sudah cocok lagi (bisa jadi sebelumnya pernah ditolak) —
                    // setujui ulang secara otomatis dan segarkan cache rate_text_ads,
                    // supaya mapping tidak nyangkut ditolak selamanya begitu kondisi
                    // harga membaik lagi.
                    $update_stmt = $mysqli->prepare(
                        "UPDATE mapping_advertisers_ads_publishers_site
                         SET is_approved_by_advertiser = 1,
                             reasons_rejected_by_advertiser = '',
                             rate_text_ads = ?
                         WHERE id = ?
                           AND is_approved_by_advertiser = 0
                           AND reasons_rejected_by_advertiser = 'out of budget'"
                    );
                    $update_stmt->bind_param("di", $rate_with_margin, $mapping_row['id']);
                    $update_stmt->execute();
                    $update_stmt->close();
                }
                echo "</div>";
            }
        }
        $mapping_stmt->close();
    }
    echo "</table>";
}

echo "<h2>Tahap 2: Memeriksa Budget Iklan dari Advertiser</h2>";

// Step 1: Fetch `budget_per_click_textads` and `local_ads_id` from `advertisers_ads`
$sql = "SELECT local_ads_id, providers_domain_url, title_ads, landingpage_ads, budget_per_click_textads,
               is_paid, ispublished, is_expired, expired_date, is_paused, paused_date
        FROM advertisers_ads";
$result = $mysqli->query($sql);

if ($result->num_rows > 0) {
    echo "<table>";
    echo "<tr><th>Judul Iklan</th><th>Landing Page</th><th>Budget Advertiser (Rp)</th><th>Status (dari table <code>advertisers_ads</code>)</th></tr>";
    while($row = $result->fetch_assoc()) {
        $local_ads_id = $row['local_ads_id'];
        // local_ads_id hanyalah PK auto-increment per server (dimulai dari 1
        // di server manapun) — tanpa menyertakan ads_providers_domain_url,
        // lookup mapping di bawah bisa saja tabrakan dengan mapping milik
        // iklan PARTNER yang kebetulan punya local_ads_id sama.
        $ads_providers_domain_url = $row['providers_domain_url'];
        $budget_per_click_textads = $row['budget_per_click_textads'];
        $title_ads = $row['title_ads'];
        $landingpage_ads = $row['landingpage_ads'];

        // Status iklan saat ini di sumbernya (`advertisers_ads`) — bukan cache
        // di tabel mapping — supaya jelas iklan mana yang sebenarnya sedang
        // tidak aktif (belum dibayar/unpublished/expired/paused) meski tetap
        // ikut dicek harganya oleh Tahap 2 ini.
        $status_paid = ((int) $row['is_paid'] === 1) ? "<span class='match'>Paid</span>" : "<span class='highlight'>Unpaid</span>";
        $status_published = ((int) $row['ispublished'] === 1) ? "<span class='match'>Published</span>" : "<span class='highlight'>Unpublished</span>";
        $status_expired = ((int) $row['is_expired'] === 1) ? "<span class='highlight'>Expired</span>" : "<span class='match'>Active</span>";
        $status_paused = ((int) $row['is_paused'] === 1) ? "<span class='highlight'>Paused</span>" : "<span class='match'>Resumed</span>";
        $status_summary = "$status_paid | $status_published | $status_expired | $status_paused";
        $status_ok = ((int) $row['is_paid'] === 1)
                  && ((int) $row['ispublished'] === 1)
                  && ((int) $row['is_expired'] === 0)
                  && ((int) $row['is_paused'] === 0);

        echo "<tr><td>{$title_ads}</td><td>{$landingpage_ads}</td><td>{$budget_per_click_textads}</td><td>{$status_summary}</td></tr>";

        if (!$status_ok) {
            echo "<div class='alert alert-warning' style='background:#fff3cd;border:1px solid #ffe69c;color:#664d03;padding:10px;border-radius:5px;margin:10px 0;'>
                    <strong>&#9888; Peringatan:</strong> Iklan <strong>{$title_ads}</strong> berstatus tidak aktif di <code>advertisers_ads</code> ({$status_summary}), tapi pengecekan rate/budget di bawah tetap berjalan untuk semua mapping-nya.
                  </div>";
        }

        // Step 2: Fetch `rate_text_ads` from `mapping_advertisers_ads_publishers_site`
        // (difilter juga dengan ads_providers_domain_url supaya tidak
        // tercampur dengan mapping milik iklan partner yang local_ads_id-nya
        // kebetulan sama — lihat catatan di atas)
        $mapping_stmt2 = $mysqli->prepare(
            "SELECT id, rate_text_ads, site_domain, title_ads, ads_providers_name
             FROM mapping_advertisers_ads_publishers_site
             WHERE local_ads_id = ?
               AND ads_providers_domain_url = ?"
        );
        $mapping_stmt2->bind_param("is", $local_ads_id, $ads_providers_domain_url);
        $mapping_stmt2->execute();
        $result_mapping = $mapping_stmt2->get_result();

        if ($result_mapping->num_rows > 0) {
            while ($mapping_row = $result_mapping->fetch_assoc()) {
                $rate_text_ads = $mapping_row['rate_text_ads'];
                $site_domain = $mapping_row['site_domain'];
                $title_ads2 = $mapping_row['title_ads'];
                $ads_providers_name = $mapping_row['ads_providers_name'];

                echo "<div class='section'>";
                echo "<p><strong>Budget {$title_ads2} ({$ads_providers_name}):</strong> Rp {$budget_per_click_textads} <> Rp {$rate_text_ads} - {$site_domain}</p>";
                echo "<p><strong>Status Iklan (dari table <code>advertisers_ads</code>):</strong> {$status_summary}</p>";

                if (!$status_ok) {
                    // Status master (advertisers_ads) tidak aktif — sinkronkan
                    // langsung ke cache mapping (is_published/is_expired/is_paused
                    // beserta tanggalnya), supaya tidak menunggu push_sync_ads_expired.php
                    // dan skrip iklan (show_ads_native*.js.php) langsung melihat
                    // status yang benar dari cache-nya juga. `is_paid` tidak
                    // disinkronkan karena tabel mapping tidak punya kolom itu.
                    $sync_stmt = $mysqli->prepare(
                        "UPDATE mapping_advertisers_ads_publishers_site
                         SET is_published = ?,
                             is_expired = ?,
                             expired_date = ?,
                             is_paused = ?,
                             paused_date = ?
                         WHERE id = ?"
                    );
                    $sync_stmt->bind_param(
                        "iisisi",
                        $row['ispublished'], $row['is_expired'], $row['expired_date'],
                        $row['is_paused'], $row['paused_date'], $mapping_row['id']
                    );
                    $sync_stmt->execute();
                    $sync_stmt->close();
                    echo "<p class='highlight'>&#9888; Status mapping (id={$mapping_row['id']}) disinkronkan mengikuti status master advertisers_ads.</p>";
                }

                if ($budget_per_click_textads < $rate_text_ads) {
                    echo "<p class='highlight'>Tolak Automatis, Harga Tidak Cocok.</p>";

                    // Step 4: Update `is_approved_by_publisher` and set rejection reason
                    $update_stmt = $mysqli->prepare(
                        "UPDATE mapping_advertisers_ads_publishers_site
                         SET reasons_rejected_by_publisher = CASE
                                 WHEN is_approved_by_publisher = 1 OR reasons_rejected_by_publisher = 'out of budget'
                                 THEN 'out of budget' ELSE reasons_rejected_by_publisher
                             END,
                             is_approved_by_publisher = 0,
                             budget_per_click_textads = ?
                         WHERE id = ?"
                    );
                    $update_stmt->bind_param("di", $budget_per_click_textads, $mapping_row['id']);
                    $update_stmt->execute();
                    $update_stmt->close();
                } else {
                    echo "<p class='match'>Oke, Harga Cocok.</p>";

                    // Budget sudah cocok lagi — setujui ulang secara otomatis dan
                    // segarkan cache budget_per_click_textads, dengan alasan yang
                    // sama seperti sisi advertiser di atas.
                    $update_stmt = $mysqli->prepare(
                        "UPDATE mapping_advertisers_ads_publishers_site
                         SET is_approved_by_publisher = 1,
                             reasons_rejected_by_publisher = '',
                             budget_per_click_textads = ?
                         WHERE id = ?
                           AND is_approved_by_publisher = 0
                           AND reasons_rejected_by_publisher = 'out of budget'"
                    );
                    $update_stmt->bind_param("di", $budget_per_click_textads, $mapping_row['id']);
                    $update_stmt->execute();
                    $update_stmt->close();
                }
                echo "</div>";
            }
        }
        $mapping_stmt2->close();
    }
    echo "</table>";
}

// Close the database connection
$mysqli->close();

echo "</body>";
echo "</html>";
?>
