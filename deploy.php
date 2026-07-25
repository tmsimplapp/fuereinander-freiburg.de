<?php
// deploy.php - Webhook für automatischen / manuellen Git Pull

// Lade Config falls vorhanden (für geheimen Token außerhalb Git)
if (file_exists(__DIR__ . '/buchung-config.php')) {
    include_once __DIR__ . '/buchung-config.php';
}

// Fallback falls nicht in buchung-config.php definiert
if (!defined('DEPLOY_TOKEN')) {
    define('DEPLOY_TOKEN', 'f8e7d6c5b4a3f2e1d0c9b8a7f6e5d4c3');
}

if (!isset($_GET['token']) || $_GET['token'] !== DEPLOY_TOKEN) {
    header('HTTP/1.1 403 Forbidden');
    echo "Zugriff verweigert.";
    exit;
}

// Führe git pull aus
$output = [];
$return_var = 0;
exec('git pull 2>&1', $output, $return_var);

echo "<h3>Git Pull Status:</h3>";
echo "<pre>" . implode("\n", $output) . "</pre>";

if ($return_var === 0) {
    echo "<p style='color:green;'>Erfolgreich aktualisiert!</p>";
} else {
    echo "<p style='color:red;'>Fehler beim Aktualisieren (Code: $return_var).</p>";
}
