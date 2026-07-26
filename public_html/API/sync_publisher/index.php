<?php
// {BASE_END_POINT}API/sync_publishers/index.php

include("../../db.php");
include("../../function.php");
ini_set("error_log", "errr_.txt");
$json = file_get_contents('php://input');
$data = json_decode($json, true);

debug_text('tra1.txt',$json);
$providers_domain_url = $data['providers_domain_url'];


if (isset($data) && isset($data['providers_domain_url'])) {
    $providers_domain_url = $data['providers_domain_url'];
} else {
    // Handle the case where $data is null or 'providers_domain_url' does not exist
    $providers_domain_url = null; // or some default value
    // Optionally, you can log an error or throw an exception if this is an unexpected condition
}
    
debug_text('tra22.txt',$providers_domain_url);
  // Database connection using PDO for secure database interaction
try {
    $pdo = new PDO("mysql:host=$servername_db;dbname=$dbname_db;charset=utf8mb4", $username_db, $password_db);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch (PDOException $e) {
    error_log("Database connection failed: " . $e->getMessage());
    exit("Database connection failed.");
}


// Database connection using MySQLi
$conn = new mysqli($servername_db, $username_db, $password_db, $dbname_db);
if ($conn->connect_error) {
    error_log("Database connection failed: " . $conn->connect_error);
    exit("Database connection failed.");
}

// Extract headers
$headers = getallheaders();
$Header_public_key = isset($headers['public_key']) ? $headers['public_key'] : null;
$Header_secret_key = isset($headers['secret_key']) ? $headers['secret_key'] : null;

   
debug_text('tra45.txt',$Header_public_key);
debug_text('tra47.txt',$Header_secret_key);

// Check if the required headers are present
if (!$Header_public_key || !$Header_secret_key) {
    $response = array(
        'status' => 'error',
        'message' => 'Missing required headers.'
    );
    header('Content-Type: application/json');
    echo json_encode($response);
    exit();
}

debug_text('tra60.txt',$Header_secret_key);

if (checkProviderCredentials($providers_domain_url, $Header_public_key, $Header_secret_key, $pdo)) {
    //echo "Credentials are valid!";
} else {
    $response = array(
        'status' => 'error',
        'message' => 'Invalid public:or secret key'
    );
    header('Content-Type: application/json');
    echo json_encode($response);
    exit();
}

debug_text('tra74.txt',$Header_secret_key);


if (isset($data['site_name'])) {

    $id = $data['id'];
    $providers_name = $data['providers_name'];
    $providers_domain_url = $data['providers_domain_url'];
    $publishers_local_id = $data['publishers_local_id'];
    $site_name = $data['site_name'];
    $site_domain = $data['site_domain']; 
    $site_desc = $data['site_desc'];

    $rate_text_ads = $data['rate_text_ads'];
    $advertiser_allowed = $data['advertiser_allowed'];
    $advertiser_rejected= $data['advertiser_rejected'];

     $regdate = $data['regdate'];
      $isbanned = $data['isbanned'];
      $banned_date  = $data['banned_date'];

      $banned_reason  = $data['banned_reason'];
            
        debug_text('tra99.txt',$id);

   
        $rt = insertOrUpdatePublisherPartner($pdo, $id,$providers_name, $providers_domain_url, $publishers_local_id, $site_name, $site_domain, $site_desc, 
            $rate_text_ads, $advertiser_allowed, 
            $advertiser_rejected, $banned_reason, $isbanned,$banned_date);

       
        $response = array(
                    'status' => 'success',
                    'message' => $rt 
                );

    }

else {
    // Missing required data
    $response = array(
        'status' => 'error',
        'message' => 'Invalid request. Missing required data.'
    );
}


// Send response as JSON
header('Content-Type: application/json');
echo json_encode($response);



function insertOrUpdatePublisherPartner($pdo, $id, $providers_name, $providers_domain_url, $publishers_local_id, $site_name, $site_domain, $site_desc, 
                                        $rate_text_ads, $advertiser_allowed, $advertiser_rejected, $banned_reason, $isbanned, $banned_date) {
    
    // Check if the entry already exists
    $check_sql = "SELECT id FROM publishers_site_partners 
                  WHERE local_id = :local_id AND publishers_local_id = :publishers_local_id AND providers_domain_url = :providers_domain_url";

    debug_text('136.txt',$check_sql);
    debug_text('137.txt','local_id:'.$id);
    debug_text('138.txt','publishers_local_id:'.$publishers_local_id);
    debug_text('139.txt','providers_domain_url:'.$providers_domain_url);

    $stmt = $pdo->prepare($check_sql);
    $stmt->bindParam(':local_id', $id);
    $stmt->bindParam(':publishers_local_id', $publishers_local_id);
    $stmt->bindParam(':providers_domain_url', $providers_domain_url);
    $stmt->execute();

    $existingEntry = $stmt->fetch(PDO::FETCH_ASSOC);

    if ($existingEntry) {
        // If the entry exists, update it
        $update_sql = "UPDATE publishers_site_partners 
                       SET providers_name = :providers_name, 
                           site_name = :site_name, 
                           site_domain = :site_domain, 
                           site_desc = :site_desc, 
                           rate_text_ads = :rate_text_ads, 
                           advertiser_allowed = :advertiser_allowed, 
                           advertiser_rejected = :advertiser_rejected, 
                           banned_reason = :banned_reason, 
                           isbanned = :isbanned, 
                           banned_date = :banned_date, 
                           last_updated = :last_updated
                       WHERE id = :id";
        
        $update_stmt = $pdo->prepare($update_sql);
        $update_stmt->bindParam(':providers_name', $providers_name);
        $update_stmt->bindParam(':site_name', $site_name);
        $update_stmt->bindParam(':site_domain', $site_domain);
        $update_stmt->bindParam(':site_desc', $site_desc);
        $update_stmt->bindParam(':rate_text_ads', $rate_text_ads);
        $update_stmt->bindParam(':advertiser_allowed', $advertiser_allowed);
        $update_stmt->bindParam(':advertiser_rejected', $advertiser_rejected);
        $update_stmt->bindParam(':banned_reason', $banned_reason);
        $update_stmt->bindParam(':isbanned', $isbanned);
        $update_stmt->bindParam(':banned_date', $banned_date);

       $date = new DateTime('now', new DateTimeZone('Asia/Jakarta')); // GMT +7 (Jakarta Time Zone)
        $last_updated = $date->format('Y-m-d H:i:s'); // Format as MySQL datetime
        $update_stmt->bindParam(':last_updated', $last_updated);

        

        $update_stmt->bindParam(':last_updated', $last_updated);
        $update_stmt->bindParam(':id', $existingEntry['id']);
        $update_stmt->execute();
        
        $return = $existingEntry['id'];  // Return the updated ID
    } else {
        // If the entry does not exist, insert a new one
        $insert_sql = "INSERT INTO publishers_site_partners 
                       (local_id, providers_name, providers_domain_url, publishers_local_id, site_name, site_domain, site_desc, 
                        rate_text_ads, advertiser_allowed, advertiser_rejected, regdate, isbanned, banned_date, banned_reason, last_updated) 
                       VALUES (:local_id, :providers_name, :providers_domain_url, :publishers_local_id, :site_name, :site_domain, :site_desc, 
                               :rate_text_ads, :advertiser_allowed, :advertiser_rejected, NOW(), :isbanned, :banned_date, :banned_reason, :last_updated)";
        
        $insert_stmt = $pdo->prepare($insert_sql);
        $insert_stmt->bindParam(':local_id', $id);
        $insert_stmt->bindParam(':providers_name', $providers_name);
        $insert_stmt->bindParam(':providers_domain_url', $providers_domain_url);
        $insert_stmt->bindParam(':publishers_local_id', $publishers_local_id);
        $insert_stmt->bindParam(':site_name', $site_name);
        $insert_stmt->bindParam(':site_domain', $site_domain);
        $insert_stmt->bindParam(':site_desc', $site_desc);
        $insert_stmt->bindParam(':rate_text_ads', $rate_text_ads);
        $insert_stmt->bindParam(':advertiser_allowed', $advertiser_allowed);
        $insert_stmt->bindParam(':advertiser_rejected', $advertiser_rejected);
        $insert_stmt->bindParam(':isbanned', $isbanned);
        $insert_stmt->bindParam(':banned_date', $banned_date);
        $insert_stmt->bindParam(':banned_reason', $banned_reason);
        $insert_stmt->bindParam(':last_updated', $last_updated);
        $insert_stmt->execute();
        
        $return = $pdo->lastInsertId();  // Return the last inserted ID
    }

    // Close the database connection
    $pdo = null;

    // Return the last inserted or updated ID
    return $return;
}


?>