<?php
// upload_image_article.php
session_start();
include("db.php");
require_once("config.php");

// koneksi DB
try {
    $db   = new Database($config['database']);
    $conn = $db->getConnection();
} catch (Exception $e) {
    echo json_encode(['error'=>'DB conn failed']);
    exit;
}

// validasi input
if (empty($_FILES['image']) || empty($_POST['article_id'])) {
    echo json_encode(['error'=>'No file or article']);
    exit;
}

// ambil user_id dari session
$user_id = $_SESSION['user_id'];

// Ambil username dari publisher_quota
$stmt = $conn->prepare("
    SELECT username 
      FROM publisher_quota 
     WHERE publisher_id = ?
");
$stmt->bind_param("i", $user_id);
$stmt->execute();
$stmt->bind_result($username);
$stmt->fetch();
$stmt->close();

// siapkan data upload
$article_id = (int)$_POST['article_id'];
$file       = $_FILES['image'];
$ext        = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
$allowed    = ['jpg','jpeg','png','gif','webp'];
if (!in_array($ext, $allowed)) {
    echo json_encode(['error'=>'Tipe file tidak diizinkan']);
    exit;
}

// folder tujuan
$targetDir = __DIR__.'/uploads/';
if (!is_dir($targetDir)) mkdir($targetDir, 0755, true);

// format nama: username_idarticle_unixtimestamp.ext
$timestamp = time();
$filename  = sprintf(
    "%s_%d_%d.%s",
    preg_replace('/[^a-zA-Z0-9_-]/','', $username),
    $article_id,
    $timestamp,
    $ext
);
$target = $targetDir . $filename;

// pindahkan file
if (move_uploaded_file($file['tmp_name'], $target)) {
    // kembalikan path relatif untuk Quill insertEmbed
    echo json_encode(['url'=>'uploads/'.$filename]);
} else {
    echo json_encode(['error'=>'Gagal menyimpan file']);
}
