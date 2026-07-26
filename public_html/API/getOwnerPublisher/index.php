<?php
/*
{BASE_END_POINT}API/getOwnerPublisher/index.php

*/
include("../../db.php");
include("../../function.php");
ini_set("error_log", "errr_.txt");
$json = file_get_contents('php://input');
$data = json_decode($json, true);
debug_text('tra1.txt',$json);


// Cek apakah providers_domain_url ada dalam data yang dikirim
if (isset($data) && isset($data['providers_domain_url'])) {
    $providers_domain_url = $data['providers_domain_url'];
} else {
    $response = array(
        'status' => 'error',
        'message' => 'providers_domain_url is missing.'
    );
    header('Content-Type: application/json');
    echo json_encode($response);
    exit();
}

// Koneksi ke database dengan PDO untuk keamanan
try {
    $pdo = new PDO("mysql:host=$servername_db;dbname=$dbname_db;charset=utf8mb4", $username_db, $password_db);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch (PDOException $e) {
    error_log("Database connection failed: " . $e->getMessage());
    exit("Database connection failed.");
}

$conn = new mysqli($servername_db, $username_db, $password_db, $dbname_db);
if ($conn->connect_error) {
    error_log("Database connection failed: " . $conn->connect_error);
    exit("Database connection failed.");
}



$id = 1; // Example id to pass
$this_providers_domain_url = get_providers_domain_url($conn, $id);
//$this_providers_domain_url = get_providers_domain_url_json("../providers_data.json", 1);

// Cek apakah header `public_key` dan `secret_key` ada
$headers = getallheaders();
$Header_public_key = isset($headers['public_key']) ? $headers['public_key'] : null;
$Header_secret_key = isset($headers['secret_key']) ? $headers['secret_key'] : null;

if (!$Header_public_key || !$Header_secret_key) {
    $response = array(
        'status' => 'error',
        'message' => 'Missing required headers.'
    );
    header('Content-Type: application/json');
    echo json_encode($response);
    exit();
}

// Validasi kredensial provider
if (!checkProviderCredentials($providers_domain_url, $Header_public_key, $Header_secret_key, $pdo)) {
    $response = array(
        'status' => 'error',
        'message' => 'Invalid public or secret key.'
    );
    header('Content-Type: application/json');
    echo json_encode($response);
    exit();
}

// Cek apakah publishers_local_id ada dalam data
if (isset($data['pub_id'])) {
    $pub_id = $data['pub_id'];
    $pubs_providers_domain_url = $data['pubs_providers_domain_url'];

    //debug_text('tra77.txt','pub_id:'.$pub_id);


    $query = "SELECT publishers_local_id FROM publishers_site WHERE id = :pub_id";
     debug_text('tra81.txt','query:'.$query);
    $stmt = $pdo->prepare($query);
    $stmt->bindParam(':pub_id', $pub_id, PDO::PARAM_INT);
    $stmt->execute();
    // Fetch the result and store it in a variable
    $publishers_site = $stmt->fetch(PDO::FETCH_ASSOC); // Use FETCH_ASSOC to get an associative array
    $publishers_local_id = $publishers_site['publishers_local_id'] ?? null; 
    
    // Query untuk mengambil data dari msusers berdasarkan publishers_local_id
    $query = "SELECT loginemail, whatsapp, bank, account_name  , account_number , '$pubs_providers_domain_url' as 'pubs_providers_domain_url' , '$publishers_local_id' as 'publishers_local_id' , '$pub_id' as 'pub_id'


    FROM msusers WHERE id = :publishers_local_id";
    debug_text('tra89.txt','query:'.$query);
    debug_text('tra90.txt','pub_id:'.$pub_id);
    debug_text('tra92.txt','publishers_local_id:'.$publishers_local_id);
    $stmt = $pdo->prepare($query);
    $stmt->bindParam(':publishers_local_id', $publishers_local_id, PDO::PARAM_INT);
    $stmt->execute();

    
    // Jika data ditemukan, kirim respons sebagai JSON
    if ($stmt->rowCount() > 0) {
        $user_data = $stmt->fetch(PDO::FETCH_ASSOC);
        $response = array(
            'status' => 'success',
            'data' => $user_data
        );
    } else {
        // Jika tidak ditemukan
        $response = array(
            'status' => 'error',
            'message' => 'No user found for the given publishers_local_id.'
        );
    }
} else {
    $response = array(
        'status' => 'error',
        'message' => 'publishers_local_id is missing.'
    );
}

// Kirim respons sebagai JSON
header('Content-Type: application/json');
echo json_encode($response);

// Tutup koneksi
$pdo = null;
?>
