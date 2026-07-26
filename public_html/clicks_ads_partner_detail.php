<?php

// clicks_ads_partner_detail.php

// Database connection
include("db.php");
include("function.php");

// Create a connection to the MySQL database
$mysqli = new mysqli($servername_db, $username_db, $password_db, $dbname_db);

// Check the connection
if ($mysqli->connect_error) {
    die("Connection failed: " . $mysqli->connect_error);
}

// Get parameters from GET request
$local_ads_id = isset($_GET['local_ads_id']) ? intval($_GET['local_ads_id']) : 0;
$click_time = isset($_GET['click_time']) ? $_GET['click_time'] : '';
$ads_providers_domain_url = isset($_GET['ads_providers_domain_url']) ? $_GET['ads_providers_domain_url'] : '';


$paging_link = "&click_time=".$click_time."&ads_providers_domain_url=".$ads_providers_domain_url;


$id = 1;
//$this_providers_domain_url = get_providers_domain_url($mysqli, $id);


$this_providers_domain_url = get_providers_domain_url_json("providers_data.json", 1);


if ($ads_providers_domain_url == $this_providers_domain_url) {
    $table_advertisers_ads = "advertisers_ads";
} else {
    $table_advertisers_ads = "advertisers_ads_partners";
}

// Prepare SQL to get data from advertisers_ads
$sql_ads = "SELECT title_ads, description_ads, landingpage_ads, image_url 
            FROM $table_advertisers_ads
            WHERE local_ads_id = ? 
            AND providers_domain_url = ?";

$stmt_ads = $mysqli->prepare($sql_ads);
if ($stmt_ads === false) {
    die("Prepare failed: " . $mysqli->error);
}

$stmt_ads->bind_param("is", $local_ads_id, $ads_providers_domain_url);

// Execute the query
$stmt_ads->execute();
$result_ads = $stmt_ads->get_result();

// Fetch the data
$ads_data = $result_ads->fetch_assoc();

if ($ads_data) {
    $title_ads = $ads_data['title_ads'];
    $description_ads = $ads_data['description_ads'];
    $landingpage_ads = $ads_data['landingpage_ads'];
    $image_url = $ads_data['image_url'];
} else {
    $title_ads = "No data available";
    $description_ads = "No data available";
    $landingpage_ads = "No data available";
    $image_url = "No data available";
}

// Pagination logic
$page = isset($_GET['page']) ? intval($_GET['page']) : 1;  // Current page
$records_per_page = 10;  // Number of records per page
$offset = ($page - 1) * $records_per_page;  // Offset for SQL query

// Prepare the dynamic SQL query
$sql = "SELECT pub_id, pub_provider, ip_address, browser_agent, referrer, click_time, local_ads_id, ads_providers_name, landingpage_ads, revenue_publishers, revenue_adnetwork_local, revenue_adnetwork_partner, site_name, site_domain 
        FROM ad_clicks_partner
        WHERE local_ads_id = ? 
        AND isaudit = 1 
        AND is_reject = 0";

// Add params for filters
$params = [$local_ads_id];
$types = "i";

// Validate and add click_time if provided and valid
if (!empty($click_time) && validateDate($click_time)) {
    $sql .= " AND date(click_time) = ?";
    $params[] = $click_time;
    $types .= "s";
}

// Add ads_providers_domain_url if provided
if (!empty($ads_providers_domain_url)) {
    $sql .= " AND ads_providers_domain_url = ?";
    $params[] = $ads_providers_domain_url;
    $types .= "s";
}

$sql .= " ORDER BY click_time DESC LIMIT ? OFFSET ?";
$params[] = $records_per_page;
$params[] = $offset;
$types .= "ii";

$stmt = $mysqli->prepare($sql);
if ($stmt === false) {
    die("Prepare failed: " . $mysqli->error);
}

// Bind parameters dynamically
$stmt->bind_param($types, ...$params);

// Execute the query
$stmt->execute();
$result = $stmt->get_result();

// Fetch total records for pagination
$total_sql = "SELECT COUNT(*) AS total_records FROM ad_clicks_partner WHERE local_ads_id = ? AND isaudit = 1 AND is_reject = 0";
$total_stmt = $mysqli->prepare($total_sql);
$total_stmt->bind_param("i", $local_ads_id);
$total_stmt->execute();
$total_result = $total_stmt->get_result();
$total_row = $total_result->fetch_assoc();
$total_records = $total_row['total_records'];
$total_pages = ceil($total_records / $records_per_page);  // Calculate total pages

// Function to validate date in yyyy-mm-dd format
function validateDate($date) {
    $format = 'Y-m-d';
    $d = DateTime::createFromFormat($format, $date);
    return $d && $d->format($format) === $date;
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Ad Clicks Data</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body>

<div class="container">
    <?php include("main_menu.php") ?>
        <?php include("include_advertiser_menu.php") ?>

     <h1>Ad Details: Transaksi klik yang terjadi pada Server Partner di luar <?php echo $this_providers_domain_url ?></h1>

    <div class="ad-details">
        <h2><?php echo htmlspecialchars($title_ads); ?></h2>
        <p><?php echo htmlspecialchars($description_ads); ?></p>
        <p><strong>Landing Page:</strong> <a href="<?php echo htmlspecialchars($landingpage_ads); ?>" target="_blank"><?php echo htmlspecialchars($landingpage_ads); ?></a></p>
        <p><strong>Image:</strong></p>
        <?php if (!empty($image_url)): ?>
            <img src="<?php echo htmlspecialchars($image_url); ?>" alt="Ad Image" style="max-width: 100%;">
        <?php else: ?>
            <p>No image available</p>
        <?php endif; ?>
    </div>

    <h1>Ad Clicks Data</h1>

    <?php if ($result->num_rows > 0): ?>
    <table class="table table-striped">
        <thead>
            <tr>
                <th>Publisher ID</th>
                <th>Publisher Provider</th>
                <th>IP Address</th>
                <th>Browser Agent</th>
                <th>Referrer</th>
                <th>Click Time</th>
                <th>Local Ads ID</th>
                <th>Ads Providers Name</th>
                <th>Landing Page Ads</th>
                <th>Revenue Publishers</th>
                <th>Revenue Ad Network Local</th>
                <th>Revenue Ad Network Partner</th>
                <th>Total Spending</th>
            </tr>
        </thead>
        <tbody>
            <?php
            $grand_total_spending = 0;
            while ($row = $result->fetch_assoc()):
                $revenue_publishers = $row['revenue_publishers'];
                $spending_revenue_adnetwork_local = $row['revenue_adnetwork_local'];
                $spending_revenue_adnetwork_partner = $row['revenue_adnetwork_partner'];

                $total_spending = $revenue_publishers + $spending_revenue_adnetwork_local + $spending_revenue_adnetwork_partner;

                $grand_total_spending += $total_spending;
            ?>
            <tr>
                <td><?php echo htmlspecialchars($row['pub_id']); ?><br><?php echo htmlspecialchars($row['site_name']); ?></td>
                <td><?php echo htmlspecialchars($row['pub_provider']); ?></td>
                <td><?php echo htmlspecialchars($row['ip_address']); ?></td>
                <td><?php echo htmlspecialchars($row['browser_agent']); ?></td>
                <td><?php echo htmlspecialchars($row['referrer']); ?></td>
                <td><?php echo htmlspecialchars($row['click_time']); ?></td>
                <td><?php echo htmlspecialchars($row['local_ads_id']); ?></td>
                <td><?php echo htmlspecialchars($row['ads_providers_name']); ?></td>
                <td><?php echo htmlspecialchars($row['landingpage_ads']); ?></td>
                <td><?php echo htmlspecialchars($row['revenue_publishers']); ?></td>
                <td><?php echo htmlspecialchars($row['revenue_adnetwork_local']); ?></td>
                <td><?php echo htmlspecialchars($row['revenue_adnetwork_partner']); ?></td>
                <td><?php echo $total_spending; ?></td>
            </tr>
            <?php endwhile; ?>
        </tbody>
    </table>


     <!-- Pagination -->
    <nav aria-label="Page navigation">
        <ul class="pagination justify-content-center">
            <?php if ($page > 1): ?>
                <li class="page-item">
                    <a class="page-link" href="?local_ads_id=<?php echo $local_ads_id; ?>&page=<?php echo $page - 1; ?><?php echo $paging_link ?>" aria-label="Previous">
                        <span aria-hidden="true">&laquo;</span>
                    </a>
                </li>
            <?php endif; ?>
            <?php for ($i = 1; $i <= $total_pages; $i++): ?>
                <li class="page-item <?php if ($i == $page) echo 'active'; ?>">
                    <a class="page-link" href="?local_ads_id=<?php echo $local_ads_id; ?>&page=<?php echo $i; ?><?php echo $paging_link ?>"><?php echo $i; ?></a>
                </li>
            <?php endfor; ?>
            <?php if ($page < $total_pages): ?>
                <li class="page-item">
                    <a class="page-link" href="?local_ads_id=<?php echo $local_ads_id; ?>&page=<?php echo $page + 1; ?><?php echo $paging_link ?>" aria-label="Next">
                        <span aria-hidden="true">&raquo;</span>
                    </a>
                </li>
            <?php endif; ?>
        </ul>
    </nav>

  

    <p>Grand Total Spending: Rp <?php echo $grand_total_spending; ?></p>

    <?php else: ?>
        <p class="no-data">No records found.</p>
    <?php endif; ?>

</div>

<?php
// Close connection
$stmt->close();
$mysqli->close();
?>

</body>
</html>
