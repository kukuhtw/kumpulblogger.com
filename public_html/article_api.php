<?php
// article_api.php - Backend API for article generation and management

// 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 https://openrouter.ai/settings/keys

// Include necessary files
require_once("db.php");
require_once("config.php");

// Initialize logger
$logger = new Logger("logs/debug.log", "logs/error.log");

// Start session
session_start();

// Set response header to JSON
header('Content-Type: application/json');

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

// Get user ID from session
$user_id = $_SESSION['user_id'];

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

// Initialize response array
$response = [
    "status" => "error",
    "message" => "Invalid request",
    "code" => 400,
    "data" => null
];

// Process API requests
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $input = json_decode(file_get_contents('php://input'), true);
    
    // If JSON parsing failed, try to use POST data
    if ($input === null) {
        $input = $_POST;
    }
    
    // Define action based on request
    $action = isset($input['action']) ? $input['action'] : '';
    
    switch ($action) {
        case 'check_quota':
            handleQuotaCheck($user_id, $conn, $logger, $response);
            break;
            
        case 'generate_article':
        // 1) Generate artikel via OpenAI
        handleArticleGeneration($user_id, $input, $conn, $logger, $response);
            break;
    
       
            
        case 'get_article':
            handleGetArticle($user_id, $input, $conn, $logger, $response);
            break;
            
        default:
            $logger->error("Invalid action requested: $action");
            $response["message"] = "Invalid action";
            break;
    }
} else {
    $response["message"] = "Method not allowed";
    $response["code"] = 405;
    $logger->error("Method not allowed: " . $_SERVER['REQUEST_METHOD']);
}

// Return JSON response
echo json_encode($response);
exit();

/**
 * Check user's quota
 */

function handleQuotaCheck($user_id, $conn, $logger, &$response) {
    try {
        // Ambil free_quota_articles dan paid_quota
        $quota_stmt = $conn->prepare("
            SELECT 
                id AS quota_id,
                pub_id AS pub_id,
                free_quota_articles, 
                paid_quota 
            FROM publisher_quota 
            WHERE publisher_id = ? 
            LIMIT 1
        ");
        $quota_stmt->bind_param("i", $user_id);
        $quota_stmt->execute();
        $quota_result = $quota_stmt->get_result();
        
        if ($quota_result->num_rows === 0) {
            throw new Exception("Quota information not found");
        }
        $quota = $quota_result->fetch_assoc();
        
        // Hitung total quota
        $total_quota = (int)$quota['free_quota_articles'] + (int)$quota['paid_quota'];
        
        // Count artikel yang sudah dibuat (seluruh waktu atau bisa ditambahkan WHERE DATE(created_at)=CURDATE())
        $count_stmt = $conn->prepare("
            SELECT COUNT(*) AS article_count 
            FROM articles 
            WHERE publishers_local_id = ?
        ");
        $count_stmt->bind_param("i", $user_id);
        $count_stmt->execute();
        $count_result = $count_stmt->get_result();
        $count_data = $count_result->fetch_assoc();
        $used_quota = (int)$count_data['article_count'];
        
        // Hitung sisa kuota
        $remaining_quota = max(0, $total_quota - $used_quota);
        
        // Build response
        $response["status"] = "success";
        $response["code"]   = 200;
        $response["message"] = "Quota retrieved successfully";
        $response["data"]   = [
            "quota_id"         => $quota['quota_id'],
            "total_quota"      => $total_quota,
            "used_quota"       => $used_quota,
            "remaining_quota"  => $remaining_quota
        ];
        
        $logger->debug("Quota check successful for user $user_id: total=$total_quota, used=$used_quota");

    } catch (Exception $e) {
        $logger->error("Quota check failed: " . $e->getMessage());
        $response["status"]  = "error";
        $response["code"]    = 500;
        $response["message"] = "Failed to retrieve quota information";
    }
}



/**
 * Generate article using OpenAI API
 */
function handleArticleGeneration($user_id, $input, $conn, $logger, &$response) {

    $logger->debug("Starting handleArticleGeneration", ['user_id' => $user_id, 'input_keys' => array_keys($input)]);


   // —————————————————————————————————————————
    // 0. Cegah double submit dalam 1 menit
    // —————————————————————————————————————————
    $stmtCheck = $conn->prepare("
        SELECT UNIX_TIMESTAMP(NOW()) - UNIX_TIMESTAMP(created_at) AS seconds_ago
        FROM articles
        WHERE publishers_local_id = ?
        ORDER BY created_at DESC
        LIMIT 1
    ");
    $stmtCheck->bind_param("i", $user_id);
    $stmtCheck->execute();
    $resCheck = $stmtCheck->get_result()->fetch_assoc();
    if ($resCheck && $resCheck['seconds_ago'] < 60) {
        $logger->error("Double submit detected for user $user_id ({$resCheck['seconds_ago']}s ago)");
        // Beri respons langsung tanpa generate artikel
        echo json_encode([
            "status"  => "error",
            "code"    => 429,
            "message" => "Artikel tidak bisa dibuat karena Anda baru saja submit kurang dari 1 menit yang lalu. Mohon tunggu sejenak sebelum mencoba lagi."
        ]);
        exit();
    }
    $stmtCheck->close();


    try {
        // 1. Validasi required fields
        $required_fields = ['topic', 'focus', 'raw_content', 'tone', 'language'];
        foreach ($required_fields as $field) {
            if (!isset($input[$field]) || $input[$field] === '') {
                $logger->error("Missing required field", ['field' => $field, 'user_id' => $user_id]);
                throw new Exception("Missing required field: $field");
            }
        }
        $logger->debug("All required fields present", ['fields' => $required_fields]);

        // 2. Ambil quota
        $logger->debug("Preparing quota query", ['user_id' => $user_id]);
        $quota_stmt = $conn->prepare("
            SELECT id AS quota_id, 
            pub_id AS pub_id,
            free_quota_articles, paid_quota
            FROM publisher_quota
            WHERE publisher_id = ?
            LIMIT 1
        ");
        if (!$quota_stmt) {
            $logger->error("Quota prepare failed", ['error' => $conn->error]);
            throw new Exception("Database error on quota prepare");
        }
        $quota_stmt->bind_param("i", $user_id);
        $quota_stmt->execute();
        $quota_result = $quota_stmt->get_result();
        if ($quota_result->num_rows === 0) {
            $logger->error("Quota information not found", ['user_id' => $user_id]);
            throw new Exception("Quota information not found");
        }
        $quota = $quota_result->fetch_assoc();
        $logger->debug("Fetched quota", $quota);

        // 3. Hitung total quota
        $total_quota = (int)$quota['free_quota_articles'] + (int)$quota['paid_quota'];
        $logger->debug("Total quota calculated", ['total_quota' => $total_quota]);

        // 4. Hitung artikel yang sudah dibuat
        $logger->debug("Preparing article count query", ['user_id' => $user_id]);
        $count_stmt = $conn->prepare("
            SELECT COUNT(*) AS article_count
            FROM articles
            WHERE publishers_local_id = ?
        ");
        if (!$count_stmt) {
            $logger->error("Count prepare failed", ['error' => $conn->error]);
            throw new Exception("Database error on count prepare");
        }
        $count_stmt->bind_param("i", $user_id);
        $count_stmt->execute();
        $count_data = $count_stmt->get_result()->fetch_assoc();
        $used_quota = (int)$count_data['article_count'];
        $logger->debug("Used quota fetched", ['used_quota' => $used_quota]);

        // 5. Cek sisa quota
        if ($used_quota >= $total_quota) {
            $logger->error("Quota exceeded", ['used' => $used_quota, 'total' => $total_quota]);
            throw new Exception("Quota exceeded: you have used {$used_quota}/{$total_quota} articles today");
        }
        $remaining_quota = $total_quota - $used_quota;
        $logger->debug("Remaining quota", ['remaining_quota' => $remaining_quota]);

        // 6. Ambil LLM settings
        $logger->debug("Preparing LLM settings query");
        $llm_stmt = $conn->prepare("SELECT * FROM llm_settings ORDER BY id LIMIT 1");
        if (!$llm_stmt) {
            $logger->error("LLM settings prepare failed", ['error' => $conn->error]);
            throw new Exception("Database error on llm_settings prepare");
        }
        $llm_stmt->execute();
        $llm_result = $llm_stmt->get_result();
        if ($llm_result->num_rows === 0) {
            $logger->error("LLM settings not found");
            throw new Exception("LLM settings not found");
        }
        $llm_settings = $llm_result->fetch_assoc();
        $logger->debug("Fetched LLM settings", $llm_settings);

        // 6.b. Riset web: dicoba dulu, tapi kalau sumber kredibel tidak cukup,
        // artikel tetap dibuat lewat jalur "tanpa sumber eksternal" (lihat prompt di bawah)
        // alih-alih gagal total. Ini menghindari kegagalan generate untuk topik yang
        // memang minim liputan web, tanpa memaksa model mengarang sumber demi lolos validasi.
        try {
            $research = researchArticleSources(
                $input['topic'],
                $input['focus'],
                $input['raw_content'],
                $llm_settings['openai_key'],
                $llm_settings['llm_model']
            );
        } catch (Exception $research_error) {
            // Riset gagal (network, format respons tidak terduga, dll) — jangan gagalkan
            // seluruh generate artikel karenanya. Turun ke mode "tanpa sumber eksternal".
            $logger->error("Web research gagal, lanjut tanpa sumber eksternal: " . $research_error->getMessage());
            $research = ['research_notes' => '', 'sources' => []];
        }
        $source_urls = [];
        $source_titles = [];
        foreach ($research['sources'] as $source) {
            $source_url = trim($source['url'] ?? '');
            $source_scheme = strtolower((string) parse_url($source_url, PHP_URL_SCHEME));
            if (!filter_var($source_url, FILTER_VALIDATE_URL) || !in_array($source_scheme, ['http', 'https'], true)) {
                continue;
            }
            $source_urls[] = $source_url;
            $source_titles[] = trim($source['title'] ?? '') ?: parse_url($source_url, PHP_URL_HOST);
        }
        $source_urls = array_values(array_unique($source_urls));
        $has_sufficient_sources = count($source_urls) >= 2;
        $research_notes = trim($research['research_notes'] ?? '');
        $logger->debug("Web research completed", [
            'source_count' => count($source_urls),
            'sufficient'   => $has_sufficient_sources
        ]);

        // 7. Membangun prompt
        $topic      = $conn->real_escape_string($input['topic']);
        $focus      = $conn->real_escape_string($input['focus']);
        $raw_content= $conn->real_escape_string($input['raw_content']);
        $tone       = $conn->real_escape_string($input['tone']);
        $language   = $conn->real_escape_string($input['language']);
        $pub_id  = $conn->real_escape_string($quota['pub_id']);

        $prompt  = "Buat artikel dengan ouput pada html_content sebanyak sekitar 12000 – 15000 karakter.\n";
        $prompt .= "Topik    : {$topic}\n";
        $prompt .= "Fokus    : {$focus}\n";
        $prompt .= "Tone     : {$tone}\n";
        $prompt .= "Bahasa   : {$language}\n";
        $prompt .= "Konten   :\n{$raw_content}\n\n";

        if ($has_sufficient_sources) {
            $prompt .= "Catatan riset web terverifikasi:\n{$research_notes}\n\n";
            $prompt .= "Sumber referensi (gunakan nomor sesuai urutan):\n";
            foreach ($source_urls as $source_index => $source_url) {
                $source_title = $source_titles[$source_index] ?? parse_url($source_url, PHP_URL_HOST);
                $prompt .= "[" . ($source_index + 1) . "] {$source_title} — {$source_url}\n";
            }
            $prompt .= "\nAturan kualitas dan sumber:\n";
            $prompt .= "- Artikel harus tetap fokus pada sudut pembahasan yang diberikan.\n";
            $prompt .= "- Gunakan hanya fakta yang tersedia pada Konten dan Catatan riset. Jangan mengarang angka, kutipan, riset, nama, atau klaim faktual.\n";
            $prompt .= "- Setiap klaim faktual penting harus diberi penanda sumber [1], [2], dan seterusnya sesuai daftar sumber.\n";
            $prompt .= "- Jika data pada Konten tidak cukup, jelaskan keterbatasannya secara jujur dan jangan mengisi dengan asumsi.\n";
            $prompt .= "- Bedakan fakta, analisis, dan opini secara jelas.\n\n";
        } else {
            // Riset web tidak menemukan cukup sumber kredibel untuk topik ini (topik terlalu baru/niche).
            // Alih-alih gagal generate, artikel tetap dibuat tapi dengan pagar ketat supaya
            // tidak generik dan tidak berhalusinasi mengarang data/sumber.
            $prompt .= "Catatan: riset web tidak menemukan cukup sumber eksternal yang bisa diverifikasi untuk topik ini.\n\n";
            $prompt .= "\nAturan kualitas tanpa sumber eksternal (WAJIB dipatuhi):\n";
            $prompt .= "- JANGAN mencantumkan penanda sumber seperti [1] atau [2], JANGAN menyebut nama studi/riset/lembaga tertentu, JANGAN menulis angka statistik spesifik, tanggal presisi, atau kutipan langsung — semua itu tidak bisa diverifikasi tanpa sumber.\n";
            $prompt .= "- JANGAN mengarang atau berpura-pura mengutip sumber apa pun.\n";
            $prompt .= "- Tulis berdasarkan pengetahuan umum yang sudah mapan dan luas diketahui, bukan klaim spesifik yang butuh rujukan.\n";
            $prompt .= "- Bangun kedalaman dan kualitas artikel lewat penjelasan konsep yang runtut, contoh ilustratif generik (bukan kasus nyata dengan nama/tanggal karangan), analogi, langkah praktis, dan perbandingan pro-kontra — bukan lewat klaim faktual yang tidak bisa dipertanggungjawabkan.\n";
            $prompt .= "- Jika suatu bagian butuh data spesifik yang tidak bisa dipastikan, tulis secara kualitatif (mis. \"cenderung meningkat\") alih-alih memberi angka pasti.\n";
            $prompt .= "- Bedakan fakta umum, analisis, dan opini secara jelas.\n\n";
        }



        $prompt .= "Output HARUS valid JSON:\n";
        $prompt .= "{\n";
        $prompt .= "  \"title\": \"…\",\n";
        $prompt .= "  \"html_content\": \"…\",\n";
        $prompt .= "  \"tag\": \"…\"\n";
        $prompt .= "}\n\n";
        $prompt  .= "tag berisi 5-8 kata kunci relevan dengan artikel, dipisahkan koma tanpa spasi setelah koma (contoh: \"AI,Karir,Teknologi,Masa Depan,Pendidikan\").\n";
        $prompt  .= "html_content mengandung Paragraf, tag HTML <br>,<ul><li> bila diperlukan .\n";
        $prompt  .= "html_content TIDAK mengandung tag H1,H2,H3,H4,H5 .\n";
        $prompt  .= "html_content tidak ada css , tag doctype.\n";
         $prompt  .= "html_content tidak perlu mengulang judul/topik.\n";
       
       
        $logger->debug("Built prompt", ['prompt_snippet' => substr($prompt, 0, 100) . '…']);

        $logger->debug("Built prompt: $prompt");


        // 8. Panggil OpenAI API
        $logger->debug("Calling OpenAI API", [
            'model'      => $llm_settings['llm_model'],
            'max_tokens' => $llm_settings['max_tokens']
        ]);
        $api_response = callOpenAiApi(
            $prompt,
            $llm_settings['openai_key'],
            $llm_settings['llm_model'],
            $llm_settings['max_tokens'],
            $llm_settings['temperature']
        );
        $logger->debug("Received API response", ['api_response' => $api_response]);

          $logger->debug("Received API response $api_response");

        // 9. Decode response
        $response_data = json_decode($api_response, true);

      

        $logger->debug("response_data", ['response_data' => $response_data]);


        if (json_last_error() !== JSON_ERROR_NONE) {
            $logger->error("Failed to decode API response JSON", ['error' => json_last_error_msg()]);
            throw new Exception("Invalid JSON from API");
        }
        $logger->debug("Decoded API response", ['response_data' => $response_data]);

        // 10. Validasi content
        $finish_reason = $response_data['choices'][0]['finish_reason'] ?? 'unknown';
        if (!isset($response_data['choices'][0]['message']['content']) || trim((string) $response_data['choices'][0]['message']['content']) === '') {
            $logger->error("API response missing content field", ['response_data' => $response_data]);
            throw new Exception("OpenAI tidak mengembalikan isi artikel (finish_reason: {$finish_reason}). Periksa max_tokens.");
        }
        $json_output = $response_data['choices'][0]['message']['content'];
        $logger->debug("Model output", ['json_output_snippet' => substr($json_output, 0, 100) . '…']);

        // 11. Extract JSON block
        if (preg_match('/```json(.*?)```/s', $json_output, $matches)) {
            $json_str = trim($matches[1]);
            $logger->debug("Extracted JSON block", ['json_str_snippet' => substr($json_str, 0, 100) . '…']);
        } else {
            $json_str = $json_output;
            $logger->debug("No JSON fences found, using full output", ['json_str_snippet' => substr($json_str, 0, 100) . '…']);
        }

        // 12. Decode article_data
        $article_data = json_decode($json_str, true);

        $logger->debug("article_data :".  json_encode($article_data));

        if (json_last_error() !== JSON_ERROR_NONE) {

           $logger->error("Failed to decode article JSON", ['error' => json_last_error_msg()]);
            throw new Exception("Invalid JSON in article content");
        }

        $logger->debug("Decoded article data", ['article_data' => $article_data]);


        // 13. Validasi structure article_data
        // Kompatibilitas jika provider membungkus hasil dalam key `article`.
        if (isset($article_data['article']) && is_array($article_data['article'])) {
            $article_data = $article_data['article'];
        }

        if (empty($article_data['title']) || empty($article_data['html_content'])) {
            $logger->error("Article data missing required fields", [
                'finish_reason' => $finish_reason,
                'received_keys' => is_array($article_data) ? array_keys($article_data) : [],
                'content_length' => strlen($json_output)
            ]);
            throw new Exception("Format artikel dari OpenAI tidak lengkap (finish_reason: {$finish_reason}). Silakan coba lagi.");
        }

        // Daftar sumber dibangun oleh server agar URL yang dicantumkan sama
        // persis dengan URL yang dikonfirmasi blogger, bukan hasil karangan model.
        // Hanya ditampilkan kalau memang ada sumber tervalidasi (mode fallback tanpa sumber tidak punya ini).
        if (!empty($source_urls)) {
            $sources_html = '<hr><section class="article-sources"><h4>Sumber Referensi</h4><ol>';
            foreach ($source_urls as $source_index => $source_url) {
                $safe_source_url = htmlspecialchars($source_url, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
                $safe_source_title = htmlspecialchars($source_titles[$source_index] ?? $source_url, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
                $sources_html .= '<li><a href="' . $safe_source_url . '" target="_blank" rel="noopener noreferrer nofollow">' . $safe_source_title . '</a></li>';
            }
            $sources_html .= '</ol></section>';
            $article_data['html_content'] .= $sources_html;
        }


        $logger->debug("Decoded article data2", ['article_data' => $article_data]);

        // 14. Ambil usage tokens
        $input_token   = $response_data['usage']['prompt_tokens']    ?? 0;
        $output_token  = $response_data['usage']['completion_tokens'] ?? 0;
       

        $logger->debug("Token usage", ['article_data' => $article_data]);


         $logger->debug("input_token :".  $input_token);
          $logger->debug("output_token :".  $output_token);




        // 15. Siapkan response
        $response = [
            "status"       => "success",
            "code"         => 200,
            "message"      => "Article generated successfully",
            "data"         => [
                "title"         => $article_data['title'],
                "html_content"  => $article_data['html_content'],
                "tag"           => $article_data['tag'] ?? '',
                "input_token"   => $input_token,
                "output_token"  => $output_token,
                "json_response" => $api_response,
                "topic"         => $topic,
                "tone"          => $tone,
                "language"      => $language,
                "pub_id"        => $pub_id,
            ],
        ];


        $logger->debug("Response payload :".  json_encode($response));




    } catch (Exception $e) {
        $logger->error("Article generation failed $e->getMessage()", [
            'message'   => $e->getMessage(),
            'exception' => $e
        ]);
        $response = [
            "status"  => "error",
            "code"    => 500,
            "message" => $e->getMessage(),
        ];
        // Jangan lanjut INSERT jika proses generate artikel gagal.
        return;
    }

     
      // —————————————————————————————————————————
    // 16. INSERT langsung ke tabel `articles`
    // —————————————————————————————————————————
    $insertSql = "
        INSERT INTO articles (
            ispublished,
            publishers_local_id,
            pub_id,
            title,
            html_content,
            images,
            tag,
            language,
            tone,
            topic,
            input_token,
            output_token,
            json_response,
            created_at,
            updated_at
        ) VALUES (
            1, ?, ?, ?, ?, '', ?, ?, ?, ?, ?, ?, ?, NOW(), NOW()
        )
    ";
    $ins = $conn->prepare($insertSql);
    if (!$ins) {

        $logger->debug("Prepare INSERT failed");

        $logger->error("Prepare INSERT failed: " . $conn->error);
        throw new Exception("Database prepare error: " . $conn->error);
    }

    $d = $response['data'];
    $ins->bind_param(
        "iisssssiiss",
        $user_id,             // publishers_local_id
        $d['pub_id'],         // pub_id
        $d['title'],          // title
        $d['html_content'],   // html_content
        $d['tag'],            // tag
        $d['language'],       // language
        $d['tone'],           // tone
        $d['topic'],          // topic
        $d['input_token'],    // input_token
        $d['output_token'],   // output_token
        $d['json_response']   // json_response
    );

    if ($ins->execute()) {
        $response['data']['article_id'] = $conn->insert_id;
      
    } else {
        $logger->error("Execute INSERT failed: " . $ins->error);
        throw new Exception("Database execute error: " . $ins->error);
    }
    // —————————————————————————————————————————

  


}




/**
 * Publish generated article to database
 */

function handleArticlePublication($user_id, $input, $conn, $logger, &$response) {
          


}




/**
 * Get a specific article
 */
function handleGetArticle($user_id, $input, $conn, $logger, &$response) {
    try {
        if (!isset($input['article_id'])) {
            throw new Exception("Missing article ID");
        }
        
        $article_id = $input['article_id'];
        
        $stmt = $conn->prepare("SELECT * FROM articles WHERE id = ? AND publishers_local_id = ?");
        $stmt->bind_param("ii", $article_id, $user_id);
        $stmt->execute();
        $result = $stmt->get_result();
        
        if ($result->num_rows === 0) {
            throw new Exception("Article not found");
        }
        
        $article = $result->fetch_assoc();
        
        $response["status"] = "success";
        $response["code"] = 200;
        $response["message"] = "Article retrieved successfully";
        $response["data"] = $article;
        
        $logger->debug("Article retrieved successfully: ID $article_id by user $user_id");
    } catch (Exception $e) {
        $logger->error("Article retrieval failed: " . $e->getMessage());
        $response["message"] = $e->getMessage();
        $response["code"] = 500;
    }
}

/**
 * Cari sumber web dan rangkum fakta sebelum artikel dibuat.
 */
function researchArticleSources($topic, $focus, $raw_content, $api_key, $model) {
    $url = "https://api.openai.com/v1/responses";
    $research_prompt = "Lakukan riset web untuk artikel berikut.\n"
        . "Topik: {$topic}\n"
        . "Fokus: {$focus}\n"
        . "Catatan blogger: {$raw_content}\n\n"
        . "Pilih maksimal 5 sumber yang benar-benar relevan dan kredibel. Utamakan sumber primer, dokumentasi resmi, "
        . "lembaga pemerintah, universitas, paper, atau media bereputasi. Jangan membuat URL. "
        . "Jika topik ini memang tidak punya liputan web yang memadai atau kredibel, kembalikan array sources kosong — "
        . "JANGAN memaksakan sumber yang lemah, tidak relevan, atau mengarang URL hanya demi memenuhi jumlah. "
        . "Rangkum hanya fakta yang benar-benar didukung sumber dan sertakan konteks, angka, serta batasan penting.";

    $data = [
        "model" => trim($model),
        "instructions" => "Anda adalah peneliti fakta. Gunakan web search dan jangan mengarang sumber atau klaim.",
        "input" => $research_prompt,
        "tools" => [["type" => "web_search"]],
        "tool_choice" => "auto",
        "reasoning" => ["effort" => "low"],
        "max_output_tokens" => 4000,
        "text" => [
            "format" => [
                "type" => "json_schema",
                "name" => "article_research",
                "strict" => true,
                "schema" => [
                    "type" => "object",
                    "properties" => [
                        "research_notes" => ["type" => "string"],
                        "sources" => [
                            "type" => "array",
                            "minItems" => 0,
                            "maxItems" => 5,
                            "items" => [
                                "type" => "object",
                                "properties" => [
                                    "title" => ["type" => "string"],
                                    "url" => ["type" => "string"]
                                ],
                                "required" => ["title", "url"],
                                "additionalProperties" => false
                            ]
                        ]
                    ],
                    "required" => ["research_notes", "sources"],
                    "additionalProperties" => false
                ]
            ]
        ]
    ];

    $payload = json_encode($data);
    if ($payload === false) {
        throw new Exception("Gagal membuat request riset: " . json_last_error_msg());
    }

    $ch = curl_init($url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_HTTPHEADER, [
        "Content-Type: application/json",
        "Authorization: Bearer " . $api_key
    ]);
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_POSTFIELDS, $payload);
    curl_setopt($ch, CURLOPT_TIMEOUT, 180);
    $result = curl_exec($ch);
    if ($result === false) {
        $curl_error = curl_error($ch);
        curl_close($ch);
        throw new Exception("Gagal melakukan riset web: {$curl_error}");
    }
    $http_status = (int) curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);

    $response = json_decode($result, true);
    if ($http_status < 200 || $http_status >= 300) {
        $message = $response['error']['message'] ?? "HTTP {$http_status}";
        throw new Exception("OpenAI web search error: {$message}");
    }

    $output_text = '';
    $cited_sources = [];
    foreach (($response['output'] ?? []) as $output_item) {
        if (($output_item['type'] ?? '') !== 'message') {
            continue;
        }
        foreach (($output_item['content'] ?? []) as $content_item) {
            if (($content_item['type'] ?? '') === 'output_text') {
                $output_text .= $content_item['text'] ?? '';
                foreach (($content_item['annotations'] ?? []) as $annotation) {
                    if (($annotation['type'] ?? '') !== 'url_citation') {
                        continue;
                    }
                    $citation = $annotation['url_citation'] ?? $annotation;
                    $citation_url = trim($citation['url'] ?? '');
                    if ($citation_url !== '') {
                        $cited_sources[$citation_url] = [
                            'title' => trim($citation['title'] ?? '') ?: parse_url($citation_url, PHP_URL_HOST),
                            'url' => $citation_url
                        ];
                    }
                }
            }
        }
    }
    if ($output_text === '') {
        throw new Exception("OpenAI web search tidak mengembalikan hasil riset.");
    }

    // Jaga-jaga bila model membungkus JSON dengan markdown fence meski schema strict diminta.
    $research_json_str = $output_text;
    if (preg_match('/```(?:json)?(.*?)```/s', $output_text, $research_json_matches)) {
        $research_json_str = trim($research_json_matches[1]);
    }

    $research = json_decode($research_json_str, true);
    if (!is_array($research)) {
        throw new Exception("OpenAI web search mengembalikan format riset yang tidak valid.");
    }
    // Sumber boleh kurang dari 2 atau kosong — handleArticleGeneration() akan beralih
    // ke mode "tanpa sumber eksternal" alih-alih memaksa model mengarang sumber tambahan.
    $research['research_notes'] = trim($research['research_notes'] ?? '');
    $research['sources'] = is_array($research['sources'] ?? null) ? $research['sources'] : [];

    // Jika API memberikan citation annotations, gunakan itu karena URL tersebut
    // berasal langsung dari hasil web search, bukan karangan model.
    if (count($cited_sources) >= 2) {
        $research['sources'] = array_slice(array_values($cited_sources), 0, 5);
    } elseif (count($cited_sources) >= 1 && count($cited_sources) > count($research['sources'])) {
        $research['sources'] = array_values($cited_sources);
    }
    return $research;
}

/**
 * Call OpenAI API
 */
function callOpenAiApi($prompt, $api_key, $model, $max_tokens, $temperature) {
    $url = "https://api.openai.com/v1/chat/completions";
    $data = [
        "model" => $model,
        "messages" => [
            ["role" => "system", "content" => "Anda adalah asisten yang membantu menulis artikel."],
            ["role" => "user", "content" => $prompt]
        ],
        "response_format" => [
            "type" => "json_schema",
            "json_schema" => [
                "name" => "generated_article",
                "strict" => true,
                "schema" => [
                    "type" => "object",
                    "properties" => [
                        "title" => ["type" => "string"],
                        "html_content" => ["type" => "string"],
                        "tag" => ["type" => "string"]
                    ],
                    "required" => ["title", "html_content", "tag"],
                    "additionalProperties" => false
                ]
            ]
        ]
    ];

    // GPT-5 adalah reasoning model dan menggunakan max_completion_tokens.
    // Model lama seperti GPT-4.1 tetap menggunakan konfigurasi sebelumnya.
    $is_gpt5 = strpos(strtolower(trim($model)), 'gpt-5') === 0;
    if ($is_gpt5) {
        // Reasoning dan output terlihat memakai kuota token yang sama.
        // Artikel panjang membutuhkan ruang lebih dari konfigurasi lama.
        $data['max_completion_tokens'] = max(8000, (int)$max_tokens);

        // GPT-5 awal (termasuk gpt-5-nano) mendukung `minimal`, sedangkan
        // keluarga bernomor seperti gpt-5.4 menggunakan none/low/medium/high/xhigh.
        $normalized_model = strtolower(trim($model));
        $data['reasoning_effort'] = preg_match('/^gpt-5\.\d+/', $normalized_model)
            ? 'low'
            : 'minimal';
    } else {
        $data['max_tokens'] = (int)$max_tokens;
        $data['temperature'] = (float)$temperature;
    }

    $payload = json_encode($data);
    if ($payload === false) {
        throw new Exception("Failed to encode OpenAI request: " . json_last_error_msg());
    }
    
    $ch = curl_init($url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_HTTPHEADER, [
        "Content-Type: application/json",
        "Authorization: Bearer " . $api_key
    ]);
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_POSTFIELDS, $payload);
    
    $result = curl_exec($ch);
    
    if (curl_errno($ch)) {
        throw new Exception("cURL Error: " . curl_error($ch));
    }
    
    $http_status = (int)curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);

    if ($result === false || $result === '') {
        throw new Exception("OpenAI API returned an empty response");
    }

    $decoded_result = json_decode($result, true);
    if ($http_status < 200 || $http_status >= 300) {
        $api_message = $decoded_result['error']['message'] ?? "HTTP {$http_status}";
        throw new Exception("OpenAI API error: " . $api_message);
    }

    if (isset($decoded_result['error'])) {
        throw new Exception("OpenAI API error: " . ($decoded_result['error']['message'] ?? 'Unknown error'));
    }

    return $result;
}
