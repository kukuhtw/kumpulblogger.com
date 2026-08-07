<?php

declare(strict_types=1);

/**
 * Keep every legacy providers_data.json location in sync during deployment.
 *
 * PROVIDER_NAME and PROVIDER_DOMAIN_URL may override the normal APP_NAME and
 * DOMAIN_NAME deployment variables. KCE_APP_URL is used to infer whether the
 * public URL is HTTP or HTTPS when PROVIDER_DOMAIN_URL is not set.
 */

$rootDirectory = dirname(__DIR__);
$publicDirectory = $rootDirectory . '/public_html';

$providerName = trim((string) (getenv('PROVIDER_NAME') ?: getenv('APP_NAME') ?: 'MyAdNetwork'));
$configuredUrl = trim((string) (getenv('PROVIDER_DOMAIN_URL') ?: ''));
$domainName = trim((string) (getenv('DOMAIN_NAME') ?: 'localhost'));
$kceAppUrl = trim((string) (getenv('KCE_APP_URL') ?: ''));

if ($providerName === '') {
    fwrite(STDERR, "PROVIDER_NAME/APP_NAME tidak boleh kosong.\n");
    exit(64);
}

function normalizeProviderUrl(string $configuredUrl, string $domainName, string $kceAppUrl): string
{
    $candidate = $configuredUrl;
    if ($candidate === '') {
        $domainHasScheme = preg_match('~^https?://~i', $domainName) === 1;
        if ($domainHasScheme) {
            $candidate = $domainName;
        } else {
            $kceScheme = parse_url($kceAppUrl, PHP_URL_SCHEME);
            $scheme = is_string($kceScheme) && in_array(strtolower($kceScheme), ['http', 'https'], true)
                ? strtolower($kceScheme)
                : 'https';
            $candidate = $scheme . '://' . $domainName;
        }
    }

    if (preg_match('~^https?://~i', $candidate) !== 1) {
        $candidate = 'https://' . $candidate;
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

try {
    $providerUrl = normalizeProviderUrl($configuredUrl, $domainName, $kceAppUrl);
} catch (InvalidArgumentException $exception) {
    fwrite(STDERR, $exception->getMessage() . "\n");
    exit(64);
}

$json = json_encode([
    [
        'id' => '1',
        'providers_name' => $providerName,
        'providers_domain_url' => $providerUrl,
    ],
], JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);

if (!is_string($json)) {
    fwrite(STDERR, "Gagal membuat data provider JSON.\n");
    exit(1);
}
$json .= PHP_EOL;

$relativeTargets = [
    'providers_data.json',
    'admin/providers_data.json',
    'API/providers_data.json',
    'blog/providers_data.json',
    'cronjob/providers_data.json',
    'JSON/providers_data.json',
];

foreach ($relativeTargets as $relativeTarget) {
    $target = $publicDirectory . '/' . $relativeTarget;
    $directory = dirname($target);
    if (!is_dir($directory) && !mkdir($directory, 0755, true) && !is_dir($directory)) {
        fwrite(STDERR, "Gagal membuat direktori {$directory}.\n");
        exit(1);
    }

    $temporary = tempnam($directory, '.providers-data-');
    if ($temporary === false || file_put_contents($temporary, $json, LOCK_EX) === false) {
        fwrite(STDERR, "Gagal menulis file sementara untuk {$target}.\n");
        exit(1);
    }
    chmod($temporary, 0644);
    // Containers start as root, but generated files must remain writable by
    // Apache/PHP jobs such as genJSON/geninfo_provider.php.
    if (DIRECTORY_SEPARATOR === '/') {
        $owner = fileowner($publicDirectory);
        $group = filegroup($publicDirectory);
        if (is_int($owner)) {
            @chown($temporary, $owner);
        }
        if (is_int($group)) {
            @chgrp($temporary, $group);
        }
    }
    if (!rename($temporary, $target)) {
        @unlink($temporary);
        fwrite(STDERR, "Gagal memperbarui {$target}.\n");
        exit(1);
    }

    echo "Provider data: {$relativeTarget}\n";
}

echo "Identitas provider disinkronkan: {$providerName} ({$providerUrl})\n";
