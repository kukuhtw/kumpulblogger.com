<?php
/*
cronjob/rekap_total_publisher.php
*/

include("../db.php");
include("../function.php");

// Create a new PDO instance
$pdo = new PDO("mysql:host=$servername_db;dbname=$dbname_db;charset=utf8mb4", $username_db, $password_db);
$pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

/**
 * Function to fetch distinct pub_id from ad_clicks table with specific conditions
 */
$sql = "
    SELECT DISTINCT `pub_id`, `pubs_providers_domain_url`
    FROM ad_clicks 
    WHERE isaudit = 1 
    AND is_reject = 0
    AND click_time >= NOW() - INTERVAL 120000 HOUR
";

$stmt = $pdo->query($sql);
$result = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Output result
if ($result) {
    foreach ($result as $row) {
        $pub_id = $row['pub_id'];
        $pubs_providers_domain_url = $row['pubs_providers_domain_url'];

        echo "<br>pub_id: " . $pub_id . "<br>";
        echo "<br>pubs_providers_domain_url: " . $pubs_providers_domain_url . "<br>";

        // Calculate and update revenues
        $current_site_revenue = calculateTotalRevenueByPubIdAndProvidersDomain($pdo, $pub_id, $pubs_providers_domain_url, true);
        $current_site_revenue_from_partner = calculateTotalRevenueByPubIdAndProvidersDomain($pdo, $pub_id, $pubs_providers_domain_url, false);

        // Update the publishers_site table with the calculated values
        updatePublishersSite($pdo, $pub_id, $current_site_revenue, $current_site_revenue_from_partner);
    }
} else {
    echo "No records found.";
}

/*
 * Function to calculate total revenue based on pub_id and pubs_providers_domain_url
 * If $isSameDomain is true, it calculates for the same domain, otherwise it calculates for different domains.
 */
function calculateTotalRevenueByPubIdAndProvidersDomain($pdo, $pub_id, $pubs_providers_domain_url, $isSameDomain) {
    try {
        $domain_condition = $isSameDomain ? "=" : "!=";
        
        // Calculate revenue for the specified pub_id and pubs_providers_domain_url
        $sql_calculate_revenue = "
            SELECT IFNULL(SUM(revenue_publishers), 0) AS total_revenue
            FROM ad_clicks
            WHERE pub_id = :pub_id
             AND isaudit = 1 
             AND is_reject = 0
            AND pubs_providers_domain_url $domain_condition ads_providers_domain_url
        ";
        
        echo "<br>sql_calculate_revenue: " . $sql_calculate_revenue . "<br>";
        echo "<br>pub_id: " . $pub_id . "<br>";
        echo "<br>pubs_providers_domain_url: " . $pubs_providers_domain_url . "<br>";

        $stmt = $pdo->prepare($sql_calculate_revenue);
        
        // Only bind pubs_providers_domain_url if we are using "=" condition
        $stmt->bindParam(':pub_id', $pub_id, PDO::PARAM_INT);
        
        $stmt->execute();
        
        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if ($result) {
            return $result['total_revenue'];
        } else {
            return 0;
        }

    } catch (PDOException $e) {
        // Log and output error message
        error_log("Error calculating total revenue: " . $e->getMessage());
        echo "SQL Error calculating total revenue: " . $e->getMessage();
        return 0;
    } catch (Exception $e) {
        // Log and output error message for any other exceptions
        error_log("General Error calculating total revenue: " . $e->getMessage());
        echo "General Error calculating total revenue: " . $e->getMessage();
        return 0;
    }
}

/*
 * Function to update the publishers_site table with the calculated revenue
 */
function updatePublishersSite($pdo, $pub_id, $current_site_revenue, $current_site_revenue_from_partner) {
    try {
        // Update publishers_site with the new revenue values
        $sql_update = "
            UPDATE publishers_site
            SET current_site_revenue = :current_site_revenue,
                current_site_revenue_from_partner = :current_site_revenue_from_partner
            WHERE id = :pub_id
        ";
echo "<br>sql_update: " . $sql_update . "<br>";
        $stmt = $pdo->prepare($sql_update);
        $stmt->bindParam(':current_site_revenue', $current_site_revenue, PDO::PARAM_STR);
        $stmt->bindParam(':current_site_revenue_from_partner', $current_site_revenue_from_partner, PDO::PARAM_STR);
        $stmt->bindParam(':pub_id', $pub_id, PDO::PARAM_INT);

        $stmt->execute();
        
        echo "<br>Updated publishers_site for pub_id: $pub_id with revenue $current_site_revenue and partner revenue $current_site_revenue_from_partner.<br>";
    } catch (PDOException $e) {
        // Log and output error message for database-related errors
        error_log("SQL Error updating publishers_site: " . $e->getMessage());
        echo "SQL Error updating publishers_site: " . $e->getMessage();
    } catch (Exception $e) {
        // Log and output error message for any other exceptions
        error_log("General Error updating publishers_site: " . $e->getMessage());
        echo "General Error updating publishers_site: " . $e->getMessage();
    }
}

?>
