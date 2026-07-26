<?php
// update_approval_advertiser_partner.php
include("db.php");
include("function.php");
session_start();

if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    header('Allow: POST');
    exit('Method not allowed.');
}

$user_id = (int) $_SESSION['user_id'];
$local_mapping_id = isset($_POST['local_mapping_id']) ? (int) $_POST['local_mapping_id'] : 0;
$local_ads_id = isset($_POST['local_ads_id']) ? (int) $_POST['local_ads_id'] : 0;
$publishers_site_local_id = isset($_POST['publishers_site_local_id']) ? (int) $_POST['publishers_site_local_id'] : 0;
$is_approved_by_advertiser = isset($_POST['is_approved_by_advertiser'])
    ? (int) $_POST['is_approved_by_advertiser']
    : -1;

if (
    $local_mapping_id < 1 ||
    $local_ads_id < 1 ||
    $publishers_site_local_id < 1 ||
    !in_array($is_approved_by_advertiser, [0, 1], true)
) {
    http_response_code(400);
    exit('Parameter approval tidak valid.');
}

$conn = new mysqli($servername_db, $username_db, $password_db, $dbname_db);
if ($conn->connect_error) {
    error_log("Database connection failed: " . $conn->connect_error);
    exit("Database connection failed.");
}

$this_providers_domain_url = get_providers_domain_url_json("providers_data.json", 1);

// Resolve all provider values from an owned mapping. Never trust the hidden
// domain fields sent by the browser, since they can be edited by a user.
$ownership_sql = "
    SELECT m.pubs_providers_domain_url, m.ads_providers_domain_url
    FROM mapping_advertisers_ads_publishers_site_from_partners AS m
    INNER JOIN advertisers_ads AS a
        ON a.local_ads_id = m.local_ads_id
       AND a.advertisers_id = m.owner_advertisers_id
       AND a.providers_domain_url = m.ads_providers_domain_url
    WHERE m.local_mapping_id = ?
      AND m.local_ads_id = ?
      AND m.publishers_site_local_id = ?
      AND m.owner_advertisers_id = ?
      AND m.ads_providers_domain_url = ?
    LIMIT 1";
$ownership_stmt = $conn->prepare($ownership_sql);
$ownership_stmt->bind_param(
    'iiiis',
    $local_mapping_id,
    $local_ads_id,
    $publishers_site_local_id,
    $user_id,
    $this_providers_domain_url
);
$ownership_stmt->execute();
$owned_mapping = $ownership_stmt->get_result()->fetch_assoc();
$ownership_stmt->close();

if (!$owned_mapping) {
    $conn->close();
    http_response_code(404);
    exit('Mapping tidak ditemukan atau bukan milik Anda.');
}

$pubs_providers_domain_url = $owned_mapping['pubs_providers_domain_url'];
$ads_providers_domain_url = $owned_mapping['ads_providers_domain_url'];

$update_sql = "
    UPDATE mapping_advertisers_ads_publishers_site_from_partners
    SET is_approved_by_advertiser = ?, approval_date_advertiser = NOW()
    WHERE local_mapping_id = ?
      AND local_ads_id = ?
      AND publishers_site_local_id = ?
      AND owner_advertisers_id = ?
      AND ads_providers_domain_url = ?";
$update_stmt = $conn->prepare($update_sql);
$update_stmt->bind_param(
    'iiiiis',
    $is_approved_by_advertiser,
    $local_mapping_id,
    $local_ads_id,
    $publishers_site_local_id,
    $user_id,
    $this_providers_domain_url
);
if (!$update_stmt->execute()) {
    error_log('Failed to update advertiser partner approval: ' . $update_stmt->error);
    $update_stmt->close();
    $conn->close();
    http_response_code(500);
    exit('Approval gagal diperbarui.');
}
$update_stmt->close();

$provider_sql = "
    SELECT api_endpoint, public_key, secret_key
    FROM providers_partners
    WHERE target_providers_domain_url = ? AND providers_domain_url = ?
    LIMIT 1";
$provider_stmt = $conn->prepare($provider_sql);
$provider_stmt->bind_param('ss', $this_providers_domain_url, $pubs_providers_domain_url);
$provider_stmt->execute();
$provider = $provider_stmt->get_result()->fetch_assoc();
$provider_stmt->close();

if (!$provider) {
    error_log('Partner provider credentials not found for approval synchronization.');
    $conn->close();
    header('Location: view_ads_publishers_partner_mapping.php?local_ads_id=' . $local_ads_id . '&status=error');
    exit;
}

$api_url = rtrim($provider['api_endpoint'], '/') . '/approval_advertiser_partner/index.php';
$payload = json_encode([
    'providers_domain_url' => $this_providers_domain_url,
    'id' => $local_mapping_id,
    'is_approved_by_advertiser' => $is_approved_by_advertiser,
    'local_ads_id' => $local_ads_id,
    'publishers_site_local_id' => $publishers_site_local_id,
    'pubs_providers_domain_url' => $pubs_providers_domain_url,
    'ads_providers_domain_url' => $ads_providers_domain_url,
]);

$ch = curl_init($api_url);
curl_setopt_array($ch, [
    CURLOPT_RETURNTRANSFER => true,
    CURLOPT_HTTPHEADER => [
        'Content-Type: application/json',
        'Accept: application/json',
        'public_key: ' . $provider['public_key'],
        'secret_key: ' . $provider['secret_key'],
    ],
    CURLOPT_POST => true,
    CURLOPT_POSTFIELDS => $payload,
    CURLOPT_CONNECTTIMEOUT => 10,
    CURLOPT_TIMEOUT => 30,
]);
$response = curl_exec($ch);
$curl_error = curl_error($ch);
$http_status = (int) curl_getinfo($ch, CURLINFO_HTTP_CODE);
curl_close($ch);
$conn->close();

$response_data = is_string($response) ? json_decode($response, true) : null;
if (
    $response === false ||
    $http_status < 200 ||
    $http_status >= 300 ||
    !is_array($response_data) ||
    ($response_data['status'] ?? '') !== 'success'
) {
    error_log(
        'Partner approval sync failed. HTTP ' . $http_status .
        ($curl_error !== '' ? '; cURL: ' . $curl_error : '')
    );
    header('Location: view_ads_publishers_partner_mapping.php?local_ads_id=' . $local_ads_id . '&status=error');
    exit;
}

header('Location: view_ads_publishers_partner_mapping.php?local_ads_id=' . $local_ads_id . '&status=success');
exit;
?>
