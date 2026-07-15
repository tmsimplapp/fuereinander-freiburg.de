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
    $_SESSION['flash'] = ['type' => 'err', 'msg' => 'Ungültige Anfrage.'];
    header('Location: community.php');
    exit;
}

$db = admin_db();
$stmt = $db->prepare('UPDATE community_organisationen SET aktiv = 1 - aktiv WHERE id = ?');
$stmt->execute([$id]);

header('Location: community.php');
exit;
