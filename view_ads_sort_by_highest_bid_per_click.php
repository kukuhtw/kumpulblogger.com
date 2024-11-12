<?php

// view_ads_sort_by_highest_bid_per_click.php
include("db.php");
include("function.php");

session_start();

// Database connection using MySQLi
$conn = new mysqli($servername_db, $username_db, $password_db, $dbname_db);
if ($conn->connect_error) {
    error_log("Database connection failed: " . $conn->connect_error);
    exit("Database connection failed.");
}

// Check if the user is logged in
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

// Get user ID from session
$user_id = $_SESSION['user_id'];

// Assuming get_providers_domain_url is a helper function to retrieve domain URL for the provider
$this_providers_id = 1;
//$this_providers_domain_url = get_providers_domain_url($conn, $this_providers_id);

$this_providers_domain_url = get_providers_domain_url_json("providers_data.json", 1);

// SQL query to fetch ads sorted by highest bid per click
$sql = "
(
  SELECT 
    local_ads_id,
    providers_name,
    providers_domain_url,
    advertisers_id,
    title_ads,
    landingpage_ads, 
    budget_per_click_textads,
    'advertisers_ads' AS source_table
  FROM 
    advertisers_ads
  WHERE 
    ispublished = 1 
    AND is_paused = 0
)
UNION
(
  SELECT 
    local_ads_id,
    providers_name,
    providers_domain_url,
    advertisers_id,
    title_ads,
    landingpage_ads, 
    budget_per_click_textads,
    'advertisers_ads_partners' AS source_table
  FROM 
    advertisers_ads_partners
  WHERE 
    ispublished = 1 
    AND is_paused = 0
)
ORDER BY budget_per_click_textads DESC
LIMIT 0, 100
";

// Execute the query
$result = $conn->query($sql);

// Start HTML with Bootstrap integration
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Ads by Highest Bid Per Click</title>
    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body>

<div class="container mt-5">
    <?php include("main_menu.php") ?>
        <?php include("include_publisher_menu.php") ?>

    <h2 class="mb-4">Ads Sorted by Highest Bid Per Click</h2>
    <?php
    if ($result->num_rows > 0) {
        // Display results in an HTML Bootstrap table
        echo '<table class="table table-striped table-bordered">';
        echo '<thead class="table-dark">';
        echo '<tr>
                <th>Ad ID</th>
                <th>Provider Name</th>
                <th>Provider Domain URL</th>
                <th>Advertiser ID</th>
                <th>Ad Title</th>
                <th>Landing Page</th>
                <th>Budget Per Click</th>
                <th>Source Table</th>
              </tr>';
        echo '</thead>';
        echo '<tbody>';
        
        while ($row = $result->fetch_assoc()) {

          $source_table = $row['source_table'];
          if ($source_table=="advertisers_ads") {
            $source_table="Local Advertiser";
          }
          else {
            $source_table="Partner Global AdNetworl - Advertiser";
          }

            echo '<tr>';
            echo '<td>' . htmlspecialchars($row['local_ads_id']) . '</td>';
            echo '<td>' . htmlspecialchars($row['providers_name']) . '</td>';
            echo '<td>' . htmlspecialchars($row['providers_domain_url']) . '</td>';
            echo '<td>' . htmlspecialchars($row['advertisers_id']) . '</td>';
            echo '<td>' . htmlspecialchars($row['title_ads']) . '</td>';
            echo '<td><a href="'.$row['landingpage_ads'].'" target="_blank">'.$row['landingpage_ads'].'</a></td>';


            echo '<td>Rp ' . htmlspecialchars($row['budget_per_click_textads']) . '</td>';
            echo '<td>' . htmlspecialchars($source_table) . '</td>';
            echo '</tr>';
        }
        
        echo '</tbody>';
        echo '</table>';
    } else {
        echo '<div class="alert alert-warning">No ads found.</div>';
    }

    // Close the database connection
    $conn->close();
    ?>
</div>

<!-- Bootstrap JS and dependencies -->
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/js/bootstrap.bundle.min.js"></script>

</body>
</html>
