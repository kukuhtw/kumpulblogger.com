<?php
/*
track_click.php

*/
session_start();  // Start the session
include("db.php");
include("function.php");

// Connect to the database
$mysqli = new mysqli($servername_db, $username_db, $password_db, $dbname_db);

if ($mysqli->connect_error) {
    die("Koneksi gagal: " . $mysqli->connect_error);
}

// Check if user cookie exists, if not, create it
if (!isset($_COOKIE['user_cookie'])) {
    // Create a unique identifier for the cookie
    $user_cookie = bin2hex(random_bytes(16)); // 32 character long unique ID
    setcookie('user_cookie', $user_cookie, time() + (7 * 24 * 60 * 60), "/"); // Set cookie for 7 days
} else {
    $user_cookie = $_COOKIE['user_cookie'];
}

// Fetch and sanitize input parameters
$adId = isset($_GET['adId']) ? (int)$_GET['adId'] : 0;
$pubId = isset($_GET['pubId']) ? (int)$_GET['pubId'] : 0;
$localAdsId = isset($_GET['localAdsId']) ? (int)$_GET['localAdsId'] : 0;

$ads_providers_name = isset($_GET['ads_providers_name']) ? $_GET['ads_providers_name'] : '';

$ip_address = isset($_GET['ip']) ? $_GET['ip'] : '';
$browser_agent = isset($_GET['agent']) ? $_GET['agent'] : '';
$referrer = isset($_GET['referrer']) ? $_GET['referrer'] : '';
$sourceTable = isset($_GET['sourceTable']) ? $_GET['sourceTable'] : '';

$skey = isset($_GET['skey']) ? $_GET['skey'] : '';
$expected_key = md5($ip_address.$adId.$pubId.$localAdsId.urlencode($referrer));

if ($skey!=$expected_key) {
    echo "<br>DontDoThat!";
    exit;
}

$pubs_providers_name = getProvidersNameById_JSON("providers_data.json", 1);
$pubs_providers_domain_url = get_providers_domain_url_json("providers_data.json", 1);


$pubProvName = isset($_GET['pubProvName']) ? $_GET['pubProvName'] : '';
$ads_from_this_providers = isset($_GET['ads_from_this_providers']) ? $_GET['ads_from_this_providers'] : '';
$ads_providers_domain_url = isset($_GET['ads_providers_domain_url']) ? $_GET['ads_providers_domain_url'] : '';

if ($adId > 0 && $pubId > 0 && $localAdsId > 0) {
    $query = "SELECT landingpage_ads, rate_text_ads, budget_per_click_textads, revenue_publishers 
              FROM mapping_advertisers_ads_publishers_site
              WHERE local_ads_id = ?
              AND publishers_site_local_id = ? 
              AND ads_providers_domain_url = ?";

    $stmt = $mysqli->prepare($query);
    if ($stmt === false) {
        die("Error in SQL prepare: " . $mysqli->error);
    }

    $stmt->bind_param("iis", $localAdsId, $pubId, $ads_providers_domain_url);
    $stmt->execute();
    $stmt->bind_result($landingpage_ads, $rate_text_ads, $budget_per_click_textads, $revenue_publishers);
    $stmt->fetch();
    $stmt->close();

    $click_time = new DateTime();
    $time_epoch_click = $click_time->getTimestamp();

    if ($pubs_providers_domain_url == $ads_providers_domain_url) {
        $revenue_adnetwork_local = $revenue_publishers / 2;
        $revenue_adnetwork_partner = 0;
    } else {
        $revenue_adnetwork_local = $revenue_publishers / 2;
        $revenue_adnetwork_partner = $revenue_publishers / 2;
    }

    // Fetch the hash_key from the providers table
    $providerQuery = "SELECT hash_key FROM providers WHERE id = ?";
    $providerStmt = $mysqli->prepare($providerQuery);
    if ($providerStmt === false) {
        die("Error in SQL prepare: " . $mysqli->error);
    }
    $providerStmt->bind_param("i", $id);
    $providerStmt->execute();
    $providerStmt->bind_result($hash_key);
    $providerStmt->fetch();
    $providerStmt->close();

    // Create the hash_click value
    $hash_string = $hash_key ."~". 
    $time_epoch_click. "~". 
    $ads_providers_name. "~".
    $pubs_providers_name. "~".
    $referrer. "~". 
    $landingpage_ads. "~".
    $rate_text_ads. "~". 
    $budget_per_click_textads. "~".
    $revenue_publishers. "~".
    $revenue_adnetwork_local. "~".
    $revenue_adnetwork_partner;
 //   debug_text('hash_string.txt',$hash_string);
    $hash_click = md5($hash_string);

    // Prepare the SQL statement for insertion
    $stmt = $mysqli->prepare("INSERT INTO ad_clicks (ad_id, pub_id, pub_provider, local_ads_id,
    ads_providers_name, ads_providers_domain_url, user_cookies, ip_address, browser_agent, referrer, landingpage_ads, click_time, time_epoch_click, rate_text_ads, budget_per_click_textads, pubs_providers_name, pubs_providers_domain_url, revenue_publishers, revenue_adnetwork_local, revenue_adnetwork_partner, hash_click) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, NOW(), ?, ?, ?, ?, ?, ?, ?, ?, ?)");

    if ($stmt === false) {
        die("Error in SQL prepare: " . $mysqli->error);
    }

    // Bind the parameters, including the new hash_click value
    $stmt->bind_param("iisssssssssiiissiiis", $adId, $pubId, $pubProvName, $localAdsId, $ads_providers_name, $ads_providers_domain_url, $user_cookie, $ip_address, $browser_agent, $referrer, $landingpage_ads, $time_epoch_click, $rate_text_ads, $budget_per_click_textads, $pubs_providers_name, $pubs_providers_domain_url, $revenue_publishers, $revenue_adnetwork_local, $revenue_adnetwork_partner, $hash_click);

    // Execute the statement
    $stmt->execute();

    // Close the statement
    $stmt->close();

    // Redirect to the landing page
    header("Location: $landingpage_ads");
    exit();
}

$mysqli->close();
?>
