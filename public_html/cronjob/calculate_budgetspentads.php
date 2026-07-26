<?php

/*
cronjob/calculate_budgetspentads.php

Kode ini berfungsi untuk menghitung transaksi klik yang terjadi pada server Local 

*/

include("../db.php");
include("../function.php");


// Create a new PDO instance
$pdo = new PDO("mysql:host=$servername_db;dbname=$dbname_db;charset=utf8mb4", $username_db, $password_db);
$pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);



// Database connection using MySQLi
$conn = new mysqli($servername_db, $username_db, $password_db, $dbname_db);
if ($conn->connect_error) {
    error_log("Database connection failed: " . $conn->connect_error);
    exit("Database connection failed.");
}

// Function to calculate budget spent after audit

echo "<h1>Menghitung Biaya Click Iklan yang diklik dari Local Adnetwork</h1>";

$id = 1;
$this_providers_domain_url = get_providers_domain_url($conn, $id);

echo "<br>Local AdNetwork = " . $this_providers_domain_url;
echo "<br>Ambil data dari Table `ad_clicks`";

// Fetch data from ad_clicks_partner where ads_providers_domain_url equals $this_providers_domain_url
$sql = "SELECT DISTINCT local_ads_id, ads_providers_domain_url , landingpage_ads FROM ad_clicks WHERE ads_providers_domain_url = ?";

//echo "<br>sql = " . $sql;
$stmt = $conn->prepare($sql);
if ($stmt === false) {
    die("Error in SQL prepare: " . $conn->error);
}

// Bind the parameter
$stmt->bind_param("s", $this_providers_domain_url);

// Execute the query
$stmt->execute();

// Get the result set
$result = $stmt->get_result();

// Loop through the results and call the calculate_budgetspentads_partner function
while ($row = $result->fetch_assoc()) {
    $current_local_ads_id = $row['local_ads_id'];
    $current_ads_providers_domain_url = $row['ads_providers_domain_url'];
    $landingpage_ads = $row['landingpage_ads'];
    echo "<hr>";
    echo "<br>Local Adnetork = " . $current_local_ads_id;
     echo "<br>Landingpage_ads = " . $landingpage_ads;
    echo "<br>Adnetwork Name = " . $current_ads_providers_domain_url;
    
    // Process the data
    calculate_budgetspentads_partner($conn, $current_local_ads_id, $current_ads_providers_domain_url);

}

// Close the statement and connection
$stmt->close();
$conn->close();



function calculate_budgetspentads_partner($conn, $local_ads_id, $ads_providers_domain_url) {
    
    $total_budget_spent = 0;
    
    echo "<br>";
    echo "<br>calculate_budgetspentads_partner";

    echo "<br>Local ads_providers_domain_url = " . $ads_providers_domain_url;
    echo "<br>local_ads_id = " . $local_ads_id;
   
    // Prepare the SQL statement to calculate the total budget spent
    $sql = "SELECT 
                SUM(revenue_publishers + revenue_adnetwork_local + revenue_adnetwork_partner) AS total_budget_spent, pubs_providers_domain_url 
            FROM 
                ad_clicks
            WHERE 
                local_ads_id = ? 
                AND ads_providers_domain_url = ?
                AND isaudit = 1
                AND is_reject = 0";

    $stmt = $conn->prepare($sql);
    if ($stmt === false) {
        die("Error in SQL prepare: " . $conn->error);
    }

    // Bind parameters to the SQL query
    $stmt->bind_param("is", $local_ads_id, $ads_providers_domain_url);

    // Execute the query
    $stmt->execute();

    // Fetch the result
    $result = $stmt->get_result();
    $row = $result->fetch_assoc();
    $total_budget_spent = $row['total_budget_spent'] ?? 0;
    $pubs_providers_domain_url = $row['pubs_providers_domain_url'];

    echo "<br>total_budget_spent = " . $total_budget_spent;
 echo "<br>pubs_providers_domain_url = " . $pubs_providers_domain_url;
   

    // Close the statement
    $stmt->close();

    // Insert the result into the rekap_click_ads_partner table

        // Update the advertisers_ads table with the total budget spent
    $update_sql = "UPDATE advertisers_ads 
                   SET current_spending = ? 
                   WHERE local_ads_id = ? 
                   AND providers_domain_url = ?";

    $update_stmt = $conn->prepare($update_sql);
    if ($update_stmt === false) {
        die("Error in SQL prepare: " . $conn->error);
    }

echo "<br>update_sql = " . $update_sql;

    // Bind parameters to the update query
    $update_stmt->bind_param("dis", $total_budget_spent, $local_ads_id, $ads_providers_domain_url);

    // Execute the update query
    $update_stmt->execute();

    // Check if current_spending + current_spending_from_partner >= (budget_allocation * 0.5)


    $check_sql = "SELECT 
                    current_spending + current_spending_from_partner AS total_spending,
                    budget_allocation
                  FROM 
                    advertisers_ads 
                  WHERE 
                    local_ads_id = ? 
                    AND providers_domain_url = ?";

    echo "<br>check_sql= " . $check_sql;
    echo "<br>local_ads_id= " . $local_ads_id;
    echo "<br>ads_providers_domain_url= " . $ads_providers_domain_url;

    $check_stmt = $conn->prepare($check_sql);
    if ($check_stmt === false) {
        die("Error in SQL prepare: " . $conn->error);
    }

    $check_stmt->bind_param("is", $local_ads_id, $ads_providers_domain_url);
    $check_stmt->execute();
    $check_result = $check_stmt->get_result();
    $check_row = $check_result->fetch_assoc();

    if ($check_row) {
        $total_spending = $check_row['total_spending'];
        $budget_allocation = $check_row['budget_allocation'];

          $Threshold = $budget_allocation * 0.7;

    echo "<br>total_spending= " . $total_spending;
    echo "<br>budget_allocation= " . $budget_allocation;
     echo "<br>Apakah total_spending = " . $total_spending ." >= 70% nya budget_allocation:  ".$Threshold ;
   
          
        if ($total_spending >= ($Threshold)) {
            // Update is_expired and expired_date (GMT+7)
            $gmt7_time = new DateTime('now', new DateTimeZone('Asia/Jakarta'));
            $expired_date = $gmt7_time->format('Y-m-d H:i:s');
             echo "<br><br><strong><font color=red>Iklan Stop!</strong></font>";

             echo "<br>Set Expired local_ads_id  = " . $local_ads_id;


            $expire_sql = "UPDATE advertisers_ads 
                           SET is_expired = 1, expired_date = ? 
                           WHERE local_ads_id = ? 
                           AND providers_domain_url = ?";

           echo "<br>Set expire_sql: " . $expire_sql;
             echo "<br>Ads_providers_domain_url: " . $ads_providers_domain_url;
   

            $expire_stmt = $conn->prepare($expire_sql);
            if ($expire_stmt === false) {
                die("Error in SQL prepare: " . $conn->error);
            }
           
            $expire_stmt->bind_param("sis", $expired_date, $local_ads_id, $ads_providers_domain_url);
            $expire_stmt->execute();
            $expire_stmt->close();
        }

     

    }


    // Close the check statement
    $check_stmt->close();

    return $total_budget_spent;
}






?>
