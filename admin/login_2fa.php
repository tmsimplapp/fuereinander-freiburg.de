<?php
require_once __DIR__ . '/config.php';

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Nur nach erfolgreichem Passwort-Login erreichbar (pre_2fa_id statt admin_logged_in)
if (empty($_SESSION['totp_pending']) || empty($_SESSION['pre_2fa_id'])) {
    header('Location: login.php');
    exit;
}

$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $code = trim($_POST['code'] ?? '');
    $db   = admin_db();

    // Rate-Limit für 2FA (eigener Identifier)
    $ip_hash = hash('sha256', ($_SERVER['REMOTE_ADDR'] ?? '') . ':2fa');
    $stmt = $db->prepare(
        'SELECT COUNT(*) FROM admin_rate_limit
         WHERE ip_hash = ? AND erstellt_am > DATE_SUB(NOW(), INTERVAL ' . BRUTE_WINDOW_SECONDS . ' SECOND)'
    );
    $stmt->execute([$ip_hash]);
    if ((int)$stmt->fetchColumn() >= BRUTE_MAX_ATTEMPTS) {
        session_unset();
        session_destroy();
        header('Location: login.php?timeout=1');
        exit;
    }

    $stmt = $db->prepare('SELECT totp_secret, totp_backup_codes, totp_last_used_slice FROM admins WHERE id = ? LIMIT 1');
    $stmt->execute([$_SESSION['pre_2fa_id']]);
    $admin = $stmt->fetch();

    $verified = false;

    if ($admin && strlen($code) === 6 && ctype_digit($code)) {
        // TOTP prüfen
        $slice = totp_verify($admin['totp_secret'], $code);
        if ($slice !== false) {
            // Replay-Schutz: atomares UPDATE verhindert Race Condition bei parallelen Requests
            $stmt = $db->prepare(
                'UPDATE admins SET totp_last_used_slice = ?
                 WHERE id = ? AND COALESCE(totp_last_used_slice, 0) < ?'
            );
            $stmt->execute([$slice, $_SESSION['pre_2fa_id'], $slice]);
            if ($stmt->rowCount() === 1) {
                $verified = true;
            } else {
                $error = 'Code bereits verwendet. Bitte warten und neuen Code eingeben.';
            }
        }
    }

    // Backup-Code prüfen (16 hex-Zeichen; 10 nur für vor der Umstellung generierte Codes)
    if (!$verified && $error === '' && (strlen($code) === 16 || strlen($code) === 10)) {
        $codes = $admin ? json_decode($admin['totp_backup_codes'] ?? '[]', true) : [];
        $code_normalized = strtolower(trim($code));
        $code_hash = hash('sha256', $code_normalized);
        foreach ($codes as $idx => $stored_hash) {
            if (hash_equals($stored_hash, $code_hash)) {
                unset($codes[$idx]);
                $db->prepare('UPDATE admins SET totp_backup_codes = ? WHERE id = ?')
                   ->execute([json_encode(array_values($codes)), $_SESSION['pre_2fa_id']]);
                $verified = true;
                break;
            }
        }
    }

    if ($verified) {
        $db->prepare('DELETE FROM admin_rate_limit WHERE ip_hash = ?')->execute([$ip_hash]);
        $admin_id = (int) $_SESSION['pre_2fa_id'];
        session_regenerate_id(true);
        unset($_SESSION['totp_pending'], $_SESSION['pre_2fa_id']);
        $_SESSION['admin_logged_in']  = true;
        $_SESSION['admin_id']         = $admin_id;
        $_SESSION['admin_last_active'] = time();
        header('Location: termine.php');
        exit;
    }

    if ($error === '') {
        $db->prepare('INSERT INTO admin_rate_limit (ip_hash) VALUES (?)')->execute([$ip_hash]);
        $error = 'Ungültiger Code. Bitte erneut versuchen.';
    }
}
?>
<!DOCTYPE html>
<html lang="de">
<head>
<link rel="icon" href="../grafik/F%C3%BCreinander%20Freiburg.svg" type="image/svg+xml">
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title>Admin – 2-Faktor-Authentifizierung</title>
<meta name="robots" content="noindex,nofollow">
<link rel="stylesheet" href="admin.css">
<style>
button[type=submit]{margin-top:1.5rem;width:100%;min-height:48px;font-size:1rem}
input[name=code]{letter-spacing:.25em;font-size:1.4rem;text-align:center}
.backup-toggle{font-size:.8rem;color:#888;text-decoration:underline;cursor:pointer;background:none;border:none;padding:0;margin-top:.75rem;display:block}
</style>
</head>
<body class="login-wrap">
<div class="login-card">
  <div class="login-logo">
    <img src="../grafik/F%C3%BCreinander%20Freiburg.svg" alt="Füreinander Freiburg Logo">
    <span>Admin</span>
  </div>

  <h2 style="font-size:1rem;margin-bottom:.25rem">2-Faktor-Authentifizierung</h2>
  <p style="font-size:.85rem;color:#666;margin-bottom:1rem">Code aus der Authenticator-App eingeben.</p>

  <?php if ($error !== ''): ?>
    <div class="alert alert-err"><?= e($error) ?></div>
  <?php endif; ?>

  <form method="post" autocomplete="off" id="totp-form">
    <label for="code">Code</label>
    <input type="text" id="code" name="code" data-mode="totp"
           inputmode="numeric" pattern="[0-9]{6}"
           maxlength="6" required autofocus autocomplete="one-time-code">
    <p class="hint" id="code-hint">6-stelliger TOTP-Code</p>

    <button type="submit" class="btn btn-primary">Bestätigen</button>
  </form>

  <button class="backup-toggle" onclick="toggleBackup()">Backup-Code verwenden</button>

  <p style="margin-top:1.5rem;font-size:.8rem;text-align:center">
    <a href="login.php" style="color:#888">Zurück zur Anmeldung</a>
  </p>
</div>
<script>
function toggleBackup() {
  const inp  = document.getElementById('code');
  const hint = document.getElementById('code-hint');
  const btn  = document.querySelector('.backup-toggle');
  if (inp.dataset.mode === 'totp') {
    inp.dataset.mode = 'backup';
    inp.pattern = '[0-9a-fA-F]{10}|[0-9a-fA-F]{16}';
    inp.maxLength = 16;
    inp.placeholder = 'xxxxxxxxxxxxxxxx';
    hint.textContent = 'Backup-Code (10 oder 16 Zeichen)';
    btn.textContent = 'TOTP-Code verwenden';
    inp.style.letterSpacing = '.15em';
  } else {
    inp.dataset.mode = 'totp';
    inp.pattern = '[0-9]{6}';
    inp.maxLength = 6;
    inp.placeholder = '';
    hint.textContent = '6-stelliger TOTP-Code';
    btn.textContent = 'Backup-Code verwenden';
    inp.style.letterSpacing = '.25em';
  }
  inp.value = '';
  inp.focus();
}
</script>
</body>
</html>
