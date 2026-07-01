<?php
header('Content-Type: application/json; charset=utf-8');
header('Access-Control-Allow-Origin: https://fuereinander-freiburg.de');
header('Access-Control-Allow-Methods: GET, POST');
header('Cache-Control: no-store');

require_once __DIR__ . '/buchung-config.php';

try {
    $db = new PDO(
        'mysql:host=' . DB_HOST . ';dbname=' . DB_NAME . ';charset=utf8mb4',
        DB_USER, DB_PASS,
        [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]
    );

    $increment = isset($_POST['increment']) && $_POST['increment'] === '1';
    
    if ($increment) {
        $db->exec("UPDATE statistiken SET wert = wert + 1 WHERE name = 'seitenaufrufe'");
    }

    $stmt = $db->query("SELECT wert FROM statistiken WHERE name = 'seitenaufrufe'");
    $count = (int) $stmt->fetchColumn();

} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['error' => 'Database error']);
    exit;
}

echo json_encode(['count' => $count]);
