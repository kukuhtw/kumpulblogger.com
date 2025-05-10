<?php
// view_all.php?user={USERNAME}
// Menampilkan daftar artikel terbitan terbaru untuk publisher tertentu dengan layout Bootstrap dan pagination

/*

 Buatkan htaccess
 
 http://localhost/kumpulblogger/blog/view_all.php?user=kukuhtw&page=2
 dapat dibaca dan redirect ke 
 http://localhost/kumpulblogger/blog/kukuhtw/page/2
 
 http://localhost/kumpulblogger/blog/view_all.php?user=kukuhtw
 dapat dibaca dan redirect ke 
 http://localhost/kumpulblogger/blog/kukuhtw
 
 

http://localhost/kumpulblogger/blog/view_article.php?user=kukuhtw&id=32&title=Apa_Itu_Fermion_dan_Boson

dapat dibaca dan redirect ke
http://localhost/kumpulblogger/blog/kukuhtw/32/Apa_Itu_Fermion_dan_Boson

perbaiki file php code 



*/

// Include necessary files
require_once("../db.php");
require_once("../config.php");
//require_once("../function.php");
session_start();

// Ambil parameter username dari query string
if (!isset($_GET['user']) || empty($_GET['user'])) {
    die("Parameter 'user' tidak ditemukan.");
}

$username = $_GET['user'];
//echo "<h1>username: ".$username."</h1>";

// Konfigurasi pagination
$perPage = 5;
$page = isset($_GET['page']) && is_numeric($_GET['page']) ? (int)$_GET['page'] : 1;
$offset = ($page - 1) * $perPage;

// Koneksi ke database
try {
    $db = new Database($config['database']);
    $conn = $db->getConnection();
} catch (Exception $e) {
    die("Database connection failed: " . $e->getMessage());
}


// Ambil parameter
$id    = isset($_GET['id']) ? (int) $_GET['id'] : 0;
$user    = isset($_GET['user']) ? $_GET['user'] : '';
$title = isset($_GET['title']) ? $_GET['title'] : '';




// Get user ID and provider domain URL
$this_providers_id = 1;
$this_providers_domain_url = get_providers_domain_url_json2("../providers_data.json", 1);
$this_providers_name = getProvidersNameById_JSON2("../providers_data.json", 1);
//echo "<h1>this_providers_domain_url: ".$this_providers_domain_url."</h1>";



//echo "<br>id: ".$id;

//echo "<h1>user: ".$user."</h1>";
//echo "<h1>id: ".$id."</h1>";


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


if ($id >=1) {
    require_once("view_article.php");
    exit;
}


// Query mengambil artikel yang sudah diterbitkan untuk publisher tersebut
$stmt = $conn->prepare(
    "SELECT id, title, html_content, created_at
     FROM articles
     WHERE ispublished = 1 AND pub_id = ?
     ORDER BY created_at DESC
     LIMIT ?, ?"
);
$stmt->bind_param("iii", $pubId, $offset, $perPage);
$stmt->execute();
$result = $stmt->get_result();
$articles = $result->fetch_all(MYSQLI_ASSOC);
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Daftar Artikel untuk <?php echo htmlspecialchars($username); ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body>
<div class="container mt-4">
    <h1 class="mb-4">Artikel Terbaru oleh <?php echo htmlspecialchars($username); ?></h1>


<script type='text/javascript' src='<?php echo $this_providers_domain_url; ?>/show_ads_native_landscape.js.php?pubId=<?php echo $pubId; ?>&pubProvName=<?php echo $this_providers_name; ?>&maxads=1&column=1'></script>



    <?php if (!empty($articles)): ?>

        
<?php foreach ($articles as $row): ?>
    <?php
        // Buat slug dari title
        $slug = preg_replace('/[^A-Za-z0-9 ]/', '', $row['title']);
        $slug = str_replace(' ', '_', $slug);

        // Snippet 25 kata pertama
        $text = strip_tags($row['html_content']);
        $words = preg_split('/\s+/', $text);
        $snippet = count($words) > 25
            ? implode(' ', array_slice($words, 0, 25)) . '...'
            : $text;
    ?>
    <div class="card mb-3">
        <div class="card-body">
            <h5 class="card-title">
                <!-- ✅ Di sini kita cetak judul sebagai teks link, lalu tutup </a> -->
                <a href="/blog/<?php echo urlencode($username); ?>/<?php echo $row['id']; ?>/<?php echo urlencode($slug); ?>"
                   class="text-decoration-none">
                    <?php echo htmlspecialchars($row['title']); ?>
                </a>
            </h5>
            <p class="card-text"><?php echo htmlspecialchars($snippet); ?></p>
            <p class="card-text">
                <small class="text-muted">Diterbitkan: <?php echo $row['created_at']; ?></small>
            </p>
            <a href="/blog/<?php echo urlencode($username); ?>/<?php echo $row['id']; ?>/<?php echo urlencode($slug); ?>"
               class="btn btn-primary btn-sm">
                Read more
            </a>
        </div>
    </div>
<?php endforeach; ?>




        
<nav>
    <ul class="pagination justify-content-center">
        <?php if ($page > 1): ?>
            <li class="page-item">
                <a class="page-link" href="/blog/<?php echo urlencode($username); ?>/page/<?php echo $page - 1; ?>">Sebelumnya</a>
            </li>
        <?php endif; ?>

        <li class="page-item">
            <a class="page-link" href="/blog/<?php echo urlencode($username); ?>/page/<?php echo $page + 1; ?>">Selanjutnya</a>
        </li>
    </ul>
</nav>




    <?php else: ?>
        <div class="alert alert-info">Tidak ada artikel yang ditemukan untuk publisher ini.</div>
    <?php endif; ?>
</div>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
<?php

function get_providers_domain_url_json2($json_file_path, $id) {
    // Check if the JSON file exists
    if (!file_exists($json_file_path)) {
        die("JSON file not found.");
    }

    // Read the contents of the JSON file
    $json_content = file_get_contents($json_file_path);

    // Decode the JSON data into a PHP array
    $providers_data = json_decode($json_content, true);

    // Check if decoding was successful
    if ($providers_data === null) {
        die("Failed to decode JSON.");
    }

    // Loop through the providers data to find the matching `id`
    foreach ($providers_data as $provider) {
        if ($provider['id'] == $id) {
            // Return the `providers_domain_url` for the matching `id`
            return $provider['providers_domain_url'];
        }
    }

    // Return null if no provider with the given `id` is found
    return null;
}



function getProvidersNameById_JSON2($json_file_path, $id) {

    // Check if the JSON file exists

    if (!file_exists($json_file_path)) {

        die("JSON file not found.");

    }



    // Read the contents of the JSON file

    $json_content = file_get_contents($json_file_path);



    // Decode the JSON data into a PHP array

    $providers_data = json_decode($json_content, true);



    // Check if decoding was successful

    if ($providers_data === null) {

        die("Failed to decode JSON.");

    }



    // Loop through the providers data to find the matching `id`

    foreach ($providers_data as $provider) {

        if ($provider['id'] == $id) {

            // Return the `providers_name` for the matching `id`

            return $provider['providers_name'];

        }

    }



    // Return null if no provider with the given `id` is found

    return null;

}


?>

