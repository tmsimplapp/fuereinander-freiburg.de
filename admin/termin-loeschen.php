<?php
require_once __DIR__ . '/auth.php';

// Nur POST erlaubt
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: termine.php');
    exit;
}

// CSRF prüfen
if (!hash_equals($_SESSION['csrf_token'] ?? '', $_POST['csrf_token'] ?? '')) {
    http_response_code(403);
    die('Ungültige Anfrage.');
}

$id = isset($_POST['id']) ? (int)$_POST['id'] : 0;
if ($id < 1) {
    $_SESSION['flash'] = ['type' => 'err', 'msg' => 'Ungültige ID.'];
    header('Location: termine.php');
    exit;
}

$db   = admin_db();
$stmt = $db->prepare('DELETE FROM slot_konfiguration WHERE id = ?');
$stmt->execute([$id]);

if ($stmt->rowCount() > 0) {
    $_SESSION['flash'] = ['type' => 'ok', 'msg' => 'Termin gelöscht.'];
} else {
    $_SESSION['flash'] = ['type' => 'err', 'msg' => 'Termin nicht gefunden.'];
}

header('Location: termine.php');
exit;
