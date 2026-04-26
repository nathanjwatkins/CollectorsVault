<?php
/**
 * CollectorVault — GitHub Webhook Deploy
 * Fetches files from GitHub raw URLs using classic token (Authorization header)
 */

define('GITHUB_TOKEN', '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');
define('GITHUB_RAW',   'https://raw.githubusercontent.com/nathanjwatkins/CollectorsVault/main/');
define('WEBHOOK_SECRET', 'cv_deploy_2025_nate');

// Verify webhook signature
$payload   = file_get_contents('php://input');
$signature = $_SERVER['HTTP_X_HUB_SIGNATURE_256'] ?? '';
$expected  = 'sha256=' . hash_hmac('sha256', $payload, WEBHOOK_SECRET);

if (!hash_equals($expected, $signature)) {
    http_response_code(403);
    die('Signature mismatch');
}

$data  = json_decode($payload, true);
$log   = [];
$log[] = date('Y-m-d H:i:s') . ' — Deploy triggered';
$log[] = 'Commit: ' . ($data['after'] ?? 'unknown');

// Fetch file from GitHub raw URL using classic token
function fetch_github($path) {
    $url = GITHUB_RAW . $path;
    $ch  = curl_init($url);
    curl_setopt_array($ch, [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_FOLLOWLOCATION => true,
        CURLOPT_TIMEOUT        => 30,
        CURLOPT_HTTPHEADER     => [
            'Authorization: token ' . GITHUB_TOKEN,
            'User-Agent: CollectorVault-Deploy/3.0',
        ],
        CURLOPT_SSL_VERIFYPEER => true,
    ]);
    $content  = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);
    if ($httpCode !== 200) return ['ok' => false, 'error' => "HTTP $httpCode"];
    return ['ok' => true, 'content' => $content];
}

$deploy_dir = __DIR__;
$beta_dir   = __DIR__ . '/beta';

// Root files
$root_files = [
    'deploy.php', 'beta_deploy.php',
    'api.php', 'categories.js.php', 'collection.php', 'index.php',
    'logout.php', 'nav.php', 'scanner.php', 'shared.css',
    'theme.php', 'toast.php', '.htaccess'
];

// Beta-specific files (fetched from beta/ subfolder in repo)
$beta_files = [
    'scanner.php', 'collection.php', 'shared.css',
    'api.php', 'categories.js.php', 'index.php',
    'logout.php', 'nav.php', 'theme.php', 'toast.php', '.htaccess'
];

$log[] = '--- Root files ---';
foreach ($root_files as $file) {
    $r = fetch_github($file);
    if ($r['ok']) {
        file_put_contents($deploy_dir . '/' . $file, $r['content']);
        $log[] = "  ✓ $file (" . strlen($r['content']) . " bytes)";
    } else {
        $log[] = "  ✗ $file — " . $r['error'];
    }
}

$log[] = '--- Beta files ---';
foreach ($beta_files as $file) {
    $r = fetch_github('beta/' . $file);
    if ($r['ok']) {
        file_put_contents($beta_dir . '/' . $file, $r['content']);
        $log[] = "  ✓ beta/$file (" . strlen($r['content']) . " bytes)";
    } else {
        $log[] = "  ✗ beta/$file — " . $r['error'];
    }
}

$log[] = 'Done.';
file_put_contents($deploy_dir . '/deploy.log', implode("\n", $log) . "\n\n", FILE_APPEND);
http_response_code(200);
echo implode("\n", $log);
