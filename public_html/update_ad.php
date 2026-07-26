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
$user_id = (int) $_SESSION['user_id'];

// Create a connection to the MySQL database
$mysqli = new mysqli($servername_db, $username_db, $password_db, $dbname_db);

// Check the connection
if ($mysqli->connect_error) {
    die("Connection failed: " . $mysqli->connect_error);
}



// Check if the form was submitted
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Get the posted values
    $ad_id = isset($_POST['ad_id']) ? (int) $_POST['ad_id'] : 0;
    $is_approved_by_publisher = isset($_POST['is_approved_by_publisher'])
        ? (int) $_POST['is_approved_by_publisher']
        : -1;
    $publisher_site_local_id = isset($_POST['publisher_site_local_id'])
        ? (int) $_POST['publisher_site_local_id']
        : 0;

    if ($ad_id < 1 || $publisher_site_local_id < 1 || !in_array($is_approved_by_publisher, [0, 1], true)) {
        http_response_code(400);
        exit('Parameter approval tidak valid.');
    }



    // Get provider's domain URL
    $this_providers_id = 1;
    //$this_providers_domain_url = get_providers_domain_url($mysqli, $this_providers_id);


    $this_providers_domain_url = get_providers_domain_url_json("providers_data.json", 1);


    // The mapping is editable only when its publisher site belongs to the
    // logged-in publisher. Hidden IDs from the browser are not trusted.
    $ownership_sql = "
        SELECT 1
        FROM mapping_advertisers_ads_publishers_site AS m
        INNER JOIN publishers_site AS ps
            ON ps.id = m.publishers_site_local_id
        WHERE m.id = ?
          AND m.publishers_site_local_id = ?
          AND m.pubs_providers_domain_url = ?
          AND ps.publishers_local_id = ?
        LIMIT 1";
    $ownership_stmt = $mysqli->prepare($ownership_sql);
    $ownership_stmt->bind_param("iisi", $ad_id, $publisher_site_local_id, $this_providers_domain_url, $user_id);
    $ownership_stmt->execute();
    $owns_mapping = $ownership_stmt->get_result()->fetch_row() !== null;
    $ownership_stmt->close();
    if (!$owns_mapping) {
        $mysqli->close();
        http_response_code(404);
        exit('Mapping tidak ditemukan atau bukan milik Anda.');
    }

    // Prepare the update query
    $sql = "UPDATE mapping_advertisers_ads_publishers_site 
            SET is_approved_by_publisher = ? 
            WHERE id = ? 
            AND publishers_site_local_id = ?
            AND pubs_providers_domain_url = ?
            AND EXISTS (
                SELECT 1 FROM publishers_site
                WHERE publishers_site.id = mapping_advertisers_ads_publishers_site.publishers_site_local_id
                  AND publishers_site.publishers_local_id = ?
            )";

    // Prepare the statement
    $stmt = $mysqli->prepare($sql);

    // Bind the parameters (approval status, ad ID, publisher site local ID, provider domain URL)
    $stmt->bind_param("iiisi", $is_approved_by_publisher, $ad_id, $publisher_site_local_id, $this_providers_domain_url, $user_id);

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
