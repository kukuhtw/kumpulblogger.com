<?php
// list_payment_pubs_local.php
session_start();
// Include the database connection
include("../db.php");
include("function_admin.php");

// Check if the user is logged in
if (!isset($_SESSION['loggedin'])) {
    // If not logged in, redirect to login page
    header('Location: login.php');
    exit;
}

// Fetch login email from session
$loginemail_admin = $_SESSION['loginemail_admin'];

// Database connection using MySQLi
$mysqli = new mysqli($servername_db, $username_db, $password_db, $dbname_db);
if ($mysqli->connect_error) {
    error_log("Database connection failed: " . $mysqli->connect_error);
    exit("Database connection failed.");
}

// Pagination settings
$limit = 10; // Number of records per page
$page = max(1, isset($_GET['page']) ? (int) $_GET['page'] : 1);
$offset = ($page - 1) * $limit; // Calculate the offset for the SQL query
$search_query = trim($_GET['search'] ?? '');
$search_sql = $search_query !== '' ? " WHERE email_pubs LIKE ? OR payment_description LIKE ?" : '';

// Prepare the query to fetch data from payment_local_pubs with limit and offset
$sql = "SELECT * FROM payment_local_pubs" . $search_sql . " ORDER BY payment_date DESC LIMIT ?, ?";
$stmt = $mysqli->prepare($sql);
if ($search_query !== '') {
    $like_search = '%' . $search_query . '%';
    $stmt->bind_param("ssii", $like_search, $like_search, $offset, $limit);
} else {
    $stmt->bind_param("ii", $offset, $limit);
}
$stmt->execute();
$result = $stmt->get_result();

// Count the total number of records in the table for pagination
$count_sql = "SELECT COUNT(*) AS total, COALESCE(SUM(nominal), 0) AS total_nominal FROM payment_local_pubs" . $search_sql;
$count_stmt = $mysqli->prepare($count_sql);
if ($search_query !== '') {
    $count_stmt->bind_param("ss", $like_search, $like_search);
}
$count_stmt->execute();
$count_result = $count_stmt->get_result();
$count_row = $count_result->fetch_assoc();
$total_records = (int) $count_row['total'];
$total_nominal = (float) $count_row['total_nominal'];
$total_pages = ceil($total_records / $limit); // Calculate total number of pages
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Pembayaran Publisher Lokal</title>
    <?php include("style_toogle.php"); ?>
    <style>
        .payment-toolbar { display: flex; align-items: end; justify-content: space-between; gap: 1rem; }
        .payment-search { display: flex; width: min(100%, 470px); }
        .payment-search .form-control { border-radius: .45rem 0 0 .45rem; }
        .payment-search .btn { border-radius: 0 .45rem .45rem 0; }
        .summary-grid { display: grid; grid-template-columns: repeat(2, minmax(0, 1fr)); gap: 1rem; margin-bottom: 1rem; }
        .summary-card { padding: 1rem 1.15rem; border-radius: .7rem; background: #fff; box-shadow: 0 3px 14px rgba(31,41,55,.08); }
        .summary-label { color: #6c757d; font-size: .78rem; font-weight: 600; letter-spacing: .035em; text-transform: uppercase; }
        .summary-value { margin-top: .2rem; color: #25313c; font-size: 1.35rem; font-weight: 700; }
        .payment-email { font-weight: 600; overflow-wrap: anywhere; }
        .payment-description { min-width: 220px; color: #52606d; white-space: normal; }
        .payment-amount { color: #198754; font-weight: 700; white-space: nowrap; }
        .payment-date { white-space: nowrap; }
        @media (max-width: 767.98px) {
            .payment-toolbar { align-items: stretch; flex-direction: column; }
            .payment-search { width: 100%; }
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
    <div class="payment-toolbar mb-4">
        <div>
            <h1 class="page-title">Pembayaran Publisher Lokal</h1>
            <p class="page-subtitle">Riwayat pembayaran revenue kepada publisher lokal.</p>
        </div>
        <form class="payment-search" method="get" action="list_payment_pubs_local.php">
            <input class="form-control" type="search" name="search" placeholder="Cari email atau keterangan" value="<?php echo htmlspecialchars($search_query, ENT_QUOTES, 'UTF-8'); ?>">
            <button class="btn btn-primary" type="submit"><i class="fas fa-search mr-1"></i> Cari</button>
        </form>
    </div>

    <div class="summary-grid">
        <div class="summary-card">
            <div class="summary-label">Total Pembayaran</div>
            <div class="summary-value">Rp <?php echo number_format($total_nominal, 0, ',', '.'); ?></div>
        </div>
        <div class="summary-card">
            <div class="summary-label">Jumlah Transaksi</div>
            <div class="summary-value"><?php echo number_format($total_records, 0, ',', '.'); ?></div>
        </div>
    </div>

    <div class="card">
      <div class="card-header">Data Pembayaran</div>
      <div class="card-body">
       <div class="table-responsive">
        <table class="table table-hover">
        <thead class="table-light">
            <tr>
                <th>ID</th>
                <th>Email</th>
                <th>Nominal</th>
                <th>Keterangan</th>
                <th>Tanggal Pembayaran</th>
            </tr>
        </thead>
        <tbody>
        <?php if ($result->num_rows > 0): ?>
            <?php while ($row = $result->fetch_assoc()): ?>
                <tr>
                    <td><?php echo (int) $row['id']; ?></td>
                    <td class="payment-email"><?php echo htmlspecialchars($row['email_pubs'], ENT_QUOTES, 'UTF-8'); ?></td>
                    <td class="payment-amount">Rp <?php echo number_format((float) $row['nominal'], 0, ',', '.'); ?></td>
                    <td class="payment-description"><?php echo htmlspecialchars($row['payment_description'] ?: '-', ENT_QUOTES, 'UTF-8'); ?></td>
                    <td class="payment-date"><?php echo htmlspecialchars($row['payment_date'], ENT_QUOTES, 'UTF-8'); ?></td>
                </tr>
            <?php endwhile; ?>
        <?php else: ?>
            <tr>
                <td colspan="5" class="text-center text-muted py-4">Data pembayaran tidak ditemukan.</td>
            </tr>
        <?php endif; ?>
        </tbody>
        </table>
       </div>

    <!-- Pagination -->
    <nav>
        <ul class="pagination justify-content-center">
            <?php if ($total_pages > 1):
                $start_page = max(1, $page - 2);
                $end_page = min($total_pages, $page + 2);
            ?>
                <?php if ($page > 1): ?>
                    <li class="page-item"><a class="page-link" href="?page=<?php echo $page - 1; ?>&amp;search=<?php echo urlencode($search_query); ?>">Sebelumnya</a></li>
                <?php endif; ?>
                <?php for ($i = $start_page; $i <= $end_page; $i++): ?>
                    <li class="page-item <?php echo ($i == $page) ? 'active' : ''; ?>">
                        <a class="page-link" href="?page=<?php echo $i; ?>&amp;search=<?php echo urlencode($search_query); ?>"><?php echo $i; ?></a>
                    </li>
                <?php endfor; ?>
                <?php if ($page < $total_pages): ?>
                    <li class="page-item"><a class="page-link" href="?page=<?php echo $page + 1; ?>&amp;search=<?php echo urlencode($search_query); ?>">Berikutnya</a></li>
                <?php endif; ?>
            <?php endif; ?>
        </ul>
    </nav>
      </div>
    </div>
</main>

<?php
// Close the database connection
$stmt->close();
$count_stmt->close();
$mysqli->close();

include("footer.php");

include("js_toogle.php"); 
?>
</body>
</html>
