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

    // Otorisasi terjadi di dalam updateKeysByDomainAndSignature() itu sendiri:
    // UPDATE-nya hanya mengenai baris yang providers_domain_url DAN signature-nya
    // cocok dengan yang sudah tersimpan di providers_partners.
    $rt = updateKeysByDomainAndSignature($pdo, $providers_domain_url, $signature, $newPublicKey, $newSecretKey);
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

?>

