<?php
    /*
    cronjob/rekap_harian_publisher.php
    */
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Penjelasan Alur Kode Rekap Harian Publisher</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            line-height: 1.6;
            margin: 20px;
            background-color: #f4f4f9;
        }
        .container {
            max-width: 1000px;
            margin: auto;
            background: #fff;
            padding: 20px;
            border-radius: 8px;
            box-shadow: 0 0 10px rgba(0,0,0,0.1);
        }
        h1, h2 {
            color: #333;
            border-bottom: 2px solid #333;
            padding-bottom: 10px;
        }
        code {
            background-color: #f4f4f4;
            padding: 5px;
            border-radius: 4px;
        }
        .info {
            background-color: #e2f0d9;
            padding: 10px;
            margin: 20px 0;
            border-left: 5px solid #28a745;
        }
        .warning {
            background-color: #fff3cd;
            padding: 10px;
            margin: 20px 0;
            border-left: 5px solid #ffc107;
        }
    </style>
</head>
<body>

<div class="container">
    <h1>Penjelasan Alur Kode Rekap Harian Publisher</h1>

    <?php
    /*
    cronjob/rekap_harian_publisher.php
    */

    include("../db.php");

     $current_site_revenue=0;
    $current_site_revenue_from_partner=0;
   

    // Membuat koneksi PDO ke database
    $pdo = new PDO("mysql:host=$servername_db;dbname=$dbname_db;charset=utf8mb4", $username_db, $password_db);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    // Menyiapkan nama tabel untuk rekap harian publisher
    $rekap_table = 'rekap_harian_publishers';

    // Query untuk mendapatkan rekap harian dari tabel ad_clicks
    $sql = "
    SELECT 
        DATE(`click_time`) AS rekap_date, 
        `pub_id`, 
        `pubs_providers_domain_url`, 
         `ads_providers_domain_url`,
        SUM(`revenue_publishers`) AS total_revenue_publishers
    FROM `ad_clicks` WHERE isaudit = 1 AND is_reject = 0 
    GROUP BY 
        rekap_date, 
        `pub_id`, 
        `pubs_providers_domain_url`, 
        `ads_providers_domain_url`
    ";

    echo "<h2>Menjalankan query untuk mendapatkan rekap harian publisher</h2>";
    echo "<code>SELECT DATE(`click_time`) AS rekap_date, `pub_id`, `pubs_providers_domain_url`, `ads_providers_domain_url`, SUM(`revenue_publishers`) AS total_revenue_publishers FROM `ad_clicks` WHERE isaudit = 1 AND is_reject = 0   BY rekap_date, pub_id, pubs_providers_domain_url, ads_providers_domain_url</code>";

    // Jalankan query
    $results = $pdo->query($sql)->fetchAll(PDO::FETCH_ASSOC);

    echo "<h2>Memproses hasil query untuk Insert atau Update</h2>";

    // Insert atau Update ke dalam tabel rekap
    foreach ($results as $row) {
        $rekap_date = $row['rekap_date'];
        $pub_id = $row['pub_id'];
        $pubs_providers_domain_url = $row['pubs_providers_domain_url'];
        $ads_providers_domain_url = $row['ads_providers_domain_url'];
        $total_revenue_publishers = $row['total_revenue_publishers'];

        if ($ads_providers_domain_url!=$pubs_providers_domain_url) {
            $current_site_revenue_from_partner = $current_site_revenue_from_partner + $total_revenue_publishers; 
        }
        if ($ads_providers_domain_url==$pubs_providers_domain_url) {
            $current_site_revenue = $current_site_revenue + $total_revenue_publishers; 
        }

        echo "<div class='info'>";
        echo "<h3>Data yang sedang diproses:</h3>";
        echo "<ul>
                <li><b>Rekap Date:</b> $rekap_date</li>
                <li><b>Pub ID:</b> $pub_id</li>
                <li><b>Pubs Providers Domain URL:</b> $pubs_providers_domain_url</li>
                <li><b>Ads Providers Domain URL:</b> $ads_providers_domain_url</li>
                <li><b>Total Revenue Publishers:</b> $total_revenue_publishers</li>
                <li><b>Total Revenue Local:</b> $current_site_revenue</li>
                <li><b>Total Revenue Partnr :</b> $current_site_revenue_from_partner</li>
                
              </ul>";
        echo "</div>";

        // Cek apakah data sudah ada
        $checkSql = "
            SELECT COUNT(*) FROM $rekap_table 
            WHERE rekap_date = :rekap_date 
            AND pub_id = :pub_id 
            AND pubs_providers_domain_url = :pubs_providers_domain_url 
            AND ads_providers_domain_url = :ads_providers_domain_url
        ";
        $stmt = $pdo->prepare($checkSql);
        $stmt->execute([
            ':rekap_date' => $rekap_date,
            ':pub_id' => $pub_id,
            ':pubs_providers_domain_url' => $pubs_providers_domain_url,
            ':ads_providers_domain_url' => $ads_providers_domain_url,
        ]);

        $exists = $stmt->fetchColumn();

        if ($exists) {
            // Jika data sudah ada, lakukan update
            echo "<div class='warning'>";
            echo "<h3>Data ditemukan. Melakukan Update:</h3>";
            echo "<p>Mengupdate kolom <b>total_revenue_publishers</b> dengan nilai: $total_revenue_publishers.</p>";

            $updateSql = "
                UPDATE $rekap_table 
                SET total_revenue_publishers = :total_revenue_publishers 
                WHERE rekap_date = :rekap_date 
                AND pub_id = :pub_id 
                AND pubs_providers_domain_url = :pubs_providers_domain_url 
                AND ads_providers_domain_url = :ads_providers_domain_url
            ";
            $stmt = $pdo->prepare($updateSql);
            $stmt->execute([
                ':total_revenue_publishers' => $total_revenue_publishers,
                ':rekap_date' => $rekap_date,
                ':pub_id' => $pub_id,
                ':pubs_providers_domain_url' => $pubs_providers_domain_url,
                ':ads_providers_domain_url' => $ads_providers_domain_url,
            ]);

        

            echo "<p>Data berhasil diupdate.</p>";
            echo "</div>";
        } else {
            // Jika belum ada, lakukan insert
            echo "<div class='info'>";
            echo "<h3>Data tidak ditemukan. Melakukan Insert:</h3>";
            echo "<p>Menyimpan data baru ke dalam tabel rekap_harian_publishers.</p>";

            $insertSql = "
                INSERT INTO $rekap_table (
                    rekap_date, pub_id, pubs_providers_domain_url,  ads_providers_domain_url, total_revenue_publishers
                ) VALUES (
                    :rekap_date, :pub_id, :pubs_providers_domain_url, :ads_providers_domain_url, :total_revenue_publishers
                )
            ";
            $stmt = $pdo->prepare($insertSql);
            $stmt->execute([
                ':rekap_date' => $rekap_date,
                ':pub_id' => $pub_id,
                ':pubs_providers_domain_url' => $pubs_providers_domain_url,
                ':ads_providers_domain_url' => $ads_providers_domain_url,
                ':total_revenue_publishers' => $total_revenue_publishers,
            ]);

            echo "<p>Data berhasil diinsert.</p>";
            echo "</div>";
        }
    }

    echo "<p>Rekap harian publisher berhasil diperbarui.</p>";
    ?>

</div>

</body>
</html>
