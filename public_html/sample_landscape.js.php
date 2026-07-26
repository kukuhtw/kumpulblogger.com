<?php
/*
preview.js.php
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
        AND is_paused = 0
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

$carousel_id = 'kb-native-carousel-' . bin2hex(random_bytes(4));

echo <<<EOT
document.write(`<style>
.landscape-ads-container {
    position: relative;
    margin: 20px 0;
    overflow: hidden;
}
.landscape-ads-item {
    display: none;
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
.landscape-ads-item.active {
    display: flex;
}
.kb-carousel-arrow {
    position: absolute;
    top: 50%;
    transform: translateY(-50%);
    z-index: 10;
    width: 32px;
    height: 32px;
    border: none;
    border-radius: 50%;
    background: rgba(0,0,0,0.45);
    color: #fff;
    font-size: 16px;
    line-height: 1;
    cursor: pointer;
    display: flex;
    align-items: center;
    justify-content: center;
    opacity: 0.7;
    transition: opacity .2s;
}
.kb-carousel-arrow:hover {
    opacity: 1;
}
.kb-carousel-arrow.prev {
    left: 8px;
}
.kb-carousel-arrow.next {
    right: 8px;
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

echo "document.write('<div id=\"$carousel_id\" class=\"landscape-ads-container\">');";

if (count($ads) > 1) {
    echo "document.write('<button type=\"button\" class=\"kb-carousel-arrow prev\" aria-label=\"Previous ad\">&#10094;</button><button type=\"button\" class=\"kb-carousel-arrow next\" aria-label=\"Next ad\">&#10095;</button>');";
}

$ad_index = 0;
foreach ($ads as $ad) {
    $active_class = ($ad_index === 0) ? ' active' : '';
    $ad_index++;
    $ad_id = $ad['id'];
    $description_ads = strlen($ad['description_ads']) > 250 ? substr($ad['description_ads'], 0, 250) . '...' : $ad['description_ads'];
    $landingpage_ads = $ad['landingpage_ads'];
    $image_url = $ad['image_url'] ?: '';

    $title_ads_safe = ad_js_escape($ad['title_ads']);
    $description_ads_safe = ad_js_escape($description_ads);
    $landingpage_ads_safe = ad_js_escape($landingpage_ads);
    $image_url_safe = ad_js_escape($image_url);

    echo <<<EOT
    document.write(`<div class='landscape-ads-item$active_class'>
        <a href='$landingpage_ads_safe' target='_blank'><img src='$image_url_safe' alt='Ad Image'></a>
        <div>
            <a href='$landingpage_ads_safe' target='_blank'><strong>$title_ads_safe</strong></a>
            <p>$description_ads_safe</p>
            <small><em>Ads by: KumpulBlogger.com</em></small>
        </div>
    </div>`);
    EOT;
}

echo "document.write('</div>');";

if (count($ads) > 1) {
    echo <<<EOT
    (function(){
        var container = document.getElementById('$carousel_id');
        if (!container) return;
        var slides = container.querySelectorAll('.landscape-ads-item');
        if (slides.length <= 1) return;
        var prevBtn = container.querySelector('.kb-carousel-arrow.prev');
        var nextBtn = container.querySelector('.kb-carousel-arrow.next');
        var idx = 0;
        var timer;
        function goTo(newIdx){
            slides[idx].classList.remove('active');
            idx = (newIdx + slides.length) % slides.length;
            slides[idx].classList.add('active');
        }
        function startTimer(){
            timer = setInterval(function(){ goTo(idx + 1); }, 7000);
        }
        function resetTimer(){
            clearInterval(timer);
            startTimer();
        }
        if (prevBtn) prevBtn.addEventListener('click', function(){ goTo(idx - 1); resetTimer(); });
        if (nextBtn) nextBtn.addEventListener('click', function(){ goTo(idx + 1); resetTimer(); });
        startTimer();
    })();
    EOT;
}
?>
