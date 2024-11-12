<?php
/*
function_publisher.php
*/

// Function to update partner_revenue_paid and partner_revenue_unpaid in msusers table
function updatePartnerRevenue($pdo, $user_id, $loginemail) {
   
        // Step 1: Sum nominal from payment_partner_pubs_sync
        $stmt_sum_nominal = $pdo->prepare("
            SELECT SUM(nominal) as total_paid 
            FROM payment_partner_pubs_sync 
            WHERE publisher_local_id = :user_id AND email_pubs = :loginemail
        ");
        $stmt_sum_nominal->bindParam(':user_id', $user_id, PDO::PARAM_INT);
        $stmt_sum_nominal->bindParam(':loginemail', $loginemail, PDO::PARAM_STR);
        $stmt_sum_nominal->execute();
        
        $result = $stmt_sum_nominal->fetch(PDO::FETCH_ASSOC);
        $total_paid = $result['total_paid'] ? $result['total_paid'] : 0;

        // Step 2: Update msusers table - partner_revenue_paid and partner_revenue_unpaid
        $stmt_update_msusers = $pdo->prepare("
            UPDATE msusers
            SET partner_revenue_paid = :total_paid,
                partner_revenue_unpaid = (current_revenue_from_partner - :total_paid),
                last_updated_revenue = NOW()
            WHERE id = :user_id AND loginemail = :loginemail
        ");
        $stmt_update_msusers->bindParam(':total_paid', $total_paid, PDO::PARAM_STR);
        $stmt_update_msusers->bindParam(':user_id', $user_id, PDO::PARAM_INT);
        $stmt_update_msusers->bindParam(':loginemail', $loginemail, PDO::PARAM_STR);
        
        if ($stmt_update_msusers->execute()) {
            echo "Successfully updated partner_revenue_paid and partner_revenue_unpaid for user ID: $user_id";
        } else {
            echo "Failed to update partner_revenue for user ID: $user_id";
        }

}




// Function to calculate and update revenue_paid and revenue_unpaid
function updatePublisherRevenuePaid_unPaid($mysqli, $publisher_local_id, $pubs_providers_domain_url, $loginemail) {
    // Step 1: Fetch total revenue_paid based on matching conditions
    $sql = "
    SELECT 
        SUM(p.nominal) AS total_paid
    FROM 
        payment_partner_pubs p
    JOIN 
        publisher_partner pp
    ON 
        pp.publishers_local_id = p.publisher_local_id 
        AND pp.pubs_providers_domain_url = p.pubs_providers_domain_url
        AND pp.loginemail = p.email_pubs
    WHERE 
        pp.publishers_local_id = ?
        AND pp.pubs_providers_domain_url = ?
        AND pp.loginemail = ?
    ";

    $stmt = $mysqli->prepare($sql);
    // Check if the preparation was successful
    if ($stmt === false) {
        // Output the MySQL error
        echo "Error preparing statement: " . $mysqli->error;
        return;
    }

    $stmt->bind_param("iss", $publisher_local_id, $pubs_providers_domain_url, $loginemail);
    $stmt->execute();
    $stmt->bind_result($total_paid);
    $stmt->fetch();
    $stmt->close();

    // Check if we have a result for total_paid
    if ($total_paid !== null) {
        // Step 2: Update revenue_paid and calculate revenue_unpaid
        $update_sql = "
        UPDATE publisher_partner
        SET 
            revenue_paid = ?,
            revenue_unpaid = revenue_total - ?,
            updated_at = NOW()
        WHERE 
            publishers_local_id = ?
            AND pubs_providers_domain_url = ?
            AND loginemail = ?";

        $stmt = $mysqli->prepare($update_sql);
        $stmt->bind_param("ddiss", $total_paid, $total_paid, $publisher_local_id, $pubs_providers_domain_url, $loginemail);
        
        if ($stmt->execute()) {
            echo "Revenue paid and unpaid successfully updated for publisher ID: " . $publisher_local_id;
        } else {
            echo "Error updating revenue: " . $stmt->error;
        }

        $stmt->close();
    } else {
        echo "No matching records found for publisher ID: " . $publisher_local_id;
    }
}


// Function to update the revenue_total in publisher_partner table
function updateRevenueTotal($conn, $publishers_local_id, $pubs_providers_domain_url) {
    // Query to sum total_revenue_publishers from rekap_total_publisher_partner
    $query = "SELECT SUM(total_revenue_publishers) AS total_revenue 
              FROM rekap_total_publisher_partner 
              WHERE owner_id = ? 
              AND pubs_providers_domain_url = ?";

        
    $stmt = $conn->prepare($query);
    $stmt->bind_param("is", $publishers_local_id, $pubs_providers_domain_url);
    $stmt->execute();
    $result = $stmt->get_result();


    
    
    if ($result->num_rows > 0) {
        $row = $result->fetch_assoc();
        $total_revenue = $row['total_revenue'];

       

        // Update the revenue_total in publisher_partner table
        $update_query = "UPDATE publisher_partner 
                         SET revenue_total = ? 
                         WHERE publishers_local_id = ? 
                         AND pubs_providers_domain_url = ?";

                       
        
        $update_stmt = $conn->prepare($update_query);
        $update_stmt->bind_param("dis", $total_revenue, $publishers_local_id, $pubs_providers_domain_url);
        $update_stmt->execute();
        
        if ($update_stmt->affected_rows > 0) {
            return "Revenue updated successfully for publisher ID: $publishers_local_id";
        } else {
            return "No update made for publisher ID: $publishers_local_id";
        }
    } else {
        return "No matching data found in rekap_total_publisher_partner.";
    }
}

// Fungsi untuk melakukan rekap total publisher partner
function rekapTotalPublisherPartner($mysqli) {
    // Query untuk merekap total revenue dan klik dari tabel rekap_publisher_revenue_harian_partner
    $query = "
        SELECT 
            pub_id, 
            site_name, 
            site_domain, 
            pubs_providers_domain_url,
            SUM(total_revenue_publishers) AS total_revenue_publishers,
            SUM(total_clicks) AS total_clicks
        FROM rekap_publisher_revenue_harian_partner
        GROUP BY pub_id, site_name, site_domain, pubs_providers_domain_url
    ";

    echo "<br>query:".$query;

    if ($result = $mysqli->query($query)) {
        // Loop untuk memproses setiap baris hasil query
        while ($row = $result->fetch_assoc()) {
            $pub_id = $row['pub_id'];
            $site_name = $row['site_name'];
            $site_domain = $row['site_domain'];
            $pubs_providers_domain_url = $row['pubs_providers_domain_url'];
            $total_revenue_publishers = $row['total_revenue_publishers'];
            $total_clicks = $row['total_clicks'];

             echo "<br>pub_id:".$pub_id;
echo "<br>site_name:".$site_name;
echo "<br>site_domain:".$site_domain;
echo "<br>pubs_providers_domain_url:".$row['pubs_providers_domain_url'];
echo "<br>total_revenue_publishers = ".$row['total_revenue_publishers'];
echo "<br>total_clicks:".$total_clicks;




            // Cek apakah data sudah ada di tabel rekap total
            $check_query = "
                SELECT id FROM rekap_total_publisher_partner 
                WHERE pub_id = ? 
                AND pubs_providers_domain_url = ?
            ";

              $check_query_p= str_replace("pub_id = ?", "pub_id = '".$pub_id."'", $check_query);
              $check_query_p= str_replace("pubs_providers_domain_url = ?", "pubs_providers_domain_url = '".$pubs_providers_domain_url."'", $check_query_p);

              echo "<br>check_query_p:".$check_query_p;
              
            $stmt_check = $mysqli->prepare($check_query);
            $stmt_check->bind_param("is", $pub_id, $pubs_providers_domain_url);
            $stmt_check->execute();
            $stmt_check->store_result();

            if ($stmt_check->num_rows > 0) {
                // Jika data sudah ada, lakukan update
                $update_query = "
                    UPDATE rekap_total_publisher_partner
                    SET total_revenue_publishers = ?, 
                        total_clicks = ?,
                        site_name = ?, 
                        site_domain = ? ,
                        rekap_date = now()
                    WHERE pub_id = ? 
                    AND pubs_providers_domain_url = ?
                ";
    
     $update_query_p= str_replace("total_revenue_publishers = ?", "total_revenue_publishers = '".$total_revenue_publishers."'", $update_query);

     $update_query_p= str_replace("pub_id = ?", "pub_id = '".$pub_id."'", $update_query_p);

$update_query_p= str_replace("pubs_providers_domain_url = ?", "pubs_providers_domain_url = '".$pubs_providers_domain_url."'", $update_query_p);

        echo "<br>update_query_p:".$update_query_p;


                $stmt_update = $mysqli->prepare($update_query);
                $stmt_update->bind_param("iissis", $total_revenue_publishers, $total_clicks, $site_name, $site_domain, $pub_id, $pubs_providers_domain_url);
                $stmt_update->execute();
                $stmt_update->close();
            } else {
                // Jika data belum ada, lakukan insert
                $insert_query = "
                    INSERT INTO rekap_total_publisher_partner
                    (pub_id, site_name, site_domain, pubs_providers_domain_url, total_revenue_publishers, total_clicks)
                    VALUES (?, ?, ?, ?, ?, ?)
                ";
                   echo "<br>insert_query:".$insert_query;

                $stmt_insert = $mysqli->prepare($insert_query);
                $stmt_insert->bind_param("isssii", $pub_id, $site_name, $site_domain, $pubs_providers_domain_url, $total_revenue_publishers, $total_clicks);
                $stmt_insert->execute();
                $stmt_insert->close();
            }

            $stmt_check->close();
        }

        // Free result set
        $result->free();
    } else {
        echo "Error: " . $mysqli->error;
    }
}



/**
 * Function to calculate total local_revenue_paid from payment_local_pubs
 * and update local_revenue_paid and local_revenue_unpaid in msusers
 * 
 * @param string $email_pubs The email of the user whose payments are calculated
 * @return void
// Example usage
$email_pubs = 'user@example.com'; // Replace with the actual email
updateRevenueForUser($email_pubs);

 */
function updateRevenueForUser($mysqli ,$email_pubs) {
    
    // Step 1: Calculate the total `local_revenue_paid` for the user
    $sql = "SELECT SUM(nominal) AS total_paid FROM payment_local_pubs WHERE email_pubs = ?";
    $stmt = $mysqli->prepare($sql);
    $stmt->bind_param("s", $email_pubs);
    $stmt->execute();
    $result = $stmt->get_result();
    $row = $result->fetch_assoc();
    $total_paid = $row['total_paid'] ?? 0; // If no payments are found, default to 0

    // Step 2: Fetch the user's current revenue from the `msusers` table
    $sql_user = "SELECT id, current_revenue FROM msusers WHERE loginemail = ?";
    $stmt_user = $mysqli->prepare($sql_user);
    $stmt_user->bind_param("s", $email_pubs);
    $stmt_user->execute();
    $result_user = $stmt_user->get_result();
    
    if ($result_user->num_rows > 0) {
        $user_row = $result_user->fetch_assoc();
        $user_id = $user_row['id'];
        $current_revenue = $user_row['current_revenue'];

        // Step 3: Calculate `local_revenue_unpaid` as `current_revenue - local_revenue_paid`
        $local_revenue_unpaid = $current_revenue - $total_paid;

        // Step 4: Update the `local_revenue_paid` and `local_revenue_unpaid` in the `msusers` table
        $update_sql = "
            UPDATE msusers
            SET local_revenue_paid = ?, local_revenue_unpaid = ? , 
            last_updated_revenue= now()
            WHERE id = ?
        ";
        $stmt_update = $mysqli->prepare($update_sql);
        $stmt_update->bind_param("ddi", $total_paid, $local_revenue_unpaid, $user_id);
        if ($stmt_update->execute()) {
            //echo "User's revenue successfully updated.";
        } else {
            //echo "Error updating user's revenue: " . $stmt_update->error;
        }

        // Close update statement
        $stmt_update->close();
    } else {
        //echo "User not found.";
    }

    // Close the statements
    $stmt->close();
    $stmt_user->close();
}



/**
 * Function to calculate total revenue for publishers_site
 */
function calculateTotalRevenue($pdo) {
    try {
        // Update current_site_revenue (when providers_domain_url matches)
        $sql_update_site_revenue = "
            UPDATE publishers_site ps
            SET ps.current_site_revenue = (
                SELECT IFNULL(SUM(ac.revenue_publishers), 0)
                FROM ad_clicks ac
                WHERE ac.pub_id = ps.id
                AND ac.ads_providers_domain_url = ps.providers_domain_url
            )
        ";
        $pdo->exec($sql_update_site_revenue);

        // Update current_site_revenue_from_partner (when providers_domain_url does not match)
        $sql_update_site_revenue_partner = "
            UPDATE publishers_site ps
            SET ps.current_site_revenue_from_partner = (
                SELECT IFNULL(SUM(ac.revenue_publishers), 0)
                FROM ad_clicks ac
                WHERE ac.pub_id = ps.id
                AND ac.ads_providers_domain_url != ps.providers_domain_url
            )
        ";
        $pdo->exec($sql_update_site_revenue_partner);

        echo "Revenue calculations completed successfully.";
    } catch (Exception $e) {
        error_log("Error calculating total revenue: " . $e->getMessage());
        echo "Error calculating total revenue.";
    }
}



// Function to calculate total revenue for publishers based on pub_id and pubs_providers_domain_url
function calculateTotalRevenuePublishers($pub_id, $pubs_providers_domain_url, $pdo) {
    // SQL query to sum the total revenue publishers
    $query = "SELECT SUM(total_revenue_publishers) AS total_revenue 
              FROM rekap_harian_publishers 
              WHERE pub_id = :pub_id AND pubs_providers_domain_url = :pubs_providers_domain_url";

    // Prepare the statement
    $stmt = $pdo->prepare($query);

    // Bind the parameters
    $stmt->bindParam(':pub_id', $pub_id, PDO::PARAM_INT);
    $stmt->bindParam(':pubs_providers_domain_url', $pubs_providers_domain_url, PDO::PARAM_STR);

    // Execute the query
    $stmt->execute();

    // Fetch the result
    $result = $stmt->fetch(PDO::FETCH_ASSOC);

    // Return the total revenue or 0 if no result found
    return $result ? $result['total_revenue'] : 0;
}

?>