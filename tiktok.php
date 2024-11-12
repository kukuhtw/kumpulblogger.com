<?php
session_start();
ini_set("error_log", "error.log");
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);


$pubid = $_GET['pubid'] ?? null;
$token = $_POST['token'] ?? null;
$startTime = $_POST['startTime'] ?? null;
$duration = $_POST['duration'] ?? null;
$referrer = $_POST['referrer'] ?? null;
$url = $_POST['url'] ?? null;

// Validate and sanitize inputs
$pubid = filter_var($pubid, FILTER_SANITIZE_STRING);
$token = filter_var($token, FILTER_SANITIZE_STRING);
$startTime = filter_var($startTime, FILTER_SANITIZE_STRING);
$duration = filter_var($duration, FILTER_VALIDATE_INT);
$referrer = filter_var($referrer, FILTER_SANITIZE_URL);
$url = filter_var($url, FILTER_SANITIZE_URL);

if ($pubid) {
    // Generate a dummy TikTok video ID for demonstration purposes
    $videoId = '7319900370820435202'; // Replace with actual logic to get TikTok video ID
    $token = md5($pubid . 'key');

    // Generate the blockquote element as a string
    $blockquote = '<blockquote class="tiktok-embed" cite="https://www.tiktok.com/@kukuhtw/video/' . $videoId . '" data-video-id="' . $videoId . '"><section></section></blockquote>';

    echo json_encode(['blockquote' => $blockquote, 'token' => $token]);
} elseif ($token && $startTime && $duration) {
    // Validate the token and log the video duration
    if ($token === md5($pubid . 'key')) {
        include 'db.php';
        try {
            // Create a new PDO instance and set error mode to exception
            $pdo = new PDO('mysql:host=' . $servername_db . ';dbname=' . $dbname_db, $username_db, $password_db);
            $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

            // Prepare the SQL statement to insert log data
            $stmt = $pdo->prepare('INSERT INTO video_tiktok_logs (pubid, startTime, duration, referrer, url) VALUES (?, ?, ?, ?, ?)');

            // Execute the statement with the provided data
            $stmt->execute([$pubid, $startTime, $duration, $referrer, $url]);

            echo json_encode(['status' => 'success', 'message' => 'Duration logged successfully']);
        } catch (PDOException $e) {
            error_log("Database error: " . $e->getMessage());
            echo json_encode(['status' => 'error', 'message' => 'Database error']);
        }
    } else {
        echo json_encode(['status' => 'error', 'message' => 'Invalid token']);
    }
} else {
    echo json_encode(['status' => 'error', 'message' => 'Missing parameters']);
}

?>
