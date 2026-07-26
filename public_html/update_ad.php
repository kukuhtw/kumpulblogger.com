<?php

// update_ad.php

// Include database connection and necessary functions
include("db.php");
include("function.php");
session_start();

// Check if the user is logged in
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

// Get user ID from session
$user_id = $_SESSION['user_id'];

// Create a connection to the MySQL database
$mysqli = new mysqli($servername_db, $username_db, $password_db, $dbname_db);

// Check the connection
if ($mysqli->connect_error) {
    die("Connection failed: " . $mysqli->connect_error);
}



// Check if the form was submitted
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Get the posted values
    $ad_id = (int)$_POST['ad_id'];
    $is_approved_by_publisher = (int)$_POST['is_approved_by_publisher'];

    $publisher_site_local_id = (int)$_POST['publisher_site_local_id']; // Ensure you get the correct value here



    // Get provider's domain URL
    $this_providers_id = 1;
    //$this_providers_domain_url = get_providers_domain_url($mysqli, $this_providers_id);


    $this_providers_domain_url = get_providers_domain_url_json("providers_data.json", 1);


    // Create a connection to the MySQL database
    $mysqli = new mysqli($servername_db, $username_db, $password_db, $dbname_db);

    // Check the connection
    if ($mysqli->connect_error) {
        die("Connection failed: " . $mysqli->connect_error);
    }

    // Prepare the update query
    $sql = "UPDATE mapping_advertisers_ads_publishers_site 
            SET is_approved_by_publisher = ? 
            WHERE id = ? 
            AND publishers_site_local_id = ?
            AND pubs_providers_domain_url = ?";

    // Prepare the statement
    $stmt = $mysqli->prepare($sql);

    // Bind the parameters (approval status, ad ID, publisher site local ID, provider domain URL)
    $stmt->bind_param("iiis", $is_approved_by_publisher, $ad_id, $publisher_site_local_id, $this_providers_domain_url);

    // Execute the statement
    if ($stmt->execute()) {
        // Redirect back to the previous page after update
        header("Location: mysite_ads.php?publisher_site_local_id=" . $publisher_site_local_id.'&status=success');
        exit();
    } else {
        echo "Error updating record: " . $mysqli->error;
    }

    // Close the statement and connection
    $stmt->close();
    $mysqli->close();
} else {
    // Redirect back if the form wasn't submitted
    header("Location: mysite_ads.php");
    exit();
}

?>
