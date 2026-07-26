<?php
session_start();
// admin/top_active_publishers.php

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

$allowed_days = [7, 30, 90, 0]; // 0 = semua waktu
$days = isset($_GET['days']) ? (int) $_GET['days'] : 30;
if (!in_array($days, $allowed_days, true)) {
    $days = 30;
}
$period_sql = $days > 0 ? " AND ac.click_time >= DATE_SUB(NOW(), INTERVAL ? DAY)" : "";

$search_query = trim($_GET['search'] ?? '');
$search_sql = $search_query !== ''
    ? " AND (ps.site_name LIKE ? OR ps.site_domain LIKE ? OR mu.loginemail LIKE ?)"
    : '';

// Ringkasan periode berjalan.
$summary_sql = "SELECT COUNT(DISTINCT ac.pub_id) AS total_active_publishers,
                       COUNT(*) AS total_clicks,
                       COALESCE(SUM(ac.revenue_publishers), 0) AS total_revenue
                FROM ad_clicks ac
                LEFT JOIN publishers_site ps ON ps.id = ac.pub_id
                LEFT JOIN msusers mu ON mu.id = ps.publishers_local_id
                WHERE ac.isaudit = 1 AND ac.is_reject = 0" . $period_sql . $search_sql;
$summary_stmt = $mysqli->prepare($summary_sql);
$summary_types = '';
$summary_params = [];
if ($days > 0) {
    $summary_types .= 'i';
    $summary_params[] = $days;
}
if ($search_query !== '') {
    $like_search = '%' . $search_query . '%';
    $summary_types .= 'sss';
    $summary_params[] = $like_search;
    $summary_params[] = $like_search;
    $summary_params[] = $like_search;
}
if ($summary_types !== '') {
    $summary_stmt->bind_param($summary_types, ...$summary_params);
}
$summary_stmt->execute();
$summary = $summary_stmt->get_result()->fetch_assoc();
$summary_stmt->close();

$total_active_publishers = (int) $summary['total_active_publishers'];
$total_clicks_period = (int) $summary['total_clicks'];
$total_revenue_period = (float) $summary['total_revenue'];
$total_pages = (int) ceil($total_active_publishers / $limit);

// Ranking publisher berdasarkan jumlah klik iklan tervalidasi (proxy aktivitas menampilkan iklan).
$rank_sql = "SELECT ac.pub_id,
                    MAX(ac.site_name) AS site_name,
                    MAX(ac.site_domain) AS site_domain,
                    COUNT(*) AS total_clicks,
                    COALESCE(SUM(ac.revenue_publishers), 0) AS total_revenue,
                    MAX(COALESCE(ac.audit_date, ac.click_time)) AS last_active,
                    mu.id AS owner_id,
                    mu.loginemail AS owner_email
             FROM ad_clicks ac
             LEFT JOIN publishers_site ps ON ps.id = ac.pub_id
             LEFT JOIN msusers mu ON mu.id = ps.publishers_local_id
             WHERE ac.isaudit = 1 AND ac.is_reject = 0" . $period_sql . $search_sql . "
             GROUP BY ac.pub_id, mu.id, mu.loginemail
             ORDER BY total_clicks DESC
             LIMIT ? OFFSET ?";
$rank_stmt = $mysqli->prepare($rank_sql);
$rank_types = $summary_types . 'ii';
$rank_params = $summary_params;
$rank_params[] = $limit;
$rank_params[] = $offset;
$rank_stmt->bind_param($rank_types, ...$rank_params);
$rank_stmt->execute();
$ranking = $rank_stmt->get_result();
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Blog Teraktif Menampilkan Iklan</title>
    <?php include("style_toogle.php"); ?>
    <style>
        .click-toolbar { display: flex; align-items: end; justify-content: space-between; gap: 1rem; flex-wrap: wrap; }
        .click-search { display: flex; width: min(100%, 420px); }
        .click-search .form-control { border-radius: .45rem 0 0 .45rem; }
        .click-search .btn { border-radius: 0 .45rem .45rem 0; white-space: nowrap; }
        .period-tabs { display: flex; gap: .4rem; }
        .period-tabs a { padding: .4rem .85rem; border-radius: .5rem; background: #fff; border: 1px solid #dee2e6; color: #374151; font-weight: 600; font-size: .85rem; text-decoration: none; }
        .period-tabs a.active { background: #0d6efd; border-color: #0d6efd; color: #fff; }
        .summary-grid { display: grid; grid-template-columns: repeat(3, minmax(0, 1fr)); gap: 1rem; margin-bottom: 1rem; }
        .summary-card { padding: 1rem 1.15rem; border-radius: .7rem; background: #fff; box-shadow: 0 3px 14px rgba(31,41,55,.08); }
        .summary-label { color: #6c757d; font-size: .75rem; font-weight: 600; letter-spacing: .035em; text-transform: uppercase; }
        .summary-value { margin-top: .2rem; color: #25313c; font-size: 1.25rem; font-weight: 700; }
        .summary-value.revenue { color: #198754; }
        .rank-badge { display: inline-flex; align-items: center; justify-content: center; width: 28px; height: 28px; border-radius: 50%; background: #eef2ff; color: #4338ca; font-weight: 700; font-size: .8rem; }
        .owner-email { display: block; font-weight: 600; overflow-wrap: anywhere; }
        .site-name { display: block; color: #25313c; font-weight: 700; }
        .site-domain { display: block; color: #0d6efd; font-size: .8rem; overflow-wrap: anywhere; }
        .clicks-value { font-weight: 700; }
        .revenue-value { font-weight: 700; color: #198754; white-space: nowrap; }
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
            <h1 class="page-title">Blog Teraktif Menampilkan Iklan</h1>
            <p class="page-subtitle">Ranking publisher berdasarkan jumlah klik iklan tervalidasi &mdash; proxy aktivitas karena sistem belum mencatat impression/tayangan.</p>
        </div>
        <div class="d-flex flex-column align-items-end" style="gap:.6rem;">
            <div class="period-tabs">
                <a href="?days=7&amp;search=<?php echo urlencode($search_query); ?>" class="<?php echo $days === 7 ? 'active' : ''; ?>">7 Hari</a>
                <a href="?days=30&amp;search=<?php echo urlencode($search_query); ?>" class="<?php echo $days === 30 ? 'active' : ''; ?>">30 Hari</a>
                <a href="?days=90&amp;search=<?php echo urlencode($search_query); ?>" class="<?php echo $days === 90 ? 'active' : ''; ?>">90 Hari</a>
                <a href="?days=0&amp;search=<?php echo urlencode($search_query); ?>" class="<?php echo $days === 0 ? 'active' : ''; ?>">Semua</a>
            </div>
            <form method="get" action="top_active_publishers.php" class="click-search">
                <input type="hidden" name="days" value="<?php echo (int) $days; ?>">
                <input type="search" name="search" class="form-control" placeholder="Cari nama situs, domain, atau email" value="<?php echo htmlspecialchars($search_query, ENT_QUOTES, 'UTF-8'); ?>">
                <button type="submit" class="btn btn-primary"><i class="fas fa-search mr-1"></i> Cari</button>
            </form>
        </div>
    </div>

    <div class="summary-grid">
        <div class="summary-card"><div class="summary-label">Publisher Aktif</div><div class="summary-value"><?php echo number_format($total_active_publishers, 0, ',', '.'); ?></div></div>
        <div class="summary-card"><div class="summary-label">Total Klik Tervalidasi</div><div class="summary-value"><?php echo number_format($total_clicks_period, 0, ',', '.'); ?></div></div>
        <div class="summary-card"><div class="summary-label">Total Revenue Publisher</div><div class="summary-value revenue">Rp <?php echo number_format($total_revenue_period, 2, ',', '.'); ?></div></div>
    </div>

    <div class="card">
        <div class="card-header">Ranking Publisher</div>
        <div class="card-body">
            <div class="table-responsive">
                <table class="table table-hover">
                    <thead><tr><th>#</th><th>Publisher</th><th>Pemilik</th><th>Klik</th><th>Revenue</th><th>Terakhir Aktif</th></tr></thead>
                    <tbody>
                    <?php $rank_no = $offset + 1; ?>
                    <?php while ($row = $ranking->fetch_assoc()): ?>
                        <tr>
                            <td><span class="rank-badge">#<?php echo $rank_no++; ?></span></td>
                            <td>
                                <span class="site-name"><?php echo htmlspecialchars($row['site_name'] ?: '-', ENT_QUOTES, 'UTF-8'); ?></span>
                                <span class="site-domain"><?php echo htmlspecialchars($row['site_domain'] ?: '-', ENT_QUOTES, 'UTF-8'); ?></span>
                                <small class="text-muted">Publisher #<?php echo (int) $row['pub_id']; ?></small>
                            </td>
                            <td>
                                <?php if (!empty($row['owner_email'])): ?>
                                    <a class="owner-email" href="manage_users.php?search=<?php echo urlencode($row['owner_email']); ?>"><?php echo htmlspecialchars($row['owner_email'], ENT_QUOTES, 'UTF-8'); ?></a>
                                    <small class="text-muted">User #<?php echo (int) $row['owner_id']; ?></small>
                                <?php else: ?><span class="text-muted">Tidak ditemukan</span><?php endif; ?>
                            </td>
                            <td><span class="clicks-value"><?php echo number_format((int) $row['total_clicks'], 0, ',', '.'); ?></span></td>
                            <td><span class="revenue-value">Rp <?php echo number_format((float) $row['total_revenue'], 2, ',', '.'); ?></span></td>
                            <td><?php echo htmlspecialchars($row['last_active'] ?: '-', ENT_QUOTES, 'UTF-8'); ?></td>
                        </tr>
                    <?php endwhile; ?>
                    <?php if ($total_active_publishers === 0): ?><tr><td colspan="6" class="text-center text-muted py-4">Belum ada aktivitas klik pada periode ini.</td></tr><?php endif; ?>
                    </tbody>
                </table>
            </div>

            <?php if ($total_pages > 1): ?>
            <nav class="pt-3" aria-label="Navigasi halaman ranking"><ul class="pagination justify-content-center">
                <?php if ($page > 1): ?><li class="page-item"><a class="page-link" href="?page=<?php echo $page - 1; ?>&amp;days=<?php echo $days; ?>&amp;search=<?php echo urlencode($search_query); ?>">Sebelumnya</a></li><?php endif; ?>
                <?php for ($i = max(1, $page - 2); $i <= min($total_pages, $page + 2); $i++): ?><li class="page-item <?php echo $i === $page ? 'active' : ''; ?>"><a class="page-link" href="?page=<?php echo $i; ?>&amp;days=<?php echo $days; ?>&amp;search=<?php echo urlencode($search_query); ?>"><?php echo $i; ?></a></li><?php endfor; ?>
                <?php if ($page < $total_pages): ?><li class="page-item"><a class="page-link" href="?page=<?php echo $page + 1; ?>&amp;days=<?php echo $days; ?>&amp;search=<?php echo urlencode($search_query); ?>">Berikutnya</a></li><?php endif; ?>
            </ul></nav>
            <?php endif; ?>
        </div>
    </div>
</main>

<?php
$rank_stmt->close();
$mysqli->close();
include("footer.php");
include("js_toogle.php");
?>
</body>
</html>
