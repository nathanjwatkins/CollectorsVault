<?php
/**
 * Beta deployer v3 — applies beta patches server-side from live files
 * No GitHub token needed
 * Usage: https://collectorsvault.store/beta_deploy.php?key=cv_beta_now
 */

if (($_GET['key'] ?? '') !== 'cv_beta_now') {
    http_response_code(403); die('Forbidden');
}

header('Content-Type: text/plain');

$root = __DIR__;
$beta = __DIR__ . '/beta';
$log  = [date('Y-m-d H:i:s') . ' — Beta patch deploy v3'];

function patch_for_beta($content, $filename) {
    // Fix all API fetch calls to use /beta/api.php
    $content = preg_replace("/fetch\('api\.php/", "fetch('/beta/api.php", $content);
    $content = preg_replace('/fetch\("api\.php/', 'fetch("/beta/api.php', $content);
    $content = preg_replace("/fetch\(`api\.php/", "fetch(`/beta/api.php", $content);

    // Fix internal links
    $content = str_replace('href="scanner.php"',    'href="/beta/scanner.php"',    $content);
    $content = str_replace('href="collection.php"', 'href="/beta/collection.php"', $content);
    $content = str_replace("href='scanner.php'",    "href='/beta/scanner.php'",    $content);
    $content = str_replace("href='collection.php'", "href='/beta/collection.php'", $content);
    $content = str_replace("href='logout.php'",     "href='/beta/logout.php'",     $content);
    $content = str_replace('href="logout.php"',     'href="/beta/logout.php"',     $content);

    // Fix PHP redirects
    $content = str_replace("header('Location: scanner.php')",    "header('Location: /beta/scanner.php')",    $content);
    $content = str_replace("header('Location: collection.php')", "header('Location: /beta/collection.php')", $content);
    $content = str_replace("header('Location: index.php')",      "header('Location: /beta/index.php')",      $content);

    // Fix PHP includes to use local beta path
    // (theme.php, nav.php, categories.js.php are relative includes — they work fine)

    // Add session name for beta
    $content = str_replace(
        "session_start();",
        "if (!isset(\$_beta_session_set)) { session_name('CVBETA'); ini_set('session.cookie_path', '/beta/'); \$_beta_session_set = true; } session_start();",
        $content
    );
    // Only do it once
    $content = str_replace(
        "if (!isset(\$_beta_session_set)) { session_name('CVBETA'); ini_set('session.cookie_path', '/beta/'); \$_beta_session_set = true; } session_start();\nif (!isset(\$_beta_session_set))",
        "session_start();\nif (!isset(\$_beta_session_set))",
        $content
    );

    return $content;
}

$files_to_patch = ['scanner.php', 'collection.php', 'index.php', 'logout.php', 'nav.php'];

foreach ($files_to_patch as $file) {
    $src = $root . '/' . $file;
    $dst = $beta . '/' . $file;

    if (!file_exists($src)) {
        $log[] = "  ✗ SOURCE MISSING: {$file}";
        continue;
    }

    $content = file_get_contents($src);
    $patched = patch_for_beta($content, $file);
    $bytes   = file_put_contents($dst, $patched);

    if ($bytes !== false) {
        // Verify style blocks
        $styleCount = substr_count($patched, '<style>');
        $log[] = "  ✓ beta/{$file} ({$bytes} bytes, {$styleCount} style tags)";
    } else {
        $log[] = "  ✗ WRITE FAILED: beta/{$file}";
    }
}

// Copy files that don't need patching
$copy_files = ['shared.css', 'api.php', 'categories.js.php', 'theme.php', 'toast.php'];
foreach ($copy_files as $file) {
    $src = $root . '/' . $file;
    $dst = $beta . '/' . $file;
    if (file_exists($src)) {
        $bytes = file_put_contents($dst, file_get_contents($src));
        $log[] = "  ✓ beta/{$file} copied ({$bytes} bytes)";
    }
}

// Fix api.php DATA_DIR to use parent path
$apiPath = $beta . '/api.php';
if (file_exists($apiPath)) {
    $api = file_get_contents($apiPath);
    $api = str_replace(
        "define('DATA_DIR',        __DIR__ . '/data/');",
        "define('DATA_DIR',        __DIR__ . '/../data/');",
        $api
    );
    $api = str_replace(
        "define('UPLOADS_DIR',     __DIR__ . '/uploads/');",
        "define('UPLOADS_DIR',     __DIR__ . '/../uploads/');",
        $api
    );
    // Add CVBETA session to api.php
    $api = str_replace(
        "session_start();",
        "session_name('CVBETA'); ini_set('session.cookie_path', '/beta/'); session_start();",
        $api
    );
    file_put_contents($apiPath, $api);
    $log[] = "  ✓ beta/api.php patched (DATA_DIR + session)";
}

$log[] = '';
$log[] = 'Done — ' . count($files_to_patch) . ' files patched, ' . count($copy_files) . ' copied.';

file_put_contents($root . '/beta_deploy.log', implode("\n", $log) . "\n\n", FILE_APPEND);
echo implode("\n", $log);
