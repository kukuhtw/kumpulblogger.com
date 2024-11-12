<?php
// confirm_payment.php

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

// Create a connection to the MySQL database
$conn = new mysqli($servername_db, $username_db, $password_db, $dbname_db);

// Check the connection
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// Check if form data is submitted
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    // Get form data
    $order_id = $_POST['order_id'];
    $grand_total = $_POST['grand_total'];
    $bank_name = $_POST['bank_name'];
    $payment_date = $_POST['payment_date'];
    $sender_name = $_POST['sender_name'];
    $sender_bank = $_POST['sender_bank'];
    
    // Create payment confirmation message
    $payment_message = "Halo, admin saya sudah membayar order id: $order_id, sebesar Rp $grand_total melalui bank $bank_name pada hari jam $payment_date dengan akun pengirim $sender_name bank $sender_bank.";
    
    // Insert into log_payment_order_influencer
    $query = "
        INSERT INTO log_payment_order_influencer (advertiser_id, order_id, payment_message, payment_date)
        VALUES (?, ?, ?, ?)
    ";
    $stmt = $conn->prepare($query);
    $stmt->bind_param("isss", $user_id, $order_id, $payment_message, $payment_date);
    
    if ($stmt->execute()) {
        // Redirect to list_invoice_payment.php with a success message
        header("Location: list_invoice_payment.php?message=Payment confirmed for Order ID $order_id");
    } else {
        // Redirect with an error message
        header("Location: list_invoice_payment.php?message=Error confirming payment for Order ID $order_id");
    }

    $stmt->close();
    $conn->close();
} else {
    // Redirect to main page if accessed directly
    header("Location: list_invoice_payment.php");
}
?>
