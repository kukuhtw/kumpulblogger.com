<?php
// {BASE_END_POINT}API/update_key/index.php

include("../../db.php");
include("../../function.php");
ini_set("error_log", "errr_.txt");
$json = file_get_contents('php://input');
$data = json_decode($json, true);

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

if (isset($data['providers_domain_url'], $data['signature'], $data['newPublicKey'], $data['newSecretKey'])) {

    $signature = $data['signature'];
    $providers_domain_url = $data['providers_domain_url'];
    $newPublicKey = $data['newPublicKey'];
    $newSecretKey = $data['newSecretKey'];

    $headers = array_change_key_case(getallheaders(), CASE_LOWER);
    $currentPublicKey = $headers['public_key'] ?? null;
    $currentSecretKey = $headers['secret_key'] ?? null;
    if (!$currentPublicKey || !$currentSecretKey ||
        !checkProviderCredentials($providers_domain_url, $currentPublicKey, $currentSecretKey, $pdo)) {
        http_response_code(401);
        $response = ['status' => 'error', 'message' => 'Invalid provider credentials.'];
    } elseif (strlen((string) $newPublicKey) < 32 || strlen((string) $newSecretKey) < 32) {
        http_response_code(422);
        $response = ['status' => 'error', 'message' => 'New keys must be at least 32 characters.'];
    } else {
        $updated = updateKeysByDomainAndSignature($pdo, $providers_domain_url, $signature, $newPublicKey, $newSecretKey);
        if ($updated) {
            $response = ['status' => 'success', 'message' => 'Provider keys updated successfully.'];
        } else {
            http_response_code(403);
            $response = ['status' => 'error', 'message' => 'Key rotation was not authorized.'];
        }
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

