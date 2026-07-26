<?php
// delete_media.php

include("db.php");
session_start();

// Check if the user is logged in
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

// Get user ID and media ID from session and URL
$user_id = $_SESSION['user_id'];
$id = $_GET['id'];

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

if ($stmt->execute()) {
    header("Location: mymedia.php?msg=Media deleted successfully");
} else {
    echo "<div class='alert alert-danger'>Error: " . $stmt->error . "</div>";
}

$stmt->close();
$mysqli->close();
?>
