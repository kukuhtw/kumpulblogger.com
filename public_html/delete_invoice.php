<?php
// delete_invoice.php

// Include database connection
include("db.php");
session_start();

// Check if the user is logged in
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

// Get user ID from session
$user_id = $_SESSION['user_id'];

// Check if order_id is set in POST
if (isset($_POST['order_id'])) {
    $order_id = $_POST['order_id'];

    // Create a connection to the MySQL database
    $conn = new mysqli($servername_db, $username_db, $password_db, $dbname_db);

    // Check the connection
    if ($conn->connect_error) {
        die("Connection failed: " . $conn->connect_error);
    }

    // Delete the order from the hasil_belanja_influencer table
    $delete_query = "DELETE FROM hasil_belanja_influencer WHERE order_id = ? AND advertiser_id = ?";
    $stmt = $conn->prepare($delete_query);
    $stmt->bind_param("si", $order_id, $user_id);

    if ($stmt->execute()) {
        // Redirect to the list_invoice_payment.php page with a success message
        header("Location: list_invoice_payment.php?message=Order deleted successfully");
    } else {
        // Handle errors
        echo "Error deleting order: " . $conn->error;
    }

    $stmt->close();
    $conn->close();
} else {
    // If no order_id is set, redirect to the main page
    header("Location: list_invoice_payment.php");
}
?>
