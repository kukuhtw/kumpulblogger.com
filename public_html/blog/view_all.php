<?php
// view_all.php?user={USERNAME}
// Menampilkan daftar artikel terbitan terbaru untuk publisher tertentu
// dengan layout Bootstrap, pagination, dan daftar blogger lain di kolom kanan

require_once("../db.php");
require_once("../config.php");
include("../gtag.js.php");

session_start();

// Ambil parameter username dari query string
if (!isset($_GET['user']) || empty($_GET['user'])) {
    die("Parameter 'user' tidak ditemukan.");
}
$username = $_GET['user'];

// Konfigurasi pagination
$perPage = 10;
$page    = isset($_GET['page']) && is_numeric($_GET['page']) ? (int)$_GET['page'] : 1;
$offset  = ($page - 1) * $perPage;

// Ambil juga parameter id (untuk view_article) dan title (slug)
$id    = isset($_GET['id'])    ? (int)   $_GET['id']    : 0;
$title = isset($_GET['title']) ? (string)$_GET['title'] : '';

// Koneksi ke database
try {
    $db   = new Database($config['database']);
    $conn = $db->getConnection();
} catch (Exception $e) {
    die("Gagal koneksi database: " . $e->getMessage());
}

// Ambil pub_id dari tabel publisher_quota berdasarkan username
$stmtPub = $conn->prepare(
    "SELECT pub_id FROM publisher_quota WHERE username = ? LIMIT 1"
);
$stmtPub->bind_param("s", $username);
$stmtPub->execute();
$resultPub = $stmtPub->get_result();
if ($resultPub->num_rows === 0) {
    die("Publisher dengan username '$username' tidak ditemukan.");
}
$rowPub = $resultPub->fetch_assoc();
$pubId = $rowPub['pub_id'];

// Jika id >= 1, tampilkan satu artikel saja
if ($id >= 1) {
    require_once("view_article.php");
    exit;
}

// Ambil parameter search jika ada
$search = isset($_GET['q']) ? trim($_GET['q']) : '';

// Query untuk artikel, cek apakah ada search
if ($search !== '') {
    // Pencarian dengan LIKE pada html_content dan tag
    $stmtArt = $conn->prepare(
        "SELECT id, title, html_content,wav, images , created_at
         FROM articles
         WHERE ispublished = 1 AND pub_id = ?
         AND (html_content LIKE ? OR tag LIKE ?)
         ORDER BY created_at DESC
         LIMIT ?, ?"
    );
    $like = '%' . $search . '%';
    $stmtArt->bind_param("issii", $pubId, $like, $like, $offset, $perPage);
} else {
    // Query normal (tanpa pencarian)
    $stmtArt = $conn->prepare(
        "SELECT id, title, html_content,  images , wav, created_at
         FROM articles
         WHERE ispublished = 1 AND pub_id = ?
         ORDER BY created_at DESC
         LIMIT ?, ?"
    );
    $stmtArt->bind_param("iii", $pubId, $offset, $perPage);
}
$stmtArt->execute();
$resultArt = $stmtArt->get_result();
$articles  = $resultArt->fetch_all(MYSQLI_ASSOC);

// Ambil 10 blogger lain dari publisher_quota (username != current user)
$stmtOthers = $conn->prepare(
    "SELECT username
     FROM publisher_quota
     WHERE username != ?
     ORDER BY rand() DESC
     LIMIT 20"
);
$stmtOthers->bind_param("s", $username);
$stmtOthers->execute();
$resultOthers   = $stmtOthers->get_result();
$otherBloggers  = $resultOthers->fetch_all(MYSQLI_ASSOC);

// Ambil data provider untuk iklan (opsional)
$this_providers_id         = 1;
$this_providers_domain_url = get_providers_domain_url_json2("../providers_data.json", $this_providers_id);
$this_providers_name       = getProvidersNameById_JSON2("../providers_data.json", $this_providers_id);
?>
<!DOCTYPE html>
<html lang="id">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>Artikel oleh <?php echo htmlspecialchars($username); ?></title>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body>
  <div class="container mt-4">

     <!-- Link Kembali ke Blogs -->
    <p><a href="../../../blogs" class="btn btn-secondary mb-3">&larr; Kembali ke Blogs</a></p>

    <div class="row">
      <!-- Kolom Kiri: Artikel -->
      <div class="col-md-8">
        <h1 class="mb-4">
          <a class="page-link" href="/blog/<?php echo urlencode($username); ?>">
            Artikel Terbaru oleh <?php echo htmlspecialchars($username); ?>
          </a>
        </h1>

        <!-- Iklan native -->
        <script
          type="text/javascript"
          src="<?php echo $this_providers_domain_url; ?>/show_ads_native_landscape.js.php?pubId=<?php echo $pubId; ?>&pubProvName=<?php echo urlencode($this_providers_name); ?>&maxads=10&column=1">
        </script>

        <!-- Form Search -->
        <form method="get" class="mb-4" action="">
          <input type="hidden" name="user" value="<?php echo htmlspecialchars($username); ?>">
          <div class="input-group">
            <input type="text" name="q" class="form-control" placeholder="Cari artikel..." value="<?php echo isset($_GET['q']) ? htmlspecialchars($_GET['q']) : ''; ?>">
            <button class="btn btn-outline-secondary" type="submit">Search</button>
          </div>
        </form>

        <?php if (!empty($articles)): ?>
          <?php $count = 0; ?>
          <?php foreach ($articles as $row): ?>
            <?php
              $count++;
              // Buat slug dari title
              $slug = preg_replace('/[^A-Za-z0-9 ]/', '', $row['title']);
              $slug = str_replace(' ', '_', $slug);
              // Snippet 25 kata pertama
              $text     = strip_tags($row['html_content']);
              $words    = preg_split('/\s+/', $text);
              $snippet  = count($words) > 25
                          ? implode(' ', array_slice($words, 0, 25)) . '...'
                          : $text;

              // [MODIFIKASI THUMBNAIL] Cari <img> pertama di html_content
            $thumbnailUrl = '';
if (preg_match('/<img\s+[^>]*src=[\'"]([^\'"]+)[\'"]/i', $row['html_content'], $matches)) {
    $src = $matches[1];

    // Jika src sudah berupa URL lengkap (http:// atau https://), pakai langsung
    if (preg_match('#^https?://#i', $src)) {
        $thumbnailUrl = $src;
    } else {
        // Jika bukan URL lengkap, asumsikan path relatif dan prefix dengan "../"
        $thumbnailUrl = "../../../" . ltrim($src, '/');
    }
}

if ($thumbnailUrl == '' && $page=='') {
      $thumbnailUrl = "../".$row['images'];
    }

if ($thumbnailUrl == '' && $page!='') {
      $thumbnailUrl = "../../../".$row['images'];
    }    

          
            ?>
            <div class="card mb-3">
              <!-- Jika ada thumbnail, tampilkan -->
              <?php  
              //echo "<br>th: ".$thumbnailUrl;
              if ( 
                !empty($thumbnailUrl) 
                && ($thumbnailUrl!="../" && $thumbnailUrl!="../../../")
                )

              {

                ?>
                <img src="<?php echo htmlspecialchars($thumbnailUrl); ?>"
                     class="card-img-top img-fluid img-thumbnail"
                     style="max-height: 200px; object-fit: cover;"
                     alt="Thumbnail Artikel">
              <?php } ?>

              <div class="card-body">
                <h5 class="card-title">
                  <a href="/blog/<?php echo urlencode($username); ?>/<?php echo $row['id']; ?>/<?php echo urlencode($slug); ?>"
                     class="text-decoration-none">
                    <?php echo htmlspecialchars($row['title']); ?>
                  </a>
                </h5>
                <p class="card-text"><?php echo htmlspecialchars($snippet); ?></p>
                <p class="card-text">
                  <small class="text-muted">Diterbitkan: <?php echo $row['created_at']; ?></small>
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
                   class="btn btn-primary btn-sm">
                  Read more
                </a>
              </div>
            </div>

            <?php if ($count === 5): // sisipkan iklan setelah 5 artikel ?>
              <div class="card mb-3">
                <div class="card-body">
                  <script
                    type="text/javascript"
                    src="<?php echo $this_providers_domain_url; ?>/show_ads_native_landscape.js.php?pubId=<?php echo $pubId; ?>&pubProvName=<?php echo urlencode($this_providers_name); ?>&maxads=10&column=1">
                  </script>
                </div>
              </div>
            <?php endif; ?>
          <?php endforeach; ?>

          <!-- Pagination -->
          <nav>
            <ul class="pagination justify-content-center">
              <?php if ($page > 1): ?>
                <li class="page-item">
                  <a class="page-link"
                     href="/blog/<?php echo urlencode($username); ?>/page/<?php echo $page - 1; ?>">
                    Sebelumnya
                  </a>
                </li>
              <?php endif; ?>
              <li class="page-item">
                <a class="page-link"
                   href="/blog/<?php echo urlencode($username); ?>/page/<?php echo $page + 1; ?>">
                  Selanjutnya
                </a>
              </li>
            </ul>
          </nav>
        <?php else: ?>
          <div class="alert alert-info">Tidak ada artikel yang ditemukan untuk publisher ini.</div>
        <?php endif; ?>
      </div>

      <!-- Kolom Kanan: Daftar Blogger Lain -->
      <div class="col-md-4">
        <div class="card">
          <div class="card-header">
            Daftar Blogger Lain
          </div>
          <ul class="list-group list-group-flush">
            <?php foreach ($otherBloggers as $ob): ?>
              <li class="list-group-item">
                <a class="page-link"
                   href="/blog/<?php echo urlencode($ob['username']); ?>">
                  <?php echo htmlspecialchars($ob['username']); ?>
                </a>
              </li>
            <?php endforeach; ?>
          </ul>
        </div>
      </div>
    </div>
  </div>

  <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
<script>
  document.querySelectorAll('audio').forEach(function(audioEl) {
    audioEl.addEventListener('loadedmetadata', function() {
      const dur = audioEl.duration;
      // Format durasi ke menit:detik, misal "02:15"
      const minutes = Math.floor(dur / 60);
      const seconds = Math.floor(dur % 60).toString().padStart(2, '0');
      const info = document.createElement('small');
      info.className = 'text-muted d-block';
      info.textContent = `Durasi: ${minutes}:${seconds}`;
      audioEl.parentNode.insertBefore(info, audioEl.nextSibling);
    });
  });
</script>



</body>
</html>

<?php
// Fungsi pembantu: baca providers_data.json
function get_providers_domain_url_json2($json_file_path, $id) {
    if (!file_exists($json_file_path)) {
        die("JSON file not found.");
    }
    $providers_data = json_decode(file_get_contents($json_file_path), true);
    if ($providers_data === null) {
        die("Failed to decode JSON.");
    }
    foreach ($providers_data as $provider) {
        if ($provider['id'] == $id) {
            return $provider['providers_domain_url'];
        }
    }
    return null;
}

function getProvidersNameById_JSON2($json_file_path, $id) {
    if (!file_exists($json_file_path)) {
        die("JSON file not found.");
    }
    $providers_data = json_decode(file_get_contents($json_file_path), true);
    if ($providers_data === null) {
        die("Failed to decode JSON.");
    }
    foreach ($providers_data as $provider) {
        if ($provider['id'] == $id) {
            return $provider['providers_name'];
        }
    }
    return null;
}
?>
