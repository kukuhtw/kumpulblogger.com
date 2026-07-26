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

$requiredEnv = ['DB_HOST', 'DB_USERNAME', 'DB_PASSWORD', 'DB_DATABASE'];
foreach ($requiredEnv as $key) {
    if (!array_key_exists($key, $env)) {
        throw new RuntimeException("Missing required environment value: {$key}");
    }
}

$servername_db = (string) $env['DB_HOST'];
$username_db   = (string) $env['DB_USERNAME'];
$password_db   = (string) $env['DB_PASSWORD'];
$dbname_db     = (string) $env['DB_DATABASE'];
