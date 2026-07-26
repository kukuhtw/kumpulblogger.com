<?php
// upload_image_article.php
session_start();
include("db.php");
require_once("config.php");

// 1. Koneksi ke database
try {
    $db   = new Database($config['database']);
    $conn = $db->getConnection();
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['error' => 'Gagal koneksi database.']);
    exit;
}

// 2. Validasi bahwa TinyMCE mengirim field 'file'
if (empty($_FILES['file'])) {
    http_response_code(400);
    echo json_encode(['error' => 'File tidak ditemukan.']);
    exit;
}

// 3. Pastikan user sudah login
if (!isset($_SESSION['user_id'])) {
    http_response_code(401);
    echo json_encode(['error' => 'User belum login.']);
    exit;
}
$user_id = $_SESSION['user_id'];

// 4. Ambil username (atau identifier lain) dari tabel publisher_quota
$stmt = $conn->prepare("
    SELECT username 
      FROM publisher_quota 
     WHERE publisher_id = ?
    LIMIT 1
");
$stmt->bind_param("i", $user_id);
$stmt->execute();
$stmt->bind_result($username);
if (!$stmt->fetch()) {
    $stmt->close();
    http_response_code(403);
    echo json_encode(['error' => 'Data publisher tidak ditemukan.']);
    exit;
}
$stmt->close();

// 5. Siapkan data upload
$file     = $_FILES['file'];
$origName = $file['name'];
$mime     = (new finfo(FILEINFO_MIME_TYPE))->file($file['tmp_name']);

// 6. Validasi MIME type: hanya izinkan JPG/PNG/GIF/WEBP
$allowedMimes = [
    'image/jpeg' => 'jpg',
    'image/png'  => 'png',
    'image/gif'  => 'gif',
    'image/webp' => 'webp'
];
if (!isset($allowedMimes[$mime])) {
    http_response_code(415);
    echo json_encode(['error' => 'Tipe file tidak diizinkan (hanya JPG, PNG, GIF, WEBP).']);
    exit;
}
$ext = $allowedMimes[$mime];

// 7. Validasi error upload dan ukuran maksimum 5MB
if ($file['error'] !== UPLOAD_ERR_OK) {
    http_response_code(400);
    echo json_encode(['error' => 'Error upload kode: '.$file['error']]);
    exit;
}
if ($file['size'] > 5 * 1024 * 1024) {
    http_response_code(413);
    echo json_encode(['error' => 'Ukuran file terlalu besar. Maksimal 5MB.']);
    exit;
}

// 8. Buat folder 'uploads/' jika belum ada
$uploadDir = __DIR__ . '/uploads/';
if (!is_dir($uploadDir)) {
    if (!mkdir($uploadDir, 0755, true)) {
        http_response_code(500);
        echo json_encode(['error' => 'Gagal membuat folder uploads.']);
        exit;
    }
}

// 9. Buat nama file: username_timestamp.ext
$safeUsername = preg_replace('/[^a-zA-Z0-9_-]/', '', $username);
$timestamp    = time();
$filename     = sprintf("%s_%d.%s", $safeUsername, $timestamp, $ext);
$targetPath   = $uploadDir . $filename;

// 10. Pindahkan file
if (!move_uploaded_file($file['tmp_name'], $targetPath)) {
    http_response_code(500);
    echo json_encode(['error' => 'Gagal memindahkan file ke folder uploads.']);
    exit;
}

// 11. Kembalikan URL lengkap dalam kunci "location" agar TinyMCE langsung menaruh <img>
$protocol = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? "https" : "http";
$baseUrl  = sprintf(
    "%s://%s%s/uploads/",
    $protocol,
    $_SERVER['HTTP_HOST'],
    rtrim(dirname($_SERVER['REQUEST_URI']), '/\\')
);
$imageUrl = $baseUrl . $filename;

echo json_encode(['location' => $imageUrl]);
exit;
