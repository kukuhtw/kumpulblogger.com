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

    $clickRules = [
        ['aa', 2, 'Max clicks by same IP and user cookie in 1 minute / Jumlah klik maksimum oleh IP dan cookie pengguna yang sama dalam 1 menit'],
        ['ab', 2, 'Max clicks by same IP and browser in 2 minutes / Jumlah klik maksimum oleh IP dan browser yang sama dalam 2 menit'],
        ['ac', 3, 'Max clicks by same IP and browser in 5 minutes / Jumlah klik maksimum oleh IP dan browser yang sama dalam 5 menit'],
        ['ad', 3, 'Max clicks by same IP and user cookie in 10 minutes / Jumlah klik maksimum oleh IP dan cookie pengguna yang sama dalam 10 menit'],
        ['ae', 4, 'Max clicks by same IP and browser in 15 minutes / Jumlah klik maksimum oleh IP dan browser yang sama dalam 15 menit'],
        ['af', 4, 'Max clicks by same IP and browser in 20 minutes / Jumlah klik maksimum oleh IP dan browser yang sama dalam 20 menit'],
        ['ag', 4, 'Max clicks by same IP and user cookie in 25 minutes / Jumlah klik maksimum oleh IP dan cookie pengguna yang sama dalam 25 menit'],
        ['ah', 5, 'Max clicks by same IP and browser in 30 minutes / Jumlah klik maksimum oleh IP dan browser yang sama dalam 30 menit'],
        ['ai', 5, 'Max clicks by same IP and user cookie in 35 minutes / Jumlah klik maksimum oleh IP dan cookie pengguna yang sama dalam 35 menit'],
        ['aj', 1, 'Max clicks by same IP and user cookie in 20 seconds / Jumlah klik maksimum oleh IP dan cookie pengguna yang sama dalam 20 detik'],
        ['ak', 5, 'Max clicks by same IP and browser in 1 hour / Jumlah klik maksimum oleh IP dan browser yang sama dalam 1 jam'],
        ['al', 6, 'Max clicks by same IP and browser in 2 hours / Jumlah klik maksimum oleh IP dan browser yang sama dalam 2 jam'],
        ['am', 6, 'Max clicks by same IP and browser in 4 hours / Jumlah klik maksimum oleh IP dan browser yang sama dalam 4 jam'],
        ['an', 5, 'Max clicks by same IP and browser in 6 hours / Jumlah klik maksimum oleh IP dan browser yang sama dalam 6 jam'],
        ['ao', 2, 'Max clicks by same IP and browser in 12 hours / Jumlah klik maksimum oleh IP dan browser yang sama dalam 12 jam'],
        ['ap', 5, 'Max clicks by same IP and browser in 24 hours / Jumlah klik maksimum oleh IP dan browser yang sama dalam 24 jam'],
    ];
    $stmt = $db->prepare(
        'INSERT IGNORE INTO setting_rule_clicks (rule_name, threshold, description) VALUES (?, ?, ?)'
    );
    $clickRulesInserted = 0;
    foreach ($clickRules as [$ruleName, $threshold, $description]) {
        $stmt->bind_param('sis', $ruleName, $threshold, $description);
        $stmt->execute();
        $clickRulesInserted += $stmt->affected_rows;
    }
    $stmt->close();

    fwrite(STDOUT, sprintf(
        "Bootstrap instalasi: providers=%s, providers_contact_person=%s, llm_settings=%s, setting_rule_clicks=%d-dibuat.\n",
        $providerInserted ? 'dibuat' : 'sudah-ada',
        $contactInserted ? 'dibuat' : 'sudah-ada',
        $llmInserted ? 'dibuat' : 'sudah-ada',
        $clickRulesInserted
    ));
} catch (Throwable $exception) {
    fwrite(STDERR, "Bootstrap data instalasi gagal: {$exception->getMessage()}\n");
    $db->close();
    exit(1);
}

$db->close();
