<?php
// {BASE_END_POINT}API/approve_request_partnership/index.php
/*

*/

include("../../db.php");
include("../../function.php");
//ini_set("error_log", "errr_.txt");
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



if (isset($data['providers_code']) && isset($data['providers_domain_url'])) {

	$providers_domain_url = $data['providers_domain_url'];
	$providers_code= $data['providers_code'];
	$signature= $data['signature'];
	

	$verifying_signature = 
	getSignatureByDomainUrl($conn, $providers_domain_url);


	$reason_error="";
	$keep_going=1;    
    if ($signature != $verifying_signature) {
		$keep_going=0;
		$reason_error .="<br>Wrong signature. should be: ".$verifying_signature. "insta: ".$signature. " providers_domain_url: ".$providers_domain_url ;

    }
    if ($signature == '') {
		$keep_going=0;
		$reason_error .="<br>blank signature";
    }

	$providers_code= $data['providers_code'];
	$id=1;
    $verifying_providers_code = getProvidersCodeById($conn, $id);



	$number_random = rand(111111,99999999).$providers_code .$providers_domain_url;
	$number_random2 = rand(111111,99999999).$providers_code .$providers_domain_url;
	
	$public_key = sha1($number_random);
	$secret_key = sha1($number_random2);

	$isapproved=1;
	
	// Define the ipaddress and source_url
	$ipaddress = $_SERVER['REMOTE_ADDR']; // Get the IP address of the user

	// Get the current URL of the application
	$source_url = (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? "https" : "http") . "://$_SERVER[HTTP_HOST]$_SERVER[REQUEST_URI]";
	
	if ($verifying_providers_code != $providers_code) {
		$keep_going=0;


	}


	if ($keep_going == 1) {
    
		$rt = UpdateProviderPartner($pdo,
			$providers_domain_url, $public_key, $secret_key, $isapproved) ;
			
			 $response = array(
	            'status' => 'success',
	            'public_key' => $public_key,
	            'secret_key' => $secret_key,
	            'message' => $rt 
	        );
		}
		
		else {
        // Invalid secret key
        $response = array(
            'status' => 'error',
            'message' => $reason_error
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