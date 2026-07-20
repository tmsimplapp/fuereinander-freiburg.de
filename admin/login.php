<?php
require_once __DIR__ . '/config.php';

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Bereits eingeloggt → direkt weiterleiten
if (!empty($_SESSION['admin_logged_in'])) {
    header('Location: index.php');
    exit;
}

$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    $username = trim($_POST['username'] ?? '');
    $password = $_POST['password'] ?? '';

    // Basis-Validierung – verhindert sinnlose DB-Abfragen
    if (
        strlen($username) < 1 || strlen($username) > 64
        || !preg_match('/^[a-zA-Z0-9._\-!]+$/', $username)
        || strlen($password) < 1 || strlen($password) > 1024
    ) {
        $error = 'Ungültige Eingabe.';
    } else {

        $db = admin_db();
        $ip_hash = hash('sha256', $_SERVER['REMOTE_ADDR'] ?? '');

        // Brute-Force-Prüfung
        // Tabellennamen und Intervall kommen aus define()-Konstanten (kein User-Input)
        $stmt = $db->prepare(
            'SELECT COUNT(*) FROM admin_rate_limit
             WHERE ip_hash = ? AND erstellt_am > DATE_SUB(NOW(), INTERVAL ' . BRUTE_WINDOW_SECONDS . ' SECOND)'
        );
        $stmt->execute([$ip_hash]);
        $attempts = (int) $stmt->fetchColumn();

        if ($attempts >= BRUTE_MAX_ATTEMPTS) {
            $error = 'Zu viele Fehlversuche. Bitte später erneut versuchen.';
        } else {

            // Admin laden
            $stmt = $db->prepare('SELECT id, password_hash, totp_enabled FROM admins WHERE username = ? LIMIT 1');
            $stmt->execute([$username]);
            $row = $stmt->fetch();

            if ($row && password_verify($password, $row['password_hash'])) {
                // Fehlversuche dieser IP löschen
                $db->prepare('DELETE FROM admin_rate_limit WHERE ip_hash = ?')->execute([$ip_hash]);

                session_regenerate_id(true);
                $_SESSION['admin_last_active'] = time();

                // 2FA aktiv? → Pre-Auth-Zustand, noch KEIN admin_logged_in setzen
                if (!empty($row['totp_enabled'])) {
                    $_SESSION['pre_2fa_id']   = (int) $row['id'];
                    $_SESSION['totp_pending'] = true;
                    header('Location: login_2fa.php');
                    exit;
                }

                // Kein 2FA → direkt einloggen
                $_SESSION['admin_logged_in']  = true;
                $_SESSION['admin_id']         = (int) $row['id'];

                header('Location: index.php');
                exit;
            } else {
                // Fehlversuch speichern
                $db->prepare('INSERT INTO admin_rate_limit (ip_hash) VALUES (?)')->execute([$ip_hash]);
                // Bewusst identische Fehlermeldung für falschen Nutzer und falsches Passwort
                $error = 'Benutzername oder Passwort falsch.';
            }
        }
    }
}

$timeout = isset($_GET['timeout']);
?>
<?php
$page_title = 'Admin – Anmelden';
$layout_type = 'login';
$extra_head = '<style>button[type=submit]{margin-top:1.5rem;width:100%;min-height:48px;font-size:1rem}</style>';
require __DIR__ . '/header.php';
?>
<div class="login-card">
  <div class="login-logo">
    <img src="../grafik/F%C3%BCreinander%20Freiburg.svg" alt="Füreinander Freiburg Logo">
    <span>Admin</span>
  </div>

  <?php if ($timeout): ?>
    <div class="alert alert-info">Sitzung abgelaufen. Bitte neu anmelden.</div>
  <?php endif; ?>

  <?php if ($error !== ''): ?>
    <div class="alert alert-err"><?= e($error) ?></div>
  <?php endif; ?>

  <form method="post" autocomplete="off" novalidate>
    <label for="username">Benutzername</label>
    <input type="text" id="username" name="username" required
           autocomplete="username" maxlength="64"
           value="<?= isset($_POST['username']) ? e($_POST['username']) : '' ?>">

    <label for="password">Passwort</label>
    <input type="password" id="password" name="password" required
           autocomplete="current-password" maxlength="1024">

    <button type="submit" class="btn btn-primary">Anmelden</button>
  </form>
</div>
<?php 
if ($error !== '') {
    ob_start();
?>
<script>document.getElementById('username').focus();</script>
<?php
    $extra_scripts = ob_get_clean();
}
require __DIR__ . '/footer.php';
?>
