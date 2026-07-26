<?php
// Include database connection
include("../db.php");

// Database connection using MySQLi
$mysqli = new mysqli($servername_db, $username_db, $password_db, $dbname_db);

// Check connection
if ($mysqli->connect_error) {
    die("Connection failed: " . $mysqli->connect_error);
}

// Query to fetch the last 500 clicks ordered by click_time
$sql = "SELECT * FROM ad_clicks ORDER BY click_time DESC LIMIT 500";
$result = $mysqli->query($sql);

$clicks_data = array();

if ($result->num_rows > 0) {
    // Fetch all the rows and store them in the array
    while ($row = $result->fetch_assoc()) {
        $clicks_data[] = $row;
    }
}

$info_tag = array(
    "info" => "data 500 klik terakhir yang terjadi dari adnetwork lokal. berguna untuk melihat transaksi klik terkini.",
    "data" => $clicks_data
);
// Encode the array to JSON
$json_data = json_encode($info_tag, JSON_PRETTY_PRINT);

// Save the JSON data to a file
$file_name = '../JSON/last500clicked.json';
if (file_put_contents($file_name, $json_data)) {
    echo "JSON file has been generated successfully.";
} else {
    echo "Failed to generate JSON file.";
}

// Close the database connection
$mysqli->close();
?>
<a href='../JSON/last500clicked.json'>Check Json</a>

