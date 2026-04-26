<?php
/**
 * Beta patcher — fetches correct files from GitHub raw with classic token
 * Call: https://collectorsvault.store/beta/patcher.php?key=cv_fix_now
 */
if (($_GET['key'] ?? '') !== 'cv_fix_now') { http_response_code(403); die(); }
header('Content-Type: text/plain');

$token = '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';
$raw   = 'https://raw.githubusercontent.com/nathanjwatkins/CollectorsVault/main/beta/';
$root  = dirname(__DIR__);
$beta  = __DIR__;

function cv_fetch($url, $token) {
    $ch = curl_init($url);
    curl_setopt_array($ch, [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_FOLLOWLOCATION => true,
        CURLOPT_TIMEOUT        => 30,
        CURLOPT_HTTPHEADER     => [
            'Authorization: token ' . $token,
            'User-Agent: CollectorVault-Patcher/1.0',
        ],
    ]);
    $body = curl_exec($ch);
    $code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);
    return [$code, $body];
}

$files = ['scanner.php', 'collection.php', 'shared.css', 'api.php',
          'categories.js.php', 'index.php', 'theme.php', 'logout.php',
          'nav.php', '.htaccess'];

$ok = 0; $fail = 0;
foreach ($files as $f) {
    [$code, $body] = cv_fetch($raw . $f, $token);
    if ($code === 200 && $body) {
        // Patch api.php paths
        if ($f === 'api.php') {
            $body = str_replace("__DIR__ . '/data/'",    "__DIR__ . '/../data/'",    $body);
            $body = str_replace("__DIR__ . '/uploads/'", "__DIR__ . '/../uploads/'", $body);
            if (strpos($body, "session_name('CVBETA')") === false)
                $body = str_replace("session_start();", "session_name('CVBETA'); ini_set('session.cookie_path', '/beta/'); session_start();", $body);
        }
        $bytes = file_put_contents($beta . '/' . $f, $body);
        $extra = '';
        if ($f === 'scanner.php') $extra = ' cat-grid=' . (strpos($body,'cat-grid')!==false?'YES':'NO') . ' styles=' . substr_count($body,'<style>');
        echo "OK $f ($bytes bytes)$extra\n";
        $ok++;
    } else {
        echo "FAIL $f HTTP=$code\n";
        $fail++;
    }
}

// Also update root beta_deploy.php from GitHub
[$code, $body] = cv_fetch('https://raw.githubusercontent.com/nathanjwatkins/CollectorsVault/main/beta_deploy.php', $token);
if ($code === 200 && $body) {
    $bytes = file_put_contents($root . '/beta_deploy.php', $body);
    echo "OK beta_deploy.php ($bytes bytes)\n";
} else {
    echo "FAIL beta_deploy.php HTTP=$code\n";
}

// Self-delete
unlink(__FILE__);
echo "Done — $ok OK, $fail FAIL\n";
