<?php
/*
{BASE_END_POINT}API/getinfoPaymentPubsPartner/index.php
*/

include("../../db.php");
include("../../function.php");
ini_set("error_log", "errr_.txt");

$json = file_get_contents('php://input');
$data = json_decode($json, true);
debug_text('tra1.txt',$json);

// Check if all required parameters are present in the array
if (
    isset($data) && isset($data['id']) &&
    isset($data['publisher_local_id']) &&
    isset($data['providers_domain_url']) &&
    isset($data['email_pubs']) &&
    isset($data['nominal']) &&
    isset($data['payment_description']) &&
    isset($data['payment_date']) &&
    isset($data['payment_by'])
) {
    $local_id = $data['id'];
    $publisher_local_id = $data['publisher_local_id'];
    $providers_domain_url = $data['providers_domain_url'];
    $email_pubs = $data['email_pubs'];
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

// Check if the record already exists based on the unique combination of `local_id`, `publisher_local_id`, and `pubs_providers_domain_url`
try {
    $stmt = $pdo->prepare("
        SELECT COUNT(*) AS total 
        FROM payment_partner_pubs_sync 
        WHERE local_id = :local_id 
        AND publisher_local_id = :publisher_local_id 
        AND pubs_providers_domain_url = :providers_domain_url
    ");
    $stmt->bindParam(':local_id', $local_id, PDO::PARAM_INT);
    $stmt->bindParam(':publisher_local_id', $publisher_local_id, PDO::PARAM_INT);
    $stmt->bindParam(':providers_domain_url', $providers_domain_url, PDO::PARAM_STR);
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
            INSERT INTO payment_partner_pubs_sync (
                local_id, publisher_local_id, pubs_providers_domain_url, email_pubs, nominal, payment_description, payment_date, payment_by
            ) VALUES (
                :local_id, :publisher_local_id, :providers_domain_url, :email_pubs, :nominal, :payment_description, :payment_date, :payment_by
            )
        ");
        $insertStmt->bindParam(':local_id', $local_id, PDO::PARAM_INT);
        $insertStmt->bindParam(':publisher_local_id', $publisher_local_id, PDO::PARAM_INT);
        $insertStmt->bindParam(':providers_domain_url', $providers_domain_url, PDO::PARAM_STR);
        $insertStmt->bindParam(':email_pubs', $email_pubs, PDO::PARAM_STR);
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
