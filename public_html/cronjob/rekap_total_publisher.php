<?php
/*
cronjob/rekap_total_publisher.php
*/

include("../db.php");
include("../function.php");

// Create a new PDO instance
$pdo = new PDO("mysql:host=$servername_db;dbname=$dbname_db;charset=utf8mb4", $username_db, $password_db);
$pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Rekap Total Publisher</title>
    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha3/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body>
<div class="container mt-5">
    <h1 class="text-center">Rekap Total Publisher</h1>
    <div class="card mt-4">
        <div class="card-body">
<?php

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
    echo '<div class="alert alert-success">Records found: ' . count($result) . '</div>';
    foreach ($result as $row) {
        $pub_id = $row['pub_id'];
        $pubs_providers_domain_url = $row['pubs_providers_domain_url'];

        echo '<div class="mb-3">';
        echo "<strong>pub_id:</strong> " . htmlspecialchars($pub_id) . "<br>";
        echo "<strong>pubs_providers_domain_url:</strong> " . htmlspecialchars($pubs_providers_domain_url) . "<br>";

        // Calculate and update revenues
        $current_site_revenue = calculateTotalRevenueByPubIdAndProvidersDomain($pdo, $pub_id, $pubs_providers_domain_url, true);
        $current_site_revenue_from_partner = calculateTotalRevenueByPubIdAndProvidersDomain($pdo, $pub_id, $pubs_providers_domain_url, false);

        // Update the publishers_site table with the calculated values
        updatePublishersSite($pdo, $pub_id, $current_site_revenue, $current_site_revenue_from_partner);

        echo "</div>";
    }
} else {
    echo '<div class="alert alert-warning">No records found.</div>';
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

        $stmt = $pdo->prepare($sql_calculate_revenue);
        $stmt->bindParam(':pub_id', $pub_id, PDO::PARAM_INT);
        $stmt->execute();

        $result = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($result) {
            return $result['total_revenue'];
        } else {
            return 0;
        }
    } catch (PDOException $e) {
        error_log("Error calculating total revenue: " . $e->getMessage());
        echo '<div class="alert alert-danger">SQL Error calculating total revenue: ' . htmlspecialchars($e->getMessage()) . '</div>';
        return 0;
    } catch (Exception $e) {
        error_log("General Error calculating total revenue: " . $e->getMessage());
        echo '<div class="alert alert-danger">General Error calculating total revenue: ' . htmlspecialchars($e->getMessage()) . '</div>';
        return 0;
    }
}

/*
 * Function to update the publishers_site table with the calculated revenue
 */
function updatePublishersSite($pdo, $pub_id, $current_site_revenue, $current_site_revenue_from_partner) {
    try {
        $sql_update = "
            UPDATE publishers_site
            SET current_site_revenue = :current_site_revenue,
                current_site_revenue_from_partner = :current_site_revenue_from_partner
            WHERE id = :pub_id
        ";
        $stmt = $pdo->prepare($sql_update);
        $stmt->bindParam(':current_site_revenue', $current_site_revenue, PDO::PARAM_STR);
        $stmt->bindParam(':current_site_revenue_from_partner', $current_site_revenue_from_partner, PDO::PARAM_STR);
        $stmt->bindParam(':pub_id', $pub_id, PDO::PARAM_INT);

        $stmt->execute();

        echo '<div class="alert alert-success">Updated publishers_site for pub_id: ' . htmlspecialchars($pub_id) . ' with revenue ' . htmlspecialchars($current_site_revenue) . ' and partner revenue ' . htmlspecialchars($current_site_revenue_from_partner) . '.</div>';
    } catch (PDOException $e) {
        error_log("SQL Error updating publishers_site: " . $e->getMessage());
        echo '<div class="alert alert-danger">SQL Error updating publishers_site: ' . htmlspecialchars($e->getMessage()) . '</div>';
    } catch (Exception $e) {
        error_log("General Error updating publishers_site: " . $e->getMessage());
        echo '<div class="alert alert-danger">General Error updating publishers_site: ' . htmlspecialchars($e->getMessage()) . '</div>';
    }
}

?>
        </div>
    </div>
</div>
<!-- Bootstrap JS -->
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
