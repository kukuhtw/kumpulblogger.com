<?php
// generate_audio_summary.php
// ——————————————————————————————————————————
// Pastikan tidak ada karakter/spasi/kosong sebelum tag <?php
// ——————————————————————————————————————————

ini_set('display_errors', 1);
error_reporting(E_ALL);

header('Content-Type: application/json; charset=utf-8');

session_start();

// **1. Include semua yang diperlukan:**

// Include necessary files
require_once("db.php");
require_once("config.php");
//require_once("logger.php");

// Initialize logger
$logger = new Logger("logs/debug.log", "logs/error.log");


// Inisialisasi Logger
$logDir       = rtrim($config['app']['log_path'], '/\\') . '/';
$logPathDebug = $logDir . 'debug.log';
$logPathError = $logDir . 'error.log';




// Pastikan direktori log ada
if (!is_dir($logDir)) {
    mkdir($logDir, 0755, true);
}

// **3. Instansiasi Logger** dengan path yang benar
$logger = new Logger($logPathDebug, $logPathError);

// ——————————————————————————————————————————
// Mulai eksekusi utama
// ——————————————————————————————————————————

// **4. Baca request body (JSON) dan validasi**
$rawInput = file_get_contents('php://input');
$data     = json_decode($rawInput, true);

if (!isset($data['article_id'])) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'article_id not provided']);
    $logger->error("article_id tidak dikirim");
    exit;
}

$articleId = intval($data['article_id']);
$logger->debug("Request generate audio untuk article_id: {$articleId}");

// **5. Validasi session user**
if (!isset($_SESSION['user_id'])) {
    http_response_code(401);
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    $logger->error("User tidak login saat memanggil generate_audio_summary");
    exit;
}
$user_id = intval($_SESSION['user_id']);

// **6. Koneksi database**
try {
    $db   = new Database($config['database']);
    $conn = $db->getConnection();
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Database connection error']);
    $logger->error("Database connection error: " . $e->getMessage());
    exit;
}

// **7. Ambil html_content dari tabel articles**
try {
    $stmt = $conn->prepare("
        SELECT html_content 
          FROM articles 
         WHERE id = ? 
           AND publishers_local_id = ?
    ");
    $stmt->bind_param("ii", $articleId, $user_id);
    $stmt->execute();
    $stmt->bind_result($htmlContent);
    if (!$stmt->fetch()) {
        http_response_code(404);
        echo json_encode(['success' => false, 'message' => 'Article not found or not owned by user']);
        $logger->error("Article ID {$articleId} tidak ditemukan untuk user {$user_id}");
        exit;
    }
    $stmt->close();
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Gagal mengambil data artikel']);
    $logger->error("Error fetch html_content: " . $e->getMessage());
    exit;
}

// **8. Ambil konfigurasi model & API key dari llm_settings**
try {
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
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Konfigurasi LLM invalid: ' . $e->getMessage()]);
    $logger->error("Error fetch llm_settings: " . $e->getMessage());
    exit;
}

// **9. Buat ringkasan via OpenAI ChatCompletion**
// Catatan: sengaja tidak memakai $llmModel dari llm_settings di sini, karena
// tabel itu sekarang berisi model reasoning (gpt-5) yang butuh 'max_completion_tokens',
// bukan 'max_tokens'. Untuk ringkasan audio cukup pakai gpt-4.1-mini yang kompatibel.
$summaryModel = 'gpt-4.1-mini';
$summary = '';
try {
    $logger->debug("Memanggil ChatCompletion dengan model={$summaryModel}");
    $chatPayload = [
        'model'       => $summaryModel,
        'messages'    => [
            [
                'role'    => 'user',
                'content' => "Buatkan summary dalam 5 kalimat untuk konten berikut:\n\n" . $htmlContent
            ]
        ],
        'max_tokens'  => $maxTokens,
        'temperature' => $temperature
    ];

    $ch = curl_init('https://api.openai.com/v1/chat/completions');
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_HTTPHEADER, [
        "Authorization: Bearer {$openaiKey}",
        "Content-Type: application/json"
    ]);
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($chatPayload));

    $apiResponse = curl_exec($ch);
    if (curl_errno($ch)) {
        throw new Exception("cURL ChatCompletion error: " . curl_error($ch));
    }
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);

    if ($httpCode !== 200) {
        throw new Exception("OpenAI ChatCompletion returned HTTP {$httpCode}: {$apiResponse}");
    }

    $decoded = json_decode($apiResponse, true);
    if (!isset($decoded['choices'][0]['message']['content'])) {
        throw new Exception("Format response ChatCompletion unexpected");
    }
    $summary = trim($decoded['choices'][0]['message']['content']);
    $logger->debug("Ringkasan berhasil: " . substr($summary, 0, 100) . "...");
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Gagal membuat ringkasan: ' . $e->getMessage()]);
    $logger->error("Error generate summary: " . $e->getMessage());
    exit;
}

// **10. Konversi ringkasan ke audio (MP3) via TTS**
$audioBinary = null;
try {
    $logger->debug("Memanggil TTS (gpt-4o-mini-tts) dengan voice=alloy");
    $ttsPayload = [
        'model' => 'gpt-4o-mini-tts',
        'input' => $summary,
        'voice' => 'alloy'
    ];

    $ch2 = curl_init('https://api.openai.com/v1/audio/speech');
    curl_setopt($ch2, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch2, CURLOPT_HTTPHEADER, [
        "Authorization: Bearer {$openaiKey}",
        "Content-Type: application/json"
    ]);
    curl_setopt($ch2, CURLOPT_POST, true);
    curl_setopt($ch2, CURLOPT_POSTFIELDS, json_encode($ttsPayload));

    $audioBinary = curl_exec($ch2);
    if (curl_errno($ch2)) {
        throw new Exception("cURL TTS error: " . curl_error($ch2));
    }
    $httpCode2 = curl_getinfo($ch2, CURLINFO_HTTP_CODE);
    curl_close($ch2);

    if ($httpCode2 !== 200) {
        throw new Exception("OpenAI TTS returned HTTP {$httpCode2}");
    }
    if (empty($audioBinary)) {
        throw new Exception("TTS API returned empty audio");
    }
    $logger->debug("Audio TTS diterima (". strlen($audioBinary) ." bytes)");
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Gagal membuat audio: ' . $e->getMessage()]);
    $logger->error("Error TTS: " . $e->getMessage());
    exit;
}

// **11. Simpan file MP3 ke folder voice/**
$voiceDir = __DIR__ . '/voice';
if (!is_dir($voiceDir)) {
    mkdir($voiceDir, 0755, true);
}
$filename    = "{$articleId}.mp3";
$filePathAbs = $voiceDir . '/' . $filename;
$filePathRel = 'voice/' . $filename; // path relatif untuk disimpan di DB

try {
    file_put_contents($filePathAbs, $audioBinary);
    $logger->debug("File audio disimpan: {$filePathAbs}");
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Gagal menyimpan file audio: ' . $e->getMessage()]);
    $logger->error("Error saving audio file: " . $e->getMessage());
    exit;
}

// **12. Update kolom wav di tabel articles**
try {
    $stmtUpd = $conn->prepare("
        UPDATE articles 
           SET wav = ? 
         WHERE id = ? 
           AND publishers_local_id = ?
    ");
    $stmtUpd->bind_param("sii", $filePathRel, $articleId, $user_id);
    if (!$stmtUpd->execute()) {
        throw new Exception("DB update failed: (" . $stmtUpd->errno . ") " . $stmtUpd->error);
    }
    $stmtUpd->close();
    $logger->debug("Kolom wav di-update: {$filePathRel} untuk article_id {$articleId}");
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Gagal update database: ' . $e->getMessage()]);
    $logger->error("Error update DB wav: " . $e->getMessage());
    exit;
}

// **13. Sukses, kembalikan JSON murni**
echo json_encode([
    'success'  => true,
    'wav_path' => $filePathRel
]);

$logger->debug("generate_audio_summary selesai untuk article_id: {$articleId}");
exit;
