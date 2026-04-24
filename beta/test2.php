<?php
session_name('CVBETA');
ini_set('session.cookie_path', '/beta/');
ini_set('session.cookie_samesite', 'Lax');
ini_set('session.cookie_secure', '1');
ini_set('session.cookie_httponly', '1');
session_start();
header('Content-Type: text/plain');

echo "User: " . ($_SESSION['user'] ?? 'NOT SET') . "\n\n";

// Test the exact API call that scanner makes
$data_dir = __DIR__ . '/../data/';
$collection_file = $data_dir . 'collection.csv';

echo "Collection file: $collection_file\n";
echo "Collection exists: " . (file_exists($collection_file) ? 'YES' : 'NO') . "\n";

if (file_exists($collection_file)) {
    $lines = file($collection_file, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
    echo "Collection rows: " . count($lines) . "\n";
}

// Test categories.js.php include
echo "\ncategories.js.php exists: " . (file_exists(__DIR__ . '/categories.js.php') ? 'YES' : 'NO') . "\n";
echo "nav.php exists: " . (file_exists(__DIR__ . '/nav.php') ? 'YES' : 'NO') . "\n";
echo "theme.php exists: " . (file_exists(__DIR__ . '/theme.php') ? 'YES' : 'NO') . "\n";
echo "shared.css exists: " . (file_exists(__DIR__ . '/shared.css') ? 'YES' : 'NO') . "\n";
