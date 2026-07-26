<?php
// {BASE_END_POINT}API/request_join/index.php
/*

request joint berfungsi untuk memfasilitasi provider Ads Network untuk bergabung dengan partner et Ads Network lain.

Data yang dibutuhkan :

1. nama provider ad network pemohon 
2. domain url provider ad network pemohon 
3. nama target provider ad network yang akan dijadikan partner
4. domain url provider ad network  yang akan dijadikan partner
5. code provider ad network  yang akan dijadikan partner . TABLE `providers` field `providers_code` varchar(255) 

BASE_END_POINT yang disetup adalah 
http://{URL PARTNER AD NETWORK}/API/request_join/index.php';


*/
include("../../db.php");
include("../../function.php");
ini_set("error_log", "errr_.txt");
$json = file_get_contents('php://input');
$data = json_decode($json, true);

//debug_text('tra1.txt',$json);

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



if (isset($data['request_from']) && isset($data['providers_domain_url'])) {
    $request_from = $data['request_from'];
    $signature = $data['signature'];
    $browser_agent = $data['browser_agent'];
    $providers_domain_url = $data['providers_domain_url'];
    $target_providers_domain_url= $data['target_providers_domain_url'];
    $providers_code= $data['providers_code'];

    $id=1;

    $verifying_providers_code = getProvidersCodeById($conn, $id);
    $id=1;
    //$get_providers_domain_url = get_providers_domain_url($conn, $id);

    $get_providers_domain_url = get_providers_domain_url_json("../providers_data.json", 1);

    


	   // Validate incoming data and keys
    $expected_secret_key = sha1($providers_code);
    
    if ($providers_code  === $verifying_providers_code) {
        // Process the request
        $providers_api_url = $data['providers_api_url'];
        $ipaddress = $data['ipaddress'];
        $source_url = $data['source_url'];
         $browser_agent = $data['browser_agent'];
        
        
        $rt = insertProvidersRequest($request_from, $signature,  $providers_domain_url, 
            $target_providers_domain_url, $providers_api_url, $ipaddress, $source_url,$browser_agent);

    // insert ke table providers_partners
        $isapproved=0;
        $public_key="";
        $secret_key= "";
         

 // Set the current date and time in GMT+7
        $request_date = new DateTime('now', new DateTimeZone('Asia/Jakarta'));
// Store the formatted date in a variable
        $formatted_request_date = $request_date->format('Y-m-d H:i:s');
   // Get the current Unix epoch time
            $time_epoch_request_date = $request_date->getTimestamp();

         $rt .= insertProviderPartner_preApproval($pdo, $signature, $request_from , 
        $providers_domain_url, 
        $get_providers_domain_url,
        $providers_api_url, $formatted_request_date, $time_epoch_request_date, $public_key, $secret_key, $isapproved, $ipaddress, $source_url,$browser_agent);


        
        // Respond to the sender
        $response = array(
            'status' => 'success',
            'message' => $rt 
        );
    } else {
        // Invalid secret key
        $response = array(
            'status' => 'error',
            'message' => 'Invalid secret key.'
        );
    }
} else {
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
