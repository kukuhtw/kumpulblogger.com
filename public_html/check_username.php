<?php
// check_username.php
include("db.php");
session_start();

header('Content-Type: application/json');

if(isset($_POST['username'])) {
    $username = trim($_POST['username']);
    
    // Validasi format username: hanya huruf dan angka
    if (!preg_match('/^[a-zA-Z0-9]+$/', $username)) {
        echo json_encode(['available' => false, 'error' => 'Invalid username format']);
        exit();
    }
    
    // Buat koneksi ke database
    $mysqli = new mysqli($servername_db, $username_db, $password_db, $dbname_db);
    if($mysqli->connect_error){
        echo json_encode(['available' => false, 'error' => 'Database connection error']);
        exit();
    }
    
    $query = "SELECT * FROM publisher_quota WHERE username = ?";
    $stmt = $mysqli->prepare($query);
    $stmt->bind_param("s", $username);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if($result->num_rows > 0){
        echo json_encode(['available' => false]);
    } else {
        echo json_encode(['available' => true]);
    }
    
    $stmt->close();
    $mysqli->close();
} else {
    echo json_encode(['available' => false, 'error' => 'Username not provided']);
}
?>
