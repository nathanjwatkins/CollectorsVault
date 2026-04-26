<?php
/**
 * Beta deployer v4
 * - scanner.php and collection.php: fetched from GitHub (beta-specific versions)
 * - shared.css, api.php, etc: copied from live root and patched
 * Usage: https://collectorsvault.store/beta_deploy.php?key=cv_beta_now
 */

if (($_GET['key'] ?? '') !== 'cv_beta_now') {
    http_response_code(403); die('Forbidden');
}

define('GITHUB_TOKEN', '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');
define('GITHUB_REPO',  'nathanjwatkins/CollectorsVault');
define('GITHUB_REF',   'main');

header('Content-Type: text/plain');

$root = __DIR__;
$beta = __DIR__ . '/beta';
$log  = [date('Y-m-d H:i:s') . ' — Beta deploy v4'];

// Fetch from GitHub API (works with private repos + classic token)
function github_fetch($path) {
    $url = 'https://api.github.com/repos/' . GITHUB_REPO . '/contents/' . ltrim($path, '/') . '?ref=' . GITHUB_REF;
    $ch = curl_init($url);
    curl_setopt_array($ch, [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_FOLLOWLOCATION => true,
        CURLOPT_TIMEOUT        => 30,
        CURLOPT_HTTPHEADER     => [
            'Authorization: token ' . GITHUB_TOKEN,
            'User-Agent: CollectorVault-Deploy/4.0',
            'Accept: application/vnd.github.v3.raw',
        ],
        CURLOPT_SSL_VERIFYPEER => true,
    ]);
    $content  = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);
    if ($content === false || $httpCode !== 200) {
        return ['ok' => false, 'error' => "HTTP {$httpCode}"];
    }
    return ['ok' => true, 'content' => $content];
}

// 1. Fetch beta-specific files from GitHub (scanner + collection have unique beta layouts)
$from_github = ['scanner.php', 'collection.php', 'index.php', 'shared.css',
                'api.php', 'categories.js.php', 'theme.php', 'toast.php',
                'logout.php', 'nav.php', '.htaccess'];

foreach ($from_github as $file) {
    $result = github_fetch('beta/' . $file);
    if ($result['ok']) {
        $bytes = file_put_contents($beta . '/' . $file, $result['content']);
        $styleCount = substr_count($result['content'], '<style>');
        $log[] = "  ✓ beta/{$file} ({$bytes} bytes" . ($styleCount ? ", {$styleCount} style blocks" : "") . ")";
    } else {
        // Fallback: copy from live root with patches for non-unique files
        $src = $root . '/' . $file;
        if (file_exists($src) && !in_array($file, ['scanner.php', 'collection.php'])) {
            $content = file_get_contents($src);
            file_put_contents($beta . '/' . $file, $content);
            $log[] = "  ~ beta/{$file} (GitHub failed [{$result['error']}], used live copy)";
        } else {
            $log[] = "  ✗ FAILED: beta/{$file} — " . $result['error'];
        }
    }
}

// 2. Fix api.php DATA_DIR and session
$apiPath = $beta . '/api.php';
if (file_exists($apiPath)) {
    $api = file_get_contents($apiPath);
    $api = str_replace("__DIR__ . '/data/'",    "__DIR__ . '/../data/'",    $api);
    $api = str_replace("__DIR__ . '/uploads/'", "__DIR__ . '/../uploads/'", $api);
    if (strpos($api, "session_name('CVBETA')") === false) {
        $api = str_replace("session_start();", "session_name('CVBETA'); ini_set('session.cookie_path', '/beta/'); session_start();", $api);
    }
    file_put_contents($apiPath, $api);
    $log[] = "  ✓ beta/api.php patched";
}

$log[] = '';
$log[] = 'Done.';
file_put_contents($root . '/beta_deploy.log', implode("\n", $log) . "\n\n", FILE_APPEND);
echo implode("\n", $log);
