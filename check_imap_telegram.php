<?php
/**
 * Dieses Skript wird per Cronjob (z.B. alle 5 Minuten) aufgerufen.
 * Es verbindet sich per IMAP mit dem Postfach, sucht nach ungelesenen Mails
 * und leitet die Benachrichtigung an Telegram weiter.
 */

define('TELEGRAM_BOT_TOKEN', '8835333763:AAEh7i7da-CjZf37JFl0GswCTCfEltezbP4');
define('TELEGRAM_CHAT_ID', '-5176222824');

// --- IMAP ZUGANGSDATEN BITTE ANPASSEN ---
$imap_server = "{w01ff866.kasserver.com:993/imap/ssl}INBOX"; // Korrekter Server für all-inkl
$imap_user   = "kontakt@fuereinander-freiburg.de";       // E-Mail oder Postfach-Login (z.B. w012345)
$imap_pass   = "Fiu_}+0jumEcn+ukZyid/";      // <-- HIER PASSWORT EINTRAGEN

// Fehleranzeige aktivieren für den direkten Aufruf im Browser
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

echo "<h3>IMAP Telegram Debugger</h3>";

// Verbindung herstellen
echo "Versuche IMAP-Verbindung zu: $imap_server mit User: $imap_user...<br>";
$inbox = @imap_open($imap_server, $imap_user, $imap_pass);

if (!$inbox) {
    die("<b>Fehler:</b> Kann nicht zu IMAP verbinden: " . imap_last_error() . "<br>Bitte prüfe Passwort und IMAP-Server.");
}
echo "Verbindung erfolgreich!<br>";

// Suche nach UNGELESENEN Mails
echo "Suche nach ungelesenen (UNSEEN) E-Mails...<br>";
$emails = imap_search($inbox, 'UNSEEN');

$log_file = __DIR__ . '/processed_emails.txt';
$processed = file_exists($log_file) ? file($log_file, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES) : [];

if ($emails) {
    echo "<b>" . count($emails) . " ungelesene E-Mails gefunden!</b><br>";
    foreach ($emails as $email_number) {
        $headerInfo = imap_headerinfo($inbox, $email_number);
        
        // Eindeutige ID der Mail
        $msg_id = isset($headerInfo->message_id) ? trim($headerInfo->message_id) : $email_number;
        
        if (in_array($msg_id, $processed)) {
            echo "- Mail ($msg_id) wurde bereits an Telegram gemeldet, überspringe...<br>";
            continue;
        }
        
        $subject = isset($headerInfo->subject) ? mb_decode_mimeheader($headerInfo->subject) : "Kein Betreff";
        $fromaddr = $headerInfo->from[0]->mailbox . "@" . $headerInfo->from[0]->host;
        $fromname = isset($headerInfo->from[0]->personal) ? mb_decode_mimeheader($headerInfo->from[0]->personal) : $fromaddr;

        echo "- Verarbeite neue Mail von: $fromname ($subject)... ";

        // Body abrufen (versuche Part 1, oft plain text)
        $body = imap_fetchbody($inbox, $email_number, 1);
        
        // Decoding basierend auf Struktur (einfacher Ansatz)
        $struct = imap_fetchstructure($inbox, $email_number);
        $encoding = isset($struct->parts[0]->encoding) ? $struct->parts[0]->encoding : $struct->encoding;
        
        if ($encoding == 3) $body = base64_decode($body);
        elseif ($encoding == 4) $body = quoted_printable_decode($body);
        
        $body = strip_tags(trim($body));
        
        // Zitate und Verlauf abschneiden (Am ... schrieb, On ... wrote, Original Message, etc.)
        $body = preg_replace('/(\nAm\s+.*\s+schrieb.*|\nOn\s+.*\s+wrote.*|\n-{5}Original Message-{5}.*|\n>.*)/is', '', $body);
        
        // Text auf maximal 300 Zeichen begrenzen
        if (mb_strlen($body) > 300) {
            $body = mb_substr($body, 0, 300) . "... [gekürzt]";
        }
        
        if (empty($body)) $body = "<i>Kein Textinhalt lesbar (eventuell nur HTML/Anhang).</i>";

        $text = "📩 <b>Neue E-Mail im Postfach!</b>\n\n"
              . "👤 <b>Von:</b> " . htmlspecialchars($fromname . " (" . $fromaddr . ")") . "\n"
              . "📌 <b>Betreff:</b> " . htmlspecialchars($subject) . "\n\n"
              . "💬 <b>Nachricht:</b>\n" . htmlspecialchars($body) . "\n\n"
              . "<i>Email bitte in Outlook öffnen, lesen und beantworten.</i>";

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
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
        $result = curl_exec($ch);
        curl_close($ch);
        
        if ($result) {
            // Speichere Message-ID, damit sie beim nächsten Mal ignoriert wird
            file_put_contents($log_file, $msg_id . PHP_EOL, FILE_APPEND);
            echo "Telegram-Status: Gesendet (ID gespeichert)<br>";
        } else {
            echo "Telegram-Status: Fehler<br>";
        }
    }
} else {
    echo "<b>Keine ungelesenen E-Mails gefunden.</b><br>";
}

imap_close($inbox);
echo "<hr>Fertig.";
?>
