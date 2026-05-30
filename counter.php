<?php
header('Content-Type: application/json; charset=utf-8');
header('Access-Control-Allow-Origin: https://fuereinander-freiburg.de');
header('Access-Control-Allow-Methods: GET, POST');
header('Cache-Control: no-store');

$file = __DIR__ . '/counter.txt';

$increment = isset($_POST['increment']) && $_POST['increment'] === '1';

if ($increment) {
    $fp = fopen($file, 'c+');
    if (!$fp) {
        http_response_code(500);
        echo json_encode(['error' => 'Cannot open counter file']);
        exit;
    }
    flock($fp, LOCK_EX);
    $count = (int) fread($fp, 20);
    $count++;
    ftruncate($fp, 0);
    rewind($fp);
    fwrite($fp, (string) $count);
    flock($fp, LOCK_UN);
    fclose($fp);
} else {
    $count = file_exists($file) ? (int) file_get_contents($file) : 0;
}

echo json_encode(['count' => $count]);
