<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Cronjob - Rekap Harian Provider Partner</title>
    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body>

<div class="container mt-5">
    <h2 class="text-center">Cronjob: Rekap Harian Provider Partner</h2>
    <p>This cron job script processes the daily recap of partner providers based on clicks in the last 3 days. The following steps are performed:</p>

    <!-- Step 1: Database Connection -->
    <div class="card mt-3">
        <div class="card-header bg-primary text-white">Step 1: Database Connection</div>
        <div class="card-body">
            <p>The script starts by establishing a connection to the MySQL database using both PDO and MySQLi.</p>
            <ul>
                <li><strong>PDO:</strong> Used for secure database operations and interactions.</li>
                <li><strong>MySQLi:</strong> Used in conjunction with other PHP functions to execute queries.</li>
            </ul>
            <pre><code><?php
/*
cronjob/rekap_harian_provider_partner.php
*/

include("../db.php");
include("../function.php");

// Create a new PDO instance
$pdo = new PDO("mysql:host=$servername_db;dbname=$dbname_db;charset=utf8mb4", $username_db, $password_db);
$pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

// Database connection using MySQLi
$mysqli = new mysqli($servername_db, $username_db, $password_db, $dbname_db);
if ($mysqli->connect_error) {
    error_log("Database connection failed: " . $mysqli->connect_error);
    exit("Database connection failed.");
}
?></code></pre>
        </div>
    </div>

    <!-- Step 2: Function to Process Daily Recap -->
    <div class="card mt-3">
        <div class="card-header bg-primary text-white">Step 2: Recap Function</div>
        <div class="card-body">
            <p>The function <code>rekapHarianProviderPartner</code> calculates the daily recap of partner providers within the past 3 days. The steps are:</p>
            <ul>
                <li><strong>Date Calculation:</strong> The function calculates the current date and the date 3 days ago.</li>
                <li><strong>SQL Query:</strong> It runs a query that selects the total number of clicks and total revenue from the partner provider for each day, where the clicks have been audited (<code>isaudit = 1</code>) and not rejected (<code>is_reject = 0</code>).</li>
                <li><strong>Data Insertion/Update:</strong> For each record, the function inserts or updates the <code>rekap_harian_provider_partner</code> table with the total clicks and total revenue for that day and partner.</li>
            </ul>
            <pre><code><?php
rekapHarianProviderPartner($mysqli,$pdo);

// Function to calculate daily recap for partner providers within the past 3 days
function rekapHarianProviderPartner($mysqli,$pdo) {
    // Get the current date
    $current_date = date('Y-m-d');
    
    // Calculate the date from 3 days ago
    $three_days_ago = date('Y-m-d', strtotime('-300 days'));

    // Prepare the SQL query to calculate the daily recap within the past 3 days
    $sql = "
        SELECT 
            DATE(click_time) as rekap_date,
            ads_providers_domain_url,
            COUNT(*) as total_clicks,
            SUM(revenue_adnetwork_partner) as total_revenue_partner
        FROM ad_clicks
        WHERE isaudit = 1 
        AND is_reject = 0
        AND revenue_adnetwork_partner >=1 
        AND click_time BETWEEN ? AND ?
        GROUP BY rekap_date, ads_providers_domain_url
    ";

    if ($stmt = $mysqli->prepare($sql)) {
        // Bind parameters for date range
        $stmt->bind_param('ss', $three_days_ago, $current_date);
        $stmt->execute();
        $result = $stmt->get_result();

        // Get the current timestamp for `created_at`
        $current_timestamp = date('Y-m-d H:i:s');

        // Loop through the result and insert/update the recap data
        while ($row = $result->fetch_assoc()) {
            $rekap_date = $row['rekap_date'];
            $ads_providers_domain_url = $row['ads_providers_domain_url'];
            $total_clicks = $row['total_clicks'];
            $total_revenue_partner = $row['total_revenue_partner'];

            // Insert or update the rekap_harian_provider_partner table with the updated `created_at`
            $insert_sql = "
                INSERT INTO rekap_harian_provider_partner (rekap_date, ads_providers_domain_url, total_clicks, total_revenue_partner, created_at)
                VALUES (?, ?, ?, ?, ?)
                ON DUPLICATE KEY UPDATE 
                    total_clicks = VALUES(total_clicks), 
                    total_revenue_partner = VALUES(total_revenue_partner), 
                    created_at = VALUES(created_at)
            ";

            if ($insert_stmt = $mysqli->prepare($insert_sql)) {
                $insert_stmt->bind_param('ssiss', $rekap_date, $ads_providers_domain_url, $total_clicks, $total_revenue_partner, $current_timestamp);
                $insert_stmt->execute();
                $insert_stmt->close();
            }

             echo "<p>Data ".$ads_providers_domain_url." sedang diproses.</p>";

            //  updatePartnerRevenueByDomain($mysqli, $ads_providers_domain_url) ;          

        }
       
        $stmt->close();
    }
  
      
}
updateProviderRevenue($pdo);
// Close the connection by setting PDO object to null
$pdo = null;

?></code></pre>
        </div>
    </div>

    <!-- Step 3: Closing the Connection -->
    <div class="card mt-3">
        <div class="card-header bg-primary text-white">Step 3: Closing the Connection</div>
        <div class="card-body">
            <p>Finally, the database connection is closed after the recap process is complete.</p>
            <pre><code><?php
$mysqli->close();
?></code></pre>
        </div>
    </div>
</div>

<!-- Bootstrap JS -->
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
