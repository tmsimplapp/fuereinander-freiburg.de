<?php
// Gemeinsame Hilfsfunktionen für buchung.php und cancel.php
// Wird per require eingebunden, setzt buchung-config.php voraus

function db(): PDO {
    static $pdo = null;
    if ($pdo === null) {
        $pdo = new PDO(
            'mysql:host=' . DB_HOST . ';dbname=' . DB_NAME . ';charset=utf8mb4',
            DB_USER, DB_PASS,
            [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]
        );
    }
    return $pdo;
}

function email_style(): string {
    return "
    <style>
        @import url('https://fonts.googleapis.com/css2?family=Lato:ital,wght@0,300;0,400;0,700;1,300;1,400;1,700&display=swap');
        body { font-family: 'Source Serif 4', Georgia, serif; background-color: #FEFAE0; color: #5c4e3a; margin: 0; padding: 40px 20px; line-height: 1.7; }
        .wrapper { max-width: 600px; margin: 0 auto; }
        .header { text-align: center; margin-bottom: 30px; }
        .logo-text { font-family: 'Lato', sans-serif; font-size: 24px; color: #3d3225; letter-spacing: 1px; }
        .logo-text i { color: #5fa88a; }
        .card { background-color: #ffffff; padding: 48px 40px; border-radius: 20px; border: 1px solid #E2C2A2; box-shadow: 0 10px 30px rgba(61,50,37,0.05); }
        h2 { font-family: 'Lato', sans-serif; color: #3d3225; font-size: 28px; font-weight: 600; margin-top: 0; margin-bottom: 24px; }
        .data-box { background-color: #d4f1e6; padding: 24px 32px; border-radius: 16px; margin: 32px 0; color: #1a2820; }
        .data-box p { margin: 0 0 12px 0; font-size: 14px; color: #3d3225; opacity: 0.8; }
        .btn { display: inline-block; background-color: #a9e2cc; color: #1a2820; padding: 14px 32px; border-radius: 50px; text-decoration: none; font-weight: 600; margin-top: 24px; }
        .btn-danger { background-color: #f87171; color: #fff; }
        .divider { height: 1px; background-color: #E2C2A2; margin: 40px 0; opacity: 0.5; }
        .footer { font-size: 13px; color: #6f6047; text-align: center; margin-top: 24px; }
        .footer a { color: #5fa88a; text-decoration: none; }
    </style>";
}

function mail_wrapper(string $inner): string {
    return '<!DOCTYPE html><html><head><meta charset="UTF-8">' . email_style() . '</head><body>
    <div class="wrapper">
        <div class="header"><div class="logo-text"><i>füreinander</i> Freiburg</div></div>
        <div class="card">' . $inner . '</div>
        <div class="footer">Selbsthilfegruppe für zweifelnde und ausgestiegene Zeugen Jehovas<br>
        <a href="' . SITE_URL . '">' . SITE_URL . '</a></div>
    </div></body></html>';
}

function sende_mail(string $to, string $subject, string $body): void {
    $headers  = 'From: Füreinander Freiburg <' . MAIL_FROM . ">\r\n";
    $headers .= "MIME-Version: 1.0\r\n";
    $headers .= "Content-Type: text/html; charset=UTF-8\r\n";
    mail($to, $subject, $body, $headers, '-f' . MAIL_FROM);
}

function datum_lesbar(string $datum): string {
    $wochentage = ['Mo', 'Di', 'Mi', 'Do', 'Fr', 'Sa', 'So'];
    $monate     = ['Jan','Feb','Mär','Apr','Mai','Jun','Jul','Aug','Sep','Okt','Nov','Dez'];
    $ts  = strtotime($datum);
    $dow = (int)date('N', $ts) - 1;
    $mon = (int)date('n', $ts) - 1;
    return $wochentage[$dow] . ', ' . (int)date('j', $ts) . '. ' . $monate[$mon] . ' ' . date('Y', $ts);
}

// Buchbare Einzeltermine aus DB laden
// Gibt zurück: ['termine' => [['datum'=>'2026-07-02','uhrzeiten'=>['18:00','19:00'],'laenge_min'=>60], ...]]
function slot_config_laden(): array {
    $rows = db()->query(
        "SELECT termin_datum, uhrzeiten, slot_laenge_min FROM slot_konfiguration WHERE aktiv = 1 ORDER BY termin_datum"
    )->fetchAll(PDO::FETCH_ASSOC);

    $termine = [];
    foreach ($rows as $row) {
        $termine[] = [
            'datum'      => $row['termin_datum'],
            'uhrzeiten'  => json_decode($row['uhrzeiten'], true),
            'laenge_min' => (int)$row['slot_laenge_min'],
        ];
    }
    return ['termine' => $termine, 'laenge_min' => empty($termine) ? SLOT_LAENGE_MIN : $termine[0]['laenge_min']];
}
