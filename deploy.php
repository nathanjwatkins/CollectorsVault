<?php
/**
 * CollectorVault — deploy.php
 * GitHub webhook → fetches all live files from GitHub raw → writes to public_html/
 * Token read from /home/u133725179/cv_token.txt (never committed to repo)
 */

$tokenFile = '/home/u133725179/cv_token.txt';
$token = file_exists($tokenFile) ? trim(file_get_contents($tokenFile)) : '';
if (!$token) { http_response_code(500); die('Token file missing'); }

define('GITHUB_TOKEN',   $token);
define('GITHUB_RAW',     'https://raw.githubusercontent.com/nathanjwatkins/CollectorsVault/main/');
define('WEBHOOK_SECRET', 'cv_deploy_2025_nate');

$payload   = file_get_contents('php://input');
$signature = $_SERVER['HTTP_X_HUB_SIGNATURE_256'] ?? '';
$expected  = 'sha256=' . hash_hmac('sha256', $payload, WEBHOOK_SECRET);
if (!hash_equals($expected, $signature)) { http_response_code(403); die('Bad signature'); }

$log  = [date('Y-m-d H:i:s') . ' — Deploy (GitHub fetch)'];
$root = __DIR__;

function cv_fetch(string $path): array {
    $ch = curl_init(GITHUB_RAW . $path);
    curl_setopt_array($ch, [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_FOLLOWLOCATION => true,
        CURLOPT_TIMEOUT        => 30,
        CURLOPT_HTTPHEADER     => [
            'Authorization: token ' . GITHUB_TOKEN,
            'User-Agent: CV-Deploy/7.0',
        ],
    ]);
    $body = curl_exec($ch);
    $code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);
    return [$code, $body ?: ''];
}

// Self-update deploy.php
[$c, $b] = cv_fetch('deploy.php');
if ($c === 200 && $b) { file_put_contents($root . '/deploy.php', $b); $log[] = "OK deploy.php (" . strlen($b) . "b)"; }
else $log[] = "FAIL deploy.php HTTP=$c";

// Live site files
$files = [
    'scanner.php',
    'collection.php',
    'shared.css',
    'api.php',
    'categories.js.php',
    'index.php',
    'theme.php',
    'logout.php',
    'nav.php',
];

foreach ($files as $f) {
    [$c, $b] = cv_fetch($f);
    if ($c === 200 && $b) {
        file_put_contents($root . '/' . $f, $b);
        $log[] = "OK $f (" . strlen($b) . "b)";
    } else {
        $log[] = "FAIL $f HTTP=$c";
    }
}

$log[] = 'Done — ' . count($files) . ' files deployed.';
file_put_contents($root . '/deploy.log', implode("\n", $log) . "\n\n", FILE_APPEND);
http_response_code(200);
echo implode("\n", $log);
