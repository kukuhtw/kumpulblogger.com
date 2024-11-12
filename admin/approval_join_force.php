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
    //$this_providers_domain_url = get_providers_domain_url($conn, $pid);

    $this_providers_domain_url = get_providers_domain_url_json("providers_data.json", 1);



   // Fetch the id from the GET or POST parameter
    $id = isset($_GET['id']) ? intval($_GET['id']) : (isset($_POST['id']) ? intval($_POST['id']) : 0);


        // Check if the ID is valid
        if ($id > 0) {
            // Prepare SQL statement to fetch data based on the ID
            $sql = "SELECT `api_endpoint`, `providers_domain_url`, `signature` FROM `providers_request` WHERE `id` = ?";
            $stmt = $conn->prepare($sql);
            $stmt->bind_param('i', $id);
            $stmt->execute();
            $stmt->bind_result($api_endpoint, $providers_domain_url, $signature);
            $stmt->fetch();
            $stmt->close();
        } else {
            $change_code_error = "Invalid request ID.";
        }

        // Get the providers_code from form input
        $providers_code = isset($_POST['providers_code']) ? $_POST['providers_code'] : '';

        // Ensure providers_code is not empty
        if (empty($providers_code)) {
            $change_code_error = "Providers code is required.";
        } else {
            // Set the API URL
            $apiUrl = $api_endpoint . '/approve_request_partnership/index.php';

            // Prepare the data array
            $data = [
                'providers_domain_url' => $this_providers_domain_url,
                'providers_code' => $providers_code,
                'signature' => $signature,
                'secret_key_request' => sha1($providers_code)
            ];

            // Display the form data and API URL for debugging
    //echo "<br>data = " . json_encode($data);
   // echo "<br>apiUrl = " . $apiUrl;


            // Initialize cURL
            $ch = curl_init($apiUrl);
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($ch, CURLOPT_POST, true);
            curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
            curl_setopt($ch, CURLOPT_HTTPHEADER, ['Content-Type: application/json']);

            // Execute the request
            $response = curl_exec($ch);

            // Check for cURL errors
            if (curl_errno($ch)) {
                $change_code_error = 'cURL error: ' . curl_error($ch);
            } else {
                $responseData = json_decode($response, true);

                // Process response
                if (isset($responseData['status']) && $responseData['status'] === 'success') {
                    $public_key = $responseData['public_key'] ?? '';
                    $secret_key = $responseData['secret_key'] ?? '';
                    $message = $responseData['message'] ?? '';

                    // Prepare SQL for updating `providers_partners`
                    $approved_date = date('Y-m-d H:i:s');
                    $time_epoch_approveddate = time();

                    $sql = "UPDATE `providers_partners` 
                            SET `public_key` = ?, `secret_key` = ?,
                            `is_followup` = 1, `isapproved` = 1, `approved_date` = ?, `time_epoch_approveddate` = ?
                            WHERE `providers_domain_url` = ?";
                    $stmt = $conn->prepare($sql);
                    $stmt->bind_param('sssis', $public_key, $secret_key, $approved_date, $time_epoch_approveddate, $providers_domain_url);
                    if ($stmt->execute()) {
                        $change_code_success = "Public and secret keys updated successfully for provider: $providers_domain_url";
                    } else {
                        $change_code_error = "Error updating keys: " . $stmt->error;
                    }

                    // Update `providers_request` table
                    $sql_request = "UPDATE `providers_request` 
                                    SET `is_followup` = ?, `request_date` = ?, `time_epoch_requestdate` = ?
                                    WHERE `providers_domain_url` = ?";
                    $stmt_request = $conn->prepare($sql_request);
                    $is_followup = 1;
                    $request_date = date('Y-m-d H:i:s');
                    $time_epoch_requestdate = time();
                    $stmt_request->bind_param('isis', $is_followup, $request_date, $time_epoch_requestdate, $providers_domain_url);
                    if ($stmt_request->execute()) {
                        $change_code_success .= "<br>Follow-up request updated successfully for provider: $providers_domain_url";
                    } else {
                        $change_code_error = "Error updating follow-up request in providers_request: " . $stmt_request->error;
                    }
                    $stmt_request->close();
                    $stmt->close();
                } else {
                    $change_code_error = "Error: " . ($responseData['message'] ?? 'Unknown error.');
                }
            }
            curl_close($ch);
        }

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

    <?php if (!empty($change_code_error)) : ?>
        <div class="alert alert-danger" role="alert">
            <?php echo $change_code_error; ?>
        </div>
    <?php endif; ?>

    <?php if (!empty($change_code_success)) : ?>
        <div class="alert alert-success" role="alert">
            <?php echo $change_code_success; ?>
        </div>
    <?php endif; ?>

    <form method="POST" action="">
        <label for="providers_code">Providers Code:</label>
        <input type="text" id="providers_code" name="providers_code" required>
        <br><br>
        <input type="submit" value="Approve Request" class="btn btn-success">
    </form>

</div>

<?php include("footer.php"); ?>

</body>
</html>
