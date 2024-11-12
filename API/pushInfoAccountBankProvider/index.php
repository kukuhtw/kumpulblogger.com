<?php
/*
{BASE_END_POINT}API/pushInfoAccountBankProvider/index.php
*/

include("../../db.php");
include("../../function.php");
ini_set("error_log", "errr_.txt");
$json = file_get_contents('php://input');
$data = json_decode($json, true);
debug_text('tra1.txt', $json);

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

/*
{
    "providers_domain_url": "http:\/\/localhost\/adnetwork_baru\/adnetA",
    "whatsapp": "628129893706",
    "account_name": "kukuh tw",
    "account_bank": "BCA",
    "account_number": "255-1111-441",
    "last_update": "2024-08-24 08:11:35"
}

*/
// Cek apakah semua field yang dibutuhkan ada dalam data
if (isset($data['email']) && isset($data['whatsapp']) && isset($data['account_name']) &&
    isset($data['account_bank']) && isset($data['account_number']) && isset($data['last_update'])) {

    $email = $data['email'];
    $whatsapp = $data['whatsapp'];
    $account_name = $data['account_name'];
    $account_bank = $data['account_bank'];
    $account_number = $data['account_number'];
    $last_update = $data['last_update'];

    // Cek apakah providers_domain_url sudah ada di database
    $stmt = $pdo->prepare("SELECT COUNT(*) FROM providers_contact_person_sync WHERE providers_domain_url = :providers_domain_url");
    $stmt->execute(['providers_domain_url' => $providers_domain_url]);
    $exists = $stmt->fetchColumn();

    if ($exists) {
        // Jika ada, lakukan update
        $stmt = $pdo->prepare("
            UPDATE providers_contact_person_sync 
            SET email = :email, whatsapp = :whatsapp, account_name = :account_name, 
                account_bank = :account_bank, account_number = :account_number, last_update = :last_update
            WHERE providers_domain_url = :providers_domain_url
        ");
        $stmt->execute([
            'email' => $email,
            'whatsapp' => $whatsapp,
            'account_name' => $account_name,
            'account_bank' => $account_bank,
            'account_number' => $account_number,
            'last_update' => $last_update,
            'providers_domain_url' => $providers_domain_url
        ]);
        $response = array(
            'status' => 'success',
            'message' => 'Data successfully updated.'
        );
    } else {
        // Jika tidak ada, lakukan insert
        $stmt = $pdo->prepare("
            INSERT INTO providers_contact_person_sync 
            (providers_domain_url, email, whatsapp, account_name, account_bank, account_number, last_update) 
            VALUES (:providers_domain_url, :email, :whatsapp, :account_name, :account_bank, :account_number, :last_update)
        ");
        $stmt->execute([
            'providers_domain_url' => $providers_domain_url,
            'email' => $email,
            'whatsapp' => $whatsapp,
            'account_name' => $account_name,
            'account_bank' => $account_bank,
            'account_number' => $account_number,
            'last_update' => $last_update
        ]);
        $response = array(
            'status' => 'success',
            'message' => 'Data successfully inserted.'
        );
    }
} else {
    $response = array(
        'status' => 'error',
        'message' => 'Missing required data fields.'
    );
}

header('Content-Type: application/json');
echo json_encode($response);

