<?php
require_once __DIR__ . '/auth.php';

$db = admin_db();

if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}
$csrf = $_SESSION['csrf_token'];

$id      = isset($_GET['id']) ? (int)$_GET['id'] : 0;
$is_edit = $id > 0;
$errors  = [];

$alle_regionen = $db->query('SELECT id, name FROM community_regionen ORDER BY name ASC')->fetchAll();
$alle_tags     = $db->query('SELECT id, name FROM community_tags ORDER BY name ASC')->fetchAll();

$kontakt = [
    'name' => '', 'website' => '', 'strasse' => '', 'plz_ort' => '',
    'vermittlung' => 'direkt', 'bundesweit' => 0, 'notizen' => '', 'aktiv' => 1,
];
$personen = [];
$region_ids = [];
$tag_ids    = [];

if ($is_edit) {
    $stmt = $db->prepare('SELECT * FROM community_organisationen WHERE id = ? LIMIT 1');
    $stmt->execute([$id]);
    $row = $stmt->fetch();
    if (!$row) {
        $_SESSION['flash'] = ['type' => 'err', 'msg' => 'Organisation nicht gefunden.'];
        header('Location: community.php');
        exit;
    }
    $kontakt = $row;

    $stmt = $db->prepare('SELECT * FROM community_personen WHERE organisation_id = ? ORDER BY id ASC');
    $stmt->execute([$id]);
    $personen = $stmt->fetchAll();

    $stmt = $db->prepare('SELECT region_id FROM community_organisation_regionen WHERE organisation_id = ?');
    $stmt->execute([$id]);
    $region_ids = array_map('intval', array_column($stmt->fetchAll(), 'region_id'));

    $stmt = $db->prepare('SELECT tag_id FROM community_organisation_tags WHERE organisation_id = ?');
    $stmt->execute([$id]);
    $tag_ids = array_map('intval', array_column($stmt->fetchAll(), 'tag_id'));
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    if (!hash_equals($csrf, $_POST['csrf_token'] ?? '')) {
        http_response_code(403);
        die('Ungültige Anfrage.');
    }

    $name            = trim($_POST['name'] ?? '');
    $website         = trim($_POST['website'] ?? '');
    $strasse         = trim($_POST['strasse'] ?? '');
    $plz_ort         = trim($_POST['plz_ort'] ?? '');
    $vermittlung     = $_POST['vermittlung'] ?? 'direkt';
    $bundesweit      = isset($_POST['bundesweit']) ? 1 : 0;
    $notizen         = trim($_POST['notizen'] ?? '');
    $aktiv           = isset($_POST['aktiv']) ? 1 : 0;
    
    $region_ids_posted = array_map('intval', $_POST['regionen'] ?? []);
    $tag_ids_posted    = array_map('intval', $_POST['tags'] ?? []);

    $p_names   = $_POST['p_name'] ?? [];
    $p_tels    = $_POST['p_telefon'] ?? [];
    $p_handys  = $_POST['p_handy'] ?? [];
    $p_emails  = $_POST['p_email'] ?? [];

    $gueltige_region_ids = array_map('intval', array_column($alle_regionen, 'id'));
    $gueltige_tag_ids    = array_map('intval', array_column($alle_tags, 'id'));
    $region_ids_in       = array_values(array_intersect($region_ids_posted, $gueltige_region_ids));
    $tag_ids_in          = array_values(array_intersect($tag_ids_posted, $gueltige_tag_ids));

    if ($name === '' || mb_strlen($name) > 160) {
        $errors[] = 'Name muss 1–160 Zeichen lang sein.';
    }
    if (!in_array($vermittlung, ['direkt', 'ueber_uns'], true)) {
        $errors[] = 'Ungültige Vermittlungsart.';
    }

    $parsed_personen = [];
    foreach ($p_names as $index => $p_name) {
        $pn = trim($p_name);
        if ($pn !== '') {
            $parsed_personen[] = [
                'name'    => $pn,
                'telefon' => trim($p_tels[$index] ?? ''),
                'handy'   => trim($p_handys[$index] ?? ''),
                'email'   => trim($p_emails[$index] ?? ''),
            ];
        }
    }

    if (empty($errors)) {
        $params = [
            $name,
            $website === '' ? null : $website,
            $strasse === '' ? null : $strasse,
            $plz_ort === '' ? null : $plz_ort,
            $vermittlung,
            $bundesweit,
            $notizen === '' ? null : $notizen,
            $aktiv,
        ];

        if ($is_edit) {
            $stmt = $db->prepare(
                'UPDATE community_organisationen
                 SET name=?, website=?, strasse=?, plz_ort=?, vermittlung=?, bundesweit=?, notizen=?, aktiv=?
                 WHERE id=?'
            );
            $stmt->execute([...$params, $id]);
        } else {
            $stmt = $db->prepare(
                'INSERT INTO community_organisationen
                 (name, website, strasse, plz_ort, vermittlung, bundesweit, notizen, aktiv)
                 VALUES (?, ?, ?, ?, ?, ?, ?, ?)'
            );
            $stmt->execute($params);
            $id = (int)$db->lastInsertId();
        }

        // Personen neu verknuepfen
        $db->prepare('DELETE FROM community_personen WHERE organisation_id = ?')->execute([$id]);
        if ($parsed_personen) {
            $stmt = $db->prepare('INSERT INTO community_personen (organisation_id, name, telefon, handy, email) VALUES (?, ?, ?, ?, ?)');
            foreach ($parsed_personen as $p) {
                $stmt->execute([
                    $id,
                    $p['name'],
                    $p['telefon'] === '' ? null : $p['telefon'],
                    $p['handy'] === '' ? null : $p['handy'],
                    $p['email'] === '' ? null : $p['email']
                ]);
            }
        }

        $db->prepare('DELETE FROM community_organisation_regionen WHERE organisation_id = ?')->execute([$id]);
        if ($region_ids_in) {
            $stmt = $db->prepare('INSERT INTO community_organisation_regionen (organisation_id, region_id) VALUES (?, ?)');
            foreach ($region_ids_in as $rid) {
                $stmt->execute([$id, $rid]);
            }
        }

        $db->prepare('DELETE FROM community_organisation_tags WHERE organisation_id = ?')->execute([$id]);
        if ($tag_ids_in) {
            $stmt = $db->prepare('INSERT INTO community_organisation_tags (organisation_id, tag_id) VALUES (?, ?)');
            foreach ($tag_ids_in as $tid) {
                $stmt->execute([$id, $tid]);
            }
        }

        $_SESSION['flash'] = ['type' => 'ok', 'msg' => $is_edit ? 'Organisation aktualisiert.' : 'Organisation angelegt.'];
        header('Location: community.php');
        exit;
    }

    // Eingaben fuer Wiederanzeige merken
    $kontakt = [
        'name' => $name, 'website' => $website, 'strasse' => $strasse, 'plz_ort' => $plz_ort,
        'vermittlung' => $vermittlung, 'bundesweit' => $bundesweit, 'notizen' => $notizen, 'aktiv' => $aktiv,
    ];
    $personen = $parsed_personen;
    $region_ids = $region_ids_in;
    $tag_ids    = $tag_ids_in;
}
?>
<!DOCTYPE html>
<html lang="de">
<head>
<link rel="icon" href="../grafik/F%C3%BCreinander%20Freiburg.svg" type="image/svg+xml">
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title>Admin – Organisation <?= $is_edit ? 'bearbeiten' : 'anlegen' ?></title>
<meta name="robots" content="noindex,nofollow">
<link rel="stylesheet" href="admin.css">
</head>
<body>
<div class="admin-layout">
<?php $active_nav = 'community'; require __DIR__ . '/nav.php'; ?>
<div class="admin-main">

  <div class="crm-header">
    <div>
      <span class="crm-eyebrow"><?= $is_edit ? 'Datensatz bearbeiten' : 'Neuer Datensatz' ?></span>
      <h1><?= $is_edit ? e($kontakt['name'] ?: 'Organisation bearbeiten') : 'Neue Organisation' ?></h1>
    </div>
    <a href="community.php" class="btn btn-edit">← Übersicht</a>
  </div>

  <?php if (!empty($errors)): ?>
    <div class="errors">
      <strong>Bitte korrigieren:</strong>
      <ul>
        <?php foreach ($errors as $err): ?>
          <li><?= e($err) ?></li>
        <?php endforeach; ?>
      </ul>
    </div>
  <?php endif; ?>

  <form method="post">
    <input type="hidden" name="csrf_token" value="<?= e($csrf) ?>">

    <div class="crm-grid">
      <!-- ── Hauptspalte ── -->
      <div class="crm-col-main">

        <section class="crm-panel">
          <div class="crm-panel-head">
            <span class="crm-icon" aria-hidden="true">🏷️</span>
            <div>
              <h2>Stammdaten</h2>
              <span class="crm-panel-sub">Name, Website und Anschrift</span>
            </div>
          </div>

          <label for="name">Name / Organisation</label>
          <input type="text" id="name" name="name" required maxlength="160" value="<?= e($kontakt['name']) ?>" placeholder="z. B. Beratungsstelle Freiburg">

          <div class="row2">
            <div>
              <label for="website">Website</label>
              <input type="url" id="website" name="website" maxlength="255" value="<?= e($kontakt['website'] ?? '') ?>" placeholder="https://…">
            </div>
            <div>
              <label for="strasse">Straße & Hausnummer</label>
              <input type="text" id="strasse" name="strasse" maxlength="120" value="<?= e($kontakt['strasse'] ?? '') ?>" placeholder="z. B. Musterstr. 1">
            </div>
          </div>

          <label for="plz_ort">PLZ / Ort</label>
          <input type="text" id="plz_ort" name="plz_ort" maxlength="120" value="<?= e($kontakt['plz_ort'] ?? '') ?>" placeholder="z. B. 79098 Freiburg">
        </section>

        <section class="crm-panel">
          <div class="crm-panel-head">
            <span class="crm-icon" aria-hidden="true">👥</span>
            <div>
              <h2>Ansprechpartner</h2>
              <span class="crm-panel-sub">Personen mit Kontaktdaten</span>
            </div>
          </div>

          <div id="personen-container">
            <?php
            $render_personen = empty($personen) ? [null] : $personen;
            foreach ($render_personen as $i => $p):
            ?>
              <div class="person-block" data-person-label="Person <?= $i + 1 ?>">
                <button type="button" class="person-remove" onclick="removePerson(this)" title="Person entfernen" aria-label="Person entfernen">✕</button>
                <div class="person-fields">
                  <div class="row2">
                    <div>
                      <label>Name</label>
                      <input type="text" name="p_name[]" maxlength="120" value="<?= e($p['name'] ?? '') ?>" placeholder="Z. B. Max Mustermann">
                    </div>
                    <div>
                      <label>E-Mail</label>
                      <input type="email" name="p_email[]" maxlength="200" value="<?= e($p['email'] ?? '') ?>">
                    </div>
                  </div>
                  <div class="row2">
                    <div>
                      <label>Telefon</label>
                      <input type="text" name="p_telefon[]" maxlength="40" value="<?= e($p['telefon'] ?? '') ?>">
                    </div>
                    <div>
                      <label>Handynummer</label>
                      <input type="text" name="p_handy[]" maxlength="40" value="<?= e($p['handy'] ?? '') ?>">
                    </div>
                  </div>
                </div>
              </div>
            <?php endforeach; ?>
          </div>
          <button type="button" class="btn btn-add-person" onclick="addPerson()">+ Weitere Person hinzufügen</button>
        </section>

        <section class="crm-panel">
          <div class="crm-panel-head">
            <span class="crm-icon" aria-hidden="true">📝</span>
            <div>
              <h2>Interne Notizen</h2>
              <span class="crm-panel-sub">Nur intern sichtbar</span>
            </div>
          </div>
          <textarea id="notizen" name="notizen" rows="5"
                    placeholder="z. B. Erfahrungen, Besonderheiten, Reaktionszeit…"><?= e($kontakt['notizen'] ?? '') ?></textarea>
        </section>

      </div>

      <!-- ── Sidebar-Panel ── -->
      <aside class="crm-side">

        <section class="crm-panel">
          <div class="crm-panel-head">
            <span class="crm-icon" aria-hidden="true">⚙️</span>
            <div><h2>Status & Vermittlung</h2></div>
          </div>

          <label>Vermittlung</label>
          <div class="crm-segmented">
            <label>
              <input type="radio" name="vermittlung" value="direkt" <?= $kontakt['vermittlung'] === 'direkt' ? 'checked' : '' ?>>
              <span>Direkt weitergebbar</span>
            </label>
            <label>
              <input type="radio" name="vermittlung" value="ueber_uns" <?= $kontakt['vermittlung'] === 'ueber_uns' ? 'checked' : '' ?>>
              <span>Nur über uns</span>
            </label>
          </div>

          <div class="crm-toggle-card" style="margin-top:1rem">
            <div class="crm-toggle-text">
              <strong>Bundesweit tätig</strong>
              <small>Nicht regional gebunden</small>
            </div>
            <input type="checkbox" id="bundesweit" name="bundesweit" value="1" <?= $kontakt['bundesweit'] ? 'checked' : '' ?>>
          </div>

          <div class="crm-toggle-card" style="margin-top:.6rem">
            <div class="crm-toggle-text">
              <strong>Aktiv</strong>
              <small>In Übersicht sichtbar</small>
            </div>
            <input type="checkbox" id="aktiv" name="aktiv" value="1" <?= $kontakt['aktiv'] ? 'checked' : '' ?>>
          </div>
        </section>

        <section class="crm-panel">
          <div class="crm-panel-head">
            <span class="crm-icon" aria-hidden="true">📍</span>
            <div><h2>Regionen</h2></div>
          </div>
          <?php if (empty($alle_regionen)): ?>
            <p class="hint">Noch keine Regionen angelegt. <a href="community-regionen.php">Regionen verwalten</a></p>
          <?php else: ?>
            <input type="text" class="checkbox-picker-search" data-picker="regionen-picker" placeholder="Regionen durchsuchen…">
            <div class="checkbox-picker" id="regionen-picker">
              <?php foreach ($alle_regionen as $r): ?>
                <label>
                  <input type="checkbox" name="regionen[]" value="<?= (int)$r['id'] ?>"
                         <?= in_array((int)$r['id'], $region_ids, true) ? 'checked' : '' ?>>
                  <?= e($r['name']) ?>
                </label>
              <?php endforeach; ?>
            </div>
          <?php endif; ?>
        </section>

        <section class="crm-panel">
          <div class="crm-panel-head">
            <span class="crm-icon" aria-hidden="true">🎯</span>
            <div><h2>Spezialisierungs-Tags</h2></div>
          </div>
          <?php if (empty($alle_tags)): ?>
            <p class="hint">Noch keine Tags angelegt. <a href="community-tags.php">Tags verwalten</a></p>
          <?php else: ?>
            <input type="text" class="checkbox-picker-search" data-picker="tags-picker" placeholder="Tags durchsuchen…">
            <div class="checkbox-picker" id="tags-picker">
              <?php foreach ($alle_tags as $t): ?>
                <label>
                  <input type="checkbox" name="tags[]" value="<?= (int)$t['id'] ?>"
                         <?= in_array((int)$t['id'], $tag_ids, true) ? 'checked' : '' ?>>
                  <?= e($t['name']) ?>
                </label>
              <?php endforeach; ?>
            </div>
          <?php endif; ?>
        </section>

      </aside>
    </div>

    <div class="crm-actions">
      <button type="submit" class="btn btn-primary">Speichern</button>
      <a href="community.php" class="btn btn-secondary">Abbrechen</a>
    </div>
  </form>

</div>
</div>
<?php if (!empty($errors)): ?>
<script>document.getElementById('name').focus();</script>
<?php endif; ?>
<script>
function renumberPersonen() {
  document.querySelectorAll('#personen-container .person-block').forEach(function(block, i) {
    block.dataset.personLabel = 'Person ' + (i + 1);
  });
}

function removePerson(btn) {
  btn.closest('.person-block').remove();
  renumberPersonen();
}

function addPerson() {
  const container = document.getElementById('personen-container');
  const block = document.createElement('div');
  block.className = 'person-block';
  block.innerHTML = `
    <button type="button" class="person-remove" onclick="removePerson(this)" title="Person entfernen" aria-label="Person entfernen">✕</button>
    <div class="person-fields">
      <div class="row2">
        <div>
          <label>Name</label>
          <input type="text" name="p_name[]" maxlength="120" placeholder="Z. B. Max Mustermann">
        </div>
        <div>
          <label>E-Mail</label>
          <input type="email" name="p_email[]" maxlength="200">
        </div>
      </div>
      <div class="row2">
        <div>
          <label>Telefon</label>
          <input type="text" name="p_telefon[]" maxlength="40">
        </div>
        <div>
          <label>Handynummer</label>
          <input type="text" name="p_handy[]" maxlength="40">
        </div>
      </div>
    </div>
  `;
  container.appendChild(block);
  renumberPersonen();
  block.querySelector('input').focus();
}

document.querySelectorAll('.checkbox-picker-search').forEach(function(input) {
  const picker = document.getElementById(input.dataset.picker);
  input.addEventListener('input', function() {
    const term = input.value.trim().toLowerCase();
    let visibleCount = 0;
    picker.querySelectorAll('label').forEach(function(label) {
      const match = label.textContent.trim().toLowerCase().includes(term);
      label.style.display = match ? '' : 'none';
      if (match) visibleCount++;
    });
    let noMatch = picker.querySelector('.no-match');
    if (visibleCount === 0) {
      if (!noMatch) {
        noMatch = document.createElement('p');
        noMatch.className = 'no-match';
        noMatch.textContent = 'Keine Treffer.';
        picker.appendChild(noMatch);
      }
    } else if (noMatch) {
      noMatch.remove();
    }
  });
});
</script>
</body>
</html>
