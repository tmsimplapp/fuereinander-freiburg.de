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

// Startuhrzeit aus JSON-Array extrahieren
function erste_uhrzeit(string $json): string {
    $arr = json_decode($json, true);
    return (!empty($arr) && isset($arr[0])) ? $arr[0] : '18:00';
}

$termin = ['termin_datum' => '', 'uhrzeit' => '18:00', 'slot_laenge_min' => 120, 'aktiv' => 1, 'bemerkung' => '', 'max_teilnehmer' => 8];

if ($is_edit) {
    $stmt = $db->prepare('SELECT * FROM slot_konfiguration WHERE id = ? LIMIT 1');
    $stmt->execute([$id]);
    $row = $stmt->fetch();
    if (!$row) {
        $_SESSION['flash'] = ['type' => 'err', 'msg' => 'Termin nicht gefunden.'];
        header('Location: termine.php');
        exit;
    }
    $termin = [
        'termin_datum'    => $row['termin_datum'],
        'uhrzeit'         => erste_uhrzeit($row['uhrzeiten']),
        'slot_laenge_min' => $row['slot_laenge_min'],
        'aktiv'           => $row['aktiv'],
        'bemerkung'       => $row['bemerkung'] ?? '',
        'max_teilnehmer'  => $row['max_teilnehmer'] ?? 8,
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
                 SET termin_datum = ?, uhrzeiten = ?, slot_laenge_min = ?, aktiv = ?, bemerkung = ?, max_teilnehmer = ?
                 WHERE id = ?'
            );
            $stmt->execute([$datum, $uhrzeiten_json, $dauer_int, $aktiv, $bemerkung_db, $max_tln_int, $id]);
            $_SESSION['flash'] = ['type' => 'ok', 'msg' => 'Termin aktualisiert.'];
        } else {
            $stmt = $db->prepare(
                'INSERT INTO slot_konfiguration (termin_datum, uhrzeiten, slot_laenge_min, aktiv, bemerkung, max_teilnehmer)
                 VALUES (?, ?, ?, ?, ?, ?)'
            );
            $stmt->execute([$datum, $uhrzeiten_json, $dauer_int, $aktiv, $bemerkung_db, $max_tln_int]);
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
}
?>
<!DOCTYPE html>
<html lang="de">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title>Admin – Termin <?= $is_edit ? 'bearbeiten' : 'anlegen' ?></title>
<meta name="robots" content="noindex,nofollow">
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/flatpickr/dist/flatpickr.min.css">
<link rel="stylesheet" href="admin.css">
</head>
<body>
<div class="card">
  <h1><?= $is_edit ? 'Termin bearbeiten' : 'Neuer Termin' ?></h1>

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
              style="width:100%;padding:.55rem .75rem;border:1px solid #ccc;border-radius:4px;font-size:1rem;resize:vertical;font-family:inherit"
              placeholder="z. B. Bitte Eingang Hintergebäude nutzen."><?= e($termin['bemerkung'] ?? '') ?></textarea>

    <div class="checkbox-row">
      <input type="checkbox" id="aktiv" name="aktiv" value="1"
             <?= $termin['aktiv'] ? 'checked' : '' ?>>
      <label for="aktiv" style="margin:0">Öffentlich sichtbar</label>
    </div>

    <div class="form-actions">
      <button type="submit" class="btn btn-primary">Speichern</button>
      <a href="termine.php" class="btn btn-secondary">Abbrechen</a>
    </div>
  </form>
</div>
<script src="https://cdn.jsdelivr.net/npm/flatpickr"></script>
<script src="https://cdn.jsdelivr.net/npm/flatpickr/dist/l10n/de.js"></script>
<script>
flatpickr('#termin_datum', {
  locale: 'de',
  dateFormat: 'Y-m-d',
  allowInput: false,
  disableMobile: true,
});
</script>
</body>
</html>
