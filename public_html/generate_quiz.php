<?php
// generate_quiz.php
session_start();
ini_set('display_errors', '0');
ini_set('display_startup_errors', '0');
error_reporting(0);

// Pastikan konten JSON sebagai output
header('Content-Type: application/json');

// Include necessary files
require_once("db.php");
require_once("config.php");

// Initialize logger
$logger = new Logger("logs/debug.log", "logs/error.log");


// Inisialisasi Logger
$logDir       = rtrim($config['app']['log_path'], '/\\') . '/';
$logPathDebug = $logDir . 'debug.log';
$logPathError = $logDir . 'error.log';



// Get user ID from session
$user_id = $_SESSION['user_id'];

// Check authentication
if (!isset($_SESSION['user_id'])) {
    $logger->error("Unauthorized access attempt");
    echo json_encode([
        "status" => "error",
        "message" => "Unauthorized access",
        "code" => 401
    ]);
    exit();
}


$raw  = file_get_contents('php://input');
$data = json_decode($raw, true);

// Validasi input JSON
if (!$data || !isset($data['article_id'])) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'article_id missing']);
    exit();
}

$article_id = intval($data['article_id']);
$user_id    = intval($_SESSION['user_id']);


// Create database connection
try {
    $db = new Database($config['database']);
    $conn = $db->getConnection();
    $logger->debug("Database connection established");
} catch (Exception $e) {
    $logger->error("Database connection failed: " . $e->getMessage());
    echo json_encode([
        "status" => "error",
        "message" => "Database connection failed",
        "code" => 500
    ]);
    exit();
}


try {

    // -------- Ambil setting LLM (model & API key) dari llm_settings --------
    $stmtLLM = $conn->prepare("
        SELECT llm_model, openai_key, max_tokens, temperature 
          FROM llm_settings 
         ORDER BY id DESC 
         LIMIT 1
    ");
    $stmtLLM->execute();
    $stmtLLM->bind_result($llmModel, $openaiKey, $maxTokens, $temperature);
    if (!$stmtLLM->fetch()) {
        $stmtLLM->close();
        throw new Exception("Tidak ada konfigurasi LLM di tabel llm_settings.");
    }
    $stmtLLM->close();

    $llmModel    = trim($llmModel);
    $openaiKey   = trim($openaiKey);
    $maxTokens   = intval($maxTokens) > 0 ? intval($maxTokens) : 1500;
    $temperature = floatval($temperature) >= 0 ? floatval($temperature) : 0.7;

    if (empty($llmModel) || empty($openaiKey)) {
        throw new Exception("Konfigurasi model atau API key pada llm_settings tidak valid.");
    }

    // -------- Cek kepemilikan artikel dan ambil json_quiz + html_content --------
    $stmtCheck = $conn->prepare("
        SELECT json_quiz, html_content 
          FROM articles 
         WHERE id = ? 
           AND publishers_local_id = ?
    ");
    $stmtCheck->bind_param("ii", $article_id, $user_id);
    $stmtCheck->execute();
    $stmtCheck->bind_result($existingJsonQuiz, $htmlContent);
    if (!$stmtCheck->fetch()) {
        $stmtCheck->close();
        http_response_code(404);
        echo json_encode(['success' => false, 'message' => 'Artikel tidak ditemukan atau bukan milik Anda']);
        exit();
    }
    $stmtCheck->close();

    // Jika json_quiz sudah ada, batalkan
    if (!empty(trim($existingJsonQuiz))) {
        $decodedExisting = json_decode($existingJsonQuiz, true);
        if (is_array($decodedExisting) && isset($decodedExisting['status']) && $decodedExisting['status'] === 'processing') {
            echo json_encode([
                'success' => false, 
                'message' => 'Quiz untuk artikel ini sedang diproses. Silakan coba beberapa saat lagi.'
            ]);
            exit();
        } else {
            echo json_encode([
                'success' => false, 
                'message' => 'Quiz sudah dibuat sebelumnya untuk artikel ini.'
            ]);
            exit();
        }
    }

    // Tandai status "processing"
    $stmtUpdate = $conn->prepare("UPDATE articles SET json_quiz = ? WHERE id = ?");
    $processingValue = json_encode(['status' => 'processing']);
    $stmtUpdate->bind_param("si", $processingValue, $article_id);
    $stmtUpdate->execute();
    $stmtUpdate->close();

    // Catatan: sengaja tidak memakai $llmModel dari llm_settings di sini, karena
    // tabel itu sekarang berisi model reasoning (gpt-5) yang butuh 'max_completion_tokens',
    // bukan 'max_tokens'. Untuk Summary FAQ cukup pakai gpt-4.1-mini yang kompatibel.
    $quizModel = 'gpt-4.1-mini';

    $logger->debug("[$article_id] Memulai proses generate quiz menggunakan model {$quizModel}.");

    // --------- Persiapan prompt dan panggil OpenAI API ---------
    $prompt  = "Buatkan sejumlah pertanyaan esai dan jawaban masing-masing dari konten berikut dalam bentuk struktur JSON:\n";
    $prompt .= $htmlContent . "\n\n";
    $prompt .= "Format JSON yang diharapkan:\n";
    $prompt .= "{\n";
    $prompt .= "  \"answers\": [\n";
    $prompt .= "    {\"question\": \"Jelaskan ...\", \"answer\": \"...\"},\n";
    $prompt .= "    {\"question\": \"Mengapa ...\", \"answer\": \"...\"},\n";
    $prompt .= "    {\"question\": \"Apa ...?\", \"answer\": \"...\"}\n";
    $prompt .= "  ]\n";
    $prompt .= "}\n";
    $prompt .= "Pastikan output valid JSON dan field \"answers\" berisi array objek dengan key \"question\" dan \"answer\".\n";

    // Inisialisasi cURL ke OpenAI
    $curl = curl_init();
    curl_setopt_array($curl, [
        CURLOPT_URL            => "https://api.openai.com/v1/chat/completions",
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_POST           => true,
        CURLOPT_HTTPHEADER     => [
            "Content-Type: application/json",
            "Authorization: Bearer {$openaiKey}"
        ],
        CURLOPT_POSTFIELDS     => json_encode([
            "model"       => $quizModel,
            "messages"    => [
                ["role" => "system", "content" => "Anda adalah asisten yang membantu membuat soal esai dan jawaban."],
                ["role" => "user",   "content" => $prompt]
            ],
            "temperature" => $temperature,
            "max_tokens"  => $maxTokens
        ]),
        CURLOPT_TIMEOUT => 120
    ]);

    $apiResponse = curl_exec($curl);
    $curlError    = curl_error($curl);
    $httpStatus   = curl_getinfo($curl, CURLINFO_HTTP_CODE);
    curl_close($curl);

    if ($curlError) {
        $logger->error("[$article_id] cURL Error: " . $curlError);
        // Revert: kosongkan kembali json_quiz
        $stmtRevert = $conn->prepare("UPDATE articles SET json_quiz = '' WHERE id = ?");
        $stmtRevert->bind_param("i", $article_id);
        $stmtRevert->execute();
        $stmtRevert->close();

        echo json_encode([
            'success' => false, 
            'message' => 'Error saat menghubungi OpenAI: ' . $curlError
        ]);
        exit();
    }

    if ($httpStatus !== 200) {
        $logger->error("[$article_id] OpenAI API returned HTTP status $httpStatus. Response: $apiResponse");
        // Revert
        $stmtRevert = $conn->prepare("UPDATE articles SET json_quiz = '' WHERE id = ?");
        $stmtRevert->bind_param("i", $article_id);
        $stmtRevert->execute();
        $stmtRevert->close();

        echo json_encode([
            'success' => false,
            'message' => 'OpenAI API error, HTTP status ' . $httpStatus
        ]);
        exit();
    }

    $decoded = json_decode($apiResponse, true);
    if (!isset($decoded['choices'][0]['message']['content'])) {
        $logger->error("[$article_id] Format response OpenAI tidak sesuai: $apiResponse");
        // Revert
        $stmtRevert = $conn->prepare("UPDATE articles SET json_quiz = '' WHERE id = ?");
        $stmtRevert->bind_param("i", $article_id);
        $stmtRevert->execute();
        $stmtRevert->close();

        echo json_encode([
            'success' => false,
            'message' => 'Response OpenAI tidak valid'
        ]);
        exit();
    }

    $quizJsonRaw = trim($decoded['choices'][0]['message']['content']);
    $quizJsonData = json_decode($quizJsonRaw, true);

    if ($quizJsonData === null) {
        $logger->error("[$article_id] Output OpenAI bukan JSON valid: $quizJsonRaw");
        // Revert
        $stmtRevert = $conn->prepare("UPDATE articles SET json_quiz = '' WHERE id = ?");
        $stmtRevert->bind_param("i", $article_id);
        $stmtRevert->execute();
        $stmtRevert->close();

        echo json_encode([
            'success' => false,
            'message' => 'OpenAI menghasilkan JSON tidak valid'
        ]);
        exit();
    }

    // Simpan hasil ke kolom json_quiz
    $stmtSave = $conn->prepare("UPDATE articles SET json_quiz = ? WHERE id = ?");
    $stmtSave->bind_param("si", $quizJsonRaw, $article_id);
    $stmtSave->execute();
    $stmtSave->close();

    $logger->debug("[$article_id] Sukses menyimpan json_quiz di DB.");

    // Kirim response sukses
    echo json_encode(['success' => true]);
    exit();

} catch (Exception $e) {
    $logger->error("[$article_id] Exception: " . $e->getMessage());

    // Revert json_quiz jika perlu
    if (isset($conn) && !$conn->connect_error) {
        $stmtRevert = $conn->prepare("UPDATE articles SET json_quiz = '' WHERE id = ?");
        $stmtRevert->bind_param("i", $article_id);
        $stmtRevert->execute();
        $stmtRevert->close();
    }

    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => 'Terjadi kesalahan: ' . $e->getMessage()
    ]);
    exit();
}
