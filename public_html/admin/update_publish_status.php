<?php
// update_publish_status.php
session_start();

if (!isset($_SESSION['loggedin'])) {
    header('Location: login.php');
    exit;
}

include("../db.php");


// Database connection using MySQLi
$conn = new mysqli($servername_db, $username_db, $password_db, $dbname_db);
if ($conn->connect_error) {
    error_log("Database connection failed: " . $conn->connect_error);
    exit("Database connection failed.");
}

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $ad_id = $_POST['ad_id'];
    $ispublished = $_POST['ispublished'];
    $publish_date = $_POST['publish_date'];
     $is_paid = $_POST['is_paid'];
    $paid_date = $_POST['paid_date'];

    // Ensure the publish_date and paid_date are in the correct format (YYYY-MM-DD HH:MM:SS)
    if (!empty($publish_date)) {
        $publish_date = date('Y-m-d H:i:s', strtotime($publish_date));
    } else {
        $publish_date = null;
    }

    if (!empty($paid_date)) {
        $paid_date = date('Y-m-d H:i:s', strtotime($paid_date));
    } else {
        $paid_date = null;
    }

    // Update the ispublished, published_date, is_paid, and paid_date fields in the database
    $sql = "UPDATE advertisers_ads SET ispublished = ?, published_date = ?, is_paid = ?, paid_date = ? WHERE id = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("isisi", $ispublished, $publish_date, $is_paid, $paid_date, $ad_id);

    if ($stmt->execute()) {
        $_SESSION['success'] = "Ad updated successfully.";
    } else {
        $_SESSION['error'] = "Failed to update ad.";
    }

    $stmt->close();
    $conn->close();

    // Redirect back to the manage ads page
    header('Location: manage_ads.php');
    exit;
}
?>
