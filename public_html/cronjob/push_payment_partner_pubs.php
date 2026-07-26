<?php
// cronjob/push_payment_partner_pubs.php

include("../db.php");

// Database connection settings
try {
    // Create a new PDO instance
    $pdo = new PDO("mysql:host=$servername_db;dbname=$dbname_db;charset=utf8mb4", $username_db, $password_db);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    // Query to select data from providers_partners table
    $stmt_providers = $pdo->query("SELECT providers_domain_url as 'target', target_providers_domain_url  as 'mynetwork', api_endpoint, public_key, secret_key FROM providers_partners");
    $providers = $stmt_providers->fetchAll(PDO::FETCH_ASSOC);

    foreach ($providers as $provider) {
        // Construct the API URL by appending "/sync_ads/index.php" to the api_endpoint
        $api_url = $provider['api_endpoint'] . "/getinfoPaymentPubsPartner/index.php";
        echo "<br>api_url: " . $api_url . "<br>";

        $target = $provider['target'];
         $mynetwork = $provider['mynetwork'];
        echo "<br>target: " . $target . "<br>";
          echo "<br>mynetwork: " . $mynetwork . "<br>";

        // Prepare the SQL query to select data from payment_partner_pubs
        $stmt_ads = $pdo->prepare("SELECT * FROM payment_partner_pubs WHERE `pubs_providers_domain_url` = :target AND payment_date BETWEEN DATE_SUB(NOW(), INTERVAL 7 DAY) AND NOW()");
        $stmt_ads->bindParam(':target', $target, PDO::PARAM_STR);
        $stmt_ads->execute();
        $ads = $stmt_ads->fetchAll(PDO::FETCH_ASSOC);

        foreach ($ads as $ad) {
            // Data that will be sent to the API
            $data = array(
                'providers_domain_url' => $mynetwork,
                'id' => $ad['id'],
                'publisher_local_id' => $ad['publisher_local_id'],
                'pubs_providers_domain_url' => $ad['pubs_providers_domain_url'],
                'email_pubs' => $ad['email_pubs'],
                'nominal' => $ad['nominal'],
                'payment_description' => $ad['payment_description'],
                'payment_date' => $ad['payment_date'],
                'payment_by' => $ad['payment_by']
            );

           

            // Convert array to JSON
            $json_data = json_encode($data);

             echo "<br>data: " . json_encode($data) . "<br>";

            // Use the public_key and secret_key from the providers_partners table
            $Header_public_key = $provider['public_key'];
            $Header_secret_key = $provider['secret_key'];

            echo "<br>Header_public_key: " . $Header_public_key . "<br>";

            echo "<br>Header_secret_key: " . $Header_secret_key . "<br>";

            $headers = array(
                'Content-Type: application/json',
                'Accept: application/json',
                "public_key: $Header_public_key",
                "secret_key: $Header_secret_key"
            );

            // Initialize cURL
            $ch = curl_init($api_url);

            // Set cURL options
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
            curl_setopt($ch, CURLOPT_POST, true);
            curl_setopt($ch, CURLOPT_POSTFIELDS, $json_data);

            // Execute cURL
            $response = curl_exec($ch);

            // Check for cURL errors
            if (curl_errno($ch)) {
                $error_msg = curl_error($ch);
                echo "cURL Error: $error_msg";
            } else {
                // Decode the JSON response from the API
                $response_data = json_decode($response, true);
                echo "response_data: " . json_encode($response_data) . "\n";

                // Check if $response_data is not null and is an array
                if (is_array($response_data)) {
                    // Display the response from the API
                    if (isset($response_data['status']) && $response_data['status'] == 'success') {
                        echo "Success: " . $response_data['message'] . "\n";
                    } else {
                        // If 'status' is not 'success' or doesn't exist
                        echo "Error: " . (isset($response_data['message']) ? $response_data['message'] : 'Unknown error') . "\n";
                    }
                } else {
                    // Handle the case where the response is null or not an array
                    echo "Error: Failed to decode API response or response is empty.\n";
                }
            }

            // Close cURL
            curl_close($ch);
        }
    }

} catch (PDOException $e) {
    echo "Database error: " . $e->getMessage();
}
?>
