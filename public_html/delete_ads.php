<?php
// delete_ads.php
include("db.php");
include("function.php");

session_start();

// Check if the user is logged in
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

// Check if the form is submitted
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $ad_id = isset($_POST['ad_id']) ? (int) $_POST['ad_id'] : 0;
    $user_id = (int) $_SESSION['user_id'];
    if ($ad_id < 1) {
        http_response_code(400);
        exit('ID iklan tidak valid.');
    }

    // Database connection
    $conn = new mysqli($servername_db, $username_db, $password_db, $dbname_db);
    if ($conn->connect_error) {
        error_log("Database connection failed: " . $conn->connect_error);
        exit("Database connection failed.");
    }

    // Delete the ad from advertisers_ads
    $stmt = $conn->prepare("DELETE FROM advertisers_ads WHERE id = ? AND advertisers_id = ?");
    $stmt->bind_param("ii", $ad_id, $user_id);

    if (!$stmt->execute()) {
        error_log('Failed to delete ad: ' . $stmt->error);
        http_response_code(500);
        exit('Gagal menghapus iklan.');
    }
    if ($stmt->affected_rows !== 1) {
        http_response_code(404);
        exit('Iklan tidak ditemukan atau bukan milik Anda.');
    }

    $stmt->close();
    $conn->close();

    // Redirect back to the ad listing page after deletion
    header("Location: view_ads.php");
    exit();
}
?>
