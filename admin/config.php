<?php
// Admin-Konfiguration – NICHT in Git einchecken
// Datenbank-Zugangsdaten aus dem Projekt-Root übernehmen
require_once __DIR__ . '/../buchung-config.php';

// Session-Einstellungen – müssen VOR session_start() gesetzt werden
ini_set('session.use_strict_mode', '1');
ini_set('session.use_only_cookies', '1');
session_set_cookie_params([
    'lifetime' => 0,
    'path'     => '/',
    'domain'   => '',
    'secure'   => true,
    'httponly' => true,
    'samesite' => 'Lax',
]);

define('SESSION_TIMEOUT', 1800); // 30 Minuten Inaktivitäts-Timeout
define('BRUTE_MAX_ATTEMPTS', 5);
define('BRUTE_WINDOW_SECONDS', 900); // 15 Minuten

function admin_db(): PDO {
    static $pdo = null;
    if ($pdo === null) {
        $pdo = new PDO(
            'mysql:host=' . DB_HOST . ';dbname=' . DB_NAME . ';charset=utf8mb4',
            DB_USER, DB_PASS,
            [
                PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_EMULATE_PREPARES   => false,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            ]
        );
    }
    return $pdo;
}

function e(string $s): string {
    return htmlspecialchars($s, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
}
