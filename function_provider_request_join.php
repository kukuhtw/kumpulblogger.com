<?php
/*
function_provider_request_join
*/

function insertProvidersRequest($request_from, $signature, $providers_domain_url, $target_providers_domain_url, $providers_api_url, $ipaddress, $source_url,$browser_agent) {
    
    include 'db.php';
    $return = "";
    try {
        // Create a new PDO instance
        $pdo = new PDO("mysql:host=$servername_db;dbname=$dbname_db;charset=utf8mb4", $username_db, $password_db);
        $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

        // SQL query to check if the record already exists
        $check_sql = "SELECT COUNT(*) FROM `providers_request` WHERE `request_from` = :request_from AND `providers_domain_url` = :providers_domain_url";
        
        // Prepare the check statement
        $check_stmt = $pdo->prepare($check_sql);
        $check_stmt->bindParam(':request_from', $request_from, PDO::PARAM_STR);
        $check_stmt->bindParam(':providers_domain_url', $providers_domain_url, PDO::PARAM_STR);

        // Execute the check statement
        $check_stmt->execute();
        $count = $check_stmt->fetchColumn();


        if ($count > 0) {
            // Record already exists
            $return =  "Record already exists";
            return $return;
        } else {
            // SQL query with placeholders for inserting new record
            $sql = "INSERT INTO `providers_request` (`request_from`, `signature` , `providers_domain_url`, 
                `target_providers_domain_url`, 
                `api_endpoint`, `request_date`, `time_epoch_requestdate`,`ipaddress`,`source_url`,`browser_agent`) 
                    VALUES (:request_from, 
                        :signature, :providers_domain_url,
                        :target_providers_domain_url , 
                         :providers_api_url, :request_date, :time_epoch, :ipaddress, :source_url, :browser_agent)";

            // Prepare statement
            $stmt = $pdo->prepare($sql);

            // Bind parameters
            $stmt->bindParam(':request_from', $request_from, PDO::PARAM_STR);
            $stmt->bindParam(':signature', $signature, PDO::PARAM_STR);
            $stmt->bindParam(':providers_domain_url', $providers_domain_url, PDO::PARAM_STR);
        
            $stmt->bindParam(':target_providers_domain_url', $target_providers_domain_url, PDO::PARAM_STR);

            $stmt->bindParam(':providers_api_url', $providers_api_url, PDO::PARAM_STR);
            $stmt->bindParam(':ipaddress', $ipaddress, PDO::PARAM_STR);
            $stmt->bindParam(':source_url', $source_url, PDO::PARAM_STR);
            $stmt->bindParam(':browser_agent', $source_url, PDO::PARAM_STR);


            // Set the current date and time in GMT+7
        $request_date = new DateTime('now', new DateTimeZone('Asia/Jakarta'));

        // Store the formatted date in a variable
        $formatted_request_date = $request_date->format('Y-m-d H:i:s');

        // Bind the formatted date variable to the parameter
        $stmt->bindParam(':request_date', $formatted_request_date, PDO::PARAM_STR);

            // Get the current Unix epoch time
            $time_epoch = $request_date->getTimestamp();
            $stmt->bindParam(':time_epoch', $time_epoch, PDO::PARAM_INT);

            // Execute the statement
            $stmt->execute();

            $return =  "New record created successfully";
            return $return;

        }

    } catch (PDOException $e) {
         $return = "Error: " . $e->getMessage();
          return $return;
    }

    // Close the connection
    $pdo = null;

}

function UpdateProviderPartner($pdo,
            $providers_domain_url, $public_key, $secret_key, $isapproved) {
    // Set timezone to GMT+7
    date_default_timezone_set('Asia/Jakarta');
    
    // Prepare the approved_date and created_at values
    $approved_date = date('Y-m-d H:i:s');
    $time_epoch_approveddate = strtotime($approved_date);
    $created_at = date('Y-m-d H:i:s');

    // Update the existing record in providers_partners
            $update_sql = "UPDATE providers_partners 
                           SET approved_date = :approved_date, 
                               time_epoch_approveddate = :time_epoch_approveddate, 
                               secret_key = :secret_key, 
                               public_key = :public_key ,
                               is_followup = 1
                           WHERE providers_domain_url = :providers_domain_url";
            
            $update_stmt = $pdo->prepare($update_sql);
            $update_stmt->bindParam(':approved_date', $approved_date, PDO::PARAM_STR);
            $update_stmt->bindParam(':time_epoch_approveddate', $time_epoch_approveddate, PDO::PARAM_INT);
            $update_stmt->bindParam(':secret_key', $secret_key, PDO::PARAM_STR);
            $update_stmt->bindParam(':public_key', $public_key, PDO::PARAM_STR);
            $update_stmt->bindParam(':providers_domain_url', $providers_domain_url, PDO::PARAM_STR);
            
            $update_stmt->execute();
            
            $return = "<br>providers_domain_url = ".$providers_domain_url;
             $return .= "<br>public_key = ".$public_key;
            $return .= "<br>Data has been updated successfully with signature from providers_request.";
            return $return;

} 


function insertProviderPartner_preApproval($pdo, $signature, $providers_name, $providers_domain_url, 
    $target_providers_domain_url, $api_endpoint, $requestdate, $time_epoch_request_date, $public_key, $secret_key, $isapproved, $ipaddress, $source_url, $browser_agent) {

    // Set timezone to GMT+7
    date_default_timezone_set('Asia/Jakarta');
    
    echo '<br>signature = '.$signature;

    // Prepare the approved_date and created_at values
    $approved_date = date('Y-m-d H:i:s');
    $time_epoch_approveddate = strtotime($approved_date);
    $created_at = date('Y-m-d H:i:s');
    
    try {
        // Check if the providers_domain_url already exists
        $check_sql = "SELECT COUNT(*) FROM providers_partners WHERE providers_domain_url = :providers_domain_url";
        $check_stmt = $pdo->prepare($check_sql);
        $check_stmt->bindParam(':providers_domain_url', $providers_domain_url, PDO::PARAM_STR);
        $check_stmt->execute();
        
        $count = $check_stmt->fetchColumn();
        
        echo '<br>count = '.$count;

        if ($count > 0) {
            return "Data with this providers_domain_url already exists.";
        }

        // Prepare the SQL statement for insertion
        $is_followup = 0;
        $sql = "INSERT INTO providers_partners (signature, providers_name, providers_domain_url,    target_providers_domain_url , api_endpoint, requestdate, time_epoch_requestdate, is_followup, public_key, secret_key, isapproved, approved_date, time_epoch_approveddate, created_at, ipaddress, source_url, browser_agent) 
                VALUES (:signature, :providers_name, :providers_domain_url, 
                    :target_providers_domain_url , :api_endpoint, :requestdate, :time_epoch_request_date, 
                    :is_followup, :public_key, :secret_key, :isapproved, :approved_date, :time_epoch_approveddate, :created_at, :ipaddress, :source_url, :browser_agent)";
        
        
        // Prepare the statement
        $stmt = $pdo->prepare($sql);

        // Bind the parameters
        $stmt->bindParam(':signature', $signature, PDO::PARAM_STR);
        $stmt->bindParam(':providers_name', $providers_name, PDO::PARAM_STR);
        $stmt->bindParam(':providers_domain_url', $providers_domain_url, PDO::PARAM_STR);
        
       $stmt->bindParam(':target_providers_domain_url', $target_providers_domain_url, PDO::PARAM_STR);
    
        $stmt->bindParam(':api_endpoint', $api_endpoint, PDO::PARAM_STR);
        $stmt->bindParam(':requestdate', $requestdate, PDO::PARAM_STR);
        $stmt->bindParam(':time_epoch_request_date', $time_epoch_request_date, PDO::PARAM_INT);
        $stmt->bindParam(':is_followup', $is_followup, PDO::PARAM_INT);
        

        $stmt->bindParam(':public_key', $public_key, PDO::PARAM_STR);
        $stmt->bindParam(':secret_key', $secret_key, PDO::PARAM_STR);
        $stmt->bindParam(':isapproved', $isapproved, PDO::PARAM_INT);
        $stmt->bindParam(':approved_date', $approved_date, PDO::PARAM_STR);
        $stmt->bindParam(':time_epoch_approveddate', $time_epoch_approveddate, PDO::PARAM_INT);
        $stmt->bindParam(':created_at', $created_at, PDO::PARAM_STR);
        $stmt->bindParam(':ipaddress', $ipaddress, PDO::PARAM_STR); // IP address should be handled as a string
        $stmt->bindParam(':source_url', $source_url, PDO::PARAM_STR);
        $stmt->bindParam(':browser_agent', $browser_agent, PDO::PARAM_STR);

        // Debugging output
        echo '<br>providers_name = '.$providers_name;
        echo '<br>providers_domain_url = '.$providers_domain_url;
        echo '<br>api_endpoint = '.$api_endpoint;
        echo '<br>source_url = '.$source_url;
        echo '<br>browser_agent = '.$browser_agent;
        echo '<br>time_epoch_approveddate = '.$time_epoch_approveddate;

        // Execute the statement
        $stmt->execute();

        return "Data has been inserted successfully.";
    } catch (PDOException $e) {
        return "Error: " . $e->getMessage();
    }
}



?>