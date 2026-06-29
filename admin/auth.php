<?php
// Wird von jeder geschützten Admin-Seite als erstes eingebunden.
// Leitet bei fehlender/abgelaufener Session sofort auf login.php um.
require_once __DIR__ . '/config.php';

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Inaktivitäts-Timeout prüfen
if (isset($_SESSION['admin_last_active'])) {
    if (time() - $_SESSION['admin_last_active'] > SESSION_TIMEOUT) {
        session_unset();
        session_destroy();
        header('Location: login.php?timeout=1');
        exit;
    }
}

// Eingeloggt?
if (empty($_SESSION['admin_logged_in'])) {
    header('Location: login.php');
    exit;
}

// 2FA noch ausstehend?
if (!empty($_SESSION['totp_pending'])) {
    header('Location: login_2fa.php');
    exit;
}

// Timestamp aktualisieren
$_SESSION['admin_last_active'] = time();
