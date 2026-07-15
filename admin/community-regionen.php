<?php
require_once __DIR__ . '/auth.php';

$db = admin_db();

$flash = $_SESSION['flash'] ?? null;
unset($_SESSION['flash']);

if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}
$csrf = $_SESSION['csrf_token'];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    if (!hash_equals($csrf, $_POST['csrf_token'] ?? '')) {
        http_response_code(403);
        die('Ungültige Anfrage.');
    }

    $action = $_POST['action'] ?? '';

    if ($action === 'add') {
        $name = trim($_POST['name'] ?? '');
        if ($name === '' || mb_strlen($name) > 80) {
            $_SESSION['flash'] = ['type' => 'err', 'msg' => 'Name muss 1–80 Zeichen lang sein.'];
        } else {
            try {
                $db->prepare('INSERT INTO community_regionen (name) VALUES (?)')->execute([$name]);
                $_SESSION['flash'] = ['type' => 'ok', 'msg' => 'Region angelegt.'];
            } catch (PDOException $e) {
                $_SESSION['flash'] = ['type' => 'err', 'msg' => 'Region existiert bereits.'];
            }
        }
    } elseif ($action === 'rename') {
        $id   = (int)($_POST['id'] ?? 0);
        $name = trim($_POST['name'] ?? '');
        if ($id < 1 || $name === '' || mb_strlen($name) > 80) {
            $_SESSION['flash'] = ['type' => 'err', 'msg' => 'Ungültige Eingabe.'];
        } else {
            try {
                $db->prepare('UPDATE community_regionen SET name = ? WHERE id = ?')->execute([$name, $id]);
                $_SESSION['flash'] = ['type' => 'ok', 'msg' => 'Region umbenannt.'];
            } catch (PDOException $e) {
                $_SESSION['flash'] = ['type' => 'err', 'msg' => 'Region existiert bereits.'];
            }
        }
    } elseif ($action === 'delete') {
        $id = (int)($_POST['id'] ?? 0);
        if ($id > 0) {
            $db->prepare('DELETE FROM community_regionen WHERE id = ?')->execute([$id]);
            $_SESSION['flash'] = ['type' => 'ok', 'msg' => 'Region gelöscht.'];
        }
    }

    header('Location: community-regionen.php');
    exit;
}

$regionen = $db->query('SELECT id, name FROM community_regionen ORDER BY name ASC')->fetchAll();
?>
<!DOCTYPE html>
<html lang="de">
<head>
<link rel="icon" href="../grafik/F%C3%BCreinander%20Freiburg.svg" type="image/svg+xml">
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title>Admin – Regionen verwalten</title>
<meta name="robots" content="noindex,nofollow">
<link rel="stylesheet" href="admin.css">
</head>
<body>
<div class="admin-layout">
<?php $active_nav = 'community-regionen'; require __DIR__ . '/nav.php'; ?>
<div class="admin-main">

<div class="page-head">
  <div>
    <span class="page-eyebrow">Community verwalten</span>
    <h1>Regionen</h1>
  </div>
</div>

<?php if ($flash): ?>
  <div class="alert <?= $flash['type'] === 'ok' ? 'alert-ok' : 'alert-err' ?>">
    <?= e($flash['msg']) ?>
  </div>
<?php endif; ?>

<section class="crm-panel is-narrow" style="margin-bottom:1.5rem">
  <div class="crm-panel-head">
    <span class="crm-icon" aria-hidden="true">📍</span>
    <div><h2>Neue Region anlegen</h2></div>
  </div>
  <form method="post" class="inline-form">
    <input type="hidden" name="csrf_token" value="<?= e($csrf) ?>">
    <input type="hidden" name="action" value="add">
    <input type="text" name="name" required maxlength="80" placeholder="z. B. Niederbayern">
    <button type="submit" class="btn btn-primary">Hinzufügen</button>
  </form>
</section>

<?php if (empty($regionen)): ?>
  <p style="color:#666;font-size:.9rem">Noch keine Regionen vorhanden.</p>
<?php else: ?>
<table class="termine-table" style="max-width:560px">
  <thead><tr><th>Name</th><th>Aktionen</th></tr></thead>
  <tbody>
  <?php foreach ($regionen as $r): ?>
    <tr>
      <td data-label="Name">
        <form method="post" class="inline-form">
          <input type="hidden" name="csrf_token" value="<?= e($csrf) ?>">
          <input type="hidden" name="action" value="rename">
          <input type="hidden" name="id" value="<?= (int)$r['id'] ?>">
          <input type="text" name="name" value="<?= e($r['name']) ?>" maxlength="80" required>
          <button type="submit" class="btn btn-edit">Speichern</button>
        </form>
      </td>
      <td data-label="Aktionen">
        <form method="post" onsubmit="return confirm('Region „' + <?= json_encode($r['name'], JSON_HEX_APOS | JSON_HEX_QUOT) ?> + '“ wirklich löschen?')">
          <input type="hidden" name="csrf_token" value="<?= e($csrf) ?>">
          <input type="hidden" name="action" value="delete">
          <input type="hidden" name="id" value="<?= (int)$r['id'] ?>">
          <button type="submit" class="btn btn-danger">Löschen</button>
        </form>
      </td>
    </tr>
  <?php endforeach; ?>
  </tbody>
</table>
<?php endif; ?>

</div>
</div>
</body>
</html>
