<?php
// update_approval_advertiser_partner.php
include("db.php");
include("function.php");
session_start();

// Database connection
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
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $id = $_POST['local_mapping_id'];
    $is_approved_by_advertiser = $_POST['is_approved_by_advertiser'];
    $local_ads_id = (int)$_POST['local_ads_id'];
    $publishers_site_local_id= (int)$_POST['publishers_site_local_id'];
    $pubs_providers_domain_url= $_POST['pubs_providers_domain_url'];
    $ads_providers_domain_url= $_POST['ads_providers_domain_url'];


    // Get user ID and provider domain URL
    $this_providers_id = 1;
    //$this_providers_domain_url = get_providers_domain_url($conn, $this_providers_id);

    $this_providers_domain_url = get_providers_domain_url_json("providers_data.json", 1);



    // Update local
    $query = "UPDATE  mapping_advertisers_ads_publishers_site_from_partners SET is_approved_by_advertiser = ?, approval_date_advertiser = now() WHERE local_mapping_id  = ? AND    local_ads_id = ? AND publishers_site_local_id = ? " ;
    $stmt = $conn->prepare($query);
    $stmt->bind_param('iiii', $is_approved_by_advertiser, $id , $local_ads_id , $publishers_site_local_id);

   echo "<br>query: ".$query;
   echo "<br>is_approved_by_advertiser: ".$is_approved_by_advertiser;
    echo "<br>id: ".$id;
    echo "<br>local_ads_id: ".$local_ads_id;
    echo "<br>publishers_site_local_id: ".$publishers_site_local_id;
      $stmt->execute();


    $query = "SELECT api_endpoint, public_key, secret_key FROM providers_partners 
              WHERE target_providers_domain_url = ? 
              AND providers_domain_url  = ?";

     echo "<br>query: ".$query;
              
    $stmt = $conn->prepare($query);
    $stmt->bind_param('ss', $this_providers_domain_url, $pubs_providers_domain_url);

    echo "<br>this_providers_domain_url: ".$this_providers_domain_url;
    echo "<br>pubs_providers_domain_url: ".$pubs_providers_domain_url;

    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows > 0) {
        $provider = $result->fetch_assoc();
        $api_url = $provider['api_endpoint']."/approval_advertiser_partner/index.php";
        $public_key = $provider['public_key'];
        $secret_key = $provider['secret_key'];

         echo "<br>api_url: ".$api_url;
         echo "<br>public_key: ".$public_key;
         echo "<br>secret_key: ".$secret_key;
        
        // Prepare the data for the API request
        $data = array(
            'providers_domain_url'=> $this_providers_domain_url,
            'id' => $id,
            'is_approved_by_advertiser' => $is_approved_by_advertiser,
            'local_ads_id' => $local_ads_id,
            'publishers_site_local_id' => $publishers_site_local_id,
            'pubs_providers_domain_url' => $pubs_providers_domain_url,
            'ads_providers_domain_url' => $ads_providers_domain_url,
        );


        
        // Convert data to JSON format
        $json_data = json_encode($data);

         echo "<br>json_data: ".$json_data;

        // Set headers
        $headers = array(
            'Content-Type: application/json',
            'Accept: application/json',
            "public_key: $public_key",
            "secret_key: $secret_key"
        );

        echo "<br>headers: ".json_encode($headers);

        // Initialize cURL
        $ch = curl_init($api_url);

        // Set cURL options
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $json_data);

        // Execute cURL and capture the response
        $response = curl_exec($ch);
         echo "<br>response: ".$response;

        // Check for errors in cURL execution
        if ($response === false) {
            $error = curl_error($ch);
            echo "cURL Error: " . $error;
        } else {
            // Handle the API response
            $response_data = json_decode($response, true);

            if ($response_data['status'] == 'success') {
                // Redirect to the success page or back to the list
                header('Location: view_ads_publishers_partner_mapping.php?local_ads_id='.$local_ads_id.'&status=success');
                exit();
            } else {
                // Handle API response error
                echo "<br>API Error: " . $response_data['message'];
            }
        }

        // Close cURL
        curl_close($ch);
    } else {
        // Handle case when no matching provider is found
        echo "<br>No matching provider found in providers_partners table";
    }


}
?>