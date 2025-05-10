<?php
// get_ideas.php
header('Content-Type: application/json');


// include konfigurasi dan helper
require_once "db.php";
require_once "config.php";


// Initialize logger
$logger = new Logger("logs/debug.log", "logs/error.log");

session_start();

// Buat koneksi ke DB
try {
    $db = new Database($config['database']);
    $conn = $db->getConnection();
    // jika pakai logger:
    // $logger->debug("Database connection established in get_ideas.php");
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        "status"  => "error",
        "message" => "Database connection failed: " . $e->getMessage()
    ]);
    exit();
}

// Ambil 20 ide acak
$stmt = $conn->prepare("
    SELECT topik, deskripsi
    FROM idea_article
    ORDER BY RAND()
    LIMIT 200
");
$stmt->execute();
$result = $stmt->get_result();

$ideas = [];
while ($row = $result->fetch_assoc()) {
    $ideas[] = $row;
}

echo json_encode($ideas);

?>