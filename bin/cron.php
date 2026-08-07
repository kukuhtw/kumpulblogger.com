<?php
declare(strict_types=1);

if (PHP_SAPI !== 'cli') {
    http_response_code(404);
    exit;
}

$jobs = [
    'click-audit' => 'click_audit.php',
    'mapping-local' => 'mapping_ads_publisher.php',
    'mapping-rate' => 'mapping_ads_publisher_check_rate.php',
    'recap-local' => 'rekap_harian_local.php',
    'recap-publisher' => 'rekap_harian_publisher.php',
    'recap-total-publisher' => 'rekap_total_publisher.php',
    'update-click-metadata' => 'update_titleads_sitename_clickads.php',
    'calculate-budget' => 'calculate_budgetspentads.php',
    'latest-publishers' => '../genJSON/last10publishers.php',
];

$job = (string) ($argv[1] ?? '');
if (!isset($jobs[$job])) {
    fwrite(STDERR, "Job tidak dikenal. Pilihan: " . implode(', ', array_keys($jobs)) . PHP_EOL);
    exit(64);
}

$cronDirectory = dirname(__DIR__) . '/public_html/cronjob';
if (!chdir($cronDirectory)) {
    fwrite(STDERR, "Folder cronjob tidak dapat dibuka." . PHP_EOL);
    exit(1);
}

require $cronDirectory . '/' . $jobs[$job];
