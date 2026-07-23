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
    'samesite' => 'Strict',
]);

define('SESSION_TIMEOUT', 900); // 15 Minuten Inaktivitäts-Timeout
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

// ── TOTP (RFC 6238, selbst implementiert) ──

function base32_encode(string $data): string {
    $alphabet = 'ABCDEFGHIJKLMNOPQRSTUVWXYZ234567';
    $binary = '';
    foreach (str_split($data) as $c) {
        $binary .= str_pad(decbin(ord($c)), 8, '0', STR_PAD_LEFT);
    }
    $output = '';
    foreach (str_split(str_pad($binary, (int)(ceil(strlen($binary) / 5) * 5), '0'), 5) as $chunk) {
        $output .= $alphabet[bindec($chunk)];
    }
    return $output;
}

function base32_decode(string $secret): string|false {
    $alphabet = 'ABCDEFGHIJKLMNOPQRSTUVWXYZ234567';
    $secret   = strtoupper(trim($secret));
    $binary   = '';
    foreach (str_split($secret) as $c) {
        $pos = strpos($alphabet, $c);
        if ($pos === false) return false;
        $binary .= str_pad(decbin($pos), 5, '0', STR_PAD_LEFT);
    }
    $output = '';
    foreach (str_split(substr($binary, 0, (int)(floor(strlen($binary) / 8) * 8)), 8) as $chunk) {
        $output .= chr(bindec($chunk));
    }
    return $output;
}

function totp_generate_secret(int $length = 16): string {
    return substr(base32_encode(random_bytes($length)), 0, $length);
}

function totp_calculate(string $secret_key, int $time_slice): string {
    $key = base32_decode($secret_key);
    if ($key === false) return '------';
    $msg     = pack('N*', 0) . pack('N*', $time_slice);
    $hash    = hash_hmac('sha1', $msg, $key, true);
    $offset  = ord($hash[19]) & 0xf;
    $code    = (
        ((ord($hash[$offset])     & 0x7f) << 24) |
        ((ord($hash[$offset + 1]) & 0xff) << 16) |
        ((ord($hash[$offset + 2]) & 0xff) << 8)  |
         (ord($hash[$offset + 3]) & 0xff)
    ) % 1000000;
    return str_pad((string)$code, 6, '0', STR_PAD_LEFT);
}

function totp_verify(string $secret_key, string $code, int $discrepancy = 0): int|false {
    $time_slice = (int)floor(time() / 30);
    for ($i = -$discrepancy; $i <= $discrepancy; $i++) {
        $slice = $time_slice + $i;
        if (hash_equals(totp_calculate($secret_key, $slice), $code)) {
            return $slice;
        }
    }
    return false;
}

function totp_qr_url(string $username, string $secret_key): string {
    $issuer = 'Füreinander Freiburg Admin';
    $label  = rawurlencode($issuer . ':' . $username);
    return 'otpauth://totp/' . $label
        . '?secret=' . rawurlencode($secret_key)
        . '&issuer=' . rawurlencode($issuer)
        . '&algorithm=SHA1&digits=6&period=30';
}
