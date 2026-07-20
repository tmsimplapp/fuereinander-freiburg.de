<?php
require_once __DIR__ . '/auth.php';

$db = admin_db();

$flash = $_SESSION['flash'] ?? null;
unset($_SESSION['flash']);

if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}
$csrf = $_SESSION['csrf_token'];

$laender = ['Deutschland', 'Österreich', 'Schweiz'];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    if (!hash_equals($csrf, $_POST['csrf_token'] ?? '')) {
        http_response_code(403);
        die('Ungültige Anfrage.');
    }

    $action = $_POST['action'] ?? '';

    if ($action === 'add') {
        $name = trim($_POST['name'] ?? '');
        $land = in_array($_POST['land'] ?? '', $laender, true) ? $_POST['land'] : $laender[0];
        if ($name === '' || mb_strlen($name) > 80) {
            $_SESSION['flash'] = ['type' => 'err', 'msg' => 'Name muss 1–80 Zeichen lang sein.'];
        } else {
            try {
                $db->prepare('INSERT INTO community_regionen (name, land) VALUES (?, ?)')->execute([$name, $land]);
                $_SESSION['flash'] = ['type' => 'ok', 'msg' => 'Region angelegt.'];
            } catch (PDOException $e) {
                $_SESSION['flash'] = ['type' => 'err', 'msg' => 'Region existiert bereits.'];
            }
        }
    } elseif ($action === 'rename') {
        $id   = (int)($_POST['id'] ?? 0);
        $name = trim($_POST['name'] ?? '');
        $land = in_array($_POST['land'] ?? '', $laender, true) ? $_POST['land'] : $laender[0];
        if ($id < 1 || $name === '' || mb_strlen($name) > 80) {
            $_SESSION['flash'] = ['type' => 'err', 'msg' => 'Ungültige Eingabe.'];
        } else {
            try {
                $db->prepare('UPDATE community_regionen SET name = ?, land = ? WHERE id = ?')->execute([$name, $land, $id]);
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

$regionen = $db->query('SELECT id, name, land FROM community_regionen ORDER BY land ASC, name ASC')->fetchAll();
$regionen_nach_land = [];
foreach ($regionen as $r) {
    $regionen_nach_land[$r['land']][] = $r;
}
?>
<?php
$page_title = 'Admin – Regionen verwalten';
$active_nav = 'community-regionen';
require __DIR__ . '/header.php';
?>

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
  <div class="crm-panel-head" style="cursor:pointer;" onclick="const f=document.getElementById('addRegionForm'); const i=document.getElementById('addRegionIcon'); if(f.style.display==='none'){f.style.display='flex';i.style.transform='rotate(180deg)';}else{f.style.display='none';i.style.transform='rotate(0deg)';}">
    <span class="crm-icon" aria-hidden="true"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M20 10c0 6-8 12-8 12s-8-6-8-12a8 8 0 0 1 16 0Z"/><circle cx="12" cy="10" r="3"/></svg></span>
    <div style="flex:1;"><h2>Neue Region anlegen</h2></div>
    <span id="addRegionIcon" style="transition:transform 0.2s; transform:rotate(0deg); color:#6b7280;"><svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="m6 9 6 6 6-6"/></svg></span>
  </div>
  <form id="addRegionForm" method="post" class="inline-form" style="display:none;">
    <input type="hidden" name="csrf_token" value="<?= e($csrf) ?>">
    <input type="hidden" name="action" value="add">
    <input type="text" name="name" required maxlength="80" placeholder="z. B. Niederbayern…">
    <select name="land" class="region-land-select">
      <?php foreach ($laender as $l): ?>
        <option value="<?= e($l) ?>"><?= e($l) ?></option>
      <?php endforeach; ?>
    </select>
    <button type="submit" class="btn btn-primary">Hinzufügen</button>
  </form>
</section>

<?php if (empty($regionen)): ?>
  <p style="color:#666;font-size:.9rem">Noch keine Regionen vorhanden.</p>
<?php else: ?>
<?php foreach ($regionen_nach_land as $land => $regionen_land): ?>
  <div class="region-land-group">
    <h2 class="region-land-heading"><?= e($land) ?> <span class="region-land-count"><?= count($regionen_land) ?></span></h2>
    <div class="tag-grid">
      <?php foreach ($regionen_land as $r): ?>
        <div class="tag-card region-card">
          <!-- Ansicht -->
          <div class="tag-card-view" id="region_view_<?= (int)$r['id'] ?>">
            <div class="tag-card-head">
              <span class="region-card-icon" aria-hidden="true"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M20 10c0 6-8 12-8 12s-8-6-8-12a8 8 0 0 1 16 0Z"/><circle cx="12" cy="10" r="3"/></svg></span>
              <span class="tag-card-name"><?= e($r['name']) ?></span>
              <div class="tag-card-actions">
                <button type="button" class="icon-btn" title="Bearbeiten" aria-label="Region bearbeiten" onclick="toggleRegionEdit(<?= (int)$r['id'] ?>, true)">
                  <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M12 20h9"/><path d="M16.5 3.5a2.121 2.121 0 0 1 3 3L7 19l-4 1 1-4Z"/></svg>
                </button>
                <form method="post" class="tag-card-delete-form">
                  <input type="hidden" name="csrf_token" value="<?= e($csrf) ?>">
                  <input type="hidden" name="action" value="delete">
                  <input type="hidden" name="id" value="<?= (int)$r['id'] ?>">
                  <button type="button" class="icon-btn danger" title="Löschen" aria-label="Region löschen"
                          onclick="loeschenBestaetigen(this.closest('form'), <?= e(json_encode($r['name'])) ?>)">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M3 6h18"/><path d="M8 6V4a2 2 0 0 1 2-2h4a2 2 0 0 1 2 2v2m3 0-1 14a2 2 0 0 1-2 2H7a2 2 0 0 1-2-2L4 6"/></svg>
                  </button>
                </form>
              </div>
            </div>
          </div>

          <!-- Bearbeiten-Modus -->
          <form method="post" class="tag-card-edit" id="region_edit_<?= (int)$r['id'] ?>">
            <input type="hidden" name="csrf_token" value="<?= e($csrf) ?>">
            <input type="hidden" name="action" value="rename">
            <input type="hidden" name="id" value="<?= (int)$r['id'] ?>">
            <div>
              <label>Regionsname</label>
              <input type="text" name="name" value="<?= e($r['name']) ?>" maxlength="80" required>
            </div>
            <div>
              <label>Land</label>
              <select name="land">
                <?php foreach ($laender as $l): ?>
                  <option value="<?= e($l) ?>" <?= $l === $r['land'] ? 'selected' : '' ?>><?= e($l) ?></option>
                <?php endforeach; ?>
              </select>
            </div>
            <div class="tag-card-edit-actions">
              <button type="submit" class="btn btn-primary">Speichern</button>
              <button type="button" class="btn btn-secondary" onclick="toggleRegionEdit(<?= (int)$r['id'] ?>, false)">Abbrechen</button>
            </div>
          </form>
        </div>
      <?php endforeach; ?>
    </div>
  </div>
<?php endforeach; ?>
<?php endif; ?>

<?php ob_start(); ?>
<div class="modal-overlay" id="loeschModal">
  <div class="modal">
    <h2>Region löschen</h2>
    <p id="loeschModalText">Soll diese Region wirklich gelöscht werden?</p>
    <div class="modal-actions">
      <button type="button" class="btn btn-secondary" onclick="modalSchliessen()">Abbrechen</button>
      <button type="button" class="btn btn-danger" id="loeschBestaetigen">Ja, löschen</button>
    </div>
  </div>
</div>

<script>
function toggleRegionEdit(id, showEdit) {
  document.getElementById('region_view_' + id).style.display = showEdit ? 'none' : 'block';
  document.getElementById('region_edit_' + id).style.display = showEdit ? 'flex' : 'none';
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

<?php 
$extra_scripts = ob_get_clean();
require __DIR__ . '/footer.php'; 
?>
