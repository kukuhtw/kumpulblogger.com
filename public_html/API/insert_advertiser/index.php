<?php
// {BASE_END_POINT}API/insert_advertiser/index.php
include("../../db.php");
include("../../function.php");
ini_set("error_log", "errr_.txt");
$json = file_get_contents('php://input');
$data = json_decode($json, true);

$advertisers_name = $data['advertisers_name'];
$advertisers_email = $data['advertisers_email'];
$advertisers_whatsapp = $data['advertisers_whatsapp'];

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


if (isset($data['advertisers_email']) && isset($data['advertisers_name'])) {
    
    $providers_name = $data['providers_name'];
    $providers_domain_url = $data['providers_domain_url'];
    $advertisers_name = $data['advertisers_name'];
    $advertisers_email = $data['advertisers_email'];
    $advertisers_whatsapp = $data['advertisers_whatsapp'];

   $secret_key_provider = $data['secret_key_provider'];
    $id=1;
    $verifying_secret_key_provider = getSecretKeyById($conn, $id);


    $number_random = rand(111111,99999999).$advertisers_name .$advertisers_email;


    $advertisers_password= sha1($number_random) ;
    $advertisers_password = substr($advertisers_password, 0,8);
    $hash_advertisers_password=sha1($advertisers_password);

    $expected_secret_key = sha1($advertisers_email . $providers_domain_url .$providers_name.$advertisers_whatsapp);

   if ($secret_key_provider === $verifying_secret_key_provider) {
        // Process the request

        $rt = insertAdvertiser($conn, $providers_name, $providers_domain_url, $advertisers_name, $advertisers_email, $advertisers_whatsapp, $hash_advertisers_password);
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