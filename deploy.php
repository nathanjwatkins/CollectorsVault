<?php
/**
 * CollectorVault — deploy.php
 * GitHub webhook → fetches all live files from GitHub raw → writes to public_html/
 * Token read from /home/u133725179/cv_token.txt (never committed to repo)
 *
 * Responds to GitHub within ~100ms so its 10s webhook timeout never trips.
 * The actual file fetching happens after the response is closed.
 */

$tokenFile = dirname(__DIR__) . '/cv_token.txt';
$token = file_exists($tokenFile) ? trim(file_get_contents($tokenFile)) : '';
if (!$token) { http_response_code(500); die('Token file missing'); }

define('GITHUB_TOKEN',   $token);
define('GITHUB_RAW',     'https://raw.githubusercontent.com/nathanjwatkins/CollectorsVault/main/');
define('WEBHOOK_SECRET', 'cv_deploy_2025_nate');

$payload   = file_get_contents('php://input');
$signature = $_SERVER['HTTP_X_HUB_SIGNATURE_256'] ?? '';
$expected  = 'sha256=' . hash_hmac('sha256', $payload, WEBHOOK_SECRET);
if (!hash_equals($expected, $signature)) { http_response_code(403); die('Bad signature'); }

// ── Respond to GitHub immediately ───────────────────────────────────────────
// GitHub gives webhooks 10 seconds; sequential GitHub-raw fetches sometimes
// exceed that on shared hosting and the delivery is marked failed even when
// the deploy actually finishes. Closing the response first avoids the race.
ignore_user_abort(true);
set_time_limit(120); // give the background work plenty of time

$ack = date('Y-m-d H:i:s') . ' — Deploy queued (background fetch starting)';
http_response_code(200);
header('Content-Type: text/plain; charset=utf-8');
header('Content-Length: ' . strlen($ack));
header('Connection: close');
echo $ack;
if (function_exists('fastcgi_finish_request')) {
    fastcgi_finish_request();
} else {
    @ob_end_flush();
    @flush();
}

// ── Background work begins here ─────────────────────────────────────────────
$log  = [date('Y-m-d H:i:s') . ' — Deploy (GitHub fetch)'];
$root = __DIR__;

function cv_fetch(string $path): array {
    // Append a cache-busting query string. GitHub raw aggressively caches
    // (~5min) which means a deploy fired immediately after a push can fetch
    // the previous commit's contents. The query param forces a fresh fetch.
    $bust = '?_cb=' . time() . '_' . mt_rand(1000, 9999);
    $ch = curl_init(GITHUB_RAW . $path . $bust);
    curl_setopt_array($ch, [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_FOLLOWLOCATION => true,
        CURLOPT_TIMEOUT        => 30,
        CURLOPT_CONNECTTIMEOUT => 10,
        CURLOPT_HTTPHEADER     => [
            'Authorization: token ' . GITHUB_TOKEN,
            'User-Agent: CV-Deploy/7.2',
            'Cache-Control: no-cache',
            'Pragma: no-cache',
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
