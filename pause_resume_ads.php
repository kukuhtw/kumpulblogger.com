<?php
include("db.php");
session_start();


// Database connection using MySQLi
$conn = new mysqli($servername_db, $username_db, $password_db, $dbname_db);
if ($conn->connect_error) {
    die("Koneksi gagal: " . $conn->connect_error);
}

// Check if the user is logged in
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

// Check if the form was submitted
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    // Check if ad_id is present in POST request
    if (!isset($_POST['ad_id'])) {
        die("ad_id tidak ditemukan.");
    }

    // Get the ad ID from the form
    $ad_id = $_POST['ad_id'];

    // Fetch the current status of the ad (is_paused)
    $stmt = $conn->prepare("SELECT is_paused FROM advertisers_ads WHERE id = ?");
    if (!$stmt) {
        die("Terjadi kesalahan dalam persiapan query: " . $conn->error);
    }
    $stmt->bind_param("i", $ad_id);
    $stmt->execute();
    $result = $stmt->get_result();
    $ad = $result->fetch_assoc();
    $stmt->close();

    // Check if the ad is found
    if (!$ad) {
        die("Iklan dengan ad_id tersebut tidak ditemukan.");
    }

    // Check if the ad is currently paused
    $is_paused = $ad['is_paused'];

    if ($is_paused) {
        // Resume the ad (set is_paused = 0 and paused_date = NULL)
        $update_stmt = $conn->prepare("UPDATE advertisers_ads SET is_paused = 0, paused_date = NULL WHERE id = ?");
        $update_stmt->bind_param("i", $ad_id);
    } else {
        // Pause the ad (set is_paused = 1 and update paused_date)
        $paused_date = date('Y-m-d H:i:s');
        $update_stmt = $conn->prepare("UPDATE advertisers_ads SET is_paused = 1, paused_date = ? WHERE id = ?");
        $update_stmt->bind_param("si", $paused_date, $ad_id);
    }

    // Execute the update statement
    if ($update_stmt->execute()) {
        header("Location: view_ads.php");
        exit();
    } else {
        echo "Error: " . $update_stmt->error;
    }

    $update_stmt->close();
}

$conn->close();
?>
