<?php
// view_article.php - Detail artikel (Mobile Responsive)

require_once "../db.php";
require_once "../config.php";
require_once "../kce/lib.php";
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
        a.json_quiz, 
        a.pub_id,
        a.publishers_local_id,
         a.wav,        
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

// Gunakan embedding artikel yang sudah tersimpan. Tidak ada panggilan API saat halaman dibuka.
try {
    $vectorRelatedArticles = kce_related_articles_by_article($conn, $id, 3);
} catch (Throwable $relatedError) {
    error_log('Related article embedding: ' . $relatedError->getMessage());
    $vectorRelatedArticles = [];
}

// Sponsored content KCE dicocokkan langsung dengan embedding artikel tersimpan.
try {
    $articleSponsors = kce_sponsors_for_article($conn, $id, 2);
} catch (Throwable $sponsorError) {
    error_log('Article KCE sponsors: ' . $sponsorError->getMessage());
    $articleSponsors = [];
}
$articlePlacementId = hash('sha256', kce_ip_hash() . '|article|' . $id);

// Konfigurasi iklan harus tersedia saat view_article.php dipanggil langsung oleh rewrite URL.
//
// providers_data.json is a flat array of {id, providers_name, providers_domain_url}
// (see public_html/providers_data.json) — this used to look for a non-existent
// {"providers": [{id, domain_url, name}]} wrapper, so $this_providers_domain_url
// was always empty (the ad <script src> below only kept working by accident,
// since a leading "/" is still a valid root-relative URL).
$pubId = (int) $article['pub_id'];
$this_providers_domain_url = '';
$this_providers_name = '';
$providerData = json_decode((string) @file_get_contents(__DIR__ . "/../providers_data.json"), true);
foreach ((array) $providerData as $provider) {
    if ((int) ($provider['id'] ?? 0) === 1) {
        $this_providers_domain_url = (string) ($provider['providers_domain_url'] ?? '');
        $this_providers_name = (string) ($provider['providers_name'] ?? '');
        break;
    }
}

// ==========================
// MULAI SISIPKAN KODE LOGGING
// ==========================

// 1) Generate slug dari judul untuk nama file
$rawTitle   = $article['title'];
$logSlug    = preg_replace('/[^A-Za-z0-9 ]/', '', $rawTitle);
$logSlug    = str_replace(' ', '_', $logSlug);

// 2) Timestamp dalam zona Asia/Jakarta
date_default_timezone_set('Asia/Jakarta');
$timestamp  = date('Y-m-d H:i:s');

// 3) Ambil IP address visitor (cek beberapa header)
if (!empty($_SERVER['HTTP_CLIENT_IP'])) {
    $ip = $_SERVER['HTTP_CLIENT_IP'];
} elseif (!empty($_SERVER['HTTP_X_FORWARDED_FOR'])) {
    $ipList = explode(',', $_SERVER['HTTP_X_FORWARDED_FOR']);
    $ip = trim($ipList[0]);
} else {
    $ip = $_SERVER['REMOTE_ADDR'];
}


// 3a) Ambil User Agent
$userAgent = isset($_SERVER['HTTP_USER_AGENT']) ? $_SERVER['HTTP_USER_AGENT'] : 'unknown';


// 4) Tentukan source: parameter UTM jika ada, kalau tidak, HTTP_REFERER, kalau juga tidak ada, "direct"
$source     = '';
$utmParams  = [];
foreach (['utm_source','utm_medium','utm_campaign','utm_term','utm_content'] as $u) {
    if (isset($_GET[$u]) && $_GET[$u] !== '') {
        $utmParams[] = $u . '=' . urlencode($_GET[$u]);
    }
}
if (!empty($utmParams)) {
    $source = implode('&', $utmParams);
} else {
    $source = isset($_SERVER['HTTP_REFERER']) && $_SERVER['HTTP_REFERER'] !== ''
              ? $_SERVER['HTTP_REFERER']
              : 'direct';
}

// 5) URL lengkap yang sedang diakses
$fullUrl    = (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? "https" : "http")
            . "://{$_SERVER['HTTP_HOST']}{$_SERVER['REQUEST_URI']}";

// 6) Nama file log: {id}_{username}_{articleslug}.txt
$articleId  = $id;
$username   = $article['username'];
$filename   = "{$articleId}_{$username}_{$logSlug}.txt";

// 7) Path lengkap ke folder traffic_logs
$logDir     = __DIR__ . '/traffic_logs';
$logPath    = "{$logDir}/{$filename}";

// 8) Baris log yang akan ditulis, sekarang mencakup User Agent setelah IP
$logLine    = "{$timestamp}|{$ip}|{$userAgent}|{$source}|{$fullUrl}" . PHP_EOL;

// 9) Buat folder jika belum ada
if (!is_dir($logDir)) {
    mkdir($logDir, 0755, true);
}

// 10) Tulis ke file (append)
file_put_contents($logPath, $logLine, FILE_APPEND | LOCK_EX);

// Hitung visitor manusia unik hari ini untuk artikel ini. Kombinasi IP dan
// user-agent dipakai hanya di memori; nilai mentah tidak ditampilkan ke publik.
// Semua file berawalan ID artikel ikut dibaca agar log lama tetap terhitung
// apabila judul (dan nama file log) pernah berubah.
$today = date('Y-m-d');
$sevenDayStart = date('Y-m-d', strtotime('-6 days'));
$thirtyDayStart = date('Y-m-d', strtotime('-29 days'));
$uniqueVisitorsToday = [];
$uniqueVisitorsSevenDays = [];
$uniqueVisitorsThirtyDays = [];
$uniqueVisitorsTotal = [];
$botPattern = '/bot|crawler|spider|slurp|bingpreview|facebookexternalhit|whatsapp|telegrambot|headless|lighthouse|pagespeed|uptimerobot|dataforseo/i';
$articleLogFiles = glob($logDir . '/' . $articleId . '_*.txt') ?: [];

foreach ($articleLogFiles as $articleLogFile) {
    $handle = @fopen($articleLogFile, 'rb');
    if ($handle === false) {
        continue;
    }

    while (($line = fgets($handle)) !== false) {
        $parts = explode('|', rtrim($line, "\r\n"), 5);
        if (count($parts) < 3) {
            continue;
        }

        $visitorIp = trim($parts[1]);
        $visitorAgent = trim($parts[2]);
        if ($visitorIp === '' || preg_match($botPattern, $visitorAgent)) {
            continue;
        }

        $visitorKey = hash('sha256', $visitorIp . "\n" . $visitorAgent);
        $visitDate = substr($parts[0], 0, 10);
        $uniqueVisitorsTotal[$visitorKey] = true;
        if ($visitDate >= $thirtyDayStart && $visitDate <= $today) {
            $uniqueVisitorsThirtyDays[$visitorKey] = true;
        }
        if ($visitDate >= $sevenDayStart && $visitDate <= $today) {
            $uniqueVisitorsSevenDays[$visitorKey] = true;
        }
        if ($visitDate === $today) {
            $uniqueVisitorsToday[$visitorKey] = true;
        }
    }
    fclose($handle);
}
$uniqueVisitorCountToday = count($uniqueVisitorsToday);
$uniqueVisitorCountSevenDays = count($uniqueVisitorsSevenDays);
$uniqueVisitorCountThirtyDays = count($uniqueVisitorsThirtyDays);
$uniqueVisitorCountTotal = count($uniqueVisitorsTotal);

// ==========================
// SELESAI SISIPKAN KODE LOGGING
// ==========================



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

// Single slug algorithm shared by the canonical URL, share/permalink links,
// and the related-articles sidebar (previously each built its own slightly
// different slug, which meant the same article was reachable/linked under
// several distinct URLs with no canonical tag to unify them for search
// engines). Matches the algorithm already used by sitemap.php.
function seo_slugify($title) {
    $slug = strtolower(trim((string) $title));
    $slug = preg_replace('/[^a-z0-9]+/', '-', $slug);
    return trim($slug, '-');
}

// Absolute site origin, independent of providers_data.json (whose structure
// doesn't actually match what this file reads from it, so $this_providers_domain_url
// is empty here) — needed for canonical/OG/JSON-LD URLs to be fully qualified.
$site_origin = (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? 'https' : 'http')
             . '://' . $_SERVER['HTTP_HOST'];

$article_slug = seo_slugify($article['title']);
$canonical_url = $site_origin . '/blog/' . rawurlencode($article['username']) . '/' . $id . '/' . rawurlencode($article_slug);

// Meta description: a plain-text excerpt of the article body, falling back
// to the tags if the body is empty. Kept short for search-result snippets.
$meta_description_source = preg_replace('/\s+/', ' ', trim(strip_tags((string) $article['html_content'])));
if ($meta_description_source === '' && !empty($article['tag'])) {
    $meta_description_source = str_replace(',', ', ', $article['tag']);
}
$meta_description = mb_substr($meta_description_source, 0, 160);
if (mb_strlen($meta_description_source) > 160) {
    $meta_description = rtrim($meta_description) . '…';
}

// Absolute OG/Twitter image (first article image), if any.
$og_image_url = !empty($images) ? $site_origin . '/' . ltrim($images[0], '/') : '';

// JSON-LD structured data (Article rich snippet: author, date, image).
$jsonld = [
    '@context' => 'https://schema.org',
    '@type' => 'BlogPosting',
    'mainEntityOfPage' => ['@type' => 'WebPage', '@id' => $canonical_url],
    'headline' => $article['title'],
    'datePublished' => date('c', strtotime($article['created_at'])),
    'author' => ['@type' => 'Person', 'name' => $article['username']],
    'publisher' => ['@type' => 'Organization', 'name' => 'KumpulBlogger'],
];
if ($og_image_url !== '') {
    $jsonld['image'] = $og_image_url;
}
$jsonld_json = str_replace('</', '<\/', json_encode($jsonld, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE));
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
     <!-- <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no">-->
    <!-- 1. Meta viewport wajib agar media query mobile berfungsi -->
  <meta name="viewport" content="width=device-width, initial-scale=1">

  <meta name="description" content="<?php echo htmlspecialchars($meta_description, ENT_QUOTES, 'UTF-8'); ?>">
  <link rel="canonical" href="<?php echo htmlspecialchars($canonical_url, ENT_QUOTES, 'UTF-8'); ?>">

  <!-- Facebook App ID -->
<meta property="fb:app_id" content="3051424688364696" />
<!-- Open Graph (supaya URL, judul, deskripsi tiap artikel dikenali) -->
<meta property="og:type"        content="article" />
<meta property="og:site_name"   content="KumpulBlogger" />
<meta property="og:url"         content="<?php echo htmlspecialchars($canonical_url, ENT_QUOTES, 'UTF-8'); ?>" />
<meta property="og:title"       content="<?php echo htmlspecialchars($article['title'], ENT_QUOTES, 'UTF-8'); ?>" />
<meta property="og:description" content="<?php echo htmlspecialchars($meta_description, ENT_QUOTES, 'UTF-8'); ?>" />
<?php if ($og_image_url !== ''): ?>
<meta property="og:image"       content="<?php echo htmlspecialchars($og_image_url, ENT_QUOTES, 'UTF-8'); ?>" />
<?php endif; ?>
<meta name="twitter:card"        content="<?php echo $og_image_url !== '' ? 'summary_large_image' : 'summary'; ?>" />
<meta name="twitter:title"       content="<?php echo htmlspecialchars($article['title'], ENT_QUOTES, 'UTF-8'); ?>" />
<meta name="twitter:description" content="<?php echo htmlspecialchars($meta_description, ENT_QUOTES, 'UTF-8'); ?>" />
<?php if ($og_image_url !== ''): ?>
<meta name="twitter:image"       content="<?php echo htmlspecialchars($og_image_url, ENT_QUOTES, 'UTF-8'); ?>" />
<?php endif; ?>
<script type="application/ld+json"><?php echo $jsonld_json; ?></script>
<!-- fb-root dan SDK -->
<div id="fb-root"></div>
<script async defer crossorigin="anonymous"
  src="https://connect.facebook.net/id_ID/sdk.js#xfbml=1&version=v16.0&appId=3051424688364696&autoLogAppEvents=1">
</script>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>


    <title><?php echo htmlspecialchars($article['title'], ENT_QUOTES, 'UTF-8'); ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link
  href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css"
  rel="stylesheet"
/>
</head>
<style type="text/css">
:root { --brand:#087f5b; --brand-dark:#075c46; --ink:#20312d; --muted:#667b75; --bg:#f3f8f6; }
body { margin:0; background:var(--bg); color:var(--ink); font-family:-apple-system,BlinkMacSystemFont,"Segoe UI",sans-serif; }
.site-nav { background:rgba(255,255,255,.96); box-shadow:0 2px 16px rgba(12,70,53,.08); }
.site-brand { color:var(--brand-dark); font-size:1.15rem; font-weight:800; text-decoration:none; }
.article-shell { max-width:1240px; }
.article-card, .sidebar-card { border:0; border-radius:1rem; background:#fff; box-shadow:0 4px 20px rgba(31,65,54,.08); }
.article-card { padding:clamp(1.1rem,3vw,2.5rem); }
.article-header { padding-bottom:1.25rem; margin-bottom:1.5rem; border-bottom:1px solid #e6eeeb; }
.article-title { margin:0 0 .8rem; font-size:clamp(1.8rem,4vw,3rem); font-weight:850; line-height:1.15; letter-spacing:-.035em; }
.author-line { display:flex; flex-wrap:wrap; align-items:center; gap:.5rem; color:var(--muted); font-size:.88rem; }
.author-name { color:var(--brand-dark); font-weight:750; }
.share-row { display:flex; flex-wrap:wrap; gap:.4rem; margin:1rem 0 1.5rem; }
.share-row .btn { width:42px; height:40px; display:inline-flex; align-items:center; justify-content:center; border-radius:.55rem!important; }
.article-hero-image { display:block; width:100%; max-height:620px; object-fit:cover; border-radius:.85rem; }
.audio-box { margin:1rem 0 1.5rem; padding:1rem; border-radius:.75rem; background:#eef8f4; }
.audio-box audio { display:block; width:100%; margin-top:.5rem; }
.ad-slot { margin:1.5rem 0; padding:1rem; border:1px solid #dce9e4; border-radius:.85rem; background:#fbfdfc; overflow:hidden; }
.ad-label { display:block; margin-bottom:.5rem; color:#879791; font-size:.65rem; font-weight:750; letter-spacing:.08em; text-align:center; text-transform:uppercase; }
.article-content { color:#293d37; font-size:1.06rem; line-height:1.85; overflow-wrap:anywhere; }
.article-content h2, .article-content h3, .article-content h4 { margin-top:1.8em; color:#173c32; font-weight:800; line-height:1.3; }
.article-content p { margin-bottom:1.2em; }
.article-content img { max-width:100%; height:auto; }
.article-content blockquote { padding:.8rem 1rem; border-left:4px solid var(--brand); background:#f2f8f6; color:#4c625b; }
.article-content iframe { max-width:100%; }
.tag-list { display:flex; flex-wrap:wrap; gap:.45rem; margin:1.5rem 0; }
.tag-list a { padding:.38rem .7rem; border-radius:999px; background:#e7f5ef; color:var(--brand-dark); font-size:.78rem; font-weight:650; text-decoration:none; }
.sidebar-card { position:sticky; top:1rem; padding:1.25rem; }
.sidebar-title { margin:0 0 1rem; font-size:1.05rem; font-weight:800; }
.related-link { display:block; padding:.8rem 0; border-bottom:1px solid #e8efec; color:#30453f; font-weight:650; line-height:1.45; text-decoration:none; }
.related-link:hover { color:var(--brand); }
.vector-related { margin:2rem 0 1.25rem; padding-top:1.5rem; border-top:1px solid #e1ebe7; }
.vector-related-heading { margin-bottom:.35rem; color:var(--brand-dark); font-size:1.35rem; font-weight:850; }
.vector-related-subtitle { margin-bottom:1rem; color:var(--muted); font-size:.9rem; }
.vector-related-grid { display:grid; grid-template-columns:repeat(3,minmax(0,1fr)); gap:.8rem; }
.vector-related-card { display:flex; flex-direction:column; min-width:0; padding:1rem; border:1px solid #deebe6; border-radius:.8rem; background:#f8fcfa; color:inherit; text-decoration:none; transition:transform .2s ease,box-shadow .2s ease,border-color .2s ease; }
.vector-related-card:hover { color:inherit; transform:translateY(-3px); border-color:#a9d7c7; box-shadow:0 8px 18px rgba(31,101,78,.12); }
.vector-related-card h3 { margin:0 0 .55rem; color:var(--brand-dark); font-size:1rem; font-weight:800; line-height:1.4; }
.vector-related-card p { margin:0 0 .8rem; color:var(--muted); font-size:.84rem; line-height:1.55; }
.vector-related-card span { margin-top:auto; color:var(--brand); font-size:.82rem; font-weight:750; }
.article-sponsors { margin:2rem 0; padding:1.15rem; border:1px solid #eadca7; border-radius:.9rem; background:#fffaf0; }
.article-sponsors-heading { margin-bottom:.8rem; color:#80651f; font-size:.68rem; font-weight:800; letter-spacing:.12em; text-transform:uppercase; }
.article-sponsor-card { display:grid; grid-template-columns:1fr auto; gap:1rem; align-items:center; padding:1rem; border:1px solid #ebdfb7; border-radius:.75rem; background:#fff; }
.article-sponsor-card + .article-sponsor-card { margin-top:.7rem; }
.article-sponsor-card h3 { margin:0 0 .35rem; color:#443817; font-size:1.02rem; font-weight:800; }
.article-sponsor-card h3 a { color:inherit; font-size:inherit; font-weight:inherit; text-decoration:none; }
.article-sponsor-card h3 a:hover { color:#745b13; text-decoration:underline; }
.article-sponsor-card p { margin:0 0 .65rem; color:#6c6249; font-size:.88rem; line-height:1.55; }
.article-sponsor-card a { color:#745b13; font-size:.85rem; font-weight:800; text-decoration:none; }
.article-sponsor-card a:hover { text-decoration:underline; }
.article-sponsor-card img { width:150px; height:82px; border-radius:.6rem; object-fit:cover; }
.article-sponsor-preview { cursor:zoom-in; }
.sponsor-image-dialog { width:auto; max-width:92vw; padding:0; border:0; border-radius:1rem; background:transparent; box-shadow:0 24px 70px rgba(0,0,0,.38); overflow:visible; }
.sponsor-image-dialog::backdrop { background:rgba(10,20,17,.78); backdrop-filter:blur(3px); }
.sponsor-image-dialog-inner { position:relative; display:grid; place-items:center; padding:.65rem; border-radius:1rem; background:#fff; }
.sponsor-image-dialog img { display:block; width:auto; max-width:88vw; height:auto; max-height:82vh; border-radius:.7rem; object-fit:contain; }
.sponsor-image-dialog-close { position:absolute; z-index:2; top:-.8rem; right:-.8rem; display:grid; place-items:center; width:2.25rem; height:2.25rem; padding:0; border:2px solid #fff; border-radius:50%; background:#18352c; color:#fff; font-size:1.35rem; line-height:1; box-shadow:0 4px 12px rgba(0,0,0,.25); cursor:pointer; }
.sponsor-image-dialog-close:hover { background:#087f5b; }
.back-link { border-color:#b9cac4; color:#40564f; }
@media (max-width:991.98px) { .sidebar-card { position:static; } }
@media (max-width:767.98px) { .vector-related-grid { grid-template-columns:1fr; } }
@media (max-width:575.98px) { .article-sponsor-card { grid-template-columns:1fr; } .article-sponsor-card img { width:100%; height:140px; } }
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
.visitor-stat {
  display: inline-flex;
  align-items: center;
  gap: .35rem;
  margin-top: .55rem;
  padding: .3rem .65rem;
  border-radius: 999px;
  background: #eef7f1;
  color: #287247;
  font-size: .82rem;
  font-weight: 600;
}

</style>

<body>
<nav class="site-nav py-3"><div class="container article-shell d-flex align-items-center justify-content-between"><a class="site-brand" href="../../../blogs/">KumpulBlogger</a><a href="../../../blogs/" class="btn btn-sm btn-outline-success">Jelajahi Artikel</a></div></nav>
<div class="container article-shell py-4">

     <!-- Link Kembali ke Blogs -->
    <p><a href="../../../blogs" class="btn btn-sm back-link mb-2">&larr; Kembali ke Blogs</a></p>

    <div class="row">
        <!-- Main Content -->
        <div class="col-12 col-lg-8 mb-4">
          <article class="article-card">
            <header class="article-header">
            <h1 class="article-title"><?php echo htmlspecialchars($article['title'], ENT_QUOTES, 'UTF-8'); ?></h1>
            <div class="author-line"><span>Ditulis oleh</span><span class="author-name"><?php echo htmlspecialchars($article['username'], ENT_QUOTES, 'UTF-8'); ?></span><span>&middot;</span><time><?php echo htmlspecialchars($article['created_at'], ENT_QUOTES, 'UTF-8'); ?></time></div>
            <div>
              <span class="visitor-stat" title="Visitor unik manusia berdasarkan kombinasi IP dan browser untuk hari ini"><i class="fas fa-eye"></i> <?php echo number_format($uniqueVisitorCountToday, 0, ',', '.'); ?> unik hari ini</span>
              <span class="visitor-stat" title="Visitor unik manusia selama 7 hari terakhir, termasuk hari ini"><i class="fas fa-calendar-week"></i> <?php echo number_format($uniqueVisitorCountSevenDays, 0, ',', '.'); ?> unik 7 hari</span>
              <span class="visitor-stat" title="Visitor unik manusia selama 30 hari terakhir, termasuk hari ini"><i class="fas fa-calendar-alt"></i> <?php echo number_format($uniqueVisitorCountThirtyDays, 0, ',', '.'); ?> unik 30 hari</span>
              <span class="visitor-stat" title="Total visitor unik manusia sepanjang riwayat artikel"><i class="fas fa-chart-line"></i> <?php echo number_format($uniqueVisitorCountTotal, 0, ',', '.'); ?> total unik</span>
            </div>
            </header>
            <?php
            // $canonical_url / $article_slug were computed earlier (shared
            // with the <link rel="canonical"> and JSON-LD tags) so the share
            // links always point at the exact same URL search engines index.
            $permalink = $canonical_url;
            $encoded_url   = rawurlencode($permalink);
            $encoded_title = rawurlencode($article['title']);
            ?>

<?php 
  if ($article['wav']!="") { 
?>
 <div class="audio-box">
        <label class="form-label fw-bold"><i class="fas fa-volume-up"></i> Dengarkan Audio:</label>
        <audio controls style="width: 100%;">
            <source src="../../../<?php echo htmlspecialchars($article['wav'], ENT_QUOTES, 'UTF-8'); ?>" type="audio/wav">
            Browser Anda tidak mendukung audio player.
        </audio>
    </div>

<?php 
    }
?>

            <!-- Share buttons -->
  <div class="share-row" role="group" aria-label="Bagikan artikel">
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
                        <img src="../../../<?php echo htmlspecialchars($img, ENT_QUOTES, 'UTF-8'); ?>" class="article-hero-image mb-3" alt="<?php echo htmlspecialchars($article['title'], ENT_QUOTES, 'UTF-8'); ?>">
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>

            <div class="ad-slot"><span class="ad-label">Iklan</span>
            <script type="text/javascript" src="<?php echo $this_providers_domain_url; ?>/show_ads_native_landscape.js.php?pubId=<?php echo $pubId; ?>&pubProvName=<?php echo $this_providers_name; ?>&maxads=10&column=1"></script>
            </div>

      

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

            <?php if (!empty($articleSponsors)): ?>
                <aside class="article-sponsors" aria-label="Sponsored content KCE">
                    <div class="article-sponsors-heading">Sponsored Content &middot; Iklan</div>
                    <?php foreach ($articleSponsors as $articleSponsor): ?>
                        <?php
                        $sponsorId = (int) $articleSponsor['id'];
                        $sponsorToken = kce_sign($sponsorId, $articlePlacementId);
                        $sponsorClickUrl = '/kce/api/event.php?' . http_build_query([
                            'id' => $sponsorId,
                            'type' => 'click',
                            'conversation_id' => $articlePlacementId,
                            'token' => $sponsorToken,
                        ]);
                        $sponsorBanner = kce_public_url($articleSponsor['banner_url'] ?? null);
                        ?>
                        <article class="article-sponsor-card"
                                 data-kce-sponsor-id="<?php echo $sponsorId; ?>"
                                 data-kce-placement="<?php echo htmlspecialchars($articlePlacementId, ENT_QUOTES, 'UTF-8'); ?>"
                                 data-kce-token="<?php echo htmlspecialchars($sponsorToken, ENT_QUOTES, 'UTF-8'); ?>">
                            <div>
                                <h3><a href="<?php echo htmlspecialchars($sponsorClickUrl, ENT_QUOTES, 'UTF-8'); ?>" target="_blank" rel="noopener sponsored"><?php echo htmlspecialchars($articleSponsor['title'], ENT_QUOTES, 'UTF-8'); ?></a></h3>
                                <p><?php echo htmlspecialchars($articleSponsor['body'], ENT_QUOTES, 'UTF-8'); ?></p>
                                <a href="<?php echo htmlspecialchars($sponsorClickUrl, ENT_QUOTES, 'UTF-8'); ?>" target="_blank" rel="noopener sponsored">Kunjungi sponsor &rarr;</a>
                            </div>
                            <?php if ($sponsorBanner): ?>
                                <img class="article-sponsor-preview" src="<?php echo htmlspecialchars($sponsorBanner, ENT_QUOTES, 'UTF-8'); ?>" alt="Banner <?php echo htmlspecialchars($articleSponsor['title'], ENT_QUOTES, 'UTF-8'); ?>" loading="lazy" tabindex="0" role="button" aria-label="Lihat banner <?php echo htmlspecialchars($articleSponsor['title'], ENT_QUOTES, 'UTF-8'); ?> dalam ukuran penuh">
                            <?php endif; ?>
                        </article>
                    <?php endforeach; ?>
                </aside>
                <dialog class="sponsor-image-dialog" id="sponsorImageDialog" aria-label="Pratinjau banner sponsor">
                    <div class="sponsor-image-dialog-inner">
                        <button class="sponsor-image-dialog-close" type="button" aria-label="Tutup pratinjau">&times;</button>
                        <img src="" alt="">
                    </div>
                </dialog>
            <?php endif; ?>

            <?php if (!empty($article['wav'])): ?>
   


<?php endif; ?>

      
<?php
// Tambahkan setelah $content, sebelum fb-comments, misal di bawah isi artikel:
if (!empty($article['json_quiz'])):
    $quiz_title = "Summary Interaktif";
    $quiz = json_decode($article['json_quiz'], true);
    if (json_last_error() === JSON_ERROR_NONE && !empty($quiz['answers'])):
?>
    <div class="mb-4">
        <h5 class="mb-2"><?= $quiz_title ?></h5>
 

 <div class="accordion" id="quizAccordion">
  <?php foreach ($quiz['answers'] as $idx => $qa): ?>
    <div class="accordion-item">
      <h2 class="accordion-header" id="heading<?= $idx ?>">
        <button class="accordion-button collapsed"
          type="button" data-bs-toggle="collapse" data-bs-target="#collapse<?= $idx ?>"
          aria-expanded="false" aria-controls="collapse<?= $idx ?>">
          <?= htmlspecialchars($qa['question']) ?>
        </button>
      </h2>
      <div id="collapse<?= $idx ?>" class="accordion-collapse collapse"
        aria-labelledby="heading<?= $idx ?>" data-bs-parent="#quizAccordion">
        <div class="accordion-body">
          <span class="fw-bold">Jawaban:</span><br>
          <?= nl2br(htmlspecialchars($qa['answer'])) ?>
        </div>
      </div>
    </div>
  <?php endforeach; ?>
</div>



    </div>
    <!-- Pastikan sudah import Bootstrap JS (sudah ada via CDN di atas) -->
<?php
    endif;
endif;
?>




            <?php if (!empty($tags)): ?>
                <div class="tag-list">
                    <?php foreach ($tags as $tag): ?>
                    <a
                href="../../../blogs/?search=<?php echo urlencode($tag); ?>" 
                class="" 
                target="_blank"
                rel="noopener noreferrer"
            >
                        #<?php echo htmlspecialchars($tag, ENT_QUOTES, 'UTF-8'); ?>
                    </a>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>

            <?php if (!empty($vectorRelatedArticles)): ?>
                <section class="vector-related" aria-labelledby="vectorRelatedTitle">
                    <h2 id="vectorRelatedTitle" class="vector-related-heading">Artikel relevan untuk Anda</h2>
                    <p class="vector-related-subtitle">Dipilih berdasarkan kedekatan topik menggunakan vector embedding.</p>
                    <div class="vector-related-grid">
                        <?php foreach ($vectorRelatedArticles as $relatedArticle): ?>
                            <a class="vector-related-card" href="<?php echo htmlspecialchars($relatedArticle['url'], ENT_QUOTES, 'UTF-8'); ?>">
                                <h3><?php echo htmlspecialchars($relatedArticle['title'], ENT_QUOTES, 'UTF-8'); ?></h3>
                                <p><?php echo htmlspecialchars($relatedArticle['excerpt'], ENT_QUOTES, 'UTF-8'); ?></p>
                                <span>Baca artikel &rarr;</span>
                            </a>
                        <?php endforeach; ?>
                    </div>
                </section>
            <?php endif; ?>


            <a href="../../../blog/<?php echo htmlspecialchars($user, ENT_QUOTES, 'UTF-8'); ?>" class="btn btn-outline-secondary d-block mt-3">Artikel lain dari penulis ini</a>
       


            <div class="ad-slot"><span class="ad-label">Iklan</span>
            <script type="text/javascript" src="<?php echo $this_providers_domain_url; ?>/show_ads_native_landscape.js.php?pubId=<?php echo $pubId; ?>&pubProvName=<?php echo $this_providers_name; ?>&maxads=10&column=1"></script>
            </div>

      



<div class="fb-comments"
     data-href="<?= 'https://'.$_SERVER['HTTP_HOST'].$_SERVER['REQUEST_URI']; ?>"
     data-width="100%"
     data-numposts="5"
     data-order-by="social">
</div>
          </article>
        </div>

        <!-- Sidebar: Artikel Lainnya -->
        <div class="col-12 col-lg-4">
          <aside class="sidebar-card">
            <h4 class="sidebar-title">Artikel lain dari <?php echo htmlspecialchars($username); ?></h4>
            <?php if (!empty($related)): ?>
                <?php foreach ($related as $row): ?>
                    <?php $slug = seo_slugify($row['title']); ?>
                    <div>
                        <a href="/blog/<?php echo urlencode($article['username']); ?>/<?php echo $row['id']; ?>/<?php echo urlencode($slug); ?>" class="related-link">
                            <?php echo htmlspecialchars($row['title'], ENT_QUOTES, 'UTF-8'); ?>
                        </a>
                        <br>
                       
                    </div>
                <?php endforeach; ?>
            <?php else: ?>
                <p>Tidak ada artikel lain <?php echo htmlspecialchars($username); ?>.</p>
            <?php endif; ?>
            <div class="ad-slot"><span class="ad-label">Iklan</span>
            <script type="text/javascript" src="<?php echo $this_providers_domain_url; ?>/show_ads_native.js.php?pubId=<?php echo $pubId; ?>&pubProvName=<?php echo $this_providers_name; ?>&maxads=10&column=1"></script>
            </div>
            <a href="../../../blog/<?php echo htmlspecialchars($user, ENT_QUOTES, 'UTF-8'); ?>" class="btn btn-outline-secondary d-block mt-3">Lihat blog penulis</a>
          </aside>
        </div>

    </div>
</div>
<script>
  (function() {
    const dialog = document.getElementById('sponsorImageDialog');
    if (!dialog) return;
    const fullImage = dialog.querySelector('img');
    const closeButton = dialog.querySelector('.sponsor-image-dialog-close');
    const openPreview = function(source) {
      fullImage.src = source.currentSrc || source.src;
      fullImage.alt = source.alt;
      if (typeof dialog.showModal === 'function') {
        if (!dialog.open) dialog.showModal();
      } else {
        dialog.setAttribute('open', '');
      }
    };
    const closePreview = function() {
      if (typeof dialog.close === 'function' && dialog.open) dialog.close();
      else dialog.removeAttribute('open');
    };
    document.querySelectorAll('.article-sponsor-preview').forEach(function(image) {
      image.addEventListener('mouseenter', function() { openPreview(image); });
      image.addEventListener('click', function() { openPreview(image); });
      image.addEventListener('keydown', function(event) {
        if (event.key === 'Enter' || event.key === ' ') {
          event.preventDefault();
          openPreview(image);
        }
      });
    });
    closeButton.addEventListener('click', closePreview);
    dialog.addEventListener('click', function(event) {
      if (event.target === dialog) closePreview();
    });
  })();

  document.querySelectorAll('.article-sponsor-card[data-kce-sponsor-id]').forEach(function(card) {
    fetch('/kce/api/event.php', {
      method: 'POST',
      credentials: 'same-origin',
      keepalive: true,
      headers: {'Content-Type': 'application/json'},
      body: JSON.stringify({
        id: Number(card.dataset.kceSponsorId),
        type: 'impression',
        conversation_id: card.dataset.kcePlacement,
        token: card.dataset.kceToken
      })
    }).catch(function() {});
  });

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
