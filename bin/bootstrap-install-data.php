<?php

declare(strict_types=1);

if (PHP_SAPI !== 'cli') {
    http_response_code(404);
    exit;
}

require dirname(__DIR__) . '/public_html/db.php';

function envValue(string $name, string $default = ''): string
{
    global $env;
    $value = $env[$name] ?? getenv($name);
    return $value === false || trim((string) $value) === '' ? $default : trim((string) $value);
}

function providerUrl(): string
{
    $configured = envValue('PROVIDER_DOMAIN_URL');
    $domain = envValue('DOMAIN_NAME', 'localhost');
    $candidate = $configured !== '' ? $configured : $domain;

    if (preg_match('~^https?://~i', $candidate) !== 1) {
        $kceScheme = parse_url(envValue('KCE_APP_URL'), PHP_URL_SCHEME);
        $scheme = is_string($kceScheme) && in_array(strtolower($kceScheme), ['http', 'https'], true)
            ? strtolower($kceScheme)
            : 'https';
        $candidate = $scheme . '://' . $candidate;
    }

    $parts = parse_url($candidate);
    if (!is_array($parts) || empty($parts['host'])) {
        throw new InvalidArgumentException('PROVIDER_DOMAIN_URL/DOMAIN_NAME bukan URL publik yang valid.');
    }

    $scheme = strtolower((string) ($parts['scheme'] ?? 'https'));
    if (!in_array($scheme, ['http', 'https'], true)) {
        throw new InvalidArgumentException('URL provider harus menggunakan skema http atau https.');
    }

    $url = $scheme . '://' . $parts['host'];
    if (isset($parts['port'])) {
        $url .= ':' . $parts['port'];
    }
    return $url;
}

$db = new mysqli($servername_db, $username_db, $password_db, $dbname_db, $port_db);
if ($db->connect_errno) {
    fwrite(STDERR, "Koneksi database gagal: {$db->connect_error}\n");
    exit(1);
}
$db->set_charset('utf8mb4');

try {
    $url = providerUrl();
    $providerName = envValue('PROVIDER_NAME', envValue('APP_NAME', 'MyAdNetwork'));
    $providerCode = envValue('PROVIDER_CODE', strtoupper(bin2hex(random_bytes(6))));
    $hashKey = envValue('PROVIDER_HASH_KEY', bin2hex(random_bytes(32)));
    $secretKey = envValue('PROVIDER_SECRET_KEY', bin2hex(random_bytes(32)));
    $apiEndpoint = envValue('PROVIDER_API_ENDPOINT', $url . '/API');

    $stmt = $db->prepare(
        'INSERT IGNORE INTO providers '
        . '(id, providers_code, providers_name, providers_domain_url, hash_key, secret_key, api_endpoint, regdate) '
        . 'VALUES (1, ?, ?, ?, ?, ?, ?, NOW())'
    );
    $stmt->bind_param('ssssss', $providerCode, $providerName, $url, $hashKey, $secretKey, $apiEndpoint);
    $stmt->execute();
    $providerInserted = $stmt->affected_rows === 1;
    $stmt->close();

    $contactEmail = envValue('PROVIDER_CONTACT_EMAIL', envValue('ADMIN_EMAIL'));
    $contactWhatsapp = envValue('PROVIDER_CONTACT_WHATSAPP', envValue('ADMIN_WHATSAPP'));
    $accountName = envValue('PROVIDER_ACCOUNT_NAME');
    $accountBank = envValue('PROVIDER_ACCOUNT_BANK');
    $accountNumber = envValue('PROVIDER_ACCOUNT_NUMBER');
    $stmt = $db->prepare(
        'INSERT IGNORE INTO providers_contact_person '
        . '(id, providers_domain_url, email, whatsapp, account_name, account_bank, account_number, last_update) '
        . 'VALUES (1, ?, NULLIF(?, \'\'), NULLIF(?, \'\'), NULLIF(?, \'\'), NULLIF(?, \'\'), NULLIF(?, \'\'), NOW())'
    );
    $stmt->bind_param('ssssss', $url, $contactEmail, $contactWhatsapp, $accountName, $accountBank, $accountNumber);
    $stmt->execute();
    $contactInserted = $stmt->affected_rows === 1;
    $stmt->close();

    $llmModel = envValue('LLM_MODEL', 'gpt-4.1-mini');
    $openAiKey = envValue('OPENAI_API_KEY');
    $replicateKey = envValue('REPLICATE_API_KEY');
    $maxTokens = max(1, (int) envValue('LLM_MAX_TOKENS', '2048'));
    $temperature = (float) envValue('LLM_TEMPERATURE', '0.70');
    if ($temperature < 0 || $temperature > 2) {
        throw new InvalidArgumentException('LLM_TEMPERATURE harus berada di antara 0 dan 2.');
    }
    $stmt = $db->prepare(
        'INSERT IGNORE INTO llm_settings '
        . '(id, llm_model, openai_key, replicate_key, max_tokens, temperature, regdate) '
        . 'VALUES (1, ?, NULLIF(?, \'\'), NULLIF(?, \'\'), ?, ?, NOW())'
    );
    $stmt->bind_param('sssid', $llmModel, $openAiKey, $replicateKey, $maxTokens, $temperature);
    $stmt->execute();
    $llmInserted = $stmt->affected_rows === 1;
    $stmt->close();

    fwrite(STDOUT, sprintf(
        "Bootstrap instalasi: providers=%s, providers_contact_person=%s, llm_settings=%s.\n",
        $providerInserted ? 'dibuat' : 'sudah-ada',
        $contactInserted ? 'dibuat' : 'sudah-ada',
        $llmInserted ? 'dibuat' : 'sudah-ada'
    ));
} catch (Throwable $exception) {
    fwrite(STDERR, "Bootstrap data instalasi gagal: {$exception->getMessage()}\n");
    $db->close();
    exit(1);
}

$db->close();
