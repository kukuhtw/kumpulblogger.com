<?php
session_start();
ini_set("error_log", "error.log");
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

include("function.php");

// Regenerate session ID to prevent session fixation
session_regenerate_id(true);



// Generate a session token if not exists
if (!isset($_SESSION['video_token'])) {
    $_SESSION['video_token'] = bin2hex(random_bytes(32));
}


$videoId = '5q4zI-rEKVs';

function isValidDuration($duration) {
    return is_numeric($duration) && $duration >= 0 && $duration <= 3600; // Max 1 hour
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $startTime=  isset($_POST['startTime']) ? $_POST['startTime'] : '';
    $duration = isset($_POST['duration']) ? intval($_POST['duration']) : 0;
    $pubid = isset($_POST['pubid']) ? intval($_POST['pubid']) : 0;
    $token = isset($_POST['token']) ? $_POST['token'] : '';
    $referrer = isset($_POST['referrer']) ? $_POST['referrer'] : '';

    $source_url = $_SERVER['HTTP_REFERER'] ?? '';


     $url = isset($_POST['url']) ? $_POST['url'] : 'unknown';
    $ip = $_SERVER['REMOTE_ADDR'];
    $useragent = $_SERVER['HTTP_USER_AGENT'];

  // Sanitize inputs
$startTime = filter_var($startTime, FILTER_SANITIZE_NUMBER_INT);
$duration = filter_var($duration, FILTER_SANITIZE_NUMBER_INT);

$pubid = filter_var($pubid, FILTER_SANITIZE_NUMBER_INT);

// For token and useragent, use htmlspecialchars to prevent XSS attacks
$token = htmlspecialchars($token, ENT_QUOTES, 'UTF-8');
$useragent = htmlspecialchars($useragent, ENT_QUOTES, 'UTF-8');

// Sanitize URLs
$referrer = filter_var($referrer, FILTER_SANITIZE_URL);

$referrer_source_url = $source_url. " - ".$referrer;

$url = filter_var($url, FILTER_SANITIZE_URL);

// Validate IP address
$ip = filter_var($ip, FILTER_VALIDATE_IP);



    // Validate session token
    if ($token !== $_SESSION['video_token']) {
        http_response_code(403);
        echo json_encode(['error' => 'Invalid token']);
        exit();
    }

    if (!isValidDuration($duration)) {
        http_response_code(400);
        echo json_encode(['error' => 'Invalid duration']);
        exit();
    }

    include 'db.php';
    $conn = new mysqli($servername_db, $username_db, $password_db, $dbname_db);
    if ($conn->connect_error) {
        error_log("Database connection failed: " . $conn->connect_error);
        echo json_encode(['error' => 'Database connection failed']);
        exit();
    }

    // Additional validation to prevent abuse: limit submissions from the same IP
    $stmt = $conn->prepare("SELECT COUNT(*) FROM video_watch_logs WHERE ip = ? AND videoId = ? AND TIMESTAMPDIFF(SECOND, viewed_at, NOW()) < 60");
    $stmt->bind_param('ss', $ip, $videoId);
    $stmt->execute();
    $stmt->bind_result($submissionCount);
    $stmt->fetch();
    $stmt->close();

    if ($submissionCount > 3) { // Limit to 3 submissions per minute from the same IP
        http_response_code(429);
        echo json_encode(['error' => 'Too many requests']);
        exit();
    }
    
    $token = $_SESSION['video_token'];
  
        $sql = 'INSERT INTO video_watch_logs (pubid, startTime,videoId ,duration, ip, useragent, referrer, viewed_at, token) VALUES (?, ? ,?, ?, ?, ?, ?, NOW(), ?)';
    $stmt = $conn->prepare($sql);
    
    if ($stmt === false) {
        error_log("Prepare failed: " . $conn->error . " for SQL: " . $sql);
        echo json_encode(['error' => 'Database prepare failed']);
        $conn->close();
        exit();
    }

    if (!$stmt->bind_param('ississss', $pubid, $startTime ,$videoId, $duration, $ip, $useragent, $referrer_source_url, $token)) {
        error_log("Binding parameters failed: " . $stmt->error);
        echo json_encode(['error' => 'Binding parameters failed']);
        $stmt->close();
        $conn->close();
        exit();
    }

    if ($stmt->execute()) {
        echo json_encode(['message' => 'Duration saved successfully']);
    } else {
        error_log('Error saving duration: ' . $stmt->error);
        echo json_encode(['error' => 'Error saving duration']);
    }

    $stmt->close();
    $conn->close();
} else {
    // For GET requests, return video ID and token
    echo json_encode([
        'videoId' => $videoId,
        'token' => $_SESSION['video_token']
    ]);
}
?>