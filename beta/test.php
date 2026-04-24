<?php
session_name('CVBETA');
ini_set('session.cookie_path', '/beta/');
ini_set('session.cookie_samesite', 'Lax');
ini_set('session.cookie_secure', '1');
ini_set('session.cookie_httponly', '1');
session_start();
header('Content-Type: text/plain');

echo "Session name: " . session_name() . "\n";
echo "Session ID: " . session_id() . "\n";
echo "Session user: " . ($_SESSION['user'] ?? 'NOT SET') . "\n";
echo "Session data: " . json_encode($_SESSION) . "\n";
echo "\n";
echo "Cookies received: " . json_encode($_COOKIE) . "\n";
echo "\n";
echo "Data path: " . __DIR__ . "/../data/users.csv\n";
echo "Data exists: " . (file_exists(__DIR__ . '/../data/users.csv') ? 'YES' : 'NO') . "\n";
echo "\n";
$users_file = __DIR__ . '/../data/users.csv';
if (file_exists($users_file)) {
    $lines = file($users_file, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
    echo "Users CSV lines: " . count($lines) . "\n";
}
