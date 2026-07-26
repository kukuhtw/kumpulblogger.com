<?php
// view_quiz_articles.php
session_start();
require_once __DIR__ . '/db.php';
require_once __DIR__ . '/config.php';
//require_once __DIR__ . '/functions.php'; // Pastikan di sini ada get_providers_domain_url_json()

// Jika belum login, redirect ke login.php
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

try {
    $db   = new Database($config['database']);
    $conn = $db->getConnection();
} catch (Exception $e) {
    die("Gagal koneksi database: " . $e->getMessage());
}

$user_id = intval($_SESSION['user_id']);

// =======================
// 1. INISIALISASI DOMAIN
// =======================
// Contoh: ambil domain provider sekali di luar loop.
// Path ke providers_data.json disesuaikan sesuai letak file di project Anda.

// ==================================
// 2. HITUNG TOTAL ARTIKEL MILIK USER
// ==================================
$page    = isset($_GET['page']) && $_GET['page'] > 0 ? (int)$_GET['page'] : 1;
$perPage = 10;
$offset  = ($page - 1) * $perPage;

$stmtCount = $conn->prepare("SELECT COUNT(*) FROM articles WHERE publishers_local_id = ?");
$stmtCount->bind_param("i", $user_id);
$stmtCount->execute();
$stmtCount->bind_result($total);
$stmtCount->fetch();
$stmtCount->close();

$totalPages = ceil($total / $perPage);

// ==========================================================
// 3. QUERY UTAMA: AMBIL DATA ARTIKEL + kolom `username` DARI JOIN
// ==========================================================
$sql = "
    SELECT 
        a.id, 
        a.title, 
        a.tag, 
        a.json_quiz, 
        pq.username 
      FROM articles AS a
      LEFT JOIN publisher_quota AS pq 
        ON a.publishers_local_id = pq.publisher_id
     WHERE a.publishers_local_id = ?
     ORDER BY a.created_at DESC
     LIMIT ? OFFSET ?
";

$stmt = $conn->prepare($sql);
if (!$stmt) {
    die("Prepare gagal: (" . $conn->errno . ") " . $conn->error);
}
$stmt->bind_param("iii", $user_id, $perPage, $offset);
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
</head>
<body>
<div class="container py-4">

    <?php
    // Jika ada menu utama atau menu publisher, include di sini:
    include("main_menu.php");
    include("include_publisher_menu.php");
    ?>

    <h2 class="mb-4">Daftar Artikel</h2>

    <?php if (!empty($this_providers_domain_url)): ?>
        <p><em>Domain provider:</em> <?= htmlspecialchars($this_providers_domain_url) ?></p>
    <?php endif; ?>

    <table class="table table-striped table-hover">
        <thead class="table-light">
            <tr>
                <th>Judul</th>
                <th>Tag</th>
                <th>View Article</th>
                <th>Aksi</th>
            </tr>
        </thead>
        <tbody>
            <?php while ($row = $result->fetch_assoc()): 
                // ===============================
                // 4. BENTUK SLUG dari judul artikel
                // ===============================
                $slug = preg_replace('/[^A-Za-z0-9 ]/', '', $row['title']);
                $slug = str_replace(' ', '_', trim($slug));

                // ===================================
                // 5. AMBIL username DARI ROW hasil JOIN
                // ===================================
                // Inilah “modifikasi username” yang ditanyakan: 
                // $row['username'] sekarang sudah ada, karena kita SELECT pq.username.
                $authorUsername = !empty($row['username']) 
                                  ? $row['username'] 
                                  : 'unknown';

                // =================================================
                // 6. BENTUK PERMALINK: {domain}/blog/{username}/{id}/{slug}
                // =================================================
                $articleId = intval($row['id']);
                $permalink = rtrim('', '/') 
                           . "/blog/" 
                           . urlencode($authorUsername) 
                           . "/" 
                           . $articleId 
                           . "/" 
                           . urlencode($slug);
                ?>
            <tr data-article-id="<?= $articleId ?>">
                <td><?= htmlspecialchars($row['title']) ?></td>
                <td><?= htmlspecialchars($row['tag']) ?></td>
                <td>
                    <a href="<?= htmlspecialchars($permalink) ?>" 
                       target="_blank" 
                       class="btn btn-sm btn-primary">
                        View Article
                    </a>
                </td>
                <td>
                    <?php if (empty($row['json_quiz'])): ?>
                        <!-- Tombol Generate Quiz aktif hanya jika json_quiz kosong -->
                        <button
                            class="btn btn-sm btn-warning btn-generate-quiz"
                            data-id="<?= $articleId ?>"
                        >
                            Generate Summary FAQ
                        </button>
                    <?php else: ?>
                        <!-- Jika sudah ada json_quiz, non-aktifkan atau ganti label -->
                        <span class="badge bg-info text-dark">Summary FAQ Sudah Dibuat</span>
                    <?php endif; ?>
                </td>
            </tr>
            <?php endwhile; ?>
        </tbody>
    </table>

   
    <!-- Pagination -->
    <?php if ($totalPages > 1): ?>
    <nav>
        <ul class="pagination justify-content-center">
            <!-- First & Prev -->
            <?php if ($page > 1): ?>
                <li class="page-item">
                    <a class="page-link" href="?page=1">First</a>
                </li>
                <li class="page-item">
                    <a class="page-link" href="?page=<?= $page - 1 ?>">Prev</a>
                </li>
            <?php else: ?>
                <li class="page-item disabled">
                    <span class="page-link">First</span>
                </li>
                <li class="page-item disabled">
                    <span class="page-link">Prev</span>
                </li>
            <?php endif; ?>

            <!-- Halaman H-3 hingga H+3 -->
            <?php
                $start = max(1, $page - 3);
                $end   = min($totalPages, $page + 3);
                for ($p = $start; $p <= $end; $p++):
            ?>
                <li class="page-item <?= $p === $page ? 'active' : '' ?>">
                    <a class="page-link" href="?page=<?= $p ?>"><?= $p ?></a>
                </li>
            <?php endfor; ?>

            <!-- Next & Last -->
            <?php if ($page < $totalPages): ?>
                <li class="page-item">
                    <a class="page-link" href="?page=<?= $page + 1 ?>">Next</a>
                </li>
                <li class="page-item">
                    <a class="page-link" href="?page=<?= $totalPages ?>">Last</a>
                </li>
            <?php else: ?>
                <li class="page-item disabled">
                    <span class="page-link">Next</span>
                </li>
                <li class="page-item disabled">
                    <span class="page-link">Last</span>
                </li>
            <?php endif; ?>
        </ul>
    </nav>
    <?php endif; ?>


   
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
<script>
// AJAX untuk Generate Quiz (sama seperti sebelumnya)
document.querySelectorAll('.btn-generate-quiz').forEach(button => {
    button.addEventListener('click', function() {
        const articleId = this.dataset.id;
        const btn = this;
        btn.disabled = true;
        btn.textContent = 'Sedang diproses...';

        fetch('generate_quiz.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json'
            },
            body: JSON.stringify({ article_id: articleId })
        })
        .then(res => {
            if (!res.ok) {
                return res.text().then(text => {
                    throw new Error(`Server error ${res.status}:\n${text}`);
                });
            }
            return res.json();
        })
        .then(data => {
            if (data.success) {
                btn.outerHTML = '<span class="badge bg-info text-dark">Summary FAQ Dibuat</span>';
            } else {
                btn.disabled = false;
                btn.textContent = 'Generate Quiz';
                alert('Gagal membuat Summary FAQ : ' + (data.message || 'Unknown error'));
            }
        })
        .catch(err => {
            console.error(err);
            btn.disabled = false;
            btn.textContent = 'Generate Quiz';
            alert('Error: ' + err.message);
        });
    });
});
</script>
</body>
</html>
