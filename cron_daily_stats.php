<?php
// cron_daily_stats.php
// Täglich per Cronjob ausführen (z. B. um 23:59 Uhr)

require_once __DIR__ . '/buchung-config.php';

try {
    $db = new PDO(
        'mysql:host=' . DB_HOST . ';dbname=' . DB_NAME . ';charset=utf8mb4',
        DB_USER, DB_PASS,
        [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]
    );

    // Aktuelle Aufrufe abfragen
    $stmt = $db->query("SELECT wert FROM statistiken WHERE name = 'seitenaufrufe'");
    $count = (int) $stmt->fetchColumn();

    // Heutiges Datum & Aufrufe speichern (bei erneutem Aufruf am selben Tag aktualisieren)
    $today = date('Y-m-d');
    $stmtInsert = $db->prepare("
        INSERT INTO statistiken_seitenaufrufe (datum, seitenaufrufe) 
        VALUES (:datum, :seitenaufrufe)
        ON DUPLICATE KEY UPDATE seitenaufrufe = :seitenaufrufe
    ");
    $stmtInsert->execute([
        ':datum' => $today,
        ':seitenaufrufe' => $count
    ]);

    // Ältere Einträge als 365 Tage löschen
    $db->exec("DELETE FROM statistiken_seitenaufrufe WHERE datum < DATE_SUB(CURDATE(), INTERVAL 365 DAY)");

    echo "OK: $today = $count\n";
} catch (Exception $e) {
    http_response_code(500);
    echo "ERROR: " . $e->getMessage() . "\n";
}
