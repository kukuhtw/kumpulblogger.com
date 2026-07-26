<?php
// view_ai_images_articles.php
session_start();
require_once __DIR__ . '/db.php';
require_once __DIR__ . '/config.php';

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

// Hitung total artikel milik user
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

// Ambil data artikel + kolom `images` + kolom `prediction_id`
$sql = "
    SELECT 
        a.id, 
        a.title, 
        a.tag, 
        a.images,
        a.prediction_id,
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
<html lang="id">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Daftar Artikel & AI Images</title>
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

    <table class="table table-striped table-hover">
        <thead class="table-light">
            <tr>
                <th>Judul</th>
                <th>Tag</th>
                <th>Prediction ID</th>
                <th>View Article</th>
                <th>AI Image</th>
            </tr>
        </thead>
        <tbody>
            <?php while ($row = $result->fetch_assoc()):
                // Bentuk slug dari judul artikel
                $slug = preg_replace('/[^A-Za-z0-9 ]/', '', $row['title']);
                $slug = str_replace(' ', '_', trim($slug));

                // Ambil username (jika ada)
                $authorUsername = !empty($row['username'])
                                  ? $row['username']
                                  : 'unknown';

                // Bentuk permalink: {domain}/blog/{username}/{id}/{slug}
                $articleId    = intval($row['id']);
                $permalink    = "/blog/" . urlencode($authorUsername) . "/" . $articleId . "/" . urlencode($slug);
                $hasImage     = !empty($row['images']);
                $predictionId = $row['prediction_id']; // bisa null atau string
                ?>
            <tr data-article-id="<?= $articleId ?>">
                <td><?= htmlspecialchars($row['title']) ?></td>
                <td><?= htmlspecialchars($row['tag']) ?></td>
                <!-- Tampilkan kolom Prediction ID -->
                <td>
                    <?= $predictionId
                        ? htmlspecialchars($predictionId)
                        : '<span class="text-muted">-</span>'; ?>
                </td>
                <td>
                    <a href="<?= htmlspecialchars($permalink) ?>"
                       target="_blank"
                       class="btn btn-sm btn-primary">
                        View Article
                    </a>
                </td>
                <td>
                    <?php if ($hasImage): ?>
                        <!-- Jika sudah ada gambar, tampilkan thumbnail -->
                        <img src="<?= htmlspecialchars($row['images']) ?>"
                             alt="AI Image Artikel <?= $articleId ?>"
                             style="max-width: 200px; height: auto; border: 1px solid #ccc; padding: 4px;">
                    <?php else: ?>
                        <?php if (!empty($predictionId)): ?>
                            <!-- Jika prediction_id sudah ada tapi images masih kosong -->
                            <button
                                class="btn btn-sm btn-warning btn-get-images"
                                data-id="<?= $articleId ?>">
                                Get AI Images
                            </button>
                        <?php else: ?>
                            <!-- Jika belum punya prediction_id sama sekali -->
                            <button
                                class="btn btn-sm btn-success btn-generate-images"
                                data-id="<?= $articleId ?>">
                                Generate AI Images
                            </button>
                        <?php endif; ?>
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
// AJAX untuk tombol “Generate AI Images”
document.querySelectorAll('.btn-generate-images').forEach(button => {
    button.addEventListener('click', function() {
        const articleId = this.dataset.id;
        const btn = this;
        btn.disabled = true;
        btn.textContent = 'Sedang membuat gambar...';

        fetch('generate_ai_images.php', {
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
                // Reload halaman agar kolom prediction_id dan tombol berubah
                location.reload();
            } else {
                btn.disabled = false;
                btn.textContent = 'Generate AI Images';
                alert('Gagal membuat AI Image: ' + (data.message || 'Unknown error'));
            }
        })
        .catch(err => {
            console.error(err);
            btn.disabled = false;
            btn.textContent = 'Generate AI Images';
            alert('Error: ' + err.message);
        });
    });
});

// AJAX untuk tombol “Get AI Images”
document.querySelectorAll('.btn-get-images').forEach(button => {
    button.addEventListener('click', function() {
        const articleId = this.dataset.id;
        const btn = this;
        btn.disabled = true;
        btn.textContent = 'Menunggu hasil...';

        fetch('generate_ai_images.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json'
            },
            body: JSON.stringify({
                article_id: articleId,
                action: 'get' // tandai bahwa kita hanya ingin fetch hasil
            })
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
                // Ganti tombol atau cell dengan thumbnail image tanpa reload page
                const row = btn.closest('tr');
                const imageCell = row.querySelector('td:last-child');
                const imageUrl = data.image_path;
                imageCell.innerHTML = `
                    <img src="${imageUrl}"
                         alt="AI Image Artikel ${articleId}"
                         style="max-width: 200px; height: auto; border: 1px solid #ccc; padding: 4px;">
                `;
            } else {
                btn.disabled = false;
                btn.textContent = 'Get AI Images';
                alert('Gagal mendapatkan AI Image: ' + (data.message || 'Unknown error'));
            }
        })
        .catch(err => {
            console.error(err);
            btn.disabled = false;
            btn.textContent = 'Get AI Images';
            alert('Error: ' + err.message);
        });
    });
});
</script>
</body>
</html>
