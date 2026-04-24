<?php
/**
 * CollectorVault — GitHub Auto-Deploy Webhook v2
 * Uses curl instead of file_get_contents for better Hostinger compatibility
 */

define('DEPLOY_SECRET', 'cv_deploy_2025_nate');
define('GITHUB_TOKEN',  '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');
define('GITHUB_RAW',    'https://raw.githubusercontent.com/nathanjwatkins/CollectorsVault/main/');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405); die('Method not allowed');
}

$payload   = file_get_contents('php://input');
$signature = $_SERVER['HTTP_X_HUB_SIGNATURE_256'] ?? '';
$expected  = 'sha256=' . hash_hmac('sha256', $payload, DEPLOY_SECRET);

if (!hash_equals($expected, $signature)) {
    http_response_code(403); die('Invalid signature');
}

$data = json_decode($payload, true);

if (($data['ref'] ?? '') !== 'refs/heads/main') {
    http_response_code(200); die('Not main branch — skipped');
}

$files = [
    'api.php', 'categories.js.php', 'collection.php', 'index.php',
    'logout.php', 'nav.php', 'scanner.php', 'shared.css',
    'theme.php', 'toast.php', '.htaccess'
];

$beta_files = [
    'api.php', 'categories.js.php', 'collection.php', 'index.php',
    'logout.php', 'nav.php', 'scanner.php', 'shared.css',
    'theme.php', 'toast.php', '.htaccess', 'test.php'
];

$log   = [];
$log[] = date('Y-m-d H:i:s') . ' — Deploy triggered';
$log[] = 'Commit: ' . ($data['after'] ?? 'unknown');
$log[] = 'Message: ' . ($data['head_commit']['message'] ?? 'no message');

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

$deploy_dir = __DIR__;

foreach ($files as $file) {
    $url    = GITHUB_RAW . $file;
    $result = fetch_file($url, GITHUB_TOKEN);
    if ($result['ok']) {
        file_put_contents($deploy_dir . '/' . $file, $result['content']);
        $log[] = "  ✓ {$file}";
    } else {
        $log[] = "  ✗ FAILED: {$file} — " . $result['error'];
    }
}

// Deploy beta files
$beta_dir = $deploy_dir . '/beta';
if (is_dir($beta_dir)) {
    $beta_raw = 'https://raw.githubusercontent.com/nathanjwatkins/CollectorsVault/main/beta/';
    foreach ($beta_files as $file) {
        $result = fetch_file($beta_raw . $file, GITHUB_TOKEN);
        if ($result['ok']) {
            file_put_contents($beta_dir . '/' . $file, $result['content']);
            $log[] = "  ✓ beta/{$file}";
        } else {
            $log[] = "  ✗ FAILED: beta/{$file} — " . $result['error'];
        }
    }
}

$log[] = 'Deploy complete.';
$log_text = implode("\n", $log) . "\n\n";
file_put_contents($deploy_dir . '/deploy.log', $log_text, FILE_APPEND);

http_response_code(200);
echo implode("\n", $log);
