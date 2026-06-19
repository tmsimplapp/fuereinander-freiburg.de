<?php
require __DIR__ . '/buchung-config.php';
require __DIR__ . '/buchung-helpers.php';

function page(string $title, string $inner): string {
    return '<!DOCTYPE html><html lang="de"><head><meta charset="UTF-8"><meta name="viewport" content="width=device-width,initial-scale=1.0">
    <title>' . $title . ' – Füreinander Freiburg</title>' . email_style() . '</head><body>
    <div class="wrapper">
        <div style="text-align:center;margin-bottom:24px;">
            <a href="' . SITE_URL . '" style="font-family:Georgia,serif;font-size:20px;color:#3d3225;text-decoration:none;"><i style="color:#5fa88a;">füreinander</i> Freiburg</a>
        </div>
        <div class="card">' . $inner . '</div>
        <div class="footer">Füreinander Freiburg · <a href="' . SITE_URL . '">' . SITE_URL . '</a></div>
    </div></body></html>';
}

$token = trim($_GET['token'] ?? '');

if (!preg_match('/^[0-9a-f]{64}$/', $token)) {
    http_response_code(400);
    echo page('Fehler', '<h2>Ungültiger Link</h2><p>Dieser Stornierungslink ist nicht gültig.</p>');
    exit;
}

$stmt = db()->prepare(
    "SELECT id, name, slot_datum, slot_uhrzeit, email, storniert FROM rueckruf_buchungen WHERE cancel_token = ?"
);
$stmt->execute([$token]);
$buchung = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$buchung) {
    http_response_code(404);
    echo page('Nicht gefunden', '<h2>Termin nicht gefunden</h2><p>Dieser Stornierungslink ist abgelaufen oder ungültig.</p>');
    exit;
}

// NULL = storniert (NULL-Trick für korrekten UNIQUE KEY)
if ($buchung['storniert'] === null) {
    echo page('Bereits storniert', '<h2>Bereits storniert</h2><p>Dieser Termin wurde bereits storniert.</p>
        <p><a href="' . SITE_URL . '/#kontakt" class="btn">Neuen Termin buchen</a></p>');
    exit;
}

$datum_text  = datum_lesbar($buchung['slot_datum']);
$uhrzeit_von = substr($buchung['slot_uhrzeit'], 0, 5);
$laenge_min  = slot_config_laden()['laenge_min'];
$uhrzeit_bis = date('H:i', strtotime($buchung['slot_uhrzeit']) + $laenge_min * 60);

// ── GET: Bestätigungsseite anzeigen (kein Prefetcher-Risiko) ──────────────
if ($_SERVER['REQUEST_METHOD'] === 'GET') {
    echo page('Termin stornieren', '
        <h2>Termin stornieren</h2>
        <p>Hallo ' . htmlspecialchars($buchung['name']) . ',</p>
        <p>möchtest du deinen Rückruf-Termin wirklich stornieren?</p>
        <div class="data-box">
            <strong>Termin:</strong> ' . $datum_text . '<br>
            <strong>Uhrzeit:</strong> ' . $uhrzeit_von . ' – ' . $uhrzeit_bis . ' Uhr
        </div>
        <form method="POST" action="cancel.php?token=' . htmlspecialchars($token) . '">
            <button type="submit" class="btn btn-danger">Ja, Termin stornieren</button>
            &nbsp;&nbsp;
            <a href="' . SITE_URL . '/#kontakt" class="btn">Abbrechen</a>
        </form>
    ');
    exit;
}

// ── POST: Stornierung durchführen ─────────────────────────────────────────
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // NULL-Trick: storniert = NULL macht den UNIQUE-Key-Slot wieder buchbar
    db()->prepare("UPDATE rueckruf_buchungen SET storniert = NULL WHERE id = ?")->execute([$buchung['id']]);

    // Storno-Bestätigung per Mail
    $headers  = 'From: Füreinander Freiburg <' . MAIL_FROM . ">\r\n";
    $headers .= "MIME-Version: 1.0\r\n";
    $headers .= "Content-Type: text/html; charset=UTF-8\r\n";
    $body = '<!DOCTYPE html><html><head><meta charset="UTF-8">' . email_style() . '</head><body>
    <div class="wrapper">
        <div class="card">
            <h2>Termin storniert</h2>
            <p>Hallo ' . htmlspecialchars($buchung['name']) . ',</p>
            <p>dein Rückruf-Termin wurde erfolgreich storniert.</p>
            <div class="data-box">
                <strong>Termin:</strong> ' . $datum_text . ', ' . $uhrzeit_von . ' – ' . $uhrzeit_bis . ' Uhr
            </div>
            <p>Der Termin ist wieder für andere verfügbar. Gerne kannst du einen neuen Termin buchen.</p>
            <a href="' . SITE_URL . '/#kontakt" class="btn">Neuen Termin buchen</a>
        </div>
        <div class="footer">Füreinander Freiburg · <a href="' . SITE_URL . '">' . SITE_URL . '</a></div>
    </div></body></html>';
    mail($buchung['email'], 'Stornierungsbestätigung – Füreinander Freiburg', $body, $headers, '-f' . MAIL_FROM);

    echo page('Termin storniert', '
        <h2>Termin erfolgreich storniert</h2>
        <p>Hallo ' . htmlspecialchars($buchung['name']) . ',</p>
        <p>dein Rückruf-Termin am <strong>' . $datum_text . '</strong> um <strong>' . $uhrzeit_von . ' Uhr</strong> wurde storniert. Du erhältst eine Bestätigung per E-Mail.</p>
        <a href="' . SITE_URL . '/#kontakt" class="btn">Neuen Termin buchen</a>
    ');
    exit;
}

http_response_code(405);
echo page('Fehler', '<h2>Ungültige Anfrage</h2>');
