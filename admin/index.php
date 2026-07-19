<?php
require_once __DIR__ . '/auth.php';

$db = admin_db();

$stmt = $db->query("SELECT wert FROM statistiken WHERE name = 'seitenaufrufe'");
$seitenaufrufe = (int) $stmt->fetchColumn();

$stmt = $db->query(
    "SELECT COUNT(*) FROM gruppentermine WHERE termin_datum >= CURRENT_DATE AND aktiv = 1"
);
$anzahl_kommende = (int) $stmt->fetchColumn();

$stmt = $db->query(
    "SELECT termin_datum, uhrzeiten, slot_laenge_min
     FROM gruppentermine
     WHERE termin_datum >= CURRENT_DATE AND aktiv = 1
     ORDER BY termin_datum ASC, uhrzeiten ASC
     LIMIT 1"
);
$naechster_termin = $stmt->fetch();

$stmt = $db->query("SELECT COUNT(*) FROM community_organisationen WHERE aktiv = 1");
$anzahl_kontakte = (int) $stmt->fetchColumn();

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
<link rel="icon" href="../grafik/F%C3%BCreinander%20Freiburg.svg" type="image/svg+xml">
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title>Admin – Dashboard</title>
<meta name="robots" content="noindex,nofollow">
<link rel="stylesheet" href="admin.css">
</head>
<body>
<div class="admin-layout">
<?php $active_nav = 'dashboard'; require __DIR__ . '/nav.php'; ?>
<div class="admin-main">

<div class="page-head">
  <div>
    <span class="page-eyebrow">Übersicht</span>
    <h1>Dashboard</h1>
  </div>
</div>

<div class="dashboard-grid">

  <a href="termine.php" class="dashboard-tile dashboard-tile-link">
    <div class="dashboard-tile-accent"></div>
    <div class="dashboard-tile-body">
      <span class="dashboard-tile-label">Nächster Termin</span>
      <?php if ($naechster_termin): ?>
        <span class="dashboard-tile-value dashboard-tile-value-sm"><?= e(datum_lesbar($naechster_termin['termin_datum'], $wochentage, $monate)) ?></span>
        <span class="dashboard-tile-sub"><?= e(zeitraum_lesbar($naechster_termin['uhrzeiten'], (int)$naechster_termin['slot_laenge_min'])) ?></span>
      <?php else: ?>
        <span class="dashboard-tile-value dashboard-tile-value-sm">–</span>
        <span class="dashboard-tile-sub">Kein kommender Termin</span>
      <?php endif; ?>
      <span class="dashboard-tile-footer"><?= number_format($anzahl_kommende, 0, ',', '.') ?> kommende Termine &rsaquo; Terminverwaltung</span>
    </div>
  </a>

  <a href="community.php" class="dashboard-tile dashboard-tile-link">
    <div class="dashboard-tile-accent"></div>
    <div class="dashboard-tile-body">
      <span class="dashboard-tile-label">Aktive Community-Kontakte</span>
      <span class="dashboard-tile-value"><?= number_format($anzahl_kontakte, 0, ',', '.') ?></span>
      <span class="dashboard-tile-footer">Kontakte verwalten &rsaquo;</span>
    </div>
  </a>

  <div class="dashboard-tile">
    <div class="dashboard-tile-accent"></div>
    <div class="dashboard-tile-body">
      <span class="dashboard-tile-label">Seitenaufrufe</span>
      <span class="dashboard-tile-value"><?= number_format($seitenaufrufe, 0, ',', '.') ?></span>
    </div>
  </div>

</div>

<div class="dashboard-links">
  <a href="community-tags.php" class="dashboard-link-card">
    <span class="crm-icon" aria-hidden="true"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M12.586 2.586A2 2 0 0 0 11.172 2H4a2 2 0 0 0-2 2v7.172a2 2 0 0 0 .586 1.414l8.704 8.704a2.426 2.426 0 0 0 3.42 0l6.58-6.58a2.426 2.426 0 0 0 0-3.42z"/><circle cx="7.5" cy="7.5" r="1.5"/></svg></span>
    <div>
      <strong>Verwaltung</strong>
      <span>Tags und Regionen</span>
    </div>
  </a>
  <a href="profil.php" class="dashboard-link-card">
    <span class="crm-icon" aria-hidden="true"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M19 21v-2a4 4 0 0 0-4-4H9a4 4 0 0 0-4 4v2"/><circle cx="12" cy="7" r="4"/></svg></span>
    <div>
      <strong>Profil</strong>
      <span>Konto und 2FA</span>
    </div>
  </a>
</div>

</div>
</div>
</body>
</html>
