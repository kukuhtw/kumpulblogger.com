<?php
// view_advertiser_list.php

include("db.php");
include("function.php");

session_start();

// Database connection using MySQLi
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

// Get user ID from session
$user_id = $_SESSION['user_id'];

// Get provider domain URL (you might have a function to get this)
$this_providers_id = 1;
//$this_providers_domain_url = get_providers_domain_url($conn, $this_providers_id);
$this_providers_domain_url = get_providers_domain_url_json("providers_data.json", 1);


// Pagination and Searching Setup
$limit = 20; // Maximum results per page
$page = max(1, isset($_GET['page']) ? (int)$_GET['page'] : 1);
$offset = ($page - 1) * $limit; // Offset for the SQL query

$search_query = trim($_GET['search'] ?? '');

// Search query with pagination, searching, and sorting
$stmt = $conn->prepare("SELECT id, local_ads_id, title_ads, description_ads, landingpage_ads, image_url,
                               budget_per_click_textads, ispublished, published_date, regdate, is_expired, expired_date
                        FROM advertisers_ads
                        WHERE ispublished = 1 AND (title_ads LIKE ? OR description_ads LIKE ?)
                        ORDER BY published_date DESC, regdate DESC, id DESC
                        LIMIT ? OFFSET ?");

$search_param = "%" . $search_query . "%";
$stmt->bind_param("ssii", $search_param, $search_param, $limit, $offset);
$stmt->execute();
$result = $stmt->get_result();

// Get total records count for pagination
$count_stmt = $conn->prepare("SELECT COUNT(*) AS total FROM advertisers_ads WHERE ispublished = 1 AND (title_ads LIKE ? OR description_ads LIKE ?)");
$count_stmt->bind_param("ss", $search_param, $search_param);
$count_stmt->execute();
$count_result = $count_stmt->get_result();
$total_records = $count_result->fetch_assoc()['total'];
$total_pages = ceil($total_records / $limit);

// Close the connection
$conn->close();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Daftar Iklan Terbaru</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        :root { --brand:#087f5b; --ink:#26313c; --muted:#6c757d; --bg:#f4f7f6; }
        body { margin:0; background:var(--bg); color:var(--ink); }
        .page-shell { max-width:1240px; }
        .page-heading { display:flex; align-items:end; justify-content:space-between; gap:1rem; }
        .page-title { margin:0; font-size:1.65rem; font-weight:800; }
        .page-subtitle { margin:.3rem 0 0; color:var(--muted); }
        .search-panel { padding:1rem; border-radius:.8rem; background:#fff; box-shadow:0 3px 14px rgba(31,65,54,.08); }
        .ad-card { overflow:hidden; border:0; border-radius:.9rem; box-shadow:0 3px 14px rgba(31,65,54,.08); transition:transform .2s ease,box-shadow .2s ease; }
        .ad-card:hover { transform:translateY(-3px); box-shadow:0 8px 24px rgba(31,65,54,.14); }
        .ad-image { width:100%; height:210px; object-fit:cover; background:#eef2f0; }
        .ad-card .card-body { display:flex; flex-direction:column; padding:1.15rem; }
        .ad-title { margin:.7rem 0 .55rem; font-size:1.08rem; font-weight:800; line-height:1.4; }
        .ad-description { display:-webkit-box; overflow:hidden; color:#5f706a; font-size:.88rem; line-height:1.6; -webkit-box-orient:vertical; -webkit-line-clamp:3; }
        .ad-meta { display:grid; gap:.4rem; margin:1rem 0; padding:.75rem; border-radius:.65rem; background:#f5f8f7; font-size:.8rem; }
        .ad-meta-row { display:flex; justify-content:space-between; gap:.8rem; }
        .ad-meta-row span { color:var(--muted); }
        .ad-budget { color:#198754; font-weight:800; white-space:nowrap; }
        .landing-button { margin-top:auto; }
        .pagination { flex-wrap:wrap; gap:.25rem; }
        .pagination .page-link { border-radius:.45rem!important; }
        @media(max-width:767.98px) { .page-heading { align-items:stretch; flex-direction:column; } }
    </style>
</head>
<body>
    <div class="container page-shell py-3 py-md-4">
        <?php include("main_menu.php") ?>
        <?php include("include_publisher_menu.php") ?>

        <header class="page-heading mt-4 mb-3"><div><h1 class="page-title">Iklan Lokal Terbaru</h1><p class="page-subtitle"><?php echo number_format($total_records, 0, ',', '.'); ?> iklan aktif ditemukan, terbaru ditampilkan lebih dahulu.</p></div></header>

        <!-- Search Form -->
        <form method="GET" action="" class="search-panel mb-4">
            <div class="input-group">
                <input type="search" class="form-control" name="search" placeholder="Cari judul atau deskripsi iklan..." value="<?php echo htmlspecialchars($search_query, ENT_QUOTES, 'UTF-8'); ?>">
                <button type="submit" class="btn btn-primary">Cari</button>
            </div>
        </form>


        



<?php if ($result->num_rows > 0): ?>
            <div class="row g-4">
                <?php while ($row = $result->fetch_assoc()): ?>
                    <div class="col-12 col-md-6 col-xl-4">
                        <div class="card ad-card h-100">
                            <img src="<?php echo htmlspecialchars($row['image_url'], ENT_QUOTES, 'UTF-8'); ?>" class="ad-image" alt="<?php echo htmlspecialchars($row['title_ads'], ENT_QUOTES, 'UTF-8'); ?>" loading="lazy">
                            <div class="card-body">
                                <div class="d-flex align-items-center justify-content-between gap-2"><small class="text-muted">ID #<?php echo (int)$row['local_ads_id']; ?></small><span class="badge <?php echo $row['is_expired'] ? 'text-bg-secondary' : 'text-bg-success'; ?>"><?php echo $row['is_expired'] ? 'Berakhir' : 'Aktif'; ?></span></div>
                                <h2 class="ad-title"><?php echo htmlspecialchars($row['title_ads'], ENT_QUOTES, 'UTF-8'); ?></h2>
                                <p class="ad-description"><?php echo htmlspecialchars($row['description_ads'], ENT_QUOTES, 'UTF-8'); ?></p>
                                <div class="ad-meta"><div class="ad-meta-row"><span>Biaya per klik</span><strong class="ad-budget">Rp <?php echo number_format((float)$row['budget_per_click_textads'], 0, ',', '.'); ?></strong></div><div class="ad-meta-row"><span>Dipublikasikan</span><strong><?php echo htmlspecialchars($row['published_date'], ENT_QUOTES, 'UTF-8'); ?></strong></div><?php if ($row['is_expired']): ?><div class="ad-meta-row"><span>Berakhir</span><strong><?php echo htmlspecialchars($row['expired_date'], ENT_QUOTES, 'UTF-8'); ?></strong></div><?php endif; ?></div>
                                <a class="btn btn-outline-primary landing-button" href="<?php echo htmlspecialchars($row['landingpage_ads'], ENT_QUOTES, 'UTF-8'); ?>" target="_blank" rel="noopener noreferrer">Kunjungi Landing Page</a>
                            </div>
                        </div>
                    </div>
                <?php endwhile; ?>
            </div>

            <!-- Pagination Links -->
            <nav class="mt-4" aria-label="Navigasi daftar iklan">
                <ul class="pagination justify-content-center">
                    <?php if ($page > 1): ?><li class="page-item"><a class="page-link" href="?page=<?php echo $page - 1; ?>&amp;search=<?php echo urlencode($search_query); ?>">Sebelumnya</a></li><?php endif; ?>
                    <?php for ($i = max(1, $page - 2); $i <= min($total_pages, $page + 2); $i++): ?>
                        <li class="page-item <?php echo $i == $page ? 'active' : ''; ?>">
                            <a class="page-link" href="?page=<?php echo $i; ?>&amp;search=<?php echo urlencode($search_query); ?>">
                                <?php echo $i; ?>
                            </a>
                        </li>
                    <?php endfor; ?>
                    <?php if ($page < $total_pages): ?><li class="page-item"><a class="page-link" href="?page=<?php echo $page + 1; ?>&amp;search=<?php echo urlencode($search_query); ?>">Berikutnya</a></li><?php endif; ?>
                </ul>
            </nav>
        <?php else: ?>
            <div class="text-center text-muted py-5">Iklan tidak ditemukan.</div>
        <?php endif; ?>
    </div>

    <!-- Include Bootstrap JS (optional) -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>

        
</body>
</html>
