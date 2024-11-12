<?php
/*
function_ads.php
*/

function insertAdvertiser($conn, $providers_name, $providers_domain_url, $advertisers_name, $advertisers_email, $advertisers_whatsapp, $advertisers_password) {
    // Prepare the SQL statement to check if the email already exists
    $check_sql = "SELECT id FROM advertisers WHERE advertisers_email = ?";
    
    if ($check_stmt = $conn->prepare($check_sql)) {
        // Bind the email parameter to the statement
        $check_stmt->bind_param("s", $advertisers_email);
        
        // Execute the statement
        $check_stmt->execute();
        
        // Store the result
        $check_stmt->store_result();
        
        // Check if any rows were returned, meaning the email already exists
        if ($check_stmt->num_rows > 0) {
            // Email already registered, return false
            $check_stmt->close();
            return false;
        }

        // Close the check statement
        $check_stmt->close();
    } else {
        // Failed to prepare the check statement
        return false;
    }

    // Prepare the SQL statement to insert the new advertiser
    $insert_sql = "INSERT INTO advertisers (providers_name, providers_domain_url, advertisers_name, advertisers_email, advertisers_whatsapp, advertisers_password, regdate) VALUES (?, ?, ?, ?, ?, ?, ?)";
    
    // Initialize a prepared statement
    if ($insert_stmt = $conn->prepare($insert_sql)) {
        // Hash the password before storing it in the database
        $hashed_password = password_hash($advertisers_password, PASSWORD_BCRYPT);
        
        // Get the current date and time in GMT+7
        $regdate = new DateTime("now", new DateTimeZone('Asia/Jakarta'));
        $formatted_regdate = $regdate->format('Y-m-d H:i:s');
        
        // Bind the parameters to the statement
        $insert_stmt->bind_param("sssssss", $providers_name, $providers_domain_url, $advertisers_name, $advertisers_email, $advertisers_whatsapp, $hashed_password, $formatted_regdate);
        
        // Execute the statement
        if ($insert_stmt->execute()) {
            // Success
            return true;
        } else {
            // Failed to execute the query
            return false;
        }
        
        // Close the statement
        $insert_stmt->close();
    } else {
        // Failed to prepare the insert statement
        return false;
    }
}


function insertAdvertisersAds($pdo, $providers_name, $providers_domain_url, $advertisers_id, $title_ads, $description_ads, $landingpage_ads, $total_click) {
    // Set timezone to GMT+7
    $lastInsertId = 0;
    date_default_timezone_set('Asia/Jakarta');
    $regdate = date('Y-m-d H:i:s'); // Current date and time in GMT+7

    // SQL query to insert data
    $sql = "INSERT INTO advertisers_ads (providers_name, providers_domain_url, advertisers_id, title_ads, description_ads, landingpage_ads, regdate, total_click)
            VALUES (:providers_name, :providers_domain_url, :advertisers_id, :title_ads, :description_ads, :landingpage_ads, :regdate, :total_click)";

    // Prepare the statement
    $stmt = $pdo->prepare($sql);

    // Bind parameters to the SQL query
    $stmt->bindParam(':providers_name', $providers_name, PDO::PARAM_STR);
    $stmt->bindParam(':providers_domain_url', $providers_domain_url, PDO::PARAM_STR);
    $stmt->bindParam(':advertisers_id', $advertisers_id, PDO::PARAM_INT);
    $stmt->bindParam(':title_ads', $title_ads, PDO::PARAM_STR);
    $stmt->bindParam(':description_ads', $description_ads, PDO::PARAM_STR);
    $stmt->bindParam(':landingpage_ads', $landingpage_ads, PDO::PARAM_STR);
    $stmt->bindParam(':regdate', $regdate, PDO::PARAM_STR);
    $stmt->bindParam(':total_click', $total_click, PDO::PARAM_INT);

    // Execute the statement
    $stmt->execute();

    // Get the last inserted ID
    $lastInsertId = $pdo->lastInsertId();

    // Success message
    echo "Ad successfully inserted! Last inserted ID: " . $lastInsertId;

    // Close the database connection
    $pdo = null;

    // Return the last inserted ID
    return $lastInsertId;
}



function updateAdvertisersAds($pdo, $id, $current_click) {
    try {
        // SQL query to update data
        $sql = "UPDATE advertisers_ads 
                SET local_ads_id = :id, 
                    current_click = :current_click 
                WHERE id = :id";

        // Prepare the statement
        $stmt = $pdo->prepare($sql);

        // Bind parameters to the SQL query
        $stmt->bindParam(':id', $id, PDO::PARAM_INT);
        $stmt->bindParam(':current_click', $current_click, PDO::PARAM_INT);

        // Execute the statement
        $stmt->execute();

        // Success message
        return "Ad successfully updated!";
    } catch (PDOException $e) {
        // Error message
        return  "Error updating ad: " . $e->getMessage();
    } finally {
        // Close the database connection
        $pdo = null;
    }
}


?>