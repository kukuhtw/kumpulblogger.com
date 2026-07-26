<?php
/*
show_ads_native_landscape.js.php
*/
error_reporting(E_ALL);
ini_set('display_errors', 1);

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
            $filter .= "AND m.ads_providers_domain_url!= '" . $mysqli->real_escape_string($row['providers_domain_url']) . "' ";
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
$filter_order = ($randomNumber <= $probability) ? "RAND()" : "m.budget_per_click_textads DESC";

$sql = "SELECT m.id, m.local_ads_id, 
               m.ads_providers_name, m.ads_providers_domain_url,
               m.title_ads, m.description_ads, m.landingpage_ads, m.image_url
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

$carousel_id = 'kb-native-carousel-' . bin2hex(random_bytes(4));

echo <<<EOT
document.write(`<style>
.landscape-ads-container {
    width: 100%;                     /* wajib full */
    position: relative;
    margin: 20px 0;
    overflow: hidden;
}

.landscape-ads-item {
    display: none;
    align-items: flex-start;   /* teks mulai di atas */
    border: 1px solid #ddd;
    padding: 15px;
    background-color: #fff;
    box-shadow: 0 4px 8px rgba(0,0,0,0.1);
    border-radius: 8px;
    transition: transform .2s, box-shadow .2s;
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
    box-shadow: 0 8px 16px rgba(0,0,0,0.2);
}
.landscape-ads-item div {
    flex: 1 1 auto;
    overflow: visible;          /* jangan dipotong */
    padding-right: 15px;
}
.landscape-ads-item img {
    float: none;                /* matikan float */
    flex-shrink: 0;             /* jangan diperkecil */
    width: 240px;
    max-width: 100%;
    height: auto;
    object-fit: cover;
    margin-right: 15px;
    margin-bottom: 0;           /* hapus jarak bawah */
    border-radius: 8px;
    border: 1px solid #ddd;
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
.kb-ads-branding {
    margin: -12px 0 20px;
    text-align: right;
    font: 500 11px/1.4 Arial, sans-serif;
}
.kb-ads-branding a {
    color: #6b7280;
    text-decoration: none;
}
.kb-ads-branding a:hover {
    color: #00796b;
    text-decoration: underline;
}

/* RESPONSIVE MOBILE */
@media (max-width: 600px) {
  .landscape-ads-item {
    flex-direction: column;
    text-align: center;
  }
  .landscape-ads-item div {
    padding: 0 0 10px;
  }
  .landscape-ads-item img {
    width: 100%;
    margin: 0 0 10px;
  }
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
    document.write(`<div class='landscape-ads-item$active_class'>
        <div>
            <a href='$click_url_safe' target='_blank'><strong>$title_ads_safe</strong></a><br>
            <p>$limited_description_safe</p>
            <small><em>Ads by <a href='$ads_providers_domain_url_safe' target='_blank'>$ads_providers_name_safe</a></em></small>

        </div>
        <a href='$click_url_safe' target='_blank'><img src='$image_url_safe' alt='Ad Image'></a>
    </div>`);
    EOT;
}

echo "document.write('</div><div class=\"kb-ads-branding\"><a href=\"https://kumpulblogger.com/\" target=\"_blank\" rel=\"noopener noreferrer\">Powered by KumpulBlogger.com</a></div>');";

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
