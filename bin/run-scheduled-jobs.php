<?php

declare(strict_types=1);

if (PHP_SAPI !== 'cli') {
    http_response_code(404);
    exit;
}

$minute = (int) gmdate('i');
$jobs = ['click-audit', 'update-click-metadata'];

if ($minute % 7 === 0) {
    $jobs[] = 'mapping-local';
}
if ($minute % 9 === 0) {
    $jobs[] = 'mapping-rate';
    $jobs[] = 'recap-local';
}
if ($minute % 8 === 0) {
    $jobs[] = 'recap-publisher';
}
if ($minute % 30 === 0) {
    $jobs[] = 'recap-total-publisher';
    $jobs[] = 'calculate-budget';
    $jobs[] = 'latest-publishers';
}

$runner = __DIR__ . '/cron.php';
foreach ($jobs as $job) {
    fwrite(STDOUT, sprintf("[%s] Menjalankan %s\n", gmdate(DATE_ATOM), $job));
    $command = escapeshellarg(PHP_BINARY) . ' ' . escapeshellarg($runner) . ' ' . escapeshellarg($job);
    passthru($command, $exitCode);
    if ($exitCode !== 0) {
        fwrite(STDERR, "Job {$job} gagal dengan exit code {$exitCode}.\n");
        exit($exitCode);
    }
}
