<?php
/**
 * TEMPORÄRES SETUP-SKRIPT – NUR EINMALIG AUSFÜHREN
 * Danach sofort per FTP/SFTP löschen!
 *
 * Erstellt:
 *  1. Tabelle `admins`
 *  2. Tabelle `admin_rate_limit`
 *  3. Einen Admin-Benutzer (Zugangsdaten unten anpassen)
 *
 * Aufruf: https://fuereinander-freiburg.de/admin/setup_admin.php?token=SETUP_TOKEN_HIER
 */

// ── Zugangsdaten für den neuen Admin – HIER ANPASSEN ──────────────────────────
$admin_username = 'tobiasm!';
$admin_password = '5*hl8adZt#HD1^7np}wFHx6<GxM{?m\2J@pl]V?$g<,*[kZeg<_s~Fd4GyPn';
// ──────────────────────────────────────────────────────────────────────────────

// Schutz: Skript nur mit Token aufrufbar
$setup_token = 'xK9mP2qL7vR4nT8s';  // Vor Upload durch zufälligen String ersetzen!

if (!isset($_GET['token']) || !hash_equals($setup_token, $_GET['token'])) {
    http_response_code(403);
    die('Zugriff verweigert.');
}

if ($setup_token === 'SETUP_TOKEN_HIER') {
    die('Bitte setup_token im Skript anpassen, bevor es hochgeladen wird!');
}

if ($admin_password === 'HIER_SICHERES_PASSWORT_EINTRAGEN') {
    die('Bitte admin_password im Skript anpassen, bevor es ausgeführt wird!');
}

// Mindestanforderung Passwort
if (strlen($admin_password) < 12) {
    die('Passwort muss mindestens 12 Zeichen haben.');
}

require_once __DIR__ . '/config.php';
$db = admin_db();

// Tabellen anlegen
$db->exec("
    CREATE TABLE IF NOT EXISTS `admins` (
      `id`            INT UNSIGNED NOT NULL AUTO_INCREMENT,
      `username`      VARCHAR(64)  NOT NULL,
      `password_hash` VARCHAR(255) NOT NULL,
      `created_at`    DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP,
      PRIMARY KEY (`id`),
      UNIQUE KEY `uq_username` (`username`)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
");

$db->exec("
    CREATE TABLE IF NOT EXISTS `admin_rate_limit` (
      `id`          INT UNSIGNED NOT NULL AUTO_INCREMENT,
      `ip_hash`     CHAR(64)     NOT NULL,
      `erstellt_am` DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP,
      PRIMARY KEY (`id`),
      KEY `idx_ip_zeit` (`ip_hash`, `erstellt_am`)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
");

// Admin anlegen (oder Passwort aktualisieren falls Benutzer schon existiert)
$hash = password_hash($admin_password, PASSWORD_BCRYPT, ['cost' => 12]);

$stmt = $db->prepare('SELECT id FROM admins WHERE username = ? LIMIT 1');
$stmt->execute([$admin_username]);
$existing = $stmt->fetch();

if ($existing) {
    $stmt = $db->prepare('UPDATE admins SET password_hash = ? WHERE username = ?');
    $stmt->execute([$hash, $admin_username]);
    echo '<p>✓ Passwort für Benutzer <strong>' . htmlspecialchars($admin_username, ENT_QUOTES, 'UTF-8') . '</strong> aktualisiert.</p>';
} else {
    $stmt = $db->prepare('INSERT INTO admins (username, password_hash) VALUES (?, ?)');
    $stmt->execute([$admin_username, $hash]);
    echo '<p>✓ Admin-Benutzer <strong>' . htmlspecialchars($admin_username, ENT_QUOTES, 'UTF-8') . '</strong> angelegt.</p>';
}

echo '<p style="color:red;font-weight:bold">⚠ Diese Datei (setup_admin.php) jetzt sofort löschen!</p>';
echo '<p><a href="login.php">→ Zum Login</a></p>';
