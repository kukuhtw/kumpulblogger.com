<?php
session_start();

if (!isset($_SESSION['loggedin'])) {
    header('Location: login.php');
    exit;
}

include('../db.php');

$loginemail_admin = $_SESSION['loginemail_admin'];
$conn = new mysqli($servername_db, $username_db, $password_db, $dbname_db);
if ($conn->connect_error) {
    error_log('Database connection failed: ' . $conn->connect_error);
    exit('Database connection failed.');
}
$conn->set_charset('utf8mb4');

if (empty($_SESSION['writer_quota_csrf'])) {
    $_SESSION['writer_quota_csrf'] = bin2hex(random_bytes(32));
}

$message = '';
$message_type = 'success';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $csrf = isset($_POST['csrf_token']) ? (string) $_POST['csrf_token'] : '';
    if (!hash_equals($_SESSION['writer_quota_csrf'], $csrf)) {
        $message = 'Permintaan tidak valid. Silakan muat ulang halaman.';
        $message_type = 'danger';
    } else {
        $action = isset($_POST['action']) ? (string) $_POST['action'] : '';
        $free_quota = filter_input(INPUT_POST, 'free_quota_articles', FILTER_VALIDATE_INT);
        $paid_quota = filter_input(INPUT_POST, 'paid_quota', FILTER_VALIDATE_INT);
        $valid_until_raw = trim((string) ($_POST['quota_valid_until'] ?? ''));
        $valid_until = $valid_until_raw === '' ? null : $valid_until_raw;
        $parsed_date = $valid_until === null ? null : DateTime::createFromFormat('!Y-m-d', $valid_until);
        $date_valid = $valid_until === null || ($parsed_date !== false && $parsed_date->format('Y-m-d') === $valid_until);

        if ($free_quota === false || $paid_quota === false || $free_quota < 0 || $paid_quota < 0 || !$date_valid) {
            $message = 'Quota harus berupa angka nol atau lebih dan tanggal harus valid.';
            $message_type = 'danger';
        } elseif ($action === 'update') {
            $quota_id = filter_input(INPUT_POST, 'quota_id', FILTER_VALIDATE_INT);
            if (!$quota_id) {
                $message = 'ID quota tidak valid.';
                $message_type = 'danger';
            } else {
                $stmt = $conn->prepare('UPDATE publisher_quota SET free_quota_articles = ?, paid_quota = ?, quota_valid_until = ?, last_updated = NOW() WHERE id = ?');
                $stmt->bind_param('iisi', $free_quota, $paid_quota, $valid_until, $quota_id);
                $stmt->execute();
                $message = $stmt->affected_rows > 0 ? 'Quota blogger berhasil diperbarui.' : 'Tidak ada perubahan pada quota.';
                $stmt->close();
            }
        } elseif ($action === 'add') {
            $pub_id = filter_input(INPUT_POST, 'pub_id', FILTER_VALIDATE_INT);
            if (!$pub_id) {
                $message = 'Pilih blogger yang valid.';
                $message_type = 'danger';
            } else {
                $stmt = $conn->prepare('SELECT ps.id, ps.publishers_local_id, ps.site_name, ps.site_desc FROM publishers_site ps LEFT JOIN publisher_quota pq ON pq.publisher_id = ps.publishers_local_id WHERE ps.id = ? AND ps.internal_blog = 1 AND pq.id IS NULL LIMIT 1');
                $stmt->bind_param('i', $pub_id);
                $stmt->execute();
                $site = $stmt->get_result()->fetch_assoc();
                $stmt->close();

                if (!$site) {
                    $message = 'Blogger tidak ditemukan atau sudah mempunyai quota.';
                    $message_type = 'danger';
                } else {
                    $daily_free_quota = 1; // Kolom legacy; pemeriksaan artikel memakai free_quota_articles.
                    $stmt = $conn->prepare('INSERT INTO publisher_quota (publisher_id, pub_id, daily_free_quota, free_quota_articles, paid_quota, quota_valid_until, username, description, last_updated) VALUES (?, ?, ?, ?, ?, ?, ?, ?, NOW())');
                    $stmt->bind_param('iiiiisss', $site['publishers_local_id'], $site['id'], $daily_free_quota, $free_quota, $paid_quota, $valid_until, $site['site_name'], $site['site_desc']);
                    $stmt->execute();
                    $message = 'Quota blogger berhasil ditambahkan.';
                    $stmt->close();
                }
            }
        }
    }
}

$search = trim((string) ($_GET['search'] ?? ''));
$like = '%' . $search . '%';
$allowed_sorts = [
    'quota' => 'total_quota',
    'used' => 'used_quota',
    'remaining' => 'remaining_quota',
];
$sort = isset($allowed_sorts[$_GET['sort'] ?? '']) ? (string) $_GET['sort'] : '';
$order = strtolower((string) ($_GET['order'] ?? '')) === 'asc' ? 'asc' : 'desc';
$sql = "SELECT pq.id, pq.publisher_id, pq.pub_id, pq.free_quota_articles, pq.paid_quota,
               pq.quota_valid_until, pq.username, pq.description, pq.last_updated,
               COALESCE(mu.realname, '') AS realname, COALESCE(mu.loginemail, '') AS loginemail,
               COALESCE(a.used_quota, 0) AS used_quota,
               (pq.free_quota_articles + pq.paid_quota) AS total_quota,
               GREATEST(0, (pq.free_quota_articles + pq.paid_quota) - COALESCE(a.used_quota, 0)) AS remaining_quota
        FROM publisher_quota pq
        LEFT JOIN msusers mu ON mu.id = pq.publisher_id
        LEFT JOIN (SELECT publishers_local_id, COUNT(*) AS used_quota FROM articles GROUP BY publishers_local_id) a
               ON a.publishers_local_id = pq.publisher_id";
if ($search !== '') {
    $sql .= ' WHERE pq.username LIKE ? OR mu.realname LIKE ? OR mu.loginemail LIKE ? OR CAST(pq.publisher_id AS CHAR) LIKE ?';
}
if ($sort !== '') {
    $sql .= ' ORDER BY ' . $allowed_sorts[$sort] . ' ' . strtoupper($order) . ', pq.id DESC';
} else {
    $sql .= ' ORDER BY pq.last_updated DESC, pq.id DESC';
}
$stmt = $conn->prepare($sql);
if ($search !== '') {
    $stmt->bind_param('ssss', $like, $like, $like, $like);
}
$stmt->execute();
$quotas = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
$stmt->close();

$available = $conn->query("SELECT ps.id AS pub_id, ps.publishers_local_id, ps.site_name,
                                  COALESCE(mu.realname, '') AS realname, COALESCE(mu.loginemail, '') AS loginemail
                           FROM publishers_site ps
                           LEFT JOIN msusers mu ON mu.id = ps.publishers_local_id
                           LEFT JOIN publisher_quota pq ON pq.publisher_id = ps.publishers_local_id
                           WHERE ps.internal_blog = 1 AND pq.id IS NULL
                           ORDER BY ps.site_name ASC")->fetch_all(MYSQLI_ASSOC);
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Quota Menulis Blogger</title>
    <?php include('style_toogle.php'); ?>
    <style>
        .quota-toolbar { display:flex; gap:1rem; justify-content:space-between; align-items:end; }
        .quota-search { display:flex; max-width:520px; width:100%; }
        .quota-search input { border-radius:.4rem 0 0 .4rem; }
        .quota-search button { border-radius:0 .4rem .4rem 0; }
        .quota-number { font-weight:700; }
        .sortable-link { display:inline-flex; align-items:center; gap:.35rem; color:inherit; text-decoration:none; }
        .sortable-link:hover { color:#0d6efd; text-decoration:none; }
        .sort-indicator { color:#0d6efd; font-size:.7rem; }
        .quota-editor { display:grid; grid-template-columns:90px 90px 150px auto; gap:.4rem; align-items:center; min-width:450px; }
        @media (max-width:767.98px) { .quota-toolbar{align-items:stretch;flex-direction:column}.quota-editor{grid-template-columns:1fr;min-width:220px} }
    </style>
</head>
<body>
<div class="admin-navbar">
    <a class="brand" href="dashboard_admin.php">Admin Dashboard</a>
    <a href="logout.php"><i class="fas fa-sign-out-alt mr-1"></i> Logout</a>
</div>
<?php include('sidebar_menu.php'); ?>

<main class="admin-main" id="mainContent">
    <div class="quota-toolbar mb-4">
        <div>
            <h1 class="page-title">Quota Menulis Blogger</h1>
            <p class="page-subtitle">Quota total = gratis + berbayar. Pemakaian dihitung dari seluruh artikel yang pernah dibuat.</p>
        </div>
        <form method="get" class="quota-search">
            <?php if ($sort !== ''): ?><input type="hidden" name="sort" value="<?php echo htmlspecialchars($sort, ENT_QUOTES, 'UTF-8'); ?>"><input type="hidden" name="order" value="<?php echo htmlspecialchars($order, ENT_QUOTES, 'UTF-8'); ?>"><?php endif; ?>
            <input class="form-control" type="search" name="search" placeholder="Cari blogger, email, atau ID" value="<?php echo htmlspecialchars($search, ENT_QUOTES, 'UTF-8'); ?>">
            <button class="btn btn-primary" type="submit"><i class="fas fa-search"></i> Cari</button>
        </form>
    </div>

    <?php if ($message !== ''): ?><div class="alert alert-<?php echo $message_type; ?>"><?php echo htmlspecialchars($message, ENT_QUOTES, 'UTF-8'); ?></div><?php endif; ?>

    <div class="card mb-4">
        <div class="card-header">Tambah Quota Blogger</div>
        <div class="card-body">
            <?php if ($available): ?>
            <form method="post" class="form-row align-items-end">
                <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($_SESSION['writer_quota_csrf'], ENT_QUOTES, 'UTF-8'); ?>">
                <input type="hidden" name="action" value="add">
                <div class="form-group col-md-5"><label>Blogger</label><select name="pub_id" class="form-control" required><option value="">Pilih blogger</option><?php foreach ($available as $site): ?><option value="<?php echo (int) $site['pub_id']; ?>"><?php echo htmlspecialchars($site['site_name'] . ' — ' . ($site['realname'] ?: $site['loginemail']) . ' (User #' . $site['publishers_local_id'] . ')', ENT_QUOTES, 'UTF-8'); ?></option><?php endforeach; ?></select></div>
                <div class="form-group col-md-2"><label>Quota gratis</label><input type="number" min="0" name="free_quota_articles" value="5" class="form-control" required></div>
                <div class="form-group col-md-2"><label>Quota berbayar</label><input type="number" min="0" name="paid_quota" value="0" class="form-control" required></div>
                <div class="form-group col-md-2"><label>Berlaku sampai</label><input type="date" name="quota_valid_until" class="form-control"></div>
                <div class="form-group col-md-1"><button class="btn btn-success btn-block" type="submit">Tambah</button></div>
            </form>
            <?php else: ?><p class="text-muted mb-0">Semua blogger internal sudah mempunyai data quota.</p><?php endif; ?>
        </div>
    </div>

    <div class="card">
        <div class="card-header">Daftar Quota (<?php echo count($quotas); ?>)</div>
        <div class="card-body"><div class="table-responsive">
            <table class="table table-hover">
                <thead><tr><th>Blogger</th>
                    <?php foreach (['quota' => 'Quota', 'used' => 'Terpakai', 'remaining' => 'Sisa'] as $sort_key => $sort_label):
                        $next_order = ($sort === $sort_key && $order === 'desc') ? 'asc' : 'desc'; ?>
                    <th><a class="sortable-link" href="?search=<?php echo urlencode($search); ?>&amp;sort=<?php echo $sort_key; ?>&amp;order=<?php echo $next_order; ?>"><?php echo $sort_label; ?><?php if ($sort === $sort_key): ?><span class="sort-indicator"><?php echo $order === 'asc' ? '&#9650;' : '&#9660;'; ?></span><?php endif; ?></a></th>
                    <?php endforeach; ?>
                    <th>Edit quota</th></tr></thead>
                <tbody>
                <?php foreach ($quotas as $row): $total = (int)$row['free_quota_articles'] + (int)$row['paid_quota']; $remaining = max(0, $total - (int)$row['used_quota']); ?>
                    <tr>
                        <td><strong><?php echo htmlspecialchars($row['username'] ?: $row['realname'], ENT_QUOTES, 'UTF-8'); ?></strong><br><small class="text-muted"><?php echo htmlspecialchars($row['loginemail'], ENT_QUOTES, 'UTF-8'); ?> · User #<?php echo (int)$row['publisher_id']; ?> · Pub #<?php echo (int)$row['pub_id']; ?></small></td>
                        <td><span class="quota-number"><?php echo $total; ?></span><br><small class="text-muted"><?php echo (int)$row['free_quota_articles']; ?> gratis + <?php echo (int)$row['paid_quota']; ?> berbayar</small></td>
                        <td class="quota-number"><?php echo (int)$row['used_quota']; ?></td>
                        <td><span class="badge badge-<?php echo $remaining > 0 ? 'success' : 'danger'; ?>"><?php echo $remaining; ?></span></td>
                        <td>
                            <form method="post" class="quota-editor">
                                <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($_SESSION['writer_quota_csrf'], ENT_QUOTES, 'UTF-8'); ?>"><input type="hidden" name="action" value="update"><input type="hidden" name="quota_id" value="<?php echo (int)$row['id']; ?>">
                                <input class="form-control form-control-sm" title="Quota gratis" aria-label="Quota gratis" type="number" min="0" name="free_quota_articles" value="<?php echo (int)$row['free_quota_articles']; ?>" required>
                                <input class="form-control form-control-sm" title="Quota berbayar" aria-label="Quota berbayar" type="number" min="0" name="paid_quota" value="<?php echo (int)$row['paid_quota']; ?>" required>
                                <input class="form-control form-control-sm" title="Berlaku sampai" aria-label="Berlaku sampai" type="date" name="quota_valid_until" value="<?php echo htmlspecialchars((string)$row['quota_valid_until'], ENT_QUOTES, 'UTF-8'); ?>">
                                <button class="btn btn-sm btn-outline-primary" type="submit"><i class="fas fa-save"></i> Simpan</button>
                            </form>
                        </td>
                    </tr>
                <?php endforeach; ?>
                <?php if (!$quotas): ?><tr><td colspan="5" class="text-center text-muted py-4">Data quota tidak ditemukan.</td></tr><?php endif; ?>
                </tbody>
            </table>
        </div></div>
    </div>
</main>
<?php $conn->close(); include('footer.php'); include('js_toogle.php'); ?>
</body>
</html>
