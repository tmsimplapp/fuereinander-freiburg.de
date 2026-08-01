<?php
header('Content-Type: text/plain; charset=utf-8');
require_once __DIR__ . '/buchung-config.php';

echo "Server IP (SERVER_ADDR): " . ($_SERVER['SERVER_ADDR'] ?? 'Unknown') . "\n";
echo "Server Name (HTTP_HOST): " . ($_SERVER['HTTP_HOST'] ?? 'Unknown') . "\n\n";

echo "Konfiguration:\n";
echo "DB_HOST: " . DB_HOST . "\n";
echo "DB_NAME: " . DB_NAME . "\n";
echo "DB_USER: " . DB_USER . "\n\n";

try {
    $dsn = 'mysql:host=' . DB_HOST . ';dbname=' . DB_NAME . ';charset=utf8mb4';
    $pdo = new PDO($dsn, DB_USER, DB_PASS, [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC
    ]);
    echo "Datenbank-Verbindung: ERFOLGREICH\n\n";

    echo "Admins in der Tabelle:\n";
    $stmt = $pdo->query('SELECT id, username, totp_enabled, created_at FROM admins');
    while ($row = $stmt->fetch()) {
        echo sprintf(
            "ID: %d | Username: %s | 2FA Aktiv (totp_enabled): %d | Erstellt: %s\n",
            $row['id'],
            $row['username'],
            $row['totp_enabled'],
            $row['created_at']
        );
    }
} catch (Exception $e) {
    echo "Datenbank-Fehler: " . $e->getMessage() . "\n";
}
