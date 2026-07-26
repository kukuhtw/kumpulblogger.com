<?php
// view_edit_articles.php
session_start();
include("db.php"); // $conn settings
require_once("config.php");

try {
    $db = new Database($config['database']);
    $conn = $db->getConnection();
} catch (Exception $e) {
    die("Gagal koneksi database: " . $e->getMessage());
}

// Check login
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}
$user_id = $_SESSION['user_id'];

// Pagination setup
$page    = isset($_GET['page']) && $_GET['page'] > 0 ? (int)$_GET['page'] : 1;
$perPage = 10;

// Count total articles
$stmtCount = $conn->prepare(
    "SELECT COUNT(*) FROM articles WHERE publishers_local_id = ?"
);
$stmtCount->bind_param("i", $user_id);
$stmtCount->execute();
$stmtCount->bind_result($total);
$stmtCount->fetch();
$stmtCount->close();

$totalPages = max(1, (int) ceil($total / $perPage));
$page = min($page, $totalPages);
$offset = ($page - 1) * $perPage;
$firstItem = $total > 0 ? $offset + 1 : 0;
$lastItem = min($offset + $perPage, $total);
$windowStart = max(1, $page - 2);
$windowEnd = min($totalPages, $page + 2);

// Fetch articles with inline LIMIT & OFFSET
$sql = "
SELECT id, title, ispublished, tag
  FROM articles
 WHERE publishers_local_id = ?
 ORDER BY created_at DESC
 LIMIT {$perPage}
 OFFSET {$offset}
";
$stmt = $conn->prepare($sql);
if (!$stmt) {
    die("Prepare failed: (" . $conn->errno . ") " . $conn->error);
}
$stmt->bind_param("i", $user_id);
$stmt->execute();
$result = $stmt->get_result();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Daftar Artikel</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        body { background: #f5f7fa; }
        .articles-card { overflow: hidden; border: 0; border-radius: .9rem; box-shadow: 0 3px 14px rgba(31,41,55,.07); }
        .articles-pagination { flex-wrap: wrap; gap: .25rem; margin-bottom: 0; }
        .articles-pagination .page-link { min-width: 40px; border-radius: .45rem !important; text-align: center; }
        .pagination-summary { color: #6c757d; font-size: .875rem; }
        @media (max-width: 575.98px) {
            .articles-pagination .page-link { min-width: 36px; padding: .45rem .6rem; font-size: .85rem; }
            .pagination-label { display: none; }
        }
    </style>
</head>
<body>
<div class="container py-4">

   
      <?php include("main_menu.php"); ?>
      <?php include("include_publisher_menu.php"); 


// Ambil username dari publisher_quota
$username = '';
if ($stmt = $conn->prepare("SELECT username FROM publisher_quota WHERE publisher_id = ?")) {
    $stmt->bind_param("i", $user_id);
    $stmt->execute();
    $stmt->bind_result($username);
    $stmt->fetch();
    $stmt->close();
}
// pastikan kalau null jadi string kosong
$username = $username ?: '';


      ?>
    <h2 class="mb-4">Daftar Artikel</h2>

    <a
  href="blog/<?php echo $username; ?>"
  target="_blank"
  id="viewArticleBtn"
  class="btn btn-success"
>
  View Article
</a>

  <div class="card articles-card mt-3">
   <div class="table-responsive">
    <table class="table table-striped table-hover mb-0">
        <thead class="table-light">
            <tr>
                <th>Judul</th>
                 <th>Tag</th>
                <th>Status</th>
                <th>Aksi</th>
            </tr>
        </thead>
        <tbody>
            <?php while ($row = $result->fetch_assoc()): ?>
            <tr>
                <td><?= htmlspecialchars($row['title']) ?></td>
                <td><?= htmlspecialchars($row['tag']) ?></td>
                <td>
                    <?php if ($row['ispublished']): ?>
                        <span class="badge bg-success">Published</span>
                    <?php else: ?>
                        <span class="badge bg-secondary">Draft</span>
                    <?php endif; ?>
                </td>
                <td>
                    <a href="edit_article.php?id=<?= $row['id'] ?>" class="btn btn-sm btn-primary">Edit</a>
                </td>
            </tr>
            <?php endwhile; ?>
        </tbody>
    </table>
   </div>
  </div>

    <!-- Pagination -->
    <div class="d-flex flex-column align-items-center gap-2 mt-4">
      <div class="pagination-summary">
        Menampilkan <?= number_format($firstItem) ?>–<?= number_format($lastItem) ?> dari <?= number_format($total) ?> artikel
      </div>
      <nav aria-label="Navigasi halaman artikel">
        <ul class="pagination articles-pagination justify-content-center">
            <li class="page-item <?= $page <= 1 ? 'disabled' : '' ?>">
                <a class="page-link" href="?page=<?= max(1, $page - 1) ?>" aria-label="Halaman sebelumnya">
                    <span aria-hidden="true">&laquo;</span> <span class="pagination-label">Sebelumnya</span>
                </a>
            </li>

            <?php if ($windowStart > 1): ?>
                <li class="page-item"><a class="page-link" href="?page=1">1</a></li>
                <?php if ($windowStart > 2): ?>
                    <li class="page-item disabled"><span class="page-link">&hellip;</span></li>
                <?php endif; ?>
            <?php endif; ?>

            <?php for ($p = $windowStart; $p <= $windowEnd; $p++): ?>
                <li class="page-item <?= $p === $page ? 'active' : '' ?>" <?= $p === $page ? 'aria-current="page"' : '' ?>>
                    <a class="page-link" href="?page=<?= $p ?>"><?= $p ?></a>
                </li>
            <?php endfor; ?>

            <?php if ($windowEnd < $totalPages): ?>
                <?php if ($windowEnd < $totalPages - 1): ?>
                    <li class="page-item disabled"><span class="page-link">&hellip;</span></li>
                <?php endif; ?>
                <li class="page-item"><a class="page-link" href="?page=<?= $totalPages ?>"><?= $totalPages ?></a></li>
            <?php endif; ?>

            <li class="page-item <?= $page >= $totalPages ? 'disabled' : '' ?>">
                <a class="page-link" href="?page=<?= min($totalPages, $page + 1) ?>" aria-label="Halaman berikutnya">
                    <span class="pagination-label">Berikutnya</span> <span aria-hidden="true">&raquo;</span>
                </a>
            </li>
        </ul>
      </nav>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
