<?php
// cronjob/push_sync_publishers.php

include("../db.php");

// Database connection settings
try {
    // Create a new PDO instance
    $pdo = new PDO("mysql:host=$servername_db;dbname=$dbname_db", $username_db, $password_db);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    // Query to select data from providers_partners table
    $stmt_providers = $pdo->query("SELECT api_endpoint, public_key, secret_key FROM providers_partners");
    $providers = $stmt_providers->fetchAll(PDO::FETCH_ASSOC);

    foreach ($providers as $provider) {
        // Construct the API URL by appending "/sync_publisher/index.php" to the api_endpoint
        $api_url = $provider['api_endpoint'] . "/sync_publisher/index.php";
        echo "<br>api_url: " . $api_url . "<br>";

        // Query to select data from advertisers_ads table
        $stmt_ads = $pdo->query("SELECT * FROM publishers_site WHERE last_updated >= DATE_SUB(NOW(), INTERVAL 240000 HOUR)");


        $pubs = $stmt_ads->fetchAll(PDO::FETCH_ASSOC);


        foreach ($pubs as $pub) {
            // Data that will be sent to the API
          
            $data = array(
                'id' => $pub['id'],
                'providers_name' => $pub['providers_name'],
                'providers_domain_url' => $pub['providers_domain_url'],
                'publishers_local_id' => $pub['publishers_local_id'],
                'site_name' => $pub['site_name'],
                'site_domain' => $pub['site_domain'],
                'site_desc' => $pub['site_desc'],
                'rate_text_ads' => $pub['rate_text_ads'],
                'advertiser_allowed' => $pub['advertiser_allowed'],

                  'advertiser_rejected' => $pub['advertiser_rejected'],
              
              
                'regdate' => $pub['regdate'],
                'isbanned' => $pub['isbanned'],
                'banned_reason' => $pub['banned_reason'],
                 'banned_date' => $pub['banned_date']

            );

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
