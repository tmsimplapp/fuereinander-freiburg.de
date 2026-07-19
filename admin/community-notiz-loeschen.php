<?php
require_once __DIR__ . '/auth.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: community.php');
    exit;
}

if (!hash_equals($_SESSION['csrf_token'] ?? '', $_POST['csrf_token'] ?? '')) {
    http_response_code(403);
    die('Ungültige Anfrage.');
}

$id                = isset($_POST['id']) ? (int)$_POST['id'] : 0;
$organisation_id   = isset($_POST['organisation_id']) ? (int)$_POST['organisation_id'] : 0;

if ($id < 1 || $organisation_id < 1) {
    $_SESSION['flash'] = ['type' => 'err', 'msg' => 'Ungültige ID.'];
    header('Location: community.php');
    exit;
}

$db = admin_db();

$stmt = $db->prepare('DELETE FROM community_notizen WHERE id = ? AND organisation_id = ?');
$stmt->execute([$id, $organisation_id]);

if ($stmt->rowCount() > 0) {
    $_SESSION['flash'] = ['type' => 'deleted', 'msg' => 'Notiz gelöscht.'];
} else {
    $_SESSION['flash'] = ['type' => 'err', 'msg' => 'Notiz nicht gefunden.'];
}

header('Location: community-bearbeiten.php?id=' . $organisation_id);
exit;
