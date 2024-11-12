
<?php
/*
cronjob/click_audit.php 

update  `ad_clicks` set `isaudit` = 0  , `reason_rejection` = ''
*/

// Database connection
include("../db.php");
include("../function.php");

$conn = new mysqli($servername_db, $username_db, $password_db, $dbname_db);
if ($conn->connect_error) {
    error_log("Database connection failed: " . $conn->connect_error);
    exit("Database connection failed.");
}

$id = 1;
$this_providers_domain_url = get_providers_domain_url_json("providers_data.json", 1);


// HTML layout begins
echo "<!DOCTYPE html>";
echo "<html lang='id'>";
echo "<head>";
echo "<meta charset='UTF-8'>";
echo "<meta name='viewport' content='width=device-width, initial-scale=1.0'>";
echo "<title>Proses Click Audit</title>";
echo "<style>";
echo "body {font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif; margin: 0; padding: 0; background-color: #f0f2f5; color: #333;}";
echo "h1 {color: #0056b3; text-align: center; padding: 20px 0;}";
echo ".container {max-width: 1200px; margin: 30px auto; padding: 20px; background-color: #fff; border-radius: 10px; box-shadow: 0 0 15px rgba(0, 0, 0, 0.1);}";
echo ".section {margin-bottom: 40px;}";
echo ".section h2 {color: #0288D1; font-size: 1.75em; margin-bottom: 10px;}";
echo ".log {padding: 15px; background-color: #f9f9c5; border-left: 5px solid #f7c600; border-radius: 8px; margin-bottom: 30px; font-size: 1.1em;}";
echo ".highlight {color: green; font-weight: bold;}";
echo ".error {color: red; font-weight: bold;}";
echo ".table-wrapper {overflow-x: auto;}";
echo ".table {width: 100%; border-collapse: collapse; margin-bottom: 20px;}";
echo ".table th, .table td {padding: 12px 15px; border: 1px solid #ddd; text-align: left;}";
echo ".table th {background-color: #0056b3; color: #fff; text-align: center;}";
echo ".table td {background-color: #f9f9f9;}";
echo ".table tbody tr:hover {background-color: #f1f1f1;}";
echo "footer {text-align: center; padding: 20px; background-color: #0056b3; color: #fff; font-size: 0.9em; border-top-left-radius: 8px; border-top-right-radius: 8px;}";
echo ".process {background-color: #e9f2fb; padding: 20px; margin-bottom: 30px; border-left: 5px solid #0288D1; font-size: 1.1em; border-radius: 8px;}";
echo "</style>";
echo "</head>";
echo "<body>";

echo "<div class='container'>";
echo "<h1>Proses Click Audit</h1>";


// Proses informasi
echo "<div class='process'>";
echo "<h2>Informasi Proses</h2>";
echo "<p>Proses ini melakukan audit klik iklan yang belum diaudit. Setiap klik diperiksa menggunakan beberapa aturan untuk mendeteksi fraud berdasarkan IP address, browser, dan pola klik. Setelah audit selesai, hasilnya akan diperbarui di tabel <code>ad_clicks</code>.</p>";
echo "</div>";

// Show current process
echo "<div class='log'>";
echo "<strong>Log Proses Audit Klik:</strong>";
echo "<p>Sistem sedang memproses klik dan memeriksa apakah ada kecurangan dalam pola klik. Thresholds diambil dari tabel <code>setting_rule_clicks</code> untuk membandingkan dengan perilaku pengguna.</p>";
echo "</div>";

echo "<div class='log'><strong>Proses sedang dilakukan...</strong><br>";


$mysqli = new mysqli($servername_db, $username_db, $password_db, $dbname_db);

// Check for connection errors
if ($mysqli->connect_error) {
    die("Connection failed: " . $mysqli->connect_error);
}

// Memulai transaksi
$conn->begin_transaction();


try {
    // Fetch the hash_key from the providers table
    $providerQuery = "SELECT hash_key FROM providers WHERE id = ?";
    $providerStmt = $mysqli->prepare($providerQuery);

        if ($providerStmt === false) {
            die("Error in SQL prepare: " . $mysqli->error);
        }

    // Assuming the provider ID you want to query is 1
    $provider_id = 1;
    $providerStmt->bind_param("i", $provider_id);
    $providerStmt->execute();
    $providerStmt->bind_result($hash_key);

    if ($providerStmt->fetch()) {
       // echo "<p>Hash key ditemukan: <span class='highlight'>$hash_key</span></p>";
    } else {
        //die("No hash_key found for the provided ID.");
    }
    $providerStmt->close();

    // Process all clicks that have not been audited

    // SELECT * FROM ad_clicks WHERE isaudit = 0 LIMIT 1000;
    $sql="SELECT * FROM ad_clicks WHERE isaudit = 0 LIMIT 1000";

    $result = $mysqli->query($sql);
    if ($result->num_rows > 0) {
        while ($click = $result->fetch_assoc()) {
            echo "<p>Proses audit untuk klik ID: <strong>{$click['id']}</strong></p>";
            checkFraud($this_providers_domain_url, $click, $mysqli, $hash_key);
            echo "<br>Proses menghitung biaya iklan telah terpakai untuk klik ID: <strong>{$click['id']}</strong>";
        }
    } else {
        echo "<br><span class='highlight'>Tidak ada klik untuk diaudit saat ini.</span>";
    }

    // Commit transaksi jika semua berjalan lancar
    $conn->commit();

} catch (Exception $e) {
    // Rollback transaksi jika terjadi kesalahan
    $conn->rollback();
    error_log("Transaction failed: " . $e->getMessage());
    echo "<br><span class='error'>Terjadi kesalahan saat memproses transaksi. Semua perubahan telah dibatalkan.</span>";
}
    echo "</div>";

// ==============

// Create a new PDO instance
$pdo = new PDO("mysql:host=$servername_db;dbname=$dbname_db;charset=utf8mb4", $username_db, $password_db);
$pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

// Database connection using MySQLi
$mysqli = new mysqli($servername_db, $username_db, $password_db, $dbname_db);
if ($mysqli->connect_error) {
    error_log("Database connection failed: " . $mysqli->connect_error);
    exit("Database connection failed.");
}

// Query to select records where title_ads and site_name are empty
$query = "SELECT id, local_ads_id, pub_id, ads_providers_domain_url, pubs_providers_domain_url
          FROM ad_clicks 
          WHERE title_ads is null or site_domain is null";

$result = $mysqli->query($query);

if ($result->num_rows > 0) {
    while ($row = $result->fetch_assoc()) {
        $ad_click_id = $row['id'];
        $local_ads_id = $row['local_ads_id'];
        $pub_id = $row['pub_id'];
        $ads_providers_domain_url = $row['ads_providers_domain_url'];
        $pubs_providers_domain_url = $row['pubs_providers_domain_url'];

        // Get title_ads from advertisers_ads or advertisers_ads_partners based on domain URL
        if ($this_providers_domain_url == $ads_providers_domain_url) {
            $ad_query = "SELECT title_ads FROM advertisers_ads WHERE local_ads_id = ?";
        } else {
            $ad_query = "SELECT title_ads FROM advertisers_ads_partners WHERE local_ads_id = ?";
        }
        $ad_stmt = $pdo->prepare($ad_query);
        $ad_stmt->execute([$local_ads_id]);
        $ad_result = $ad_stmt->fetch(PDO::FETCH_ASSOC);
        $title_ads = $ad_result['title_ads'];

        // Get site_name and site_domain from publishers_site based on pub_id
        if ($this_providers_domain_url == $pubs_providers_domain_url) {
            $site_query = "SELECT site_name, site_domain FROM publishers_site WHERE id = ?";
            $site_stmt = $pdo->prepare($site_query);
            $site_stmt->execute([$pub_id]);
            $site_result = $site_stmt->fetch(PDO::FETCH_ASSOC);
            $site_name = $site_result['site_name'];
            $site_domain = $site_result['site_domain'];
        }

        // Update ad_clicks table with the fetched title_ads, site_name, and site_domain
        $update_query = "UPDATE ad_clicks 
                         SET title_ads = ?, site_name = ?, site_domain = ? 
                         WHERE id = ?";
        $update_stmt = $pdo->prepare($update_query);
        $update_stmt->execute([$title_ads, $site_name, $site_domain, $ad_click_id]);
    }
}

// Query to select records where title_ads and site_name are empty in ad_clicks_partner
$query = "SELECT id, local_ads_id, pub_id, ads_providers_domain_url, pubs_providers_domain_url
          FROM ad_clicks_partner 
          WHERE title_ads is null";

$result = $mysqli->query($query);

if ($result->num_rows > 0) {
    while ($row = $result->fetch_assoc()) {
        $ad_click_id = $row['id'];
        $local_ads_id = $row['local_ads_id'];
        $pub_id = $row['pub_id'];
        $ads_providers_domain_url = $row['ads_providers_domain_url'];
        $pubs_providers_domain_url = $row['pubs_providers_domain_url'];

        // Get title_ads from advertisers_ads or advertisers_ads_partners based on domain URL
        if ($this_providers_domain_url == $ads_providers_domain_url) {
            $ad_query = "SELECT title_ads FROM advertisers_ads WHERE local_ads_id = ?";
        } else {
            $ad_query = "SELECT title_ads FROM advertisers_ads_partners WHERE local_ads_id = ?";
        }
        $ad_stmt = $pdo->prepare($ad_query);
        $ad_stmt->execute([$local_ads_id]);
        $ad_result = $ad_stmt->fetch(PDO::FETCH_ASSOC);
        $title_ads = $ad_result['title_ads'];

        // Get site_name and site_domain from publishers_site_partners based on pub_id
        if ($this_providers_domain_url != $pubs_providers_domain_url) {
            $site_query = "SELECT site_name, site_domain FROM publishers_site_partners WHERE id = ?";
            $site_stmt = $pdo->prepare($site_query);
            $site_stmt->execute([$pub_id]);
            $site_result = $site_stmt->fetch(PDO::FETCH_ASSOC);
            $site_name = $site_result['site_name'];
            $site_domain = $site_result['site_domain'];
        }

        // Update ad_clicks_partner table with the fetched title_ads, site_name, and site_domain
        $update_query = "UPDATE ad_clicks_partner 
                         SET title_ads = ?, site_name = ?, site_domain = ? 
                         WHERE id = ?";
        $update_stmt = $pdo->prepare($update_query);
        $update_stmt->execute([$title_ads, $site_name, $site_domain, $ad_click_id]);
    }
}

$mysqli->close();
$pdo = null;

// ====================

// HTML footer
echo "<footer>";
echo "<p>&copy; 2024 - Proses Click Audit</p>";
echo "</footer>";
echo "</div>"; // .container
echo "</body>";
echo "</html>";




function isUsingProxyOrVpnHeaders() {
    if (!empty($_SERVER['HTTP_X_FORWARDED_FOR']) || 
        !empty($_SERVER['HTTP_VIA']) ||
        !empty($_SERVER['HTTP_FORWARDED'])) {
        return true; // Terindikasi menggunakan proxy atau VPN
    }
   
    return false; // Tidak terdeteksi proxy atau VPN
}


// Main fraud detection logic
function checkFraud($this_providers_domain_url,$click, $mysqli,$hash_key) {
    // Get threshold values from the database
    $ads_providers_domain_url = $click['ads_providers_domain_url'];

    echo "<br>ads_providers_domain_url: ".$ads_providers_domain_url;
     echo "<br>this_providers_domain_url: ".$this_providers_domain_url;


    if ($this_providers_domain_url==$ads_providers_domain_url) {
        $table_advertisers_ads = 
        "advertisers_ads";
    }
    else {
       $table_advertisers_ads = 
        "advertisers_ads_partners";
    }

    echo "<br>table_advertisers_ads: ".$table_advertisers_ads;

    $local_ads_id = $click['local_ads_id'];

    echo "<br>local_ads_id: ".$local_ads_id;

    
    // Validate if the ad is still active
    if (!isAdActive($table_advertisers_ads, $local_ads_id,$ads_providers_domain_url, $mysqli)) {
        auditClick($click['id'], 'Ad is expired or paused', 1, $mysqli);
        return;
    }
      echo "<br>isAdActive: Ad is not expired nor paused: ".$local_ads_id;

     echo "<br>click_audit_id checkFraud: ".json_encode($click);
    $aa = getThreshold('aa', $mysqli);
    $ab = getThreshold('ab', $mysqli);
    $ac = getThreshold('ac', $mysqli);
    $ad = getThreshold('ad', $mysqli);
    $ae = getThreshold('ae', $mysqli);
    $af = getThreshold('af', $mysqli);
    $ag = getThreshold('ag', $mysqli);
    $ah = getThreshold('ah', $mysqli);
    $ai = getThreshold('ai', $mysqli);
    $aj = getThreshold('aj', $mysqli);
    $ak = getThreshold('ak', $mysqli);
    $al = getThreshold('al', $mysqli);
    $am = getThreshold('am', $mysqli);
    $an = getThreshold('an', $mysqli);
    $ao = getThreshold('ao', $mysqli);
    $ap = getThreshold('ap', $mysqli);

  // Check for reverse proxy or VPN headers
echo "<br>isUsingProxyOrVpnHeaders: " . (isset($_SERVER['HTTP_X_FORWARDED_FOR']) ? $_SERVER['HTTP_X_FORWARDED_FOR'] : 'Tidak ada header');

echo "<br>isUsingProxyOrVpnHeaders:HTTP_X_REAL_IP: " . (isset($_SERVER['HTTP_X_REAL_IP']) ? $_SERVER['HTTP_X_REAL_IP'] : 'Tidak ada header');


echo "<br>isUsingProxyOrVpnHeaders: " . (isset($_SERVER['HTTP_VIA']) ? $_SERVER['HTTP_VIA'] : 'Tidak ada header');

echo "<br>isUsingProxyOrVpnHeaders: " . (isset($_SERVER['HTTP_FORWARDED']) ? $_SERVER['HTTP_FORWARDED'] : 'Tidak ada header');


    if (isUsingProxyOrVpnHeaders()) {
        auditClick($click['id'], 'Detected using Proxy or VPN', 1, $mysqli);
        return;
    }


    // Check if IP or Browser is banned
    if (isIpBanned($click['ip_address'], $mysqli,$click)) {
        auditClick($click['id'], 'IP Banned', 1, $mysqli);
        return;
    }

    if (isBrowserBanned($click['browser_agent'], $mysqli)) {
        auditClick($click['id'], 'Browser Banned', 1, $mysqli);
        return;
    }
  
    // Check click thresholds using the thresholds from the database

    if (countClicks($click['ip_address'], $click['user_cookies'], $click['browser_agent'], 20, $mysqli) > $aj) {
        auditClick($click['id'], 'Threshold AJ exceeded '.$aj.' ( rentang Jarak 20 detik terakhir dihitung dari `time_epoch_click`  antara klik terekam ada IP address dan browser yang sama) ', 1, $mysqli);
        return;
    }


    if (countClicks($click['ip_address'], null, $click['browser_agent'], 60, $mysqli) > $aa) {
        auditClick($click['id'], 'Threshold AA exceeded '.$aa.' ( rentang Jarak 1 menit terakhir dihitung dari `time_epoch_click`  antara klik terekam ada IP address dan browser yang sama) ', 1, $mysqli);
        return;
    }

    if (countClicks($click['ip_address'], null, $click['browser_agent'], 120, $mysqli) > $ab) {
        auditClick($click['id'], 'Threshold AB exceeded '.$ab.' (rentang Jarak 2 menit terakhir dihtung dari `time_epoch_click `antara klik terekam ada  IP address dan browser yang sama)', 1, $mysqli);
        return;
    }

    if (countClicks($click['ip_address'], null, $click['browser_agent'], 300, $mysqli) > $ac) {
        auditClick($click['id'], 'Threshold AC exceeded '.$ac.' (rentang Jarak 5 menit terakhir dihtung dari `time_epoch_click `antara klik terekam ada  IP address dan browser yang sama)', 1, $mysqli);
        return;
    }

    if (countClicks($click['ip_address'], $click['user_cookies'], $click['browser_agent'], 600, $mysqli) > $ad) {
        auditClick($click['id'], 'Threshold AD exceeded '.$ad.' (rentang Jarak 10 menit terakhir dihtung dari `time_epoch_click `antara klik terekam ada  IP address dan browser yang sama)', 1, $mysqli);
        return;
    }

    if (countClicks($click['ip_address'], null, $click['browser_agent'], 900, $mysqli) > $ae) {
        auditClick($click['id'], 'Threshold AE exceeded '.$ae.' (rentang Jarak 15 menit terakhir dihtung dari `time_epoch_click `antara klik terekam ada  IP address dan browser yang sama)', 1, $mysqli);
        return;
    }

    if (countClicks($click['ip_address'], null, $click['browser_agent'], 1200, $mysqli) > $af) {
        auditClick($click['id'], 'Threshold AF exceeded '.$af.' (rentang Jarak 20 menit terakhir dihtung dari `time_epoch_click `antara klik terekam ada  IP address dan browser yang sama)', 1, $mysqli);
        return;
    }

    if (countClicks($click['ip_address'], $click['user_cookies'], $click['browser_agent'], 1500, $mysqli) > $ag) {
        auditClick($click['id'], 'Threshold AG exceeded '.$ag.' (rentang Jarak 25 menit terakhir dihtung dari `time_epoch_click `antara klik terekam ada  IP address dan browser yang sama)', 1, $mysqli);
        return;
    }

    if (countClicks($click['ip_address'], null, $click['browser_agent'], 1800, $mysqli) > $ah) {
        auditClick($click['id'], 'Threshold AH exceeded '.$ah.'  (rentang Jarak 30  menit terakhir dihtung dari `time_epoch_click `antara klik terekam ada  IP address dan browser yang sama)', 1, $mysqli);
        return;
    }

    if (countClicks($click['ip_address'], null, $click['browser_agent'], 2100, $mysqli) > $ai) {
        auditClick($click['id'], 'Threshold AI exceeded '.$ai.' (rentang Jarak 35 menit terakhir dihtung dari `time_epoch_click `antara klik terekam ada  IP address dan browser yang sama)', 1, $mysqli);
        return;
    }

     if (countClicks($click['ip_address'], null, $click['browser_agent'], 3600 , $mysqli) > $ak) {
        auditClick($click['id'], 'Threshold AK exceeded '.$ai.' (rentang Jarak 1 jam terakhir dihtung dari `time_epoch_click `antara klik terekam ada  IP address dan browser yang sama)', 1, $mysqli);
        return;
    }

    // Cek klik dalam rentang waktu 2 jam
    if (countClicks($click['ip_address'], null, $click['browser_agent'], 7200, $mysqli) > $al) {
        auditClick($click['id'], 'Threshold 2 jam exceeded', 1, $mysqli);
        return;
    }


    // Cek klik dalam rentang waktu 4 jam
    if (countClicks($click['ip_address'], null, $click['browser_agent'], 14400, $mysqli) > $am) {
        auditClick($click['id'], 'Threshold 4 jam exceeded', 1, $mysqli);
        return;
    }


    // Cek klik dalam rentang waktu 6 jam
    if (countClicks($click['ip_address'], null, $click['browser_agent'], 21600, $mysqli) > $an) {
        auditClick($click['id'], 'Threshold 6 jam exceeded', 1, $mysqli);
        return;
    }


    // Cek klik dalam rentang waktu 12 jam
    if (countClicks($click['ip_address'], null, $click['browser_agent'], 43200, $mysqli) > $ao) {
        auditClick($click['id'], 'Threshold 12 jam exceeded', 1, $mysqli);
        return;
    }


    // Cek klik dalam rentang waktu 24 jam
    if (countClicks($click['ip_address'], null, $click['browser_agent'], 86400, $mysqli) > $ap) {
        auditClick($click['id'], 'Threshold 24 jam exceeded', 1, $mysqli);
        return;
    }

    // If no fraud detected, audit as valid
    $click_audit_id = $click['id'];
    echo "<br>click_audit_id: ".$click_audit_id. "<strong><font color=green>-Valid-</strong></font>";
    echo "<br>";
    auditClick($click['id'], '', 0, $mysqli);
    createHashAudit($click, $mysqli, 'Valid Click',$hash_key);

}

//include("update_titleads_sitename_clickads.php");



    


function getThreshold($rule_name, $mysqli) {
    $stmt = $mysqli->prepare("SELECT threshold FROM setting_rule_clicks WHERE rule_name = ?");
    $stmt->bind_param("s", $rule_name);
    $stmt->execute();
    $stmt->bind_result($threshold);
    $stmt->fetch();
    $stmt->close();
    echo "<br>rule_name: ".$rule_name. ". Threshold: ".$threshold ;
    return $threshold;
}


// Function to check if IP is in local range
function isLocalIpRange($ip_address) {
    // Define local IP ranges (example: 192.168.0.0/16, 10.0.0.0/8)
    $local_ranges = [
        '192.168.0.0/16',
        '10.0.0.0/8',
        '172.16.0.0/12',
        '127.0.0.0/8'
    ];

    foreach ($local_ranges as $range) {
        if (ipInRange($ip_address, $range)) {
            return true; // IP is in the local range
        }
    }

    return false; // IP is not in any local range
}

// Helper function to check if IP is within a range
function ipInRange($ip_address, $range) {
    list($subnet, $bits) = explode('/', $range);
    $ip = ip2long($ip_address);
    $subnet = ip2long($subnet);
    $mask = -1 << (32 - $bits);
    $subnet &= $mask; // Subnet mask

    return ($ip & $mask) == $subnet; // Check if IP is in the range
}

// Function to check if IP is banned
function isIpBanned($ip_address, $mysqli,$click) {
    // Check if the IP is in the banned list
    $stmt = $mysqli->prepare("SELECT COUNT(*) FROM list_ip_banned WHERE ip_address = ?");
    $stmt->bind_param("s", $ip_address);
    $stmt->execute();
    $stmt->bind_result($count);
    $stmt->fetch();
    $stmt->close();
    echo "<br>ip_address: ".$ip_address. " count: ".$count;


    // Check if the IP is in the local range
    if (isLocalIpRange($ip_address)) {
        echo "<br>IP lokal terdeteksi: ".$ip_address;
        $is_reject = 1; // Reject local IP
        auditClick($click['id'], 'IP Lokal', $is_reject, $mysqli); // Call auditClick with reject
        return true;
    }

    return false; // IP is not banned or local

}

function isBrowserBanned($browser_agent, $mysqli) {
    $stmt = $mysqli->prepare("SELECT COUNT(*) FROM list_browser_banned WHERE browser_agent = ?");
    $stmt->bind_param("s", $browser_agent);
    $stmt->execute();
    $stmt->bind_result($count);
    $stmt->fetch();
    $stmt->close();
      echo "<br>browser_agent: ".$browser_agent. " count: ".$count;
  

    // Check for banned keywords in the user agent
    if ($count > 0 || isBrowserAgentBot($browser_agent)) {
        return true;
    }

    return false;
}


function isBrowserAgentBot($browser_agent) {
    $banned_keywords = ['Bot', 'crawler', 'spider', 'archive'];

    foreach ($banned_keywords as $keyword) {
        if (stripos($browser_agent, $keyword) !== false) {
            return true;
        }
    }

    return false;
}

// Function to count clicks within time frame
function countClicks($ip_address, $user_cookies, $browser_agent, $time_interval, $mysqli) {
    $stmt = $mysqli->prepare("SELECT COUNT(*) FROM ad_clicks WHERE ip_address = ? AND user_cookies = ? AND browser_agent = ? AND time_epoch_click > (UNIX_TIMESTAMP() - ?)");
    $stmt->bind_param("sssi", $ip_address, $user_cookies, $browser_agent, $time_interval);
    $stmt->execute();
    $stmt->bind_result($count);
    $stmt->fetch();
    $stmt->close();
    echo "<br>time_interval: ".$time_interval. " count: ".$count;
    return $count;
}

// Function to audit clicks
function auditClick($click_id, $reason, $is_reject, $mysqli) {
    echo "<br>hasil audit click_id: ".$click_id. " is_reject: ".$is_reject. " Alasan: ".$reason;
    $stmt = $mysqli->prepare("UPDATE ad_clicks SET isaudit = 1, audit_date = NOW(), is_reject = ?, reason_rejection = ? WHERE id = ?");
    $stmt->bind_param("isi", $is_reject, $reason, $click_id);
    $stmt->execute();
    $stmt->close();
}





function createHashAudit($click, $mysqli, $audit_reason,$hash_key) {

      // Create the hash_audit value
    $hash_string = $hash_key ."~".
                   $click['id'] ."~".
                   $click['time_epoch_click'] ."~".
                   $click['ads_providers_name'] ."~".
                   $click['pubs_providers_name'] ."~".
                   $click['referrer'] ."~".
                   $click['landingpage_ads'] ."~".
                   $audit_reason;
                   
    $hash_audit = md5($hash_string);
    echo "<br>hash_audit: ".$hash_audit. " click id: ".$click['id'] ;


    // Update the hash_audit in the database
    $stmt = $mysqli->prepare("UPDATE ad_clicks SET hash_audit = ? WHERE id = ?");
    if ($stmt === false) {
        die("Error in SQL prepare: " . $mysqli->error);
    }

    $stmt->bind_param("si", $hash_audit, $click['id']);
    $stmt->execute();
    $stmt->close();
}



// Function to check if the ad is active (not expired or paused)
function isAdActive($table_advertisers_ads, $local_ads_id, $ads_providers_domain_url , $mysqli) {
    $sqlCheck ="SELECT is_expired, is_paused FROM $table_advertisers_ads WHERE local_ads_id = ? AND providers_domain_url = ?";
    $stmt = $mysqli->prepare($sqlCheck);
    $stmt->bind_param("is", $local_ads_id, $ads_providers_domain_url);
    $stmt->execute();
    $stmt->bind_result($is_expired, $is_paused);
    $stmt->fetch();
    $stmt->close();
    
     echo "<br>ads_providers_domain_url: ".$ads_providers_domain_url;
    echo "<br>is_expired: ".$is_expired;
    echo "<br>is_paused: ".$is_paused;
    
    // Check if the ad is expired or paused
    return ($is_expired == 0 && $is_paused == 0);
}


?>


