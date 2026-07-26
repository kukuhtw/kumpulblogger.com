<?php
include("db.php");
session_start();

// Database connection
$conn = new mysqli($servername_db, $username_db, $password_db, $dbname_db);
if ($conn->connect_error) {
    error_log("Database connection failed: " . $conn->connect_error);
    exit("Database connection failed.");
}


// Check if the user is logged in
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}


// Pagination setup
$limit = 20;
$page = max(1, isset($_GET['page']) ? (int)$_GET['page'] : 1);
$offset = ($page - 1) * $limit; // Offset for SQL query

// Search setup
$search = isset($_GET['search']) ? trim($_GET['search']) : '';

// Sorting setup
$sort_order = isset($_GET['sort']) && $_GET['sort'] === 'desc' ? 'DESC' : 'ASC';

// Prepare SQL query with search and sorting
$sql = "SELECT * FROM publishers_site WHERE (site_name LIKE ? OR site_domain LIKE ? OR site_desc LIKE ?)
        ORDER BY rate_text_ads $sort_order LIMIT ? OFFSET ?";

// Prepare the statement
$stmt = $conn->prepare($sql);

// Add search wildcard
$search_param = '%' . $search . '%';

// Bind parameters: 3 search fields and pagination limits
$stmt->bind_param('sssii', $search_param, $search_param, $search_param, $limit, $offset);

// Execute the query
$stmt->execute();
$result = $stmt->get_result();

// Count total records for pagination
$count_sql = "SELECT COUNT(*) FROM publishers_site WHERE (site_name LIKE ? OR site_domain LIKE ? OR site_desc LIKE ?)";
$count_stmt = $conn->prepare($count_sql);
$count_stmt->bind_param('sss', $search_param, $search_param, $search_param);
$count_stmt->execute();
$count_stmt->bind_result($total_records);
$count_stmt->fetch();
$total_pages = max(1, (int) ceil($total_records / $limit));
$window_start = max(1, $page - 2);
$window_end = min($total_pages, $page + 2);
$sort_param = strtolower($sort_order);

$stmt->close();
$count_stmt->close();
$conn->close();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Publisher Sites</title>
    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        body { background: #f5f7fa; color: #26313c; }
        .page-shell { max-width: 1320px; }
        .page-heading h1 { margin: 0; font-size: 1.65rem; font-weight: 750; }
        .page-heading p { margin: .3rem 0 0; color: #6c757d; }
        .toolbar { display: grid; grid-template-columns: minmax(260px, 1fr) auto; gap: .75rem; padding: 1rem; border-radius: .85rem; background: #fff; box-shadow: 0 3px 14px rgba(31,41,55,.07); }
        .publisher-table-card { overflow: hidden; border: 0; border-radius: .9rem; box-shadow: 0 3px 14px rgba(31,41,55,.08); }
        .publisher-table { margin: 0; }
        .publisher-table thead th { white-space: nowrap; color: #59636e; font-size: .72rem; text-transform: uppercase; }
        .publisher-table td { vertical-align: top; font-size: .86rem; }
        .site-cell, .policy-cell { min-width: 210px; }
        .domain-link { display: block; max-width: 230px; overflow-wrap: anywhere; }
        .detail-label { color: #6c757d; font-size: .72rem; font-weight: 700; }
        .rate-value { white-space: nowrap; font-weight: 750; color: #12683a; }
        .publisher-card { overflow: hidden; border: 0; border-radius: .9rem; box-shadow: 0 3px 14px rgba(31,41,55,.08); transition: transform .2s ease, box-shadow .2s ease; }
        .publisher-card:hover { transform: translateY(-3px); box-shadow: 0 7px 22px rgba(31,41,55,.12); }
        .publisher-card .card-body { display: flex; flex-direction: column; }
        .publisher-title { font-size: 1.08rem; font-weight: 750; overflow-wrap: anywhere; }
        .publisher-domain { display: block; margin-bottom: .8rem; font-size: .8rem; overflow-wrap: anywhere; }
        .publisher-description { min-height: 2.8rem; color: #64707c; font-size: .86rem; }
        .rate-grid { display: grid; grid-template-columns: 1fr 1fr; gap: .65rem; margin: .75rem 0 1rem; }
        .rate-box { padding: .75rem; border-radius: .65rem; background: #f3f6f8; }
        .rate-box.selling { background: #eaf8f0; color: #12683a; }
        .rate-label { display: block; font-size: .68rem; font-weight: 700; text-transform: uppercase; }
        .rate-amount { display: block; margin-top: .2rem; font-size: 1.05rem; font-weight: 800; }
        .policy-box { margin-bottom: .65rem; padding: .7rem; border-radius: .6rem; background: #f8f9fa; font-size: .8rem; overflow-wrap: anywhere; }
        .policy-box.allowed { border-left: 3px solid #198754; }
        .policy-box.rejected { border-left: 3px solid #dc3545; }
        .publisher-pagination { flex-wrap: wrap; gap: .25rem; }
        .publisher-pagination .page-link { min-width: 38px; border-radius: .45rem !important; text-align: center; }
        @media (max-width: 767.98px) {
            .toolbar { grid-template-columns: 1fr; }
            .publisher-table-responsive { overflow: visible; }
            .publisher-table, .publisher-table tbody, .publisher-table tr, .publisher-table td { display: block; width: 100%; }
            .publisher-table thead { display: none; }
            .publisher-table tbody { display: grid; gap: 1rem; }
            .publisher-table tbody tr { overflow: hidden; border: 1px solid #e1e6eb; border-radius: .75rem; background: #fff; }
            .publisher-table tbody td { min-width: 0; padding: .75rem 1rem; border: 0; border-bottom: 1px solid #edf0f2; }
            .publisher-table tbody td:last-child { border-bottom: 0; }
            .publisher-table tbody td::before { content: attr(data-label); display: block; margin-bottom: .3rem; color: #6c757d; font-size: .68rem; font-weight: 700; text-transform: uppercase; }
            .domain-link { max-width: 100%; }
        }
        @media (max-width: 575.98px) { .page-shell { padding-right: .75rem; padding-left: .75rem; } .page-heading h1 { font-size: 1.4rem; } }
    </style>
</head>
<body>
    <div class="container page-shell py-3 py-md-4">
          <?php include("main_menu.php") ?>
        <?php include("include_advertiser_menu.php") ?>


        <div class="page-heading mb-4"><h1>Rate Publisher Lokal</h1><p>Bandingkan harga per klik dan kebijakan iklan dari <?php echo number_format($total_records); ?> site publisher.</p></div>

        <!-- Search Form -->
        <div class="toolbar mb-4">
            <form method="GET" action="view_rate_publisher.php" class="input-group">
                <input type="search" name="search" class="form-control" placeholder="Cari nama, domain, atau deskripsi site" aria-label="Cari publisher" value="<?php echo htmlspecialchars($search); ?>">
                <input type="hidden" name="sort" value="<?php echo htmlspecialchars($sort_param); ?>">
                <button class="btn btn-primary" type="submit"><i class="fas fa-search me-1"></i> Cari</button>
            </form>
            <a href="?search=<?php echo urlencode($search); ?>&amp;sort=<?php echo $sort_order === 'ASC' ? 'desc' : 'asc'; ?>" class="btn btn-outline-secondary"><i class="fas fa-sort-amount-<?php echo $sort_order === 'ASC' ? 'down' : 'up'; ?> me-1"></i> Rate <?php echo $sort_order === 'ASC' ? 'Tertinggi' : 'Terendah'; ?></a>
        </div>

        <!-- Display Publishers as Cards -->
        <?php if ($result->num_rows > 0): ?>
          <div class="row g-4">
            <?php while ($row = $result->fetch_assoc()):
                $rate_text_ads = (float) $row['rate_text_ads'];
                $rate_text_ads_with_markup_local = $rate_text_ads + ($rate_text_ads / 2);
            ?>
              <div class="col-12 col-md-6 col-xl-4">
                <article class="card publisher-card h-100">
                  <div class="card-body p-4">
                    <div class="d-flex align-items-start justify-content-between gap-2">
                      <h2 class="publisher-title mb-1"><?php echo htmlspecialchars($row['site_name']); ?></h2>
                      <span class="badge <?php echo $row['isbanned'] ? 'text-bg-danger' : 'text-bg-success'; ?>"><?php echo $row['isbanned'] ? 'Banned' : 'Aktif'; ?></span>
                    </div>
                    <a class="publisher-domain" href="<?php echo htmlspecialchars($row['site_domain'], ENT_QUOTES); ?>" target="_blank" rel="noopener noreferrer"><?php echo htmlspecialchars($row['site_domain']); ?></a>
                    <p class="publisher-description"><?php echo htmlspecialchars($row['site_desc']); ?></p>

                    <div class="rate-grid">
                      <div class="rate-box"><span class="rate-label">Rate Publisher</span><span class="rate-amount">Rp <?php echo number_format($rate_text_ads, 1, ',', '.'); ?></span></div>
                      <div class="rate-box selling"><span class="rate-label">Harga Jual</span><span class="rate-amount">Rp <?php echo number_format($rate_text_ads_with_markup_local, 1, ',', '.'); ?></span></div>
                    </div>

                    <div class="policy-box allowed"><span class="detail-label">Iklan Diizinkan</span><br><?php echo htmlspecialchars($row['advertiser_allowed'] ?: '-'); ?></div>
                    <div class="policy-box rejected"><span class="detail-label">Iklan Ditolak</span><br><?php echo htmlspecialchars($row['advertiser_rejected'] ?: '-'); ?></div>
                    <div class="d-flex justify-content-between gap-2 mt-auto pt-2 text-muted small"><span>Provider: <?php echo htmlspecialchars($row['providers_name']); ?></span><span><?php echo htmlspecialchars($row['regdate']); ?></span></div>
                  </div>
                </article>
              </div>
            <?php endwhile; ?>
          </div>
        <?php else: ?>
          <div class="alert alert-warning text-center">Tidak ada publisher ditemukan.</div>
        <?php endif; ?>

        <!-- Pagination -->
        <div class="text-center text-muted small mt-4">Halaman <?php echo $page; ?> dari <?php echo $total_pages; ?></div>
        <nav class="mt-2" aria-label="Navigasi rate publisher">
            <ul class="pagination publisher-pagination justify-content-center">
                <li class="page-item <?php echo $page <= 1 ? 'disabled' : ''; ?>"><a class="page-link" href="?page=<?php echo max(1, $page - 1); ?>&amp;search=<?php echo urlencode($search); ?>&amp;sort=<?php echo $sort_param; ?>">&laquo;</a></li>
                <?php if ($window_start > 1): ?>
                    <li class="page-item"><a class="page-link" href="?page=1&amp;search=<?php echo urlencode($search); ?>&amp;sort=<?php echo $sort_param; ?>">1</a></li>
                    <?php if ($window_start > 2): ?><li class="page-item disabled"><span class="page-link">&hellip;</span></li><?php endif; ?>
                <?php endif; ?>
                <?php for ($i = $window_start; $i <= $window_end; $i++): ?>
                    <li class="page-item <?php echo $i === $page ? 'active' : ''; ?>"><a class="page-link" href="?page=<?php echo $i; ?>&amp;search=<?php echo urlencode($search); ?>&amp;sort=<?php echo $sort_param; ?>"><?php echo $i; ?></a></li>
                <?php endfor; ?>
                <?php if ($window_end < $total_pages): ?>
                    <?php if ($window_end < $total_pages - 1): ?><li class="page-item disabled"><span class="page-link">&hellip;</span></li><?php endif; ?>
                    <li class="page-item"><a class="page-link" href="?page=<?php echo $total_pages; ?>&amp;search=<?php echo urlencode($search); ?>&amp;sort=<?php echo $sort_param; ?>"><?php echo $total_pages; ?></a></li>
                <?php endif; ?>
                <li class="page-item <?php echo $page >= $total_pages ? 'disabled' : ''; ?>"><a class="page-link" href="?page=<?php echo min($total_pages, $page + 1); ?>&amp;search=<?php echo urlencode($search); ?>&amp;sort=<?php echo $sort_param; ?>">&raquo;</a></li>
            </ul>
        </nav>
    </div>

    <!-- Bootstrap JS with Popper -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
