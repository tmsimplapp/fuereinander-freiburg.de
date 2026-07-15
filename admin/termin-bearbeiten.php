<?php
require_once __DIR__ . '/auth.php';

$db = admin_db();

if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}
$csrf = $_SESSION['csrf_token'];

$id      = isset($_GET['id']) ? (int)$_GET['id'] : 0;
$is_copy = isset($_GET['copy']) && $_GET['copy'] == 1;
$is_edit = $id > 0 && !$is_copy;
$errors  = [];

// Startuhrzeit aus JSON-Array extrahieren
function erste_uhrzeit(string $json): string {
    $arr = json_decode($json, true);
    return (!empty($arr) && isset($arr[0])) ? $arr[0] : '18:00';
}

$termin = ['termin_datum' => '', 'uhrzeit' => '18:00', 'slot_laenge_min' => 120, 'aktiv' => 1, 'bemerkung' => '', 'max_teilnehmer' => 8, 'ausgebucht' => 0];

if ($is_edit || $is_copy) {
    $stmt = $db->prepare('SELECT * FROM slot_konfiguration WHERE id = ? LIMIT 1');
    $stmt->execute([$id]);
    $row = $stmt->fetch();
    if (!$row) {
        $_SESSION['flash'] = ['type' => 'err', 'msg' => 'Termin nicht gefunden.'];
        header('Location: termine.php');
        exit;
    }
    $termin = [
        'termin_datum'    => $is_copy ? '' : $row['termin_datum'],
        'uhrzeit'         => erste_uhrzeit($row['uhrzeiten']),
        'slot_laenge_min' => $row['slot_laenge_min'],
        'aktiv'           => $is_copy ? 0 : $row['aktiv'],
        'bemerkung'       => $row['bemerkung'] ?? '',
        'max_teilnehmer'  => $row['max_teilnehmer'] ?? 8,
        'ausgebucht'      => $is_copy ? 0 : $row['ausgebucht'],
    ];
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    if (!hash_equals($csrf, $_POST['csrf_token'] ?? '')) {
        http_response_code(403);
        die('Ungültige Anfrage.');
    }

    $datum     = trim($_POST['termin_datum'] ?? '');
    $uhrzeit   = trim($_POST['uhrzeit'] ?? '');
    $dauer     = $_POST['slot_laenge_min'] ?? '';
    $aktiv     = isset($_POST['aktiv']) ? 1 : 0;
    $bemerkung      = trim($_POST['bemerkung'] ?? '');
    $max_teilnehmer = $_POST['max_teilnehmer'] ?? '';
    $ausgebucht     = isset($_POST['ausgebucht']) ? 1 : 0;

    // Datum
    $d = DateTime::createFromFormat('Y-m-d', $datum);
    if (!$d || $d->format('Y-m-d') !== $datum) {
        $errors[] = 'Datum ist ungültig.';
    }

    // Uhrzeit
    if (!preg_match('/^\d{2}:\d{2}$/', $uhrzeit)) {
        $errors[] = 'Uhrzeit ist ungültig.';
    } else {
        [$h, $m] = explode(':', $uhrzeit);
        if ((int)$h > 23 || (int)$m > 59) {
            $errors[] = 'Uhrzeit außerhalb des gültigen Bereichs.';
        }
    }

    // Dauer
    if (!ctype_digit((string)$dauer) || (int)$dauer < 1 || (int)$dauer > 1440) {
        $errors[] = 'Dauer muss eine positive ganze Zahl in Minuten sein (1–1440).';
    }

    // Max. Teilnehmer
    if (!ctype_digit((string)$max_teilnehmer) || (int)$max_teilnehmer < 1 || (int)$max_teilnehmer > 999) {
        $errors[] = 'Max. Teilnehmer muss eine Zahl zwischen 1 und 999 sein.';
    }

    if (empty($errors)) {
        $dauer_int      = (int)$dauer;
        $uhrzeiten_json = json_encode([$uhrzeit]);
        $bemerkung_db   = $bemerkung === '' ? null : $bemerkung;
        $max_tln_int    = (int)$max_teilnehmer;

        if ($is_edit) {
            $stmt = $db->prepare(
                'UPDATE slot_konfiguration
                 SET termin_datum = ?, uhrzeiten = ?, slot_laenge_min = ?, aktiv = ?, bemerkung = ?, max_teilnehmer = ?, ausgebucht = ?
                 WHERE id = ?'
            );
            $stmt->execute([$datum, $uhrzeiten_json, $dauer_int, $aktiv, $bemerkung_db, $max_tln_int, $ausgebucht, $id]);
            $_SESSION['flash'] = ['type' => 'ok', 'msg' => 'Termin aktualisiert.'];
        } else {
            $stmt = $db->prepare(
                'INSERT INTO slot_konfiguration (termin_datum, uhrzeiten, slot_laenge_min, aktiv, bemerkung, max_teilnehmer, ausgebucht)
                 VALUES (?, ?, ?, ?, ?, ?, ?)'
            );
            $stmt->execute([$datum, $uhrzeiten_json, $dauer_int, $aktiv, $bemerkung_db, $max_tln_int, $ausgebucht]);
            $_SESSION['flash'] = ['type' => 'ok', 'msg' => 'Termin angelegt.'];
        }

        header('Location: termine.php');
        exit;
    }

    // Eingaben für Wiederanzeige merken
    $termin['termin_datum']    = $datum;
    $termin['uhrzeit']         = $uhrzeit;
    $termin['slot_laenge_min'] = $dauer;
    $termin['aktiv']           = $aktiv;
    $termin['bemerkung']       = $bemerkung;
    $termin['max_teilnehmer']  = $max_teilnehmer;
    $termin['ausgebucht']      = $ausgebucht;
}
?>
<!DOCTYPE html>
<html lang="de">
<head>
<link rel="icon" href="../grafik/F%C3%BCreinander%20Freiburg.svg" type="image/svg+xml">
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title>Admin – Termin <?= $is_edit ? 'bearbeiten' : ($is_copy ? 'kopieren' : 'anlegen') ?></title>
<meta name="robots" content="noindex,nofollow">
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/flatpickr/dist/flatpickr.min.css">
<link rel="stylesheet" href="admin.css">
</head>
<body>
<div class="admin-layout">
<?php $active_nav = 'termine'; require __DIR__ . '/nav.php'; ?>
<div class="admin-main">

  <div class="crm-header">
    <div>
      <span class="crm-eyebrow"><?= $is_edit ? 'Termin bearbeiten' : ($is_copy ? 'Termin kopieren' : 'Neuer Termin') ?></span>
      <h1><?= $is_edit ? 'Termin bearbeiten' : ($is_copy ? 'Termin kopieren' : 'Neuer Termin') ?></h1>
    </div>
    <a href="termine.php" class="btn btn-edit">← Übersicht</a>
  </div>

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
      <div class="crm-col-main">
        <section class="crm-panel">
          <div class="crm-panel-head">
            <span class="crm-icon" aria-hidden="true"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect width="18" height="18" x="3" y="4" rx="2"/><path d="M3 10h18M8 2v4M16 2v4"/></svg></span>
            <div>
              <h2>Termindaten</h2>
              <span class="crm-panel-sub">Datum, Uhrzeit und Kapazität</span>
            </div>
          </div>

          <label for="termin_datum">Datum</label>
          <input type="text" id="termin_datum" name="termin_datum" required
                 value="<?= e($termin['termin_datum']) ?>" placeholder="Datum wählen" readonly>

          <div class="row2">
            <div>
              <label for="uhrzeit">Uhrzeit (Beginn)</label>
              <input type="time" id="uhrzeit" name="uhrzeit" required
                     value="<?= e($termin['uhrzeit']) ?>">
            </div>
            <div>
              <label for="slot_laenge_min">Dauer (Minuten)</label>
              <input type="number" id="slot_laenge_min" name="slot_laenge_min" required
                     min="1" max="1440" value="<?= e((string)$termin['slot_laenge_min']) ?>">
            </div>
          </div>

          <label for="max_teilnehmer">Max. Teilnehmer</label>
          <input type="number" id="max_teilnehmer" name="max_teilnehmer" required
                 min="1" max="999" value="<?= e((string)$termin['max_teilnehmer']) ?>">

          <label for="bemerkung">Bemerkung <span style="color:#aaa;font-weight:400">(optional, öffentlich sichtbar)</span></label>
          <textarea id="bemerkung" name="bemerkung" rows="3"
                    placeholder="z. B. Bitte Eingang Hintergebäude nutzen…"><?= e($termin['bemerkung'] ?? '') ?></textarea>
        </section>
      </div>

      <aside class="crm-side">
        <section class="crm-panel">
          <div class="crm-panel-head">
            <span class="crm-icon" aria-hidden="true"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M2 12s3-7 10-7 10 7 10 7-3 7-10 7-10-7-10-7Z"/><circle cx="12" cy="12" r="3"/></svg></span>
            <div><h2>Sichtbarkeit</h2></div>
          </div>

          <div class="crm-toggle-card">
            <div class="crm-toggle-text">
              <strong>Öffentlich sichtbar</strong>
              <small>Auf der Website anzeigen</small>
            </div>
            <input type="checkbox" id="aktiv" name="aktiv" value="1" <?= $termin['aktiv'] ? 'checked' : '' ?>>
          </div>

          <div class="crm-toggle-card is-warn" style="margin-top:.6rem">
            <div class="crm-toggle-text">
              <strong>Ausgebucht</strong>
              <small>Keine Buchung mehr möglich</small>
            </div>
            <input type="checkbox" id="ausgebucht" name="ausgebucht" value="1" <?= $termin['ausgebucht'] ? 'checked' : '' ?>>
          </div>
        </section>
      </aside>
    </div>

    <div class="crm-actions">
      <button type="submit" class="btn btn-primary">Speichern</button>
      <a href="termine.php" class="btn btn-secondary">Abbrechen</a>
    </div>
  </form>

</div>
</div>
<script src="https://cdn.jsdelivr.net/npm/flatpickr"></script>
<script src="https://cdn.jsdelivr.net/npm/flatpickr/dist/l10n/de.js"></script>
<script>
flatpickr('#termin_datum', {
  locale: 'de',
  dateFormat: 'Y-m-d',
  altInput: true,
  altFormat: 'd.m.Y',
  allowInput: false,
  disableMobile: true,
  onReady(_, __, fp) {
    const select = fp.calendarContainer.querySelector('.flatpickr-monthDropdown-months');
    if (!select) return;
    const wrap = document.createElement('span');
    wrap.className = 'fp-month-wrap';
    select.parentNode.insertBefore(wrap, select);
    wrap.appendChild(select);
  }
});
<?php if (!empty($errors)): ?>
(function() {
  const err = document.querySelector('.errors');
  if (err) err.scrollIntoView({ block: 'center' });
  const invalid = document.querySelector('form :invalid');
  (invalid || document.getElementById('uhrzeit')).focus();
})();
<?php endif; ?>
</script>
</body>
</html>
