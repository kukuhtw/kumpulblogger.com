<?php
session_start();

if (!isset($_SESSION['loggedin'])) {
    header('Location: login.php');
    exit;
}

include("../db.php");

$loginemail_admin = $_SESSION['loginemail_admin'];
$mysqli = new mysqli($servername_db, $username_db, $password_db, $dbname_db);
if ($mysqli->connect_error) {
    error_log("Database connection failed: " . $mysqli->connect_error);
    exit("Database connection failed.");
}

$limit = 20;
$page = max(1, isset($_GET['page']) ? (int) $_GET['page'] : 1);
$offset = ($page - 1) * $limit;
$search_query = trim($_GET['search'] ?? '');
$search_sql = $search_query !== ''
    ? " AND (mu.loginemail LIKE ? OR ac.site_name LIKE ? OR ac.site_domain LIKE ? OR ac.title_ads LIKE ? OR ac.ip_address LIKE ?)"
    : '';

$summary_sql = "SELECT COUNT(*) AS total_clicks,
                       COALESCE(SUM(ac.revenue_publishers), 0) AS total_revenue,
                       MAX(COALESCE(ac.audit_date, ac.click_time)) AS latest_recognized
                FROM ad_clicks ac
                LEFT JOIN publishers_site ps ON ps.id = ac.pub_id
                LEFT JOIN msusers mu ON mu.id = ps.publishers_local_id
                WHERE ac.isaudit = 1 AND ac.is_reject = 0" . $search_sql;
$summary_stmt = $mysqli->prepare($summary_sql);
if ($search_query !== '') {
    $like_search = '%' . $search_query . '%';
    $summary_stmt->bind_param("sssss", $like_search, $like_search, $like_search, $like_search, $like_search);
}
$summary_stmt->execute();
$summary = $summary_stmt->get_result()->fetch_assoc();
$summary_stmt->close();

$total_clicks = (int) $summary['total_clicks'];
$total_revenue = (float) $summary['total_revenue'];
$latest_recognized = $summary['latest_recognized'];
$total_pages = (int) ceil($total_clicks / $limit);

$clicks_sql = "SELECT ac.id, ac.pub_id, ac.site_name, ac.site_domain, ac.title_ads,
                      ac.landingpage_ads, ac.ip_address, ac.click_time, ac.audit_date,
                      ac.revenue_publishers, mu.id AS owner_id, mu.loginemail AS owner_email
               FROM ad_clicks ac
               LEFT JOIN publishers_site ps ON ps.id = ac.pub_id
               LEFT JOIN msusers mu ON mu.id = ps.publishers_local_id
               WHERE ac.isaudit = 1 AND ac.is_reject = 0" . $search_sql . "
               ORDER BY COALESCE(ac.audit_date, ac.click_time) DESC, ac.id DESC
               LIMIT ? OFFSET ?";
$clicks_stmt = $mysqli->prepare($clicks_sql);
if ($search_query !== '') {
    $clicks_stmt->bind_param("sssssii", $like_search, $like_search, $like_search, $like_search, $like_search, $limit, $offset);
} else {
    $clicks_stmt->bind_param("ii", $limit, $offset);
}
$clicks_stmt->execute();
$clicks = $clicks_stmt->get_result();
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Klik Terakhir Diakui</title>
    <?php include("style_toogle.php"); ?>
    <style>
        .click-toolbar { display: flex; align-items: end; justify-content: space-between; gap: 1rem; }
        .click-search { display: flex; width: min(100%, 520px); }
        .click-search .form-control { border-radius: .45rem 0 0 .45rem; }
        .click-search .btn { border-radius: 0 .45rem .45rem 0; white-space: nowrap; }
        .summary-grid { display: grid; grid-template-columns: repeat(3, minmax(0, 1fr)); gap: 1rem; margin-bottom: 1rem; }
        .summary-card { padding: 1rem 1.15rem; border-radius: .7rem; background: #fff; box-shadow: 0 3px 14px rgba(31,41,55,.08); }
        .summary-label { color: #6c757d; font-size: .75rem; font-weight: 600; letter-spacing: .035em; text-transform: uppercase; }
        .summary-value { margin-top: .2rem; color: #25313c; font-size: 1.25rem; font-weight: 700; }
        .summary-value.revenue, .revenue-value { color: #198754; }
        .owner-email { display: block; font-weight: 600; overflow-wrap: anywhere; }
        .site-name { display: block; color: #25313c; font-weight: 700; }
        .site-domain { display: block; color: #0d6efd; font-size: .8rem; overflow-wrap: anywhere; }
        .ad-title { min-width: 190px; color: #374151; font-weight: 600; }
        .date-stack { min-width: 155px; font-size: .8rem; line-height: 1.6; }
        .date-stack span { display: block; }
        .date-stack small { color: #6c757d; }
        .revenue-value { font-weight: 700; white-space: nowrap; }
        @media (max-width: 767.98px) {
            .click-toolbar { align-items: stretch; flex-direction: column; }
            .click-search { width: 100%; }
            .summary-grid { grid-template-columns: 1fr; }
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
    <div class="click-toolbar mb-4">
        <div>
            <h1 class="page-title">Klik Terakhir Diakui</h1>
            <p class="page-subtitle">Daftar klik yang sudah diaudit dan tidak ditolak, terbaru lebih dahulu.</p>
        </div>
        <form method="get" action="latest_recognized_clicks.php" class="click-search">
            <input type="search" name="search" class="form-control" placeholder="Cari email, situs, iklan, atau IP" value="<?php echo htmlspecialchars($search_query, ENT_QUOTES, 'UTF-8'); ?>">
            <button type="submit" class="btn btn-primary"><i class="fas fa-search mr-1"></i> Cari</button>
        </form>
    </div>

    <div class="summary-grid">
        <div class="summary-card"><div class="summary-label">Total Klik Diakui</div><div class="summary-value"><?php echo number_format($total_clicks, 0, ',', '.'); ?></div></div>
        <div class="summary-card"><div class="summary-label">Total Revenue Publisher</div><div class="summary-value revenue">Rp <?php echo number_format($total_revenue, 2, ',', '.'); ?></div></div>
        <div class="summary-card"><div class="summary-label">Terakhir Diakui</div><div class="summary-value"><?php echo htmlspecialchars($latest_recognized ?: '-', ENT_QUOTES, 'UTF-8'); ?></div></div>
    </div>

    <div class="card">
        <div class="card-header">Riwayat Klik Valid</div>
        <div class="card-body">
            <div class="table-responsive">
                <table class="table table-hover">
                    <thead><tr><th>ID</th><th>Pemilik</th><th>Publisher</th><th>Iklan</th><th>IP</th><th>Waktu</th><th>Revenue</th></tr></thead>
                    <tbody>
                    <?php while ($row = $clicks->fetch_assoc()): ?>
                        <tr>
                            <td class="text-muted font-weight-bold">#<?php echo (int) $row['id']; ?></td>
                            <td>
                                <?php if (!empty($row['owner_email'])): ?>
                                    <a class="owner-email" href="manage_users.php?search=<?php echo urlencode($row['owner_email']); ?>"><?php echo htmlspecialchars($row['owner_email'], ENT_QUOTES, 'UTF-8'); ?></a>
                                    <small class="text-muted">User #<?php echo (int) $row['owner_id']; ?></small>
                                <?php else: ?><span class="text-muted">Tidak ditemukan</span><?php endif; ?>
                            </td>
                            <td><span class="site-name"><?php echo htmlspecialchars($row['site_name'] ?: '-', ENT_QUOTES, 'UTF-8'); ?></span><span class="site-domain"><?php echo htmlspecialchars($row['site_domain'] ?: '-', ENT_QUOTES, 'UTF-8'); ?></span><small class="text-muted">Publisher #<?php echo (int) $row['pub_id']; ?></small></td>
                            <td class="ad-title"><?php echo htmlspecialchars($row['title_ads'] ?: '-', ENT_QUOTES, 'UTF-8'); ?><?php if (!empty($row['landingpage_ads'])): ?><br><a class="btn btn-sm btn-outline-primary mt-2" href="<?php echo htmlspecialchars($row['landingpage_ads'], ENT_QUOTES, 'UTF-8'); ?>" target="_blank" rel="noopener noreferrer">Landing Page</a><?php endif; ?></td>
                            <td><?php echo htmlspecialchars($row['ip_address'] ?: '-', ENT_QUOTES, 'UTF-8'); ?></td>
                            <td class="date-stack"><span><small>Klik:</small> <?php echo htmlspecialchars($row['click_time'], ENT_QUOTES, 'UTF-8'); ?></span><span><small>Diakui:</small> <?php echo htmlspecialchars($row['audit_date'] ?: $row['click_time'], ENT_QUOTES, 'UTF-8'); ?></span></td>
                            <td><span class="revenue-value">Rp <?php echo number_format((float) $row['revenue_publishers'], 2, ',', '.'); ?></span></td>
                        </tr>
                    <?php endwhile; ?>
                    <?php if ($total_clicks === 0): ?><tr><td colspan="7" class="text-center text-muted py-4">Klik yang diakui tidak ditemukan.</td></tr><?php endif; ?>
                    </tbody>
                </table>
            </div>

            <?php if ($total_pages > 1): ?>
            <nav class="pt-3" aria-label="Navigasi halaman klik"><ul class="pagination justify-content-center">
                <?php if ($page > 1): ?><li class="page-item"><a class="page-link" href="?page=<?php echo $page - 1; ?>&amp;search=<?php echo urlencode($search_query); ?>">Sebelumnya</a></li><?php endif; ?>
                <?php for ($i = max(1, $page - 2); $i <= min($total_pages, $page + 2); $i++): ?><li class="page-item <?php echo $i === $page ? 'active' : ''; ?>"><a class="page-link" href="?page=<?php echo $i; ?>&amp;search=<?php echo urlencode($search_query); ?>"><?php echo $i; ?></a></li><?php endfor; ?>
                <?php if ($page < $total_pages): ?><li class="page-item"><a class="page-link" href="?page=<?php echo $page + 1; ?>&amp;search=<?php echo urlencode($search_query); ?>">Berikutnya</a></li><?php endif; ?>
            </ul></nav>
            <?php endif; ?>
        </div>
    </div>
</main>

<?php
$clicks_stmt->close();
$mysqli->close();
include("footer.php");
include("js_toogle.php");
?>
</body>
</html>
