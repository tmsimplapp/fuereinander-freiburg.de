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
                $db->prepare('INSERT INTO community_tags (name) VALUES (?)')->execute([$name]);
                $_SESSION['flash'] = ['type' => 'ok', 'msg' => 'Tag angelegt.'];
            } catch (PDOException $e) {
                $_SESSION['flash'] = ['type' => 'err', 'msg' => 'Tag existiert bereits.'];
            }
        }
    } elseif ($action === 'rename') {
        $id   = (int)($_POST['id'] ?? 0);
        $name = trim($_POST['name'] ?? '');
        if ($id < 1 || $name === '' || mb_strlen($name) > 80) {
            $_SESSION['flash'] = ['type' => 'err', 'msg' => 'Ungültige Eingabe.'];
        } else {
            try {
                $db->prepare('UPDATE community_tags SET name = ? WHERE id = ?')->execute([$name, $id]);
                $_SESSION['flash'] = ['type' => 'ok', 'msg' => 'Tag umbenannt.'];
            } catch (PDOException $e) {
                $_SESSION['flash'] = ['type' => 'err', 'msg' => 'Tag existiert bereits.'];
            }
        }
    } elseif ($action === 'delete') {
        $id = (int)($_POST['id'] ?? 0);
        if ($id > 0) {
            $db->prepare('DELETE FROM community_tags WHERE id = ?')->execute([$id]);
            $_SESSION['flash'] = ['type' => 'ok', 'msg' => 'Tag gelöscht.'];
        }
    }

    header('Location: community-tags.php');
    exit;
}

$tags = $db->query('SELECT id, name FROM community_tags ORDER BY name ASC')->fetchAll();
?>
<!DOCTYPE html>
<html lang="de">
<head>
<link rel="icon" href="../grafik/F%C3%BCreinander%20Freiburg.svg" type="image/svg+xml">
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title>Admin – Tags verwalten</title>
<meta name="robots" content="noindex,nofollow">
<link rel="stylesheet" href="admin.css">
</head>
<body>
<div class="admin-layout">
<?php $active_nav = 'community-tags'; require __DIR__ . '/nav.php'; ?>
<div class="admin-main">

<div class="page-head">
  <div>
    <span class="page-eyebrow">Community verwalten</span>
    <h1>Tags</h1>
  </div>
</div>

<?php if ($flash): ?>
  <div class="alert <?= $flash['type'] === 'ok' ? 'alert-ok' : 'alert-err' ?>">
    <?= e($flash['msg']) ?>
  </div>
<?php endif; ?>

<section class="crm-panel is-narrow" style="margin-bottom:1.5rem">
  <div class="crm-panel-head">
    <span class="crm-icon" aria-hidden="true"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M12.586 2.586A2 2 0 0 0 11.172 2H4a2 2 0 0 0-2 2v7.172a2 2 0 0 0 .586 1.414l8.704 8.704a2.426 2.426 0 0 0 3.42 0l6.58-6.58a2.426 2.426 0 0 0 0-3.42z"/><circle cx="7.5" cy="7.5" r="1.5"/></svg></span>
    <div><h2>Neuen Tag anlegen</h2></div>
  </div>
  <form method="post" class="inline-form">
    <input type="hidden" name="csrf_token" value="<?= e($csrf) ?>">
    <input type="hidden" name="action" value="add">
    <input type="text" name="name" required maxlength="80" placeholder="z. B. ohne theologischen Hintergrund…">
    <button type="submit" class="btn btn-primary">Hinzufügen</button>
  </form>
</section>

<?php if (empty($tags)): ?>
  <p style="color:#666;font-size:.9rem">Noch keine Tags vorhanden.</p>
<?php else: ?>
<table class="termine-table" style="max-width:560px">
  <thead><tr><th>Name</th><th>Aktionen</th></tr></thead>
  <tbody>
  <?php foreach ($tags as $t): ?>
    <tr>
      <td data-label="Name">
        <form method="post" class="inline-form">
          <input type="hidden" name="csrf_token" value="<?= e($csrf) ?>">
          <input type="hidden" name="action" value="rename">
          <input type="hidden" name="id" value="<?= (int)$t['id'] ?>">
          <input type="text" name="name" value="<?= e($t['name']) ?>" maxlength="80" required>
          <button type="submit" class="btn btn-edit">Speichern</button>
        </form>
      </td>
      <td data-label="Aktionen">
        <form method="post" class="loeschen-form">
          <input type="hidden" name="csrf_token" value="<?= e($csrf) ?>">
          <input type="hidden" name="action" value="delete">
          <input type="hidden" name="id" value="<?= (int)$t['id'] ?>">
          <button type="button" class="btn btn-danger"
                  onclick="loeschenBestaetigen(this.closest('form'), <?= e(json_encode($t['name'])) ?>)">Löschen</button>
        </form>
      </td>
    </tr>
  <?php endforeach; ?>
  </tbody>
</table>
<?php endif; ?>

<div class="modal-overlay" id="loeschModal">
  <div class="modal">
    <h2>Tag löschen</h2>
    <p id="loeschModalText">Soll dieser Tag wirklich gelöscht werden?</p>
    <div class="modal-actions">
      <button type="button" class="btn btn-secondary" onclick="modalSchliessen()">Abbrechen</button>
      <button type="button" class="btn btn-danger" id="loeschBestaetigen">Ja, löschen</button>
    </div>
  </div>
</div>

<script>
let pendingForm = null;
function loeschenBestaetigen(form, name) {
  pendingForm = form;
  document.getElementById('loeschModalText').textContent = 'Soll „' + name + '" wirklich gelöscht werden?';
  document.getElementById('loeschModal').classList.add('active');
}
function modalSchliessen() {
  pendingForm = null;
  document.getElementById('loeschModal').classList.remove('active');
}
document.getElementById('loeschBestaetigen').addEventListener('click', function() {
  if (pendingForm) pendingForm.submit();
});
document.getElementById('loeschModal').addEventListener('click', function(e) {
  if (e.target === this) modalSchliessen();
});
document.addEventListener('keydown', function(e) {
  if (e.key === 'Escape') modalSchliessen();
});
</script>

</div>
</div>
</body>
</html>
