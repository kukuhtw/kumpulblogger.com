<?php

/*
cronjob/push_sync_mapping_ads_publisher.php
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

// -----------------------------------------


$id = 1;
//$this_providers_domain_url = get_providers_domain_url($conn, $id);

$this_providers_domain_url = get_providers_domain_url_json("../providers_data.json", 1);


$providers = getProvidersDetails($conn);


echo "<h1>cronjob/push_sync_mapping_ads_publisher.php</h1>";
echo "<h2>Berfungsi melaporkan data mapping_ads_publisher untuk partner adnetwork, Publisher local dan advertiser berasal dari partner</h2>"; 



foreach ($providers as $provider) {
    $providers_domain_url = $provider['providers_domain_url'];
    $target_providers_domain_url = $provider['target_providers_domain_url']; // --- target_providers_domain_url = our local server

    $pubs_providers_domain_url = $target_providers_domain_url;
    $ads_providers_domain_url = $providers_domain_url;
    

    $api_endpoint = $provider['api_endpoint'];
    $public_key = $provider['public_key'];
    $secret_key = $provider['secret_key'];

   // echo "<br>providers_domain_url = ".$providers_domain_url;
    
    echo "<br>Data mapping siap disetor ke =".$providers_domain_url;
    
    $data_mapping = getMapping_ads_publisher($conn, $pubs_providers_domain_url, $ads_providers_domain_url);

      echo "<br>data_mapping =".json_encode($data_mapping);
    
    if (count($data_mapping) > 0) {
   
        $response = syncClicksToApi($api_endpoint, $this_providers_domain_url, $public_key, $secret_key, $data_mapping);
        
   

        
    }
}

$conn->close();




function getProvidersDetails($conn) {
    $sql = "SELECT providers_domain_url, target_providers_domain_url ,api_endpoint, public_key, secret_key FROM providers_partners WHERE isapproved = 1 ";
     //echo "<br>sql =".$sql;

    $result = $conn->query($sql);
    if ($result->num_rows > 0) {
        return $result->fetch_all(MYSQLI_ASSOC);
    }
    return [];
}

function getMapping_ads_publisher($conn, 
    $pubs_providers_domain_url, $ads_providers_domain_url) {
    
  
    // Adjust the query to retrieve records updated in the last 24 hours
    $sql = "SELECT * FROM mapping_advertisers_ads_publishers_site 
            WHERE `last_updated` >= NOW() - INTERVAL 2400 HOUR
            AND `pubs_providers_domain_url` = ?
            AND `ads_providers_domain_url` = ?


    ";
    
     echo "<br>sql =".$sql;

    $stmt = $conn->prepare($sql);
    $stmt->bind_param("ss", $pubs_providers_domain_url, $ads_providers_domain_url);
    $stmt->execute();
    $result = $stmt->get_result();

     echo "<br>pubs_providers_domain_url =".$pubs_providers_domain_url;

     echo "<br>ads_providers_domain_url =".$ads_providers_domain_url;


    return $result->fetch_all(MYSQLI_ASSOC);
}

function syncClicksToApi($api_endpoint, $this_providers_domain_url, $public_key, $secret_key, $data_mapping) {
    $url = $api_endpoint . "/sync_mapping_advertisers_ads_publishers_site_from_partners/index.php";
    
     echo "<br>syncClicksToApi =".$url;


    $data = [
        'providers_domain_url' => $this_providers_domain_url,
        'ad_data' => $data_mapping
    ];
    
    echo "<br>url = ".$url;
    echo "<br>this_providers_domain_url = ".$this_providers_domain_url;
    
    $headers = [
        'Content-Type: application/json',
        'Accept: application/json',
        "public_key: $public_key",
        "secret_key: $secret_key"
    ];
    
    // Initialize cURL session
    $ch = curl_init($url);
    
    // Set cURL options
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
    curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
    
    // Execute cURL request
    $result = curl_exec($ch);
    
  if ($result === false) {
    echo "cURL Error: " . curl_error($ch);
} else {
    echo "<br>Raw cURL result: " . htmlspecialchars($result);
    $decoded_result = json_decode($result, true);
    if (json_last_error() === JSON_ERROR_NONE) {
        echo "<br>Decoded response: " . json_encode($decoded_result);
    } else {
        echo "<br>JSON Decode Error: " . json_last_error_msg();
    }
}



    // Check for cURL errors
    if ($result === false) {
        // Handle error
        curl_close($ch);
        return false;
    }
    
    // Close cURL session
    curl_close($ch);
    
    // Return the result as an associative array
    return json_decode($result, true);
}

?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Penjelasan Detail Kode PHP</title>
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
