<?php

// clicks_ads_local_detail.php

// Database connection
include("db.php");
include("function.php");
session_start();

if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit;
}
$user_id = (int) $_SESSION['user_id'];

// Create a connection to the MySQL database
$mysqli = new mysqli($servername_db, $username_db, $password_db, $dbname_db);

// Check the connection
if ($mysqli->connect_error) {
    die("Connection failed: " . $mysqli->connect_error);
}

$id = 1;
//$this_providers_domain_url = get_providers_domain_url($mysqli, $id);

$this_providers_domain_url = get_providers_domain_url_json("providers_data.json", 1);

// Get parameters from GET request
$local_ads_id = isset($_GET['local_ads_id']) ? intval($_GET['local_ads_id']) : 0;
$click_time = isset($_GET['click_time']) ? $_GET['click_time'] : '';
$ads_providers_domain_url = isset($_GET['ads_providers_domain_url']) ? $_GET['ads_providers_domain_url'] : '';

$paging_link = "&click_time=" . urlencode($click_time) . "&ads_providers_domain_url=" . urlencode($ads_providers_domain_url);

$page = max(1, isset($_GET['page']) ? intval($_GET['page']) : 1);
$records_per_page = 10;  // Define how many records you want per page
$offset = ($page - 1) * $records_per_page;  // Calculate the OFFSET for pagination

if ($ads_providers_domain_url == $this_providers_domain_url) {
    $table_advertisers_ads = "advertisers_ads";
} else {
    $table_advertisers_ads = "advertisers_ads_partners";
}

// Prepare SQL to get data from advertisers_ads
$sql_ads = "SELECT title_ads, description_ads, landingpage_ads, image_url 
            FROM $table_advertisers_ads
            WHERE local_ads_id = ? 
            AND providers_domain_url = ?
            AND advertisers_id = ?";

$stmt_ads = $mysqli->prepare($sql_ads);
if ($stmt_ads === false) {
    die("Prepare failed: " . $mysqli->error);
}

$stmt_ads->bind_param("isi", $local_ads_id, $ads_providers_domain_url, $user_id);

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
    http_response_code(404);
    exit('Iklan tidak ditemukan atau bukan milik Anda.');
}

// Prepare the dynamic SQL query
$sql = "SELECT pub_id, pub_provider, ip_address, browser_agent, referrer, click_time, local_ads_id, ads_providers_name, landingpage_ads, revenue_publishers, revenue_adnetwork_local, revenue_adnetwork_partner, click_time, site_name, site_domain 
        FROM ad_clicks   
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

$sql .= " ORDER BY click_time DESC LIMIT ?, ?";
$params[] = $offset;
$params[] = $records_per_page;
$types .= "ii";

// Prepare the SQL statement
$stmt = $mysqli->prepare($sql);
if ($stmt === false) {
    die("Prepare failed: " . $mysqli->error);
}

// Bind parameters dynamically
$stmt->bind_param($types, ...$params);

// Execute the query
$stmt->execute();
$result = $stmt->get_result();

// Fetch the total number of records for pagination
$total_sql = "SELECT COUNT(*) AS total_records FROM ad_clicks WHERE local_ads_id = ? AND isaudit = 1 AND is_reject = 0";
$total_params = [$local_ads_id];
$total_types = "i";
if (!empty($click_time) && validateDate($click_time)) {
    $total_sql .= " AND date(click_time) = ?";
    $total_params[] = $click_time;
    $total_types .= "s";
}
if (!empty($ads_providers_domain_url)) {
    $total_sql .= " AND ads_providers_domain_url = ?";
    $total_params[] = $ads_providers_domain_url;
    $total_types .= "s";
}
$total_stmt = $mysqli->prepare($total_sql);
$total_stmt->bind_param($total_types, ...$total_params);
$total_stmt->execute();
$total_result = $total_stmt->get_result();
$total_row = $total_result->fetch_assoc();
$total_records = $total_row['total_records'];
$total_pages = max(1, (int) ceil($total_records / $records_per_page));
$window_start = max(1, $page - 2);
$window_end = min($total_pages, $page + 2);

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
    <style>
        body { background: #f5f7fa; color: #26313c; }
        .page-shell { max-width: 1240px; }
        .page-heading h1 { margin: 0; font-size: 1.65rem; font-weight: 750; }
        .page-heading p { margin: .3rem 0 0; color: #6c757d; }
        .ad-summary { overflow: hidden; border: 0; border-radius: .9rem; box-shadow: 0 3px 14px rgba(31,41,55,.08); }
        .ad-image { width: 100%; height: 100%; min-height: 230px; object-fit: cover; background: #e9ecef; }
        .report-card { overflow: hidden; border: 0; border-radius: .9rem; box-shadow: 0 3px 14px rgba(31,41,55,.08); }
        .click-table { margin: 0; }
        .click-table thead th { white-space: nowrap; color: #59636e; font-size: .72rem; text-transform: uppercase; }
        .click-table td { vertical-align: top; }
        .visitor-cell { min-width: 230px; }
        .small-detail { display: block; margin-top: .25rem; color: #6c757d; font-size: .75rem; overflow-wrap: anywhere; }
        .page-total { padding: 1rem; border-radius: .75rem; background: #eaf8f0; color: #12683a; }
        .click-pagination { flex-wrap: wrap; gap: .25rem; }
        .click-pagination .page-link { min-width: 38px; border-radius: .45rem !important; text-align: center; }
        @media (max-width: 767.98px) {
            .ad-image { height: 220px; min-height: 0; }
            .click-table-responsive { overflow: visible; }
            .click-table, .click-table tbody, .click-table tr, .click-table td { display: block; width: 100%; }
            .click-table thead { display: none; }
            .click-table tbody { display: grid; gap: 1rem; }
            .click-table tbody tr { overflow: hidden; border: 1px solid #e1e6eb; border-radius: .75rem; background: #fff; }
            .click-table tbody td { min-width: 0; padding: .75rem 1rem; border: 0; border-bottom: 1px solid #edf0f2; }
            .click-table tbody td:last-child { border-bottom: 0; }
            .click-table tbody td::before { content: attr(data-label); display: block; margin-bottom: .3rem; color: #6c757d; font-size: .68rem; font-weight: 700; text-transform: uppercase; }
        }
        @media (max-width: 575.98px) { .page-shell { padding-right: .75rem; padding-left: .75rem; } .page-heading h1 { font-size: 1.4rem; } }
    </style>
</head>
<body>

<div class="container page-shell py-3 py-md-4">
     <?php include("main_menu.php") ?>
        <?php include("include_advertiser_menu.php") ?>

    <div class="page-heading mb-4"><h1>Laporan Klik Iklan Lokal</h1><p>Detail klik valid dan biaya transaksi untuk iklan #<?php echo htmlspecialchars($local_ads_id); ?>.</p></div>

    <div class="card ad-summary mb-4"><div class="row g-0">
        <div class="col-12 col-md-4"><img src="<?php echo htmlspecialchars($image_url, ENT_QUOTES); ?>" alt="Gambar iklan" class="ad-image"></div>
        <div class="col-12 col-md-8"><div class="card-body p-4 d-flex flex-column h-100">
            <h2 class="h4 fw-bold"><?php echo htmlspecialchars($title_ads); ?></h2>
            <p class="text-muted"><?php echo htmlspecialchars($description_ads); ?></p>
            <a class="btn btn-outline-primary align-self-start mt-auto" href="<?php echo htmlspecialchars($landingpage_ads, ENT_QUOTES); ?>" target="_blank" rel="noopener noreferrer"><i class="fas fa-external-link-alt me-1"></i> Buka Landing Page</a>
        </div></div>
    </div></div>

    <div class="d-flex flex-column flex-sm-row justify-content-between align-items-sm-center gap-2 mb-3"><div><h2 class="h4 mb-0">Data Klik</h2><span class="text-muted small"><?php echo number_format($total_records); ?> transaksi ditemukan</span></div><?php if ($click_time): ?><span class="badge text-bg-primary">Tanggal: <?php echo htmlspecialchars($click_time); ?></span><?php endif; ?></div>

    <?php if ($result->num_rows > 0): ?>
   <div class="card report-card"><div class="table-responsive click-table-responsive">
    <table class="table table-hover click-table">
        <thead>
            <tr>
                <th>Publisher</th>
                <th>Pengunjung</th>
                <th>Click Time</th>
                <th>Referrer</th>
                <th>Revenue Publisher</th>
                <th>Total Spending</th>
            </tr>
        </thead>
        <tbody>
            <?php
            $grand_total_spending = 0;
            while ($row = $result->fetch_assoc()):
                $total_spending = $row['revenue_publishers'] + $row['revenue_adnetwork_local'] + $row['revenue_adnetwork_partner'];
                $grand_total_spending += $total_spending;
            ?>
            <tr>
                <td data-label="Publisher"><strong>#<?php echo htmlspecialchars($row['pub_id']); ?></strong><span class="small-detail"><?php echo htmlspecialchars($row['site_name']); ?></span><span class="small-detail"><?php echo htmlspecialchars($row['site_domain']); ?></span></td>
                <td class="visitor-cell" data-label="Pengunjung"><strong><?php echo htmlspecialchars($row['ip_address']); ?></strong><span class="small-detail"><?php echo htmlspecialchars($row['browser_agent']); ?></span></td>
                <td class="text-nowrap" data-label="Waktu Klik"><?php echo htmlspecialchars($row['click_time']); ?></td>
                <td data-label="Referrer"><span class="small-detail"><?php echo htmlspecialchars($row['referrer'] ?: '-'); ?></span></td>
                <td class="text-nowrap" data-label="Revenue Publisher">Rp <?php echo number_format((float) $row['revenue_publishers'], 2, ',', '.'); ?></td>
                <td class="text-nowrap" data-label="Total Spending"><strong>Rp <?php echo number_format((float) $total_spending, 2, ',', '.'); ?></strong></td>
            </tr>
            <?php endwhile; ?>
        </tbody>
    </table>
   </div></div>

   

     <!-- Pagination -->
    <nav aria-label="Page navigation">
        <ul class="pagination click-pagination justify-content-center mt-4">
            <li class="page-item <?php echo $page <= 1 ? 'disabled' : ''; ?>"><a class="page-link" href="?local_ads_id=<?php echo $local_ads_id; ?>&amp;page=<?php echo max(1, $page - 1); ?><?php echo $paging_link; ?>">&laquo;</a></li>
            <?php if ($window_start > 1): ?>
                <li class="page-item"><a class="page-link" href="?local_ads_id=<?php echo $local_ads_id; ?>&amp;page=1<?php echo $paging_link; ?>">1</a></li>
                <?php if ($window_start > 2): ?><li class="page-item disabled"><span class="page-link">&hellip;</span></li><?php endif; ?>
            <?php endif; ?>
            <?php for ($i = $window_start; $i <= $window_end; $i++): ?>
                <li class="page-item <?php echo $i === $page ? 'active' : ''; ?>"><a class="page-link" href="?local_ads_id=<?php echo $local_ads_id; ?>&amp;page=<?php echo $i; ?><?php echo $paging_link; ?>"><?php echo $i; ?></a></li>
            <?php endfor; ?>
            <?php if ($window_end < $total_pages): ?>
                <?php if ($window_end < $total_pages - 1): ?><li class="page-item disabled"><span class="page-link">&hellip;</span></li><?php endif; ?>
                <li class="page-item"><a class="page-link" href="?local_ads_id=<?php echo $local_ads_id; ?>&amp;page=<?php echo $total_pages; ?><?php echo $paging_link; ?>"><?php echo $total_pages; ?></a></li>
            <?php endif; ?>
            <li class="page-item <?php echo $page >= $total_pages ? 'disabled' : ''; ?>"><a class="page-link" href="?local_ads_id=<?php echo $local_ads_id; ?>&amp;page=<?php echo min($total_pages, $page + 1); ?><?php echo $paging_link; ?>">&raquo;</a></li>
        </ul>
    </nav>

  

    <div class="page-total"><span class="small fw-bold text-uppercase">Total spending pada halaman ini</span><div class="fs-4 fw-bold">Rp <?php echo number_format((float) $grand_total_spending, 2, ',', '.'); ?></div></div>

    <?php else: ?>
        <p>No records found.</p>
    <?php endif; ?>
</div>

</body>
</html>

<?php
// Close connections
$stmt->close();
$stmt_ads->close();
$total_stmt->close();
$mysqli->close();
?>
