<?php
require_once __DIR__ . '/config.php';

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Bereits eingeloggt → direkt weiterleiten
if (!empty($_SESSION['admin_logged_in'])) {
    header('Location: termine.php');
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
            $stmt = $db->prepare('SELECT id, password_hash FROM admins WHERE username = ? LIMIT 1');
            $stmt->execute([$username]);
            $row = $stmt->fetch();

            if ($row && password_verify($password, $row['password_hash'])) {
                // Fehlversuche dieser IP löschen
                $db->prepare('DELETE FROM admin_rate_limit WHERE ip_hash = ?')->execute([$ip_hash]);

                session_regenerate_id(true);
                $_SESSION['admin_logged_in']  = true;
                $_SESSION['admin_id']         = (int) $row['id'];
                $_SESSION['admin_last_active'] = time();

                header('Location: termine.php');
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
<!DOCTYPE html>
<html lang="de">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title>Admin – Anmelden</title>
<meta name="robots" content="noindex,nofollow">
<style>
*{box-sizing:border-box;margin:0;padding:0}
body{font-family:system-ui,sans-serif;background:#f4f4f4;display:flex;align-items:center;justify-content:center;min-height:100vh;padding:1rem}
.card{background:#fff;border-radius:8px;padding:2rem;width:100%;max-width:360px;box-shadow:0 2px 8px rgba(0,0,0,.12)}
h1{font-size:1.2rem;margin-bottom:1.5rem;color:#222}
label{display:block;font-size:.85rem;color:#555;margin-bottom:.25rem;margin-top:1rem}
input{width:100%;padding:.55rem .75rem;border:1px solid #ccc;border-radius:4px;font-size:1rem}
input:focus{outline:2px solid #a9e2cc;border-color:#a9e2cc}
button{margin-top:1.5rem;width:100%;padding:.65rem;background:#a9e2cc;border:none;border-radius:4px;font-size:1rem;font-weight:600;cursor:pointer;color:#1a2820}
button:hover{background:#8dd4bb}
.alert{margin-top:1rem;padding:.65rem .75rem;border-radius:4px;font-size:.9rem}
.alert-error{background:#fee2e2;color:#991b1b}
.alert-info{background:#dbeafe;color:#1e40af}
</style>
</head>
<body>
<div class="card">
  <h1>Füreinander Freiburg · Admin</h1>

  <?php if ($timeout): ?>
    <div class="alert alert-info">Sitzung abgelaufen. Bitte neu anmelden.</div>
  <?php endif; ?>

  <?php if ($error !== ''): ?>
    <div class="alert alert-error"><?= e($error) ?></div>
  <?php endif; ?>

  <form method="post" autocomplete="off" novalidate>
    <label for="username">Benutzername</label>
    <input type="text" id="username" name="username" required
           autocomplete="username" maxlength="64"
           value="<?= isset($_POST['username']) ? e($_POST['username']) : '' ?>">

    <label for="password">Passwort</label>
    <input type="password" id="password" name="password" required
           autocomplete="current-password" maxlength="1024">

    <button type="submit">Anmelden</button>
  </form>
</div>
</body>
</html>
