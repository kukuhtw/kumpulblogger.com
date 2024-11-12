<?php
// {BASE_END_POINT}API/sync_mapping_advertisers_ads_publishers_site_from_partners/index.php
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


debug_text("t19.txt",json_encode($json)) ;


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

// Extract ad data from the JSON input
$ad_data = $data['ad_data'] ?? [];


debug_text("t63.txt",json_encode($ad_data)) ;


// Prepare SQL statement to insert/update data
// Prepare SQL statement to insert/update data
$sql = "
INSERT INTO mapping_advertisers_ads_publishers_site_from_partners 
    (   local_mapping_id , 
        rate_text_ads, 
        budget_per_click_textads,
         local_ads_id,
           publishers_site_local_id,
           owner_advertisers_id, 
           title_ads, 
        description_ads, 
        landingpage_ads, 
        publishers_local_id, 
        
        site_name, 
        site_domain, 
        site_desc, 
        image_url, 
        is_published, 
        is_paused, 
        is_expired, 
        is_approved_by_publisher, 
        is_approved_by_advertiser, 
        published_date, 
        
        paused_date, 
        expired_date, 
        approval_date_publisher, 
        approval_date_advertiser, 
        pubs_providers_name, 
        pubs_providers_domain_url, 
        ads_providers_name, 
        ads_providers_domain_url, 
        reasons_rejected_by_advertiser, reasons_rejected_by_publisher, 
        
        revenue_publishers
    ) 
    VALUES (
        ?, ?, ?, 
        ?, ?, ?, 
        ?, ?, ?, 
        ?, ?, ?, 
        ?, ?, ?, 
        ?, ?, ?, 
        ?, ?, ?, 
              ?, ?, ?,
              ?, ?, ?,
                    ?, ?, ?,
                          ?       
    )
    ON DUPLICATE KEY UPDATE 
    rate_text_ads = VALUES(rate_text_ads), 
    budget_per_click_textads = VALUES(budget_per_click_textads), 
    local_ads_id = VALUES(local_ads_id), 
    publishers_site_local_id = VALUES(publishers_site_local_id), 
    owner_advertisers_id = VALUES(owner_advertisers_id), 
    title_ads = VALUES(title_ads), 
    description_ads = VALUES(description_ads), 
    landingpage_ads = VALUES(landingpage_ads), 
    publishers_local_id = VALUES(publishers_local_id), 
    site_name = VALUES(site_name), 
    site_domain = VALUES(site_domain), 
    site_desc = VALUES(site_desc), 
    image_url = VALUES(image_url), 
    is_published = VALUES(is_published), 
    is_paused = VALUES(is_paused), 
    is_expired = VALUES(is_expired), 
    is_approved_by_publisher = VALUES(is_approved_by_publisher), 
    is_approved_by_advertiser = VALUES(is_approved_by_advertiser), 
    published_date = VALUES(published_date), 
    paused_date = VALUES(paused_date), 
    expired_date = VALUES(expired_date), 
    approval_date_publisher = VALUES(approval_date_publisher), 
    approval_date_advertiser = VALUES(approval_date_advertiser), 
    pubs_providers_name = VALUES(pubs_providers_name), 
    pubs_providers_domain_url = VALUES(pubs_providers_domain_url), 
    ads_providers_name = VALUES(ads_providers_name), 
    ads_providers_domain_url = VALUES(ads_providers_domain_url), 
    reasons_rejected_by_advertiser = VALUES(reasons_rejected_by_advertiser), 
    reasons_rejected_by_publisher = VALUES(reasons_rejected_by_publisher), 
    revenue_publishers = VALUES(revenue_publishers)
";

// Debugging SQL
debug_text("t1.txt", $sql);

$stmt = $pdo->prepare($sql);

// Process each ad data
foreach ($ad_data as $ad) {
    // Check if hash_audit already exists
    $stmt_check = $pdo->prepare("SELECT COUNT(*) FROM mapping_advertisers_ads_publishers_site_from_partners WHERE id = ?");
    $stmt_check->execute([$ad['id']]);
    $exists = $stmt_check->fetchColumn();

    debug_text("t2.txt", "exists :" . $exists);

    if (true) { // Condition for inserting/updating
        try {
            // Insert or update data in mapping_advertisers_ads_publishers_site_from_partners
            $stmt->execute([
                $ad['id'],
                $ad['rate_text_ads'], 
                $ad['budget_per_click_textads'], 
                $ad['local_ads_id'], 
                $ad['publishers_site_local_id'], 
                $ad['owner_advertisers_id'], 
                $ad['title_ads'], 
                $ad['description_ads'], 
                $ad['landingpage_ads'], 
                $ad['publishers_local_id'], 
                $ad['site_name'], 
                $ad['site_domain'], 
                $ad['site_desc'], 
                $ad['image_url'], 
                $ad['is_published'], 
                $ad['is_paused'], 
                $ad['is_expired'], 
                $ad['is_approved_by_publisher'], 
                $ad['is_approved_by_advertiser'], 
                $ad['published_date'], 
                $ad['paused_date'], 
                $ad['expired_date'], 
                $ad['approval_date_publisher'], 
                $ad['approval_date_advertiser'], 
                $ad['pubs_providers_name'], 
                $ad['pubs_providers_domain_url'], 
                $ad['ads_providers_name'], 
                $ad['ads_providers_domain_url'], 
                $ad['reasons_rejected_by_advertiser'], 
                $ad['reasons_rejected_by_publisher'], 
                $ad['revenue_publishers']
            ]);

        } catch (PDOException $e) {
            error_log("Failed to execute statement: " . $e->getMessage());
            continue; // Skip this ad if insertion fails
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
