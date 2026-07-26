<?php
/*
{BASE_END_POINT}API/getinfoPaymentProviderPartner/index.php
*/

include("../../db.php");
include("../../function.php");
ini_set("error_log", "errr_.txt");

$json = file_get_contents('php://input');
$data = json_decode($json, true);
debug_text('tra1.txt',$json);


if (isset($data) && isset($data['providers_domain_url'])) {
    $providers_domain_url = $data['providers_domain_url'];
} else {
    // Handle the case where $data is null or 'providers_domain_url' does not exist
    $providers_domain_url = null; // or some default value
    // Optionally, you can log an error or throw an exception if this is an unexpected condition
}
    

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


// Check if all required parameters are present in the array
if (
    isset($data) && isset($data['id']) &&
    isset($data['partner_providers_domain_url']) &&
    isset($data['email_provider']) &&
    isset($data['nominal']) &&
    isset($data['payment_description']) &&
    isset($data['payment_date']) &&
    isset($data['payment_by'])
) {
   $local_id = $data['id'];
   $partner_providers_domain_url = $data['partner_providers_domain_url'];
    $email_provider = $data['email_provider'];
    $nominal = $data['nominal'];
    $payment_description = $data['payment_description'];
    $payment_date = $data['payment_date'];
    $payment_by = $data['payment_by'];
} else {
    $response = array(
        'status' => 'error',
        'message' => 'Missing required parameters.'
    );
    header('Content-Type: application/json');
    echo json_encode($response);
    exit();
}

// Database connection using PDO
try {
    $pdo = new PDO("mysql:host=$servername_db;dbname=$dbname_db;charset=utf8mb4", $username_db, $password_db);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch (PDOException $e) {
    error_log("Database connection failed: " . $e->getMessage());
    $response = array(
        'status' => 'error',
        'message' => 'Database connection failed.'
    );
    header('Content-Type: application/json');
    echo json_encode($response);
    exit();
}

// Check if the record already exists based on the unique combination of `local_id` and `partner_providers_domain_url`
try {
    $stmt = $pdo->prepare("
        SELECT COUNT(*) AS total 
        FROM  payment_partner_providers_sync
        WHERE local_id = :local_id 
        AND partner_providers_domain_url = :partner_providers_domain_url
    ");
    $stmt->bindParam(':local_id', $local_id, PDO::PARAM_INT);
    $stmt->bindParam(':partner_providers_domain_url', $partner_providers_domain_url, PDO::PARAM_STR);
    $stmt->execute();
    
    $result = $stmt->fetch(PDO::FETCH_ASSOC);

    // If the record exists, do nothing
    if ($result['total'] > 0) {
        $response = array(
            'status' => 'info',
            'message' => 'Record already exists, no changes made.'
        );
    } else {
        // Insert the data into the table if no record exists
        $insertStmt = $pdo->prepare("
            INSERT INTO payment_partner_providers_sync (
                local_id, partner_providers_domain_url, email_provider, nominal, payment_description, payment_date, payment_by
            ) VALUES (
                :local_id, :partner_providers_domain_url, :email_provider, :nominal, :payment_description, :payment_date, :payment_by
            )
        ");
        $insertStmt->bindParam(':local_id', $local_id, PDO::PARAM_INT);
        $insertStmt->bindParam(':partner_providers_domain_url', $partner_providers_domain_url, PDO::PARAM_STR);
        $insertStmt->bindParam(':email_provider', $email_provider, PDO::PARAM_STR);
        $insertStmt->bindParam(':nominal', $nominal, PDO::PARAM_STR);
        $insertStmt->bindParam(':payment_description', $payment_description, PDO::PARAM_STR);
        $insertStmt->bindParam(':payment_date', $payment_date, PDO::PARAM_STR);
        $insertStmt->bindParam(':payment_by', $payment_by, PDO::PARAM_STR);

        if ($insertStmt->execute()) {
            $response = array(
                'status' => 'success',
                'message' => 'Data successfully inserted.'
            );
        } else {
            $response = array(
                'status' => 'error',
                'message' => 'Failed to insert data.'
            );
        }
    }
} catch (PDOException $e) {
    error_log("Database query failed: " . $e->getMessage());
    $response = array(
        'status' => 'error',
        'message' => 'Database query failed.'
    );
}

header('Content-Type: application/json');
echo json_encode($response);

?>
