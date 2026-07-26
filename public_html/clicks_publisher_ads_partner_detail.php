<?php

// clicks_publisher_ads_partner_detail.php

// Database connection
include("db.php"); // Koneksi database
include("function.php");
session_start();

// Check if the user is logged in
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

// Get user ID from session
$user_id = $_SESSION['user_id'];

// Create a connection to the MySQL database
$mysqli = new mysqli($servername_db, $username_db, $password_db, $dbname_db);

// Check the connection
if ($mysqli->connect_error) {
    die("Connection failed: " . $mysqli->connect_error);
}

// Get `pubs_providers_domain_url` from the GET request
$pubs_providers_domain_url = isset($_GET['pubs_providers_domain_url']) ? $_GET['pubs_providers_domain_url'] : '';


$local = isset($_GET['local']) && (int) $_GET['local'] === 1 ? 1 : 0;

if ($local==0) {
    $filter = " AND ac.ads_providers_domain_url != ac.pubs_providers_domain_url ";
}
else {
    $filter = " AND ac.ads_providers_domain_url = ac.pubs_providers_domain_url ";
}


if (empty($pubs_providers_domain_url)) {
    die("Invalid parameter: pubs_providers_domain_url.");
}

// Pagination settings
$limit = 10; // Number of records per page
$page = max(1, isset($_GET['page']) ? intval($_GET['page']) : 1);
$offset = ($page - 1) * $limit;

// Prepare the dynamic SQL query
$sql = "
    SELECT ac.pub_id, ac.pub_provider, ac.ip_address, ac.browser_agent, ac.referrer, ac.click_time, 
           ac.local_ads_id, ac.ads_providers_name, ac.title_ads, ac.landingpage_ads, ac.revenue_publishers, 
           ps.site_name, ps.site_domain 
    FROM ad_clicks ac
    JOIN publishers_site ps ON ac.pub_id = ps.id
    JOIN msusers mu ON ps.publishers_local_id = mu.id
    WHERE mu.id = ? 
    AND ac.pubs_providers_domain_url = ? 
    
    ".$filter."

    AND ac.isaudit = 1 
    AND ac.is_reject = 0
    ORDER BY ac.click_time DESC 
    LIMIT ?, ?
";

// Prepare the SQL statement
$stmt = $mysqli->prepare($sql);

// Bind the parameters (user_id, pubs_providers_domain_url, offset, limit)
$stmt->bind_param("isii", $user_id, $pubs_providers_domain_url, $offset, $limit);

// Execute the query
$stmt->execute();

// Get the result
$result = $stmt->get_result();

// Prepare the query to calculate the total revenue for the current user
$total_revenue_sql = "
    SELECT SUM(ac.revenue_publishers) as total_revenue
    FROM ad_clicks ac
    JOIN publishers_site ps ON ac.pub_id = ps.id
    JOIN msusers mu ON ps.publishers_local_id = mu.id
    WHERE mu.id = ?
    AND ac.pubs_providers_domain_url = ?
     ".$filter."

    AND ac.isaudit = 1 
    AND ac.is_reject = 0
";

// Prepare the SQL statement for total revenue
$total_revenue_stmt = $mysqli->prepare($total_revenue_sql);
$total_revenue_stmt->bind_param("is", $user_id, $pubs_providers_domain_url);
$total_revenue_stmt->execute();
$total_revenue_result = $total_revenue_stmt->get_result();
$total_revenue_row = $total_revenue_result->fetch_assoc();
$total_revenue = $total_revenue_row['total_revenue'];


?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Click Publisher Detail</title>
    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        body { background: #f5f7fa; color: #27313b; }
        .page-shell { max-width: 1320px; }
        .page-heading h1 { margin: 0; font-size: 1.6rem; font-weight: 700; }
        .page-heading p { margin: .3rem 0 0; color: #6c757d; overflow-wrap: anywhere; }
        .summary-card { border: 0; border-radius: .8rem; box-shadow: 0 3px 14px rgba(31,41,55,.07); }
        .summary-label { color: #6c757d; font-size: .75rem; font-weight: 700; text-transform: uppercase; }
        .click-table-card { overflow: hidden; border: 0; border-radius: .9rem; box-shadow: 0 3px 14px rgba(31,41,55,.07); }
        .click-table { margin: 0; }
        .click-table thead th { white-space: nowrap; color: #59636e; font-size: .75rem; text-transform: uppercase; }
        .click-table td { vertical-align: top; }
        .visitor-info, .ad-info, .site-info { min-width: 190px; }
        .small-detail { display: block; margin-top: .25rem; color: #6c757d; font-size: .78rem; overflow-wrap: anywhere; }
        .click-pagination { flex-wrap: wrap; gap: .25rem; }
        .click-pagination .page-link { min-width: 38px; border-radius: .4rem !important; text-align: center; }
        @media (max-width: 767.98px) {
            .click-table-responsive { overflow: visible; }
            .click-table, .click-table tbody, .click-table tr, .click-table td { display: block; width: 100%; }
            .click-table thead { display: none; }
            .click-table tbody { display: grid; gap: 1rem; }
            .click-table tbody tr { overflow: hidden; border: 1px solid #e1e6eb; border-radius: .75rem; background: #fff; }
            .click-table tbody td { min-width: 0; padding: .75rem 1rem; border: 0; border-bottom: 1px solid #edf0f2; }
            .click-table tbody td:last-child { border-bottom: 0; }
            .click-table tbody td::before { content: attr(data-label); display: block; margin-bottom: .3rem; color: #6c757d; font-size: .68rem; font-weight: 700; text-transform: uppercase; }
        }
    </style>
</head>
<body>
<div class="container page-shell py-3 py-md-4">
    <?php include("main_menu.php"); ?>
    <?php include("include_publisher_menu.php"); ?>
    <div class="page-heading mb-4">
        <h1>Detail Klik Publisher</h1>
        <p><?php echo $local ? 'Klik dari iklan lokal' : 'Klik dari iklan partner'; ?> &middot; <?php echo htmlspecialchars($pubs_providers_domain_url); ?></p>
    </div>
    <div class="row g-3 mb-4">
        <div class="col-12 col-sm-6">
            <div class="card summary-card h-100"><div class="card-body">
                <span class="summary-label">Total Pendapatan</span>
                <div class="fs-4 fw-bold text-success">Rp <?php echo number_format((float) $total_revenue, 2, ',', '.'); ?></div>
            </div></div>
        </div>
        <div class="col-12 col-sm-6">
            <div class="card summary-card h-100"><div class="card-body">
                <span class="summary-label">Tipe Trafik</span>
                <div class="fs-5 fw-semibold"><?php echo $local ? 'Lokal' : 'Partner'; ?></div>
            </div></div>
        </div>
    </div>
    <?php if ($result->num_rows > 0) : ?>
      <div class="card click-table-card">
       <div class="table-responsive click-table-responsive">
        <table class="table table-hover click-table">
            <thead class="table-light">
                <tr>
                    <th>Publisher & Site</th>
                    <th>Pengunjung</th>
                    <th>Iklan</th>
                    <th>Waktu Klik</th>
                    <th>Pendapatan</th>
                    <th>Aksi</th>
                </tr>
            </thead>
            <tbody>
            <?php while ($row = $result->fetch_assoc()) : ?>
                <tr>
                    <td class="site-info" data-label="Publisher & Site"><strong><?php echo htmlspecialchars($row['site_name']); ?></strong><span class="small-detail">Publisher #<?php echo htmlspecialchars($row['pub_id']); ?> &middot; <?php echo htmlspecialchars($row['pub_provider']); ?></span><span class="small-detail"><?php echo htmlspecialchars($row['site_domain']); ?></span></td>
                    <td class="visitor-info" data-label="Pengunjung"><strong><?php echo htmlspecialchars($row['ip_address']); ?></strong><span class="small-detail"><?php echo htmlspecialchars($row['browser_agent']); ?></span><span class="small-detail">Referrer: <?php echo htmlspecialchars($row['referrer'] ?: '-'); ?></span></td>
                    <td class="ad-info" data-label="Iklan"><strong><?php echo htmlspecialchars($row['title_ads']); ?></strong><span class="small-detail">Ad #<?php echo htmlspecialchars($row['local_ads_id']); ?> &middot; <?php echo htmlspecialchars($row['ads_providers_name']); ?></span></td>
                    <td class="text-nowrap" data-label="Waktu Klik"><?php echo htmlspecialchars($row['click_time']); ?></td>
                    <td class="text-nowrap" data-label="Pendapatan"><strong>Rp <?php echo number_format((float) $row['revenue_publishers'], 2, ',', '.'); ?></strong></td>
                    <td data-label="Aksi"><a class="btn btn-sm btn-outline-primary" href="<?php echo htmlspecialchars($row['landingpage_ads']); ?>" target="_blank" rel="noopener noreferrer"><i class="fas fa-external-link-alt me-1"></i> Landing Page</a></td>
                </tr>
            <?php endwhile; ?>
            </tbody>
        </table>
       </div>
      </div>

    <?php else : ?>
        <div class="alert alert-warning">No records found.</div>
    <?php endif; ?>

    <?php
    // Prepare the count query to calculate total pages
    $count_sql = "
        SELECT COUNT(*) as total 
        FROM ad_clicks ac
        JOIN publishers_site ps ON ac.pub_id = ps.id
        JOIN msusers mu ON ps.publishers_local_id = mu.id
        WHERE mu.id = ? 
        AND ac.pubs_providers_domain_url = ?
        ".$filter."
        AND ac.isaudit = 1 
        AND ac.is_reject = 0
    ";

    $count_stmt = $mysqli->prepare($count_sql);
    $count_stmt->bind_param("is", $user_id, $pubs_providers_domain_url);
    $count_stmt->execute();
    $count_result = $count_stmt->get_result();
    $count_row = $count_result->fetch_assoc();
    $total_records = $count_row['total'];

    // Calculate total pages
    $total_pages = max(1, (int) ceil($total_records / $limit));
    $window_start = max(1, $page - 2);
    $window_end = min($total_pages, $page + 2);
    ?>

    <!-- Pagination Links -->
    <div class="text-center text-muted small mt-3">Halaman <?php echo $page; ?> dari <?php echo $total_pages; ?> &middot; <?php echo number_format($total_records); ?> klik</div>
    <nav class="mt-2" aria-label="Navigasi detail klik">
        <ul class="pagination click-pagination justify-content-center">
            <li class="page-item <?php echo $page <= 1 ? 'disabled' : ''; ?>">
                <a class="page-link" href="?pubs_providers_domain_url=<?php echo urlencode($pubs_providers_domain_url); ?>&amp;local=<?php echo $local; ?>&amp;page=<?php echo max(1, $page - 1); ?>" aria-label="Sebelumnya">&laquo;</a>
            </li>
            <?php if ($window_start > 1): ?>
                <li class="page-item"><a class="page-link" href="?pubs_providers_domain_url=<?php echo urlencode($pubs_providers_domain_url); ?>&amp;local=<?php echo $local; ?>&amp;page=1">1</a></li>
                <?php if ($window_start > 2): ?><li class="page-item disabled"><span class="page-link">&hellip;</span></li><?php endif; ?>
            <?php endif; ?>
            <?php for ($i = $window_start; $i <= $window_end; $i++): ?>
                <li class="page-item <?php echo $i === $page ? 'active' : ''; ?>">
                    <a class="page-link" href="?pubs_providers_domain_url=<?php echo urlencode($pubs_providers_domain_url); ?>&amp;local=<?php echo $local; ?>&amp;page=<?php echo $i; ?>"><?php echo $i; ?></a>
                </li>
            <?php endfor; ?>
            <?php if ($window_end < $total_pages): ?>
                <?php if ($window_end < $total_pages - 1): ?><li class="page-item disabled"><span class="page-link">&hellip;</span></li><?php endif; ?>
                <li class="page-item"><a class="page-link" href="?pubs_providers_domain_url=<?php echo urlencode($pubs_providers_domain_url); ?>&amp;local=<?php echo $local; ?>&amp;page=<?php echo $total_pages; ?>"><?php echo $total_pages; ?></a></li>
            <?php endif; ?>
            <li class="page-item <?php echo $page >= $total_pages ? 'disabled' : ''; ?>">
                <a class="page-link" href="?pubs_providers_domain_url=<?php echo urlencode($pubs_providers_domain_url); ?>&amp;local=<?php echo $local; ?>&amp;page=<?php echo min($total_pages, $page + 1); ?>" aria-label="Berikutnya">&raquo;</a>
            </li>
        </ul>
    </nav>
</div>

<!-- Bootstrap JS -->
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>

<?php
// Close the statement and connection
$stmt->close();
$total_revenue_stmt->close();
$count_stmt->close();
$mysqli->close();
?>
