<?php
header('Content-Type: application/json');

// --- Telegram-Konfiguration ---
// Zugangsdaten liegen in nicht-versionierter telegram-config.php (siehe .gitignore)
require __DIR__ . '/telegram-config.php';

// Hilfsfunktion für Telegram-Benachrichtigung
function send_telegram_notification($name, $method, $value, $message) {
    $text = "🔔 <b>Neue Kontaktanfrage!</b>\n\n"
          . "👤 <b>Name:</b> " . htmlspecialchars($name) . "\n"
          . "📞 <b>Kontaktweg:</b> " . htmlspecialchars($method) . "\n"
          . "✉️ <b>Kontakt:</b> " . htmlspecialchars($value) . "\n\n"
          . "💬 <b>Nachricht:</b>\n" . htmlspecialchars($message);

    $url = "https://api.telegram.org/bot" . TELEGRAM_BOT_TOKEN . "/sendMessage";
    $post_fields = [
        'chat_id' => TELEGRAM_CHAT_ID,
        'text' => $text,
        'parse_mode' => 'HTML'
    ];

    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $url);
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($post_fields));
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, true);
    $result = curl_exec($ch);
    curl_close($ch);
    return $result;
}

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    // 1. Honeypot check (Bot-Schutz)
    if (!empty($_POST['website'])) {
        echo json_encode(['status' => 'error', 'message' => 'Spam erkannt.']);
        exit;
    }

    // 2. Daten sammeln & bereinigen
    $name = strip_tags(trim($_POST["name"] ?? ''));
    $contact_method = strip_tags(trim($_POST["contact_method"] ?? ''));
    $contact_value = strip_tags(trim($_POST["contact_value"] ?? ''));
    $message = strip_tags(trim($_POST["message"] ?? ''));
    $privacy = isset($_POST["privacy"]) ? true : false;

    // 3. Validierung
    if (empty($name) || empty($contact_method) || empty($contact_value) || empty($message) || !$privacy) {
        echo json_encode(['status' => 'error', 'message' => 'Bitte alle Pflichtfelder ausfüllen und den Datenschutzhinweis akzeptieren.']);
        exit;
    }

    if ($contact_method === 'email' && !filter_var($contact_value, FILTER_VALIDATE_EMAIL)) {
        echo json_encode(['status' => 'error', 'message' => 'Bitte eine gültige E-Mail-Adresse angeben.']);
        exit;
    }

    // 4. E-Mail an die Gruppe senden (HTML)
    $to = "kontakt@fuereinander-freiburg.de";
    $subject = "Neue Kontaktanfrage über die Website";
    
    // HTML Template Style (Premium Editorial)
    $email_style = "
        <style>
            @import url('https://fonts.googleapis.com/css2?family=Playfair+Display:ital,wght@0,400;0,600;1,400&family=Source+Serif+4:wght@400;600&display=swap');
            
            body { font-family: 'Source Serif 4', Georgia, serif; background-color: #FEFAE0; color: #5c4e3a; margin: 0; padding: 40px 20px; line-height: 1.7; }
            .wrapper { max-width: 600px; margin: 0 auto; }
            .header { text-align: center; margin-bottom: 30px; }
            .logo-text { font-family: 'Playfair Display', Georgia, serif; font-size: 24px; color: #3d3225; letter-spacing: 1px; }
            .logo-text i { color: #5fa88a; }
            .card { background-color: #ffffff; padding: 48px 40px; border-radius: 20px; border: 1px solid #E2C2A2; box-shadow: 0 10px 30px rgba(61, 50, 37, 0.05); }
            h2 { font-family: 'Playfair Display', Georgia, serif; color: #3d3225; font-size: 28px; font-weight: 600; margin-top: 0; margin-bottom: 24px; }
            .data-box { background-color: #d4f1e6; padding: 24px 32px; border-radius: 16px; margin: 32px 0; color: #1a2820; }
            .data-box p { margin: 0 0 12px 0; font-size: 14px; color: #3d3225; opacity: 0.8; }
            .quote-box { background-color: #fff4d6; padding: 24px 32px; border-radius: 16px; border-left: 4px solid #ffda69; margin-top: 24px; font-style: italic; }
            .divider { height: 1px; background-color: #E2C2A2; margin: 40px 0; opacity: 0.5; }
            .footer { font-size: 13px; color: #6f6047; text-align: center; margin-top: 24px; }
            .footer a { color: #5fa88a; text-decoration: none; }
        </style>
    ";

    $email_content = "
    <!DOCTYPE html>
    <html>
    <head>
        <meta charset='UTF-8'>
        $email_style
    </head>
    <body>
        <div class='wrapper'>
            <div class='header'>
                <div class='logo-text'><i>füreinander</i> Freiburg</div>
            </div>
            <div class='card'>
                <h2>Neue Kontaktanfrage</h2>
                
                <div class='data-box'>
                    <p>Folgende Anfrage ist über die Website eingegangen:</p>
                    <strong>Name / Pseudonym:</strong> $name<br>
                    <strong>Kontaktweg:</strong> $contact_method<br>
                    <strong>Kontakt:</strong> $contact_value
                </div>
                
                <p><strong>Nachricht:</strong></p>
                <div class='quote-box'>" . nl2br($message) . "</div>
                
                <div class='divider'></div>
                <p style='margin-bottom: 0; font-size: 14px; color: #5fa88a;'>Datenschutzhinweis wurde vom Absender akzeptiert.</p>
            </div>
        </div>
    </body>
    </html>
    ";
    
    $email_headers = "From: Formular Füreinander Freiburg <kontakt@fuereinander-freiburg.de>\r\n";
    $email_headers .= "MIME-Version: 1.0\r\n";
    $email_headers .= "Content-Type: text/html; charset=UTF-8\r\n";
    
    if ($contact_method === 'email') {
        $email_headers .= "Reply-To: $contact_value\r\n";
    }

    $mail_success = mail($to, $subject, $email_content, $email_headers, "-fkontakt@fuereinander-freiburg.de");

    // Telegram Benachrichtigung senden (falls konfiguriert)
    if ($mail_success && TELEGRAM_BOT_TOKEN !== 'HIER_NEUEN_TOKEN_EINTRAGEN' && TELEGRAM_CHAT_ID !== 'HIER_CHAT_ID_EINTRAGEN') {
        send_telegram_notification($name, $contact_method, $contact_value, $message);
    }

    // 5. Auto-Responder an den Absender (HTML)
    if ($mail_success && $contact_method === 'email') {
        $auto_subject = "Eingangsbestätigung: Deine Nachricht an Füreinander Freiburg";
        $auto_content = "
        <!DOCTYPE html>
        <html>
        <head>
            <meta charset='UTF-8'>
            $email_style
        </head>
        <body>
            <div class='wrapper'>
                <div class='header'>
                    <div class='logo-text'><i>füreinander</i> Freiburg</div>
                </div>
                <div class='card'>
                    <h2>Hallo $name,</h2>
                    <p>vielen Dank für deine Nachricht. Wir haben diese erhalten und werden uns schnellstmöglich bei dir melden.</p>
                    
                    <div class='data-box'>
                        <p>Folgende Daten speichern wir zur gewünschten Kontaktaufnahme:</p>
                        <strong>Kontaktweg:</strong> E-Mail<br>
                        <strong>E-Mail:</strong> $contact_value
                    </div>
                    
                    <p><strong>Deine Nachricht an uns:</strong></p>
                    <div class='quote-box'>" . nl2br($message) . "</div>
                    
                    <div class='divider'></div>
                    <p style='margin-bottom: 0;'>Herzliche Grüße,<br><strong>Das Team von Füreinander Freiburg</strong></p>
                </div>
                <div class='footer'>
                    Selbsthilfegruppe für zweifelnde und ausgestiegene Zeugen Jehovas<br>
                    <a href='https://fuereinander-freiburg.de'>fuereinander-freiburg.de</a>
                </div>
            </div>
        </body>
        </html>
        ";
        
        $auto_headers = "From: Füreinander Freiburg <kontakt@fuereinander-freiburg.de>\r\n";
        $auto_headers .= "MIME-Version: 1.0\r\n";
        $auto_headers .= "Content-Type: text/html; charset=UTF-8\r\n";
        
        mail($contact_value, $auto_subject, $auto_content, $auto_headers, "-fkontakt@fuereinander-freiburg.de");
    }

    // 6. Antwort an Frontend
    if ($mail_success) {
        echo json_encode(['status' => 'success']);
    } else {
        echo json_encode(['status' => 'error', 'message' => 'Die Nachricht konnte aufgrund eines Server-Fehlers nicht gesendet werden.']);
    }
} else {
    echo json_encode(['status' => 'error', 'message' => 'Ungültige Anfrage.']);
}
?>
