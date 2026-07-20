<?php
require_once __DIR__ . '/auth.php';

$db = admin_db();

if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}
$csrf = $_SESSION['csrf_token'];

$flash = $_SESSION['flash'] ?? null;
unset($_SESSION['flash']);

$id      = isset($_GET['id']) ? (int)$_GET['id'] : 0;
$is_edit = $id > 0;
$errors  = [];

$alle_regionen = $db->query('SELECT id, name FROM community_regionen ORDER BY name ASC')->fetchAll();
$alle_tags     = $db->query('SELECT id, name, beschreibung FROM community_tags ORDER BY name ASC')->fetchAll();

$kontakt = [
    'name' => '', 'name_zusatz' => '', 'website' => '', 'telefon' => '', 'strasse' => '', 'plz' => '', 'ort' => '',
    'vermittlung' => 'direkt', 'bundesweit' => 0, 'aktiv' => 1,
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

$notizen_liste = [];
if ($is_edit) {
    $stmt = $db->prepare('SELECT id, text, erstellt_am FROM community_notizen WHERE organisation_id = ? ORDER BY erstellt_am DESC, id DESC');
    $stmt->execute([$id]);
    $notizen_liste = $stmt->fetchAll();
}

if ($is_edit && $_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['notiz_action'] ?? '') === 'add') {
    if (!hash_equals($csrf, $_POST['csrf_token'] ?? '')) {
        http_response_code(403);
        die('Ungültige Anfrage.');
    }
    $neue_notiz = trim($_POST['neue_notiz'] ?? '');
    if ($neue_notiz !== '') {
        $stmt = $db->prepare('INSERT INTO community_notizen (organisation_id, text, erstellt_am) VALUES (?, ?, NOW())');
        $stmt->execute([$id, $neue_notiz]);
        $_SESSION['flash'] = ['type' => 'ok', 'msg' => 'Notiz gespeichert.'];
    } else {
        $_SESSION['flash'] = ['type' => 'err', 'msg' => 'Notiz war leer, wurde nicht gespeichert.'];
    }
    header('Location: community-bearbeiten.php?id=' . $id);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    if (!hash_equals($csrf, $_POST['csrf_token'] ?? '')) {
        http_response_code(403);
        die('Ungültige Anfrage.');
    }

    $name            = trim($_POST['name'] ?? '');
    $name_zusatz     = trim($_POST['name_zusatz'] ?? '');
    $website         = trim($_POST['website'] ?? '');
    if ($website !== '' && !preg_match('#^https?://#i', $website)) {
        $website = 'https://' . $website;
    }
    $telefon         = trim($_POST['telefon'] ?? '');
    $strasse         = trim($_POST['strasse'] ?? '');
    $plz             = trim($_POST['plz'] ?? '');
    $ort             = trim($_POST['ort'] ?? '');
    $vermittlung     = $_POST['vermittlung'] ?? 'direkt';
    $bundesweit      = isset($_POST['bundesweit']) ? 1 : 0;
    $aktiv           = isset($_POST['aktiv']) ? 1 : 0;
    
    $region_ids_posted = array_map('intval', $_POST['regionen'] ?? []);
    $tag_ids_posted    = array_map('intval', $_POST['tags'] ?? []);

    $p_names   = $_POST['p_name'] ?? [];
    $p_tels    = $_POST['p_telefon'] ?? [];
    $p_handys  = $_POST['p_handy'] ?? [];
    $p_emails  = $_POST['p_email'] ?? [];
    $p_strasses= $_POST['p_strasse'] ?? [];
    $p_plzs    = $_POST['p_plz'] ?? [];
    $p_orts    = $_POST['p_ort'] ?? [];

    $gueltige_region_ids = array_map('intval', array_column($alle_regionen, 'id'));
    $gueltige_tag_ids    = array_map('intval', array_column($alle_tags, 'id'));
    $region_ids_in       = array_values(array_intersect($region_ids_posted, $gueltige_region_ids));
    $tag_ids_in          = array_values(array_intersect($tag_ids_posted, $gueltige_tag_ids));

    if ($name === '' || mb_strlen($name) > 160) {
        $errors[] = 'Name muss 1–160 Zeichen lang sein.';
    }
    if (mb_strlen($name_zusatz) > 160) {
        $errors[] = 'Namenszusatz darf max. 160 Zeichen lang sein.';
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
                'strasse' => trim($p_strasses[$index] ?? ''),
                'plz'     => trim($p_plzs[$index] ?? ''),
                'ort'     => trim($p_orts[$index] ?? ''),
            ];
        }
    }

    if (empty($errors)) {
        $params = [
            $name,
            $name_zusatz === '' ? null : $name_zusatz,
            $website === '' ? null : $website,
            $telefon === '' ? null : $telefon,
            $strasse === '' ? null : $strasse,
            $plz === '' ? null : $plz,
            $ort === '' ? null : $ort,
            $vermittlung,
            $bundesweit,
            $aktiv,
        ];

        if ($is_edit) {
            $stmt = $db->prepare(
                'UPDATE community_organisationen
                 SET name=?, name_zusatz=?, website=?, telefon=?, strasse=?, plz=?, ort=?, vermittlung=?, bundesweit=?, aktiv=?
                 WHERE id=?'
            );
            $stmt->execute([...$params, $id]);
        } else {
            $stmt = $db->prepare(
                'INSERT INTO community_organisationen
                 (name, name_zusatz, website, telefon, strasse, plz, ort, vermittlung, bundesweit, aktiv)
                 VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)'
            );
            $stmt->execute($params);
            $id = (int)$db->lastInsertId();
        }

        // Personen neu verknuepfen
        $db->prepare('DELETE FROM community_personen WHERE organisation_id = ?')->execute([$id]);
        if ($parsed_personen) {
            $stmt = $db->prepare('INSERT INTO community_personen (organisation_id, name, telefon, handy, email, strasse, plz, ort) VALUES (?, ?, ?, ?, ?, ?, ?, ?)');
            foreach ($parsed_personen as $p) {
                $stmt->execute([
                    $id,
                    $p['name'],
                    $p['telefon'] === '' ? null : $p['telefon'],
                    $p['handy'] === '' ? null : $p['handy'],
                    $p['email'] === '' ? null : $p['email'],
                    $p['strasse'] === '' ? null : $p['strasse'],
                    $p['plz'] === '' ? null : $p['plz'],
                    $p['ort'] === '' ? null : $p['ort']
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

        $_SESSION['flash'] = ['type' => 'ok', 'msg' => ($is_edit ? 'Aktualisiert: ' : 'Angelegt: ') . $name];
        if (isset($_POST['save_action']) && $_POST['save_action'] === 'save_stay') {
            header('Location: community-bearbeiten.php?id=' . $id);
        } else {
            header('Location: community.php');
        }
        exit;
    }

    // Eingaben fuer Wiederanzeige merken
    $kontakt = [
        'name' => $name, 'name_zusatz' => $name_zusatz, 'website' => $website, 'telefon' => $telefon,
        'strasse' => $strasse, 'plz' => $plz, 'ort' => $ort,
        'vermittlung' => $vermittlung, 'bundesweit' => $bundesweit, 'aktiv' => $aktiv,
    ];
    $personen = $parsed_personen;
    $region_ids = $region_ids_in;
    $tag_ids    = $tag_ids_in;
}
?>
<?php
$page_title = 'Admin – Organisation ' . ($is_edit ? 'bearbeiten' : 'anlegen');
$active_nav = 'community';
require __DIR__ . '/header.php';
?>

  <div class="crm-header">
    <div>
      <span class="crm-eyebrow"><?= $is_edit ? 'Datensatz bearbeiten' : 'Neuer Datensatz' ?></span>
      <h1><?= $is_edit ? e($kontakt['name'] ?: 'Organisation bearbeiten') : 'Neue Organisation' ?></h1>
    </div>
    <a href="community.php" class="btn btn-edit">← Übersicht</a>
  </div>

  <?php if ($flash): ?>
    <div class="alert alert-<?= e($flash['type']) ?>">
      <?= e($flash['msg']) ?>
    </div>
  <?php endif; ?>

  <?php if (!empty($errors)): ?>
    <div class="errors" role="alert" tabindex="-1">
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
          <button type="button" class="crm-panel-head crm-panel-head-toggle" aria-expanded="false" aria-controls="stammdaten_container" onclick="const t=document.getElementById('stammdaten_container'); const i=document.getElementById('stammdaten_icon'); const open=t.style.display==='block'; t.style.display=open?'none':'block'; i.style.transform=open?'rotate(0deg)':'rotate(180deg)'; this.setAttribute('aria-expanded', open?'false':'true');">
            <span class="crm-icon" aria-hidden="true"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M3 21h18"/><path d="M5 21V7l8-4v18"/><path d="M19 21V11l-6-4"/><path d="M9 9v.01M9 12v.01M9 15v.01M9 18v.01"/></svg></span>
            <div style="flex:1;">
              <h2>Stammdaten</h2>
              <span class="crm-panel-sub">Name, Website und Anschrift</span>
            </div>
            <span id="stammdaten_icon" style="transition:transform 0.2s; transform:rotate(0deg); color:#6b7280;"><svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="m6 9 6 6 6-6"/></svg></span>
          </button>
          <div id="stammdaten_container" style="display:none; margin-top:1rem;">

          <label for="name">Name / Organisation</label>
          <input type="text" id="name" name="name" required maxlength="160" autocomplete="organization" value="<?= e($kontakt['name']) ?>" placeholder="z. B. Beratungsstelle Freiburg…">

          <label for="name_zusatz">Zusatz (z. B. Abteilung/Team)</label>
          <input type="text" id="name_zusatz" name="name_zusatz" maxlength="160" autocomplete="off" value="<?= e($kontakt['name_zusatz'] ?? '') ?>" placeholder="z. B. Fachbereich Sucht…">

          <div class="row2">
            <div>
              <label for="website">Website</label>
              <div style="display:flex;gap:.3rem;">
                <input type="text" id="website" name="website" maxlength="255" autocomplete="url" inputmode="url" value="<?= e($kontakt['website'] ?? '') ?>" placeholder="z. B. domain.de" style="flex:1;">
                <a href="#" id="website-link" class="btn btn-secondary" target="_blank" style="padding:0 .6rem;display:flex;align-items:center;min-width:44px;" title="Website im neuen Tab öffnen">
                  <svg width="18" height="18" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" viewBox="0 0 24 24"><path d="M18 13v6a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2V8a2 2 0 0 1 2-2h6"/><polyline points="15 3 21 3 21 9"/><line x1="10" y1="14" x2="21" y2="3"/></svg>
                </a>
              </div>
            </div>
            <div>
              <label for="telefon">Telefon</label>
              <input type="tel" id="telefon" name="telefon" maxlength="40" autocomplete="tel" inputmode="tel" value="<?= e($kontakt['telefon'] ?? '') ?>" placeholder="z. B. 0761 123456…">
            </div>
          </div>

          <label for="strasse">Straße & Hausnummer</label>
          <input type="text" id="strasse" name="strasse" maxlength="120" autocomplete="street-address" value="<?= e($kontakt['strasse'] ?? '') ?>" placeholder="z. B. Musterstr. 1…">

          <div class="row2">
            <div>
              <label for="plz">PLZ</label>
              <input type="text" id="plz" name="plz" maxlength="10" autocomplete="postal-code" inputmode="numeric" value="<?= e($kontakt['plz'] ?? '') ?>" placeholder="z. B. 79098…">
            </div>
            <div>
              <label for="ort">Ort</label>
              <input type="text" id="ort" name="ort" maxlength="120" autocomplete="address-level2" value="<?= e($kontakt['ort'] ?? '') ?>" placeholder="z. B. Freiburg…">
            </div>
          </div>
          </div>
        </section>

        <section class="crm-panel">
          <button type="button" class="crm-panel-head crm-panel-head-toggle" aria-expanded="true" aria-controls="ansprechpartner_container" onclick="const t=document.getElementById('ansprechpartner_container'); const i=document.getElementById('ansprechpartner_icon'); const c=document.getElementById('ansprechpartner_count'); const open=t.style.display!=='none'; if(!open){t.style.display='block';i.style.transform='rotate(180deg)';c.style.display='none';}else{t.style.display='none';i.style.transform='rotate(0deg)'; const num = Array.from(document.querySelectorAll('#personen-container input[name=&quot;p_name[]&quot;]')).filter(inp => inp.value.trim() !== '').length; c.textContent = ' (' + num + ')'; c.style.display='inline';} this.setAttribute('aria-expanded', open?'false':'true');">
            <span class="crm-icon" aria-hidden="true"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M16 21v-2a4 4 0 0 0-4-4H6a4 4 0 0 0-4 4v2"/><circle cx="9" cy="7" r="4"/><path d="M22 21v-2a4 4 0 0 0-3-3.87"/><path d="M16 3.13a4 4 0 0 1 0 7.75"/></svg></span>
            <div style="flex:1;">
              <h2>Ansprechpartner<span id="ansprechpartner_count" style="display:none; color:#6b7280; font-weight:normal;"></span></h2>
              <span class="crm-panel-sub">Personen mit Kontaktdaten</span>
            </div>
            <span id="ansprechpartner_icon" style="transition:transform 0.2s; transform:rotate(180deg); color:#6b7280;"><svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="m6 9 6 6 6-6"/></svg></span>
          </button>
          <div id="ansprechpartner_container" style="display:block; margin-top:1rem;">

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
                      <input type="text" name="p_name[]" maxlength="120" autocomplete="off" value="<?= e($p['name'] ?? '') ?>" placeholder="z. B. Max Mustermann…">
                    </div>
                    <div>
                      <label>E-Mail</label>
                      <input type="email" name="p_email[]" maxlength="200" autocomplete="off" inputmode="email" spellcheck="false" value="<?= e($p['email'] ?? '') ?>">
                    </div>
                  </div>
                  <div class="row2">
                    <div>
                      <label>Telefon</label>
                      <input type="tel" name="p_telefon[]" maxlength="40" autocomplete="off" inputmode="tel" value="<?= e($p['telefon'] ?? '') ?>">
                    </div>
                    <div>
                      <label>Handynummer</label>
                      <input type="tel" name="p_handy[]" maxlength="40" autocomplete="off" inputmode="tel" value="<?= e($p['handy'] ?? '') ?>">
                    </div>
                  </div>
                  <?php $has_addr = !empty($p['strasse']) || !empty($p['plz']) || !empty($p['ort']); ?>
                  <div style="margin-top: .75rem;">
                    <label style="display:inline-flex; align-items:center; gap:.4rem; font-weight:normal; font-size:.9rem; cursor:pointer; color:#4b5563;">
                      <input type="checkbox" onchange="this.parentElement.nextElementSibling.style.display = this.checked ? 'block' : 'none'" <?= $has_addr ? 'checked' : '' ?>>
                      Abweichende Adresse eingeben
                    </label>
                    <div style="display: <?= $has_addr ? 'block' : 'none' ?>; margin-top: .5rem; padding-top: .5rem; border-top: 1px dashed #e5e7eb;">
                      <div class="person-address-grid">
                        <div>
                          <label>Straße & Hausnummer</label>
                          <input type="text" name="p_strasse[]" maxlength="255" autocomplete="off" value="<?= e($p['strasse'] ?? '') ?>">
                        </div>
                        <div>
                          <label>PLZ</label>
                          <input type="text" name="p_plz[]" maxlength="10" autocomplete="off" value="<?= e($p['plz'] ?? '') ?>">
                        </div>
                        <div>
                          <label>Ort</label>
                          <input type="text" name="p_ort[]" maxlength="255" autocomplete="off" value="<?= e($p['ort'] ?? '') ?>">
                        </div>
                      </div>
                    </div>
                  </div>
                </div>
              </div>
            <?php endforeach; ?>
          </div>
          <button type="button" class="btn btn-add-person" onclick="addPerson()">+ Weitere Person hinzufügen</button>
          </div>
        </section>

        <section class="crm-panel">
          <button type="button" class="crm-panel-head crm-panel-head-toggle" aria-expanded="false" aria-controls="notizen_container" onclick="const t=document.getElementById('notizen_container'); const i=document.getElementById('notizen_icon'); const open=t.style.display==='block'; t.style.display=open?'none':'block'; i.style.transform=open?'rotate(0deg)':'rotate(180deg)'; this.setAttribute('aria-expanded', open?'false':'true');">
            <span class="crm-icon" aria-hidden="true"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M12 20h9"/><path d="M16.5 3.5a2.12 2.12 0 0 1 3 3L7 19l-4 1 1-4Z"/></svg></span>
            <div style="flex:1;">
              <h2>Interne Notizen<span id="notizen_alert" style="color:#ef4444; font-weight:bold; margin-left:0.3rem; display:<?= !empty($notizen_liste) ? 'inline' : 'none' ?>;" title="Notizen vorhanden">(!)</span></h2>
              <span class="crm-panel-sub">Nur intern sichtbar</span>
            </div>
            <span id="notizen_icon" style="transition:transform 0.2s; transform:rotate(0deg); color:#6b7280;"><svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="m6 9 6 6 6-6"/></svg></span>
          </button>
          <div id="notizen_container" style="display:none; margin-top:1rem;">

            <?php if (!$is_edit): ?>
              <p class="crm-panel-sub">Notizen können erst nach dem ersten Speichern der Organisation hinzugefügt werden.</p>
            <?php else: ?>

              <?php if (empty($notizen_liste)): ?>
                <p class="crm-panel-sub">Noch keine Notizen vorhanden.</p>
              <?php else: ?>
                <ul style="list-style:none; margin:0 0 1rem; padding:0; display:flex; flex-direction:column; gap:0.75rem;">
                  <?php foreach ($notizen_liste as $n): ?>
                    <li style="border:1px solid var(--border-light); border-radius:0.5rem; padding:0.75rem;">
                      <div style="display:flex; justify-content:space-between; align-items:start; gap:0.5rem;">
                        <span style="font-size:0.8rem; color:#6b7280;"><?= e(date('d.m.Y H:i', strtotime($n['erstellt_am']))) ?> Uhr</span>
                        <button type="button" class="community-icon-btn danger" title="Notiz löschen"
                                data-notiz-id="<?= (int)$n['id'] ?>"
                                onclick="notizLoeschenBestaetigen(<?= (int)$n['id'] ?>)">
                          <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M3 6h18"/><path d="M8 6V4a2 2 0 0 1 2-2h4a2 2 0 0 1 2 2v2"/><path d="M19 6l-1 14a2 2 0 0 1-2 2H8a2 2 0 0 1-2-2L5 6"/></svg>
                        </button>
                      </div>
                      <p style="margin:0.5rem 0 0; white-space:pre-wrap;"><?= e($n['text']) ?></p>
                    </li>
                  <?php endforeach; ?>
                </ul>
              <?php endif; ?>

              <div style="display:flex; flex-direction:column; gap:0.5rem;">
                <textarea id="neue_notiz_text" rows="3" placeholder="Neue Notiz hinzufügen…"></textarea>
                <button type="button" class="btn btn-primary btn-notiz-speichern" onclick="notizSpeichern()">Neue Notiz speichern</button>
              </div>

            <?php endif; ?>
          </div>
        </section>

      </div>

      <!-- ── Sidebar-Panel ── -->
      <aside class="crm-side">

        <section class="crm-panel">
          <div class="crm-panel-head">
            <span class="crm-icon" aria-hidden="true"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="3"/><path d="M19.4 15a1.65 1.65 0 0 0 .33 1.82l.06.06a2 2 0 1 1-2.83 2.83l-.06-.06a1.65 1.65 0 0 0-1.82-.33 1.65 1.65 0 0 0-1 1.51V21a2 2 0 0 1-4 0v-.09A1.65 1.65 0 0 0 9 19.4a1.65 1.65 0 0 0-1.82.33l-.06.06a2 2 0 1 1-2.83-2.83l.06-.06a1.65 1.65 0 0 0 .33-1.82 1.65 1.65 0 0 0-1.51-1H3a2 2 0 0 1 0-4h.09A1.65 1.65 0 0 0 4.6 9a1.65 1.65 0 0 0-.33-1.82l-.06-.06a2 2 0 1 1 2.83-2.83l.06.06a1.65 1.65 0 0 0 1.82.33H9a1.65 1.65 0 0 0 1-1.51V3a2 2 0 0 1 4 0v.09a1.65 1.65 0 0 0 1 1.51 1.65 1.65 0 0 0 1.82-.33l.06-.06a2 2 0 1 1 2.83 2.83l-.06.06a1.65 1.65 0 0 0-.33 1.82V9a1.65 1.65 0 0 0 1.51 1H21a2 2 0 0 1 0 4h-.09a1.65 1.65 0 0 0-1.51 1Z"/></svg></span>
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
            <span class="crm-icon" aria-hidden="true"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M20 10c0 6-8 12-8 12s-8-6-8-12a8 8 0 0 1 16 0Z"/><circle cx="12" cy="10" r="3"/></svg></span>
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
            <span class="crm-icon" aria-hidden="true"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M12.586 2.586A2 2 0 0 0 11.172 2H4a2 2 0 0 0-2 2v7.172a2 2 0 0 0 .586 1.414l8.704 8.704a2.426 2.426 0 0 0 3.42 0l6.58-6.58a2.426 2.426 0 0 0 0-3.42z"/><circle cx="7.5" cy="7.5" r="1.5"/></svg></span>
            <div><h2>Spezialisierungs-Tags</h2></div>
          </div>
          <?php if (empty($alle_tags)): ?>
            <p class="hint">Noch keine Tags angelegt. <a href="community-tags.php">Tags verwalten</a></p>
          <?php else: ?>
            <input type="text" class="checkbox-picker-search" data-picker="tags-picker" placeholder="Tags durchsuchen…">
            <div class="checkbox-picker" id="tags-picker">
              <?php foreach ($alle_tags as $t): ?>
                <label style="flex-direction:column;align-items:flex-start;margin-bottom:0.6rem;">
                  <div style="display:flex;align-items:center;gap:.35rem;">
                    <input type="checkbox" name="tags[]" value="<?= (int)$t['id'] ?>"
                           <?= in_array((int)$t['id'], $tag_ids, true) ? 'checked' : '' ?>>
                    <?= e($t['name']) ?>
                  </div>
                  <?php if (!empty($t['beschreibung'])): ?>
                    <div style="font-size:0.8rem;color:#6b7280;margin-left:1.5rem;font-weight:normal;line-height:1.2;margin-top:0.15rem;"><?= e($t['beschreibung']) ?></div>
                  <?php endif; ?>
                </label>
              <?php endforeach; ?>
            </div>
          <?php endif; ?>
        </section>

      </aside>
    </div>

    <div class="crm-actions crm-actions-sticky">
      <button type="submit" name="save_action" value="save_close" class="btn btn-primary">Speichern & schließen</button>
      <button type="submit" name="save_action" value="save_stay" class="btn btn-soft-green" style="font-weight:500;">Zwischenspeichern</button>
      <a href="community.php" class="btn btn-secondary crm-actions-cancel">Abbrechen</a>
    </div>
  </form>

<?php ob_start(); ?>

<?php if ($is_edit): ?>
<!-- Notiz hinzufügen (eigenständiges Formular, außerhalb des Hauptformulars) -->
<form method="post" id="notizAddForm" style="display:none;">
  <input type="hidden" name="csrf_token" value="<?= e($csrf) ?>">
  <input type="hidden" name="notiz_action" value="add">
  <input type="hidden" name="neue_notiz" id="notizAddText">
</form>

<!-- Notiz löschen (eigenständiges Formular, außerhalb des Hauptformulars) -->
<form method="post" action="community-notiz-loeschen.php" id="notizDeleteForm" style="display:none;">
  <input type="hidden" name="csrf_token" value="<?= e($csrf) ?>">
  <input type="hidden" name="id" id="notizDeleteId">
  <input type="hidden" name="organisation_id" value="<?= (int)$id ?>">
</form>

<!-- Notiz-Lösch-Modal -->
<div class="modal-overlay" id="notizLoeschModal">
  <div class="modal">
    <h2>Notiz löschen</h2>
    <p>Soll diese Notiz wirklich gelöscht werden?</p>
    <div class="modal-actions">
      <button class="btn btn-secondary" onclick="notizModalSchliessen()">Abbrechen</button>
      <button class="btn btn-danger" id="notizLoeschBestaetigen">Ja, löschen</button>
    </div>
  </div>
</div>

<script>
let pendingNotizId = null;

function notizSpeichern() {
  const text = document.getElementById('neue_notiz_text').value.trim();
  if (text === '') return;
  document.getElementById('notizAddText').value = text;
  document.getElementById('notizAddForm').submit();
}

function notizLoeschenBestaetigen(notizId) {
  pendingNotizId = notizId;
  document.getElementById('notizLoeschModal').classList.add('active');
}

function notizModalSchliessen() {
  pendingNotizId = null;
  document.getElementById('notizLoeschModal').classList.remove('active');
}

document.getElementById('notizLoeschBestaetigen').addEventListener('click', function() {
  if (pendingNotizId) {
    document.getElementById('notizDeleteId').value = pendingNotizId;
    document.getElementById('notizDeleteForm').submit();
  }
});

document.getElementById('notizLoeschModal').addEventListener('click', function(e) {
  if (e.target === this) notizModalSchliessen();
});
</script>
<?php endif; ?>

<?php if (!empty($errors)): ?>
<script>
(function() {
  const err = document.querySelector('.errors');
  if (err) err.scrollIntoView({ block: 'center' });
  const invalid = document.querySelector('form :invalid');
  (invalid || document.getElementById('name')).focus();
})();
</script>
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
          <input type="text" name="p_name[]" maxlength="120" autocomplete="off" placeholder="z. B. Max Mustermann…">
        </div>
        <div>
          <label>E-Mail</label>
          <input type="email" name="p_email[]" maxlength="200" autocomplete="off" inputmode="email" spellcheck="false">
        </div>
      </div>
      <div class="row2">
        <div>
          <label>Telefon</label>
          <input type="tel" name="p_telefon[]" maxlength="40" autocomplete="off" inputmode="tel">
        </div>
        <div>
          <label>Handynummer</label>
          <input type="tel" name="p_handy[]" maxlength="40" autocomplete="off" inputmode="tel">
        </div>
      </div>
      <div style="margin-top: .75rem;">
        <label style="display:inline-flex; align-items:center; gap:.4rem; font-weight:normal; font-size:.9rem; cursor:pointer; color:#4b5563;">
          <input type="checkbox" onchange="this.parentElement.nextElementSibling.style.display = this.checked ? 'block' : 'none'">
          Abweichende Adresse eingeben
        </label>
        <div style="display: none; margin-top: .5rem; padding-top: .5rem; border-top: 1px dashed #e5e7eb;">
          <div class="person-address-grid">
            <div>
              <label>Straße & Hausnummer</label>
              <input type="text" name="p_strasse[]" maxlength="255" autocomplete="off">
            </div>
            <div>
              <label>PLZ</label>
              <input type="text" name="p_plz[]" maxlength="10" autocomplete="off">
            </div>
            <div>
              <label>Ort</label>
              <input type="text" name="p_ort[]" maxlength="255" autocomplete="off">
            </div>
          </div>
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

(function() {
  const webInput = document.getElementById('website');
  const webLink = document.getElementById('website-link');
  if (!webInput || !webLink) return;
  
  function updateLink() {
    let val = webInput.value.trim();
    if (val) {
      if (!/^https?:\/\//i.test(val)) val = 'https://' + val;
      webLink.href = val;
      webLink.style.pointerEvents = 'auto';
      webLink.style.opacity = '1';
    } else {
      webLink.removeAttribute('href');
      webLink.style.pointerEvents = 'none';
      webLink.style.opacity = '0.5';
    }
  }
  webInput.addEventListener('input', updateLink);
  updateLink();
})();
</script>
<?php 
$extra_scripts = ob_get_clean();
require __DIR__ . '/footer.php'; 
?>
