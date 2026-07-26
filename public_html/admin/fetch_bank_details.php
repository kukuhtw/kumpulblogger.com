<?php
// fetch_bank_details.php
include("../db.php");

$email = $_GET['email'];

// Database connection
$mysqli = new mysqli($servername_db, $username_db, $password_db, $dbname_db);
if ($mysqli->connect_error) {
    die(json_encode(['success' => false, 'error' => 'Database connection failed']));
}

// Prepare and execute the query to fetch bank details
$stmt = $mysqli->prepare("SELECT bank, account_name, account_number FROM publisher_partner WHERE loginemail = ?");
$stmt->bind_param("s", $email);
$stmt->execute();
$stmt->bind_result($bank, $account_name, $account_number);
$stmt->fetch();

if ($bank) {
    echo json_encode([
        'success' => true,
        'bank' => $bank,
        'account_name' => $account_name,
        'account_number' => $account_number
    ]);
} else {
    echo json_encode(['success' => false]);
}

$stmt->close();
$mysqli->close();
?>
