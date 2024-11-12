<?php
// Fungsi untuk melakukan rekap publisher revenue harian

// cronjob/rekapPublisherRevenueHarianPartner.php


include("../db.php");
include("../function.php");


// Database connection using MySQLi
$mysqli = new mysqli($servername_db, $username_db, $password_db, $dbname_db);
if ($mysqli->connect_error) {
    error_log("Database connection failed: " . $mysqli->connect_error);
    exit("Database connection failed.");
}

// Panggil fungsi rekapitulasi
rekapPublisherRevenueHarianPartner($mysqli);
updateSiteInfo($mysqli);
rekapTotalPublisherPartner($mysqli);

// Tutup koneksi
$mysqli->close();

function rekapPublisherRevenueHarianPartner($mysqli) {
    // Query untuk mengambil data yang akan direkap dari ad_clicks_partner
    $query = "
        SELECT 
            pub_id, 
            pubs_providers_domain_url,
            site_domain , 
            DATE(click_time) AS date_click,
            SUM(revenue_publishers) AS total_revenue_publishers,
            COUNT(*) AS total_clicks
        FROM ad_clicks_partner
        WHERE isaudit = 1 
        AND is_reject = 0
        GROUP BY pub_id, pubs_providers_domain_url, DATE(click_time)
    ";

echo "<br>query: ".$query;



    if ($result = $mysqli->query($query)) {
        // Loop untuk memproses setiap baris hasil query
        while ($row = $result->fetch_assoc()) {
            $pub_id = $row['pub_id'];
            $site_domain = $row['site_domain'];
            $pubs_providers_domain_url = $row['pubs_providers_domain_url'];
            $date_click = $row['date_click'];
            $total_revenue_publishers = $row['total_revenue_publishers'];
            $total_clicks = $row['total_clicks'];

            echo "<br>pub_id: ".$pub_id;
             echo "<br>site_domain: ".$site_domain;
            echo "<br>pubs_providers_domain_url: ".$pubs_providers_domain_url;
            echo "<br>date_click: ".$date_click;
            echo "<br>total_revenue_publishers: ".$total_revenue_publishers;
            echo "<br>total_clicks: ".$total_clicks;
            echo "<br>";

            // Cek apakah data sudah ada di tabel rekap
            $check_query = "
                SELECT id FROM rekap_publisher_revenue_harian_partner 
                WHERE pub_id = ? 
                AND pubs_providers_domain_url = ? 
                AND date_click = ?
            ";
            $check_query_p= str_replace("pub_id = ?", "pub_id = '".$pub_id."'", $check_query);
             $check_query_p= str_replace("pubs_providers_domain_url = ?", "pub_id = '".$pubs_providers_domain_url."'", $check_query_p);
               $check_query_p= str_replace("date_click = ?", "date_click = '".$date_click."'", $check_query_p);

             echo "<br>check_query_p: ".$check_query_p;

            $stmt_check = $mysqli->prepare($check_query);
            $stmt_check->bind_param("iss", $pub_id, $pubs_providers_domain_url, $date_click);
            $stmt_check->execute();
            $stmt_check->store_result();

             echo "<br>stmt_check->num_rows: ".$stmt_check->num_rows;

            if ($stmt_check->num_rows > 0) {
                // Jika data sudah ada, lakukan update
                $update_query = "
                    UPDATE rekap_publisher_revenue_harian_partner
                    SET total_revenue_publishers =  ?, 
                        total_clicks = ? 
                    WHERE pub_id = ? 
                    AND pubs_providers_domain_url = ? 
                    AND date_click = ?
                ";

                echo "<br>update_query: ".$update_query;
                $stmt_update = $mysqli->prepare($update_query);
                $stmt_update->bind_param("iiiss", $total_revenue_publishers, $total_clicks, $pub_id, $pubs_providers_domain_url, $date_click);

                 echo "<br>UPDATE rekap_publisher_revenue_harian_partner: pub_id: ".$pub_id;
            echo "<br>pubs_providers_domain_url: ".$pubs_providers_domain_url;
             echo "<br>total_clicks: ".$total_clicks;
            echo "<br>date_click: ".$date_click;
            echo "<br>";


                $stmt_update->execute();
                $stmt_update->close();
            } else {
                // Jika data belum ada, lakukan insert
                $insert_query = "
                    INSERT INTO rekap_publisher_revenue_harian_partner
                    (pub_id, pubs_providers_domain_url, date_click, total_revenue_publishers, total_clicks)
                    VALUES (?, ?, ?, ?, ?)
                ";

                 echo "<br>insert_query: ".$insert_query;
                $stmt_insert = $mysqli->prepare($insert_query);
                $stmt_insert->bind_param("issii", $pub_id, $pubs_providers_domain_url, $date_click, $total_revenue_publishers, $total_clicks);
                $stmt_insert->execute();
                $stmt_insert->close();
            }

            $stmt_check->close();
        }

        // Free result set
        $result->free();
    } else {
        echo "Error: " . $mysqli->error;
    }
}

// Fungsi untuk mengupdate site_name dan site_domain dari publishers_site_partners
function updateSiteInfo($mysqli) {
    // Query untuk mengupdate rekap_publisher_revenue_harian_partner berdasarkan data dari publishers_site_partners
    $update_query = "
        UPDATE rekap_publisher_revenue_harian_partner r
        JOIN publishers_site_partners p ON r.pub_id = p.id
        SET r.site_name = p.site_name, r.site_domain = p.site_domain
        WHERE r.pub_id = p.id
    ";
    
    if ($mysqli->query($update_query)) {
        echo "Site information updated successfully.";
    } else {
        echo "Error updating site information: " . $mysqli->error;
    }
}


?>
