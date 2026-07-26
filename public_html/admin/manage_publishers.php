<?php
/*
admin/manage_publishers.php
*/
session_start();

// Check if the user is logged in
if (!isset($_SESSION['loggedin'])) {
    // If not logged in, redirect to login page
    header('Location: login.php');
    exit;
}

// Include the database connection
include("../db.php");

$loginemail_admin = $_SESSION['loginemail_admin'];

// Set default page number and items per page
$limit = 10;
$page = max(1, isset($_GET['page']) ? (int)$_GET['page'] : 1);
$offset = ($page - 1) * $limit;
$search_query = trim($_GET['search'] ?? '');
$search_sql = $search_query !== ''
    ? " WHERE ps.site_name LIKE ? OR ps.site_domain LIKE ? OR ps.providers_name LIKE ? OR mu.loginemail LIKE ?"
    : '';
$allowed_sorts = [
    'rate_text_ads' => 'ps.rate_text_ads',
    'revenue_local' => 'ps.current_site_revenue',
    'revenue_partner' => 'ps.current_site_revenue_from_partner',
    'regdate' => 'ps.regdate',
];
$sort = isset($allowed_sorts[$_GET['sort'] ?? '']) ? $_GET['sort'] : 'revenue_local';
$order = strtolower($_GET['order'] ?? '') === 'asc' ? 'asc' : 'desc';
$sort_column = $allowed_sorts[$sort];
$sort_direction = strtoupper($order);

// Database connection using MySQLi
$conn = new mysqli($servername_db, $username_db, $password_db, $dbname_db);
if ($conn->connect_error) {
    error_log("Database connection failed: " . $conn->connect_error);
    exit("Database connection failed.");
}

// Get total number of publishers
$sql = "SELECT COUNT(*) AS total FROM publishers_site ps LEFT JOIN msusers mu ON ps.publishers_local_id = mu.id" . $search_sql;
$count_stmt = $conn->prepare($sql);
if ($search_query !== '') {
    $like_search = '%' . $search_query . '%';
    $count_stmt->bind_param("ssss", $like_search, $like_search, $like_search, $like_search);
}
$count_stmt->execute();
$total_publishers = (int) $count_stmt->get_result()->fetch_assoc()['total'];

$sql = "
SELECT ps.id, 
       ps.publishers_local_id,
       ps.site_name,
       ps.site_domain,
       ps.providers_name,
       ps.rate_text_ads, 
       ps.current_site_revenue,
       ps.current_site_revenue_from_partner,
       ps.advertiser_allowed,
       ps.advertiser_rejected,
       ps.regdate,
       mu.loginemail AS owner_email
FROM publishers_site ps
LEFT JOIN msusers mu ON ps.publishers_local_id = mu.id
" . $search_sql . "
ORDER BY " . $sort_column . " " . $sort_direction . ", ps.id DESC
LIMIT ? OFFSET ?";
$stmt = $conn->prepare($sql);
if ($search_query !== '') {
    $stmt->bind_param("ssssii", $like_search, $like_search, $like_search, $like_search, $limit, $offset);
} else {
    $stmt->bind_param("ii", $limit, $offset);
}
$stmt->execute();
$result = $stmt->get_result();



// Calculate total pages
$total_pages = ceil($total_publishers / $limit);

// Close the statement and connection
$stmt->close();
$count_stmt->close();
$conn->close();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Kelola Publisher</title>
    <?php include("style_toogle.php"); ?>
    <style>
        .publisher-toolbar { display: flex; align-items: end; justify-content: space-between; gap: 1rem; }
        .publisher-search { display: flex; width: min(100%, 760px); }
        .publisher-search .search-input { border-radius: .45rem 0 0 .45rem; }
        .publisher-search .sort-select, .publisher-search .order-select { width: auto; border-left: 0; border-radius: 0; }
        .publisher-search .btn { border-radius: 0 .45rem .45rem 0; white-space: nowrap; }
        .sortable-link { display: inline-flex; align-items: center; gap: .35rem; color: inherit; text-decoration: none; }
        .sortable-link:hover { color: #0d6efd; text-decoration: none; }
        .sort-indicator { color: #0d6efd; font-size: .7rem; }
        .site-name { display: block; color: #25313c; font-weight: 700; }
        .site-domain { display: block; margin-top: .15rem; color: #0d6efd; font-size: .82rem; overflow-wrap: anywhere; }
        .provider-badge { display: inline-block; margin-top: .35rem; padding: .2rem .45rem; border-radius: 99px; background: #eef2ff; color: #4f46e5; font-size: .72rem; }
        .owner-email { display: block; color: #25313c; font-weight: 600; overflow-wrap: anywhere; }
        .owner-id { color: #6c757d; font-size: .75rem; }
        .revenue-value { color: #198754; font-weight: 700; white-space: nowrap; }
        .metric-pair { min-width: 115px; font-size: .82rem; line-height: 1.7; }
        .metric-pair span { display: flex; justify-content: space-between; gap: .65rem; }
        .metric-pair strong { color: #374151; }
        .publishers-table td { vertical-align: middle; }
        @media (max-width: 767.98px) {
            .publisher-toolbar { align-items: stretch; flex-direction: column; }
            .publisher-search { display: grid; grid-template-columns: 1fr 1fr; width: 100%; }
            .publisher-search .search-input { grid-column: 1 / -1; border-radius: .45rem; }
            .publisher-search .sort-select, .publisher-search .order-select { width: 100%; border: 1px solid #ced4da; border-radius: .45rem; }
            .publisher-search .btn { grid-column: 1 / -1; border-radius: .45rem; }
        }
    </style>
</head>

<body>

<div class="admin-navbar">
    <a class="brand" href="dashboard_admin.php">Admin Dashboard</a>
    <a href="logout.php"><i class="fas fa-sign-out-alt mr-1"></i> Logout</a>
</div>

<?php include("sidebar_menu.php"); ?>


<main class="admin-main" id="mainContent">
    <div class="publisher-toolbar mb-4">
        <div>
            <h1 class="page-title">Situs Publisher</h1>
            <p class="page-subtitle"><?php echo number_format($total_publishers, 0, ',', '.'); ?> situs publisher ditemukan.</p>
        </div>
        <form method="get" action="manage_publishers.php" class="publisher-search">
            <input type="search" name="search" class="form-control search-input" placeholder="Cari situs, domain, provider, atau email" value="<?php echo htmlspecialchars($search_query, ENT_QUOTES, 'UTF-8'); ?>">
            <select name="sort" class="form-control sort-select" aria-label="Urutkan berdasarkan">
                <option value="rate_text_ads" <?php echo $sort === 'rate_text_ads' ? 'selected' : ''; ?>>Rate Text Ads</option>
                <option value="revenue_local" <?php echo $sort === 'revenue_local' ? 'selected' : ''; ?>>Revenue Lokal</option>
                <option value="revenue_partner" <?php echo $sort === 'revenue_partner' ? 'selected' : ''; ?>>Revenue Partner</option>
                <option value="regdate" <?php echo $sort === 'regdate' ? 'selected' : ''; ?>>Tanggal Daftar</option>
            </select>
            <select name="order" class="form-control order-select" aria-label="Urutan sortir">
                <option value="desc" <?php echo $order === 'desc' ? 'selected' : ''; ?>>Terbesar</option>
                <option value="asc" <?php echo $order === 'asc' ? 'selected' : ''; ?>>Terkecil</option>
            </select>
            <button class="btn btn-primary" type="submit"><i class="fas fa-filter mr-1"></i> Terapkan</button>
        </form>
    </div>
        <div class="card">
            <div class="card-header">Data Publisher</div>
            <div class="card-body">
              <div class="table-responsive">
                <table class="table table-hover publishers-table">
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>Situs</th>
                            <th>Email Pemilik</th>
                            <?php
                            $sort_headers = [
                                'rate_text_ads' => 'Rate Text Ads',
                                'revenue_local' => 'Revenue Lokal',
                                'revenue_partner' => 'Revenue Partner',
                            ];
                            foreach ($sort_headers as $sort_key => $sort_label):
                                $next_order = ($sort === $sort_key && $order === 'desc') ? 'asc' : 'desc';
                            ?>
                            <th>
                                <a class="sortable-link" href="?search=<?php echo urlencode($search_query); ?>&amp;sort=<?php echo $sort_key; ?>&amp;order=<?php echo $next_order; ?>">
                                    <?php echo $sort_label; ?>
                                    <?php if ($sort === $sort_key): ?><span class="sort-indicator"><?php echo $order === 'asc' ? '▲' : '▼'; ?></span><?php endif; ?>
                                </a>
                            </th>
                            <?php endforeach; ?>
                            <th>Advertiser</th>
                            <?php $regdate_next_order = ($sort === 'regdate' && $order === 'desc') ? 'asc' : 'desc'; ?>
                            <th>
                                <a class="sortable-link" href="?search=<?php echo urlencode($search_query); ?>&amp;sort=regdate&amp;order=<?php echo $regdate_next_order; ?>">
                                    Terdaftar
                                    <?php if ($sort === 'regdate'): ?><span class="sort-indicator"><?php echo $order === 'asc' ? '▲' : '▼'; ?></span><?php endif; ?>
                                </a>
                            </th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php while ($row = $result->fetch_assoc()): ?>
                        <tr>
                            <td><?php echo (int) $row['id']; ?></td>
                            <td>
                                <span class="site-name"><?php echo htmlspecialchars($row['site_name'] ?: '-', ENT_QUOTES, 'UTF-8'); ?></span>
                                <span class="site-domain"><?php echo htmlspecialchars($row['site_domain'] ?: '-', ENT_QUOTES, 'UTF-8'); ?></span>
                                <span class="provider-badge"><?php echo htmlspecialchars($row['providers_name'] ?: 'Tanpa provider', ENT_QUOTES, 'UTF-8'); ?></span>
                            </td>
                            <td>
                                <?php if (!empty($row['owner_email'])): ?>
                                    <a class="owner-email" href="manage_users.php?search=<?php echo urlencode($row['owner_email']); ?>" title="Lihat pemilik di daftar pengguna">
                                        <?php echo htmlspecialchars($row['owner_email'], ENT_QUOTES, 'UTF-8'); ?>
                                    </a>
                                <?php else: ?>
                                    <span class="owner-email text-muted">Email tidak ditemukan</span>
                                <?php endif; ?>
                                <span class="owner-id">User ID: <?php echo (int) $row['publishers_local_id']; ?></span>
                            </td>
                            <td>Rp <?php echo number_format((float) $row['rate_text_ads'], 0, ',', '.'); ?></td>
                            <td><span class="revenue-value">Rp <?php echo number_format((float) $row['current_site_revenue'], 0, ',', '.'); ?></span></td>
                            <td><span class="revenue-value">Rp <?php echo number_format((float) $row['current_site_revenue_from_partner'], 0, ',', '.'); ?></span></td>
                            <td class="metric-pair">
                                <span>Diizinkan <strong><?php echo number_format((int) $row['advertiser_allowed'], 0, ',', '.'); ?></strong></span>
                                <span>Ditolak <strong><?php echo number_format((int) $row['advertiser_rejected'], 0, ',', '.'); ?></strong></span>
                            </td>
                            <td><?php echo htmlspecialchars($row['regdate'] ?: '-', ENT_QUOTES, 'UTF-8'); ?></td>
                        </tr>
                        <?php endwhile; ?>
                        <?php if ($total_publishers === 0): ?>
                        <tr><td colspan="8" class="text-center text-muted py-4">Publisher tidak ditemukan.</td></tr>
                        <?php endif; ?>
                    </tbody>
                </table>
              </div>

                <!-- Pagination Links -->
                <nav>
                    <ul class="pagination">
                        <?php if ($page > 1): ?>
                            <li class="page-item">
                                <a class="page-link" href="?page=<?php echo $page - 1; ?>&amp;search=<?php echo urlencode($search_query); ?>&amp;sort=<?php echo $sort; ?>&amp;order=<?php echo $order; ?>">Sebelumnya</a>
                            </li>
                        <?php endif; ?>

                        <?php
                        $start_page = max(1, $page - 2);
                        $end_page = min($total_pages, $page + 2);
                        for ($i = $start_page; $i <= $end_page; $i++):
                        ?>
                            <li class="page-item <?php if ($i == $page) echo 'active'; ?>">
                                <a class="page-link" href="?page=<?php echo $i; ?>&amp;search=<?php echo urlencode($search_query); ?>&amp;sort=<?php echo $sort; ?>&amp;order=<?php echo $order; ?>"><?php echo $i; ?></a>
                            </li>
                        <?php endfor; ?>

                        <?php if ($page < $total_pages): ?>
                            <li class="page-item">
                                <a class="page-link" href="?page=<?php echo $page + 1; ?>&amp;search=<?php echo urlencode($search_query); ?>&amp;sort=<?php echo $sort; ?>&amp;order=<?php echo $order; ?>">Berikutnya</a>
                            </li>
                        <?php endif; ?>
                    </ul>
                </nav>
            </div>
        </div>
</main>

<?php
include("footer.php");
include("js_toogle.php");
?>


</body>
</html>
