<?php
require_once __DIR__ . '/auth.php';

$db = admin_db();

$flash = $_SESSION['flash'] ?? null;
unset($_SESSION['flash']);

if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}
$csrf = $_SESSION['csrf_token'];

$regionen = $db->query('SELECT id, name FROM community_regionen ORDER BY name ASC')->fetchAll();
$tags     = $db->query('SELECT id, name FROM community_tags ORDER BY name ASC')->fetchAll();

$f_region      = isset($_GET['region']) ? (int)$_GET['region'] : 0;
$f_tag         = isset($_GET['tag']) ? (int)$_GET['tag'] : 0;
$f_aktiv       = $_GET['aktiv'] ?? '';
$f_suche       = trim($_GET['q'] ?? '');

$where  = [];
$params = [];

if ($f_region > 0) {
    $where[] = 'EXISTS (SELECT 1 FROM community_organisation_regionen kr WHERE kr.organisation_id = k.id AND kr.region_id = ?)';
    $params[] = $f_region;
}
if ($f_tag > 0) {
    $where[] = 'EXISTS (SELECT 1 FROM community_organisation_tags kt WHERE kt.organisation_id = k.id AND kt.tag_id = ?)';
    $params[] = $f_tag;
}
if ($f_aktiv === '1' || $f_aktiv === '0') {
    $where[] = 'k.aktiv = ?';
    $params[] = (int)$f_aktiv;
}
if ($f_suche !== '') {
    $where[] = '(k.name LIKE ? OR k.website LIKE ? OR k.telefon LIKE ? OR k.strasse LIKE ? OR k.plz LIKE ? OR k.ort LIKE ? OR k.notizen LIKE ? OR EXISTS (SELECT 1 FROM community_personen p WHERE p.organisation_id = k.id AND (p.name LIKE ? OR p.telefon LIKE ? OR p.handy LIKE ? OR p.email LIKE ?)))';
    $like = '%' . $f_suche . '%';
    array_push($params, $like, $like, $like, $like, $like, $like, $like, $like, $like, $like, $like);
}

$sql = 'SELECT k.* FROM community_organisationen k';
if ($where) {
    $sql .= ' WHERE ' . implode(' AND ', $where);
}
$sql .= ' ORDER BY k.aktiv DESC, k.name ASC';

$stmt = $db->prepare($sql);
$stmt->execute($params);
$kontakte = $stmt->fetchAll();

// Regionen/Tags je Kontakt in einem Rutsch nachladen
$kontakt_ids = array_column($kontakte, 'id');
$regionen_je_kontakt = [];
$tags_je_kontakt = [];
$personen_je_org = [];
if ($kontakt_ids) {
    $in = implode(',', array_fill(0, count($kontakt_ids), '?'));

    $stmt = $db->prepare(
        "SELECT kr.organisation_id as kontakt_id, r.name FROM community_organisation_regionen kr
         JOIN community_regionen r ON r.id = kr.region_id
         WHERE kr.organisation_id IN ($in) ORDER BY r.name ASC"
    );
    $stmt->execute($kontakt_ids);
    foreach ($stmt->fetchAll() as $row) {
        $regionen_je_kontakt[$row['kontakt_id']][] = $row['name'];
    }

    $stmt = $db->prepare(
        "SELECT kt.organisation_id as kontakt_id, t.name, t.farbe FROM community_organisation_tags kt
         JOIN community_tags t ON t.id = kt.tag_id
         WHERE kt.organisation_id IN ($in) ORDER BY t.name ASC"
    );
    $stmt->execute($kontakt_ids);
    foreach ($stmt->fetchAll() as $row) {
        $tags_je_kontakt[$row['kontakt_id']][] = ['name' => $row['name'], 'farbe' => $row['farbe']];
    }

    $stmt = $db->prepare("SELECT * FROM community_personen WHERE organisation_id IN ($in) ORDER BY name ASC");
    $stmt->execute($kontakt_ids);
    foreach ($stmt->fetchAll() as $p) {
        $personen_je_org[$p['organisation_id']][] = $p;
    }
}
?>
<!DOCTYPE html>
<html lang="de">
<head>
<link rel="icon" href="../grafik/F%C3%BCreinander%20Freiburg.svg" type="image/svg+xml">
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title>Admin – Community</title>
<meta name="robots" content="noindex,nofollow">
<link rel="stylesheet" href="admin.css">
</head>
<body>
<div class="admin-layout">
<?php $active_nav = 'community'; require __DIR__ . '/nav.php'; ?>
<div class="admin-main">

<div class="page-head">
  <div>
    <span class="page-eyebrow">Community</span>
    <h1>Kontakte</h1>
  </div>
  <a href="community-bearbeiten.php" class="btn btn-primary add-link" style="margin-bottom:0">+ Neuer Kontakt</a>
</div>

<?php if ($flash): ?>
  <div class="alert alert-<?= e($flash['type']) ?>">
    <?= e($flash['msg']) ?>
  </div>
<?php endif; ?>

<div class="infobox-row" style="display:flex;align-items:stretch;gap:.75rem;margin-bottom:1rem;flex-wrap:wrap">
  <form method="get" style="display:flex;gap:.5rem;flex-wrap:wrap;align-items:center;flex:1">
    <input type="text" name="q" value="<?= e($f_suche) ?>" placeholder="Suche…" style="margin:0;min-width:160px;flex:1">
    <select name="region" aria-label="Nach Region filtern" style="margin:0;width:auto;flex:0 1 auto">
      <option value="0">Alle Regionen</option>
      <?php foreach ($regionen as $r): ?>
        <option value="<?= (int)$r['id'] ?>" <?= $f_region === (int)$r['id'] ? 'selected' : '' ?>><?= e($r['name']) ?></option>
      <?php endforeach; ?>
    </select>
    <select name="tag" aria-label="Nach Tag filtern" style="margin:0;width:auto;flex:0 1 auto">
      <option value="0">Alle Tags</option>
      <?php foreach ($tags as $t): ?>
        <option value="<?= (int)$t['id'] ?>" <?= $f_tag === (int)$t['id'] ? 'selected' : '' ?>><?= e($t['name']) ?></option>
      <?php endforeach; ?>
    </select>
    <select name="aktiv" aria-label="Nach Status filtern" style="margin:0;width:auto;flex:0 1 auto">
      <option value="">Alle Status</option>
      <option value="1" <?= $f_aktiv === '1' ? 'selected' : '' ?>>Nur aktive</option>
      <option value="0" <?= $f_aktiv === '0' ? 'selected' : '' ?>>Nur inaktive</option>
    </select>
    <button type="submit" class="btn btn-secondary">Filtern</button>
    <?php if ($f_region || $f_tag || $f_aktiv !== '' || $f_suche !== ''): ?>
      <a href="community.php" class="btn btn-edit">Zurücksetzen</a>
    <?php endif; ?>
  </form>
</div>

<?php if (empty($kontakte)): ?>
  <p style="color:#666;font-size:.9rem">Keine Kontakte gefunden.</p>
<?php else: ?>
<div class="community-grid">
  <?php foreach ($kontakte as $k): ?>
    <div class="community-card <?= $k['aktiv'] ? '' : 'inactive' ?>">
      <div class="community-card-accent"></div>
      <div class="community-card-body">
        <div class="community-card-header">
          <div>
            <h3 class="community-card-title"><?= e($k['name']) ?></h3>
            <?php
              $anzeige_telefon = $k['telefon'];
              if (!$anzeige_telefon && !empty($personen_je_org[$k['id']])) {
                  $erste_person = $personen_je_org[$k['id']][0];
                  $anzeige_telefon = $erste_person['telefon'] ?: $erste_person['handy'];
              }
            ?>
            <div class="community-card-meta">
              <?php if ($k['website']): ?><a href="<?= e($k['website']) ?>" target="_blank" style="color:inherit">Website</a><?php endif; ?>
              <?php if ($k['website'] && $anzeige_telefon): ?> &bull; <?php endif; ?>
              <?php if ($anzeige_telefon): ?><?= e($anzeige_telefon) ?><?php endif; ?>
              <?php $plz_ort = trim(($k['plz'] ?? '') . ' ' . ($k['ort'] ?? '')); ?>
              <?php if ($k['strasse'] || $plz_ort !== ''): ?>
                <br>
                <?php if ($k['strasse']): ?><?= e($k['strasse']) ?><?php endif; ?>
                <?php if ($k['strasse'] && $plz_ort !== ''): ?> &middot; <?php endif; ?>
                <?= e($plz_ort) ?>
              <?php endif; ?>
            </div>
          </div>
          <span class="badge <?= $k['vermittlung'] === 'direkt' ? 'badge-on' : 'badge-off' ?>" style="white-space:nowrap;">
            <?= $k['vermittlung'] === 'direkt' ? 'Direkt' : 'Über uns' ?>
          </span>
        </div>

        <?php if (!empty($personen_je_org[$k['id']])): ?>
          <div class="community-card-divider"></div>
          <div>
            <div class="community-card-section-title">Ansprechpartner</div>
            <?php foreach ($personen_je_org[$k['id']] as $idx => $p): ?>
              <?php if ($idx > 0) echo '<hr style="border:none;border-top:1px solid var(--border-light);margin:.5rem 0;">'; ?>
              <div style="display:flex;flex-direction:column;gap:.1rem;">
                <strong style="font-size:.85rem;font-weight:600;"><?= e($p['name']) ?></strong>
                <?php if ($p['email']): ?><a href="mailto:<?= e($p['email']) ?>" style="color:var(--text-muted);text-decoration:none;font-size:.8rem;"><?= e($p['email']) ?></a><?php endif; ?>
              </div>
            <?php endforeach; ?>
          </div>
        <?php endif; ?>

        <?php
          $regionen_str = [];
          if ($k['bundesweit']) $regionen_str[] = 'Bundesweit';
          if (!empty($regionen_je_kontakt[$k['id']])) {
             $regionen_str = array_merge($regionen_str, $regionen_je_kontakt[$k['id']]);
          }
          $tags_arr = $tags_je_kontakt[$k['id']] ?? [];
        ?>

        <?php if ($regionen_str || $tags_arr): ?>
          <div class="community-card-divider"></div>
          <div class="community-card-taxo">
            <?php foreach ($regionen_str as $r): ?><span class="community-chip"><?= e($r) ?></span><?php endforeach; ?>
            <?php foreach ($tags_arr as $t): ?><span class="community-chip community-chip-tag" style="--tag-farbe:<?= e($t['farbe'] ?: '#e5e7eb') ?>"><?= e($t['name']) ?></span><?php endforeach; ?>
          </div>
        <?php endif; ?>
      </div>

      <div class="community-card-footer">
        <form method="post" action="community-toggle.php" style="margin:0">
          <input type="hidden" name="csrf_token" value="<?= e($csrf) ?>">
          <input type="hidden" name="id" value="<?= (int)$k['id'] ?>">
          <button type="submit" class="community-toggle <?= $k['aktiv'] ? 'active' : '' ?>" title="Klicken zum Umschalten">
            <span class="community-toggle-track"></span>
            <?= $k['aktiv'] ? 'Aktiv' : 'Inaktiv' ?>
          </button>
        </form>
        <div class="community-card-actions">
          <?php if (trim($k['notizen'] ?? '') !== ''): ?>
            <span class="community-notiz-warn" title="Interne Notizen vorhanden">
              <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M10.29 3.86 1.82 18a2 2 0 0 0 1.71 3h16.94a2 2 0 0 0 1.71-3L13.71 3.86a2 2 0 0 0-3.42 0Z"/><line x1="12" y1="9" x2="12" y2="13"/><line x1="12" y1="17" x2="12.01" y2="17"/></svg>
            </span>
          <?php endif; ?>
          <a href="community-bearbeiten.php?id=<?= (int)$k['id'] ?>" class="community-icon-btn" title="Bearbeiten">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M12 20h9"/><path d="M16.5 3.5a2.12 2.12 0 0 1 3 3L7 19l-4 1 1-4Z"/></svg>
          </a>
          <form method="post" action="community-loeschen.php" class="loeschen-form" style="margin:0;">
            <input type="hidden" name="csrf_token" value="<?= e($csrf) ?>">
            <input type="hidden" name="id" value="<?= (int)$k['id'] ?>">
            <button type="button" class="community-icon-btn danger" title="Löschen"
                    onclick="loeschenBestaetigen(this.closest('form'), <?= e(json_encode($k['name'])) ?>)">
              <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M3 6h18"/><path d="M8 6V4a2 2 0 0 1 2-2h4a2 2 0 0 1 2 2v2"/><path d="M19 6l-1 14a2 2 0 0 1-2 2H8a2 2 0 0 1-2-2L5 6"/></svg>
            </button>
          </form>
        </div>
      </div>
    </div>
  <?php endforeach; ?>
</div>
<?php endif; ?>

</div>
</div>

<!-- Lösch-Modal -->
<div class="modal-overlay" id="loeschModal">
  <div class="modal">
    <h2>Kontakt löschen</h2>
    <p id="loeschModalText">Soll dieser Kontakt wirklich gelöscht werden?</p>
    <div class="modal-actions">
      <button class="btn btn-secondary" onclick="modalSchliessen()">Abbrechen</button>
      <button class="btn btn-danger" id="loeschBestaetigen">Ja, löschen</button>
    </div>
  </div>
</div>

<script>
let pendingForm = null;

function loeschenBestaetigen(form, name) {
  pendingForm = form;
  document.getElementById('loeschModalText').textContent = 'Soll der Kontakt „' + name + '“ wirklich gelöscht werden?';
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
