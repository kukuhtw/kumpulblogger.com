<?php
/*
admin/approval_join_force.php
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
     include("../function.php");

    // Database connection using MySQLi
    $conn = new mysqli($servername_db, $username_db, $password_db, $dbname_db);
    if ($conn->connect_error) {
        error_log("Database connection failed: " . $conn->connect_error);
        exit("Database connection failed.");
    }

    $pid = 1;
    $this_providers_domain_url = get_providers_domain_url($conn, $pid);



   // Fetch the id from the GET or POST parameter
$id = isset($_GET['id']) ? intval($_GET['id']) : (isset($_POST['id']) ? intval($_POST['id']) : 0);


    // Check if the ID is valid
    if ($id > 0) {
        // Prepare SQL statement to fetch data based on the ID
        $sql = "SELECT api_endpoint, providers_domain_url, signature FROM providers_request WHERE id = ?";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param('i', $id);
        $stmt->execute();
        $stmt->bind_result($api_endpoint, $providers_domain_url, $signature);
        $stmt->fetch();
        $stmt->close();
    } else {
        exit("Invalid request ID.");
    }

    // Get the providers_code from form input
    $providers_code = isset($_POST['providers_code']) ? $_POST['providers_code'] : '';

    echo "<br>api_endpoint: ".$api_endpoint;
     echo "<br>providers_code: ".$providers_code;
     echo "<br>providers_domain_url: ".$providers_domain_url;
    echo "<br>this_providers_domain_url: ".$this_providers_domain_url;
    echo "<br>signature: ".$signature;

    // Ensure providers_code is not empty
    if (empty($providers_code)) {
        echo "Providers code is required.";
        exit;
    }

    // Set the API URL by appending '/approve_request_partnership/index.php' to the api_endpoint
    $apiUrl = $api_endpoint . '/approve_request_partnership/index.php';

     echo "<br>apiUrl: ".$apiUrl;

    // Prepare the data array
    $data = array(
        'providers_domain_url' => $this_providers_domain_url,
        'providers_code' => $providers_code,
        'signature' => $signature,
    );



    // Display the form data and API URL for debugging
    echo "<br>data = " . json_encode($data);
    echo "<br>apiUrl = " . $apiUrl;

    // Generate the secret key
    $data['secret_key_request'] = sha1($providers_code);

    // Initialize cURL
    $ch = curl_init($apiUrl);

    // Set cURL options
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
    curl_setopt($ch, CURLOPT_HTTPHEADER, array(
        'Content-Type: application/json',
    ));

    // Execute the request and capture the response
    $response = curl_exec($ch);

    // Check for cURL errors
    if (curl_errno($ch)) {
        echo 'cURL error: ' . curl_error($ch) . "\n";
    } else {
        // Decode the response
        $responseData = json_decode($response, true);

         echo "<br>response = " . $response;

        // Check if the response contains the expected data
        if (isset($responseData['status']) && $responseData['status'] === 'success') {
            echo "Request was successful for provider {$providers_domain_url}: " . $responseData['message'] . "\n";

             $public_key = isset($responseData['public_key']) ? $responseData['public_key'] : '';
            $secret_key = isset($responseData['secret_key']) ? $responseData['secret_key'] : '';
            $message = isset($responseData['message']) ? $responseData['message'] : '';

             echo "<br>public_key = " . $public_key;
              echo "<br>secret_key = " . $secret_key;
               echo "<br>message = " . $message;

               //==========================

// SQL query to update the public_key and secret_key where providers_domain_url equals the given value

    // Set the additional fields to update
    $approved_date = date('Y-m-d H:i:s'); // Current date and time for approval
    $time_epoch_approveddate = time(); // Unix timestamp for approved date


    $sql = "UPDATE providers_partners 
            SET public_key = ?, secret_key = ? ,
            is_followup = 1 , isapproved = 1, approved_date = ?, time_epoch_approveddate = ?
            WHERE providers_domain_url = ?";
             echo "<br>message = " . $sql;
              echo "<br>providers_domain_url = " . $providers_domain_url;
    // Prepare the SQL statement
    if ($stmt = $conn->prepare($sql)) {
        // Bind the parameters (public_key, secret_key, and providers_domain_url)
           $stmt->bind_param('sssis', $public_key, $secret_key, $approved_date, $time_epoch_approveddate, $providers_domain_url);
        // Execute the statement
        if ($stmt->execute()) {
            echo "<br>Public and secret keys updated successfully for provider: " . $providers_domain_url;
        } else {
            echo "<br>Error updating keys: " . $stmt->error;
        }

// =====================


 // Update providers_request table
    $sql_request = "UPDATE providers_request 
                    SET is_followup = ?, request_date = ?, time_epoch_requestdate = ?
                    WHERE providers_domain_url = ?";

    // Prepare the SQL statement for providers_request
    if ($stmt_request = $conn->prepare($sql_request)) {
        // Set the fields for the providers_request table
        $is_followup = 1; // Assuming this is being updated to 1 as part of the follow-up process
        $request_date = date('Y-m-d H:i:s'); // Set the current date and time for the request date
        $time_epoch_requestdate = time(); // Current Unix timestamp

        // Bind the parameters for providers_request
        $stmt_request->bind_param('isis', $is_followup, $request_date, $time_epoch_requestdate, $providers_domain_url);

        // Execute the statement for providers_request
        if ($stmt_request->execute()) {
            echo "<br>Follow-up request updated successfully for provider: " . $providers_domain_url;
        } else {
            echo "<br>Error updating follow-up request in providers_request: " . $stmt_request->error;
        }

        // Close the statement
        $stmt_request->close();
    }

// ==========
            // Close the statement
            $stmt->close();
    } // 
                // ====================
        } else {
            echo "<br>Error: " . $responseData['message'] . "\n";
        }
    }

    // Close cURL
    curl_close($ch);

    // Close the database connection
    $conn->close();

}

?>


<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Approve Partnership Request</title>
    <link href="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/css/bootstrap.min.css" rel="stylesheet">
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
<div class="container">
  
      <h2>Approve Partnership Request</h2>
    <form method="POST" action="">
        <label for="providers_code">Providers Code:</label>
        <input type="text" id="providers_code" name="providers_code" required>
        <br><br>
        <input type="submit" value="Approve Request">
    </form>

</div>

<?php include("footer.php");?>

</body>
</html>


</body>
</html>

