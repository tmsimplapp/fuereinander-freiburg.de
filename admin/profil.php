<?php
require_once __DIR__ . '/auth.php';

$db = admin_db();

if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}
$csrf   = $_SESSION['csrf_token'];
$errors = [];
$success = false;

// Aktuellen Admin laden
$stmt = $db->prepare('SELECT id, username, totp_enabled, totp_backup_codes FROM admins WHERE id = ? LIMIT 1');
$stmt->execute([$_SESSION['admin_id']]);
$admin = $stmt->fetch();

if (!$admin) {
    session_destroy();
    header('Location: login.php');
    exit;
}

$totp_action        = '';
$totp_setup_data    = null;
$totp_backup_new    = null;
$totp_success       = '';
$totp_error         = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    if (!hash_equals($csrf, $_POST['csrf_token'] ?? '')) {
        http_response_code(403);
        die('Ungültige Anfrage.');
    }

    $totp_action = $_POST['totp_action'] ?? '';

    // ── 2FA einrichten: QR anzeigen ──
    if ($totp_action === 'setup_init') {
        $secret = totp_generate_secret(16);
        $_SESSION['totp_setup_secret'] = $secret;
        $qr_url = totp_qr_url($admin['username'], $secret);
        $totp_setup_data = ['secret' => $secret, 'qr_url' => $qr_url];

    // ── 2FA aktivieren: Code bestätigen ──
    } elseif ($totp_action === 'setup_confirm') {
        $code   = trim($_POST['totp_code'] ?? '');
        $secret = $_SESSION['totp_setup_secret'] ?? '';
        if (!$secret) {
            $totp_error = 'Setup-Sitzung abgelaufen. Bitte neu starten.';
        } elseif (!ctype_digit($code) || strlen($code) !== 6) {
            $totp_error = 'Ungültiger Code.';
        } else {
            $slice = totp_verify($secret, $code);
            if ($slice === false) {
                $totp_error = 'Code stimmt nicht. Bitte erneut versuchen.';
                // Secret für erneuten Versuch erhalten
                $qr_url = totp_qr_url($admin['username'], $secret);
                $totp_setup_data = ['secret' => $secret, 'qr_url' => $qr_url];
            } else {
                // 10 Backup-Codes generieren
                $plain_codes  = [];
                $hashed_codes = [];
                for ($i = 0; $i < 10; $i++) {
                    $c = bin2hex(random_bytes(8));
                    $plain_codes[]  = $c;
                    $hashed_codes[] = hash('sha256', strtolower(trim($c)));
                }
                $db->prepare(
                    'UPDATE admins SET totp_secret=?, totp_enabled=1, totp_backup_codes=?, totp_last_used_slice=NULL WHERE id=?'
                )->execute([$secret, json_encode($hashed_codes), $_SESSION['admin_id']]);
                unset($_SESSION['totp_setup_secret']);
                $admin['totp_enabled'] = 1;
                $totp_backup_new = $plain_codes;
                $totp_success = '2FA erfolgreich aktiviert.';
            }
        }

    // ── Backup-Codes neu generieren ──
    } elseif ($totp_action === 'regen_backup') {
        $pw = $_POST['confirm_password'] ?? '';
        $stmt = $db->prepare('SELECT password_hash FROM admins WHERE id = ? LIMIT 1');
        $stmt->execute([$_SESSION['admin_id']]);
        $row = $stmt->fetch();
        if (!$row || !password_verify($pw, $row['password_hash'])) {
            $totp_error = 'Passwort falsch.';
        } else {
            $plain_codes  = [];
            $hashed_codes = [];
            for ($i = 0; $i < 10; $i++) {
                $c = bin2hex(random_bytes(5));
                $plain_codes[]  = $c;
                $hashed_codes[] = hash('sha256', strtolower(trim($c)));
            }
            $db->prepare('UPDATE admins SET totp_backup_codes=? WHERE id=?')
               ->execute([json_encode($hashed_codes), $_SESSION['admin_id']]);
            $totp_backup_new = $plain_codes;
            $totp_success = 'Backup-Codes neu generiert.';
        }

    // ── 2FA deaktivieren ──
    } elseif ($totp_action === 'disable') {
        $pw = $_POST['confirm_password'] ?? '';
        $stmt = $db->prepare('SELECT password_hash FROM admins WHERE id = ? LIMIT 1');
        $stmt->execute([$_SESSION['admin_id']]);
        $row = $stmt->fetch();
        if (!$row || !password_verify($pw, $row['password_hash'])) {
            $totp_error = 'Passwort falsch.';
        } else {
            $db->prepare(
                'UPDATE admins SET totp_secret=NULL, totp_enabled=0, totp_backup_codes=NULL, totp_last_used_slice=NULL WHERE id=?'
            )->execute([$_SESSION['admin_id']]);
            $admin['totp_enabled'] = 0;
            $totp_success = '2FA wurde deaktiviert.';
        }

    // ── Profil-Daten ändern ──
    } else {
        $neuer_username     = trim($_POST['username'] ?? '');
        $aktuelles_passwort = $_POST['current_password'] ?? '';
        $neues_passwort     = $_POST['new_password'] ?? '';
        $passwort_wdh       = $_POST['new_password_confirm'] ?? '';

        if (strlen($neuer_username) < 1 || strlen($neuer_username) > 64) {
            $errors[] = 'Benutzername muss 1–64 Zeichen lang sein.';
        } elseif (!preg_match('/^[a-zA-Z0-9._\-!]+$/', $neuer_username)) {
            $errors[] = 'Benutzername enthält ungültige Zeichen.';
        }

        if (empty($aktuelles_passwort)) {
            $errors[] = 'Aktuelles Passwort ist erforderlich.';
        } else {
            $stmt = $db->prepare('SELECT password_hash FROM admins WHERE id = ? LIMIT 1');
            $stmt->execute([$_SESSION['admin_id']]);
            $row = $stmt->fetch();
            if (!$row || !password_verify($aktuelles_passwort, $row['password_hash'])) {
                $errors[] = 'Aktuelles Passwort ist falsch.';
            }
        }

        $passwort_aendern = $neues_passwort !== '';
        if ($passwort_aendern) {
            if (strlen($neues_passwort) < 12) {
                $errors[] = 'Neues Passwort muss mindestens 12 Zeichen lang sein.';
            } elseif ($neues_passwort !== $passwort_wdh) {
                $errors[] = 'Neues Passwort und Wiederholung stimmen nicht überein.';
            }
        }

        if (empty($errors)) {
            if ($passwort_aendern) {
                $hash = password_hash($neues_passwort, PASSWORD_BCRYPT, ['cost' => 12]);
                $stmt = $db->prepare('UPDATE admins SET username = ?, password_hash = ? WHERE id = ?');
                $stmt->execute([$neuer_username, $hash, $_SESSION['admin_id']]);
            } else {
                $stmt = $db->prepare('UPDATE admins SET username = ? WHERE id = ?');
                $stmt->execute([$neuer_username, $_SESSION['admin_id']]);
            }
            $admin['username'] = $neuer_username;
            $success = true;
        }
    }
}

$backup_count = 0;
if (!empty($admin['totp_enabled']) && !$totp_backup_new) {
    $codes = json_decode($admin['totp_backup_codes'] ?? '[]', true);
    $backup_count = is_array($codes) ? count($codes) : 0;
}
?>
<!DOCTYPE html>
<html lang="de">
<head>
<link rel="icon" href="../grafik/F%C3%BCreinander%20Freiburg.svg" type="image/svg+xml">
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title>Admin – Profil</title>
<meta name="robots" content="noindex,nofollow">
<link rel="stylesheet" href="admin.css">
</head>
<body>

<div class="topbar">
  <div class="topbar-brand">
    <img src="../grafik/F%C3%BCreinander%20Freiburg.svg" alt="Logo">
    <h1>Profil</h1>
  </div>
  <div class="topbar-nav">
    <a href="termine.php" class="nav-link">Termine</a>
    <form method="post" action="logout.php">
      <button type="submit" class="btn-logout">Abmelden</button>
    </form>
  </div>
</div>

<div class="card">
  <h2>Profil bearbeiten</h2>

  <?php if ($success): ?>
    <div class="alert alert-ok">Änderungen gespeichert.</div>
  <?php endif; ?>

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

  <form method="post" autocomplete="off">
    <input type="hidden" name="csrf_token" value="<?= e($csrf) ?>">

    <label for="username">Benutzername</label>
    <input type="text" id="username" name="username" required maxlength="64"
           value="<?= e($admin['username']) ?>" autocomplete="username">

    <hr class="divider">

    <label for="current_password">Aktuelles Passwort <span style="color:#991b1b">*</span></label>
    <input type="password" id="current_password" name="current_password" required
           autocomplete="current-password" maxlength="1024">
    <p class="hint">Zur Bestätigung aller Änderungen erforderlich.</p>

    <label for="new_password">Neues Passwort <span style="color:#aaa;font-weight:400">(leer lassen = nicht ändern)</span></label>
    <input type="password" id="new_password" name="new_password"
           autocomplete="new-password" maxlength="1024">
    <p class="hint">Mindestens 12 Zeichen.</p>

    <label for="new_password_confirm">Neues Passwort wiederholen</label>
    <input type="password" id="new_password_confirm" name="new_password_confirm"
           autocomplete="new-password" maxlength="1024">

    <div class="form-actions">
      <button type="submit" class="btn btn-primary">Speichern</button>
      <a href="termine.php" class="btn btn-secondary">Abbrechen</a>
    </div>
  </form>
</div>

<!-- ── 2FA-Bereich ── -->
<div class="card" style="margin-top:1.5rem">
  <h2 style="margin-bottom:.25rem">2-Faktor-Authentifizierung</h2>
  <p style="font-size:.85rem;color:#666;margin-bottom:1rem">
    Schütze deinen Account mit einem TOTP-Authenticator (z.&nbsp;B. Google Authenticator, Aegis, Bitwarden).
  </p>

  <?php if ($totp_success !== ''): ?>
    <div class="alert alert-ok"><?= e($totp_success) ?></div>
  <?php endif; ?>
  <?php if ($totp_error !== ''): ?>
    <div class="alert alert-err"><?= e($totp_error) ?></div>
  <?php endif; ?>

  <?php if (!empty($admin['totp_enabled'])): ?>
    <p>Status: <span class="badge badge-on">Aktiv</span>
       &nbsp;<span style="font-size:.8rem;color:#888"><?= $backup_count ?> Backup-Code<?= $backup_count !== 1 ? 's' : '' ?> verbleibend</span></p>

    <?php if ($totp_backup_new): ?>
      <div class="alert alert-info" style="margin-top:1rem">
        <strong>Neue Backup-Codes — jetzt sichern, werden nicht erneut angezeigt:</strong>
        <ul style="margin-top:.5rem;margin-left:1rem;font-family:monospace;font-size:.95rem">
          <?php foreach ($totp_backup_new as $bc): ?>
            <li><?= e($bc) ?></li>
          <?php endforeach; ?>
        </ul>
      </div>
    <?php endif; ?>

    <hr class="divider">

    <!-- Backup-Codes neu generieren -->
    <form method="post" autocomplete="off" style="margin-bottom:1.5rem">
      <input type="hidden" name="csrf_token" value="<?= e($csrf) ?>">
      <input type="hidden" name="totp_action" value="regen_backup">
      <label for="regen_pw">Backup-Codes neu generieren</label>
      <input type="password" id="regen_pw" name="confirm_password"
             placeholder="Aktuelles Passwort zur Bestätigung" required maxlength="1024"
             autocomplete="current-password">
      <div class="form-actions">
        <button type="submit" class="btn btn-secondary">Neue Codes generieren</button>
      </div>
    </form>

    <!-- 2FA deaktivieren -->
    <form method="post" autocomplete="off" id="disable-2fa-form" style="display:none">
      <input type="hidden" name="csrf_token" value="<?= e($csrf) ?>">
      <input type="hidden" name="totp_action" value="disable">
      <label for="disable_pw">Passwort zur Bestätigung</label>
      <input type="password" id="disable_pw" name="confirm_password"
             required maxlength="1024" autocomplete="current-password">
      <div class="form-actions">
        <button type="submit" class="btn btn-danger">2FA jetzt deaktivieren</button>
        <button type="button" class="btn btn-secondary" onclick="document.getElementById('disable-2fa-form').style.display='none';document.getElementById('disable-2fa-toggle').style.display='inline-flex'">Abbrechen</button>
      </div>
    </form>
    <button id="disable-2fa-toggle" class="btn btn-danger" onclick="this.style.display='none';document.getElementById('disable-2fa-form').style.display='block'">2FA deaktivieren</button>

  <?php elseif ($totp_setup_data): ?>
    <!-- Setup-Schritt 2: QR scannen + Code eingeben -->
    <p style="font-size:.85rem;margin-bottom:1rem">Scanne den QR-Code mit deiner Authenticator-App und gib dann den 6-stelligen Code ein.</p>

    <div id="qr-container" style="text-align:center;margin:1rem 0">
      <canvas id="qr-canvas" style="border-radius:8px"></canvas>
    </div>

    <details style="margin-bottom:1rem;font-size:.8rem;color:#888">
      <summary>Manuellen Schlüssel anzeigen</summary>
      <code style="display:block;margin-top:.5rem;word-break:break-all;font-size:.9rem"><?= e($totp_setup_data['secret']) ?></code>
    </details>

    <form method="post" autocomplete="off">
      <input type="hidden" name="csrf_token" value="<?= e($csrf) ?>">
      <input type="hidden" name="totp_action" value="setup_confirm">
      <label for="totp_code">Code aus Authenticator-App</label>
      <input type="text" id="totp_code" name="totp_code"
             inputmode="numeric" pattern="[0-9]{6}" maxlength="6"
             required autofocus autocomplete="one-time-code"
             style="letter-spacing:.25em;font-size:1.4rem;text-align:center">
      <div class="form-actions">
        <button type="submit" class="btn btn-primary">Bestätigen &amp; aktivieren</button>
      </div>
    </form>

    <script>
    // QR-Code lokal rendern (kein externer Dienst)
    (function(){
      const otpauth = <?= json_encode($totp_setup_data['qr_url'], JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT) ?>;
      const script = document.createElement('script');
      script.src = 'https://cdn.jsdelivr.net/npm/qrcodejs@1.0.0/qrcode.min.js';
      script.onload = function() {
        new QRCode(document.getElementById('qr-container'), {
          text: otpauth,
          width: 200, height: 200,
          colorDark: '#1a2820', colorLight: '#ffffff',
          correctLevel: QRCode.CorrectLevel.M
        });
        document.getElementById('qr-canvas') && (document.getElementById('qr-canvas').style.display='none');
      };
      document.head.appendChild(script);
    })();
    </script>

  <?php else: ?>
    <!-- 2FA noch nicht eingerichtet -->
    <p>Status: <span class="badge badge-off">Inaktiv</span></p>
    <form method="post" autocomplete="off" style="margin-top:1rem">
      <input type="hidden" name="csrf_token" value="<?= e($csrf) ?>">
      <input type="hidden" name="totp_action" value="setup_init">
      <button type="submit" class="btn btn-primary">2FA einrichten</button>
    </form>
  <?php endif; ?>
</div>

</body>
</html>
