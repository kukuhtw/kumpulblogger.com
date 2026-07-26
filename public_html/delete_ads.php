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
    $ad_id = (int)$_POST['ad_id'];

    // Database connection
    $conn = new mysqli($servername_db, $username_db, $password_db, $dbname_db);
    if ($conn->connect_error) {
        error_log("Database connection failed: " . $conn->connect_error);
        exit("Database connection failed.");
    }

    // Delete the ad from advertisers_ads
    $stmt = $conn->prepare("DELETE FROM advertisers_ads WHERE id = ?");
    $stmt->bind_param("i", $ad_id);

    if ($stmt->execute()) {
        echo "<div class='alert alert-success text-center'>Iklan berhasil dihapus!</div>";
    } else {
        echo "<div class='alert alert-danger text-center'>Gagal menghapus iklan.</div>";
    }

    $stmt->close();
    $conn->close();

    // Redirect back to the ad listing page after deletion
    header("Location: view_ads.php");
    exit();
}
?>
