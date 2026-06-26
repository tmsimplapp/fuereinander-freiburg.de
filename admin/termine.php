<?php
require_once __DIR__ . '/auth.php';

$db = admin_db();

$flash = $_SESSION['flash'] ?? null;
unset($_SESSION['flash']);

$stmt = $db->query(
    'SELECT id, termin_datum, uhrzeiten, slot_laenge_min, aktiv
     FROM slot_konfiguration
     ORDER BY termin_datum ASC'
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
?>
<!DOCTYPE html>
<html lang="de">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title>Admin – Termine</title>
<meta name="robots" content="noindex,nofollow">
<style>
*{box-sizing:border-box;margin:0;padding:0}
body{font-family:system-ui,sans-serif;background:#f4f4f4;color:#222;padding:1rem}
.topbar{display:flex;justify-content:space-between;align-items:center;background:#fff;padding:.75rem 1rem;border-radius:6px;margin-bottom:1.5rem;box-shadow:0 1px 4px rgba(0,0,0,.1)}
.topbar h1{font-size:1rem;color:#333}
a.btn,button.btn{display:inline-block;padding:.45rem .9rem;border-radius:4px;font-size:.875rem;font-weight:600;text-decoration:none;cursor:pointer;border:none;line-height:1.4}
.btn-primary{background:#a9e2cc;color:#1a2820}.btn-primary:hover{background:#8dd4bb}
.btn-danger{background:#fee2e2;color:#991b1b}.btn-danger:hover{background:#fecaca}
.btn-secondary{background:#e5e7eb;color:#374151}.btn-secondary:hover{background:#d1d5db}
.btn-toggle-on{background:#fef9c3;color:#854d0e;border:1px solid #fde047}.btn-toggle-on:hover{background:#fef08a}
.btn-toggle-off{background:#dcfce7;color:#166534;border:1px solid #86efac}.btn-toggle-off:hover{background:#bbf7d0}
.btn-logout{background:transparent;color:#666;font-size:.8rem;text-decoration:underline;padding:.25rem .5rem;border:none;cursor:pointer}
table{width:100%;border-collapse:collapse;background:#fff;border-radius:6px;overflow:hidden;box-shadow:0 1px 4px rgba(0,0,0,.1)}
th,td{text-align:left;padding:.7rem .85rem;font-size:.875rem;vertical-align:middle}
th{background:#f9fafb;color:#555;font-weight:600;border-bottom:1px solid #e5e7eb}
tr:not(:last-child) td{border-bottom:1px solid #f3f4f6}
.inactive td{opacity:.45}
.badge{display:inline-block;padding:.2rem .55rem;border-radius:99px;font-size:.75rem;font-weight:600}
.badge-on{background:#dcfce7;color:#166534}
.badge-off{background:#f3f4f6;color:#6b7280}
.alert{padding:.65rem .85rem;border-radius:4px;margin-bottom:1rem;font-size:.875rem}
.alert-ok{background:#d1fae5;color:#065f46}
.alert-err{background:#fee2e2;color:#991b1b}
.actions{display:flex;gap:.4rem;flex-wrap:wrap}
.add-link{margin-bottom:1rem;display:inline-block}
/* Modal */
.modal-overlay{display:none;position:fixed;inset:0;background:rgba(0,0,0,.4);z-index:100;align-items:center;justify-content:center}
.modal-overlay.active{display:flex}
.modal{background:#fff;border-radius:10px;padding:1.5rem;max-width:340px;width:90%;box-shadow:0 8px 32px rgba(0,0,0,.18)}
.modal h2{font-size:1rem;margin-bottom:.5rem;color:#222}
.modal p{font-size:.875rem;color:#555;margin-bottom:1.25rem}
.modal-actions{display:flex;gap:.6rem;justify-content:flex-end}
</style>
</head>
<body>
<div class="topbar">
  <h1>Füreinander Freiburg · Terminverwaltung</h1>
  <form method="post" action="logout.php">
    <button type="submit" class="btn-logout">Abmelden</button>
  </form>
</div>

<?php if ($flash): ?>
  <div class="alert <?= $flash['type'] === 'ok' ? 'alert-ok' : 'alert-err' ?>">
    <?= e($flash['msg']) ?>
  </div>
<?php endif; ?>

<a href="termin-bearbeiten.php" class="btn btn-primary add-link">+ Neuer Termin</a>

<?php if (empty($termine)): ?>
  <p style="color:#666;font-size:.9rem">Noch keine Termine vorhanden.</p>
<?php else: ?>
<table>
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
  <?php foreach ($termine as $t): ?>
    <tr class="<?= $t['aktiv'] ? '' : 'inactive' ?>">
      <td><strong><?= e(datum_lesbar($t['termin_datum'], $wochentage, $monate)) ?></strong></td>
      <td><?= e(zeitraum_lesbar($t['uhrzeiten'], (int)$t['slot_laenge_min'])) ?></td>
      <td><?= (int)$t['slot_laenge_min'] ?> min</td>
      <td>
        <span class="badge <?= $t['aktiv'] ? 'badge-on' : 'badge-off' ?>">
          <?= $t['aktiv'] ? 'Sichtbar' : 'Versteckt' ?>
        </span>
      </td>
      <td>
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
