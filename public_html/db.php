<?php
// Database configuration is stored outside the public web root.
$envFile = dirname(__DIR__) . '/.env';
if (!is_readable($envFile)) {
    throw new RuntimeException('Database environment file is missing or unreadable.');
}

$env = parse_ini_file($envFile, false, INI_SCANNER_RAW);
if ($env === false) {
    throw new RuntimeException('Unable to parse database environment file.');
}

$requiredEnv = [
    'DB_HOST',
    'DB_USERNAME',
    'DB_PASSWORD',
    'DB_DATABASE',
    'SMTP_API_KEY',
    'DOMAIN_NAME',
    'RECAPTCHA_SITE_KEY',
    'RECAPTCHA_SECRET',
    'PAYMENT_INFO',
];
foreach ($requiredEnv as $key) {
    if (!array_key_exists($key, $env)) {
        throw new RuntimeException("Missing required environment value: {$key}");
    }
}

$servername_db = (string) $env['DB_HOST'];
$username_db   = (string) $env['DB_USERNAME'];
$password_db   = (string) $env['DB_PASSWORD'];
$dbname_db     = (string) $env['DB_DATABASE'];

// Application configuration. Keep these legacy variable names so existing
// pages continue to work while their values remain outside the web root.
$SMTP_API_KEY       = (string) $env['SMTP_API_KEY'];
$DOMAIN_NAME        = (string) $env['DOMAIN_NAME'];
$recaptcha_site_key = (string) $env['RECAPTCHA_SITE_KEY'];
$recaptcha_secret   = (string) $env['RECAPTCHA_SECRET'];
$info_pembayaran    = str_replace('\\n', "\n", (string) $env['PAYMENT_INFO']);
