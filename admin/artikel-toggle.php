<?php
require_once __DIR__ . '/auth.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: artikel.php');
    exit;
}

if (!hash_equals($_SESSION['csrf_token'] ?? '', $_POST['csrf_token'] ?? '')) {
    http_response_code(403);
    die('Ungültige Anfrage.');
}

$id = isset($_POST['id']) ? (int)$_POST['id'] : 0;

if ($id < 1) {
    $_SESSION['flash'] = ['type' => 'err', 'msg' => 'Ungültige Anfrage.'];
    header('Location: artikel.php');
    exit;
}

$db = admin_db();
$stmt = $db->prepare("UPDATE artikel SET is_published = 1 - is_published WHERE id = ?");
$stmt->execute([$id]);

$_SESSION['flash'] = ['type' => 'ok', 'msg' => 'Status erfolgreich geändert.'];
header('Location: artikel.php');
exit;
