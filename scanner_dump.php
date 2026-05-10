<?php
header('Access-Control-Allow-Origin: *');
header('Content-Type: application/json');
echo json_encode(['content' => file_get_contents(__DIR__ . '/scanner.php')]);
