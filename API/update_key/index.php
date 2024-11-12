<?php
// {BASE_END_POINT}API/update_key/index.php

include("../../db.php");
include("../../function.php");
ini_set("error_log", "errr_.txt");
$json = file_get_contents('php://input');
$data = json_decode($json, true);

debug_text('tra1.txt',$json);

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

if (isset($data['providers_domain_url']) && isset($data['signature'])) {

    $signature = $data['signature'];
    $providers_domain_url = $data['providers_domain_url'];
    $newPublicKey = $data['newPublicKey'];
    $newSecretKey = $data['newSecretKey'];

     $expected_secret_key = sha1($signature . $providers_domain_url .$newPublicKey.$newSecretKey);

    if (1==1) {
        // Process the request 
    
        $rt = updateKeysByDomainAndSignature($pdo, $providers_domain_url, $signature, $newPublicKey, $newSecretKey);
        $response = array(
                    'status' => 'success',
                    'message' => $rt 
                );
    }
    else {
         // Invalid secret key
        $response = array(
            'status' => 'error',
            'message' => 'Invalid secret key.'
            );
    }

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

?>

