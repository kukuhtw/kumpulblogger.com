<?php
/*
cronjob/getinfoOwnerPublisherGlobal.php
*/

// Include your database connection file
include("../db.php");
include("../function.php");

// Database connection using PDO
try {
    $pdo = new PDO("mysql:host=$servername_db;dbname=$dbname_db;charset=utf8mb4", $username_db, $password_db);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch (PDOException $e) {
    error_log("Database connection failed: " . $e->getMessage());
    exit("Database connection failed.");
}

// Database connection using MySQLi
$conn = new mysqli($servername_db, $username_db, $password_db, $dbname_db);
if ($conn->connect_error) {
    error_log("Database connection failed: " . $conn->connect_error);
    exit("Database connection failed.");
}

$id = 1; // Example id to pass
//$this_providers_domain_url = get_providers_domain_url($conn, $id);

$this_providers_domain_url = get_providers_domain_url_json("../providers_data.json", 1);

// Get data from `rekap_total_publisher_partner` table for the last 24 hours
$query = "SELECT pub_id, pubs_providers_domain_url FROM rekap_total_publisher_partner WHERE rekap_date >= NOW() - INTERVAL 24 HOUR";
$stmt = $pdo->query($query);

?>

<!DOCTYPE html>
<html lang='id'>
<head>
    <meta charset='UTF-8'>
    <meta name='viewport' content='width=device-width, initial-scale=1.0'>
    <title>Proses Pengambilan Informasi Publisher</title>
    <style>
        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            margin: 0;
            padding: 0;
            background-color: #f0f2f5;
            color: #333;
        }
        h1 {
            color: #0056b3;
            text-align: center;
            padding: 20px 0;
        }
        .container {
            max-width: 1200px;
            margin: 30px auto;
            padding: 20px;
            background-color: #fff;
            border-radius: 10px;
            box-shadow: 0 0 15px rgba(0, 0, 0, 0.1);
        }
        .log {
            padding: 15px;
            background-color: #f9f9c5;
            border-left: 5px solid #f7c600;
            border-radius: 8px;
            margin-bottom: 30px;
            font-size: 1.1em;
        }
        .highlight {
            color: green;
            font-weight: bold;
        }
    </style>
</head>
<body>
    <div class='container'>
        <h1>Proses Pengambilan Informasi Publisher</h1>

        <div class='log'>
            <strong>Langkah 1: Mengambil Data Publisher dalam 24 Jam Terakhir</strong>
            <p>Query: <code><?php echo $query; ?></code></p>
        </div>

<?php

if ($stmt->rowCount() > 0) {
    while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
        $pub_id = $row['pub_id'];
        $pubs_providers_domain_url = $row['pubs_providers_domain_url'];

        echo "<div class='log'><strong>Proses Data Publisher ID:</strong> $pub_id, Domain URL: $pubs_providers_domain_url</div>";

        // Retrieve provider information from `providers_partners` table
        $provider_query = "SELECT public_key, secret_key, api_endpoint FROM providers_partners WHERE providers_domain_url = :pubs_providers_domain_url";
        $provider_stmt = $pdo->prepare($provider_query);
        $provider_stmt->bindParam(":pubs_providers_domain_url", $pubs_providers_domain_url);
        $provider_stmt->execute();

        if ($provider_stmt->rowCount() > 0) {
            $provider_row = $provider_stmt->fetch(PDO::FETCH_ASSOC);
            $public_key = $provider_row['public_key'];
            $secret_key = $provider_row['secret_key'];
            $api_endpoint = rtrim($provider_row['api_endpoint'], '/') . "/getOwnerPublisher/index.php";

            echo "<div class='log'><strong>API Endpoint:</strong> $api_endpoint</div>";

            // Set headers for the API call
            $headers = [
                'Content-Type: application/json',
                'Accept: application/json',
                "public_key: $public_key",
                "secret_key: $secret_key"
            ];

            // Prepare JSON data to send
            $data = array(
                'pub_id' => $pub_id,
                'providers_domain_url' => $this_providers_domain_url,
                'pubs_providers_domain_url' => $pubs_providers_domain_url
            );

            echo "<div class='log'><strong>Data yang Dikirim ke API:</strong><br><pre>" . json_encode($data, JSON_PRETTY_PRINT) . "</pre></div>";

            // Initialize CURL and make the API request
            $ch = curl_init($api_endpoint);
            curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($ch, CURLOPT_POST, true);
            curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));

            // Execute CURL request and capture the response
            $response = curl_exec($ch);

            if (curl_errno($ch)) {
                error_log('CURL error: ' . curl_error($ch));
            } else {
                echo "<div class='log'><strong>Response dari API:</strong><br><pre>xxxxxx</pre></div>";

                // Decode the JSON response into a PHP array
                $data = json_decode($response, true);

                if (json_last_error() === JSON_ERROR_NONE) {
                    echo "<div class='log'><strong>Data yang Diproses:</strong><br><pre>" . json_encode($data, JSON_PRETTY_PRINT) . "</pre></div>";

                    if (isset($data['data']['loginemail'], $data['data']['whatsapp'], $data['data']['bank'], $data['data']['account_name'], $data['data']['pubs_providers_domain_url'], $data['data']['publishers_local_id'], $data['data']['pub_id'])) {

                        $publishers_local_id = $data['data']['publishers_local_id'];
                        $pubs_providers_domain_url = $data['data']['pubs_providers_domain_url'];

                        updateRevenueTotal($conn, $publishers_local_id, $pubs_providers_domain_url);

                        $loginemail = $data['data']['loginemail'];
                        $whatsapp = $data['data']['whatsapp'];
                        $bank = $data['data']['bank'];
                        $account_name = $data['data']['account_name'];
                        $account_number= $data['data']['account_number'];
                        $pubs_providers_domain_url = $data['data']['pubs_providers_domain_url'];
                        $publishers_local_id = $data['data']['publishers_local_id'];
                        $pub_id = $data['data']['pub_id'];

                        echo "<div class='log'><strong>Owner Publisher:</strong> Email: , WhatsApp: xxxxx, Bank: ,Account Name: </div>";

                        // SQL to insert or update the data in publisher_partner
                        $query = "
                            INSERT INTO publisher_partner (loginemail, whatsapp, bank, account_name, account_number,pubs_providers_domain_url, publishers_local_id)
                            VALUES (:loginemail, :whatsapp, :bank, :account_name, :account_number, :pubs_providers_domain_url, :publishers_local_id)
                            ON DUPLICATE KEY UPDATE 
                                whatsapp = VALUES(whatsapp),
                                bank = VALUES(bank),
                                account_name = VALUES(account_name),
                                account_number = VALUES(account_number),
                                updated_at = NOW()
                        ";

                        // Prepare the query
                        $stmt_insert = $pdo->prepare($query);

                        // Bind parameters
                        $stmt_insert->bindParam(':loginemail', $loginemail);
                        $stmt_insert->bindParam(':whatsapp', $whatsapp);
                        $stmt_insert->bindParam(':bank', $bank);
                        $stmt_insert->bindParam(':account_name', $account_name);
                        $stmt_insert->bindParam(':account_number', $account_number);
                        

                        $stmt_insert->bindParam(':pubs_providers_domain_url', $pubs_providers_domain_url);
                        $stmt_insert->bindParam(':publishers_local_id', $publishers_local_id, PDO::PARAM_INT);

                        // Execute the query
                        if ($stmt_insert->execute()) {
                            echo "<div class='log highlight'>Record inserted/updated successfully.</div>";

                            // Update the rekap_total_publisher_partner table with the owner_id
                            $update_query = "
                                UPDATE rekap_total_publisher_partner 
                                SET owner_id = :publishers_local_id 
                                WHERE pub_id = :pub_id 
                                AND pubs_providers_domain_url = :pubs_providers_domain_url
                            ";

                            $stmt_update = $pdo->prepare($update_query);
                            $stmt_update->bindParam(':publishers_local_id', $publishers_local_id, PDO::PARAM_INT);
                            $stmt_update->bindParam(':pub_id', $pub_id, PDO::PARAM_INT);
                            $stmt_update->bindParam(':pubs_providers_domain_url', $pubs_providers_domain_url);

                            if ($stmt_update->execute()) {
                                echo "<div class='log highlight'>rekap_total_publisher_partner updated successfully.</div>";
                            } else {
                                echo "<div class='log error'>Error updating rekap_total_publisher_partner: " . $stmt_update->errorInfo()[2] . "</div>";
                            }

                        } else {
                            echo "<div class='log error'>Error inserting/updating publisher_partner: " . $stmt_insert->errorInfo()[2] . "</div>";
                        }

                    } else {
                        echo "<div class='log error'>Invalid data structure in JSON response.</div>";
                    }
                } else {
                    echo "<div class='log error'>Invalid JSON response: " . json_last_error_msg() . "</div>";
                }

                // Log response or process it as needed
                error_log('API response: ' . $response);
            }

            // Close CURL
            curl_close($ch);
        }
    }
} else {
    echo "<div class='log error'>No data found for the last 24 hours in rekap_total_publisher_partner.</div>";
    error_log('No data found for the last 24 hours in rekap_total_publisher_partner.');
}

// Close the database connection
$pdo = null;
?>

    </div>
</body>
</html>
