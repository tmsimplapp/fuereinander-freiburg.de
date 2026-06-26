<?php
require_once __DIR__ . '/config.php';

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Vollständiger Logout
$cookie_name   = session_name();
$cookie_params = session_get_cookie_params();
session_unset();
session_destroy();

// Session-Cookie löschen (Params vor destroy lesen, sonst ggf. leer)
setcookie(
    $cookie_name, '', [
        'expires'  => time() - 42000,
        'path'     => $cookie_params['path'],
        'domain'   => $cookie_params['domain'],
        'secure'   => $cookie_params['secure'],
        'httponly' => $cookie_params['httponly'],
        'samesite' => $cookie_params['samesite'] ?? 'Lax',
    ]
);

header('Location: login.php');
exit;
