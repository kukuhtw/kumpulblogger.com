<?php
/*
admin/manage_users.php
*/
// Start session
session_start();

// Check if the user is logged in
if (!isset($_SESSION['loggedin'])) {
    // If not logged in, redirect to login page
    header('Location: login.php');
    exit;
}

// Include the database connection
include("../db.php");
include("function_admin.php");
include("../function_publisher.php");

$loginemail_admin = $_SESSION['loginemail_admin'];
// Set default page number and items per page
$limit = 10;
$page = max(1, isset($_GET['page']) ? (int)$_GET['page'] : 1);
$offset = ($page - 1) * $limit;

// Search functionality
$search_query = "";
if (isset($_GET['search'])) {
    $search_query = trim($_GET['search']);
}

// Whitelist sortable columns and directions before using them in SQL.
$allowed_sorts = [
    'total_revenue' => 'total_revenue',
    'total_paid' => 'total_paid',
    'total_unpaid' => 'total_unpaid',
];
$sort = isset($allowed_sorts[$_GET['sort'] ?? '']) ? $_GET['sort'] : 'total_unpaid';
$order = strtolower($_GET['order'] ?? '') === 'asc' ? 'asc' : 'desc';
$sort_column = $allowed_sorts[$sort];
$sort_direction = strtoupper($order);

// Database connection using MySQLi
$conn = new mysqli($servername_db, $username_db, $password_db, $dbname_db);
if ($conn->connect_error) {
    error_log("Database connection failed: " . $conn->connect_error);
    exit("Database connection failed.");
}

// Build search SQL query
$search_sql = "";
if ($search_query != "") {
    $search_sql = " WHERE loginemail LIKE ? OR realname LIKE ? OR whatsapp LIKE ?";
}

// Get total number of users
$sql = "SELECT COUNT(*) AS total FROM msusers" . $search_sql;
$stmt = $conn->prepare($sql);
if ($search_query != "") {
    $like_search = '%' . $search_query . '%';
    $stmt->bind_param("sss", $like_search, $like_search, $like_search);
}
$stmt->execute();
$result = $stmt->get_result();
$total_users = $result->fetch_assoc()['total'];

// Fetch users and calculate the revenue summary shown in the table.
$sql = "SELECT msusers.*,
            (COALESCE(local_revenue_paid, 0) + COALESCE(partner_revenue_paid, 0)) AS total_paid,
            (COALESCE(local_revenue_unpaid, 0) + COALESCE(partner_revenue_unpaid, 0)) AS total_unpaid,
            (COALESCE(local_revenue_paid, 0) + COALESCE(partner_revenue_paid, 0)
                + COALESCE(local_revenue_unpaid, 0) + COALESCE(partner_revenue_unpaid, 0)) AS total_revenue
        FROM msusers" . $search_sql . " ORDER BY " . $sort_column . " " . $sort_direction . ", id DESC LIMIT ? OFFSET ?";
$stmt = $conn->prepare($sql);
if ($search_query != "") {
    $stmt->bind_param("sssii", $like_search, $like_search, $like_search, $limit, $offset);
} else {
    $stmt->bind_param("ii", $limit, $offset);
}
$stmt->execute();
$result = $stmt->get_result();

// Calculate total pages
$total_pages = ceil($total_users / $limit);

// Close the connection
$stmt->close();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>User List</title>
    <?php include("style_toogle.php"); ?>
    <style>
        .users-toolbar { display: flex; align-items: center; justify-content: space-between; gap: 1rem; }
        .users-search { display: flex; width: min(100%, 760px); }
        .users-search .search-input { border-radius: .45rem 0 0 .45rem; }
        .users-search .sort-select, .users-search .order-select { width: auto; border-left: 0; border-radius: 0; }
        .users-search .btn { border-radius: 0 .45rem .45rem 0; white-space: nowrap; }
        .sortable-link { display: inline-flex; align-items: center; gap: .35rem; color: inherit; text-decoration: none; }
        .sortable-link:hover { color: #0d6efd; text-decoration: none; }
        .sort-indicator { color: #0d6efd; font-size: .7rem; }
        .user-name { color: #25313c; font-weight: 600; }
        .user-email { color: #6c757d; font-size: .82rem; overflow-wrap: anywhere; }
        .user-meta { margin-top: .35rem; color: #6c757d; font-size: .78rem; }
        .revenue-value { display: block; font-size: .95rem; font-weight: 700; white-space: nowrap; }
        .revenue-paid { color: #198754; }
        .revenue-unpaid { color: #dc3545; }
        .revenue-total { color: #0d6efd; }
        .revenue-detail { display: block; margin-top: .25rem; color: #6c757d; font-size: .72rem; line-height: 1.45; white-space: nowrap; }
        .account-info { font-size: .82rem; line-height: 1.55; }
        .account-info strong { color: #374151; }
        .users-table td { vertical-align: middle; }
        .users-table .col-id { width: 64px; }
        .users-pagination { padding-top: 1rem; }
        @media (max-width: 767.98px) {
            .users-toolbar { align-items: stretch; flex-direction: column; }
            .users-search { width: 100%; }
            .users-search { display: grid; grid-template-columns: 1fr 1fr; }
            .users-search .search-input { grid-column: 1 / -1; border-radius: .45rem; }
            .users-search .sort-select, .users-search .order-select { width: 100%; border: 1px solid #ced4da; border-radius: .45rem; }
            .users-search .btn { grid-column: 1 / -1; border-radius: .45rem; }
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

    <div class="users-toolbar mb-4">
        <div>
            <h1 class="page-title">Daftar Pengguna</h1>
            <p class="page-subtitle"><?php echo number_format($total_users); ?> pengguna ditemukan.</p>
        </div>
        <form method="GET" action="manage_users.php" class="users-search">
            <input type="search" name="search" class="form-control search-input" placeholder="Cari email, nama, atau WhatsApp" aria-label="Cari pengguna" value="<?php echo htmlspecialchars($search_query, ENT_QUOTES, 'UTF-8'); ?>">
            <select name="sort" class="form-control sort-select" aria-label="Urutkan berdasarkan">
                <option value="total_revenue" <?php echo $sort === 'total_revenue' ? 'selected' : ''; ?>>Total Revenue</option>
                <option value="total_paid" <?php echo $sort === 'total_paid' ? 'selected' : ''; ?>>Total Paid</option>
                <option value="total_unpaid" <?php echo $sort === 'total_unpaid' ? 'selected' : ''; ?>>Total Unpaid</option>
            </select>
            <select name="order" class="form-control order-select" aria-label="Urutan sortir">
                <option value="desc" <?php echo $order === 'desc' ? 'selected' : ''; ?>>Terbesar</option>
                <option value="asc" <?php echo $order === 'asc' ? 'selected' : ''; ?>>Terkecil</option>
            </select>
            <button type="submit" class="btn btn-primary"><i class="fas fa-search mr-1"></i> Terapkan</button>
        </form>
    </div>

    <div class="card">
        <div class="card-header">Data Pengguna</div>
        <div class="card-body">
          <div class="table-responsive">
            <table class="table table-hover users-table">
                <thead>
                    <tr>
                        <th class="col-id">ID</th>
                        <th>Pengguna</th>
                        <th>WhatsApp</th>
                        <?php
                        $sort_headers = [
                            'total_paid' => 'Total Paid',
                            'total_unpaid' => 'Total Unpaid',
                            'total_revenue' => 'Total Revenue',
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
                        <th>Rekening</th>
                        <th>Login Terakhir</th>
                        <th>Aksi</th>
                    </tr>
                </thead>
                <tbody>
                    <?php while ($row = $result->fetch_assoc()): ?>
                    <tr>
                        <td><?php echo htmlspecialchars($row['id']); ?></td>
                        <td>
                            <span class="user-name"><?php echo htmlspecialchars($row['realname'] ?: '-', ENT_QUOTES, 'UTF-8'); ?></span>
                            <span class="user-email"><?php echo htmlspecialchars($row['loginemail'], ENT_QUOTES, 'UTF-8'); ?></span>
                        </td>
                        <td>
                            <?php echo htmlspecialchars($row['whatsapp'] ?: '-', ENT_QUOTES, 'UTF-8'); ?>
                        </td>
                        <td>
                            <span class="revenue-value revenue-paid">Rp <?php echo number_format((float)$row['total_paid'], 0, ',', '.'); ?></span>
                            <span class="revenue-detail">Lokal Rp <?php echo number_format((float)$row['local_revenue_paid'], 0, ',', '.'); ?><br>Partner Rp <?php echo number_format((float)$row['partner_revenue_paid'], 0, ',', '.'); ?></span>
                        </td>
                        <td>
                            <span class="revenue-value revenue-unpaid">Rp <?php echo number_format((float)$row['total_unpaid'], 0, ',', '.'); ?></span>
                            <span class="revenue-detail">Lokal Rp <?php echo number_format((float)$row['local_revenue_unpaid'], 0, ',', '.'); ?><br>Partner Rp <?php echo number_format((float)$row['partner_revenue_unpaid'], 0, ',', '.'); ?></span>
                        </td>
                        <td><span class="revenue-value revenue-total">Rp <?php echo number_format((float)$row['total_revenue'], 0, ',', '.'); ?></span></td>
                        <td class="account-info">
                            <strong><?php echo htmlspecialchars($row['bank'] ?: '-', ENT_QUOTES, 'UTF-8'); ?></strong><br>
                            <?php echo htmlspecialchars($row['account_name'] ?: '-', ENT_QUOTES, 'UTF-8'); ?><br>
                            <?php echo htmlspecialchars($row['account_number'] ?: '-', ENT_QUOTES, 'UTF-8'); ?>
                        </td>
                        <td><?php echo htmlspecialchars($row['last_login'] ?: '-', ENT_QUOTES, 'UTF-8'); ?></td>
                        <td>
                            <a class="btn btn-sm btn-outline-primary" href="rekap_user_local_click.php?user_id=<?php echo urlencode($row['id']); ?>">Detail</a>
                        </td>
                    </tr>
                    <?php endwhile; ?>
                    <?php if ($total_users == 0): ?>
                    <tr><td colspan="9" class="text-center text-muted py-4">Pengguna tidak ditemukan.</td></tr>
                    <?php endif; ?>
                </tbody>
            </table>
          </div>

            <!-- Pagination Links -->
            <nav class="users-pagination" aria-label="Navigasi halaman pengguna">
                <ul class="pagination">
                    <?php if ($page > 1): ?>
                        <li class="page-item">
                            <a class="page-link" href="?page=<?php echo $page - 1; ?>&amp;search=<?php echo urlencode($search_query); ?>&amp;sort=<?php echo $sort; ?>&amp;order=<?php echo $order; ?>">Sebelumnya</a>
                        </li>
                    <?php endif; ?>

                    <?php for ($i = 1; $i <= $total_pages; $i++): ?>
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
$conn->close();
include("footer.php"); 
include("js_toogle.php"); 
?>

</body>
</html>
