<?php
session_name('CVBETA');
session_start();
header('Content-Type: text/plain');

echo "Session name: " . session_name() . "\n";
echo "Session ID: " . session_id() . "\n";
echo "Session user: " . ($_SESSION['user'] ?? 'NOT SET') . "\n";
echo "Session data: " . json_encode($_SESSION) . "\n";
echo "\n";

// Test data path
$data = __DIR__ . '/../data/users.csv';
echo "Data path: $data\n";
echo "Data exists: " . (file_exists($data) ? 'YES' : 'NO') . "\n";
echo "\n";

// Test API call simulation
$users_file = __DIR__ . '/../data/users.csv';
if (file_exists($users_file)) {
    $lines = file($users_file, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
    echo "Users CSV lines: " . count($lines) . "\n";
    echo "First line: " . $lines[0] . "\n";
} else {
    echo "CANNOT READ users.csv\n";
}
