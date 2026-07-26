<?php
// DATA/list_publisher_site.php

include("../db.php"); // Koneksi database
include("../function.php");

// Create a connection to the MySQL database
$mysqli = new mysqli($servername_db, $username_db, $password_db, $dbname_db);

// Check the connection
if ($mysqli->connect_error) {
    die("Connection failed: " . $mysqli->connect_error);
}

// Pencarian
$search = isset($_GET['search']) ? $_GET['search'] : '';

// Pagination
$page = max(1, isset($_GET['page']) ? (int)$_GET['page'] : 1);
$limit = 10; // Jumlah item per halaman
$offset = ($page - 1) * $limit;

// Prepare the SQL statement with placeholders
$sql = "SELECT * FROM publishers_site 
        WHERE site_name LIKE ? OR site_domain LIKE ? OR site_desc LIKE ?
        ORDER BY regdate DESC 
        LIMIT ? OFFSET ?";

// Prepare the statement
$stmt = $mysqli->prepare($sql);

// Bind the parameters
$search_param = "%{$search}%";
$stmt->bind_param('sssii', $search_param, $search_param, $search_param, $limit, $offset);

// Execute the statement
$stmt->execute();

// Get the result
$result = $stmt->get_result();

// Prepare the SQL statement for counting total rows
$total_sql = "SELECT COUNT(*) FROM publishers_site 
              WHERE site_name LIKE ? OR site_domain LIKE ? OR site_desc LIKE ?";

// Prepare the statement
$total_stmt = $mysqli->prepare($total_sql);

// Bind the parameters
$total_stmt->bind_param('sss', $search_param, $search_param, $search_param);

// Execute the statement
$total_stmt->execute();

// Get the result
$total_result = $total_stmt->get_result();
$total_rows = $total_result->fetch_array()[0];
$total_pages = max(1, (int) ceil($total_rows / $limit));
$window_start = max(1, $page - 2);
$window_end = min($total_pages, $page + 2);

// Close the statement for total count
$total_stmt->close();

?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Publishers Site List</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.1/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.3/css/all.min.css">
    <style>
        body { background: #f5f7fa; color: #26313c; }
        .navbar { box-shadow: 0 2px 12px rgba(31,41,55,.07); }
        .navbar-brand { font-weight: 750; }
        .page-shell { max-width: 1200px; }
        .hero { padding: 1.5rem; border-radius: 1rem; background: linear-gradient(135deg, #172033, #275681); color: #fff; }
        .hero h1 { margin: 0; font-size: 1.7rem; font-weight: 750; }
        .hero p { margin: .35rem 0 0; color: rgba(255,255,255,.78); }
        .search-panel { padding: 1rem; border-radius: .85rem; background: #fff; box-shadow: 0 3px 14px rgba(31,41,55,.07); }
        .publisher-card { overflow: hidden; border: 0; border-radius: .9rem; box-shadow: 0 3px 14px rgba(31,41,55,.08); transition: transform .2s ease, box-shadow .2s ease; }
        .publisher-card:hover { transform: translateY(-3px); box-shadow: 0 7px 22px rgba(31,41,55,.12); }
        .publisher-card .card-body { display: flex; flex-direction: column; }
        .site-id { color: #6c757d; font-size: .72rem; font-weight: 700; text-transform: uppercase; }
        .site-title { margin: .25rem 0; font-size: 1.08rem; font-weight: 750; overflow-wrap: anywhere; }
        .site-domain { display: block; margin-bottom: .8rem; font-size: .8rem; overflow-wrap: anywhere; }
        .site-description { min-height: 3.8rem; color: #64707c; font-size: .86rem; }
        .rate-grid { display: grid; grid-template-columns: 1fr 1fr; gap: .65rem; margin: .75rem 0 1rem; }
        .rate-box { padding: .75rem; border-radius: .65rem; background: #f3f6f8; }
        .rate-box.selling { background: #eaf8f0; color: #12683a; }
        .rate-label { display: block; font-size: .67rem; font-weight: 700; text-transform: uppercase; }
        .rate-value { display: block; margin-top: .2rem; font-size: 1rem; font-weight: 800; }
        .registration-date { margin-top: auto; color: #6c757d; font-size: .75rem; }
        .publisher-pagination { flex-wrap: wrap; gap: .25rem; }
        .publisher-pagination .page-link { min-width: 38px; border-radius: .45rem !important; text-align: center; }
        #reviewContent { white-space: pre-line; line-height: 1.7; }
        @media (max-width: 575.98px) { .page-shell { padding-right: .75rem; padding-left: .75rem; } .hero { padding: 1.1rem; } .hero h1 { font-size: 1.4rem; } }
    </style>
</head>

<body>

    <!-- Navbar -->
    <nav class="navbar navbar-expand-lg navbar-light bg-white sticky-top">
        <div class="container">
            <a class="navbar-brand" href="../index.php">KumpulBlogger</a>
            <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav" aria-controls="navbarNav" aria-expanded="false" aria-label="Toggle navigation">
                <span class="navbar-toggler-icon"></span>
            </button>
            <div class="collapse navbar-collapse" id="navbarNav">
                <ul class="navbar-nav ms-auto">
                    <li class="nav-item"><a class="nav-link" href="../reg.php">Daftar</a></li>
                    <li class="nav-item"><a class="nav-link" href="../login.php">Login</a></li>
                    <li class="nav-item"><a class="nav-link" href="../forgot_password.php">Lupa Password?</a></li>
                    <li class="nav-item"><a class="nav-link" href="../index.php">Home</a></li>
                </ul>
            </div>
        </div>
    </nav>

    <!-- Main content -->
    <div class="container page-shell py-4 py-md-5">
        <div class="hero mb-4"><h1>Daftar Site Publisher</h1><p>Temukan site publisher dan bandingkan rate iklan per klik.</p></div>

        <!-- Form Pencarian -->
        <form class="search-panel d-flex mb-4" method="GET" action="">
            <input class="form-control me-2" type="search" name="search" placeholder="Cari nama, domain, atau deskripsi site" aria-label="Cari publisher" value="<?php echo htmlspecialchars($search); ?>">
            <button class="btn btn-primary" type="submit">Cari</button>
        </form>
        
        <?php if ($result->num_rows > 0): ?>
            <div class="row g-4">
                <?php while ($row = $result->fetch_assoc()):
                    $ulasan = str_replace('*', '', (string) $row['ulasan']);
                ?>
                    <div class="col-12 col-md-6 col-xl-4">
                        <article class="card publisher-card h-100">
                            <div class="card-body p-4">
                                <span class="site-id">Publisher Site #<?php echo htmlspecialchars($row['id']); ?></span>
                                <h2 class="site-title"><?php echo htmlspecialchars($row['site_name']); ?></h2>
                                <a class="site-domain" href="<?php echo htmlspecialchars($row['site_domain'], ENT_QUOTES); ?>" target="_blank" rel="noopener noreferrer"><?php echo htmlspecialchars($row['site_domain']); ?></a>
                                <p class="site-description"><?php echo htmlspecialchars($row['site_desc']); ?></p>
                                <div class="rate-grid">
                                    <div class="rate-box"><span class="rate-label">Rate Publisher</span><span class="rate-value">Rp <?php echo number_format((float) $row['rate_text_ads'], 2, ',', '.'); ?></span></div>
                                    <div class="rate-box selling"><span class="rate-label">Harga Jual</span><span class="rate-value">Rp <?php echo number_format((float) $row['rate_text_ads'] * 1.5, 2, ',', '.'); ?></span></div>
                                </div>
                                <?php if ($ulasan !== ''): ?>
                                    <button class="btn btn-outline-primary btn-sm mb-3" data-bs-toggle="modal" data-bs-target="#reviewModal" data-review="<?php echo htmlspecialchars($ulasan, ENT_QUOTES); ?>" onclick="showReview(this.dataset.review)">Lihat Ulasan</button>
                                <?php endif; ?>
                                <span class="registration-date"><i class="far fa-calendar-alt me-1"></i> Terdaftar <?php echo htmlspecialchars(date('d M Y, H:i', strtotime($row['regdate']))); ?></span>
                            </div>
                        </article>
                    </div>
                <?php endwhile; ?>
            </div>

            <!-- Pagination -->
            <div class="text-center text-muted small mt-4">Halaman <?php echo $page; ?> dari <?php echo $total_pages; ?> &middot; <?php echo number_format($total_rows); ?> site</div>
            <nav class="mt-2" aria-label="Navigasi publisher site">
                <ul class="pagination publisher-pagination justify-content-center">
                    <li class="page-item <?php echo $page <= 1 ? 'disabled' : ''; ?>"><a class="page-link" href="?page=<?php echo max(1, $page - 1); ?>&amp;search=<?php echo urlencode($search); ?>">&laquo;</a></li>
                    <?php if ($window_start > 1): ?>
                        <li class="page-item"><a class="page-link" href="?page=1&amp;search=<?php echo urlencode($search); ?>">1</a></li>
                        <?php if ($window_start > 2): ?><li class="page-item disabled"><span class="page-link">&hellip;</span></li><?php endif; ?>
                    <?php endif; ?>
                    <?php for ($i = $window_start; $i <= $window_end; $i++): ?>
                        <li class="page-item <?php echo $page === $i ? 'active' : ''; ?>"><a class="page-link" href="?page=<?php echo $i; ?>&amp;search=<?php echo urlencode($search); ?>"><?php echo $i; ?></a></li>
                    <?php endfor; ?>
                    <?php if ($window_end < $total_pages): ?>
                        <?php if ($window_end < $total_pages - 1): ?><li class="page-item disabled"><span class="page-link">&hellip;</span></li><?php endif; ?>
                        <li class="page-item"><a class="page-link" href="?page=<?php echo $total_pages; ?>&amp;search=<?php echo urlencode($search); ?>"><?php echo $total_pages; ?></a></li>
                    <?php endif; ?>
                    <li class="page-item <?php echo $page >= $total_pages ? 'disabled' : ''; ?>"><a class="page-link" href="?page=<?php echo min($total_pages, $page + 1); ?>&amp;search=<?php echo urlencode($search); ?>">&raquo;</a></li>
                </ul>
            </nav>
        <?php else: ?>
            <p class="text-center">No records found.</p>
        <?php endif; ?>
    </div>

    <!-- Modal untuk menampilkan ulasan -->
  <div class="modal fade" id="reviewModal" tabindex="-1" aria-labelledby="reviewModalLabel" aria-hidden="true">
      <div class="modal-dialog modal-xl">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="reviewModalLabel">Ulasan Situs</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <p id="reviewContent"></p>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
            </div>
        </div>
    </div>
</div>





<script>
function showReview(reviewContent) {
    document.getElementById('reviewContent').textContent = reviewContent;
}
</script>



    <script src="https://cdn.jsdelivr.net/npm/@popperjs/core@2.11.8/dist/umd/popper.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.1/dist/js/bootstrap.min.js"></script>
</body>

</html>

<?php
// Close the statement and connection
$stmt->close();
$mysqli->close();
?>
