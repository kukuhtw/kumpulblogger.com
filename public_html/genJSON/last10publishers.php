<?php
// last10publishers
// Include database connection
include("../db.php");

// Database connection using MySQLi
$mysqli = new mysqli($servername_db, $username_db, $password_db, $dbname_db);

// Check connection
if ($mysqli->connect_error) {
    die("Connection failed: " . $mysqli->connect_error);
}

// Query to fetch the top 10 publishers_site ordered by regdate DESC
$sql = "SELECT id, providers_domain_url, site_name, site_domain, rate_text_ads, site_desc, regdate 
        FROM publishers_site 
        ORDER BY regdate DESC 
        LIMIT 10";

$result = $mysqli->query($sql);

$publishers_list = array();

// Check if records were found
if ($result->num_rows > 0) {
    // Fetch all data into an array
    while($row = $result->fetch_assoc()) {
        $publishers_list[] = $row;
    }
} else {
    echo "No records found.";
}

// Convert the array to JSON format
$json_data = json_encode($publishers_list, JSON_PRETTY_PRINT);

// Save the JSON data to a file
$file_name = '../JSON/last10publishers.json';
if (file_put_contents($file_name, $json_data)) {
    echo "JSON file has been generated successfully.";
} else {
    echo "Failed to generate JSON file.";
}

// Close the database connection
$mysqli->close();
?>

<a href='../JSON/last10publishers.json'>Check Json</a>
