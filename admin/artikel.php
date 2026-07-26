<?php
require_once __DIR__ . '/auth.php';

$db = admin_db();

$flash = $_SESSION['flash'] ?? null;
unset($_SESSION['flash']);

// Artikel abfragen
$stmt = $db->query(
    'SELECT id, slug, titel, kategorie, is_published, created_at, updated_at
     FROM artikel
     ORDER BY created_at DESC'
);
$artikel_liste = $stmt->fetchAll();

if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}
$csrf = $_SESSION['csrf_token'];

$page_title = 'Admin – Themenartikel';
$active_nav = 'artikel';
require __DIR__ . '/header.php';
?>

<div class="page-head">
  <div>
    <span class="page-eyebrow">Themen</span>
    <h1>Themenartikel verwalten</h1>
  </div>
  <div class="page-head-actions">
    <a href="artikel-editor.php" class="btn btn-primary add-link" style="margin-bottom:0">+ Neuer Artikel</a>
  </div>
</div>

<?php if ($flash): ?>
  <div class="alert <?= $flash['type'] === 'ok' ? 'alert-ok' : 'alert-err' ?>">
    <?= e($flash['msg']) ?>
  </div>
<?php endif; ?>

<?php if (empty($artikel_liste)): ?>
  <p class="leer-hinweis">Noch keine Themenartikel vorhanden. Klicke oben auf „+ Neuer Artikel“, um den ersten Beitrag zu verfassen.</p>
<?php else: ?>

<table class="termine-table">
  <thead>
    <tr>
      <th>Titel und Adresse</th>
      <th>Kategorie</th>
      <th>Status</th>
      <th>Erstellt am</th>
      <th>Aktionen</th>
    </tr>
  </thead>
  <tbody>
  <?php foreach ($artikel_liste as $art): ?>
    <tr class="<?= !$art['is_published'] ? 'inactive' : '' ?>">
      <td data-label="Titel">
        <strong><?= e($art['titel']) ?></strong>
        <div class="artikel-adresse">
          Adresse der Seite: /themen/<?= e($art['slug']) ?>
        </div>
      </td>
      <td data-label="Kategorie">
        <span style="display:inline-block;padding:.2rem .5rem;background:#f0f4f2;border-radius:4px;font-size:.85rem;">
          <?= e($art['kategorie']) ?>
        </span>
      </td>
      <td data-label="Status">
        <form method="post" action="artikel-toggle.php" style="margin:0">
          <input type="hidden" name="csrf_token" value="<?= e($csrf) ?>">
          <input type="hidden" name="id" value="<?= (int)$art['id'] ?>">
          <button type="submit" class="toggle-switch <?= $art['is_published'] ? 'active' : '' ?>" title="Klicken zum Umschalten">
            <span class="toggle-track"><span class="toggle-knob"></span></span>
            <span class="toggle-label"><?= $art['is_published'] ? 'Veröffentlicht' : 'Entwurf' ?></span>
          </button>
        </form>
      </td>
      <td data-label="Erstellt am">
        <?= date('d.m.Y H:i', strtotime($art['created_at'])) ?>
      </td>
      <td data-label="Aktionen">
        <div class="actions">
          <a href="../themen/<?= e($art['slug']) ?>" target="_blank" class="icon-btn" title="Vorschau im Frontend" aria-label="Artikel im Frontend ansehen (neuer Tab)">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"/><circle cx="12" cy="12" r="3"/></svg>
          </a>
          <a href="artikel-editor.php?id=<?= (int)$art['id'] ?>" class="icon-btn" title="Bearbeiten" aria-label="Artikel bearbeiten">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M12 20h9"/><path d="M16.5 3.5a2.12 2.12 0 0 1 3 3L7 19l-4 1 1-4Z"/></svg>
          </a>
          <form method="post" action="artikel-loeschen.php" class="loeschen-form" style="margin:0">
            <input type="hidden" name="csrf_token" value="<?= e($csrf) ?>">
            <input type="hidden" name="id" value="<?= (int)$art['id'] ?>">
            <button type="button" class="icon-btn danger" title="Löschen" aria-label="Artikel löschen"
                    onclick="loeschenBestaetigen(this.closest('form'), <?= e(json_encode($art['titel'])) ?>)">
              <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M3 6h18"/><path d="M8 6V4a2 2 0 0 1 2-2h4a2 2 0 0 1 2 2v2"/><path d="M19 6l-1 14a2 2 0 0 1-2 2H8a2 2 0 0 1-2-2L5 6"/></svg>
            </button>
          </form>
        </div>
      </td>
    </tr>
  <?php endforeach; ?>
  </tbody>
</table>

<?php endif; ?>

<!-- Lösch-Modal -->
<div class="modal-overlay" id="loeschModal">
  <div class="modal">
    <h2>Artikel löschen</h2>
    <p id="loeschModalText">Soll dieser Artikel wirklich gelöscht werden?</p>
    <div class="modal-actions">
      <button class="btn btn-secondary" onclick="modalSchliessen()">Abbrechen</button>
      <button class="btn btn-danger" id="loeschBestaetigen">Ja, löschen</button>
    </div>
  </div>
</div>

<script>
let pendingForm = null;

function loeschenBestaetigen(form, titel) {
  pendingForm = form;
  document.getElementById('loeschModalText').textContent = 'Soll der Artikel „' + titel + '" wirklich gelöscht werden?';
  document.getElementById('loeschModal').classList.add('active');
}

function modalSchliessen() {
  pendingForm = null;
  document.getElementById('loeschModal').classList.remove('active');
}

document.getElementById('loeschBestaetigen').addEventListener('click', function() {
  if (pendingForm) pendingForm.submit();
});

// Klick auf Hintergrund, Escape und Fokus-Handling: siehe modal.js
document.getElementById('loeschModal').addEventListener('modal:geschlossen', function() {
  pendingForm = null;
});
</script>

<?php require __DIR__ . '/footer.php'; ?>
