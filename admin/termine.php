<?php
require_once __DIR__ . '/auth.php';

$db = admin_db();

$flash = $_SESSION['flash'] ?? null;
unset($_SESSION['flash']);

$stmt = $db->query(
    'SELECT id, termin_datum, uhrzeiten, slot_laenge_min, aktiv
     FROM slot_konfiguration
     ORDER BY (termin_datum < CURRENT_DATE), termin_datum ASC'
);
$termine = $stmt->fetchAll();

$counter_file = __DIR__ . '/../counter.txt';
$seitenaufrufe = file_exists($counter_file) ? (int) file_get_contents($counter_file) : 0;

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
?>
<!DOCTYPE html>
<html lang="de">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title>Admin – Termine</title>
<meta name="robots" content="noindex,nofollow">
<link rel="stylesheet" href="admin.css">
</head>
<body>
<div class="topbar">
  <div class="topbar-brand">
    <img src="../grafik/F%C3%BCreinander%20Freiburg.svg" alt="Logo">
    <h1>Terminverwaltung</h1>
  </div>
  <div class="topbar-nav">
    <a href="profil.php" class="nav-link">Profil</a>
    <form method="post" action="logout.php">
      <button type="submit" class="btn-logout">Abmelden</button>
    </form>
  </div>
</div>

<?php if ($flash): ?>
  <div class="alert <?= $flash['type'] === 'ok' ? 'alert-ok' : 'alert-err' ?>">
    <?= e($flash['msg']) ?>
  </div>
<?php endif; ?>

<div class="infobox">
  <svg width="16" height="16" fill="none" stroke="currentColor" stroke-width="1.8" viewBox="0 0 24 24" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/><path stroke-linecap="round" stroke-linejoin="round" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/></svg>
  Seitenaufrufe: <strong><?= number_format($seitenaufrufe, 0, ',', '.') ?></strong>
</div>

<a href="termin-bearbeiten.php" class="btn btn-primary add-link">+ Neuer Termin</a>

<?php if (empty($termine)): ?>
  <p style="color:#666;font-size:.9rem">Noch keine Termine vorhanden.</p>
<?php else: ?>
<table class="termine-table">
  <thead>
    <tr>
      <th>Datum</th>
      <th>Uhrzeit</th>
      <th>Dauer</th>
      <th>Status</th>
      <th>Aktionen</th>
    </tr>
  </thead>
  <tbody>
  <?php foreach ($termine as $t): 
    $is_past = strtotime($t['termin_datum']) < strtotime('today');
    $row_class = [];
    if (!$t['aktiv']) $row_class[] = 'inactive';
    if ($is_past) $row_class[] = 'past-event';
  ?>
    <tr class="<?= implode(' ', $row_class) ?>">
      <td data-label="Datum"><strong><?= e(datum_lesbar($t['termin_datum'], $wochentage, $monate)) ?></strong></td>
      <td data-label="Uhrzeit"><?= e(zeitraum_lesbar($t['uhrzeiten'], (int)$t['slot_laenge_min'])) ?></td>
      <td data-label="Dauer"><?= (int)$t['slot_laenge_min'] ?> min</td>
      <td data-label="Status">
        <span class="badge <?= $t['aktiv'] ? 'badge-on' : 'badge-off' ?>">
          <?= $t['aktiv'] ? 'Sichtbar' : 'Versteckt' ?>
        </span>
      </td>
      <td data-label="Aktionen">
        <div class="actions">
          <a href="termin-bearbeiten.php?id=<?= (int)$t['id'] ?>" class="btn btn-secondary">Bearbeiten</a>

          <form method="post" action="termin-toggle.php">
            <input type="hidden" name="csrf_token" value="<?= e($csrf) ?>">
            <input type="hidden" name="id" value="<?= (int)$t['id'] ?>">
            <button type="submit" class="btn <?= $t['aktiv'] ? 'btn-toggle-on' : 'btn-toggle-off' ?>">
              <?= $t['aktiv'] ? 'Verstecken' : 'Zeigen' ?>
            </button>
          </form>

          <form method="post" action="termin-loeschen.php" class="loeschen-form">
            <input type="hidden" name="csrf_token" value="<?= e($csrf) ?>">
            <input type="hidden" name="id" value="<?= (int)$t['id'] ?>">
            <button type="button" class="btn btn-danger"
                    onclick="loeschenBestaetigen(this.closest('form'), <?= e(json_encode(datum_lesbar($t['termin_datum'], $wochentage, $monate))) ?>)">
              Löschen
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
</body>
</html>
