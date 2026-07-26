<?php
// Set error reporting to log errors
ini_set("display_errors", 0);
ini_set("error_log", "errr_xml.php.txt");
error_reporting(E_ALL);
include("db.php");

date_default_timezone_set("Asia/Jakarta");


// Database connection
    $conn = new mysqli($servername_db, $username_db, $password_db, $dbname_db);

if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// Start output buffering to capture XML content
ob_start();

// XML header
echo '<?xml version="1.0" encoding="UTF-8"?>';
echo "\n<!-- XML Sitemap -->";
echo "\n<urlset xmlns:xsi=\"http://www.w3.org/2001/XMLSchema-instance\" xmlns=\"http://www.sitemaps.org/schemas/sitemap/0.9\">";

// Ambil 2000 artikel terbaru beserta info penulis
$sql = "
    SELECT a.id, a.title, a.created_at,
           pq.username
    FROM articles a
    LEFT JOIN publisher_quota pq ON a.pub_id = pq.pub_id
    WHERE a.ispublished = 1
    ORDER BY a.created_at DESC
    LIMIT 0, 2000";

$result = $conn->query($sql);

// Hitung total
$total_urls = $result->num_rows;
echo "\n<!-- This XML Sitemap contains $total_urls URLs. -->";

if ($total_urls > 0) {
    while ($row = $result->fetch_assoc()) {
        $id       = $row['id'];
        $title    = $row['title'];
        $username = $row['username'];
        $created  = $row['created_at'];

        // Slugify the title
        $slug = strtolower(trim($title));
        $slug = preg_replace('/[^a-z0-9]+/', '-', $slug);
        $slug = trim($slug, '-');

        // Final article URL
        $shareUrl = "https://KumpulBlogger.com/blog/" . urlencode($username) . "/" . $id . "/" . urlencode($slug);
        $lastmod  = (new DateTime($created, new DateTimeZone('Asia/Jakarta')))->format('Y-m-d');

        // Print XML block
        echo "\n<url>";
        echo "\n<loc>$shareUrl</loc>";
        echo "\n<lastmod>$lastmod</lastmod>";
        echo "\n</url>";
    }
}

echo "\n</urlset>";

// Save to file
$xmlContent = ob_get_clean();
file_put_contents('sitemap.xml', $xmlContent);

// Close connection
$conn->close();
?>
