<?php
/**
 * CollectorVault — deploy.php
 * Reads GitHub token from /home/u133725179/cv_token.txt (not in repo)
 */

$tokenFile = '/home/u133725179/cv_token.txt';
$token = file_exists($tokenFile) ? trim(file_get_contents($tokenFile)) : '';
if (!$token) { http_response_code(500); die('Token file missing: ' . $tokenFile); }

define('GITHUB_TOKEN',   $token);
define('GITHUB_RAW',     'https://raw.githubusercontent.com/nathanjwatkins/CollectorsVault/main/');
define('WEBHOOK_SECRET', 'cv_deploy_2025_nate');

$payload   = file_get_contents('php://input');
$signature = $_SERVER['HTTP_X_HUB_SIGNATURE_256'] ?? '';
$expected  = 'sha256=' . hash_hmac('sha256', $payload, WEBHOOK_SECRET);
if (!hash_equals($expected, $signature)) { http_response_code(403); die('Bad signature'); }

$log = [date('Y-m-d H:i:s') . ' — Deploy fired'];
$root = __DIR__; $beta = __DIR__ . '/beta';

function cv_fetch(string $path): array {
    $ch = curl_init(GITHUB_RAW . $path);
    curl_setopt_array($ch, [CURLOPT_RETURNTRANSFER=>true, CURLOPT_FOLLOWLOCATION=>true, CURLOPT_TIMEOUT=>30,
        CURLOPT_HTTPHEADER=>['Authorization: token '.GITHUB_TOKEN, 'User-Agent: CV-Deploy/6.0']]);
    $body = curl_exec($ch); $code = curl_getinfo($ch, CURLINFO_HTTP_CODE); curl_close($ch);
    return [$code, $body ?: ''];
}

foreach (['deploy.php','beta_deploy.php'] as $f) {
    [$c,$b] = cv_fetch($f);
    if ($c===200 && $b) { file_put_contents($root.'/'.$f, $b); $log[]="OK $f"; }
    else $log[]="FAIL $f HTTP=$c";
}

$beta_files = ['scanner.php','collection.php','shared.css','api.php','categories.js.php','index.php','theme.php','logout.php','nav.php','.htaccess'];
foreach ($beta_files as $f) {
    [$c,$b] = cv_fetch('beta/'.$f);
    if ($c===200 && $b) {
        if ($f==='api.php') {
            $b=str_replace("__DIR__.'/data/'","__DIR__.'/../data/'",$b);
            $b=str_replace("__DIR__.'/uploads/'","__DIR__.'/../uploads/'",$b);
            if (!str_contains($b,"session_name('CVBETA')"))
                $b=str_replace("session_start();","session_name('CVBETA');ini_set('session.cookie_path','/beta/');session_start();",$b);
        }
        file_put_contents($beta.'/'.$f, $b);
        $log[]="OK beta/$f (".strlen($b)."b)";
    } else $log[]="FAIL beta/$f HTTP=$c";
}

$log[]='Done.';
file_put_contents($root.'/deploy.log', implode("\n",$log)."\n\n", FILE_APPEND);
http_response_code(200); echo implode("\n",$log);
