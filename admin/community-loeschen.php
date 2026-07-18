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

$id = isset($_POST['id']) ? (int)$_POST['id'] : 0;
if ($id < 1) {
    $_SESSION['flash'] = ['type' => 'err', 'msg' => 'Ungültige ID.'];
    header('Location: community.php');
    exit;
}

$db = admin_db();

// Delete linked data first (if ON DELETE CASCADE is not configured)
$db->prepare('DELETE FROM community_personen WHERE organisation_id = ?')->execute([$id]);
$db->prepare('DELETE FROM community_organisation_regionen WHERE organisation_id = ?')->execute([$id]);
$db->prepare('DELETE FROM community_organisation_tags WHERE organisation_id = ?')->execute([$id]);

// Delete the main organisation
$stmt = $db->prepare('DELETE FROM community_organisationen WHERE id = ?');
$stmt->execute([$id]);

if ($stmt->rowCount() > 0) {
    $_SESSION['flash'] = ['type' => 'deleted', 'msg' => 'Kontakt gelöscht.'];
} else {
    $_SESSION['flash'] = ['type' => 'err', 'msg' => 'Kontakt nicht gefunden.'];
}

header('Location: community.php');
exit;
