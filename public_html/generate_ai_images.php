<?php
// generate_ai_images.php

// 1. Pastikan tidak ada spasi atau baris kosong sebelum tag <?php

// 2. Matikan semua tampilan error ke browser, kirimkan ke log saja
ini_set('display_errors', 1);
ini_set('log_errors', 1);
ini_set('error_log', __DIR__ . '/logs/php_errors.log'); // catat error ke file logs/php_errors.log
error_reporting(E_ALL);

// 3. SMS/telegram/fungsi lain yang mencetak HTML harus dimatikan di sini

// 4. Kirim header JSON dulu, tanpa output apapun
header('Content-Type: application/json; charset=utf-8');

session_start();




// Include necessary files
require_once("db.php");
require_once("config.php");

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



// 5. Baca body JSON
$rawInput = file_get_contents('php://input');
$data     = json_decode($rawInput, true);

// 6. Validasi session user
if (!isset($_SESSION['user_id'])) {
    http_response_code(401);
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    $logger->error("User tidak login saat memanggil generate_ai_images");
    exit;
}
$user_id = intval($_SESSION['user_id']);

// 7. Validasi article_id
if (empty($data['article_id'])) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'article_id not provided']);
    $logger->error("article_id tidak dikirim");
    exit;
}
$articleId = intval($data['article_id']);

// 8. Koneksi database
try {
    $db   = new Database($config['database']);
    $conn = $db->getConnection();
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Database connection error']);
    $logger->error("Database connection error: " . $e->getMessage());
    exit;
}

// Kompatibilitas untuk mengambil hasil prediction Replicate yang dibuat sebelum migrasi GPT Image 2.
function replicateGetResult($predictionId, $replicateToken) {
    $url = "https://api.replicate.com/v1/predictions/{$predictionId}";
    $ch = curl_init($url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_HTTPHEADER, [
        "Authorization: Token {$replicateToken}",
        'Content-Type: application/json'
    ]);
    $response = curl_exec($ch);
    if ($response === false) {
        $error = curl_error($ch);
        curl_close($ch);
        return ['error' => $error];
    }
    curl_close($ch);
    return json_decode($response, true) ?: ['error' => 'Invalid Replicate response'];
}

// 9. Cek apakah request bertipe “get” untuk prediction lama atau “generate” baru.
if (isset($data['action']) && $data['action'] === 'get') {
    $logger->debug("Memproses GET AI Images untuk article_id: {$articleId}");

    // 9.a. Ambil prediction_id dan cek apakah images masih kosong
    $stmtChk = $conn->prepare("
        SELECT prediction_id, images 
          FROM articles 
         WHERE id = ? 
           AND publishers_local_id = ?
    ");
    $stmtChk->bind_param("ii", $articleId, $user_id);
    $stmtChk->execute();
    $stmtChk->bind_result($existingPredId, $existingImage);
    if (!$stmtChk->fetch()) {
        http_response_code(404);
        echo json_encode(['success' => false, 'message' => 'Article not found atau tidak dimiliki user']);
        $logger->error("Article ID {$articleId} tidak ditemukan untuk user {$user_id}");
        exit;
    }
    $stmtChk->close();

    if (empty($existingPredId)) {
        http_response_code(400);
        echo json_encode(['success' => false, 'message' => 'Belum ada prediction_id untuk artikel ini']);
        $logger->error("Tidak ada prediction_id untuk article_id: {$articleId}");
        exit;
    }
    if (!empty($existingImage)) {
        http_response_code(400);
        echo json_encode(['success' => false, 'message' => 'Artikel sudah memiliki AI image']);
        $logger->error("Artikel {$articleId} sudah punya image: {$existingImage}");
        exit;
    }

    // 9.b. Ambil replicate_key
    try {
        $stmtLLM = $conn->prepare("
            SELECT replicate_key 
              FROM llm_settings 
             ORDER BY id DESC 
             LIMIT 1
        ");
        $stmtLLM->execute();
        $stmtLLM->bind_result($replicateKey);
        $stmtLLM->fetch();
        $stmtLLM->close();
        $replicateKey = trim($replicateKey);
        if (empty($replicateKey)) {
            throw new Exception("replicate_key kosong");
        }
    } catch (Exception $e) {
        http_response_code(500);
        echo json_encode(['success' => false, 'message' => 'Konfigurasi replicate_key invalid']);
        $logger->error("Gagal ambil replicate_key: " . $e->getMessage());
        exit;
    }

    // 9.c. Fungsi fetch hasil prediksi
    // 9.d. Ambil hasilnya
    $predictionResult = replicateGetResult($existingPredId, $replicateKey);
    if (isset($predictionResult['error'])) {
        http_response_code(500);
        echo json_encode(['success' => false, 'message' => 'Gagal mendapatkan hasil image']);
        $logger->error("ReplicateGetResult error: " . $predictionResult['error']);
        exit;
    }
    if (!isset($predictionResult['output'][0])) {
        http_response_code(500);
        echo json_encode(['success' => false, 'message' => 'Output dari Replicate tidak valid']);
        $logger->error("Replicate output invalid");
        exit;
    }
    $outputImageUrl = $predictionResult['output'][0];
    $logger->debug("Output image URL: {$outputImageUrl}");

    // 9.e. Unduh ke folder ai_images
    try {
        $stmtUser = $conn->prepare("
            SELECT pq.username 
              FROM articles AS a
              LEFT JOIN publisher_quota AS pq 
                ON a.publishers_local_id = pq.publisher_id
             WHERE a.id = ?
        ");
        $stmtUser->bind_param("i", $articleId);
        $stmtUser->execute();
        $stmtUser->bind_result($authorUsername);
        $stmtUser->fetch();
        $stmtUser->close();
        if (empty($authorUsername)) {
            $authorUsername = 'unknown';
        }
    } catch (Exception $e) {
        $authorUsername = 'unknown';
        $logger->error("Error fetch username: " . $e->getMessage());
    }

    $aiDir = __DIR__ . '/ai_images';
    if (!is_dir($aiDir)) {
        mkdir($aiDir, 0755, true);
    }
    $safeUsername = preg_replace('/[^A-Za-z0-9_-]/', '_', $authorUsername);
    $filename     = "{$safeUsername}_{$articleId}.jpg";
    $filePathAbs  = $aiDir . '/' . $filename;
    $filePathRel  = 'ai_images/' . $filename;

    $fileData = @file_get_contents($outputImageUrl);
    if ($fileData === FALSE) {
        http_response_code(500);
        echo json_encode(['success' => false, 'message' => 'Gagal mendownload image dari Replicate']);
        $logger->error("Error download image dari URL: {$outputImageUrl}");
        exit;
    }
    file_put_contents($filePathAbs, $fileData);
    $logger->debug("File image disimpan: {$filePathAbs}");

    // 9.f. Update kolom images di tabel articles
    try {
        $stmtUpd = $conn->prepare("
            UPDATE articles 
               SET images = ? 
             WHERE id = ? 
               AND publishers_local_id = ?
        ");
        $stmtUpd->bind_param("sii", $filePathRel, $articleId, $user_id);
        $stmtUpd->execute();
        $stmtUpd->close();
        $logger->debug("Kolom images di-update: {$filePathRel} untuk article_id {$articleId}");
    } catch (Exception $e) {
        http_response_code(500);
        echo json_encode(['success' => false, 'message' => 'Gagal update database']);
        $logger->error("Error update DB images: " . $e->getMessage());
        exit;
    }

    // 9.g. Kembalikan JSON sukses
    echo json_encode([
        'success'    => true,
        'image_path' => $filePathRel
    ]);
    $logger->debug("GET AI Images selesai untuk article_id: {$articleId}");
    exit;
}

// ====== 10. Alur default: generate prompt baru → simpan prediction_id → tunggu hasil → download image ======

// 10.a. Ambil html_content & cek apakah kolom images kosong
try {
    $stmt = $conn->prepare("
        SELECT title, html_content, images 
          FROM articles 
         WHERE id = ? 
           AND publishers_local_id = ?
    ");
    $stmt->bind_param("ii", $articleId, $user_id);
    $stmt->execute();
    $stmt->bind_result($articleTitle, $htmlContent, $existingImage);
    if (!$stmt->fetch()) {
        http_response_code(404);
        echo json_encode(['success' => false, 'message' => 'Article not found atau tidak dimiliki user']);
        $logger->error("Article ID {$articleId} tidak ditemukan untuk user {$user_id}");
        exit;
    }
    $stmt->close();

    if (!empty($existingImage)) {
        http_response_code(400);
        echo json_encode(['success' => false, 'message' => 'Artikel sudah memiliki AI image']);
        $logger->error("Artikel {$articleId} sudah punya image: {$existingImage}");
        exit;
    }
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Gagal mengambil data artikel']);
    $logger->error("Error fetch html_content: " . $e->getMessage());
    exit;
}

// 10.b. Ambil konfigurasi LLM & API key
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

    $llmModel     = trim($llmModel);
    $openaiKey    = trim($openaiKey);
    $maxTokens    = intval($maxTokens) > 0 ? intval($maxTokens) : 1500;
    $temperature  = floatval($temperature) >= 0 ? floatval($temperature) : 0.7;

    if (empty($llmModel) || empty($openaiKey)) {
        throw new Exception("Konfigurasi model atau OpenAI key tidak valid.");
    }
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Konfigurasi LLM invalid']);
    $logger->error("Error fetch llm_settings: " . $e->getMessage());
    exit;
}

// 10.c. Buat prompt gambar via OpenAI ChatCompletion
$imagePrompt = '';
try {
    $logger->debug("Memanggil ChatCompletion untuk membuat image prompt, model={$llmModel}");
    $articleText = trim(preg_replace('/\s+/', ' ', html_entity_decode(strip_tags($htmlContent), ENT_QUOTES | ENT_HTML5, 'UTF-8')));
    $articleText = mb_substr($articleText, 0, 12000, 'UTF-8');
    $chatPayload = [
        'model'       => $llmModel,
        'messages'    => [
            [
                'role' => 'system',
                'content' => 'You are an expert editorial art director. Convert an article into one precise image-generation prompt. Preserve the article meaning and never invent unrelated subjects.'
            ],
            [
                'role'    => 'user',
                'content' => "ARTICLE TITLE:\n{$articleTitle}\n\nARTICLE CONTENT:\n{$articleText}\n\n"
                    . "Write one detailed English image prompt for a 3D cartoon editorial illustration. "
                    . "Identify and preserve the article's main subject, central action, setting, mood, and essential supporting objects. "
                    . "The illustration must communicate the article's actual core idea at first glance. "
                    . "Use polished stylized 3D cartoon characters and objects, expressive but natural poses, dimensional lighting, "
                    . "soft shadows, rich materials, clean composition, vibrant balanced colors, and a professional editorial finish. "
                    . "Use a wide 3:2 composition with a clear focal subject and enough breathing room. "
                    . "Do not add unrelated objects, written text, captions, logos, watermarks, UI screenshots, or brand marks. "
                    . "Do not imitate a named artist, studio, movie, or copyrighted character. "
                    . "Return only the final image prompt, without explanation or markdown."
            ]
        ]
    ];
    if (strpos(strtolower($llmModel), 'gpt-5') === 0) {
        $chatPayload['max_completion_tokens'] = max(2500, $maxTokens);
        $normalizedModel = strtolower($llmModel);
        $chatPayload['reasoning_effort'] = preg_match('/^gpt-5\.\d+/', $normalizedModel) ? 'low' : 'minimal';
    } else {
        $chatPayload['max_tokens'] = $maxTokens;
        $chatPayload['temperature'] = $temperature;
    }

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
        throw new Exception("cURL ChatCompletion error");
    }
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);

    if ($httpCode !== 200) {
        throw new Exception("OpenAI ChatCompletion returned HTTP {$httpCode}");
    }

    $decoded = json_decode($apiResponse, true);
    if (!isset($decoded['choices'][0]['message']['content'])) {
        throw new Exception("Format response ChatCompletion unexpected");
    }
    $imagePrompt = trim($decoded['choices'][0]['message']['content']);
    $logger->debug("Prompt image berhasil dibuat");
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Gagal membuat prompt gambar']);
    $logger->error("Error generate image prompt: " . $e->getMessage());
    exit;
}

// 10.d. Panggil OpenAI Images API menggunakan GPT Image 2.
function openAiGenerateImage($openaiKey, $prompt) {
    $payload = [
        'model' => 'gpt-image-2',
        'prompt' => "Create a polished 3D cartoon editorial illustration that accurately represents the article. "
            . "Content fidelity is the highest priority: keep the main subject, action, setting, mood, and meaningful objects exactly aligned with this brief. "
            . "Use an original stylized 3D cartoon aesthetic, dimensional lighting, soft shadows, tactile materials, expressive natural poses, "
            . "vibrant balanced colors, a clean professional composition, and a clear focal point. Wide 3:2 layout. "
            . "No written words, captions, logos, watermarks, brand marks, UI text, unrelated objects, named artist styles, or copyrighted characters.\n\n"
            . "CONTENT BRIEF:\n" . $prompt,
        'size' => '1536x1024',
        'quality' => 'medium',
        'output_format' => 'jpeg',
        'n' => 1
    ];

    $ch = curl_init('https://api.openai.com/v1/images/generations');
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_HTTPHEADER, [
        "Authorization: Bearer {$openaiKey}",
        'Content-Type: application/json'
    ]);
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($payload));
    curl_setopt($ch, CURLOPT_TIMEOUT, 180);

    $response = curl_exec($ch);
    if ($response === false) {
        $curlError = curl_error($ch);
        curl_close($ch);
        throw new Exception("cURL OpenAI Images error: {$curlError}");
    }
    $httpCode = (int) curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);

    $decoded = json_decode($response, true);
    if ($httpCode < 200 || $httpCode >= 300) {
        $message = $decoded['error']['message'] ?? "HTTP {$httpCode}";
        throw new Exception("OpenAI Images API error: {$message}");
    }
    if (empty($decoded['data'][0]['b64_json'])) {
        throw new Exception('OpenAI Images API tidak mengembalikan data gambar.');
    }

    $imageBytes = base64_decode($decoded['data'][0]['b64_json'], true);
    if ($imageBytes === false) {
        throw new Exception('Data base64 gambar dari OpenAI tidak valid.');
    }
    return $imageBytes;
}

try {
    $logger->debug('Memanggil OpenAI Images API dengan model gpt-image-2');
    $fileData = openAiGenerateImage($openaiKey, $imagePrompt);
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Gagal membuat gambar dengan GPT Image 2']);
    $logger->error('Error GPT Image 2: ' . $e->getMessage());
    exit;
}

// 10.g. Unduh ke folder ai_images
try {
    $stmtUser = $conn->prepare("
        SELECT pq.username 
          FROM articles AS a
          LEFT JOIN publisher_quota AS pq 
            ON a.publishers_local_id = pq.publisher_id
         WHERE a.id = ?
    ");
    $stmtUser->bind_param("i", $articleId);
    $stmtUser->execute();
    $stmtUser->bind_result($authorUsername);
    $stmtUser->fetch();
    $stmtUser->close();
    if (empty($authorUsername)) {
        $authorUsername = 'unknown';
    }
} catch (Exception $e) {
    $authorUsername = 'unknown';
    $logger->error("Error fetch username: " . $e->getMessage());
}

$aiDir = __DIR__ . '/ai_images';
if (!is_dir($aiDir)) {
    mkdir($aiDir, 0755, true);
}
$safeUsername = preg_replace('/[^A-Za-z0-9_-]/', '_', $authorUsername);
$filename     = "{$safeUsername}_{$articleId}.jpg";
$filePathAbs  = $aiDir . '/' . $filename;
$filePathRel  = 'ai_images/' . $filename;

$bytesWritten = file_put_contents($filePathAbs, $fileData);
if ($bytesWritten === false) {
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Gagal menyimpan gambar']);
    $logger->error("Gagal menyimpan file image: {$filePathAbs}");
    exit;
}
$logger->debug("File image disimpan: {$filePathAbs}");

// 10.h. Update kolom images di tabel articles
try {
    $stmtUpd = $conn->prepare("
        UPDATE articles 
           SET images = ? 
         WHERE id = ? 
           AND publishers_local_id = ?
    ");
    $stmtUpd->bind_param("sii", $filePathRel, $articleId, $user_id);
    $stmtUpd->execute();
    $stmtUpd->close();
    $logger->debug("Kolom images di-update: {$filePathRel}");
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Gagal update database']);
    $logger->error("Error update DB images");
    exit;
}

// 10.i. Kembalikan JSON sukses
echo json_encode([
    'success'    => true,
    'image_path' => $filePathRel
]);
$logger->debug("generate_ai_images selesai untuk article_id: {$articleId}");
exit;
