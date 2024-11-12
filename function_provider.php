<?php

include("function_provider_request_join.php");

function updateProviderRevenue($pdo) {
    try {
        // Step 1: Calculate total revenue from `ad_clicks_partner`
        $sqlRevenue = "SELECT pub_provider, SUM(revenue_adnetwork_partner) as total_revenue
                       FROM ad_clicks_partner
                       GROUP BY pub_provider";
        $stmtRevenue = $pdo->prepare($sqlRevenue);
        $stmtRevenue->execute();
        $revenues = $stmtRevenue->fetchAll(PDO::FETCH_ASSOC);

        // Step 2: Calculate total paid from `payment_partner_providers_sync`
        $sqlPaid = "SELECT partner_providers_domain_url, SUM(nominal) as total_paid
                    FROM payment_partner_providers_sync
                    GROUP BY partner_providers_domain_url";
        $stmtPaid = $pdo->prepare($sqlPaid);
        $stmtPaid->execute();
        $paidAmounts = $stmtPaid->fetchAll(PDO::FETCH_ASSOC);

        // Step 3: Loop through each provider and update their `my_revenue`, `my_revenue_paid`, and `my_revenue_unpaid`
        foreach ($revenues as $revenue) {
            $pub_provider = $revenue['pub_provider'];
            $totalRevenue = $revenue['total_revenue'];

            // Find the corresponding paid amount
            $totalPaid = 0;
            foreach ($paidAmounts as $paid) {
                $partner_providers_domain_url = $paid['partner_providers_domain_url'];

                 //echo "<br>partner_providers_domain_url: ".$partner_providers_domain_url;
                 //echo "<br>pub_provider: ".$pub_provider;


                $totalPaid = $paid['total_paid'];
                   // echo "<br>totalPaid: ".$totalPaid;
                
            }

            // Calculate unpaid revenue
            $totalUnpaid = $totalRevenue - $totalPaid;

           // echo "<br>totalUnpaid: ".$totalUnpaid;
           // echo "<br>totalRevenue: ".$totalRevenue;
            
           // echo "<br>totalPaid: ".$totalPaid;
            

            // Step 4: Update the `providers` table
            $sqlUpdate = "UPDATE providers 
                          SET my_revenue = :my_revenue, 
                              my_revenue_paid = :my_revenue_paid, 
                              my_revenue_unpaid = :my_revenue_unpaid
                          WHERE providers_domain_url = :providers_domain_url";
            
            $sqlUpdate_v = str_replace(":providers_domain_url",$partner_providers_domain_url,$sqlUpdate);

            $stmtUpdate = $pdo->prepare($sqlUpdate);
           //  echo "<br>sqlUpdate_v: ".$sqlUpdate_v;

            $stmtUpdate->execute([
                ':my_revenue' => $totalRevenue,
                ':my_revenue_paid' => $totalPaid,
                ':my_revenue_unpaid' => $totalUnpaid,
                ':providers_domain_url' => $partner_providers_domain_url
            ]);
        }
    } catch (PDOException $e) {
        echo "Error: " . $e->getMessage();
    }
}


function updatePartnerRevenueByDomain($mysqli, $ads_providers_domain_url) {
    try {
        // Step 1: Calculate the total revenue for the specific provider
        $sql = "
            SELECT SUM(rp.total_revenue_partner) AS total_revenue
            FROM rekap_harian_provider_partner rp
            WHERE rp.ads_providers_domain_url = ?
        ";

       // echo "<p>sql: " . $sql . " sedang diproses.</p>";
       // echo "<p>ads_providers_domain_url: " . $ads_providers_domain_url . " sedang diproses.</p>";

        // Prepare the query
        $stmt = $mysqli->prepare($sql);
        if (!$stmt) {
            throw new Exception("Error preparing SQL query: " . $mysqli->error);
        }

        // Bind the parameter
        $stmt->bind_param('s', $ads_providers_domain_url);
        
        // Execute the query
        if (!$stmt->execute()) {
            throw new Exception("Error executing SQL query: " . $stmt->error);
        }

        // Fetch the result
        $result = $stmt->get_result();
        $row = $result->fetch_assoc();

        $totalRevenue = $row['total_revenue'] ?? 0; // Default to 0 if no revenue is found

       // echo "<p>ads_providers_domain_url: " . $ads_providers_domain_url . " sedang diproses.</p>";
       // echo "<p>totalRevenue: " . $totalRevenue . " sedang diproses.</p>";

        // Step 2: Update the partner_revenue and last_updated_revenue in providers_partners
        $updateSql = "
            UPDATE providers_partners 
            SET partner_revenue = ?, 
                last_updated_revenue = NOW() 
            WHERE providers_domain_url = ?
        ";

        $updateStmt = $mysqli->prepare($updateSql);
        if (!$updateStmt) {
            throw new Exception("Error preparing update SQL query: " . $mysqli->error);
        }

        // Bind the parameters for update
        $updateStmt->bind_param('ds', $totalRevenue, $ads_providers_domain_url);
        
        // Execute the update query
        if (!$updateStmt->execute()) {
            throw new Exception("Error executing update SQL query: " . $updateStmt->error);
        }

        echo "<div class='log'><strong>Partner revenue updated successfully for domain: " . $ads_providers_domain_url."</strong></div>";;
    } catch (Exception $e) {
        echo "Error: " . $e->getMessage();
    }
}

function getProvidersNameById_JSON($json_file_path, $id) {
    // Check if the JSON file exists
    if (!file_exists($json_file_path)) {
        die("JSON file not found.");
    }

    // Read the contents of the JSON file
    $json_content = file_get_contents($json_file_path);

    // Decode the JSON data into a PHP array
    $providers_data = json_decode($json_content, true);

    // Check if decoding was successful
    if ($providers_data === null) {
        die("Failed to decode JSON.");
    }

    // Loop through the providers data to find the matching `id`
    foreach ($providers_data as $provider) {
        if ($provider['id'] == $id) {
            // Return the `providers_name` for the matching `id`
            return $provider['providers_name'];
        }
    }

    // Return null if no provider with the given `id` is found
    return null;
}



function getProvidersNameById($conn, $id) {
    // Prepare the SQL statement
    $stmt = $conn->prepare("SELECT `providers_name` FROM `providers` WHERE `id` = ?");
    
    // Bind the `id` parameter to the statement
    $stmt->bind_param("i", $id);
    
    // Execute the statement
    $stmt->execute();
    
    // Bind the result to a variable
    $stmt->bind_result($providers_name);
    
    // Fetch the result
    if ($stmt->fetch()) {
        // Return the `providers_name`
        return $providers_name;
    } else {
        // Return null if no result found
        return null;
    }
    
    // Close the statement
    $stmt->close();
}


function updateKeysByDomainAndSignature($pdo, $domainUrl, $signature, $newPublicKey, $newSecretKey) {
    try {
        // Prepare the SQL statement with placeholders
        $sql = "UPDATE providers_partners 
                SET public_key = :public_key, secret_key = :secret_key 
                WHERE providers_domain_url = :domain_url AND signature = :signature";
        
        // Prepare the statement
        $stmt = $pdo->prepare($sql);
        
        // Bind the parameters
        $stmt->bindParam(':public_key', $newPublicKey, PDO::PARAM_STR);
        $stmt->bindParam(':secret_key', $newSecretKey, PDO::PARAM_STR);
        $stmt->bindParam(':domain_url', $domainUrl, PDO::PARAM_STR);
        $stmt->bindParam(':signature', $signature, PDO::PARAM_STR);
        
        // Execute the statement
        $stmt->execute();
        
        // Check if any row was updated
        if ($stmt->rowCount() > 0) {
            return "Public key and secret key updated successfully.";
        } else {
            return "No matching provider found or keys are the same.";
        }
    } catch (PDOException $e) {
        // Handle any errors
        return "Error updating keys: " . $e->getMessage();
    }
}


function checkProviderCredentials($providers_domain_url, $public_key, $secret_key, $pdo) {

    //debug_text('function_6.txt',$providers_domain_url);
    //debug_text('function_7.txt',$public_key);
    //debug_text('function_8.txt',$secret_key);
    
    
    try {
        // Prepare the SQL statement
        $stmt = $pdo->prepare("
            SELECT COUNT(*) as count
            FROM `providers_partners`
            WHERE `providers_domain_url` = :providers_domain_url
              AND `public_key` = :public_key
              AND `secret_key` = :secret_key
        ");

        // Bind the parameters
        $stmt->bindParam(':providers_domain_url', $providers_domain_url, PDO::PARAM_STR);
        $stmt->bindParam(':public_key', $public_key, PDO::PARAM_STR);
        $stmt->bindParam(':secret_key', $secret_key, PDO::PARAM_STR);

        // Execute the statement
        $stmt->execute();

        // Fetch the result
        $result = $stmt->fetch(PDO::FETCH_ASSOC);

        // Check if any record exists
        return $result['count'] > 0;
    } catch (PDOException $e) {
        // Handle any errors
        echo "Error: " . $e->getMessage();
        return false;
    }
}



?>