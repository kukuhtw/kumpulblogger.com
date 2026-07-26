<?php
// article_api.php - Backend API for article generation and management

// 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 https://openrouter.ai/settings/keys

// Include necessary files
require_once("db.php");
require_once("config.php");
//require_once("logger.php");

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
    try {
        // 1. Validasi required fields
        $required_fields = ['topic', 'raw_content', 'tone', 'language'];
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

        // 7. Membangun prompt
        $topic      = $conn->real_escape_string($input['topic']);
        $raw_content= $conn->real_escape_string($input['raw_content']);
        $tone       = $conn->real_escape_string($input['tone']);
        $language   = $conn->real_escape_string($input['language']);
        $pub_id  = $conn->real_escape_string($quota['pub_id']);

        $prompt  = "Buat artikel dengan ouput pada html_content sebanyak 6700 karakter.\n";
        $prompt .= "Topik    : {$topic}\n";
        $prompt .= "Tone     : {$tone}\n";
        $prompt .= "Bahasa   : {$language}\n";
        $prompt .= "Konten   :\n{$raw_content}\n\n";

       

        $prompt .= "Output HARUS valid JSON:\n";
        $prompt .= "{\n";
        $prompt .= "  \"title\": \"…\",\n";
        $prompt .= "  \"html_content\": \"…\",\n";
        $prompt .= "  \"tag\": \"…\"\n";
        $prompt .= "}\n\n";
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
        if (!isset($response_data['choices'][0]['message']['content'])) {
            $logger->error("API response missing content field", ['response_data' => $response_data]);
            throw new Exception("Failed to get valid response from API");
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
        if (empty($article_data['title']) || empty($article_data['html_content'])) {
             $logger->debug("Article data missing");

            throw new Exception("Invalid API response format");
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
        $logger->error("Article generation failed", [
            'message'   => $e->getMessage(),
            'exception' => $e
        ]);
        $response = [
            "status"  => "error",
            "code"    => 500,
            "message" => $e->getMessage(),
        ];
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
        "max_tokens" => (int)$max_tokens,
        "temperature" => (float)$temperature
    ];
    $payload = json_encode($data);
    
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
    
    curl_close($ch);
    return $result;
}