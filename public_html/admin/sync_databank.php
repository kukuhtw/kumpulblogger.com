<?php
// sync_databank.php
// admin/entry_bank_account.php
session_start();

// Include your database connection file and functions
include("../db.php");
include("../function.php");


// Check if the user is logged in
if (!isset($_SESSION['loggedin'])) {
    // If not logged in, redirect to login page
    header('Location: login.php');
    exit;
}

$loginemail_admin = $_SESSION['loginemail_admin'];

// Database connection using MySQLi
$conn = new mysqli($servername_db, $username_db, $password_db, $dbname_db);
if ($conn->connect_error) {
    error_log("Database connection failed: " . $conn->connect_error);
    exit("Database connection failed.");
}

// Retrieve provider details from the database
$providers = getProvidersDetails($conn);

foreach ($providers as $provider) {
    $providers_domain_url = $provider['providers_domain_url'];
    $api_endpoint = $provider['api_endpoint'];
    $public_key = $provider['public_key'];
    $secret_key = $provider['secret_key'];
    $this_provider_domain = $provider['target_providers_domain_url'];

    // Fetch the contact person details for the current provider
    $stmt = $conn->prepare("SELECT email, whatsapp, account_name, account_bank, account_number, last_update 
                            FROM providers_contact_person 
                            WHERE id = 1 AND providers_domain_url = ?");
    $stmt->bind_param("s", $this_provider_domain);
    $stmt->execute();
    $stmt->bind_result($email, $whatsapp, $account_name, $account_bank, $account_number, $last_update);
    $stmt->fetch();
    $stmt->close();

   // echo "<br>providers_domain_url: ".$providers_domain_url;
   // echo "<br>api_endpoint: ".$api_endpoint;
   // echo "<br>this_provider_domain: ".$this_provider_domain;
   // echo "<br>email: ".$email;
   // echo "<br>whatsapp: ".$whatsapp;
   // echo "<br>account_name: ".$account_name;
   // echo "<br>last_update: ".$last_update;
    

    // If no data found for the provider, skip to the next one
    if (!$whatsapp || !$account_name || !$account_bank || !$account_number) {
        echo "No data found for provider: " . $providers_domain_url . "<br>";
        continue;
    }

    // Prepare the data for the API call
    $data = array(
        'providers_domain_url' => $this_provider_domain,
        'email' => $email,
        'whatsapp' => $whatsapp,
        'account_name' => $account_name,
        'account_bank' => $account_bank,
        'account_number' => $account_number,
        'last_update' => $last_update ? $last_update : date('Y-m-d H:i:s') // Use last_update from DB or current timestamp
    );
   // echo "<br>data: ".json_encode($data);

    // Encode the data to JSON format
    $jsonData = json_encode($data);

    // Set the API endpoint
    $url = $api_endpoint . "/pushInfoAccountBankProvider/index.php";

   //   echo "<br>url: ".$url;

    // Initialize cURL session
    $ch = curl_init($url);

    // Set cURL options
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_HTTPHEADER, array(
        'Content-Type: application/json',
        'public_key: ' . $public_key,
        'secret_key: ' . $secret_key
    ));
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_POSTFIELDS, $jsonData);

    // Execute cURL session and get the response
    $response = curl_exec($ch);

    // Check for cURL errors
    if (curl_errno($ch)) {
        $error_msg = curl_error($ch);
        error_log("cURL error: " . $error_msg);
    }

    // Close cURL session
    curl_close($ch);

    // Decode the API response
    $decodedResponse = json_decode($response, true);

    
    // Check if the API call was successful
    if (isset($decodedResponse['status']) && $decodedResponse['status'] === 'success') {
        $_SESSION['sync_message'] = "Data successfully synced for provider: " . $providers_domain_url;
    } else {
        $_SESSION['sync_message'] = "Error syncing data for provider: " . $providers_domain_url . ". Message: " . $decodedResponse['message'];
        error_log("Error syncing data: " . $response);
    }
}



function getProvidersDetails($conn) {
    $sql = "SELECT providers_domain_url, target_providers_domain_url ,api_endpoint, public_key, secret_key FROM providers_partners WHERE isapproved = 1 ";
     //echo "<br>sql =".$sql;

    $result = $conn->query($sql);
    if ($result->num_rows > 0) {
        return $result->fetch_all(MYSQLI_ASSOC);
    }
    return [];
}


// Close the database connection
$conn->close();



?>


<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Your Account Bank Data Provider Adnetwork</title>
    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
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
        .footer {
            background-color: #343a40;
            color: white;
            text-align: center;
            padding: 10px;
            position: absolute;
            bottom: 0;
            width: 100%;
        }
        .table {
            margin-top: 20px;
        }
        .pagination {
            margin-top: 20px;
            justify-content: center;
        }
    </style>
</head>
<body>

<?php include("sidebar_menu.php"); ?>

<div class="container" id="mainContent">    

    <h1>Synchronization Completed</h1>
    <p><?php echo $_SESSION['sync_message']; ?></p>
    <p>You will be redirected to the bank account page in 2 seconds...</p>

    <script type="text/javascript">
        // Redirect after 5 seconds if JavaScript is enabled
        setTimeout(function() {
            window.location.href = 'entry_bank_account.php';
        }, 2000);
    </script>

</div>

<?php
// Close the database connection

include("footer.php");
?>

<?php include("js_toogle.php"); ?>

</body>
</html>
