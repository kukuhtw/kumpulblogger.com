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
$limit     = 12;
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
    SELECT a.id, a.pub_id, a.title, LEFT(a.html_content, 12000) AS html_content,
           a.tag, a.created_at, a.wav, a.images,
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

// Konfigurasi provider untuk slot iklan native.
$providerId = 1;
$providerDomain = '';
$providerName = '';
$providerData = json_decode((string) @file_get_contents(__DIR__ . "/../providers_data.json"), true);
foreach ((array) $providerData as $provider) {
    if ((int) ($provider['id'] ?? 0) === $providerId) {
        $providerDomain = rtrim((string) ($provider['providers_domain_url'] ?? ''), '/');
        $providerName = (string) ($provider['providers_name'] ?? '');
        break;
    }
}

// Untuk pagination links
$searchQuery = $searchTerm !== '' ? '&search=' . urlencode($searchTerm) : '';
?>
<!DOCTYPE html>
<html lang="id">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>Blog KumpulBlogger - Artikel Terbaru</title>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
  <style>
    :root { --brand:#087f5b; --brand-dark:#075c46; --ink:#20312d; --muted:#667b75; --surface:#fff; --bg:#f3f8f6; }
    body { background:var(--bg); color:var(--ink); font-family:-apple-system,BlinkMacSystemFont,"Segoe UI",sans-serif; }
    .site-nav { background:rgba(255,255,255,.96); box-shadow:0 2px 16px rgba(12,70,53,.08); }
    .site-brand { color:var(--brand-dark); font-size:1.15rem; font-weight:800; text-decoration:none; }
    .hero { padding:3rem 1.5rem; border-radius:1.2rem; background:linear-gradient(135deg,#063f34,#0b8f69); color:#fff; overflow:hidden; position:relative; }
    .hero:after { content:""; position:absolute; width:260px; height:260px; right:-80px; top:-110px; border-radius:50%; background:rgba(255,255,255,.09); }
    .hero h1 { max-width:700px; margin:0; font-size:clamp(1.8rem,4vw,3rem); font-weight:850; letter-spacing:-.035em; }
    .hero p { max-width:650px; margin:.75rem 0 0; color:rgba(255,255,255,.82); font-size:1.05rem; }
    .search-panel { margin-top:-1.4rem; position:relative; z-index:2; padding:1rem; border-radius:.85rem; background:#fff; box-shadow:0 8px 28px rgba(27,72,58,.12); }
    .search-panel .form-control { min-height:48px; border-color:#d8e5e0; }
    .search-panel .btn { min-width:110px; background:var(--brand); border-color:var(--brand); }
    .section-title { margin:2rem 0 1rem; font-size:1.25rem; font-weight:800; }
    .card { overflow:hidden; border:0; border-radius:1rem; box-shadow:0 4px 18px rgba(31,65,54,.08); transition:transform .2s ease,box-shadow .2s ease; }
    .card:hover { transform:translateY(-4px); box-shadow:0 10px 28px rgba(31,65,54,.14); }
    .card-img-top { width:100%; height:220px!important; max-height:none!important; border:0!important; border-radius:0!important; object-fit:cover; padding:0!important; }
    .article-list { max-width:900px; margin:0 auto; }
    .article-card { display:grid; grid-template-columns:minmax(220px,280px) 1fr; }
    .article-card .card-img-top { height:100%!important; min-height:230px; }
    .article-card .card-body:only-child { grid-column:1 / -1; }
    .card-body { padding:1.25rem; }
    .card-title { font-size:1.12rem; font-weight:800; line-height:1.4; }
    .card-title a { color:var(--ink); }
    .card-title a:hover { color:var(--brand); }
    .card-text { color:var(--muted); line-height:1.65; }
    .article-meta { color:var(--muted); font-size:.78rem; }
    .btn-primary { background:var(--brand); border-color:var(--brand); }
    .btn-primary:hover { background:var(--brand-dark); border-color:var(--brand-dark); }
    .ad-slot { margin:1.5rem 0; padding:1rem; border:1px solid #dce9e4; border-radius:.9rem; background:#fff; box-shadow:0 3px 14px rgba(31,65,54,.06); }
    .ad-label { display:block; margin-bottom:.5rem; color:#879791; font-size:.65rem; font-weight:700; letter-spacing:.08em; text-align:center; text-transform:uppercase; }
    .pagination { flex-wrap:wrap; gap:.25rem; }
    .pagination .page-link { border-radius:.45rem!important; color:var(--brand-dark); }
    .pagination .active .page-link { background:var(--brand); border-color:var(--brand); color:#fff; }
    .site-footer { margin-top:2rem; padding:2rem 0; border-top:1px solid #dce7e3; color:var(--muted); font-size:.85rem; }
    @media (max-width:767.98px) { .article-card { grid-template-columns:1fr; } .article-card .card-img-top { height:220px!important; min-height:0; } }
  </style>
</head>
<body>
  <nav class="site-nav py-3"><div class="container d-flex align-items-center justify-content-between"><a class="site-brand" href="../index.php">KumpulBlogger</a><div><a class="btn btn-sm btn-outline-success" href="../login.php">Masuk</a> <a class="btn btn-sm btn-success" href="../reg.php">Daftar</a></div></div></nav>
  <div class="container py-4 py-md-5">
    <header class="hero"><h1>Temukan cerita dan wawasan terbaru</h1><p>Kumpulan artikel pilihan dari kreator KumpulBlogger tentang teknologi, bisnis, gaya hidup, pendidikan, dan banyak lagi.</p></header>

    <!-- Form Search -->
    <form method="get" action="" class="search-panel mb-4">
      <div class="input-group">
        <input
          type="text"
          name="search"
          class="form-control"
          placeholder="Cari judul, topik, atau kata kunci..."
          value="<?php echo htmlspecialchars($searchTerm); ?>"
        >
        <button class="btn btn-primary" type="submit">Cari</button>
      </div>
    </form>

    <div class="d-flex align-items-end justify-content-between"><h2 class="section-title"><?php echo $searchTerm !== '' ? 'Hasil pencarian' : 'Artikel terbaru'; ?></h2><span class="article-meta mb-3"><?php echo number_format($total, 0, ',', '.'); ?> artikel</span></div>

    <div class="article-list">
      <!-- Kolom Kiri: 10 Artikel Pertama -->
      <div>
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
          <article class="card article-card mb-3">

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
                    &mdash; <?php echo htmlspecialchars($authorDesc); ?>
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
                 class="btn btn-primary btn-sm">Baca selengkapnya</a>

            </div>
          </article>
          <?php if (($i + 1) % 4 === 0 && $providerDomain !== '' && $providerName !== ''): ?>
            <div class="ad-slot" aria-label="Iklan">
              <span class="ad-label">Iklan</span>
              <script type="text/javascript" src="<?php echo htmlspecialchars($providerDomain, ENT_QUOTES, 'UTF-8'); ?>/show_ads_native_landscape.js.php?<?php echo htmlspecialchars(http_build_query(['pubId' => (int) $row['pub_id'], 'pubProvName' => $providerName, 'maxads' => 10, 'column' => 1]), ENT_QUOTES, 'UTF-8'); ?>"></script>
            </div>
          <?php endif; ?>
        <?php endfor; ?>
      </div>

      <!-- Kolom Kanan: 10 Artikel Berikutnya -->
      <div>
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
          <article class="card article-card mb-3">
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
                    &mdash; <?php echo htmlspecialchars($authorDesc); ?>
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
                 class="btn btn-primary btn-sm">Baca selengkapnya</a>
            </div>
          </article>
          <?php if (($i + 1) % 4 === 0 && $providerDomain !== '' && $providerName !== ''): ?>
            <div class="ad-slot" aria-label="Iklan">
              <span class="ad-label">Iklan</span>
              <script type="text/javascript" src="<?php echo htmlspecialchars($providerDomain, ENT_QUOTES, 'UTF-8'); ?>/show_ads_native_landscape.js.php?<?php echo htmlspecialchars(http_build_query(['pubId' => (int) $row['pub_id'], 'pubProvName' => $providerName, 'maxads' => 10, 'column' => 1]), ENT_QUOTES, 'UTF-8'); ?>"></script>
            </div>
          <?php endif; ?>
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
        <a class="page-link" href="?page=1<?php echo $searchQuery; ?>">Awal</a>
      </li>
      <li class="page-item">
        <a class="page-link" href="?page=<?php echo ($page - 1) . $searchQuery; ?>">Sebelumnya</a>
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
        <a class="page-link" href="?page=<?php echo ($page + 1) . $searchQuery; ?>">Berikutnya</a>
      </li>
      <li class="page-item">
        <a class="page-link" href="?page=<?php echo $totalPages . $searchQuery; ?>">Akhir</a>
      </li>
    <?php endif; ?>
  </ul>
</nav>

  <?php if (empty($articles)): ?>
    <div class="text-center py-5"><h3 class="h5">Artikel tidak ditemukan</h3><p class="text-muted">Coba gunakan kata kunci pencarian yang berbeda.</p></div>
  <?php endif; ?>

  <footer class="site-footer text-center">&copy; <?php echo date('Y'); ?> KumpulBlogger &middot; Ruang berbagi cerita dan pengetahuan.</footer>
  </div>
  <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
