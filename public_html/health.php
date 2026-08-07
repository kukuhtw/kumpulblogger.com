<?php
declare(strict_types=1);

header('Content-Type: application/json; charset=utf-8');
header('Cache-Control: no-store');

echo json_encode([
    'status' => 'ok',
    'service' => getenv('APP_NAME') ?: 'MyAdNetwork',
], JSON_UNESCAPED_SLASHES);
