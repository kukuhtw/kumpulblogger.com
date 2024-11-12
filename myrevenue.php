<?php

// myrevenue.php 

// Include database connection
include("db.php");
include("function.php");
include("admin/function_admin.php");
session_start();

// Check if the user is logged in
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

// Get user ID from session
$user_id = $_SESSION['user_id'];
$email =  $_SESSION['email'];
// Create a connection to the MySQL database
$mysqli = new mysqli($servername_db, $username_db, $password_db, $dbname_db);

// Check the connection
if ($mysqli->connect_error) {
    die("Connection failed: " . $mysqli->connect_error);
}

try {
    $pdo = new PDO("mysql:host=$servername_db;dbname=$dbname_db;charset=utf8mb4", $username_db, $password_db);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch (PDOException $e) {
    error_log("Database connection failed: " . $e->getMessage());
    exit("Database connection failed.");
}

// Get user ID and provider domain URL
$this_providers_id = 1;
//$this_providers_domain_url = get_providers_domain_url($mysqli, $this_providers_id);


$this_providers_domain_url = get_providers_domain_url_json("providers_data.json", 1);


// Initialize variables for grand total
$grand_total_site_revenue = 0;
$grand_total_site_revenue_from_partner = 0;

 $countTotalAds = countTotalAds($mysqli, $user_id);
$updateLocalSpending = updateLocalSpending($mysqli, $user_id);
$updateGlobalSpending = updateGlobalSpending($mysqli, $user_id);
$countTotalWebsites = countTotalWebsites($mysqli, $user_id);
$updateLocalRevenue = updateLocalRevenue($mysqli, $user_id);

$updateGlobalRevenue = updateGlobalRevenue($mysqli, $user_id);

updatePartnerRevenue($pdo, $user_id, $email);


?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Revenue Information</title>
    <link rel="stylesheet" href="https://maxcdn.bootstrapcdn.com/bootstrap/4.5.2/css/bootstrap.min.css">
</head>
<body>

<div class="container mt-5">

    <?php include("main_menu.php"); ?>
    <?php include("include_publisher_menu.php"); ?>
    <h2 class="mb-4">Publisher Site Revenue</h2>

    <table class="table table-bordered">
        <thead class="thead-dark">
            <tr>
                <th>Site Name</th>
                <th>Site Domain</th>
                <th>Current Site Revenue</th>
                <th>Current Site Revenue From Partner</th>
            </tr>
        </thead>
        <tbody>
            <?php
            // Prepare SQL query to fetch data from `publishers_site` table
            $sql = "SELECT site_name, site_domain, current_site_revenue, current_site_revenue_from_partner 
                    FROM publishers_site 
                    WHERE providers_domain_url = ?
                    AND publishers_local_id = ?
                    ";

            $stmt = $mysqli->prepare($sql);
            $stmt->bind_param("ss", $this_providers_domain_url ,$user_id  );
            $stmt->execute();
            $result = $stmt->get_result();

            if ($result->num_rows > 0) {
                while ($row = $result->fetch_assoc()) {
                    // Add to grand totals
                    $grand_total_site_revenue += $row['current_site_revenue'];
                    $grand_total_site_revenue_from_partner += $row['current_site_revenue_from_partner'];
                    
                    echo "<tr>";
                    echo "<td>" . htmlspecialchars($row['site_name']) . "</td>";
                    echo "<td>" . htmlspecialchars($row['site_domain']) . "</td>";
                    echo "<td>" . number_format($row['current_site_revenue'], 2) . "</td>";
                    echo "<td>" . number_format($row['current_site_revenue_from_partner'], 2) . "</td>";
                    echo "</tr>";
                }
            } else {
                echo "<tr><td colspan='4'>No revenue data available for publisher sites.</td></tr>";
            }
            ?>
        </tbody>
        <tfoot class="font-weight-bold">
            <tr>
                <td colspan="2" class="text-right">Grand Total:</td>
                <td><?php echo number_format($grand_total_site_revenue, 2); ?></td>
                <td><?php echo number_format($grand_total_site_revenue_from_partner, 2); ?></td>
            </tr>
        </tfoot>
    </table>


    <h2 class="mt-5 mb-4">MyRevenue Information</h2>

<table class="table table-bordered">
        <thead class="thead-dark">
            <tr>
                <th>Current Revenue Local</th>
                <th>Current Revenue From Partner</th>
                <th>Total Current Revenue (Local+Partner)</th>
            </tr>
        </thead>
        <tbody>
            <?php
            // Prepare SQL query to fetch data from `msusers` table for the logged-in user
            $sql_msusers = "SELECT current_revenue, current_revenue_from_partner, total_current_revenue 
                            FROM msusers 
                            WHERE id = ?";

            $stmt_msusers = $mysqli->prepare($sql_msusers);
            $stmt_msusers->bind_param("i", $user_id);
            $stmt_msusers->execute();
            $result_msusers = $stmt_msusers->get_result();

            if ($result_msusers->num_rows > 0) {
                while ($row_msusers = $result_msusers->fetch_assoc()) {


                    $current_revenue = $row_msusers['current_revenue'] ;

                    $current_revenue_from_partner = $row_msusers['current_revenue_from_partner'] ;


                    $paramlocal = "?pubs_providers_domain_url=".$this_providers_domain_url."&local=1";
                    $local = "clicks_publisher_ads_partner_detail.php".$paramlocal;
                    $llocal = "<a href='".$local."'>".number_format($current_revenue,2)."</a>";


                    $paramglobal = "?pubs_providers_domain_url=".$this_providers_domain_url."&local=0";
                    $global = "clicks_publisher_ads_partner_detail.php".$paramglobal;
                    
                    $lglobal = "<a href='".$global."'>".number_format($current_revenue_from_partner,2)."</a>";

                    echo "<tr>";
                    
                     echo "<td>Rp " . $llocal . "</td>";
                    

                    echo "<td>Rp " . $lglobal . "</td>";

                    echo "<td>Rp " . number_format($row_msusers['total_current_revenue'], 2) . "</td>";
                    echo "</tr>";
                }
            } else {
                echo "<tr><td colspan='3'>No revenue data available for this user.</td></tr>";
            }

           
            ?>
        </tbody>
    </table>




    <h2 class="mt-5 mb-4">MyPaid Revenue Information</h2>
    
    <table class="table table-bordered">
        <thead class="thead-dark">
            <tr>
                <th>Paid Revenue Local</th>
                <th>Paid Revenue From Partner</th>
                <th>Total Paid Revenue (Local+Partner)</th>
            </tr>
        </thead>
        <tbody>
            <?php
            // Prepare SQL query to fetch data from `msusers` table for the logged-in user
            $sql_msusers = "SELECT local_revenue_paid , partner_revenue_paid 
                            FROM msusers 
                            WHERE id = ?";

            $stmt_msusers = $mysqli->prepare($sql_msusers);
            $stmt_msusers->bind_param("i", $user_id);
            $stmt_msusers->execute();
            $result_msusers = $stmt_msusers->get_result();

            if ($result_msusers->num_rows > 0) {
                while ($row_msusers = $result_msusers->fetch_assoc()) {


                    $partner_revenue_paid = $row_msusers['partner_revenue_paid'] ;

                    $local_revenue_paid = $row_msusers['local_revenue_paid'] ;


                    $total_paid =$partner_revenue_paid + $local_revenue_paid;


                    
                    echo "<tr>";
                    
                     echo "<td>Rp " . $local_revenue_paid . "</td>";
                    

                    echo "<td>Rp " . $partner_revenue_paid . "</td>";

                    echo "<td>Rp " . number_format($total_paid, 2) . "</td>";

                    echo "</tr>";
                }
            } else {
                echo "<tr><td colspan='3'>No revenue data available for this user.</td></tr>";
            }

            
            // Close the statements and connection
            $stmt->close();
            $stmt_msusers->close();
            $mysqli->close();
            ?>
        </tbody>
    </table>





    <h2 class="mt-5 mb-4">MyUnPaid Revenue Information</h2>
    
    <table class="table table-bordered">
        <thead class="thead-dark">
            <tr>
                <th>UnPaid Revenue Local</th>
                <th>UnPaid Revenue From Partner</th>
                <th>Total UnPaid Revenue (Local+Partner)</th>
            </tr>
        </thead>
        <tbody>
            <?php

            $unpaid_local_revenue = $current_revenue -  $local_revenue_paid;

            $unpaid_partnr_revenue = $current_revenue_from_partner - $partner_revenue_paid;

            $total_unpaid =$unpaid_local_revenue + $unpaid_partnr_revenue;


                    
                    echo "<tr>";
                    
                     echo "<td>Rp " . number_format($unpaid_local_revenue,2) . "</td>";
                    

                    echo "<td>Rp " . number_format($unpaid_partnr_revenue,2) . "</td>";

                    echo "<td>Rp " . number_format($total_unpaid, 2) . "</td>";

                    echo "</tr>";
            
            ?>
        </tbody>
    </table>

    
<script src="https://code.jquery.com/jquery-3.5.1.slim.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@4.5.2/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
