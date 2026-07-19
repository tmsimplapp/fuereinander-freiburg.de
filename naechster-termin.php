<?php
require_once __DIR__ . '/buchung-config.php';
header('Content-Type: application/json; charset=utf-8');

$pdo = new PDO(
    'mysql:host=' . DB_HOST . ';dbname=' . DB_NAME . ';charset=utf8mb4',
    DB_USER, DB_PASS,
    [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION, PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC]
);

$stmt = $pdo->query(
    "SELECT termin_datum, uhrzeiten, slot_laenge_min
     FROM gruppentermine
     WHERE aktiv = 1 AND termin_datum >= CURDATE()
     ORDER BY termin_datum ASC
     LIMIT 1"
);
$termin = $stmt->fetch();

if (!$termin) {
    echo json_encode(['found' => false]);
    exit;
}

$wochentage = ['Montag','Dienstag','Mittwoch','Donnerstag','Freitag','Samstag','Sonntag'];
$monate     = ['Januar','Februar','März','April','Mai','Juni','Juli','August','September','Oktober','November','Dezember'];

$ts  = strtotime($termin['termin_datum']);
$dow = (int)date('N', $ts) - 1;
$mon = (int)date('n', $ts) - 1;
$datum = $wochentage[$dow] . ', ' . (int)date('j', $ts) . '. ' . $monate[$mon] . ' ' . date('Y', $ts);

$uhrzeiten = json_decode($termin['uhrzeiten'], true);
$zeitraum = '';
if (!empty($uhrzeiten)) {
    $start = $uhrzeiten[0];
    [$h, $m] = explode(':', $start);
    $end_min = (int)$h * 60 + (int)$m + (int)$termin['slot_laenge_min'];
    $zeitraum = $start . ' bis ' . sprintf('%02d:%02d', intdiv($end_min, 60), $end_min % 60) . ' Uhr';
}

echo json_encode(['found' => true, 'datum' => $datum, 'zeitraum' => $zeitraum]);
