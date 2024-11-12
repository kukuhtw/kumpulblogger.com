<?php
/*
admin/join_force.php
*/
// Start session
session_start();

// Check if the user is logged in
if (!isset($_SESSION['loggedin'])) {
    // If not logged in, redirect to login page
    header('Location: login.php');
    exit;
}

$loginemail_admin = $_SESSION['loginemail_admin'];

$change_code_error = '';
$change_code_success = '';

// Process the form if the request method is POST
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    include("../db.php");

    // Database connection using MySQLi
    $conn = new mysqli($servername_db, $username_db, $password_db, $dbname_db);
    if ($conn->connect_error) {
        error_log("Database connection failed: " . $conn->connect_error);
        exit("Database connection failed.");
    }

    // Get the target partner network domain and providers code from the POST data
    $target_partner_network_domain = $_POST['target_partner_network_domain'];
    $providers_code_target = $_POST['providers_code_target'];
    
    echo "<br>target_partner_network_domain: ".$target_partner_network_domain;
    echo "<br>providers_code_target: ".$providers_code_target;
    





    // Define the ipaddress and source_url
    $ipaddress = $_SERVER['REMOTE_ADDR']; // Get the IP address of the user
    $source_url = (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? "https" : "http") . "://$_SERVER[HTTP_HOST]$_SERVER[REQUEST_URI]";
    $browser_agent = $_SERVER['HTTP_USER_AGENT'];

    $signature = sha1(rand(1111,9999).$ipaddress.$source_url.$browser_agent);

    // Base endpoint for the API request
    $BASE_END_POINT = $target_partner_network_domain . '/API/request_join/index.php';

    echo "<br>BASE_END_POINT: ".$BASE_END_POINT;

    // Call the function to send the join request
    $response = sendJoinRequestToPartner($providers_code_target, $loginemail_admin, $signature, $target_partner_network_domain, $target_partner_network_domain, $BASE_END_POINT, $ipaddress, $source_url, $browser_agent);

    // Check and handle the response
    $status_code = isset($response['status_code']) ? $response['status_code'] : null;
    $response_message = isset($response['response']['message']) ? $response['response']['message'] : null;

    echo "<br>status_code: ".$status_code;
    echo "<br>response_message: ".$response_message;
    


    if ($status_code == 200) {
        $change_code_success = "Request sent successfully: " . $response_message;
    } else {
        $change_code_error = "Failed to send request. Error: " . $response_message;
    }
}

// Function to send the join request to the partner network
function sendJoinRequestToPartner($providers_code, $request_from, $signature, $providers_domain_url, $target_providers_domain_url, $BASE_END_POINT, $ipaddress, $source_url, $browser_agent) {
    $Header_ClientID = $providers_domain_url;
    $Header_PassKey = sha1($providers_domain_url);
    $secret_key_request = sha1($request_from . $providers_domain_url);

    $providers_api_url = $providers_domain_url."/API";

    $postData = array(
        'providers_code' => $providers_code,
        'request_from' => $request_from,
        'signature' => $signature,
        'providers_domain_url' => $providers_domain_url,
        'target_providers_domain_url' => $target_providers_domain_url,

        'providers_api_url' => $providers_api_url,

              'ipaddress' => $ipaddress,
        'source_url' => $source_url,
        'browser_agent' => $browser_agent
    );

     echo "<br>postData: ".json_encode($postData);

    // Initialize cURL
    $ch = curl_init($BASE_END_POINT);
    $headers = array(
        'Content-Type: application/json',
        'Accept: application/json',
        "Client-ID: $Header_ClientID",
        "Pass-Key: $Header_PassKey"
    );

    curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($postData));

    // Execute the request
    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);


     echo "<br>response: ".$response;

    // Close cURL session
    curl_close($ch);

    // Return response and status
    return array(
        'status_code' => $httpCode,
        'response' => json_decode($response, true)
    );
}

?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>JOIN FORCE</title>
<?php include("style_toogle.php") ?>

     <style>
        body {
            background-color: #f8f9fa;
            position: relative;
            min-height: 100vh;
        }
        .navbar {
            background-color: #343a40;
            color: white;
            padding: 10px;
            font-size: 18px;
            font-weight: bold;
        }
        .navbar a {
            color: white;
            text-decoration: none;
            margin-left: 20px;
        }
        .sidebar {
            background-color: #343a40;
            padding: 20px;
            height: 100vh;
            position: fixed;
            color: white;
        }
        .sidebar ul {
            list-style-type: none;
            padding: 0;
        }
        .sidebar ul li a {
            display: block;
            padding: 10px;
            text-decoration: none;
            color: white;
        }
        .sidebar ul li a:hover {
            background-color: #575757;
        }
        .container {
            margin-left: 250px;
            padding: 20px;
        }
        .card {
            margin-top: 20px;
            box-shadow: 0 4px 8px rgba(0, 0, 0, 0.1);
        }
        .card-header {
            background-color: #28a745;
            color: white;
            font-size: 24px;
            text-align: center;
        }
        .form-group label {
            font-weight: bold;
        }
        .btn-success {
            background-color: #28a745;
            border-color: #28a745;
        }
        .footer {
            background-color: #343a40;
            color: white;
            text-align: center;
            padding: 10px;
            position: absolute;
            bottom: 0;
            width: 100%;
        }
        .alert {
            margin-top: 20px;
        }
    </style>
</head>

<body>

<div class="navbar">
    Admin Dashboard
    <a href="logout.php" style="float:right;">Logout</a>
</div>

<?php include("sidebar_menu.php"); ?>

<div class="container" id="mainContent"> 

        <div class="col-md-6">
            <div class="card">
                <div class="card-header">
                    Join Force
                </div>
                <div class="card-body">
                    <?php if ($change_code_error) : ?>
                        <div class="alert alert-danger">
                            <?= $change_code_error ?>
                        </div>
                    <?php endif; ?>

                    <?php if ($change_code_success) : ?>
                        <div class="alert alert-success">
                            <?= $change_code_success ?>
                        </div>
                    <?php endif; ?>

                    <form action="" method="POST">
                        <div class="form-group">
                            <label for="target_partner_network_domain">Target Partner Network Domain</label>
                            <input type="text" name="target_partner_network_domain" id="target_partner_network_domain" class="form-control" required>
                        </div>

                        <div class="form-group">
                            <label for="providers_code_target">Providers Code Target</label>
                            <input type="text" name="providers_code_target" id="providers_code_target" class="form-control" required>
                        </div>

                        <button type="submit" class="btn btn-success btn-block">Update Provider Code</button>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>

<?php include("footer.php");?>

<?php include("js_toogle.php"); ?>
</body>
</html>
