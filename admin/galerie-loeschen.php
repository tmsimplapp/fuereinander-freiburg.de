<?php
require_once __DIR__ . '/auth.php';
require_once __DIR__ . '/../galerie-helpers.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: galerie.php');
    exit;
}

if (!hash_equals($_SESSION['csrf_token'] ?? '', $_POST['csrf_token'] ?? '')) {
    http_response_code(403);
    die('Ungültige Anfrage.');
}

$id = isset($_POST['id']) ? (int)$_POST['id'] : 0;

if ($id < 1) {
    $_SESSION['flash'] = ['type' => 'err', 'msg' => 'Ungültige Anfrage.'];
    header('Location: galerie.php');
    exit;
}

$db   = admin_db();
$stmt = $db->prepare('SELECT datei, thumb FROM galerie WHERE id = ?');
$stmt->execute([$id]);
$bild = $stmt->fetch();

if (!$bild) {
    $_SESSION['flash'] = ['type' => 'err', 'msg' => 'Foto nicht gefunden.'];
    header('Location: galerie.php');
    exit;
}

$db->prepare('DELETE FROM galerie WHERE id = ?')->execute([$id]);
galerie_dateien_loeschen($bild['datei'], $bild['thumb']);

$_SESSION['flash'] = ['type' => 'ok', 'msg' => 'Foto gelöscht.'];
header('Location: galerie.php');
exit;
