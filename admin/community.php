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
$f_vermittlung = $_GET['vermittlung'] ?? '';
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
if (in_array($f_vermittlung, ['direkt', 'ueber_uns'], true)) {
    $where[] = 'k.vermittlung = ?';
    $params[] = $f_vermittlung;
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
        "SELECT kt.organisation_id as kontakt_id, t.name FROM community_organisation_tags kt
         JOIN community_tags t ON t.id = kt.tag_id
         WHERE kt.organisation_id IN ($in) ORDER BY t.name ASC"
    );
    $stmt->execute($kontakt_ids);
    foreach ($stmt->fetchAll() as $row) {
        $tags_je_kontakt[$row['kontakt_id']][] = $row['name'];
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
  <div class="alert <?= $flash['type'] === 'ok' ? 'alert-ok' : 'alert-err' ?>">
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
    <select name="vermittlung" aria-label="Nach Vermittlungsart filtern" style="margin:0;width:auto;flex:0 1 auto">
      <option value="">Direkt &amp; über uns</option>
      <option value="direkt" <?= $f_vermittlung === 'direkt' ? 'selected' : '' ?>>Direkt weitergebbar</option>
      <option value="ueber_uns" <?= $f_vermittlung === 'ueber_uns' ? 'selected' : '' ?>>Nur über uns</option>
    </select>
    <button type="submit" class="btn btn-secondary">Filtern</button>
    <?php if ($f_region || $f_tag || $f_vermittlung !== '' || $f_suche !== ''): ?>
      <a href="community.php" class="btn btn-edit">Zurücksetzen</a>
    <?php endif; ?>
  </form>
</div>

<?php if (empty($kontakte)): ?>
  <p style="color:#666;font-size:.9rem">Keine Kontakte gefunden.</p>
<?php else: ?>
<table class="termine-table">
  <thead>
    <tr>
      <th>Name</th>
      <th>Kontakt</th>
      <th>Regionen</th>
      <th>Tags</th>
      <th>Vermittlung</th>
      <th>Status</th>
      <th>Aktionen</th>
    </tr>
  </thead>
  <tbody>
  <?php foreach ($kontakte as $k): ?>
    <tr class="<?= $k['aktiv'] ? '' : 'inactive' ?>">
      <td data-label="Name">
        <strong><?= e($k['name']) ?></strong>
        <?php if ($k['website']): ?><br><a href="<?= e($k['website']) ?>" target="_blank" style="font-size:.8rem;color:inherit;text-decoration:underline">Website</a><?php endif; ?>
        <?php if ($k['telefon']): ?><br><span style="font-size:.8rem;color:#666"><?= e($k['telefon']) ?></span><?php endif; ?>
        <?php $plz_ort = trim(($k['plz'] ?? '') . ' ' . ($k['ort'] ?? '')); ?>
        <?php if ($k['strasse'] || $plz_ort !== ''): ?>
          <div style="font-size:.8rem;color:#666;margin-top:.2rem">
            <?php if ($k['strasse']): ?><?= e($k['strasse']) ?><br><?php endif; ?>
            <?= e($plz_ort) ?>
          </div>
        <?php endif; ?>
      </td>
      <td data-label="Kontakt" style="font-size:.8rem">
        <?php if (empty($personen_je_org[$k['id']])): ?>
          <span style="color:#888">Keine Personen</span>
        <?php else: ?>
          <?php foreach ($personen_je_org[$k['id']] as $p): ?>
            <div style="margin-bottom:.5rem">
              <strong><?= e($p['name']) ?></strong><br>
              <?php if ($p['telefon']): ?><?= e($p['telefon']) ?><br><?php endif; ?>
              <?php if ($p['handy']): ?><?= e($p['handy']) ?> (Mobil)<br><?php endif; ?>
              <?php if ($p['email']): ?><a href="mailto:<?= e($p['email']) ?>" style="color:inherit;text-decoration:underline"><?= e($p['email']) ?></a><?php endif; ?>
            </div>
          <?php endforeach; ?>
        <?php endif; ?>
      </td>
      <td data-label="Regionen" style="font-size:.8rem">
        <?= $k['bundesweit'] ? '<strong>Bundesweit</strong>' : '' ?>
        <?php if (!empty($regionen_je_kontakt[$k['id']])): ?>
          <?= $k['bundesweit'] ? '<br>' : '' ?><?= e(implode(', ', $regionen_je_kontakt[$k['id']])) ?>
        <?php endif; ?>
      </td>
      <td data-label="Tags" style="font-size:.8rem"><?= e(implode(', ', $tags_je_kontakt[$k['id']] ?? [])) ?></td>
      <td data-label="Vermittlung">
        <span class="badge <?= $k['vermittlung'] === 'direkt' ? 'badge-on' : 'badge-off' ?>">
          <?= $k['vermittlung'] === 'direkt' ? 'Direkt' : 'Über uns' ?>
        </span>
      </td>
      <td data-label="Status">
        <form method="post" action="community-toggle.php" style="margin:0">
          <input type="hidden" name="csrf_token" value="<?= e($csrf) ?>">
          <input type="hidden" name="id" value="<?= (int)$k['id'] ?>">
          <button type="submit" class="toggle-switch <?= $k['aktiv'] ? 'active' : '' ?>" title="Klicken zum Umschalten">
            <span class="toggle-track"><span class="toggle-knob"></span></span>
            <span class="toggle-label"><?= $k['aktiv'] ? 'Aktiv' : 'Inaktiv' ?></span>
          </button>
        </form>
      </td>
      <td data-label="Aktionen">
        <div class="actions">
          <a href="community-bearbeiten.php?id=<?= (int)$k['id'] ?>" class="btn btn-edit">Bearbeiten</a>
        </div>
      </td>
    </tr>
  <?php endforeach; ?>
  </tbody>
</table>
<?php endif; ?>

</div>
</div>
</body>
</html>
