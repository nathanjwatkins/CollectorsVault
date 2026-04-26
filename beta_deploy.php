<?php
/**
 * Standalone beta deployer — no webhook signature needed
 * Protected by a simple secret key
 * Usage: https://collectorsvault.store/beta_deploy.php?key=cv_beta_now
 */

define('SECRET_KEY', 'cv_beta_now');
define('GITHUB_TOKEN', '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');
define('GITHUB_RAW', 'https://raw.githubusercontent.com/nathanjwatkins/CollectorsVault/main/beta/');

if (($_GET['key'] ?? '') !== SECRET_KEY) {
    http_response_code(403);
    die('Forbidden');
}

$beta_files = [
    'api.php', 'categories.js.php', 'collection.php', 'index.php',
    'logout.php', 'nav.php', 'scanner.php', 'shared.css',
    'theme.php', 'toast.php', '.htaccess'
];

$beta_dir = __DIR__ . '/beta';
$log = [];
$log[] = date('Y-m-d H:i:s') . ' — Beta manual deploy';

function fetch_file($url, $token) {
    $ch = curl_init($url);
    curl_setopt_array($ch, [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_FOLLOWLOCATION => true,
        CURLOPT_TIMEOUT        => 30,
        CURLOPT_HTTPHEADER     => [
            'Authorization: token ' . $token,
            'User-Agent: CollectorVault-Deploy/2.0',
            'Accept: application/vnd.github.v3.raw',
        ],
        CURLOPT_SSL_VERIFYPEER => true,
    ]);
    $content  = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $error    = curl_error($ch);
    curl_close($ch);
    if ($content === false || $httpCode !== 200) {
        return ['ok' => false, 'error' => "HTTP {$httpCode} — {$error}"];
    }
    return ['ok' => true, 'content' => $content];
}

header('Content-Type: text/plain');

foreach ($beta_files as $file) {
    $result = fetch_file(GITHUB_RAW . $file, GITHUB_TOKEN);
    if ($result['ok']) {
        $bytes = file_put_contents($beta_dir . '/' . $file, $result['content']);
        $log[] = "  ✓ beta/{$file} ({$bytes} bytes)";
    } else {
        $log[] = "  ✗ FAILED: beta/{$file} — " . $result['error'];
    }
}

$log[] = 'Done.';
echo implode("\n", $log);
