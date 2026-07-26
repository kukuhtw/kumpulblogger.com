<?php

/*
function.php

*/
include("function_provider.php");
include("function_ads.php");
include("function_publisher.php");

// Server-side secret used to sign click-tracking parameters (skey) so it cannot
// be forged just by reading the publicly visible click_url. Never expose this
// value to the client; rotate it (and invalidate in-flight click URLs) if leaked.
if (!defined('CLICK_SKEY_SECRET')) {
    define('CLICK_SKEY_SECRET', '0adef24b36954e0a3ac15cf7eafec8fc902d69045104a6d41c4ce44278d3abcb');
}

// Computes the signed key used to verify that a click on track_click.php
// actually originated from an ad we rendered, instead of being forged by
// replaying the visible query-string parameters.
function build_click_skey($ip_address, $ad_id, $pub_id, $local_ads_id, $referrer) {
    $payload = $ip_address . '|' . $ad_id . '|' . $pub_id . '|' . $local_ads_id . '|' . urlencode($referrer);
    return hash_hmac('sha256', $payload, CLICK_SKEY_SECRET);
}

// Escapes a value for safe interpolation inside a document.write(`...`) JS
// template literal that also contains single-quoted HTML attributes, so
// advertiser/DB-sourced content (title, description, image/domain URLs) can't
// break out of the template or inject HTML/JS into the publisher's page.
function ad_js_escape($value) {
    $value = (string) $value;
    $value = str_replace('\\', '\\\\', $value);
    $value = str_replace('`', '\\`', $value);
    $value = str_replace('$', '\\$', $value);
    $value = str_replace("'", '&#39;', $value);
    $value = str_replace('"', '&quot;', $value);
    $value = str_replace('<', '&lt;', $value);
    $value = str_replace('>', '&gt;', $value);
    return $value;
}

// Reads a single threshold from setting_rule_clicks. Named distinctly from
// cronjob/click_audit.php's own getThreshold() (which is declared as a plain
// global function in that file) to avoid a fatal function-redeclaration error,
// since click_audit.php also includes this file.
function get_click_rule_threshold($rule_name, $mysqli) {
    $stmt = $mysqli->prepare("SELECT threshold FROM setting_rule_clicks WHERE rule_name = ?");
    $stmt->bind_param("s", $rule_name);
    $stmt->execute();
    $stmt->bind_result($threshold);
    $stmt->fetch();
    $stmt->close();
    return $threshold;
}

// Counts clicks from the same IP+browser combo in the last $time_interval_seconds,
// used for a real-time velocity check at click-recording time (see track_click.php),
// ahead of the fuller async sweep in cronjob/click_audit.php.
function count_recent_clicks($ip_address, $browser_agent, $time_interval_seconds, $mysqli) {
    $stmt = $mysqli->prepare("SELECT COUNT(*) FROM ad_clicks WHERE ip_address = ? AND browser_agent = ? AND time_epoch_click > (UNIX_TIMESTAMP() - ?)");
    $stmt->bind_param("ssi", $ip_address, $browser_agent, $time_interval_seconds);
    $stmt->execute();
    $stmt->bind_result($count);
    $stmt->fetch();
    $stmt->close();
    return (int) $count;
}

// Flags user agents that are very unlikely to be a real browser: known
// bot/crawler/automation-tool signatures, HTTP-library defaults commonly used
// by click-fraud scripts (curl, python-requests, ...), or a missing/empty UA.
// Real browsers have identified as "Mozilla/..." since the 1990s for legacy
// compatibility reasons, so its absence is itself a strong signal.
function is_probable_bot_user_agent($browser_agent) {
    $browser_agent = trim((string) $browser_agent);
    if ($browser_agent === '') {
        return true;
    }

    $banned_keywords = [
        'bot', 'crawler', 'spider', 'archive', 'headless',
        'phantomjs', 'selenium', 'puppeteer', 'playwright',
        'curl', 'wget', 'python-requests', 'python-urllib',
        'go-http-client', 'okhttp', 'java/', 'libwww-perl',
        'scrapy', 'axios', 'node-fetch', 'httpclient',
        'postmanruntime', 'insomnia',
    ];
    $agent_lower = strtolower($browser_agent);
    foreach ($banned_keywords as $keyword) {
        if (strpos($agent_lower, $keyword) !== false) {
            return true;
        }
    }

    if (strpos($browser_agent, 'Mozilla') === false) {
        return true;
    }

    return false;
}

function validateLastTenHashClicks($mysqli) {
    // Query to get the last 10 records from ad_clicks
    $query = "SELECT 
                ad_clicks.id,
                ad_clicks.ads_providers_name,
                ad_clicks.pubs_providers_name,
                ad_clicks.referrer,
                ad_clicks.landingpage_ads, 
                ad_clicks.time_epoch_click, 
                ad_clicks.rate_text_ads, 
                ad_clicks.budget_per_click_textads, 
                ad_clicks.revenue_publishers, 
                ad_clicks.revenue_adnetwork_local, 
                ad_clicks.revenue_adnetwork_partner, 
                ad_clicks.hash_click
              FROM ad_clicks
              ORDER BY ad_clicks.id DESC
              LIMIT 10";

    $result = $mysqli->query($query);
    if ($result === false) {
        die("Error in SQL query: " . $mysqli->error);
    }

    $isAllValid = true; // Variable to keep track of the overall validity

    // Loop through each record
    while ($row = $result->fetch_assoc()) {
        $pub_id = 1;
         $id = $row['id'];
        $ads_providers_name = $row['ads_providers_name'];
        $pubs_providers_name = $row['pubs_providers_name'];
         $referrer = $row['referrer'];
         $landingpage_ads= $row['landingpage_ads'];
        $time_epoch_click = $row['time_epoch_click'];
        $rate_text_ads = $row['rate_text_ads'];
        $budget_per_click_textads = $row['budget_per_click_textads'];
        $revenue_publishers = $row['revenue_publishers'];
        $revenue_adnetwork_local = $row['revenue_adnetwork_local'];
        $revenue_adnetwork_partner = $row['revenue_adnetwork_partner'];
        $stored_hash_click = $row['hash_click'];

        // Fetch the hash_key from the providers table
        $providerQuery = "SELECT hash_key FROM providers WHERE id = ?";
        $providerStmt = $mysqli->prepare($providerQuery);
        if ($providerStmt === false) {
            die("Error in SQL prepare: " . $mysqli->error);
        }
        $providerStmt->bind_param("i", $pub_id);
        $providerStmt->execute();
        $providerStmt->bind_result($hash_key);
        $providerStmt->fetch();
        $providerStmt->close();

   

        // Create the hash_click value
        $hash_string = $hash_key . "~" . 
                       $time_epoch_click . "~" .
                       $ads_providers_name. "~". 
                       $pubs_providers_name. "~".
                        $referrer. "~". 
                        $landingpage_ads. "~".
                       $rate_text_ads . "~" . 
                       $budget_per_click_textads . "~" .
                       $revenue_publishers . "~" .
                       $revenue_adnetwork_local . "~" .
                       $revenue_adnetwork_partner;
 
          echo "<br>hash_string = "   .$hash_string;          
         debug_text('hash_string2.txt',$hash_string);
                    
        $computed_hash_click = md5($hash_string);

        // Compare the computed hash with the stored hash
        if ($computed_hash_click !== $stored_hash_click) {
            $isAllValid = false;
            echo "Invalid hash_click detected for id $id: Computed = $computed_hash_click, Stored = $stored_hash_click<br>";
        } else {
            echo "Valid hash_click for id $id: $computed_hash_click<br>";
        }
    }

    if ($isAllValid) {
        echo "All hash_clicks are valid.";
    } else {
        echo "Some hash_clicks are invalid.";
    }

    $result->free();
}



function getSignatureByDomainUrl($conn, $domain_url) {
    // Prepare the SQL statement
    $sql = "SELECT `signature` FROM `providers_partners` WHERE `providers_domain_url` = ?";
    $stmt = $conn->prepare($sql);

    
    // Bind the `domain_url` parameter to the statement
    $stmt->bind_param("s", $domain_url);
    
    // Execute the statement
    $stmt->execute();
    
    // Bind the result to a variable
    $stmt->bind_result($signature);
    
    // Fetch the result
    if ($stmt->fetch()) {
        // Return the `signature`
       debug_text('getSignatureByDomainUrl_12.txt',$signature);
     
        return $signature;
    } else {
        // Return null if no result found
        return null;
    }
    
    // Close the statement
    $stmt->close();
}

function get_providers_name($conn, $id) {
    // Prepare the SQL statement
    $stmt = $conn->prepare("SELECT 
        `providers_name` FROM `providers` WHERE `id` = ?");
    
    // Bind the `id` parameter to the statement
    $stmt->bind_param("i", $id);
    
    // Execute the statement
    $stmt->execute();
    
    // Bind the result to a variable
    $stmt->bind_result($providers_name);
    
    // Fetch the result
    if ($stmt->fetch()) {
        // Return the `secret_key`
        return $providers_name;
    } else {
        // Return null if no result found
        return null;
    }
    
    // Close the statement
    $stmt->close();
}

function get_providers_domain_url($conn, $id) {
    // Prepare the SQL statement
    $stmt = $conn->prepare("SELECT 
        `providers_domain_url` FROM `providers` WHERE `id` = ?");
    
    // Bind the `id` parameter to the statement
    $stmt->bind_param("i", $id);
    
    // Execute the statement
    $stmt->execute();
    
    // Bind the result to a variable
    $stmt->bind_result($providers_domain_url);
    
    // Fetch the result
    if ($stmt->fetch()) {
        // Return the `secret_key`
        return $providers_domain_url;
    } else {
        // Return null if no result found
        return null;
    }
    
    // Close the statement
    $stmt->close();
}

function get_providers_domain_url_json($json_file_path, $id) {
    // Check if the JSON file exists
    if (!file_exists($json_file_path)) {
        die("JSON file not found.");
    }

    // Read the contents of the JSON file
    $json_content = file_get_contents($json_file_path);

    // Decode the JSON data into a PHP array
    $providers_data = json_decode($json_content, true);

    // Check if decoding was successful
    if ($providers_data === null) {
        die("Failed to decode JSON.");
    }

    // Loop through the providers data to find the matching `id`
    foreach ($providers_data as $provider) {
        if ($provider['id'] == $id) {
            // Return the `providers_domain_url` for the matching `id`
            return $provider['providers_domain_url'];
        }
    }

    // Return null if no provider with the given `id` is found
    return null;
}




function getSecretKeyById($conn, $id) {
    // Prepare the SQL statement
    $stmt = $conn->prepare("SELECT `secret_key` FROM `providers` WHERE `id` = ?");
    
    // Bind the `id` parameter to the statement
    $stmt->bind_param("i", $id);
    
    // Execute the statement
    $stmt->execute();
    
    // Bind the result to a variable
    $stmt->bind_result($secret_key);
    
    // Fetch the result
    if ($stmt->fetch()) {
        // Return the `secret_key`
        return $secret_key;
    } else {
        // Return null if no result found
        return null;
    }
    
    // Close the statement
    $stmt->close();
}



function getProvidersCodeById($conn, $id) {
    // Prepare the SQL statement
    $stmt = $conn->prepare("SELECT `providers_code` FROM `providers` WHERE `id` = ?");
    
    // Bind the `id` parameter to the statement
    $stmt->bind_param("i", $id);
    
    // Execute the statement
    $stmt->execute();
    
    // Bind the result to a variable
    $stmt->bind_result($providers_code);
    
    // Fetch the result
    if ($stmt->fetch()) {
        // Return the `providers_code`
        return $providers_code;
    } else {
        // Return null if no result found
        return null;
    }
    
    // Close the statement
    $stmt->close();
}


function debug_text($namafile,$contentdebug) {
  $myfile = fopen($namafile, "w") or die("Unable to open file!");
   fwrite($myfile, $contentdebug);
   fclose($myfile);
}

?>
