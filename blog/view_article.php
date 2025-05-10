<?php
// view_article.php - Detail artikel (Mobile Responsive)

require_once("../db.php");
require_once("../config.php");

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
if (empty($user) || empty($title) || $id <= 0) {
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
$stmt = $conn->prepare("
    SELECT 
        a.title, 
        a.html_content, 
        a.images, 
        a.tag, 
        a.created_at, 
        pq.username
    FROM articles a
    LEFT JOIN publisher_quota pq 
        ON a.publishers_local_id = pq.publisher_id
    WHERE a.id = ? 
      AND a.ispublished = 1
      AND pq.username = ?
    LIMIT 1
");
$stmt->bind_param("is", $id, $user);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows === 0) {
    die("Artikel tidak ditemukan, belum dipublikasikan, atau Anda tidak berhak melihatnya.");
}
$article = $result->fetch_assoc();

// Parse images (JSON atau comma-separated)
$images = [];
if (!empty($article['images'])) {
    $decoded = json_decode($article['images'], true);
    $images  = (json_last_error() === JSON_ERROR_NONE && is_array($decoded))
        ? $decoded
        : array_filter(array_map('trim', explode(',', $article['images'])));
}

// Parse tags
$tags = !empty($article['tag']) ? array_filter(array_map('trim', explode(',', $article['tag']))) : [];
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no">
    <title><?php echo htmlspecialchars($article['title'], ENT_QUOTES, 'UTF-8'); ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body>
<div class="container-fluid mt-4">
    <div class="row justify-content-center">
        <div class="col-12 col-md-10 col-lg-8">
            <h1 class="mb-3"><?php echo htmlspecialchars($article['title'], ENT_QUOTES, 'UTF-8'); ?></h1>
            <p class="text-muted small mb-4">Diterbitkan: <?php echo htmlspecialchars($article['created_at'], ENT_QUOTES, 'UTF-8'); ?></p>

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
                // Jadikan img di dalam html_content responsive
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

            <script type="text/javascript" src="<?php echo $this_providers_domain_url; ?>/show_ads_native_landscape.js.php?pubId=<?php echo $pubId; ?>&pubProvName=<?php echo $this_providers_name; ?>&maxads=1&column=1"></script>

            <a href="../../../blog/<?php echo htmlspecialchars($user, ENT_QUOTES, 'UTF-8'); ?>" class="btn btn-outline-secondary d-block mt-3">Kembali</a>
        </div>
    </div>
</div>
</body>
</html>
