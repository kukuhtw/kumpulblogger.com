<?php
/*
show_ads_native_landscape.js.php
*/
error_reporting(E_ALL);
ini_set('display_errors', 1);

header('Content-Type: application/javascript');

$pubId = isset($_GET['pubId']) ? intval($_GET['pubId']) : '';
$pubProvName = isset($_GET['pubProvName']) ? $_GET['pubProvName'] : '';
$maxAds = isset($_GET['maxads']) ? intval($_GET['maxads']) : 1;
$column = isset($_GET['column']) ? intval($_GET['column']) : 2;

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
    echo "document.write('<p>Access Denied: Your IP address has been blocked.</p>');";
    $mysqli->close();
    exit;
}

// Fetch all providers from the table
$sql = "SELECT id, providers_domain_url, is_hold FROM providers_partners";
$result = $mysqli->query($sql);

$filter = "";
if ($result->num_rows > 0) {
    while ($row = $result->fetch_assoc()) {
        if ($row['is_hold'] == 1) {
            $filter .= "AND ads_providers_domain_url!= '" . $mysqli->real_escape_string($row['providers_domain_url']) . "' ";
        }
    }
}

$maxAds = ($maxAds > 0 && $maxAds <= 50) ? $maxAds : 10;
$column = ($column > 0 && $column <= 12) ? $column : 3;

$pubs_providers_domain_url = get_providers_domain_url_json("providers_data.json", 1);

$stmt_alt = $mysqli->prepare("SELECT alternate_code FROM publishers_site WHERE id = ?");
$stmt_alt->bind_param("i", $pubId);
$stmt_alt->execute();
$result_alt = $stmt_alt->get_result();
$alternate_code = $result_alt->fetch_assoc()['alternate_code'];
$stmt_alt->close();

$probability = 55;
$randomNumber = rand(1, 100);
$filter_order = ($randomNumber <= $probability) ? "RAND()" : "budget_per_click_textads DESC";

$sql = "SELECT id, local_ads_id, 
               ads_providers_name, ads_providers_domain_url,
               title_ads, description_ads, landingpage_ads, image_url
        FROM mapping_advertisers_ads_publishers_site
        WHERE publishers_site_local_id = ? 
        AND is_published = 1
        AND is_expired = 0 
        AND is_approved_by_publisher = 1 
        AND is_approved_by_advertiser = 1
        $filter 
        ORDER BY $filter_order
        LIMIT ?";

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
    if (!empty($alternate_code)) {
        echo "document.write(`$alternate_code`);";
    } else {
        echo <<<EOT
        document.write("<p>No ads available from this publisher. Displaying fallback ads from another ad network.</p>");
        EOT;
    }
}

$mysqli->close();

echo <<<EOT
document.write(`<style>
.landscape-ads-container {
    display: grid;
    grid-template-columns: repeat($column, 1fr);
    gap: 20px;
    margin: 20px 0;
}
.landscape-ads-item {
    display: flex;
    align-items: center;
    border: 1px solid #ddd;
    padding: 15px;
    margin: 10px 0;
    background-color: #ffffff;
    box-shadow: 0 4px 8px rgba(0, 0, 0, 0.1);
    border-radius: 8px;
    transition: transform 0.2s ease, box-shadow 0.2s ease;
    text-align: left;
}
.landscape-ads-item:hover {
    transform: translateY(-5px);
    box-shadow: 0 8px 16px rgba(0, 0, 0, 0.2);
}
.landscape-ads-item div {
    flex-basis: 75%;
    overflow: hidden;
    padding-right: 15px;
}
.landscape-ads-item img {
    width: 240px;
    height: auto;
    object-fit: cover;
    margin-right: 15px;
    border-radius: 8px;
    border: 1px solid #ddd;
    float: left;
    margin-bottom: 10px;
}
.landscape-ads-item a {
    color: #333;
    text-decoration: none;
    font-weight: bold;
}
.landscape-ads-item a:hover {
    text-decoration: underline;
    color: #007BFF;
}
.landscape-ads-item p {
    margin: 5px 0;
    font-size: 14px;
    color: #666;
}
.landscape-ads-item small {
    font-size: 12px;
    color: #999;
}
.landscape-clearfix::after {
    content: "";
    display: table;
    clear: both;
}
</style>`);
EOT;

echo "document.write('<div class=\"landscape-ads-container\">');";

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
    $skey = md5($ip_address.$ad_id.$pubId.$local_ads_id.urlencode($referrer));

    $click_url = $pubs_providers_domain_url . 
    "/track_click.php?adId=$ad_id&pubId=$pubId&pubProvName=$pubProvName&localAdsId=$local_ads_id&ads_providers_name=" . urlencode($ads_providers_name) . "&ads_providers_domain_url=$ads_providers_domain_url&ip=$ip_address&agent=" . urlencode($browser_agent) . "&referrer=" . urlencode($referrer)."&skey=".$skey;

    echo <<<EOT
    document.write(`<div class='landscape-ads-item'>
        <div>
            <a href='$click_url' target='_blank'><strong>{$ad['title_ads']}</strong></a><br>
            <p>{$limited_description}</p>
            <small><em>Ads by {$ads_providers_name}</em></small>
        </div>
        <a href='$click_url' target='_blank'><img src='$image_url' alt='Ad Image'></a>
    </div>`);
    EOT;
}

echo "document.write('</div>');";
?>
