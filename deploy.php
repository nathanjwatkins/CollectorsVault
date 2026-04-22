<?php
/**
 * CollectorVault — GitHub Auto-Deploy Webhook
 * Place this file at: public_html/deploy.php
 * Set GitHub webhook to POST to: https://collectorsvault.store/deploy.php
 */

// Secret key — must match the GitHub webhook secret
define('DEPLOY_SECRET', 'cv_deploy_2025_nate');

// Only allow POST from GitHub
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405); die('Method not allowed');
}

// Verify GitHub signature
$payload   = file_get_contents('php://input');
$signature = $_SERVER['HTTP_X_HUB_SIGNATURE_256'] ?? '';
$expected  = 'sha256=' . hash_hmac('sha256', $payload, DEPLOY_SECRET);

if (!hash_equals($expected, $signature)) {
    http_response_code(403); die('Invalid signature');
}

$data = json_decode($payload, true);

// Only deploy on pushes to main branch
if (($data['ref'] ?? '') !== 'refs/heads/main') {
    http_response_code(200); die('Not main branch — skipped');
}

// Pull latest files from GitHub
$repo_url = 'https://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@github.com/nathanjwatkins/CollectorsVault.git';
$deploy_dir = __DIR__;

// Files to deploy (excludes data/ and uploads/)
$files = [
    'api.php', 'categories.js.php', 'collection.php', 'index.php',
    'logout.php', 'nav.php', 'scanner.php', 'shared.css',
    'theme.php', 'toast.php', '.htaccess'
];

$log = [];
$log[] = date('Y-m-d H:i:s') . ' — Deploy triggered by push to main';
$log[] = 'Commit: ' . ($data['after'] ?? 'unknown');
$log[] = 'Message: ' . ($data['head_commit']['message'] ?? 'no message');

// Use GitHub raw content API to fetch each file
foreach ($files as $file) {
    $url = "https://raw.githubusercontent.com/nathanjwatkins/CollectorsVault/main/{$file}";
    $ctx = stream_context_create([
        'http' => [
            'method' => 'GET',
            'header' => "Authorization: token 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\r\n" .
                        "User-Agent: CollectorVault-Deploy\r\n",
            'timeout' => 15,
        ]
    ]);
    $content = @file_get_contents($url, false, $ctx);
    if ($content !== false) {
        file_put_contents($deploy_dir . '/' . $file, $content);
        $log[] = "  ✓ {$file}";
    } else {
        $log[] = "  ✗ FAILED: {$file}";
    }
}

$log[] = 'Deploy complete.';
$log_text = implode("\n", $log) . "\n\n";

// Append to deploy log
file_put_contents($deploy_dir . '/deploy.log', $log_text, FILE_APPEND);

http_response_code(200);
echo implode("\n", $log);
