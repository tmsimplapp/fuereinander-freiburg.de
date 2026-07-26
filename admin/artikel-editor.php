<?php
require_once __DIR__ . '/auth.php';

$db = admin_db();

$id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
$artikel = null;

if ($id > 0) {
    $stmt = $db->prepare('SELECT * FROM artikel WHERE id = ?');
    $stmt->execute([$id]);
    $artikel = $stmt->fetch();
    if (!$artikel) {
        $_SESSION['flash'] = ['type' => 'err', 'msg' => 'Artikel nicht gefunden.'];
        header('Location: artikel.php');
        exit;
    }
}

// Alle anderen Artikel für die Auswahl passender Verlinkungen laden
$andere_artikel_stmt = $db->prepare('SELECT id, titel FROM artikel WHERE id != ? ORDER BY titel ASC');
$andere_artikel_stmt->execute([$id]);
$alle_artikel_liste = $andere_artikel_stmt->fetchAll();

// Alle bestehenden Kategorien für Datalist laden
$kategorien_stmt = $db->query('SELECT DISTINCT kategorie FROM artikel WHERE kategorie IS NOT NULL AND kategorie != "" ORDER BY kategorie ASC');
$bestehende_kategorien = $kategorien_stmt->fetchAll(PDO::FETCH_COLUMN);

if (empty($bestehende_kategorien)) {
    $bestehende_kategorien = [
        'Ausstiegsphasen & Orientierung',
        'Für Angehörige & Freunde',
        'Psychologische Aufarbeitung & Selbsthilfe',
        'Gruppenleben & Erfahrungsberichte'
    ];
}

$errors = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!hash_equals($_SESSION['csrf_token'] ?? '', $_POST['csrf_token'] ?? '')) {
        http_response_code(403);
        die('Ungültige Anfrage.');
    }

    $titel           = trim($_POST['titel'] ?? '');
    $slug            = trim($_POST['slug'] ?? '');
    $kategorie       = trim($_POST['kategorie'] ?? 'Ausstieg');
    $tags            = trim($_POST['tags'] ?? '');
    $teaser          = trim($_POST['teaser'] ?? '');
    $inhalt          = trim($_POST['inhalt'] ?? '');
    $lesedauer_min   = max(1, (int)($_POST['lesedauer_min'] ?? 5));
    $titelbild       = trim($_POST['titelbild'] ?? '');
    $meta_title      = trim($_POST['meta_title'] ?? '');
    $meta_description= trim($_POST['meta_description'] ?? '');
    $is_published    = isset($_POST['is_published']) ? 1 : 0;

    // Bis zu 3 verlinkte Artikel IDs verarbeiten
    $related_selected = [];
    if (!empty($_POST['related_ids']) && is_array($_POST['related_ids'])) {
        foreach ($_POST['related_ids'] as $r_id) {
            $r_id = (int)$r_id;
            if ($r_id > 0 && count($related_selected) < 3 && !in_array($r_id, $related_selected, true)) {
                $related_selected[] = $r_id;
            }
        }
    }
    $related_ids_str = implode(',', $related_selected);

    // Slug aus Titel generieren falls leer
    if ($slug === '' && $titel !== '') {
        $slug = strtolower(trim(preg_replace('/[^a-zA-Z0-9]+/', '-', $titel), '-'));
    }

    if ($titel === '') {
        $errors[] = 'Bitte gib einen Titel ein.';
    }
    if ($slug === '') {
        $errors[] = 'Bitte gib einen Titel oder eine Adresse für die Seite ein.';
    }
    if ($teaser === '') {
        $errors[] = 'Bitte gib einen Teaser-Text ein.';
    }
    if ($inhalt === '') {
        $errors[] = 'Bitte gib den Inhalt ein.';
    }

    if (empty($errors)) {
        // Prüfen ob Slug eindeutig ist (außer bei eigenem ID)
        $chk_stmt = $db->prepare('SELECT id FROM artikel WHERE slug = ? AND id != ?');
        $chk_stmt->execute([$slug, $id]);
        if ($chk_stmt->fetch()) {
            $errors[] = 'Diese Adresse wird bereits von einem anderen Artikel verwendet. Bitte passe sie an.';
        } else {
            // Spalten in DB ermitteln
            $col_stmt = $db->query("SHOW COLUMNS FROM artikel");
            $existing_cols = $col_stmt->fetchAll(PDO::FETCH_COLUMN);

            // Dynamisch Spalten hinzufügen falls in DB noch nicht vorhanden
            if (!in_array('tags', $existing_cols, true)) {
                $db->exec("ALTER TABLE artikel ADD COLUMN tags VARCHAR(255) NULL AFTER kategorie");
            }
            if (!in_array('related_ids', $existing_cols, true)) {
                $db->exec("ALTER TABLE artikel ADD COLUMN related_ids VARCHAR(255) NULL AFTER tags");
            }

            if ($id > 0) {
                // Update
                $stmt = $db->prepare(
                    'UPDATE artikel SET
                        slug = ?, titel = ?, teaser = ?, inhalt = ?, kategorie = ?, tags = ?, related_ids = ?,
                        lesedauer_min = ?, titelbild = ?, meta_title = ?, meta_description = ?, is_published = ?
                     WHERE id = ?'
                );
                $stmt->execute([
                    $slug, $titel, $teaser, $inhalt, $kategorie, $tags, $related_ids_str,
                    $lesedauer_min, $titelbild, $meta_title, $meta_description, $is_published,
                    $id
                ]);
                $_SESSION['flash'] = ['type' => 'ok', 'msg' => 'Artikel wurde gespeichert.'];
            } else {
                // Insert
                $stmt = $db->prepare(
                    'INSERT INTO artikel
                        (slug, titel, teaser, inhalt, kategorie, tags, related_ids, lesedauer_min, titelbild, meta_title, meta_description, is_published)
                     VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)'
                );
                $stmt->execute([
                    $slug, $titel, $teaser, $inhalt, $kategorie, $tags, $related_ids_str,
                    $lesedauer_min, $titelbild, $meta_title, $meta_description, $is_published
                ]);
                $_SESSION['flash'] = ['type' => 'ok', 'msg' => 'Artikel wurde neu erstellt.'];
            }
            
            if (isset($_POST['save_action']) && $_POST['save_action'] === 'save_stay') {
                $redirect_id = $id > 0 ? $id : (int)$db->lastInsertId();
                header('Location: artikel-editor.php?id=' . $redirect_id);
            } else {
                header('Location: artikel.php');
            }
            exit;
        }
    }
} else {
    // Werte für Formular
    $titel           = $artikel['titel'] ?? '';
    $slug            = $artikel['slug'] ?? '';
    $kategorie       = $artikel['kategorie'] ?? 'Ausstiegsphasen & Orientierung';
    $tags            = $artikel['tags'] ?? '';
    $teaser          = $artikel['teaser'] ?? '';
    $inhalt          = $artikel['inhalt'] ?? '';
    $lesedauer_min   = $artikel['lesedauer_min'] ?? 5;
    $titelbild       = $artikel['titelbild'] ?? '';
    $meta_title      = $artikel['meta_title'] ?? '';
    $meta_description= $artikel['meta_description'] ?? '';
    $is_published    = isset($artikel['is_published']) ? (int)$artikel['is_published'] : 0;
    
    $related_selected = !empty($artikel['related_ids']) ? array_map('intval', explode(',', $artikel['related_ids'])) : [];
}

if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}
$csrf = $_SESSION['csrf_token'];

$page_title = $id > 0 ? 'Admin – Artikel bearbeiten' : 'Admin – Neuer Artikel';
$active_nav = 'artikel';

// EasyMDE Stylesheets laden
$extra_head = '<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/easymde/dist/easymde.min.css">';
$extra_head .= '<style>
  .EasyMDEContainer { width: 100% !important; max-width: 100% !important; }
  .editor-preview { background: #fefae0 !important; color: #1a2820 !important; font-family: "Lato", sans-serif !important; }
  .editor-preview h2 { font-size: 1.5rem; font-weight: bold; color: #1a2820; margin-top: 1.2rem; }
  .editor-preview p { line-height: 1.6; margin-bottom: 1rem; }
</style>';

require __DIR__ . '/header.php';
?>

<div class="crm-header">
  <div>
    <span class="crm-eyebrow"><a href="artikel.php" style="color:inherit; text-decoration:none;">&larr; Zurück zur Übersicht</a></span>
    <h1><?= $id > 0 ? 'Artikel bearbeiten' : 'Neuer Themenartikel' ?></h1>
  </div>
  <a href="artikel.php" class="btn btn-edit">&larr; Abbrechen</a>
</div>

<?php if (!empty($errors)): ?>
  <div class="errors" role="alert" style="margin-bottom:1.5rem;">
    <strong>Bitte korrigieren:</strong>
    <ul>
      <?php foreach ($errors as $err): ?>
        <li><?= e($err) ?></li>
      <?php endforeach; ?>
    </ul>
  </div>
<?php endif; ?>

<form method="post" action="">
  <input type="hidden" name="csrf_token" value="<?= e($csrf) ?>">

  <div class="crm-grid">
    <!-- Linke Spalte: Hauptinhalte -->
    <div class="crm-col-main">
      <section class="crm-panel">
        <div class="crm-panel-head">
          <span class="crm-icon"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/><polyline points="14 2 14 8 20 8"/></svg></span>
          <div>
            <h2>Artikelinhalt</h2>
            <span class="crm-panel-sub">Titel, Beschreibung und Artikeltext</span>
          </div>
        </div>

        <label for="titel">Titel *</label>
        <input type="text" id="titel" name="titel" value="<?= e($titel) ?>" required placeholder="z. B. Ausstiegsfolgen überwinden">

        <label for="slug">Adresse der Seite</label>
        <p class="hint" style="margin-bottom:.5rem">Unter dieser Adresse ist der Artikel später erreichbar. Leer lassen – die Adresse wird dann automatisch aus dem Titel gebildet.</p>
        <div style="display:flex; align-items:center; gap:.4rem; margin-bottom: 1.2rem;">
          <span style="font-size:.85rem; color:#888;">/themen/</span>
          <input type="text" id="slug" name="slug" value="<?= e($slug) ?>" placeholder="ausstiegsfolgen-ueberwinden" style="margin-bottom:0;">
        </div>

        <label for="teaser">Teaser / Kurzbeschreibung *</label>
        <textarea id="teaser" name="teaser" rows="3" required placeholder="Kurze Vorschau für die Übersicht..." style="resize:vertical;"><?= e($teaser) ?></textarea>

        <label for="inhalt">Inhalt *</label>
        <textarea id="inhalt" name="inhalt"><?= e($inhalt) ?></textarea>
      </section>

      <!-- SEO Sektion -->
      <section class="crm-panel" style="margin-top:1.5rem;">
        <div class="crm-panel-head">
          <span class="crm-icon"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="11" cy="11" r="8"/><line x1="21" y1="21" x2="16.65" y2="16.65"/></svg></span>
          <div>
            <h2>Suchmaschinen-Optimierung (SEO)</h2>
            <span class="crm-panel-sub">Optional: Suchergebnis-Darstellung anpassen</span>
          </div>
        </div>

        <label for="meta_title">SEO Meta-Titel</label>
        <input type="text" id="meta_title" name="meta_title" value="<?= e($meta_title) ?>" placeholder="Falls abweichend vom Artikel-Titel">

        <label for="meta_description">SEO Meta-Beschreibung</label>
        <textarea id="meta_description" name="meta_description" rows="2" placeholder="Falls abweichend vom Teaser" style="resize:vertical;"><?= e($meta_description) ?></textarea>
      </section>
    </div>

    <!-- Rechte Spalte: Einstellungen & Metadaten -->
    <aside class="crm-side">
      <!-- Veröffentlichungspanel -->
      <section class="crm-panel">
        <div class="crm-panel-head">
          <span class="crm-icon"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M2 12s3-7 10-7 10 7 10 7-3 7-10 7-10-7-10-7Z"/><circle cx="12" cy="12" r="3"/></svg></span>
          <div><h2>Status</h2></div>
        </div>

        <div class="crm-toggle-card">
          <div class="crm-toggle-text">
            <strong>Veröffentlicht</strong>
            <small>Artikel im Frontend sichtbar machen</small>
            <div style="font-size:0.75rem; color:#d35400; margin-top:0.4rem; line-height:1.3; font-weight:normal;">
              Als eingeloggter Admin kannst du diesen Artikel auch als „Entwurf“ im Frontend aufrufen. Für normale Besucher ist er unsichtbar (404-Meldung).
            </div>
            <div style="margin-top: 0.8rem; display: flex; flex-direction: column; gap: 0.5rem;">
              <?php if ($id > 0): ?>
                <a href="../themen/<?= e($slug) ?>" target="_blank" class="btn btn-soft-green" style="font-size: 0.8rem; padding: 0.4rem 0.8rem; text-decoration: none; display: inline-flex; align-items: center; justify-content: center; gap: 0.4rem; font-weight: 500;">
                  Artikel im Frontend ansehen
                </a>
              <?php endif; ?>
              <button type="button" onclick="downloadMarkdown()" class="btn btn-secondary" style="font-size: 0.8rem; padding: 0.4rem 0.8rem; cursor: pointer; text-decoration: none; display: inline-flex; align-items: center; justify-content: center; gap: 0.4rem; font-weight: 500;">
                Als Markdown herunterladen
              </button>
            </div>
          </div>
          <input type="checkbox" id="is_published" name="is_published" value="1" <?= $is_published ? 'checked' : '' ?>>
        </div>
      </section>

      <!-- Kategorisierung -->
      <section class="crm-panel" style="margin-top:1.2rem;">
        <div class="crm-panel-head">
          <span class="crm-icon"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M22 19a2 2 0 0 1-2 2H4a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h5l2 3h9a2 2 0 0 1 2 2z"/></svg></span>
          <div><h2>Kategorisierung</h2></div>
        </div>

        <label for="kategorie">Kategorie *</label>
        <input type="text" id="kategorie" name="kategorie" value="<?= e($kategorie) ?>" list="kategorie-liste" placeholder="z. B. Ausstieg, Angehörige" required>
        <datalist id="kategorie-liste">
          <?php foreach ($bestehende_kategorien as $kat): ?>
            <option value="<?= e($kat) ?>">
          <?php endforeach; ?>
        </datalist>

        <label for="tags" style="margin-top:.8rem;">Tags (Kommagetrennt)</label>
        <input type="text" id="tags" name="tags" value="<?= e($tags) ?>" placeholder="z. B. Zweifel, Familie, Isolation">

        <label for="lesedauer_min" style="margin-top:.8rem;">Lesedauer (Minuten)</label>
        <input type="number" id="lesedauer_min" name="lesedauer_min" value="<?= (int)$lesedauer_min ?>" min="1" max="120">
      </section>

      <!-- Verlinkungen -->
      <section class="crm-panel" style="margin-top:1.2rem;">
        <div class="crm-panel-head">
          <span class="crm-icon"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M10 13a5 5 0 0 0 7.54.54l3-3a5 5 0 0 0-7.07-7.07l-1.72 1.71"/><path d="M14 11a5 5 0 0 0-7.54-.54l-3 3a5 5 0 0 0 7.07 7.07l1.71-1.71"/></svg></span>
          <div><h2>Empfehlungen</h2></div>
        </div>
        <p style="font-size:.8rem; color:#666; margin-bottom:.8rem; line-height:1.4;">Wähle bis zu 3 passende Artikel aus, die unter dem Beitrag verlinkt werden:</p>

        <?php for ($i = 0; $i < 3; $i++):
          $curr_val = $related_selected[$i] ?? 0;
        ?>
          <div style="margin-bottom:.6rem;">
            <label style="font-size:.75rem; color:#888; font-weight:600; margin-bottom:.1rem; display:block;">Empfehlung <?= $i + 1 ?></label>
            <select name="related_ids[]" style="font-size:.85rem; padding:.4rem;">
              <option value="0">-- Keine Empfehlung --</option>
              <?php foreach ($alle_artikel_liste as $a_item): ?>
                <option value="<?= (int)$a_item['id'] ?>" <?= $curr_val === (int)$a_item['id'] ? 'selected' : '' ?>>
                  <?= e($a_item['titel']) ?>
                </option>
              <?php endforeach; ?>
            </select>
          </div>
        <?php endfor; ?>
      </section>
    </aside>
  </div>

  <div class="crm-actions crm-actions-sticky">
    <button type="submit" name="save_action" value="save_close" class="btn btn-primary">Speichern & schließen</button>
    <button type="submit" name="save_action" value="save_stay" class="btn btn-soft-green" style="font-weight:500;">Speichern</button>
    <a href="artikel.php" class="btn btn-secondary crm-actions-cancel">Abbrechen</a>
  </div>
</form>

<script src="https://cdn.jsdelivr.net/npm/easymde/dist/easymde.min.js"></script>
<script>
const easyMDE = new EasyMDE({
  element: document.getElementById('inhalt'),
  spellChecker: false,
  status: ['lines', 'words', 'cursor'],
  toolbar: [
    'bold', 'italic', 'heading-2', 'heading-3', '|',
    'quote', 'unordered-list', 'ordered-list', '|',
    'link', 'horizontal-rule', '|',
    'preview', 'side-by-side', 'fullscreen', '|',
    'guide'
  ],
  placeholder: 'Schreibe hier deinen Artikel...',
  minHeight: '380px'
});

// Warnen, wenn die Seite mit ungespeicherten Änderungen verlassen wird.
(function() {
  const form = document.querySelector('form[method="post"]');
  if (!form) return;

  let schmutzig = false;
  let wirdGespeichert = false;

  form.addEventListener('input', function() { schmutzig = true; });
  form.addEventListener('change', function() { schmutzig = true; });
  easyMDE.codemirror.on('change', function() { schmutzig = true; });

  // Beim Absenden ist das Verlassen gewollt.
  form.addEventListener('submit', function() { wirdGespeichert = true; });

  window.addEventListener('beforeunload', function(e) {
    if (!schmutzig || wirdGespeichert) return;
    e.preventDefault();
    // Der Browser zeigt seinen eigenen Text; returnValue ist nur für ältere nötig.
    e.returnValue = '';
  });
})();

function downloadMarkdown() {
  const titel = document.getElementById('titel').value || 'artikel';
  const slug = document.getElementById('slug').value || 'artikel';
  const kategorie = document.getElementById('kategorie').value || '';
  const tags = document.getElementById('tags').value || '';
  const teaser = (document.getElementById('teaser').value || '').replace(/\r?\n/g, ' ');
  const inhalt = typeof easyMDE !== 'undefined' ? easyMDE.value() : document.getElementById('inhalt').value;

  const markdown = `---\n` +
                   `title: ${titel}\n` +
                   `slug: ${slug}\n` +
                   `category: ${kategorie}\n` +
                   `tags: ${tags}\n` +
                   `teaser: ${teaser}\n` +
                   `---\n\n` +
                   inhalt;

  const blob = new Blob([markdown], { type: 'text/markdown;charset=utf-8' });
  const url = URL.createObjectURL(blob);
  const a = document.createElement('a');
  a.href = url;
  a.download = slug.replace(/[^a-zA-Z0-9\-]/g, '_') + '.md';
  document.body.appendChild(a);
  a.click();
  document.body.removeChild(a);
  URL.revokeObjectURL(url);
}
</script>

<?php require __DIR__ . '/footer.php'; ?>
