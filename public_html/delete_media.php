<?php
// delete_media.php

include("db.php");
include("function.php");
session_start();

// Check if the user is logged in
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    header('Allow: POST');
    exit('Method not allowed.');
}

if (!user_csrf_valid()) {
    http_response_code(403);
    exit('Permintaan tidak valid. Silakan muat ulang halaman dan coba lagi.');
}

// Get user ID and media ID from the authenticated POST request.
$user_id = (int) $_SESSION['user_id'];
$id = isset($_POST['id']) ? (int) $_POST['id'] : 0;
if ($id < 1) {
    http_response_code(400);
    exit('ID media tidak valid.');
}

// Create a connection to the MySQL database
$mysqli = new mysqli($servername_db, $username_db, $password_db, $dbname_db);

// Check connection
if ($mysqli->connect_error) {
    die("Connection failed: " . $mysqli->connect_error);
}

// Delete the media entry if the user is the owner
$delete_query = "DELETE FROM influencer_media WHERE id = ? AND owner_id = ?";
$stmt = $mysqli->prepare($delete_query);
$stmt->bind_param("ii", $id, $user_id);

if ($stmt->execute() && $stmt->affected_rows === 1) {
    header("Location: mymedia.php?msg=Media deleted successfully");
    exit;
} else {
    http_response_code(404);
    exit('Media tidak ditemukan atau bukan milik Anda.');
}

$stmt->close();
$mysqli->close();
?>
