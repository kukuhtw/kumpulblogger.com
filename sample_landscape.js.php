<?php
/*
preview.js.php
*/
header('Content-Type: application/javascript');

$pubId = isset($_GET['pubId']) ? intval($_GET['pubId']) : '';
$pubProvName = isset($_GET['pubProvName']) ? $_GET['pubProvName'] : '';
$maxAds = isset($_GET['maxads']) ? intval($_GET['maxads']) : 1; // Default to 1 if not provided
$column = isset($_GET['column']) ? intval($_GET['column']) : 2; // Default to 2 if not provided
$local_ads_id = isset($_GET['local_ads_id']) ? intval($_GET['local_ads_id']) : 1; // Default to 1 if not provided

include("db.php");
include("function.php");

$mysqli = new mysqli($servername_db, $username_db, $password_db, $dbname_db);

if ($mysqli->connect_error) {
    die("Koneksi gagal: " . $mysqli->connect_error);
}

// Database connection using MySQLi
$conn = new mysqli($servername_db, $username_db, $password_db, $dbname_db);
if ($conn->connect_error) {
    error_log("Database connection failed: " . $conn->connect_error);
    exit("Database connection failed.");
}

$maxAds = ($maxAds > 0 && $maxAds <= 50) ? $maxAds : 10; // Limit the number of ads between 1 and 50
$column = ($column > 0 && $column <= 12) ? $column : 3; // Limit the number of columns between 1 and 12

$pubs_providers_domain_url = get_providers_domain_url_json("providers_data.json", 1);


$probability = 55;
$randomNumber = rand(1, 100);
$filter_order = ($randomNumber <= $probability) ? "RAND()" : "budget_per_click_textads DESC";


$sql = "SELECT id, local_ads_id, title_ads, description_ads, landingpage_ads, image_url
        FROM advertisers_ads
        WHERE 1 = 1
        AND ispublished = 1
        AND is_expired = 0 
        ORDER BY $filter_order

        ";

$stmt = $mysqli->prepare($sql);
if ($stmt === false) {
    die("Error in SQL prepare: " . $mysqli->error);
}

$stmt->execute();
$result = $stmt->get_result();

$ads = [];
if ($result->num_rows > 0) {
    $ads = $result->fetch_all(MYSQLI_ASSOC);
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
    $description_ads = strlen($ad['description_ads']) > 250 ? substr($ad['description_ads'], 0, 250) . '...' : $ad['description_ads'];
    $landingpage_ads = $ad['landingpage_ads'];
    $image_url = $ad['image_url'] ?: '';

    
    echo <<<EOT
    document.write(`<div class='landscape-ads-item'>
        <a href='$landingpage_ads' target='_blank'><img src='$image_url' alt='Ad Image'></a>
        <div>
            <a href='$landingpage_ads' target='_blank'><strong>{$ad['title_ads']}</strong></a>
            <p>{$description_ads}</p>
            <small><em>Ads by: KumpulBlogger.com</em></small>
        </div>
    </div>`);
    EOT;
}

echo "document.write('</div>');";
?>
