<?php
// cronjob/push_sync_ads_expired.php

include("../db.php");

// Database connection settings
try {
    // Create a new PDO instance
    $pdo = new PDO("mysql:host=$servername_db;dbname=$dbname_db", $username_db, $password_db);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    // Query to select data from providers_partners table
   
    $stmt_providers = $pdo->query("SELECT api_endpoint, public_key, secret_key FROM providers_partners 
        WHERE is_hold = 0 ");
    $providers = $stmt_providers->fetchAll(PDO::FETCH_ASSOC);

    foreach ($providers as $provider) {
        // Construct the API URL by appending "/sync_ads/index.php" to the api_endpoint
        $api_url = $provider['api_endpoint'] . "/sync_ads/index.php";
       

echo "<h1>Tahap 1 Step 1: Melakukan Cek Advertiser yang expired = 1 pada 12 jam terakhir</h1>";


 echo "<br><h2>Data ini akan dilaporkan ke api_url: " . $api_url . "</h2><br>";

        // Query to select data from advertisers_ads table
         $sqlCheck="SELECT *
FROM advertisers_ads
WHERE is_expired = 1
  AND expired_date >= NOW() - INTERVAL 250 HOUR";
    //  echo "<br>sqlCheck: " . $sqlCheck . "<br>";

        $stmt_ads = $pdo->query($sqlCheck);
        $ads = $stmt_ads->fetchAll(PDO::FETCH_ASSOC);

        foreach ($ads as $ad) {
            // Data that will be sent to the API

            $local_ads_id = $ad['local_ads_id'];
            $title_ads = $ad['title_ads'];
            $providers_domain_url = $ad['providers_domain_url'];
            $expired_date = $ad['expired_date'];

            echo "<br>local_ads_id: " . $local_ads_id . "";
            echo "<br>title_ads: " . $title_ads . "";
            echo "<br>providers_domain_url: " . $providers_domain_url . "";
            echo "<br>expired_date: " . $expired_date . "";

            

            $data = array(
                'providers_name' => $ad['providers_name'],
                'providers_domain_url' => $ad['providers_domain_url'],
                'advertisers_id' => $ad['advertisers_id'],
                'local_ads_id' => $ad['local_ads_id'],
                'ispublished' => $ad['ispublished'],
                'title_ads' => $ad['title_ads'],
                'description_ads' => $ad['description_ads'],
                'landingpage_ads' => $ad['landingpage_ads'],
                'is_expired' => $ad['is_expired'],
                'expired_date' => $ad['expired_date'],
                 'is_paused' => $ad['is_paused'],
               'paused_date' => $ad['paused_date'],

               
               'budget_allocation' => $ad['budget_allocation'],

               'current_spending' => $ad['current_spending'],
               'image_url' => $ad['image_url'],




               
                'total_click' => $ad['total_click'],
                'current_click' => $ad['current_click'],
                'budget_per_click_textads' => $ad['budget_per_click_textads'] 

            );

             echo "<br>data: " . json_encode($data) . "<br>";
           
            // Generate secret_key_request using SHA1
            $data['secret_key_request'] = sha1($data['title_ads'] . $data['description_ads'] . $data['landingpage_ads'] . $data['providers_domain_url']);

            // Convert array to JSON
            $json_data = json_encode($data);

            // Use the public_key and secret_key from the providers_partners table
            $Header_public_key = $provider['public_key'];
            $Header_secret_key = $provider['secret_key'];

            $headers = array(
                'Content-Type: application/json',
                'Accept: application/json',
                "public_key: $Header_public_key",
                "secret_key: $Header_secret_key"
            );

            // Initialize cURL
            $ch = curl_init($api_url);

            // Set cURL options
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
            curl_setopt($ch, CURLOPT_POST, true);
            curl_setopt($ch, CURLOPT_POSTFIELDS, $json_data);

            // Execute cURL
            $response = curl_exec($ch);

            // Check for cURL errors
            if (curl_errno($ch)) {
                $error_msg = curl_error($ch);
                echo "cURL Error: $error_msg";
            } else {
                // Decode the JSON response from the API
                $response_data = json_decode($response, true);
                echo "response_data: " . json_encode($response_data) . "\n";

                // Check if $response_data is not null and is an array
                if (is_array($response_data)) {
                    // Display the response from the API
                    if (isset($response_data['status']) && $response_data['status'] == 'success') {
                        echo "Success: " . $response_data['message'] . "\n";
                    } else {
                        // If 'status' is not 'success' or doesn't exist
                        echo "Error: " . (isset($response_data['message']) ? $response_data['message'] : 'Unknown error') . "\n";
                    }
                } else {
                    // Handle the case where the response is null or not an array
                    echo "Error: Failed to decode API response or response is empty.\n";
                }
            }

            // Close cURL
            curl_close($ch);
        }
    }

} catch (PDOException $e) {
    echo "Database error: " . $e->getMessage();
}

// ----------------- Update status expired ke table mappinf

echo "<br><h2>Tahap 2, Update ke table `mapping_advertisers_ads_publishers_site` , Ambil data dari table `advertisers_ads` </h2><br>";
 

 $sqlCheck="SELECT *
FROM advertisers_ads
WHERE is_expired = 1 ";
    //  echo "<br>sqlCheck: " . $sqlCheck . "<br>";

        $stmt_ads = $pdo->query($sqlCheck);
        $ads = $stmt_ads->fetchAll(PDO::FETCH_ASSOC);

        foreach ($ads as $ad) {
            // Data that will be sent to the API

            $local_ads_id = $ad['local_ads_id'];
            $title_ads = $ad['title_ads'];
            $providers_domain_url = $ad['providers_domain_url'];
            $expired_date = $ad['expired_date'];

            echo "<br>local_ads_id: " . $local_ads_id . "";
            echo "<br>title_ads: " . $title_ads . "";
            echo "<br>providers_domain_url: " . $providers_domain_url . "";
            echo "<br>expired_date: " . $expired_date . "";

// SQL query to update the `mapping_advertisers_ads_publishers_site` table
    $sqlUpdate = "UPDATE mapping_advertisers_ads_publishers_site
                  SET is_expired = 1, expired_date = ?
                  WHERE local_ads_id = ?
                  AND ads_providers_domain_url = ?";

    // Prepare the statement
    $stmt_update = $pdo->prepare($sqlUpdate);

    // Bind the parameters
    $stmt_update->bindParam(1, $expired_date);
    $stmt_update->bindParam(2, $local_ads_id);
    $stmt_update->bindParam(3, $providers_domain_url);

    // Execute the update query
    if ($stmt_update->execute()) {
        echo "<br>Update successful for local_ads_id: " . $local_ads_id . " and providers_domain_url: " . $providers_domain_url;
    } else {
        echo "<br>Update failed for local_ads_id: " . $local_ads_id . " and providers_domain_url: " . $providers_domain_url;
    }
}
        

        echo "<br><h2>Tahap 3, Update ke table `mapping_advertisers_ads_publishers_site` , Ambil data dari table `advertisers_ads_partners` </h2><br>";
 

 $sqlCheck="SELECT *
FROM advertisers_ads_partners
WHERE is_expired = 1 ";
    //  echo "<br>sqlCheck: " . $sqlCheck . "<br>";

        $stmt_ads = $pdo->query($sqlCheck);
        $ads = $stmt_ads->fetchAll(PDO::FETCH_ASSOC);

        foreach ($ads as $ad) {
            // Data that will be sent to the API

            $local_ads_id = $ad['local_ads_id'];
            $title_ads = $ad['title_ads'];
            $providers_domain_url = $ad['providers_domain_url'];
            $expired_date = $ad['expired_date'];

            echo "<br>local_ads_id: " . $local_ads_id . "";
            echo "<br>title_ads: " . $title_ads . "";
            echo "<br>providers_domain_url: " . $providers_domain_url . "";
            echo "<br>expired_date: " . $expired_date . "";

// SQL query to update the `mapping_advertisers_ads_publishers_site` table
    $sqlUpdate = "UPDATE mapping_advertisers_ads_publishers_site
                  SET is_expired = 1, expired_date = ?
                  WHERE local_ads_id = ?
                  AND ads_providers_domain_url = ?";

    // Prepare the statement
    $stmt_update = $pdo->prepare($sqlUpdate);

    // Bind the parameters
    $stmt_update->bindParam(1, $expired_date);
    $stmt_update->bindParam(2, $local_ads_id);
    $stmt_update->bindParam(3, $providers_domain_url);

    // Execute the update query
    if ($stmt_update->execute()) {
        echo "<br>Update successful for local_ads_id: " . $local_ads_id . " and providers_domain_url: " . $providers_domain_url;
    } else {
        echo "<br>Update failed for local_ads_id: " . $local_ads_id . " and providers_domain_url: " . $providers_domain_url;
    }
}
        


 
// ----------------- Sinkron status is_paused ke table mapping
//
// cronjob/mapping_ads_publisher.php only refreshes an existing mapping row's
// is_paused flag when it revisits that (ad, publisher) pair, which requires
// the ad to still satisfy the publisher's price condition. Once a mapping
// row falls out of that price match it's never revisited again, so if the
// ad is paused afterwards, is_paused can stay stuck at 0 forever — unlike
// is_expired above, there was no catch-all push for is_paused. This section
// pushes the current is_paused status (both directions: paused and
// unpaused) straight onto every matching mapping row, independent of price
// or publish state, exactly like the is_expired sync above.

echo "<br><h2>Tahap 4, Sinkron `is_paused` ke table `mapping_advertisers_ads_publishers_site` , Ambil data dari table `advertisers_ads` </h2><br>";

$sqlPausedLocal = "SELECT local_ads_id, providers_domain_url, is_paused, paused_date FROM advertisers_ads";
$stmt_ads_paused_local = $pdo->query($sqlPausedLocal);
$ads_paused_local = $stmt_ads_paused_local->fetchAll(PDO::FETCH_ASSOC);

$sqlUpdatePausedLocal = "UPDATE mapping_advertisers_ads_publishers_site
              SET is_paused = ?, paused_date = ?
              WHERE local_ads_id = ?
              AND ads_providers_domain_url = ?
              AND is_paused != ?";
$stmt_update_paused_local = $pdo->prepare($sqlUpdatePausedLocal);

foreach ($ads_paused_local as $ad) {
    $local_ads_id = $ad['local_ads_id'];
    $providers_domain_url = $ad['providers_domain_url'];
    $is_paused = (int) $ad['is_paused'];
    $paused_date = $ad['paused_date'];

    echo "<br>local_ads_id: " . $local_ads_id . " providers_domain_url: " . $providers_domain_url . " is_paused: " . $is_paused;

    $stmt_update_paused_local->bindParam(1, $is_paused, PDO::PARAM_INT);
    $stmt_update_paused_local->bindParam(2, $paused_date);
    $stmt_update_paused_local->bindParam(3, $local_ads_id);
    $stmt_update_paused_local->bindParam(4, $providers_domain_url);
    $stmt_update_paused_local->bindParam(5, $is_paused, PDO::PARAM_INT);

    if ($stmt_update_paused_local->execute()) {
        echo " -> is_paused sync ok (" . $stmt_update_paused_local->rowCount() . " baris diperbarui)";
    } else {
        echo " -> is_paused sync GAGAL";
    }
}

echo "<br><h2>Tahap 5, Sinkron `is_paused` ke table `mapping_advertisers_ads_publishers_site` , Ambil data dari table `advertisers_ads_partners` </h2><br>";

$sqlPausedPartner = "SELECT local_ads_id, providers_domain_url, is_paused, paused_date FROM advertisers_ads_partners";
$stmt_ads_paused_partner = $pdo->query($sqlPausedPartner);
$ads_paused_partner = $stmt_ads_paused_partner->fetchAll(PDO::FETCH_ASSOC);

$sqlUpdatePausedPartner = "UPDATE mapping_advertisers_ads_publishers_site
              SET is_paused = ?, paused_date = ?
              WHERE local_ads_id = ?
              AND ads_providers_domain_url = ?
              AND is_paused != ?";
$stmt_update_paused_partner = $pdo->prepare($sqlUpdatePausedPartner);

foreach ($ads_paused_partner as $ad) {
    $local_ads_id = $ad['local_ads_id'];
    $providers_domain_url = $ad['providers_domain_url'];
    $is_paused = (int) $ad['is_paused'];
    $paused_date = $ad['paused_date'];

    echo "<br>local_ads_id: " . $local_ads_id . " providers_domain_url: " . $providers_domain_url . " is_paused: " . $is_paused;

    $stmt_update_paused_partner->bindParam(1, $is_paused, PDO::PARAM_INT);
    $stmt_update_paused_partner->bindParam(2, $paused_date);
    $stmt_update_paused_partner->bindParam(3, $local_ads_id);
    $stmt_update_paused_partner->bindParam(4, $providers_domain_url);
    $stmt_update_paused_partner->bindParam(5, $is_paused, PDO::PARAM_INT);

    if ($stmt_update_paused_partner->execute()) {
        echo " -> is_paused sync ok (" . $stmt_update_paused_partner->rowCount() . " baris diperbarui)";
    } else {
        echo " -> is_paused sync GAGAL";
    }
}

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Penjelasan Detail Kode PHP: push_sync_ads.php</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            background-color: #f4f4f4;
            line-height: 1.6;
            color: #333;
            margin: 0;
            padding: 20px;
        }
        .container {
            max-width: 900px;
            margin: auto;
            background: #fff;
            padding: 20px;
            border-radius: 10px;
            box-shadow: 0 0 10px rgba(0, 0, 0, 0.1);
        }
        h1, h2 {
            color: #333;
        }
        h1 {
            text-align: center;
        }
        h2 {
            margin-top: 20px;
            border-bottom: 2px solid #e4e4e4;
            padding-bottom: 10px;
        }
        p {
            margin: 10px 0;
        }
        code {
            background-color: #f4f4f4;
            padding: 2px 4px;
            border-radius: 4px;
            color: #d63384;
            font-family: "Courier New", Courier, monospace;
        }
    </style>
</head>
<body>

<div class="container">
    <h1>Penjelasan Detail Kode PHP: <code>push_sync_ads.php</code></h1>

    <h2>1. Inklusi dan Inisialisasi Koneksi Database</h2>
    <p>Kode ini dimulai dengan menyertakan file <code>db.php</code> yang berisi informasi koneksi ke database. Setelah itu, koneksi ke database dibuat menggunakan <code>PDO</code> dengan pengaturan untuk menangani kesalahan menggunakan <code>PDO::ERRMODE_EXCEPTION</code>. Jika terjadi kesalahan dalam koneksi, pesan kesalahan akan ditampilkan.</p>

    <h2>2. Mengambil Data dari Tabel <code>providers_partners</code></h2>
    <p>Setelah koneksi ke database berhasil, kode ini menjalankan query untuk mengambil data dari tabel <code>providers_partners</code>. Data yang diambil mencakup <code>api_endpoint</code>, <code>public_key</code>, dan <code>secret_key</code>. Data ini digunakan untuk melakukan sinkronisasi iklan dengan server lain.</p>

    <h2>3. Melakukan Sinkronisasi Iklan</h2>
    <p>Setiap entri dari tabel <code>providers_partners</code> diproses sebagai berikut:</p>
    <ul>
        <li>URL API dibangun dengan menambahkan <code>/sync_ads/index.php</code> ke <code>api_endpoint</code>.</li>
        <li>Kode ini kemudian mengambil semua iklan yang dipublikasikan (<code>ispublished = 1</code>) dan belum kadaluarsa (<code>is_expired = 0</code>) dari tabel <code>advertisers_ads</code>.</li>
        <li>Untuk setiap iklan yang ditemukan, data dikemas dalam array dan ditambahkan dengan <code>secret_key_request</code>, yang dihasilkan menggunakan fungsi <code>sha1</code> pada kombinasi beberapa kolom.</li>
        <li>Array ini kemudian dikonversi menjadi JSON untuk dikirim ke API.</li>
    </ul>

    <h2>4. Mengirim Data ke API Menggunakan cURL</h2>
    <p>Untuk setiap iklan, data dikirim ke API menggunakan cURL dengan langkah-langkah berikut:</p>
    <ul>
        <li>Inisialisasi cURL dengan URL API yang telah dibangun.</li>
        <li>Menentukan opsi cURL, termasuk pengaturan header yang menyertakan <code>public_key</code> dan <code>secret_key</code>, serta data JSON yang akan dikirim.</li>
        <li>Menjalankan cURL untuk mengirim data ke API.</li>
        <li>Memeriksa apakah ada kesalahan cURL. Jika ada, pesan kesalahan dicetak.</li>
        <li>Jika tidak ada kesalahan, response dari API di-decode dari format JSON dan ditampilkan. Jika status dari response adalah "success", maka pesan sukses ditampilkan; jika tidak, pesan kesalahan ditampilkan.</li>
        <li>Jika response dari API tidak dapat di-decode atau response kosong, pesan kesalahan akan ditampilkan.</li>
        <li>cURL kemudian ditutup untuk mengakhiri sesi.</li>
    </ul>

    <h2>5. Penanganan Kesalahan Database</h2>
    <p>Jika terjadi kesalahan pada koneksi atau eksekusi query database, pesan kesalahan dari <code>PDOException</code> akan ditangkap dan ditampilkan. Hal ini memastikan bahwa kesalahan pada level database dapat diidentifikasi dengan jelas.</p>

    <h2>Ringkasan</h2>
    <p>Kode ini bertujuan untuk melakukan sinkronisasi data iklan dari sistem lokal ke server eksternal melalui API. Data diambil dari tabel <code>advertisers_ads</code> dan disinkronkan dengan server lain yang informasinya terdapat di tabel <code>providers_partners</code>. Kode ini menggunakan cURL untuk mengirim data ke API dan menangani berbagai skenario kesalahan untuk memastikan bahwa proses sinkronisasi berjalan dengan baik.</p>

</div>

</body>
</html>
