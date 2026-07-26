<?php
/*
cronjob/mapping_ads_publisher.php 
*/

// Database connection
echo "<!DOCTYPE html>
<html lang='en'>
<head>
    <meta charset='UTF-8'>
    <meta name='viewport' content='width=device-width, initial-scale=1.0'>
    <title>Mapping Ads to Publishers</title>
    <link href='https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/css/bootstrap.min.css' rel='stylesheet'>
</head>
<body>
<div class='container'>
    <h1 class='mt-5 text-primary'>Mapping Publisher Local dengan Iklan Lokal</h1>
    <div class='alert alert-info' role='alert'>
        Tahap 1: Melakukan Cek Advertiser yang publish = 1 dan expired = 0
    </div>";

include("../db.php");
include("../function.php");
$mysqli = new mysqli($servername_db, $username_db, $password_db, $dbname_db);

// Check connection
if ($mysqli->connect_error) {
    die("<div class='alert alert-danger' role='alert'>Connection failed: " . $mysqli->connect_error . "</div>");
}

// Ambil data dari tabel advertisers_ads yang ispublished = 1 dan is_expired = 0
$sql_ads = "SELECT * FROM advertisers_ads WHERE ispublished = 1 AND is_expired = 0";

 echo "<div class='alert alert-success'>sql_ads: ". $sql_ads."</div>";

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
        $is_expired = $row_ads['is_expired'];
        $is_paused = $row_ads['is_paused'];
        $expired_date = $row_ads['expired_date'];
        $ispublished = $row_ads['ispublished'];
        $is_paid = $row_ads['is_paid'];

        // Status iklan saat ini di `advertisers_ads` — meski loop ini sendiri
        // sudah difilter ispublished=1 AND is_expired=0 (lihat $sql_ads di
        // atas), status paid/paused tetap perlu ditampilkan karena tidak ikut
        // difilter di query itu.
        $status_paid = ((int) $is_paid === 1) ? "<span class='badge badge-success'>Paid</span>" : "<span class='badge badge-danger'>Unpaid</span>";
        $status_published = ((int) $ispublished === 1) ? "<span class='badge badge-success'>Published</span>" : "<span class='badge badge-secondary'>Unpublished</span>";
        $status_expired = ((int) $is_expired === 1) ? "<span class='badge badge-danger'>Expired</span>" : "<span class='badge badge-success'>Active</span>";
        $status_paused = ((int) $is_paused === 1) ? "<span class='badge badge-warning'>Paused</span>" : "<span class='badge badge-success'>Resumed</span>";
        $status_summary = "$status_paid $status_published $status_expired $status_paused";

        echo "<div class='card my-3'>
                <div class='card-body'>
                    <h3 class='card-title text-success'>Judul Iklan: $title_ads</h3>
                    <ul class='list-group'>
                        <li class='list-group-item'>Local_ads_id: $local_ads_id</li>
                        <li class='list-group-item'>Landingpage: $landingpage_ads</li>
                        <li class='list-group-item'>Budget per Click: Rp $budget_per_click_textads</li>
                        <li class='list-group-item'>Providers Name: $ads_providers_name</li>
                        <li class='list-group-item'>Status (dari table <code>advertisers_ads</code>): $status_summary</li>
                    </ul>
                </div>
              </div>";

        // Ambil data dari tabel publishers_site
        $sql_site = "SELECT * FROM publishers_site";

 echo "<div class='alert alert-success'>sql_ads: ". $sql_site."</div>";


        echo "<h4 class='text-primary'>Pasangkan Iklan ini $landingpage_ads ke publisher</h4>";
        $result_site = $mysqli->query($sql_site);

        if ($result_site->num_rows > 0) {
            while($row_site = $result_site->fetch_assoc()) {
                $publishers_site_local_id = $row_site['id'];
                $rate_text_ads = $row_site['rate_text_ads'];
                $publishers_local_id = $row_site['publishers_local_id'];
                $site_name = $row_site['site_name'];
                $site_domain = $row_site['site_domain'];
                $site_desc = $row_site['site_desc'];
                $site_desc = str_replace("'","",$site_desc);

                $pubs_providers_name = $row_site['providers_name'];
                $pubs_providers_domain_url = $row_site['providers_domain_url'];

                echo "<div class='alert alert-secondary'>
                        <h5>Publisher Site: $site_name</h5>
                        <ul>
                            <li>Rate Text Ads: Rp $rate_text_ads</li>
                            <li>Providers Name: $pubs_providers_name</li>
                        </ul>
                      </div>";

                // Tambahkan markup 50% pada rate_text_ads
                $rate_text_ads_with_markup = $rate_text_ads * 1.5;
                $revenue_publishers = $rate_text_ads;
                echo "<p><strong>Rate dengan markup: Rp $rate_text_ads_with_markup</strong></p>";

                // Cek apakah budget_per_click_textads memenuhi syarat
                if ($budget_per_click_textads >= $rate_text_ads_with_markup) {
                    echo "<div class='alert alert-success'>Oke, Harga Cocok. ads: $landingpage_ads untuk site  $site_domain </div>";

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

                    echo "<div class='alert alert-success'>check: local_ads_id=$local_ads_id, publishers_site_local_id=$publishers_site_local_id, ads_providers_domain_url=$ads_providers_domain_url</div>";


                    if ($check_result->num_rows > 0) {
                        // Update data jika sudah ada
                        echo "<div class='alert alert-warning'>Data sudah ada, melakukan update.</div>";
                        $update_stmt = $mysqli->prepare(
                            "UPDATE mapping_advertisers_ads_publishers_site
                             SET owner_advertisers_id = ?,
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
                                 revenue_publishers = ?,
                                 budget_per_click_textads = ?
                             WHERE local_ads_id = ?
                               AND publishers_site_local_id = ?
                               AND ads_providers_domain_url = ?"
                        );
                        $update_stmt->bind_param(
                            "isssssssiiisddiis",
                            $advertisers_id, $title_ads, $description_ads, $landingpage_ads, $image_url,
                            $site_name, $site_domain, $site_desc, $ispublished, $is_paused, $is_expired,
                            $expired_date, $revenue_publishers, $budget_per_click_textads,
                            $local_ads_id, $publishers_site_local_id, $ads_providers_domain_url
                        );
                        echo "<div class='alert alert-info'>Update mapping untuk local_ads_id=$local_ads_id, site=$publishers_site_local_id</div>";
                        $update_stmt->execute();
                        $update_stmt->close();
                    } else {
                        // Insert data baru jika belum ada
                        $insert_stmt = $mysqli->prepare(
                            "INSERT INTO mapping_advertisers_ads_publishers_site
                                (rate_text_ads, budget_per_click_textads, local_ads_id,
                                 publishers_site_local_id, pubs_providers_name, pubs_providers_domain_url,
                                 ads_providers_name, ads_providers_domain_url, owner_advertisers_id, title_ads,
                                 description_ads, landingpage_ads, image_url, publishers_local_id, site_name,
                                 site_domain, site_desc, is_published, is_paused, is_expired,
                                 is_approved_by_publisher, is_approved_by_advertiser, approval_date_publisher,
                                 approval_date_advertiser, revenue_publishers)
                             VALUES (?, ?, ?,
                                     ?, ?, ?,
                                     ?, ?, ?, ?,
                                     ?, ?, ?, ?, ?,
                                     ?, ?, 1, ?, 0,
                                     1, 1, NOW(),
                                     NOW(), ?)"
                        );
                        $insert_stmt->bind_param(
                            "ddiissssissssisssid",
                            $rate_text_ads_with_markup, $budget_per_click_textads, $local_ads_id,
                            $publishers_site_local_id, $pubs_providers_name, $pubs_providers_domain_url,
                            $ads_providers_name, $ads_providers_domain_url, $advertisers_id, $title_ads,
                            $description_ads, $landingpage_ads, $image_url, $publishers_local_id, $site_name,
                            $site_domain, $site_desc, $is_paused, $revenue_publishers
                        );
                        echo "<div class='alert alert-info'>Insert mapping baru untuk local_ads_id=$local_ads_id, site=$publishers_site_local_id</div>";
                        $insert_stmt->execute();
                        $insert_stmt->close();
                    }
                } else {
                    echo "<div class='alert alert-danger'>Harga Tidak Cocok. ads $landingpage_ads di site $site_domain  </div>";
                }
            }
        }
    }
}

echo "</div></body></html>";




echo "<h2>SELESAI</h2>";


// Close connection
$mysqli->close();
?>
