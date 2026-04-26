<?php
/**
 * CollectorVault — GitHub Webhook Deploy v4
 * Uses GitHub API with base64 content decoding — most reliable for private repos
 */

define('GITHUB_USER',  'nathanjwatkins');
define('GITHUB_TOKEN', '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');
define('GITHUB_REPO',  'CollectorsVault');
define('GITHUB_REF',   'main');
define('WEBHOOK_SECRET', 'cv_deploy_2025_nate');

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
$log[] = 'Message: ' . ($data['head_commit']['message'] ?? 'no message');

// Fetch via GitHub Contents API — returns JSON with base64-encoded content
// Works reliably for private repos with classic token
function fetch_github_file($path) {
    $url = 'https://api.github.com/repos/' . GITHUB_USER . '/' . GITHUB_REPO . '/contents/' . ltrim($path, '/') . '?ref=' . GITHUB_REF;
    
    $ch = curl_init($url);
    curl_setopt_array($ch, [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_FOLLOWLOCATION => true,
        CURLOPT_TIMEOUT        => 30,
        CURLOPT_USERPWD        => GITHUB_USER . ':' . GITHUB_TOKEN,
        CURLOPT_HTTPHEADER     => [
            'User-Agent: CollectorVault-Deploy/4.0',
            'Accept: application/vnd.github.v3+json',
        ],
        CURLOPT_SSL_VERIFYPEER => true,
    ]);
    
    $body     = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $error    = curl_error($ch);
    curl_close($ch);
    
    if ($httpCode !== 200) {
        return ['ok' => false, 'error' => "HTTP $httpCode: $error"];
    }
    
    $json = json_decode($body, true);
    if (!isset($json['content'])) {
        return ['ok' => false, 'error' => 'No content in response'];
    }
    
    // GitHub returns base64-encoded content (with newlines)
    $content = base64_decode(str_replace("\n", '', $json['content']));
    return ['ok' => true, 'content' => $content];
}

$deploy_dir = __DIR__;
$beta_dir   = __DIR__ . '/beta';

// Root files to deploy
$root_files = [
    'deploy.php', 'beta_deploy.php',
    'api.php', 'categories.js.php', 'collection.php', 'index.php',
    'logout.php', 'nav.php', 'scanner.php', 'shared.css',
    'theme.php', 'toast.php', '.htaccess'
];

// Beta-specific files
$beta_files = [
    'scanner.php', 'collection.php', 'shared.css',
    'api.php', 'categories.js.php', 'index.php',
    'logout.php', 'nav.php', 'theme.php', 'toast.php', '.htaccess', 'patcher.php'
];

$log[] = '--- Root ---';
foreach ($root_files as $file) {
    $r = fetch_github_file($file);
    if ($r['ok']) {
        $bytes = file_put_contents($deploy_dir . '/' . $file, $r['content']);
        $log[] = "  ✓ $file ($bytes bytes)";
    } else {
        $log[] = "  X FAILED: $file — " . $r['error'];
    }
}

$log[] = '--- Beta ---';
foreach ($beta_files as $file) {
    $r = fetch_github_file('beta/' . $file);
    if ($r['ok']) {
        $content = $r['content'];
        // Patch api.php for beta paths
        if ($file === 'api.php') {
            $content = str_replace("__DIR__ . '/data/'",    "__DIR__ . '/../data/'",    $content);
            $content = str_replace("__DIR__ . '/uploads/'", "__DIR__ . '/../uploads/'", $content);
            if (strpos($content, "session_name('CVBETA')") === false) {
                $content = str_replace("session_start();", "session_name('CVBETA'); ini_set('session.cookie_path', '/beta/'); session_start();", $content);
            }
        }
        $bytes = file_put_contents($beta_dir . '/' . $file, $content);
        $info  = '';
        if ($file === 'scanner.php') $info = ' cat-grid=' . (strpos($content,'cat-grid')!==false?'YES':'NO') . ' styles=' . substr_count($content,'<style>');
        $log[] = "  ✓ beta/$file ($bytes bytes)$info";
    } else {
        $log[] = "  X FAILED: beta/$file — " . $r['error'];
    }
}

$log[] = 'Done.';
file_put_contents($deploy_dir . '/deploy.log', implode("\n", $log) . "\n\n", FILE_APPEND);
http_response_code(200);
echo implode("\n", $log);
