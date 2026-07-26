<?php
// {BASE_END_POINT}API/sync_clicks/index.php

include("../../db.php");
include("../../function.php");

ini_set("error_log", "errr_.txt");

// Database connection using MySQLi
$conn = new mysqli($servername_db, $username_db, $password_db, $dbname_db);
if ($conn->connect_error) {
    error_log("Database connection failed: " . $conn->connect_error);
    exit("Database connection failed.");
}

// Retrieve and decode JSON input
$json = file_get_contents('php://input');
$data = json_decode($json, true);

$providers_domain_url = $data['providers_domain_url'] ?? null;

$id = 1;
//$this_providers_domain_url = get_providers_domain_url($conn, $id);
$this_providers_domain_url = get_providers_domain_url_json("../providers_data.json", 1);


// Database connection using PDO for secure database interaction
try {
    $pdo = new PDO("mysql:host=$servername_db;dbname=$dbname_db;charset=utf8mb4", $username_db, $password_db);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch (PDOException $e) {
    error_log("Database connection failed: " . $e->getMessage());
    exit("Database connection failed.");
}

// Extract headers
$headers = getallheaders();
$Header_public_key = $headers['public_key'] ?? null;
$Header_secret_key = $headers['secret_key'] ?? null;

// Check if the required headers are present
if (!$Header_public_key || !$Header_secret_key) {
    $response = array(
        'status' => 'error',
        'message' => 'Missing required headers.'
    );
    header('Content-Type: application/json');
    echo json_encode($response);
    exit();
}

// Validate provider credentials
if (!checkProviderCredentials($providers_domain_url, $Header_public_key, $Header_secret_key, $pdo)) {
    $response = array(
        'status' => 'error',
        'message' => 'Invalid public or secret key'
    );
    header('Content-Type: application/json');
    echo json_encode($response);
    exit();
}

// Extract ad click data
$ad_click_data = $data['ad_clicks'] ?? [];

// Debugging SQL
//debug_text("t65.txt", json_encode($ad_click_data));
// Prepare SQL statement to insert data
$sql = "
INSERT INTO ad_clicks_partner 
    (
        local_click_id, local_ads_id, rate_text_ads, budget_per_click_textads, 
        ad_id, pub_id, pub_provider, user_cookies, 
        ip_address, browser_agent, referrer, 
        landingpage_ads, click_time, time_epoch_click, 
        isaudit, audit_date, is_reject, 
        reason_rejection, ads_providers_name, ads_providers_domain_url, 
        pubs_providers_name, pubs_providers_domain_url, 
        revenue_publishers, revenue_adnetwork_local, revenue_adnetwork_partner, 
        hash_click, hash_audit , title_ads , site_name , site_domain 
    ) 
    VALUES (?,
        ?, ?, ?, 
        ?, ?, ?, ?, 
        ?, ?, ?, 
        ?, ?, ?, 
        ?, ?, ?, 
        ?, ?, ?, 
        ?, ?, 
        ?, ?, ?, 
        ?, ? , ?, ?, ?
    )
    ON DUPLICATE KEY UPDATE 
    local_ads_id = VALUES(local_ads_id), 
    rate_text_ads = VALUES(rate_text_ads), 
    budget_per_click_textads = VALUES(budget_per_click_textads), 
    ad_id = VALUES(ad_id), 
    pub_id = VALUES(pub_id), pub_provider = VALUES(pub_provider), user_cookies = VALUES(user_cookies), 
    ip_address = VALUES(ip_address), browser_agent = VALUES(browser_agent), referrer = VALUES(referrer), 
    landingpage_ads = VALUES(landingpage_ads), click_time = VALUES(click_time), 
    time_epoch_click = VALUES(time_epoch_click), isaudit = VALUES(isaudit), 
    audit_date = VALUES(audit_date), is_reject = VALUES(is_reject), 
    reason_rejection = VALUES(reason_rejection), ads_providers_name = VALUES(ads_providers_name), 
    ads_providers_domain_url = VALUES(ads_providers_domain_url), 
    pubs_providers_name = VALUES(pubs_providers_name), pubs_providers_domain_url = VALUES(pubs_providers_domain_url), 
    revenue_publishers = VALUES(revenue_publishers), revenue_adnetwork_local = VALUES(revenue_adnetwork_local), 
    revenue_adnetwork_partner = VALUES(revenue_adnetwork_partner), hash_click = VALUES(hash_click), 
    hash_audit = VALUES(hash_audit)
";


$stmt = $pdo->prepare($sql);

// Process each click data
foreach ($ad_click_data as $click) {
    // Only process if the domain URLs do not match
    if ($click['pubs_providers_domain_url'] !== $this_providers_domain_url) {
        // Check if hash_audit already exists
        $stmt_check = $pdo->prepare("SELECT COUNT(*) FROM ad_clicks_partner WHERE hash_audit = ?");
        $stmt_check->execute([$click['hash_audit']]);
        $exists = $stmt_check->fetchColumn();

        debug_text("t121.txt", $exists);
        if ($exists == 0) {
            try {
                // Insert data into ad_clicks_partner
$stmt->execute([
    $click['id'],
    $click['local_ads_id'], 
    $click['rate_text_ads'], 
    $click['budget_per_click_textads'], 
    $click['ad_id'], 
    $click['pub_id'], 
    $click['pub_provider'], 
    $click['user_cookies'], 
    $click['ip_address'], 
    $click['browser_agent'], 
    $click['referrer'], 
    $click['landingpage_ads'], 
    $click['click_time'], 
    $click['time_epoch_click'], 
    $click['isaudit'], 
    $click['audit_date'], 
    $click['is_reject'], 
    $click['reason_rejection'], 
    $click['ads_providers_name'], 
    $click['ads_providers_domain_url'], 
    $click['pubs_providers_name'], 
    $click['pubs_providers_domain_url'], 
    $click['revenue_publishers'], 
    $click['revenue_adnetwork_local'], 
    $click['revenue_adnetwork_partner'], 
    $click['hash_click'], 
    $click['hash_audit'], 
    $click['title_ads'], 
    $click['site_name'], 
    $click['site_domain']
]);

            } catch (PDOException $e) {

                 debug_text("t161.txt", $e->getMessage());

                error_log("Failed to execute statement: " . $e->getMessage());
                continue; // Skip this click if insertion fails
            }
        }
    }
}

// Response
$response = array(
    'status' => 'success',
    'message' => 'Data processed successfully.'
);

header('Content-Type: application/json');
echo json_encode($response);

?>

