<?php
header('Content-Type: application/json; charset=utf-8');
require __DIR__ . '/buchung-config.php';
require __DIR__ . '/buchung-helpers.php';

// ── Alle buchbaren Slots mit Belegungsstatus ───────────────────────────────
function slots_laden(): array {
    $config = slot_config_laden();
    $slots  = [];

    foreach ($config['termine'] as $termin) {
        foreach ($termin['uhrzeiten'] as $uhrzeit) {
            $slots[] = ['datum' => $termin['datum'], 'uhrzeit' => $uhrzeit, 'belegt' => false];
        }
    }

    if (empty($slots)) return $slots;

    $placeholders = implode(',', array_fill(0, count($slots), '(?,?)'));
    $params = [];
    foreach ($slots as $s) { $params[] = $s['datum']; $params[] = $s['uhrzeit']; }
    $stmt = db()->prepare(
        "SELECT slot_datum, TIME_FORMAT(slot_uhrzeit, '%H:%i') AS slot_uhrzeit FROM rueckruf_buchungen
         WHERE (slot_datum, slot_uhrzeit) IN ($placeholders) AND storniert IS NOT NULL"
    );
    $stmt->execute($params);
    $belegt = [];
    foreach ($stmt->fetchAll(PDO::FETCH_ASSOC) as $row) {
        $belegt[$row['slot_datum'] . '_' . $row['slot_uhrzeit']] = true;
    }
    foreach ($slots as &$s) {
        if (isset($belegt[$s['datum'] . '_' . $s['uhrzeit']])) $s['belegt'] = true;
    }
    return $slots;
}

// ── Routing ────────────────────────────────────────────────────────────────
$action = $_GET['action'] ?? '';

// GET ?action=slots
if ($_SERVER['REQUEST_METHOD'] === 'GET' && $action === 'slots') {
    echo json_encode(['status' => 'ok', 'slots' => slots_laden()]);
    exit;
}

// POST ?action=buchen
if ($_SERVER['REQUEST_METHOD'] === 'POST' && $action === 'buchen') {
    $input = json_decode(file_get_contents('php://input'), true) ?? [];

    $datum    = trim($input['datum']    ?? '');
    $uhrzeit  = trim($input['uhrzeit']  ?? '');
    $name     = strip_tags(trim($input['name']     ?? ''));
    $telefon  = strip_tags(trim($input['telefon']  ?? ''));
    $email    = trim($input['email']    ?? '');

    // Validierung
    if (!$datum || !$uhrzeit || !$name || !$telefon || !$email) {
        http_response_code(400);
        echo json_encode(['status' => 'error', 'message' => 'Alle Felder ausfüllen.']);
        exit;
    }
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        http_response_code(400);
        echo json_encode(['status' => 'error', 'message' => 'Ungültige E-Mail-Adresse.']);
        exit;
    }

    // Rate-Limiting: max. 3 Versuche pro IP in 10 Minuten
    $ip_hash = hash('sha256', $_SERVER['REMOTE_ADDR'] ?? '');
    $fenster = date('Y-m-d H:i:s', time() - 600);
    $count = db()->prepare(
        "SELECT COUNT(*) FROM buchung_rate_limit WHERE ip_hash = ? AND erstellt_am > ?"
    );
    $count->execute([$ip_hash, $fenster]);
    if ((int)$count->fetchColumn() >= 3) {
        http_response_code(429);
        echo json_encode(['status' => 'error', 'message' => 'Zu viele Anfragen. Bitte versuche es in 10 Minuten erneut.']);
        exit;
    }
    db()->prepare("INSERT INTO buchung_rate_limit (ip_hash) VALUES (?)")->execute([$ip_hash]);

    // Slot-Whitelist-Prüfung gegen DB-Einzeltermine
    $config  = slot_config_laden();
    $erlaubt = false;
    foreach ($config['termine'] as $termin) {
        if ($termin['datum'] === $datum && in_array($uhrzeit, $termin['uhrzeiten'], true)) {
            $erlaubt = true;
            break;
        }
    }

    if (!$erlaubt) {
        http_response_code(409);
        echo json_encode(['status' => 'error', 'message' => 'Dieser Termin ist nicht verfügbar.']);
        exit;
    }

    $token = bin2hex(random_bytes(32));

    try {
        $pdo = db();
        $pdo->beginTransaction();

        // Atomarer Belegungscheck: SELECT FOR UPDATE sperrt Zeile für parallele Requests
        $check = $pdo->prepare(
            "SELECT id FROM rueckruf_buchungen
             WHERE slot_datum = ? AND slot_uhrzeit = ? AND storniert IS NOT NULL
             FOR UPDATE"
        );
        $check->execute([$datum, $uhrzeit]);
        if ($check->fetch()) {
            $pdo->rollBack();
            http_response_code(409);
            echo json_encode(['status' => 'error', 'message' => 'Dieser Termin ist nicht verfügbar.']);
            exit;
        }

        $pdo->prepare(
            "INSERT INTO rueckruf_buchungen (slot_datum, slot_uhrzeit, name, telefon, email, cancel_token)
             VALUES (?, ?, ?, ?, ?, ?)"
        )->execute([$datum, $uhrzeit, $name, $telefon, $email, $token]);

        $pdo->commit();
    } catch (PDOException $e) {
        if (isset($pdo) && $pdo->inTransaction()) $pdo->rollBack();
        http_response_code(409);
        echo json_encode(['status' => 'error', 'message' => 'Dieser Termin wurde soeben anderweitig gebucht.']);
        exit;
    }

    $config       = slot_config_laden();
    $datum_text   = datum_lesbar($datum);
    $storno_link  = SITE_URL . '/cancel.php?token=' . $token;
    $uhrzeit_ende = date('H:i', strtotime($uhrzeit) + $config['laenge_min'] * 60);

    sende_mail(
        MAIL_FROM,
        'Neuer Rückruf-Termin: ' . $datum_text . ' ' . $uhrzeit,
        mail_wrapper("
            <h2>Neuer Rückruf-Termin</h2>
            <div class='data-box'>
                <p>Folgende Buchung ist eingegangen:</p>
                <strong>Termin:</strong> $datum_text, $uhrzeit – $uhrzeit_ende Uhr<br>
                <strong>Name:</strong> " . htmlspecialchars($name) . "<br>
                <strong>Telefon:</strong> " . htmlspecialchars($telefon) . "<br>
                <strong>E-Mail:</strong> " . htmlspecialchars($email) . "
            </div>
        ")
    );

    sende_mail(
        $email,
        'Dein Rückruf-Termin bei Füreinander Freiburg',
        mail_wrapper("
            <h2>Hallo " . htmlspecialchars($name) . ",</h2>
            <p>dein Rückruf-Termin ist bestätigt. Wir rufen dich zu folgender Zeit an:</p>
            <div class='data-box'>
                <strong>Termin:</strong> $datum_text<br>
                <strong>Uhrzeit:</strong> $uhrzeit – $uhrzeit_ende Uhr<br>
                <strong>Deine Nummer:</strong> " . htmlspecialchars($telefon) . "
            </div>
            <p>Falls du den Termin nicht wahrnehmen kannst, storniere ihn bitte rechtzeitig:</p>
            <a href='$storno_link' class='btn'>Termin stornieren</a>
            <div class='divider'></div>
            <p style='margin-bottom:0;'>Herzliche Grüße,<br><strong>Das Team von Füreinander Freiburg</strong></p>
        ")
    );

    echo json_encode(['status' => 'ok', 'message' => 'Termin gebucht! Bestätigung wurde an deine E-Mail gesendet.']);
    exit;
}

http_response_code(400);
echo json_encode(['status' => 'error', 'message' => 'Ungültige Anfrage.']);
