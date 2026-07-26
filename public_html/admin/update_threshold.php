<?php
// update_threshold.php
include("../db.php");



// Database connection using MySQLi
$mysqli = new mysqli($servername_db, $username_db, $password_db, $dbname_db);
if ($mysqli->connect_error) {
    error_log("Database connection failed: " . $mysqli->connect_error);
    exit("Database connection failed.");
}

// Check if form is submitted
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $rule_id = $_POST['rule_id'];
    $new_threshold = $_POST['threshold'];

    // Update query
    $query = "UPDATE setting_rule_clicks SET threshold = ? WHERE id = ?";
    $stmt = $mysqli->prepare($query);
    $stmt->bind_param('ii', $new_threshold, $rule_id);

    if ($stmt->execute()) {
        header("Location: list_setting_rule_clicks.php?update_success=1");
    } else {
        header("Location: list_setting_rule_clicks.php?update_fail=1");
    }

    // Close connection
    $stmt->close();
    $mysqli->close();
}
?>
