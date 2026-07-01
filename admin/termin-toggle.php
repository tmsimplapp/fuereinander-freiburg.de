<?php
require_once __DIR__ . '/auth.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: termine.php');
    exit;
}

if (!hash_equals($_SESSION['csrf_token'] ?? '', $_POST['csrf_token'] ?? '')) {
    http_response_code(403);
    die('Ungültige Anfrage.');
}

$id = isset($_POST['id']) ? (int)$_POST['id'] : 0;
$field = $_POST['field'] ?? 'aktiv';

if ($id < 1 || !in_array($field, ['aktiv', 'ausgebucht'], true)) {
    $_SESSION['flash'] = ['type' => 'err', 'msg' => 'Ungültige Anfrage.'];
    header('Location: termine.php');
    exit;
}

$db   = admin_db();
$stmt = $db->prepare("UPDATE slot_konfiguration SET $field = 1 - $field WHERE id = ?");
$stmt->execute([$id]);

header('Location: termine.php');
exit;
