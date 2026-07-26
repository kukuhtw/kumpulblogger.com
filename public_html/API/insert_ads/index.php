<?php
// {BASE_END_POINT}API/insert_ads/index.php
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

if (isset($data['title_ads']) && isset($data['description_ads'])) {
    
    $providers_name = $data['providers_name'];
    $providers_domain_url = $data['providers_domain_url'];
    $advertisers_id = $data['advertisers_id'];
    $title_ads = $data['title_ads'];
    $description_ads = $data['description_ads'];
    $landingpage_ads = $data['landingpage_ads'];
     $total_click = $data['total_click'];
      $secret_key = $data['secret_key'];
    
    
    $id=1;
    $verifying_secret_key = getSecretKeyById($conn, $id);

    $expected_secret_key = sha1($title_ads . $description_ads .$landingpage_ads.$providers_domain_url);

    if ($secret_key === $verifying_secret_key) {
        // Process the request 

        $lastInsertId = insertAdvertisersAds($pdo, $providers_name, $providers_domain_url, $advertisers_id, $title_ads, $description_ads, $landingpage_ads, $total_click);

        $current_click = 0;

       debug_text('tra46.txt',$lastInsertId);
       debug_text('tra47.txt',$current_click);
     


        $rt = updateAdvertisersAds($pdo, $lastInsertId, $current_click);

        debug_text('tra52.txt',$rt);

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