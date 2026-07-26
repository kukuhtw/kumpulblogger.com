<?php

// update_paid_desc.php
include("db.php");
include("function.php");
session_start();

// Database connection
$conn = new mysqli($servername_db, $username_db, $password_db, $dbname_db);
if ($conn->connect_error) {
    error_log("Database connection failed: " . $conn->connect_error);
    exit("Database connection failed.");
}

// Check if the user is logged in
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}


// Get user ID from session
$user_id = $_SESSION['user_id'];

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
 // Get form data
    $ad_id = $_POST['ad_id'];
    $paid_desc = $_POST['paid_desc'];

    // Set timezone to GMT+7
    date_default_timezone_set('Asia/Jakarta'); // GMT +7

    // Prepare query to update `paid_desc` and `last_update` (in MySQL, set `last_update` to current time with `NOW()` + interval)
    $stmt = $conn->prepare("UPDATE advertisers_ads 
        SET paid_desc = ?, last_update = NOW() 
        WHERE id = ? AND advertisers_id = ?");

    
   // Bind the parameters
    $stmt->bind_param("sii", $paid_desc, $ad_id, $user_id);

    if ($stmt->execute()) {
        $_SESSION['success_message'] = "Deskripsi pembayaran berhasil diperbarui. ad_id:".$ad_id. " user_id: ".$user_id;
        $warning_note = $_SESSION['success_message'];
    } else {
        $_SESSION['error_message'] = "Gagal memperbarui deskripsi pembayaran.";
        $warning_note = $_SESSION['error_message'];
    }

   

// Redirect back to the ads page with a warning note
header("Location: view_ads.php?warning_note=" . urlencode($warning_note) . "&status=" . ($stmt->execute() ? 'success' : 'error'));
 $stmt->close();
    $conn->close();
 
exit();


}

?>