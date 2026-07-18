<?php
require_once __DIR__ . '/auth.php';

$db = admin_db();

$flash = $_SESSION['flash'] ?? null;
unset($_SESSION['flash']);

$stmt = $db->query(
    'SELECT id, termin_datum, uhrzeiten, slot_laenge_min, aktiv, ausgebucht
     FROM slot_konfiguration
     ORDER BY (termin_datum < CURRENT_DATE), termin_datum ASC'
);
$termine = $stmt->fetchAll();

if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}
$csrf = $_SESSION['csrf_token'];

$wochentage = ['Mo','Di','Mi','Do','Fr','Sa','So'];
$monate     = ['Januar','Februar','März','April','Mai','Juni','Juli','August','September','Oktober','November','Dezember'];

function datum_lesbar(string $datum, array $wt, array $mo): string {
    $ts = strtotime($datum);
    return $wt[(int)date('N',$ts)-1] . ', ' . (int)date('j',$ts) . '. ' . $mo[(int)date('n',$ts)-1] . ' ' . date('Y',$ts);
}

function zeitraum_lesbar(string $json, int $dauer): string {
    $arr = json_decode($json, true);
    if (empty($arr)) return '–';
    $start = $arr[0];
    [$h,$m] = explode(':', $start);
    $end_min = (int)$h*60 + (int)$m + $dauer;
    return $start . ' – ' . sprintf('%02d:%02d', intdiv($end_min,60), $end_min%60) . ' Uhr';
}

function termin_zeile(array $t, bool $is_past, string $csrf, array $wt, array $mo): string {
    $row_class = [];
    if (!$t['aktiv']) $row_class[] = 'inactive';
    if ($is_past) $row_class[] = 'past-event';
    ob_start();
    ?>
    <tr class="<?= implode(' ', $row_class) ?>">
      <td data-label="Datum"><strong><?= e(datum_lesbar($t['termin_datum'], $wt, $mo)) ?></strong></td>
      <td data-label="Uhrzeit"><?= e(zeitraum_lesbar($t['uhrzeiten'], (int)$t['slot_laenge_min'])) ?></td>
      <td data-label="Dauer"><?= (int)$t['slot_laenge_min'] ?> min</td>
      <td data-label="Ausgebucht">
        <form method="post" action="termin-toggle.php" style="margin:0">
          <input type="hidden" name="csrf_token" value="<?= e($csrf) ?>">
          <input type="hidden" name="id" value="<?= (int)$t['id'] ?>">
          <input type="hidden" name="field" value="ausgebucht">
          <button type="submit" class="toggle-switch warn <?= $t['ausgebucht'] ? 'active' : '' ?>" title="Klicken zum Umschalten">
            <span class="toggle-track"><span class="toggle-knob"></span></span>
            <span class="toggle-label"><?= $t['ausgebucht'] ? 'Ja' : 'Nein' ?></span>
          </button>
        </form>
      </td>
      <td data-label="Status">
        <form method="post" action="termin-toggle.php" style="margin:0">
          <input type="hidden" name="csrf_token" value="<?= e($csrf) ?>">
          <input type="hidden" name="id" value="<?= (int)$t['id'] ?>">
          <input type="hidden" name="field" value="aktiv">
          <button type="submit" class="toggle-switch <?= $t['aktiv'] ? 'active' : '' ?>" title="Klicken zum Umschalten">
            <span class="toggle-track"><span class="toggle-knob"></span></span>
            <span class="toggle-label"><?= $t['aktiv'] ? 'Sichtbar' : 'Versteckt' ?></span>
          </button>
        </form>
      </td>
      <td data-label="Aktionen">
        <div class="actions">
          <a href="termin-bearbeiten.php?id=<?= (int)$t['id'] ?>" class="icon-btn" title="Bearbeiten">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M12 20h9"/><path d="M16.5 3.5a2.12 2.12 0 0 1 3 3L7 19l-4 1 1-4Z"/></svg>
          </a>
          <a href="termin-bearbeiten.php?id=<?= (int)$t['id'] ?>&copy=1" class="icon-btn" title="Kopieren">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect x="9" y="9" width="13" height="13" rx="2"/><path d="M5 15H4a2 2 0 0 1-2-2V4a2 2 0 0 1 2-2h9a2 2 0 0 1 2 2v1"/></svg>
          </a>
          <form method="post" action="termin-loeschen.php" class="loeschen-form" style="margin:0">
            <input type="hidden" name="csrf_token" value="<?= e($csrf) ?>">
            <input type="hidden" name="id" value="<?= (int)$t['id'] ?>">
            <button type="button" class="icon-btn danger" title="Löschen"
                    onclick="loeschenBestaetigen(this.closest('form'), <?= e(json_encode(datum_lesbar($t['termin_datum'], $wt, $mo))) ?>)">
              <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M3 6h18"/><path d="M8 6V4a2 2 0 0 1 2-2h4a2 2 0 0 1 2 2v2"/><path d="M19 6l-1 14a2 2 0 0 1-2 2H8a2 2 0 0 1-2-2L5 6"/></svg>
            </button>
          </form>
        </div>
      </td>
    </tr>
    <?php
    return ob_get_clean();
}
?>
<!DOCTYPE html>
<html lang="de">
<head>
<link rel="icon" href="../grafik/F%C3%BCreinander%20Freiburg.svg" type="image/svg+xml">
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title>Admin – Termine</title>
<meta name="robots" content="noindex,nofollow">
<link rel="stylesheet" href="admin.css">
</head>
<body>
<div class="admin-layout">
<?php $active_nav = 'termine'; require __DIR__ . '/nav.php'; ?>
<div class="admin-main">

<div class="page-head">
  <div>
    <span class="page-eyebrow">Termine</span>
    <h1>Terminverwaltung</h1>
  </div>
  <div class="page-head-actions">
    <a href="termin-bearbeiten.php" class="btn btn-primary add-link" style="margin-bottom:0">+ Neuer Termin</a>
  </div>
</div>

<?php if ($flash): ?>
  <div class="alert <?= $flash['type'] === 'ok' ? 'alert-ok' : 'alert-err' ?>">
    <?= e($flash['msg']) ?>
  </div>
<?php endif; ?>

<?php if (empty($termine)): ?>
  <p style="color:#666;font-size:.9rem">Noch keine Termine vorhanden.</p>
<?php else:
  $heute = strtotime('today');
  $kommende = [];
  $vergangene = [];
  foreach ($termine as $t) {
      if (strtotime($t['termin_datum']) < $heute) {
          $vergangene[] = $t;
      } else {
          $kommende[] = $t;
      }
  }
?>

<?php if (empty($kommende)): ?>
  <p style="color:#666;font-size:.9rem">Keine kommenden Termine vorhanden.</p>
<?php else: ?>
<table class="termine-table">
  <thead>
    <tr>
      <th>Datum</th>
      <th>Uhrzeit</th>
      <th>Dauer</th>
      <th>Ausgebucht</th>
      <th>Status</th>
      <th>Aktionen</th>
    </tr>
  </thead>
  <tbody>
  <?php foreach ($kommende as $t): ?>
    <?= termin_zeile($t, false, $csrf, $wochentage, $monate) ?>
  <?php endforeach; ?>
  </tbody>
</table>
<?php endif; ?>

<?php if (!empty($vergangene)): ?>
<details class="vergangene-termine">
  <summary>Vergangene Termine (<?= count($vergangene) ?>)</summary>
  <table class="termine-table">
    <thead>
      <tr>
        <th>Datum</th>
        <th>Uhrzeit</th>
        <th>Dauer</th>
        <th>Ausgebucht</th>
        <th>Status</th>
        <th>Aktionen</th>
      </tr>
    </thead>
    <tbody>
    <?php foreach ($vergangene as $t): ?>
      <?= termin_zeile($t, true, $csrf, $wochentage, $monate) ?>
    <?php endforeach; ?>
    </tbody>
  </table>
</details>
<?php endif; ?>

<?php endif; ?>

<!-- Lösch-Modal -->
<div class="modal-overlay" id="loeschModal">
  <div class="modal">
    <h2>Termin löschen</h2>
    <p id="loeschModalText">Soll dieser Termin wirklich gelöscht werden?</p>
    <div class="modal-actions">
      <button class="btn btn-secondary" onclick="modalSchliessen()">Abbrechen</button>
      <button class="btn btn-danger" id="loeschBestaetigen">Ja, löschen</button>
    </div>
  </div>
</div>

<script>
let pendingForm = null;

function loeschenBestaetigen(form, datum) {
  pendingForm = form;
  document.getElementById('loeschModalText').textContent = 'Soll „' + datum + '" wirklich gelöscht werden?';
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
</script>
</div>
</div>
</body>
</html>
