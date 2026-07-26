<?php
// view_article.php - Detail artikel (Mobile Responsive)

require_once "../db.php";
require_once "../config.php";
include("../../../gtag.js.php");

try {
    $db = new Database($config['database']);
    $conn = $db->getConnection();
} catch (Exception $e) {
    die("Gagal koneksi database: " . $e->getMessage());
}

// Ambil parameter
$id    = isset($_GET['id']) ? (int) $_GET['id'] : 0;
$user  = isset($_GET['user']) ? $_GET['user'] : '';
$title = isset($_GET['title']) ? $_GET['title'] : '';

// Validasi parameter
if ($id <= 0 || empty($title)) {
    die("Parameter tidak valid.");
}

// Parsing pretty URLs jika diperlukan
if (empty($user) || empty($title)) {
    $uri   = trim($_SERVER['REQUEST_URI'], '/');
    $parts = explode('/', $uri);
    if (count($parts) >= 4) {
        $user  = $parts[count($parts)-3];
        $id    = (int)$parts[count($parts)-2];
        $title = $parts[count($parts)-1];
    }
}
if ($id <= 0 || empty($user) || empty($title)) {
    header("HTTP/1.0 404 Not Found");
    die("Parameter tidak valid.");
}

// Ambil artikel dengan validasi username publisher
$stmt = $conn->prepare(
    "SELECT
        a.title,
        a.html_content,
        a.images,
        a.tag,
        a.created_at,
        a.publishers_local_id,
        pq.username
    FROM articles a
    LEFT JOIN publisher_quota pq ON a.publishers_local_id = pq.publisher_id
    WHERE a.id = ? AND a.ispublished = 1 AND pq.username = ?
    LIMIT 1"
);
$stmt->bind_param("is", $id, $user);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows === 0) {
    die("Artikel tidak ditemukan, belum dipublikasikan, atau Anda tidak berhak melihatnya.");
}
$article = $result->fetch_assoc();
$stmt->close();

// Ambil 5 artikel random dari user yang sama (kecuali artikel saat ini)
$publisher_id = $article['publishers_local_id'];
$stmt2 = $conn->prepare(
    "SELECT id, title
     FROM articles
     WHERE publishers_local_id = ? AND ispublished = 1 AND id <> ?
     ORDER BY RAND()
     LIMIT 10"
);
$stmt2->bind_param("ii", $publisher_id, $id);
$stmt2->execute();
$result2 = $stmt2->get_result();
$related = [];
while ($row = $result2->fetch_assoc()) {
    $related[] = $row;
}
$stmt2->close();

// Parse images (JSON atau comma-separated)
$images = [];
if (!empty($article['images'])) {
    $decoded = json_decode($article['images'], true);
    $images = (json_last_error() === JSON_ERROR_NONE && is_array($decoded))
        ? $decoded
        : array_filter(array_map('trim', explode(',', $article['images'])));
}

// Parse tags
$tags = [];
if (!empty($article['tag'])) {
    $tags = array_filter(array_map('trim', explode(',', $article['tag'])));
}
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
     <!-- <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no">-->
    <!-- 1. Meta viewport wajib agar media query mobile berfungsi -->
  <meta name="viewport" content="width=device-width, initial-scale=1">
    <title><?php echo htmlspecialchars($article['title'], ENT_QUOTES, 'UTF-8'); ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link
  href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css"
  rel="stylesheet"
/>
</head>
<style type="text/css">
/* 1) Kalau mau simpel: biarkan iframe menyesuaikan lebar container */
.ql-editor .ql-video {
  display: block;
  width: 100% !important;
  height: auto !important;
}

/* 2) Atau pakai wrapper untuk jaga aspek rasio 16:9 */
.video-responsive {
  position: relative;
  padding-bottom: 56.25%; /* 9/16 = 0.5625 */
  height: 0;
  overflow: hidden;
}
.video-responsive .ql-video {
  position: absolute;
  top: 0; left: 0;
  width: 100%;
  height: 100%;
}

</style>

<body>
<div class="container-fluid mt-4">
    <div class="row">
        <!-- Main Content -->
        <div class="col-12 col-lg-8 mb-4">
            <h1 class="mb-3"><?php echo htmlspecialchars($article['title'], ENT_QUOTES, 'UTF-8'); ?></h1>
            <h5><?php echo htmlspecialchars($article['username'], ENT_QUOTES, 'UTF-8'); ?></h5>
            <?php

              $slug = preg_replace('/[^A-Za-z0-9 ]/', '', $article['title']);
            $slug = str_replace(' ', '_', $slug);

            $this_providers_domain_url = get_providers_domain_url_json2("../providers_data.json", 1);

            $permalink = $this_providers_domain_url."/blog/".urlencode($article['username'])."/".
            $id."/".urlencode($slug);

            // Setelah Anda generate $permalink dan $article['title']:
  $encoded_url   = rawurlencode($permalink);
  $encoded_title = rawurlencode($article['title']);

            ?>
            <p class="text-muted small mb-4"><a href="<?php echo htmlspecialchars($permalink); ?>" target="_blank">&nbsp;Link&nbsp;</a>
</p>


            <p class="text-muted small mb-4">Diterbitkan: <?php echo htmlspecialchars($article['created_at'], ENT_QUOTES, 'UTF-8'); ?></p>

            <!-- Share buttons -->
  <div class="btn-group mb-4" role="group" aria-label="Share artikel">
    <!-- Facebook -->
    <a
      href="https://www.facebook.com/sharer/sharer.php?u=<?php echo $encoded_url; ?>"
      target="_blank"
      class="btn btn-outline-primary"
      title="Share di Facebook"
    >
      <i class="fab fa-facebook-f"></i>
    </a>
    <!-- Twitter -->
    <a
      href="https://twitter.com/intent/tweet?url=<?php echo $encoded_url; ?>&text=<?php echo $encoded_title; ?>"
      target="_blank"
      class="btn btn-outline-info"
      title="Tweet"
    >
      <i class="fab fa-twitter"></i>
    </a>
    <!-- LinkedIn -->
    <a
      href="https://www.linkedin.com/sharing/share-offsite/?url=<?php echo $encoded_url; ?>"
      target="_blank"
      class="btn btn-outline-primary"
      title="Share di LinkedIn"
    >
      <i class="fab fa-linkedin-in"></i>
    </a>
    <!-- Copy Link -->
    <button
      type="button"
      class="btn btn-outline-secondary"
      id="copyLinkBtn"
      title="Salin link ke clipboard"
    >
      <i class="fas fa-copy"></i>
    </button>
  </div>

            <?php if (!empty($images)): ?>
                <div class="mb-4">
                    <?php foreach ($images as $img): ?>
                        <img src="<?php echo htmlspecialchars($img, ENT_QUOTES, 'UTF-8'); ?>" class="img-fluid rounded mb-3" alt="<?php echo htmlspecialchars($article['title'], ENT_QUOTES, 'UTF-8'); ?>">
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>

            <div class="article-content mb-4">
                <?php
                // Perbaiki path dan tambahkan kelas responsive untuk <img>
                $content = str_replace(
                    'src="uploads/',
                    'src="../../../uploads/',
                    $article['html_content']
                );
                $content = str_replace('<img ', '<img class="img-fluid rounded mb-3" ', $content);
                echo $content;
                ?>
            </div>

            <?php if (!empty($tags)): ?>
                <div class="mb-4">
                    <?php foreach ($tags as $tag): ?>
                        <span class="badge bg-secondary me-1 mb-1"><?php echo htmlspecialchars($tag, ENT_QUOTES, 'UTF-8'); ?></span>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>

            <!-- Native Ads Script -->
            <script type="text/javascript" src="<?php echo $this_providers_domain_url; ?>/show_ads_native_landscape.js.php?pubId=<?php echo $pubId; ?>&pubProvName=<?php echo $this_providers_name; ?>&maxads=1&column=1"></script>

            <a href="../../../blog/<?php echo htmlspecialchars($user, ENT_QUOTES, 'UTF-8'); ?>" class="btn btn-outline-secondary d-block mt-3">Kembali</a>
        </div>

        <!-- Sidebar: Artikel Lainnya -->
        <div class="col-12 col-lg-4">
            <h4 class="mb-3">Artikel Lain <?php echo htmlspecialchars($username); ?></h4>
            <?php if (!empty($related)): ?>
                <?php foreach ($related as $row): ?>
                    <?php $slug = strtolower(trim(preg_replace('/[^A-Za-z0-9-]+/', '-', $row['title']), '-')); ?>
                    <div class="mb-3">
                        <a href="/blog/<?php echo urlencode($article['username']); ?>/<?php echo $row['id']; ?>/<?php echo urlencode($slug); ?>" class="text-decoration-none">
                            <?php echo htmlspecialchars($row['title'], ENT_QUOTES, 'UTF-8'); ?>
                        </a>
                        <br>
                       
                    </div>
                <?php endforeach; ?>
            <?php else: ?>
                <p>Tidak ada artikel lain <?php echo htmlspecialchars($username); ?>.</p>
            <?php endif; ?>
            <!-- Native Ads Script -->
            <script type="text/javascript" src="<?php echo $this_providers_domain_url; ?>/show_ads_native.js.php?pubId=<?php echo $pubId; ?>&pubProvName=<?php echo $this_providers_name; ?>&maxads=1&column=1"></script>
            <a href="../../../blog/<?php echo htmlspecialchars($user, ENT_QUOTES, 'UTF-8'); ?>" class="btn btn-outline-secondary d-block mt-3">Kembali</a>
        </div>

    </div>
</div>
<script>
  document.getElementById('copyLinkBtn').addEventListener('click', function() {
    const link = '<?php echo $permalink; ?>';
    navigator.clipboard.writeText(link)
      .then(() => {
        // Umpan balik sederhana
        alert('Link berhasil disalin ke clipboard!');
      })
      .catch(err => {
        console.error('Gagal menyalin: ', err);
      });
  });
</script>

</body>
</html>