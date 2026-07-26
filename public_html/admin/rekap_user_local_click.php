<?php

// Start session
session_start();
// admin/rekap_user_local_click.php
if (!isset($_SESSION['loggedin'])) {
    header('Location: login.php');
    exit;
}
include("../db.php"); // Koneksi database
include("../function.php");

// Create a connection to the MySQL database
$mysqli = new mysqli($servername_db, $username_db, $password_db, $dbname_db);

// Check the connection
if ($mysqli->connect_error) {
    error_log("Database connection failed: " . $mysqli->connect_error);
    exit("Database connection failed.");
}

$loginemail_admin = $_SESSION['loginemail_admin'];

// Ambil parameter GET user_id
$user_id = isset($_GET['user_id']) ? intval($_GET['user_id']) : 0;
if ($user_id == 0) {
    header('Location: manage_users.php');
    exit;
}

// Pagination
$limit = 10; // Rows per page
$page = max(1, isset($_GET['page']) ? intval($_GET['page']) : 1);
$offset = ($page - 1) * $limit;

// Ambil identitas user untuk konteks halaman.
$stmt_user = $mysqli->prepare("SELECT loginemail, realname FROM msusers WHERE id = ? LIMIT 1");
$stmt_user->bind_param("i", $user_id);
$stmt_user->execute();
$user = $stmt_user->get_result()->fetch_assoc();
$stmt_user->close();

if (!$user) {
    header('Location: manage_users.php');
    exit;
}

// Ringkasan seluruh klik valid milik user.
$query_summary = "SELECT COUNT(*) AS total_clicks, COALESCE(SUM(ac.revenue_publishers), 0) AS total_revenue,
                         COUNT(DISTINCT ps.id) AS total_sites
                  FROM ad_clicks ac
                  INNER JOIN publishers_site ps ON ps.id = ac.pub_id
                  WHERE ps.publishers_local_id = ? AND ac.isaudit = 1 AND ac.is_reject = 0";
$stmt_summary = $mysqli->prepare($query_summary);
$stmt_summary->bind_param("i", $user_id);
$stmt_summary->execute();
$summary = $stmt_summary->get_result()->fetch_assoc();
$stmt_summary->close();
$total_rows = (int) $summary['total_clicks'];
$total_revenue = (float) $summary['total_revenue'];
$total_sites = (int) $summary['total_sites'];
$total_pages = (int) ceil($total_rows / $limit);

// Ambil klik valid melalui relasi situs sehingga tidak perlu membangun daftar IN dinamis.
$query_clicks = "SELECT ac.* FROM ad_clicks ac
                 INNER JOIN publishers_site ps ON ps.id = ac.pub_id
                 WHERE ps.publishers_local_id = ? AND ac.isaudit = 1 AND ac.is_reject = 0
                 ORDER BY ac.click_time DESC LIMIT ? OFFSET ?";
$stmt_clicks = $mysqli->prepare($query_clicks);
$stmt_clicks->bind_param("iii", $user_id, $limit, $offset);
$stmt_clicks->execute();
$result_clicks = $stmt_clicks->get_result();

// Tampilkan data klik
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Rekap Klik Pengguna</title>
    <?php include("style_toogle.php"); ?>
    <style>
        .user-heading { display: flex; align-items: center; justify-content: space-between; gap: 1rem; }
        .user-identity { margin-top: .35rem; color: #6c757d; }
        .user-identity strong { color: #374151; }
        .summary-grid { display: grid; grid-template-columns: repeat(3, minmax(0, 1fr)); gap: 1rem; margin-bottom: 1rem; }
        .summary-card { padding: 1rem 1.15rem; border-radius: .7rem; background: #fff; box-shadow: 0 3px 14px rgba(31,41,55,.08); }
        .summary-label { color: #6c757d; font-size: .75rem; font-weight: 600; letter-spacing: .035em; text-transform: uppercase; }
        .summary-value { margin-top: .2rem; color: #25313c; font-size: 1.3rem; font-weight: 700; }
        .summary-value.revenue { color: #198754; }
        .click-id { color: #6c757d; font-weight: 600; }
        .publisher-name { display: block; color: #25313c; font-weight: 700; }
        .publisher-domain { display: block; color: #0d6efd; font-size: .8rem; overflow-wrap: anywhere; }
        .technical-info { min-width: 240px; font-size: .77rem; }
        .technical-info > div { display: grid; grid-template-columns: 58px 1fr; gap: .5rem; padding: .18rem 0; border-bottom: 1px dashed #e5e7eb; }
        .technical-info span:first-child { color: #6c757d; }
        .technical-info span:last-child { overflow-wrap: anywhere; }
        .ad-title { min-width: 190px; color: #374151; font-weight: 600; }
        .revenue-cell { color: #198754; font-weight: 700; white-space: nowrap; }
        .click-time { white-space: nowrap; }
        @media (max-width: 767.98px) {
            .user-heading { align-items: stretch; flex-direction: column; }
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
    <div class="user-heading mb-4">
        <div>
            <h1 class="page-title">Rekap Transaksi Klik</h1>
            <div class="user-identity">
                <strong><?php echo htmlspecialchars($user['realname'] ?: 'Tanpa nama', ENT_QUOTES, 'UTF-8'); ?></strong>
                &middot; <?php echo htmlspecialchars($user['loginemail'], ENT_QUOTES, 'UTF-8'); ?>
                &middot; User #<?php echo $user_id; ?>
            </div>
        </div>
        <a href="manage_users.php?search=<?php echo urlencode($user['loginemail']); ?>" class="btn btn-outline-secondary"><i class="fas fa-arrow-left mr-1"></i> Kembali ke Pengguna</a>
    </div>

    <div class="summary-grid">
        <div class="summary-card"><div class="summary-label">Total Klik Valid</div><div class="summary-value"><?php echo number_format($total_rows, 0, ',', '.'); ?></div></div>
        <div class="summary-card"><div class="summary-label">Situs Menghasilkan Klik</div><div class="summary-value"><?php echo number_format($total_sites, 0, ',', '.'); ?></div></div>
        <div class="summary-card"><div class="summary-label">Total Revenue</div><div class="summary-value revenue">Rp <?php echo number_format($total_revenue, 2, ',', '.'); ?></div></div>
    </div>

  <div class="card">
    <div class="card-header">Riwayat Klik</div>
    <div class="card-body">
     <div class="table-responsive">
      <table class="table table-hover">
        <thead>
            <tr>
            <th scope="col">ID</th>
            <th scope="col">Publisher</th>
            <th scope="col">Informasi Klik</th>
            <th scope="col">Iklan</th>
            <th scope="col">Waktu Klik</th>
            <th scope="col">Revenue</th>
        </tr>
        </thead>
        <tbody>
            <?php while ($row = $result_clicks->fetch_assoc()): ?>
                <tr>
                    <td class="click-id">#<?php echo (int) $row['id']; ?></td>
                    <td>
                        <span class="publisher-name"><?php echo htmlspecialchars($row['site_name'] ?: '-', ENT_QUOTES, 'UTF-8'); ?></span>
                        <span class="publisher-domain"><?php echo htmlspecialchars($row['site_domain'] ?: '-', ENT_QUOTES, 'UTF-8'); ?></span>
                        <span class="data-label">Publisher #<?php echo (int) $row['pub_id']; ?></span>
                    </td>
                    <td class="technical-info">
                        <div><span>IP</span><span><?php echo htmlspecialchars($row['ip_address'] ?: '-', ENT_QUOTES, 'UTF-8'); ?></span></div>
                        <div><span>Cookie</span><span><?php echo htmlspecialchars($row['user_cookies'] ?: '-', ENT_QUOTES, 'UTF-8'); ?></span></div>
                        <div><span>Browser</span><span><?php echo htmlspecialchars($row['browser_agent'] ?: '-', ENT_QUOTES, 'UTF-8'); ?></span></div>
                        <div><span>Referrer</span><span><?php echo htmlspecialchars($row['referrer'] ?: '-', ENT_QUOTES, 'UTF-8'); ?></span></div>
                    </td>
                    <td class="ad-title">
                        <?php echo htmlspecialchars($row['title_ads'] ?: '-', ENT_QUOTES, 'UTF-8'); ?><br>
                        <?php if (!empty($row['landingpage_ads'])): ?><a class="btn btn-sm btn-outline-primary mt-2" href="<?php echo htmlspecialchars($row['landingpage_ads'], ENT_QUOTES, 'UTF-8'); ?>" target="_blank" rel="noopener noreferrer">Buka Landing Page</a><?php endif; ?>
                    </td>
                    <td class="click-time"><?php echo htmlspecialchars($row['click_time'], ENT_QUOTES, 'UTF-8'); ?></td>
                    <td class="revenue-cell">Rp <?php echo number_format((float) $row['revenue_publishers'], 2, ',', '.'); ?></td>
                </tr>
            <?php endwhile; ?>
            <?php if ($total_rows === 0): ?>
                <tr><td colspan="6" class="text-center text-muted py-4">Belum ada klik valid untuk pengguna ini.</td></tr>
            <?php endif; ?>
    </tbody>
      </table>
     </div>
    <nav aria-label="Page navigation">
        <ul class="pagination justify-content-center">
            <?php if ($page > 1): ?><li class="page-item"><a class="page-link" href="?user_id=<?php echo $user_id; ?>&amp;page=<?php echo $page - 1; ?>">Sebelumnya</a></li><?php endif; ?>
            <?php for ($i = max(1, $page - 2); $i <= min($total_pages, $page + 2); $i++): ?>
                <li class="page-item <?php echo $i === $page ? 'active' : ''; ?>"><a class="page-link" href="?user_id=<?php echo $user_id; ?>&amp;page=<?php echo $i; ?>"><?php echo $i; ?></a></li>
            <?php endfor; ?>
            <?php if ($page < $total_pages): ?><li class="page-item"><a class="page-link" href="?user_id=<?php echo $user_id; ?>&amp;page=<?php echo $page + 1; ?>">Berikutnya</a></li><?php endif; ?>
        </ul>
    </nav>
    </div>
  </div>
</main>

<?php
include("js_toogle.php");
$stmt_clicks->close();
$mysqli->close();
include("footer.php");
?>

</body>
</html>
