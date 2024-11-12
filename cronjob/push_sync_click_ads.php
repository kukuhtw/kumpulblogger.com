<?php

/*
cronjob/push_sync_click_ads.php
*/

// Include your database connection file
include("../db.php");
include("../function.php");

// Database connection using MySQLi
$conn = new mysqli($servername_db, $username_db, $password_db, $dbname_db);
if ($conn->connect_error) {
    error_log("Database connection failed: " . $conn->connect_error);
    exit("Database connection failed.");
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Proses Push Sync Click Ads</title>
    <style>
        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            margin: 0;
            padding: 0;
            background-color: #f0f2f5;
            color: #333;
        }
        .container {
            max-width: 1200px;
            margin: 30px auto;
            padding: 20px;
            background-color: #fff;
            border-radius: 10px;
            box-shadow: 0 0 15px rgba(0, 0, 0, 0.1);
        }
        h1 {
            color: #0056b3;
            text-align: center;
            padding: 20px 0;
        }
        .log {
            padding: 15px;
            background-color: #f9f9c5;
            border-left: 5px solid #f7c600;
            border-radius: 8px;
            margin-bottom: 30px;
            font-size: 1.1em;
        }
        .highlight {
            color: green;
            font-weight: bold;
        }
        .error {
            color: red;
            font-weight: bold;
        }
    </style>
</head>
<body>
    <div class="container">
        <h1>Proses Push Sync Click Ads</h1>
        <div class="log">
            <p>Proses ini berfungsi untuk mensinkronisasi data klik iklan yang telah diaudit dengan API partner adnetwork.</p>
        </div>
   

<?php


$id = 1;
$this_providers_domain_url = get_providers_domain_url_json("../providers_data.json", 1);

$providers = getProvidersDetails($conn);

echo "<h1>cronjob/push_sync_click_ads.php</h1>";
echo "<h2>Berfungsi melaporkan data transaksi klik milik advertiser dari partner adnetwork</h2>"; 

foreach ($providers as $provider) {
    $providers_domain_url = $provider['providers_domain_url'];
    $api_endpoint = $provider['api_endpoint'];
    $public_key = $provider['public_key'];
    $secret_key = $provider['secret_key'];

    // Menampilkan provider yang sedang diproses
    echo "<div class='log'><strong>Proses Provider:</strong> Domain URL: $providers_domain_url, API Endpoint: $api_endpoint</div>";

    updatePartnerRevenueByDomain($conn, $providers_domain_url);
    
    echo "<div class='log'><strong><br>Data click yang sudah diaudit dan belum pernah diSync, siap disetor ke: ".$providers_domain_url."</strong></div>";
    
    $clicks = getPendingClicks($conn, $providers_domain_url);

    // Menampilkan data klik yang diambil dari database
    echo "<div class='log'><strong>Click yang diambil:</strong><pre>" . json_encode($clicks, JSON_PRETTY_PRINT) . "</pre></div>";

    if (count($clicks) > 0) {
        // Sinkronisasi data click ke API
        $response = syncClicksToApi($api_endpoint, $this_providers_domain_url, $public_key, $secret_key, $clicks);
        
        if ($response && $response['status'] == 'success') {
            foreach ($clicks as $click) {
                updateSyncStatus($conn, $click['id']);
                echo "<div class='log highlight'>Sinkronisasi sukses untuk Click ID: " . $click['id'] . "</div>";
            }
        } else {
            echo "<div class='log error'>Sinkronisasi gagal. Response: " . json_encode($response) . "</div>";
        }
    } else {
        echo "<div class='log'>Tidak ada click untuk di-sync saat ini.</div>";
    }
}

$conn->close();
?>
 </div>
</body>
</html>


<?php
// Functions
function getProvidersDetails($conn) {
    $sql = "SELECT providers_domain_url, target_providers_domain_url, api_endpoint, public_key, secret_key FROM providers_partners WHERE isapproved = 1";
    $result = $conn->query($sql);
    if ($result->num_rows > 0) {
        return $result->fetch_all(MYSQLI_ASSOC);
    }
    return [];
}

function getPendingClicks($conn, $providers_domain_url) {
    $sql = "
    SELECT * FROM ad_clicks 
    WHERE isaudit = 1
    AND is_reject = 0
    AND ads_providers_domain_url = ?
    AND click_time >= NOW() - INTERVAL 14 DAY";
    
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("s", $providers_domain_url);
    $stmt->execute();
    $result = $stmt->get_result();

    return $result->fetch_all(MYSQLI_ASSOC);
}

function updateSyncStatus($conn, $id) {
    $sql = "UPDATE ad_clicks SET is_sync = 1, syncdate = NOW() WHERE id = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("i", $id);
    return $stmt->execute();
}

function syncClicksToApi($api_endpoint, $this_providers_domain_url, $public_key, $secret_key, $clicks_data) {
    $url = $api_endpoint . "/sync_clicks/index.php";
    
    // Menampilkan informasi sinkronisasi API
    echo "<div class='log'><strong>Sinkronisasi API:</strong><br>URL: $url<br>Provider Domain: $this_providers_domain_url</div>";

    $data = [
        'providers_domain_url' => $this_providers_domain_url,
        'ad_clicks' => $clicks_data
    ];
    
    // Menampilkan data yang dikirim ke API
    echo "<div class='log'><strong>Data yang dikirim ke API:</strong><pre>" . json_encode($data, JSON_PRETTY_PRINT) . "</pre></div>";
    
    $headers = [
        'Content-Type: application/json',
        'Accept: application/json',
        "public_key: $public_key",
        "secret_key: $secret_key"
    ];
    
    // Initialize cURL session
    $ch = curl_init($url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
    curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
    
    // Execute cURL request
    $result = curl_exec($ch);
    
    // Menampilkan hasil dari API
    if ($result === false) {
        echo "<div class='log error'>cURL Error: " . curl_error($ch) . "</div>";
    } else {
        echo "<div class='log'><strong>Hasil dari API:</strong><pre>" . htmlspecialchars($result) . "</pre></div>";
        $decoded_result = json_decode($result, true);
        if (json_last_error() === JSON_ERROR_NONE) {
            echo "<div class='log highlight'>Response yang diterima: <pre>" . json_encode($decoded_result, JSON_PRETTY_PRINT) . "</pre></div>";
        } else {
            echo "<div class='log error'>JSON Decode Error: " . json_last_error_msg() . "</div>";
        }
    }
    
    // Close cURL session
    curl_close($ch);
    
    // Return the result as an associative array
    return json_decode($result, true);
}
?>
