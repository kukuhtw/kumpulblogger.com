<?php
/*
function_admin.php
*/



// Fungsi untuk ambil user pemilik iklan
function getuser($mysqli, $user_id) {
   $query = "SELECT `loginemail` FROM  msusers WHERE id = ?";
    $stmt = $mysqli->prepare($query);
    $stmt->bind_param("i", $user_id);
    $stmt->execute();
    $result = $stmt->get_result();
    $row = $result->fetch_assoc();

    return $row['loginemail'];
}


// Fungsi untuk menghitung jumlah total iklan yang dimiliki oleh user
function countTotalAds($mysqli, $user_id) {
   $query = "SELECT COUNT(*) AS total_ads FROM advertisers_ads WHERE advertisers_id = ?";
    $stmt = $mysqli->prepare($query);
    $stmt->bind_param("i", $user_id);
    $stmt->execute();
    $result = $stmt->get_result();
    $row = $result->fetch_assoc();

    return $row['total_ads'];
}

// Fungsi untuk menghitung total pengeluaran lokal dan memperbarui tabel msusers
function updateLocalSpending($mysqli,$user_id) {
     $query = "SELECT SUM(current_spending) AS total_spending FROM advertisers_ads WHERE advertisers_id = ?";
    $stmt = $mysqli->prepare($query);
    $stmt->bind_param("i", $user_id);
    $stmt->execute();
    $result = $stmt->get_result();
    $row = $result->fetch_assoc();

    $total_spending = $row['total_spending'] ?: 0.00;
    $now = gmdate('Y-m-d H:i:s', time() + 7 * 3600);

    $update_query = "UPDATE msusers SET current_spending = ?, last_updated_spending = ? WHERE id = ?";
    $stmt_update = $mysqli->prepare($update_query);
    $stmt_update->bind_param("dsi", $total_spending, $now, $user_id);
    $stmt_update->execute();
     return $total_spending;
}

// Fungsi untuk menghitung total pengeluaran dari partner global dan memperbarui tabel msusers
function updateGlobalSpending($mysqli, $user_id) {
    $query = "SELECT SUM(current_spending_from_partner) AS total_spending_partner FROM advertisers_ads WHERE advertisers_id = ?";
    $stmt = $mysqli->prepare($query);
    $stmt->bind_param("i", $user_id);
    $stmt->execute();
    $result = $stmt->get_result();
    $row = $result->fetch_assoc();

    $total_spending_partner = $row['total_spending_partner'] ?: 0.00;
    $now = gmdate('Y-m-d H:i:s', time() + 7 * 3600);

    $update_query = "UPDATE msusers SET current_spending_from_partner = ?, last_updated_spending = ? WHERE id = ?";
    $stmt_update = $mysqli->prepare($update_query);
    $stmt_update->bind_param("dsi", $total_spending_partner, $now, $user_id);
    $stmt_update->execute();
    return $total_spending_partner;
}


// Fungsi untuk menghitung jumlah total website yang dimiliki oleh user
function countTotalWebsites($mysqli,$user_id) {
   $query = "SELECT COUNT(*) AS total_sites FROM publishers_site WHERE publishers_local_id = ?";
    $stmt = $mysqli->prepare($query);
    $stmt->bind_param("i", $user_id);
    $stmt->execute();
    $result = $stmt->get_result();
    $row = $result->fetch_assoc();
 return $row['total_sites'];
}

// Fungsi untuk menghitung total revenue lokal dan memperbarui tabel msusers
function updateLocalRevenue($mysqli,$user_id) {
    
    $query = "SELECT SUM(current_site_revenue) AS total_revenue FROM publishers_site WHERE publishers_local_id = ?";
    $stmt = $mysqli->prepare($query);
    $stmt->bind_param("i", $user_id);
    $stmt->execute();
    $result = $stmt->get_result();
    $row = $result->fetch_assoc();

    $total_revenue = $row['total_revenue'] ?: 0.00;
    $now = gmdate('Y-m-d H:i:s', time() + 7 * 3600);

    $update_query = "UPDATE msusers SET current_revenue = ?, last_updated_revenue = ? WHERE id = ?";
    $stmt_update = $mysqli->prepare($update_query);
    $stmt_update->bind_param("dsi", $total_revenue, $now, $user_id);
    $stmt_update->execute();
    return $total_revenue;
}

// Fungsi untuk menghitung total revenue dari partner global dan memperbarui tabel msusers
function updateGlobalRevenue($mysqli,$user_id) {
      $query = "SELECT SUM(current_site_revenue_from_partner) AS total_revenue FROM publishers_site WHERE publishers_local_id = ?";
    $stmt = $mysqli->prepare($query);
    $stmt->bind_param("i", $user_id);
    $stmt->execute();
    $result = $stmt->get_result();
    $row = $result->fetch_assoc();

    $total_revenue = $row['total_revenue'] ?: 0.00;
    $now = gmdate('Y-m-d H:i:s', time() + 7 * 3600);

    $update_query = "UPDATE msusers SET current_revenue_from_partner = ?, last_updated_revenue = ? WHERE id = ?";
    $stmt_update = $mysqli->prepare($update_query);
    $stmt_update->bind_param("dsi", $total_revenue, $now, $user_id);
    $stmt_update->execute();
    updateTotalRevenue($mysqli, $user_id);

    return $total_revenue;
}

// Function to update the total revenue (local + global)
function updateTotalRevenue($mysqli, $user_id) {
    // Query to get the local and global revenues
    $query = "SELECT current_revenue, current_revenue_from_partner FROM msusers WHERE id = ?";
    $stmt = $mysqli->prepare($query);
    $stmt->bind_param("i", $user_id);
    $stmt->execute();
    $result = $stmt->get_result();
    $row = $result->fetch_assoc();

    // Calculate total current revenue
    $total_current_revenue = ($row['current_revenue'] ?: 0.00) + ($row['current_revenue_from_partner'] ?: 0.00);

    // Update the total current revenue in the msusers table
    $update_query = "UPDATE msusers SET total_current_revenue = ? WHERE id = ?";
    $stmt_update = $mysqli->prepare($update_query);
    $stmt_update->bind_param("di", $total_current_revenue, $user_id);
    $stmt_update->execute();

    return $total_current_revenue;
}

function updateCurrentClick_local($mysqli, $id, $providersDomainUrl) {
    // First, calculate the total `jumlah_klik` from the `rekap_harian` table where `local_ads_id` and `ads_providers_domain_url` match
    $queryTotalClicks = "
        SELECT SUM(jumlah_klik) AS total_clicks
        FROM rekap_harian
        WHERE local_ads_id = ? 
        AND ads_providers_domain_url = ?";
    
    if ($stmt = $mysqli->prepare($queryTotalClicks)) {
        $stmt->bind_param("is", $id, $providersDomainUrl);
        $stmt->execute();
        $stmt->bind_result($totalClicks);
        $stmt->fetch();
        $stmt->close();
    } else {
        // Handle query preparation error
        echo "Error preparing the query: " . $mysqli->error;
        return false;
    }

    // Next, update the `current_click` in the `advertisers_ads` table
    $updateQuery = "
        UPDATE advertisers_ads
        SET current_click = ?
        WHERE id = ? 
        AND providers_domain_url = ?";
    
    if ($stmt = $mysqli->prepare($updateQuery)) {
        $stmt->bind_param("iis", $totalClicks, $id, $providersDomainUrl);
        if ($stmt->execute()) {
            //echo "Successfully updated current_click for advertisers_ads id:" . $id. ", totalClicks:".$totalClicks;
        } else {
            // Handle execution error
            echo "Error executing the update: " . $stmt->error;
        }
        $stmt->close();
    } else {
        // Handle query preparation error
        echo "Error preparing the update query: " . $mysqli->error;
        return false;
    }

    return true;
}


?>
