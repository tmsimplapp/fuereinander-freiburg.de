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
        $beschreibung = trim($_POST['beschreibung'] ?? '');
        $farbe = trim($_POST['farbe'] ?? '');
        if (!preg_match('/^#[0-9a-fA-F]{6}$/', $farbe)) {
            $farbe = null;
        }
        if ($name === '' || mb_strlen($name) > 80) {
            $_SESSION['flash'] = ['type' => 'err', 'msg' => 'Name muss 1–80 Zeichen lang sein.'];
        } else {
            try {
                $db->prepare('INSERT INTO community_tags (name, beschreibung, farbe) VALUES (?, ?, ?)')->execute([$name, $beschreibung === '' ? null : $beschreibung, $farbe]);
                $_SESSION['flash'] = ['type' => 'ok', 'msg' => 'Tag angelegt.'];
            } catch (PDOException $e) {
                $_SESSION['flash'] = ['type' => 'err', 'msg' => 'Tag existiert bereits.'];
            }
        }
    } elseif ($action === 'rename') {
        $id   = (int)($_POST['id'] ?? 0);
        $name = trim($_POST['name'] ?? '');
        $beschreibung = trim($_POST['beschreibung'] ?? '');
        $farbe = trim($_POST['farbe'] ?? '');
        if (!preg_match('/^#[0-9a-fA-F]{6}$/', $farbe)) {
            $farbe = null;
        }
        if ($id < 1 || $name === '' || mb_strlen($name) > 80) {
            $_SESSION['flash'] = ['type' => 'err', 'msg' => 'Ungültige Eingabe.'];
        } else {
            try {
                $db->prepare('UPDATE community_tags SET name = ?, beschreibung = ?, farbe = ? WHERE id = ?')->execute([$name, $beschreibung === '' ? null : $beschreibung, $farbe, $id]);
                $_SESSION['flash'] = ['type' => 'ok', 'msg' => 'Tag aktualisiert.'];
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

$tags = $db->query('SELECT id, name, beschreibung, farbe FROM community_tags ORDER BY name ASC')->fetchAll();
$default_farbe = '#e5e7eb';
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
  <div class="crm-panel-head" style="cursor:pointer;" onclick="const f=document.getElementById('addTagForm'); const i=document.getElementById('addTagIcon'); if(f.style.display==='none'){f.style.display='flex';i.style.transform='rotate(180deg)';}else{f.style.display='none';i.style.transform='rotate(0deg)';}">
    <span class="crm-icon" aria-hidden="true"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M12.586 2.586A2 2 0 0 0 11.172 2H4a2 2 0 0 0-2 2v7.172a2 2 0 0 0 .586 1.414l8.704 8.704a2.426 2.426 0 0 0 3.42 0l6.58-6.58a2.426 2.426 0 0 0 0-3.42z"/><circle cx="7.5" cy="7.5" r="1.5"/></svg></span>
    <div style="flex:1;"><h2>Neuen Tag anlegen</h2></div>
    <span id="addTagIcon" style="transition:transform 0.2s; transform:rotate(0deg); color:#6b7280;"><svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="m6 9 6 6 6-6"/></svg></span>
  </div>
  <form id="addTagForm" method="post" style="display:none;flex-direction:column;gap:0.75rem;max-width:560px;margin-top:1rem;">
    <input type="hidden" name="csrf_token" value="<?= e($csrf) ?>">
    <input type="hidden" name="action" value="add">
    <div style="display:flex;flex-direction:column;gap:0.75rem;">
      <div>
        <label style="font-size:0.85rem;font-weight:500;color:#374151;margin-bottom:0.25rem;display:block">Tag-Name</label>
        <input type="text" name="name" required maxlength="80" placeholder="z. B. ohne theologischen Hintergrund…">
      </div>
      <div>
        <label style="font-size:0.85rem;font-weight:500;color:#374151;margin-bottom:0.25rem;display:block">Farbe</label>
        <input type="color" name="farbe" value="<?= e($default_farbe) ?>" style="min-height:44px;width:70px;padding:.25rem;cursor:pointer">
      </div>
      <div>
        <label style="font-size:0.85rem;font-weight:500;color:#374151;margin-bottom:0.25rem;display:block">Beschreibung (optional)</label>
        <textarea name="beschreibung" rows="3" placeholder="Kurzbeschreibung der Bedeutung…"></textarea>
      </div>
    </div>
    <div>
      <button type="submit" class="btn btn-primary">Hinzufügen</button>
    </div>
  </form>
</section>

<?php if (empty($tags)): ?>
  <p style="color:#666;font-size:.9rem">Noch keine Tags vorhanden.</p>
<?php else: ?>
<div class="tag-grid">
  <?php foreach ($tags as $t): ?>
    <div class="tag-card">
      <!-- Ansicht -->
      <div class="tag-card-view" id="tag_view_<?= (int)$t['id'] ?>">
        <div class="tag-card-head">
          <span class="tag-card-dot" style="background:<?= e($t['farbe'] ?: $default_farbe) ?>"></span>
          <span class="tag-card-name"><?= e($t['name']) ?></span>
          <div class="tag-card-actions" id="tag_actions_<?= (int)$t['id'] ?>">
            <button type="button" class="icon-btn" title="Bearbeiten" aria-label="Tag bearbeiten" onclick="toggleTagEdit(<?= (int)$t['id'] ?>, true)">
              <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M12 20h9"/><path d="M16.5 3.5a2.121 2.121 0 0 1 3 3L7 19l-4 1 1-4Z"/></svg>
            </button>
            <form method="post" class="tag-card-delete-form">
              <input type="hidden" name="csrf_token" value="<?= e($csrf) ?>">
              <input type="hidden" name="action" value="delete">
              <input type="hidden" name="id" value="<?= (int)$t['id'] ?>">
              <button type="button" class="icon-btn danger" title="Löschen" aria-label="Tag löschen"
                      onclick="loeschenBestaetigen(this.closest('form'), <?= e(json_encode($t['name'])) ?>)">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M3 6h18"/><path d="M8 6V4a2 2 0 0 1 2-2h4a2 2 0 0 1 2 2v2m3 0-1 14a2 2 0 0 1-2 2H7a2 2 0 0 1-2-2L4 6"/></svg>
              </button>
            </form>
          </div>
        </div>
        <?php if (!empty($t['beschreibung'])): ?>
          <p class="tag-card-desc"><?= e($t['beschreibung']) ?></p>
        <?php endif; ?>
      </div>

      <!-- Bearbeiten-Modus -->
      <form method="post" class="tag-card-edit" id="tag_edit_<?= (int)$t['id'] ?>">
        <input type="hidden" name="csrf_token" value="<?= e($csrf) ?>">
        <input type="hidden" name="action" value="rename">
        <input type="hidden" name="id" value="<?= (int)$t['id'] ?>">
        <div class="tag-card-edit-row">
          <div>
            <label>Farbe</label>
            <input type="color" name="farbe" value="<?= e($t['farbe'] ?: $default_farbe) ?>" style="min-height:44px;width:70px;padding:.25rem;cursor:pointer">
          </div>
          <div style="flex:1">
            <label>Tag-Name</label>
            <input type="text" name="name" value="<?= e($t['name']) ?>" maxlength="80" required>
          </div>
        </div>
        <div>
          <label>Beschreibung</label>
          <textarea name="beschreibung" rows="3" placeholder="Kurzbeschreibung (optional)"><?= e($t['beschreibung'] ?? '') ?></textarea>
        </div>
        <div class="tag-card-edit-actions">
          <button type="submit" class="btn btn-primary">Speichern</button>
          <button type="button" class="btn btn-secondary" onclick="toggleTagEdit(<?= (int)$t['id'] ?>, false)">Abbrechen</button>
        </div>
      </form>
    </div>
  <?php endforeach; ?>
</div>
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
function toggleTagEdit(id, showEdit) {
  document.getElementById('tag_view_' + id).style.display = showEdit ? 'none' : 'block';
  document.getElementById('tag_edit_' + id).style.display = showEdit ? 'flex' : 'none';
}

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
