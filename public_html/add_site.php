
<?php
// add_site.php  Include database connection
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


// Get provider's domain URL
$this_providers_id = 1;
//$this_providers_domain_url = get_providers_domain_url($mysqli, $this_providers_id);

$this_providers_domain_url = get_providers_domain_url_json("providers_data.json", 1);

//$this_providers_name =  get_providers_name($mysqli, $this_providers_id) ;

$this_providers_name = getProvidersNameById_JSON("providers_data.json", 1);




// Continue add_site.php

// Generate random public and secret keys
$public_key = bin2hex(random_bytes(16));
$secret_key = bin2hex(random_bytes(16));

// Validate input and set variables
if (isset($_POST['site_name']) && isset($_POST['site_domain']) && isset($_POST['site_desc'])) {
    $site_name = $mysqli->real_escape_string($_POST['site_name']);
    $site_domain = $mysqli->real_escape_string($_POST['site_domain']);
    $site_desc = $mysqli->real_escape_string($_POST['site_desc']);
    
   // Get rate_text_ads from user input and validate the range
    $rate_text_ads = intval($_POST['rate_text_ads']);
    if ($rate_text_ads < 10 || $rate_text_ads > 10000) {
        echo "Error: Rate Text Ads must be between 10 and 10,000.";
        exit();
    }

    // Set default values for revenue fields
    $current_site_revenue = 0;
    $current_site_revenue_from_partner = 0;

    // Set banned status and registration date
    $isbanned = 0;
    $regdate = date('Y-m-d H:i:s', strtotime('+7 hours')); // GMT +7

    // Prepare SQL statement to insert data into publishers_site table

    // Prepare SQL statement to insert data into publishers_site table


// Prepare SQL statement to insert data into publishers_site table
$stmt = $mysqli->prepare("INSERT INTO publishers_site (
    providers_name, 
    providers_domain_url, 
    publishers_local_id, 
    site_name, 
    site_domain, 
    site_desc, 
    public_key, 
    secret_key, 
    rate_text_ads, 
    advertiser_allowed, 
    advertiser_rejected, 
    regdate, 
    current_site_revenue, 
    current_site_revenue_from_partner, 
    isbanned, 
    banned_date, 
    banned_reason
) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, '', '', ?, ?, ?, ?, NULL, '')");

if (!$stmt) {
    die("Prepare failed: " . $mysqli->error);
}

    // Bind parameters (ensure the right data types are used)
    $publishers_local_id = $user_id; // Assuming user_id is the local publisher's ID
    $stmt->bind_param("ssisssssisddi", 
        $this_providers_name, 
        $this_providers_domain_url, 
        $publishers_local_id, 
        $site_name, 
        $site_domain, 
        $site_desc, 
        $public_key, 
        $secret_key, 
        $rate_text_ads, 
        $regdate, 
        $current_site_revenue, 
        $current_site_revenue_from_partner, 
        $isbanned
    );

        // Execute the statement
    if ($stmt->execute()) {
        echo "Site added successfully!";
         header("Location: mysite.php");
        exit(); // Always call exit after header redirect to stop further script execution

    } else {
        echo "Error: " . $stmt->error;
    }

    // Close the statement
    $stmt->close();

}


?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta http-equiv="X-UA-Compatible" content="ie=edge">
    <title>Add New Publisher Site</title>
    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        body {
            background-color: #f8f9fa;
        }

        .container {
            margin-top: 50px;
            max-width: 900px;
        }

        .card {
            box-shadow: 0 4px 8px rgba(0, 0, 0, 0.1);
            border-radius: 8px;
        }

        .form-label {
            font-weight: 500;
        }

        .btn-primary {
            background-color: #007bff;
            border: none;
        }

        .btn-primary:hover {
            background-color: #0056b3;
        }

        .alert {
            margin-top: 20px;
        }

        .menu-buttons {
            display: flex;
            justify-content: center;
            flex-wrap: wrap;
            gap: 10px;
            margin-bottom: 20px;
        }

        .menu-buttons .btn {
            flex-grow: 1;
            max-width: 200px;
            text-align: center;
        }
    </style>

</head>

<body>
    <div class="container">
        <div class="card">
            <div class="card-body">
                 <?php include("main_menu.php") ?>
                <?php include("include_publisher_menu.php") ?>
                 <p>&nbsp;</p>
                <h2 class="text-center">Add New Publisher Site</h2>
                 <p>&nbsp;</p>
                <form action="add_site.php" method="POST">
                    <div class="mb-3">
                        <label for="site_name" class="form-label">Site Name</label>
                        <input type="text" class="form-control" id="site_name" name="site_name" required>
                    </div>
                    <div class="mb-3">
                        <label for="site_domain" class="form-label">Site Domain</label>
                        <input type="text" class="form-control" id="site_domain" name="site_domain" placeholder="e.g. https://example.com" required>
                    </div>
                    <div class="mb-3">
                        <label for="site_desc" class="form-label">Site Description</label>
                        <textarea class="form-control" id="site_desc" name="site_desc" rows="3" required></textarea>
                    </div>
                    <div class="mb-3">
                        <label for="rate_text_ads" class="form-label">Rate Text Ads (Between 10 and 500)</label>
                        <input type="number" class="form-control" id="rate_text_ads" name="rate_text_ads" min="10" max="10000" required>
                    </div>
                    
                    <div class="d-grid">
                        <button type="submit" class="btn btn-primary btn-block">Add Site</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- Bootstrap JS -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/js/bootstrap.bundle.min.js"></script>
</body>

</html>
