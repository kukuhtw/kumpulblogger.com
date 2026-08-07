<?php
// Database configuration is stored outside the public web root.
$envFile = dirname(__DIR__) . '/.env';
$fileEnv = is_readable($envFile)
    ? parse_ini_file($envFile, false, INI_SCANNER_RAW)
    : [];
if ($fileEnv === false) {
    throw new RuntimeException('Unable to parse database environment file.');
}
$processEnv = getenv();
$env = array_merge($fileEnv, is_array($processEnv) ? $processEnv : []);

$requiredEnv = [
    'DB_HOST',
    'DB_USERNAME',
    'DB_PASSWORD',
    'DB_DATABASE',
];
foreach ($requiredEnv as $key) {
    if (!array_key_exists($key, $env)) {
        throw new RuntimeException("Missing required environment value: {$key}");
    }
}

$servername_db = (string) $env['DB_HOST'];
$port_db       = max(1, (int) ($env['DB_PORT'] ?? 3306));
$username_db   = (string) $env['DB_USERNAME'];
$password_db   = (string) $env['DB_PASSWORD'];
$dbname_db     = (string) $env['DB_DATABASE'];
ini_set('mysqli.default_port', (string) $port_db);

// Application configuration. Keep these legacy variable names so existing
// pages continue to work while their values remain outside the web root.
$SMTP_API_KEY       = (string) ($env['SMTP_API_KEY'] ?? '');
$SMTP_API_SECRET    = (string) ($env['SMTP_API_SECRET'] ?? '');
$DOMAIN_NAME        = (string) ($env['DOMAIN_NAME'] ?? '');
$recaptcha_site_key = (string) ($env['RECAPTCHA_SITE_KEY'] ?? '');
$recaptcha_secret   = (string) ($env['RECAPTCHA_SECRET'] ?? '');
$info_pembayaran    = str_replace('\\n', "\n", (string) ($env['PAYMENT_INFO'] ?? ''));
