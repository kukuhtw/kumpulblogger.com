<?php
// {BASE_END_POINT}API/approval_advertiser_partner/index.php
/*
*/
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

$providers_domain_url = $data['providers_domain_url'];

// Extract headers
$headers = getallheaders();
$Header_public_key = isset($headers['public_key']) ? $headers['public_key'] : null;
$Header_secret_key = isset($headers['secret_key']) ? $headers['secret_key'] : null;

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


if (isset($data['providers_domain_url'])) {
    $id = $data['id'];
    $local_ads_id = $data['local_ads_id'];
    $pubs_providers_domain_url = $data['pubs_providers_domain_url'];
    $providers_domain_url = $data['providers_domain_url'];
    $ads_providers_domain_url = $data['ads_providers_domain_url'];
    $is_approved_by_advertiser = $data['is_approved_by_advertiser'];

    try {
        // Prepare the base SQL query
        $sql = "UPDATE mapping_advertisers_ads_publishers_site 
                SET is_approved_by_advertiser = :is_approved_by_advertiser ";

        // Check if approval_date_advertiser needs to be updated
        if ($is_approved_by_advertiser != 0) {
            $sql .= ", approval_date_advertiser = :approval_date_advertiser ";
        }

        $sql .= "WHERE id = :id 
                 AND local_ads_id = :local_ads_id 
                 AND pubs_providers_domain_url = :pubs_providers_domain_url 
                 AND ads_providers_domain_url = :ads_providers_domain_url";

        // Prepare the statement
        $stmt = $pdo->prepare($sql);

        // Bind parameters
        $stmt->bindParam(':is_approved_by_advertiser', $is_approved_by_advertiser, PDO::PARAM_INT);
        $stmt->bindParam(':id', $id, PDO::PARAM_INT);
        $stmt->bindParam(':local_ads_id', $local_ads_id, PDO::PARAM_INT);
        $stmt->bindParam(':pubs_providers_domain_url', $pubs_providers_domain_url, PDO::PARAM_STR);
        $stmt->bindParam(':ads_providers_domain_url', $ads_providers_domain_url, PDO::PARAM_STR);

        // Bind approval_date_advertiser only if approval is not 0
        if ($is_approved_by_advertiser != 0) {
           
            $date = new DateTime('now', new DateTimeZone('Asia/Jakarta')); // Jakarta is in GMT+7
            $approval_date_advertiser = $date->format('Y-m-d H:i:s'); // Format to MySQL datetime format
             $stmt->bindParam(':approval_date_advertiser', $approval_date_advertiser, PDO::PARAM_STR);
        }

        // Execute the query
        if ($stmt->execute()) {
            $response = array(
                'status' => 'success',
                'message' => 'Update successful'
            );
        } else {
            $response = array(
                'status' => 'error',
                'message' => 'Failed to update record'
            );
        }

    } catch (PDOException $e) {
        error_log("Database error: " . $e->getMessage());
        $response = array(
            'status' => 'error',
            'message' => 'Database error occurred'
        );
    }

} else {
    $response = array(
        'status' => 'error',
        'message' => 'Invalid input'
    );
}

// Return response as JSON
header('Content-Type: application/json');
echo json_encode($response);

?>