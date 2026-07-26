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

    if ($secret_key_provider === $verifying_secret_key_provider) {

        // Cek apakah email sudah terdaftar di msusers (loginemail tidak
        // dijamin unik di level database, jadi dicek eksplisit di sini)
        $check_sql = "SELECT id FROM msusers WHERE loginemail = ?";
        $check_stmt = $conn->prepare($check_sql);
        $check_stmt->bind_param("s", $publishers_email);
        $check_stmt->execute();
        $check_stmt->store_result();

        if ($check_stmt->num_rows > 0) {
            $response = array(
                'status' => 'error',
                'message' => 'Email sudah terdaftar.'
            );
            $check_stmt->close();
        } else {
            $check_stmt->close();

            $number_random = rand(111111, 99999999) . $publishers_name . $publishers_email;
            $publishers_password = sha1($number_random);
            $publishers_password = substr($publishers_password, 0, 8);
            $hash_publishers_password = password_hash($publishers_password, PASSWORD_BCRYPT);

            $regdate = new DateTime('now', new DateTimeZone('Asia/Jakarta'));
            $formatted_regdate = $regdate->format('Y-m-d H:i:s');

            // Akun publisher disimpan di `msusers` — satu tabel akun yang
            // dipakai bersama oleh publisher & advertiser (lihat
            // documentation/README.md). providers_name/providers_domain_url
            // sudah tidak dipakai karena tidak ada kolom padanannya di msusers.
            $sqlInsert = "INSERT INTO msusers (loginemail, passwords, whatsapp, realname, bank, account_name, account_number, regdate) VALUES (?, ?, ?, ?, ?, ?, ?, ?)";
            $stmt = $conn->prepare($sqlInsert);
            $stmt->bind_param(
                "ssssssss",
                $publishers_email, $hash_publishers_password, $publishers_whatsapp,
                $publishers_name, $publishers_bank, $publishers_account_name,
                $publishers_account_number, $formatted_regdate
            );

            if ($stmt->execute()) {
                $response = array(
                    'status' => 'success',
                    'message' => 'Publisher inserted successfully',
                    'id' => $conn->insert_id
                );
            } else {
                $response = array(
                    'status' => 'error',
                    'message' => 'Failed to insert publisher. Error: ' . $stmt->error
                );
            }
            $stmt->close();
        }
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

$conn->close();
?>
