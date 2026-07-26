<?php
// process_clicks_report_csv.php

// process_clicks_report

include("db.php");
session_start();

// Database connection using MySQLi
$conn = new mysqli($servername_db, $username_db, $password_db, $dbname_db);
if ($conn->connect_error) {
    die("Koneksi gagal: " . $conn->connect_error);
}

// Get local_ads_id and ads_providers_domain_url from POST request
if (!isset($_POST['local_ads_id']) || !isset($_POST['ads_providers_domain_url'])) {
    die("local_ads_id dan ads_providers_domain_url harus disertakan.");
}

$local_ads_id = $_POST['local_ads_id'];
$ads_providers_domain_url = $_POST['ads_providers_domain_url'];

// Fetch data from advertisers_ads based on local_ads_id and ads_providers_domain_url
$ads_sql = "
    SELECT title_ads, landingpage_ads
    FROM advertisers_ads
    WHERE local_ads_id = ? AND providers_domain_url = ?";
$ads_stmt = $conn->prepare($ads_sql);
$ads_stmt->bind_param("is", $local_ads_id, $ads_providers_domain_url);
$ads_stmt->execute();
$ads_stmt->bind_result($title_ads, $landingpage_ads);
$ads_stmt->fetch();
$ads_stmt->close();

// Check and fetch data from ad_clicks with isaudit = 1 and is_reject = 0
$clicks_sql = "
    SELECT 
        rate_text_ads, 
        budget_per_click_textads, 
        user_cookies, 
        ip_address, 
        browser_agent, 
        referrer, 
        landingpage_ads, 
        click_time, 
        audit_date, 
        revenue_publishers, 
        revenue_adnetwork_local, 
        revenue_adnetwork_partner, 
        hash_audit , 
        pubs_providers_domain_url   
    FROM ad_clicks_partner
    WHERE local_ads_id = ? AND ads_providers_domain_url = ? AND isaudit = 1 AND is_reject = 0
    ORDER BY click_time DESC";
    
$clicks_stmt = $conn->prepare($clicks_sql);
$clicks_stmt->bind_param("is", $local_ads_id, $ads_providers_domain_url);
$clicks_stmt->execute();
$clicks_result = $clicks_stmt->get_result();

// Set CSV headers for file download
header('Content-Type: text/csv');
header('Content-Disposition: attachment;filename="clicks_report.csv"');

// Create a file pointer to the output stream (used to write CSV)
$output = fopen('php://output', 'w');

// Add the CSV column headers
fputcsv($output, array(
    'Title Ads', 
    'Landing Page Ads', 
    'Rate Text Ads', 
    'Budget Per Click Text Ads', 
    'User Cookies', 
    'IP Address', 
    'Browser Agent', 
    'Referrer', 
    'Landing Page Clicked', 
    'Click Time', 
    'Audit Date', 
    'Budget Spent', 
    'Hash Audit', 
    'Network Domain'
));

// Loop through the results and add them to the CSV
while ($row = $clicks_result->fetch_assoc()) {
    $budget_spent = $row['revenue_publishers'] + $row['revenue_adnetwork_local'] + $row['revenue_adnetwork_partner'];

    // Add each row to the CSV
    fputcsv($output, array(
        $title_ads,
        $landingpage_ads,
        $row['rate_text_ads'],
        $row['budget_per_click_textads'],
        $row['user_cookies'],
        $row['ip_address'],
        $row['browser_agent'],
        $row['referrer'],
        $row['landingpage_ads'],
        $row['click_time'],
        $row['audit_date'],
        $budget_spent,
        $row['hash_audit'],
        "~".$row['pubs_providers_domain_url']."~"
    ));
}

// Close the statement and connection
$clicks_stmt->close();
$conn->close();

// Close the output stream (end of CSV)
fclose($output);
exit();
?>
