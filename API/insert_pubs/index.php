<?php
// {BASE_END_POINT}API/insert_pubs/index.php
include("../../db.php");
include("../../function.php");
ini_set("error_log", "errr_.txt");
$json = file_get_contents('php://input');
$data = json_decode($json, true);

$publishers_name = $data['publishers_name'];
$publishers_email = $data['publishers_email'];
$publishers_whatsapp = $data['publishers_whatsapp'];
$publishers_bank = $data['publishers_bank'];
$publishers_account_name = $data['publishers_account_name'];
$publishers_account_number = $data['publishers_account_number'];
$secret_key_provider = $data['secret_key_provider'];

try {
    // Database connection using PDO for secure database interaction
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

if (isset($data['publishers_email']) && isset($data['publishers_name'])) {
    $id = 1; // This ID is static as per your request

    // Retrieve the secret key for verification
    $verifying_secret_key_provider = getSecretKeyById($conn, $id);

    // Fetch providers_name and providers_domain_url using the prepared statement
    $sqlProvider = "SELECT providers_name, providers_domain_url FROM providers WHERE id = ?";
    $stmtProvider = $conn->prepare($sqlProvider);
    $stmtProvider->bind_param("i", $id);

    if ($stmtProvider->execute()) {
        $result = $stmtProvider->get_result();
        if ($result->num_rows > 0) {
            $provider = $result->fetch_assoc();
            $providers_name = $provider['providers_name'];
            $providers_domain_url = $provider['providers_domain_url'];

            $number_random = rand(111111, 99999999) . $publishers_name . $publishers_email;
            $publishers_password = sha1($number_random);
            $publishers_password = substr($publishers_password, 0, 8);
            $hash_publishers_password = sha1($publishers_password);

            $expected_secret_key = sha1($publishers_email . $providers_domain_url . $providers_name . $publishers_whatsapp);

            if ($secret_key_provider === $verifying_secret_key_provider) {
                // Insert the publisher's data along with the provider's name and domain URL
                $sqlInsert = "INSERT INTO publishers (publishers_local_id, providers_name, providers_domain_url, publishers_name, publishers_email, publishers_password, publishers_whatsapp, publishers_bank, publishers_account_name, publishers_account_number) VALUES (NULL, ?, ?, ?, ?, ?, ?, ?, ?, ?)";
                $stmt = $conn->prepare($sqlInsert);
                $stmt->bind_param("sssssssss", $providers_name, $providers_domain_url, $publishers_name, $publishers_email, $hash_publishers_password, $publishers_whatsapp, $publishers_bank, $publishers_account_name, $publishers_account_number);

                if ($stmt->execute()) {
                    // Get the last inserted ID
                    $last_id = $conn->insert_id;

                    // Update the publishers_local_id field with the last inserted ID
                    $sqlUpdate = "UPDATE publishers SET publishers_local_id = ? WHERE id = ?";
                    $stmtUpdate = $conn->prepare($sqlUpdate);
                    $stmtUpdate->bind_param("ii", $last_id, $last_id);

                    if ($stmtUpdate->execute()) {
                        $response = array(
                            'status' => 'success',
                            'message' => 'Publisher inserted and updated successfully'
                        );
                    } else {
                        $response = array(
                            'status' => 'error',
                            'message' => 'Failed to update publishers_local_id.'
                        );
                    }
                    $stmtUpdate->close();
                } else {
                    $response = array(
                        'status' => 'error',
                        'message' => 'Failed to insert publisher. Error: ' . $stmt->error
                    );
                }
                $stmt->close();
            } else {
                // Invalid secret key
                $response = array(
                    'status' => 'error',
                    'message' => 'Invalid secret key.'
                );
            }
        } else {
            // Provider not found
            $response = array(
                'status' => 'error',
                'message' => 'Provider not found.'
            );
        }
    } else {
        // Error in executing provider query
        $response = array(
            'status' => 'error',
            'message' => 'Error retrieving provider data.'
        );
    }
    $stmtProvider->close();
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

$conn->close();
?>
