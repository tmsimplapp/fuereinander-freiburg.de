<?php
require_once __DIR__ . '/auth.php';
require_once __DIR__ . '/../galerie-helpers.php';

$db = admin_db();

if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}
$csrf = $_SESSION['csrf_token'];

// ── Upload (mehrere Dateien) ──
if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['aktion'] ?? '') === 'upload') {
    if (!hash_equals($csrf, $_POST['csrf_token'] ?? '')) {
        http_response_code(403);
        die('Ungültige Anfrage.');
    }

    $dateien  = $_FILES['bilder'] ?? null;
    $erfolge  = 0;
    $fehler   = [];

    if ($dateien && is_array($dateien['name'])) {
        $max_sort = (int)$db->query('SELECT COALESCE(MAX(sortierung), 0) FROM galerie')->fetchColumn();
        $ins = $db->prepare(
            'INSERT INTO galerie (datei, thumb, titel, alt_text, breite, hoehe, sortierung)
             VALUES (?, ?, ?, ?, ?, ?, ?)'
        );

        foreach (array_keys($dateien['name']) as $i) {
            if ($dateien['error'][$i] === UPLOAD_ERR_NO_FILE) continue;

            $einzeln = [
                'name'     => $dateien['name'][$i],
                'tmp_name' => $dateien['tmp_name'][$i],
                'error'    => $dateien['error'][$i],
                'size'     => $dateien['size'][$i],
            ];
            $res = galerie_upload_verarbeiten($einzeln);
            if (is_string($res)) {
                $fehler[] = $dateien['name'][$i] . ': ' . $res;
                continue;
            }

            $max_sort += 10;
            $ins->execute([
                $res['datei'], $res['thumb'],
                '', 'Foto von einem Treffen der Selbsthilfegruppe',
                $res['breite'], $res['hoehe'], $max_sort,
            ]);
            $erfolge++;
        }
    }

    if ($erfolge > 0 && !$fehler) {
        $_SESSION['flash'] = ['type' => 'ok', 'msg' => $erfolge . ' Bild(er) hochgeladen. Ergänze jetzt Titel und Bildbeschreibung.'];
    } elseif ($erfolge > 0) {
        $_SESSION['flash'] = ['type' => 'err', 'msg' => $erfolge . ' Bild(er) hochgeladen, aber: ' . implode(' | ', $fehler)];
    } else {
        $_SESSION['flash'] = ['type' => 'err', 'msg' => $fehler ? implode(' | ', $fehler) : 'Keine Datei ausgewählt.'];
    }
    header('Location: galerie.php');
    exit;
}

// ── Texte + Reihenfolge speichern ──
if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['aktion'] ?? '') === 'speichern') {
    if (!hash_equals($csrf, $_POST['csrf_token'] ?? '')) {
        http_response_code(403);
        die('Ungültige Anfrage.');
    }

    $upd = $db->prepare('UPDATE galerie SET titel = ?, alt_text = ?, sortierung = ? WHERE id = ?');
    foreach (($_POST['titel'] ?? []) as $id => $titel) {
        $id = (int)$id;
        if ($id < 1) continue;
        $upd->execute([
            mb_substr(trim($titel), 0, 200),
            mb_substr(trim($_POST['alt_text'][$id] ?? ''), 0, 255),
            (int)($_POST['sortierung'][$id] ?? 0),
            $id,
        ]);
    }

    $_SESSION['flash'] = ['type' => 'ok', 'msg' => 'Änderungen gespeichert.'];
    header('Location: galerie.php');
    exit;
}

$flash = $_SESSION['flash'] ?? null;
unset($_SESSION['flash']);

$bilder = $db->query(
    'SELECT id, datei, thumb, titel, alt_text, sortierung, aktiv, erstellt
     FROM galerie ORDER BY sortierung ASC, id ASC'
)->fetchAll();

$page_title = 'Admin – Galerie';
$active_nav = 'galerie';
require __DIR__ . '/header.php';
?>

<div class="page-head">
  <div>
    <span class="page-eyebrow">Galerie</span>
    <h1>Bildergalerie</h1>
  </div>
</div>

<?php if ($flash): ?>
  <div class="alert <?= $flash['type'] === 'ok' ? 'alert-ok' : 'alert-err' ?>">
    <?= e($flash['msg']) ?>
  </div>
<?php endif; ?>

<div class="alert alert-err" style="background:#fff4d6;border-color:#E2C2A2;color:#5c4e3a">
  <strong>Bitte beachten:</strong> Lade nur Fotos hoch, für die eine Einwilligung aller abgebildeten Personen vorliegt.
  Bei einer Selbsthilfegruppe sind erkennbare Gesichter besonders heikel – bevorzuge Detail-, Raum- oder Rückenaufnahmen.
</div>

<form method="post" enctype="multipart/form-data" class="card" style="margin-bottom:1.5rem">
  <input type="hidden" name="csrf_token" value="<?= e($csrf) ?>">
  <input type="hidden" name="aktion" value="upload">
  <strong>Neue Fotos hochladen</strong>

  <label class="upload-dropzone" for="bilder" id="dropzone">
    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
      <path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"/><path d="M17 8l-5-5-5 5"/><path d="M12 3v12"/>
    </svg>
    <span class="upload-dropzone-titel">Fotos auswählen oder hierher ziehen</span>
    <span class="upload-dropzone-hinweis">
      JPG, PNG oder WebP · max. 8 MB je Datei · mehrere Dateien auf einmal möglich.<br>
      Bilder werden automatisch auf max. 1600 px verkleinert und als WebP gespeichert.
    </span>
    <input type="file" id="bilder" name="bilder[]" accept="image/jpeg,image/png,image/webp" multiple required>
  </label>

  <ul class="upload-dateiliste" id="upload-dateiliste"></ul>

  <button type="submit" class="btn btn-primary" style="margin-top:1rem">Hochladen</button>
</form>

<?php if (empty($bilder)): ?>
  <p class="leer-hinweis">Noch keine Fotos vorhanden. Lade oben das erste Bild hoch.</p>
<?php else: ?>
<form method="post">
  <input type="hidden" name="csrf_token" value="<?= e($csrf) ?>">
  <input type="hidden" name="aktion" value="speichern">

  <table class="termine-table">
    <thead>
      <tr>
        <th>Vorschau</th>
        <th>Titel</th>
        <th>Bildbeschreibung (Alt-Text)</th>
        <th>Reihenfolge</th>
        <th>Status</th>
        <th>Aktionen</th>
      </tr>
    </thead>
    <tbody>
    <?php foreach ($bilder as $b): ?>
      <tr class="<?= $b['aktiv'] ? '' : 'inactive' ?>">
        <td data-label="Vorschau">
          <img src="../<?= GALERIE_URL ?>/<?= e($b['thumb'] !== '' ? $b['thumb'] : $b['datei']) ?>"
               alt="" loading="lazy"
               style="width:110px;height:80px;object-fit:cover;border-radius:8px;border:1px solid #E2C2A2">
        </td>
        <td data-label="Titel">
          <input type="text" name="titel[<?= (int)$b['id'] ?>]" value="<?= e($b['titel']) ?>"
                 placeholder="z. B. Treffen im Mai" maxlength="200" style="width:100%">
        </td>
        <td data-label="Bildbeschreibung">
          <input type="text" name="alt_text[<?= (int)$b['id'] ?>]" value="<?= e($b['alt_text']) ?>"
                 placeholder="Was ist zu sehen? (für Screenreader)" maxlength="255" style="width:100%">
        </td>
        <td data-label="Reihenfolge">
          <input type="number" name="sortierung[<?= (int)$b['id'] ?>]" value="<?= (int)$b['sortierung'] ?>"
                 step="10" style="width:5.5rem">
          <span class="status-erklaerung">Kleine Zahl = weiter vorn</span>
        </td>
        <td data-label="Status">
          <button type="submit" form="toggle-<?= (int)$b['id'] ?>"
                  class="toggle-switch <?= $b['aktiv'] ? 'active' : '' ?>"
                  title="<?= $b['aktiv'] ? 'Klicken: Foto auf der Website verstecken' : 'Klicken: Foto auf der Website anzeigen' ?>">
            <span class="toggle-track"><span class="toggle-knob"></span></span>
            <span class="toggle-label"><?= $b['aktiv'] ? 'Sichtbar' : 'Versteckt' ?></span>
          </button>
          <span class="status-erklaerung"><?= $b['aktiv'] ? 'Steht in der Galerie' : 'Nicht auf der Website' ?></span>
        </td>
        <td data-label="Aktionen">
          <div class="actions">
            <button type="button" class="icon-btn danger" title="Löschen" aria-label="Foto löschen"
                    onclick="loeschenBestaetigen(document.getElementById('del-<?= (int)$b['id'] ?>'), <?= e(json_encode($b['titel'] !== '' ? $b['titel'] : $b['datei'])) ?>)">
              <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M3 6h18"/><path d="M8 6V4a2 2 0 0 1 2-2h4a2 2 0 0 1 2 2v2"/><path d="M19 6l-1 14a2 2 0 0 1-2 2H8a2 2 0 0 1-2-2L5 6"/></svg>
            </button>
          </div>
        </td>
      </tr>
    <?php endforeach; ?>
    </tbody>
  </table>

  <button type="submit" class="btn btn-primary" style="margin-top:1rem">Titel &amp; Reihenfolge speichern</button>
</form>

<?php foreach ($bilder as $b): ?>
  <form method="post" action="galerie-toggle.php" id="toggle-<?= (int)$b['id'] ?>" hidden>
    <input type="hidden" name="csrf_token" value="<?= e($csrf) ?>">
    <input type="hidden" name="id" value="<?= (int)$b['id'] ?>">
  </form>
  <form method="post" action="galerie-loeschen.php" id="del-<?= (int)$b['id'] ?>" hidden>
    <input type="hidden" name="csrf_token" value="<?= e($csrf) ?>">
    <input type="hidden" name="id" value="<?= (int)$b['id'] ?>">
  </form>
<?php endforeach; ?>
<?php endif; ?>

<!-- Lösch-Modal -->
<div class="modal-overlay" id="loeschModal">
  <div class="modal">
    <h2>Foto löschen</h2>
    <p id="loeschModalText">Soll dieses Foto wirklich gelöscht werden?</p>
    <div class="modal-actions">
      <button class="btn btn-secondary" onclick="modalSchliessen()">Abbrechen</button>
      <button class="btn btn-danger" id="loeschBestaetigen">Ja, löschen</button>
    </div>
  </div>
</div>

<script>
// ── Upload-Dropzone: Dateiliste anzeigen + Drag & Drop ──
(function () {
  const zone  = document.getElementById('dropzone');
  const input = document.getElementById('bilder');
  const liste = document.getElementById('upload-dateiliste');

  function groesse(bytes) {
    return bytes < 1024 * 1024
      ? Math.round(bytes / 1024) + ' KB'
      : (bytes / 1024 / 1024).toFixed(1).replace('.', ',') + ' MB';
  }

  function listeAktualisieren() {
    liste.textContent = '';
    Array.from(input.files).forEach(function (datei) {
      const li = document.createElement('li');
      li.innerHTML = '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect x="3" y="3" width="18" height="18" rx="2"/><circle cx="8.5" cy="8.5" r="1.5"/><path d="m21 15-5-5L5 21"/></svg>';
      const name = document.createElement('span');
      name.textContent = datei.name;
      const gr = document.createElement('span');
      gr.className = 'upload-dateigroesse';
      gr.textContent = groesse(datei.size);
      li.append(name, gr);
      liste.appendChild(li);
    });
  }

  input.addEventListener('change', listeAktualisieren);

  ['dragenter', 'dragover'].forEach(function (ev) {
    zone.addEventListener(ev, function (e) {
      e.preventDefault();
      zone.classList.add('is-hover');
    });
  });

  ['dragleave', 'drop'].forEach(function (ev) {
    zone.addEventListener(ev, function (e) {
      e.preventDefault();
      zone.classList.remove('is-hover');
    });
  });

  zone.addEventListener('drop', function (e) {
    if (e.dataTransfer.files.length) {
      input.files = e.dataTransfer.files;
      listeAktualisieren();
    }
  });
})();

let pendingForm = null;

function loeschenBestaetigen(form, name) {
  pendingForm = form;
  document.getElementById('loeschModalText').textContent = 'Soll „' + name + '“ wirklich gelöscht werden? Die Bilddatei wird dabei vom Server entfernt.';
  document.getElementById('loeschModal').classList.add('active');
}

function modalSchliessen() {
  pendingForm = null;
  document.getElementById('loeschModal').classList.remove('active');
}

document.getElementById('loeschBestaetigen').addEventListener('click', function() {
  if (pendingForm) pendingForm.submit();
});

document.getElementById('loeschModal').addEventListener('modal:geschlossen', function() {
  pendingForm = null;
});
</script>
<?php require __DIR__ . '/footer.php'; ?>
