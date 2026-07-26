<?php
/*
show_ads_native_portrait.js.php
*/
header('Content-Type: application/javascript');
// Prevent the browser from caching this response — each request must get a
// fresh, unique $carousel_id. If a page embeds this script twice with the
// same query string, a cached response served for both would duplicate the
// same "random" carousel id, breaking the second carousel's prev/next
// buttons (getElementById always resolves to the first matching element).
header('Cache-Control: no-cache, no-store, must-revalidate');
header('Pragma: no-cache');
header('Expires: 0');

$pubId = isset($_GET['pubId']) ? intval($_GET['pubId']) : '';
$pubProvName = isset($_GET['pubProvName']) ? $_GET['pubProvName'] : '';
$maxAds = isset($_GET['maxads']) ? intval($_GET['maxads']) : 1; // Default to 1 if not provided
$column = isset($_GET['column']) ? intval($_GET['column']) : 2; // Default to 2 if not provided

if (!$pubId) {
    die('Publisher ID is missing.');
}

include("db.php");
include("function.php");

$mysqli = new mysqli($servername_db, $username_db, $password_db, $dbname_db);

if ($mysqli->connect_error) {
    die("Koneksi gagal: " . $mysqli->connect_error);
}

// Check if the user's IP address is banned
$ip_address = $_SERVER['REMOTE_ADDR'];
$stmt = $mysqli->prepare("SELECT COUNT(*) FROM list_ip_banned WHERE ip_address = ?");
$stmt->bind_param("s", $ip_address);
$stmt->execute();
$stmt->bind_result($count);
$stmt->fetch();
$stmt->close();

if ($count > 0) {
    // If the IP is banned, do not display any ads
    echo "document.write('<p>Access Denied: Your IP address has been blocked.</p>');";
    $mysqli->close();
    exit;
}

// Database connection using MySQLi
$conn = new mysqli($servername_db, $username_db, $password_db, $dbname_db);
if ($conn->connect_error) {
    error_log("Database connection failed: " . $conn->connect_error);
    exit("Database connection failed.");
}

// Ambil semua providers dari table
$sql = "SELECT id, providers_domain_url, is_hold FROM providers_partners";
$result = $conn->query($sql);

$filter = "";
if ($result->num_rows > 0) {
    // Loop melalui semua hasil
    $filter = "";
    while ($row = $result->fetch_assoc()) {
        $partner_providers_domain_url = $conn->real_escape_string($row['providers_domain_url']);
        $is_hold = $row['is_hold'];
        if ($is_hold==1) {
             $filter .= "AND m.ads_providers_domain_url!= '".$partner_providers_domain_url."' ";
        }

    }
}

$maxAds = ($maxAds > 0 && $maxAds <= 50) ? $maxAds : 10; // Limit the number of ads between 1 and 50
$column = ($column > 0 && $column <= 12) ? $column : 3; // Limit the number of columns between 1 and 12

$id = 1;
$pubs_providers_domain_url = get_providers_domain_url($mysqli, $id);

$sql = "SELECT m.id, m.local_ads_id, 
               m.ads_providers_name,
               m.ads_providers_domain_url,
               m.title_ads, m.description_ads, 
               m.landingpage_ads, m.image_url
        FROM mapping_advertisers_ads_publishers_site m
        LEFT JOIN advertisers_ads a
               ON a.local_ads_id = m.local_ads_id
              AND a.providers_domain_url = m.ads_providers_domain_url
        LEFT JOIN advertisers_ads_partners ap
               ON ap.local_ads_id = m.local_ads_id
              AND ap.providers_domain_url = m.ads_providers_domain_url
        WHERE m.publishers_site_local_id = ?
        AND m.is_published = 1
        AND m.is_expired = 0
        AND m.is_paused = 0
        AND COALESCE(a.is_expired, ap.is_expired, 0) = 0
        AND COALESCE(a.is_paused, ap.is_paused, 0) = 0
        AND m.is_approved_by_publisher = 1
        AND m.is_approved_by_advertiser = 1
        ".$filter."
        ORDER BY m.budget_per_click_textads
        DESC LIMIT ?";
//echo "<br>sql = ".$sql;

$stmt = $mysqli->prepare($sql);
if ($stmt === false) {
    die("Error in SQL prepare: " . $mysqli->error);
}

$stmt->bind_param("ii", $pubId, $maxAds);
$stmt->execute();
$result = $stmt->get_result();

$ads = [];
if ($result->num_rows > 0) {
    $ads = $result->fetch_all(MYSQLI_ASSOC);
} else {
    echo "document.write('<p>No ads found for this publisher.</p>');";
}

$mysqli->close();

echo <<<EOT
document.write(`<style>
.portrait-ads-container {
    display: grid;
    grid-template-columns: repeat($column, 1fr);
    gap: 20px;
    margin: 20px 0;
}

@media (max-width: 768px) {
    .portrait-ads-container {
        grid-template-columns: 1fr; /* Single column for smaller screens */
    }
}

.portrait-ads-item {
    display: flex;
    flex-direction: column; /* Ensure image is above the text */
    align-items: center;
    border: 1px solid #ddd;
    padding: 15px;
    margin: 10px 0;
    background-color: #ffffff;
    box-shadow: 0 4px 8px rgba(0, 0, 0, 0.1);
    border-radius: 8px;
    transition: transform 0.2s ease, box-shadow 0.2s ease;
}

.portrait-ads-item:hover {
    transform: translateY(-5px);
    box-shadow: 0 8px 16px rgba(0, 0, 0, 0.2);
}

.portrait-ads-item img {
    width: 100%;
    height: auto;
    object-fit: cover;
    margin-bottom: 15px; /* Space between image and text */
    border-radius: 8px;
    border: 1px solid #ddd;
}

.portrait-ads-item div {
    flex-grow: 1;
    text-align: center; /* Center text below the image */
}

.portrait-ads-item a {
    color: #333;
    text-decoration: none;
}

.portrait-ads-item a:hover {
    text-decoration: underline;
    color: #007BFF;
}

.portrait-ads-item p {
    margin: 5px 0;
    font-size: 14px;
    color: #666;
}

.portrait-ads-item small {
    font-size: 12px;
    color: #999;
}
</style>`);
EOT;

echo "document.write('<div class=\"portrait-ads-container\">');";

foreach ($ads as $ad) {
    $ad_id = $ad['id'];
    $local_ads_id = $ad['local_ads_id'];
    $description_ads = $ad['description_ads'];
    $limited_description = substr($description_ads, 0, 250);
    $ads_providers_name = $ad['ads_providers_name'];
    $ads_providers_domain_url = $ad['ads_providers_domain_url'];
    $image_url = $ad['image_url'] ?: "http://localhost/adnetwork_baru/adnetA/banner_mini/f.png";
  
    $ip_address = $_SERVER['REMOTE_ADDR'];
    $browser_agent = $_SERVER['HTTP_USER_AGENT'];
    $referrer = $_SERVER['HTTP_REFERER'] ?? '';
    $skey = build_click_skey($ip_address, $ad_id, $pubId, $local_ads_id, $referrer);

    $click_url = $pubs_providers_domain_url .
    "/track_click.php?adId=$ad_id&pubId=$pubId&pubProvName=" . urlencode($pubProvName) . "&localAdsId=$local_ads_id&ads_providers_name=" . urlencode($ads_providers_name) . "&ads_providers_domain_url=" . urlencode($ads_providers_domain_url) . "&ip=" . urlencode($ip_address) . "&agent=" . urlencode($browser_agent) . "&referrer=" . urlencode($referrer)."&skey=".$skey;

    $title_ads_safe = ad_js_escape($ad['title_ads']);
    $limited_description_safe = ad_js_escape($limited_description);
    $ads_providers_name_safe = ad_js_escape($ads_providers_name);
    $ads_providers_domain_url_safe = ad_js_escape($ads_providers_domain_url);
    $image_url_safe = ad_js_escape($image_url);
    $click_url_safe = ad_js_escape($click_url);

    echo <<<EOT
    document.write(`<div class='portrait-ads-item'>
        <a href='$click_url_safe' target='_blank'><img src='$image_url_safe' alt='Ad Image'></a>
        <div>
            <a href='$click_url_safe' target='_blank'><strong>$title_ads_safe</strong></a><br>
            <p>$limited_description_safe</p>
             <small><em>Ads by <a href='$ads_providers_domain_url_safe' target='_blank'>$ads_providers_name_safe</a></em></small>

        </div>
    </div>`);
    EOT;
}


echo "document.write('</div>');";
?>
