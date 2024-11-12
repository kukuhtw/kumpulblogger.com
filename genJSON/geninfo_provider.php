<?php

// UUUU
/*

genJson/geninfo_provider.php



*/
// Include database connection
include("../db.php");

// Database connection using MySQLi
$mysqli = new mysqli($servername_db, $username_db, $password_db, $dbname_db);

// Check connection
if ($mysqli->connect_error) {
    die("Connection failed: " . $mysqli->connect_error);
}

function generateProvidersJson($mysqli) {
    // Query to select the required fields
    $query = "SELECT id, providers_name, providers_domain_url FROM providers";
    
    // Execute the query
    $result = $mysqli->query($query);

    // Check if query was successful
    if ($result->num_rows > 0) {
        $providers = [];

        // Fetch rows and store in array
        while($row = $result->fetch_assoc()) {
            $providers[] = $row;
        }

        // Encode the array as JSON
        $json_data = json_encode($providers, JSON_PRETTY_PRINT);

        // Save JSON data to file
        $file_path = "../JSON/providers_data.json";
        if (file_put_contents($file_path, $json_data)) {
            echo "JSON file created successfully: " . $file_path;
        } else {
            echo "Error writing to file.";
        }
    } else {
        echo "No records found.";
    }
}

// Call the function to generate the JSON file
generateProvidersJson($mysqli);

// Close the database connection
$mysqli->close();
?>
