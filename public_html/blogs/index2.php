<?php

require_once("../db.php");
require_once("../config.php");

// Koneksi ke database
try {
    $db   = new Database($config['database']);
    $conn = $db->getConnection();
} catch (Exception $e) {
    die("Gagal koneksi database: " . $e->getMessage());
}

// Pagination
$page      = isset($_GET['page']) && is_numeric($_GET['page']) ? (int) $_GET['page'] : 1;
if ($page < 1) {
    $page = 1;
}
$limit     = 20;
$offset    = ($page - 1) * $limit;

// Search filter
$searchTerm = isset($_GET['search']) ? trim($_GET['search']) : '';
$searchSql  = '';
$params     = [];
$types      = '';

// Jika ada keyword, siapkan SQL tambahan dan parameter binding
if ($searchTerm !== '') {
    $searchSql = " AND (
        a.title LIKE ? 
        OR a.html_content LIKE ? 
        OR a.tag LIKE ?
    )";
    $like      = "%{$searchTerm}%";
    // tiga parameter string untuk LIKE
    $types     = 'sss';
    $params    = [&$like, &$like, &$like];
}

// Hitung total artikel
$countSql = "SELECT COUNT(*) AS total
             FROM articles a
             WHERE a.ispublished = 1"
             . $searchSql;
$countStmt = $conn->prepare($countSql);

// Bind parameter jika perlu
if ($searchTerm !== '') {
    $countStmt->bind_param($types, ...$params);
}

$countStmt->execute();
$countResult = $countStmt->get_result()->fetch_assoc();
$total       = (int) $countResult['total'];
$totalPages  = (int) ceil($total / $limit);

// Ambil 20 artikel terbaru beserta info penulis
$fetchSql = "
    SELECT a.id, a.title, a.html_content, a.tag, a.created_at,a.wav , a.images , 
           pq.username, pq.description AS author_description
    FROM articles a
    LEFT JOIN publisher_quota pq ON a.pub_id = pq.pub_id
    WHERE a.ispublished = 1"
    . $searchSql . "
    ORDER BY a.created_at DESC
    LIMIT ?, ?";
$stmtArt = $conn->prepare($fetchSql);

// Bind parameter untuk search + offset & limit
if ($searchTerm !== '') {
    // sssii: 3 string untuk LIKE, 2 integer untuk offset & limit
    $typesArt = 'sssii';
    $stmtArt->bind_param(
        $typesArt,
        $like, $like, $like,
        $offset, $limit
    );
} else {
    $stmtArt->bind_param("ii", $offset, $limit);
}

$stmtArt->execute();
$resultArt = $stmtArt->get_result();
$articles  = $resultArt->fetch_all(MYSQLI_ASSOC);

// Untuk pagination links
$searchQuery = $searchTerm !== '' ? '&search=' . urlencode($searchTerm) : '';
?>
<!DOCTYPE html>
<html lang="id">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>Artikel Terbaru</title>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body>
  <div class="container mt-4">
    <h1 class="mb-4">Artikel Terbaru</h1>

    <!-- Form Search -->
    <form method="get" action="" class="mb-4">
      <div class="input-group">
        <input
          type="text"
          name="search"
          class="form-control"
          placeholder="Cari artikel..."
          value="<?php echo htmlspecialchars($searchTerm); ?>"
        >
        <button class="btn btn-outline-secondary" type="submit">Search</button>
      </div>
    </form>

    <div class="row">
      <!-- Kolom Kiri: 10 Artikel Pertama -->
      <div class="col-md-6">
        <?php for ($i = 0; $i < min(10, count($articles)); $i++): ?>
          <?php
            $row        = $articles[$i];
            $username   = $row['username'] ?? 'unknown';
            $authorDesc = $row['author_description'] ?? '';
            $slug       = preg_replace('/[^A-Za-z0-9 ]/', '', $row['title']);
            $slug       = str_replace(' ', '_', $slug);
            $text       = strip_tags($row['html_content']);
            $words      = preg_split('/\s+/', $text);
            $snippet    = count($words) > 25
                         ? implode(' ', array_slice($words, 0, 25)) . '...'
                         : $text;

            // Cari <img> pertama di html_content
            // Cari <img> pertama di html_content
$thumbnailUrl = '';
if (preg_match('/<img\s+[^>]*src=[\'"]([^\'"]+)[\'"]/i', $row['html_content'], $matches)) {
    $src = $matches[1];

    // Jika src sudah berupa URL lengkap (http:// atau https://), pakai langsung
    if (preg_match('#^https?://#i', $src)) {
        $thumbnailUrl = $src;
    } else {
        // Jika bukan URL lengkap, asumsikan path relatif dan prefix dengan "../"
        $thumbnailUrl = "../" . ltrim($src, '/');
    }

    
}

//echo "<br>1. thumbnailUrl:". htmlspecialchars($thumbnailUrl);

    if ($thumbnailUrl == '') {
      $thumbnailUrl = "../".$row['images'];

    }



          ?>
          <div class="card mb-3">

            <?php if (!empty($thumbnailUrl) && $thumbnailUrl!="../"): ?>
              <img
                src="<?php echo htmlspecialchars($thumbnailUrl); ?>"
                class="card-img-top img-fluid img-thumbnail"
                style="max-height: 200px; object-fit: cover;"
                alt="Thumbnail Artikel"
              >
            <?php endif; ?>
            <div class="card-body">
              <h5 class="card-title">
                <a href="/blog/<?php echo urlencode($username); ?>/<?php echo $row['id']; ?>/<?php echo urlencode($slug); ?>"
                   class="text-decoration-none">
                  <?php echo htmlspecialchars($row['title']); ?>
                </a>
              </h5>
              <p class="card-text text-secondary">
                <small>
                  Penulis: <strong><?php echo htmlspecialchars($username); ?></strong>
                  <?php if ($authorDesc): ?>
                    — <?php echo htmlspecialchars($authorDesc); ?>
                  <?php endif; ?>
                </small>
              </p>
              <p class="card-text"><?php echo htmlspecialchars($snippet); ?></p>
              <p class="card-text">
                <small class="text-muted">
                  <?php echo sprintf('Diterbitkan: %s', $row['created_at']); ?>
                </small>
              </p>

<!-- 🎧 Tampilkan pemutar audio hanya jika kolom wav tidak kosong -->
        <?php if (!empty($row['wav'])): ?>
          <div class="mb-2">
            <audio controls style="width: 100%;">
              <source 
                src="<?php 
                  // Asumsi: file .wav disimpan di folder 'uploads/wav/' atau sesuai struktur Anda.
                  // Ganti 'uploads/wav/' dengan path yang benar ke file áudio Anda.
                  echo htmlspecialchars("../../../{$row['wav']}"); 
                ?>" 
                type="audio/wav">
              Browser Anda tidak mendukung pemutar audio.
            </audio>
          </div>
        <?php endif; ?>


 


              <a href="/blog/<?php echo urlencode($username); ?>/<?php echo $row['id']; ?>/<?php echo urlencode($slug); ?>"
                 class="btn btn-primary btn-sm">Read more</a>

            </div>
          </div>
        <?php endfor; ?>
      </div>

      <!-- Kolom Kanan: 10 Artikel Berikutnya -->
      <div class="col-md-6">
        <?php for ($i = 10; $i < min(20, count($articles)); $i++): ?>
          <?php
            $row        = $articles[$i];
            $username   = $row['username'] ?? 'unknown';
            $authorDesc = $row['author_description'] ?? '';
            $slug       = preg_replace('/[^A-Za-z0-9 ]/', '', $row['title']);
            $slug       = str_replace(' ', '_', $slug);
            $text       = strip_tags($row['html_content']);
            $words      = preg_split('/\s+/', $text);
            $snippet    = count($words) > 25
                         ? implode(' ', array_slice($words, 0, 25)) . '...'
                         : $text;

            // Cari <img> pertama di html_content
           // Cari <img> pertama di html_content
$thumbnailUrl = '';
if (preg_match('/<img\s+[^>]*src=[\'"]([^\'"]+)[\'"]/i', $row['html_content'], $matches)) {
    $src = $matches[1];

    // Jika src sudah berupa URL lengkap (http:// atau https://), pakai langsung
    if (preg_match('#^https?://#i', $src)) {
        $thumbnailUrl = $src;
    } else {
        // Jika bukan URL lengkap, asumsikan path relatif dan prefix dengan "../"
        $thumbnailUrl = "../" . ltrim($src, '/');
    }

 
   
}

 if ($thumbnailUrl == '') {
      $thumbnailUrl = "../".$row['images'];

    }

          ?>
          <div class="card mb-3">
            <?php if (!empty($thumbnailUrl) && $thumbnailUrl!="../"): ?>
              <img
                src="<?php echo htmlspecialchars($thumbnailUrl); ?>"
                class="card-img-top img-fluid img-thumbnail"
                style="max-height: 200px; object-fit: cover;"
                alt="Thumbnail Artikel"
              >
            <?php endif; ?>
            <div class="card-body">
              <h5 class="card-title">
                <a href="/blog/<?php echo urlencode($username); ?>/<?php echo $row['id']; ?>/<?php echo urlencode($slug); ?>"
                   class="text-decoration-none">
                  <?php echo htmlspecialchars($row['title']); ?>
                </a>
              </h5>
              <p class="card-text text-secondary">
                <small>
                  Penulis: <strong><?php echo htmlspecialchars($username); ?></strong>
                  <?php if ($authorDesc): ?>
                    — <?php echo htmlspecialchars($authorDesc); ?>
                  <?php endif; ?>
                </small>
              </p>
              <p class="card-text"><?php echo htmlspecialchars($snippet); ?></p>
              <p class="card-text">
                <small class="text-muted">
                  <?php echo sprintf('Diterbitkan: %s', $row['created_at']); ?>
                </small>
              </p>

                        
<!-- 🎧 Tampilkan pemutar audio hanya jika kolom wav tidak kosong -->
        <?php if (!empty($row['wav'])): ?>
          <div class="mb-2">
            <audio controls style="width: 100%;">
              <source 
                src="<?php 
                  // Asumsi: file .wav disimpan di folder 'uploads/wav/' atau sesuai struktur Anda.
                  // Ganti 'uploads/wav/' dengan path yang benar ke file áudio Anda.
                  echo htmlspecialchars("../../../{$row['wav']}"); 
                ?>" 
                type="audio/wav">
              Browser Anda tidak mendukung pemutar audio.
            </audio>
          </div>
        <?php endif; ?>


 


                            <a href="/blog/<?php echo urlencode($username); ?>/<?php echo $row['id']; ?>/<?php echo urlencode($slug); ?>"
                 class="btn btn-primary btn-sm">Read more</a>
            </div>
          </div>
        <?php endfor; ?>
      </div>
    </div>

   
<!-- Pagination -->
<nav aria-label="Page navigation">
  <ul class="pagination justify-content-center">
    <?php
      // Hitung batas bawah dan atas untuk penomoran halaman (h-3 sampai h+3)
      $startPage = max(1, $page - 3);
      $endPage   = min($totalPages, $page + 3);
    ?>

    <!-- First & Prev -->
    <?php if ($page > 1): ?>
      <li class="page-item">
        <a class="page-link" href="?page=1<?php echo $searchQuery; ?>">First</a>
      </li>
      <li class="page-item">
        <a class="page-link" href="?page=<?php echo ($page - 1) . $searchQuery; ?>">Prev</a>
      </li>
    <?php endif; ?>

    <!-- Halaman h-3 … h-1 -->
    <?php for ($p = $startPage; $p < $page; $p++): ?>
      <li class="page-item">
        <a class="page-link" href="?page=<?php echo $p . $searchQuery; ?>">
          <?php echo $p; ?>
        </a>
      </li>
    <?php endfor; ?>

    <!-- Halaman h (aktif) -->
    <li class="page-item active" aria-current="page">
      <span class="page-link"><?php echo $page; ?></span>
    </li>

    <!-- Halaman h+1 … h+3 -->
    <?php for ($p = $page + 1; $p <= $endPage; $p++): ?>
      <li class="page-item">
        <a class="page-link" href="?page=<?php echo $p . $searchQuery; ?>">
          <?php echo $p; ?>
        </a>
      </li>
    <?php endfor; ?>

    <!-- Next & Last -->
    <?php if ($page < $totalPages): ?>
      <li class="page-item">
        <a class="page-link" href="?page=<?php echo ($page + 1) . $searchQuery; ?>">Next</a>
      </li>
      <li class="page-item">
        <a class="page-link" href="?page=<?php echo $totalPages . $searchQuery; ?>">Last</a>
      </li>
    <?php endif; ?>
  </ul>
</nav>



  <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
