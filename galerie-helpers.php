<?php
// Gemeinsame Helfer für die Bildergalerie (Frontend + Admin).

// Ordnername bewusst NICHT "galerie": sonst kollidiert er mit galerie.php
// (die .htaccess-Regel hängt .php nur an, wenn kein gleichnamiges Verzeichnis existiert).
const GALERIE_DIR       = __DIR__ . '/galerie-bilder';
const GALERIE_URL       = 'galerie-bilder';
const GALERIE_MAX_BYTES = 8 * 1024 * 1024;
const GALERIE_MAX_KANTE = 1600;
const GALERIE_THUMB_KANTE = 600;

function galerie_db(): PDO {
    static $pdo = null;
    if ($pdo === null) {
        require_once __DIR__ . '/buchung-config.php';
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

/** Sichtbare Bilder für die öffentliche Galerie. */
function galerie_bilder_aktiv(PDO $db): array {
    return $db->query(
        'SELECT id, datei, thumb, titel, alt_text, breite, hoehe
         FROM galerie WHERE aktiv = 1
         ORDER BY sortierung ASC, id ASC'
    )->fetchAll();
}

/**
 * Verarbeitet eine hochgeladene Datei: prüft, skaliert, speichert Bild + Thumbnail.
 * Gibt bei Erfolg ['datei','thumb','breite','hoehe'] zurück, sonst einen Fehlertext.
 */
function galerie_upload_verarbeiten(array $file): array|string {
    if (($file['error'] ?? UPLOAD_ERR_NO_FILE) !== UPLOAD_ERR_OK) {
        return match ($file['error']) {
            UPLOAD_ERR_INI_SIZE, UPLOAD_ERR_FORM_SIZE => 'Die Datei ist zu groß.',
            UPLOAD_ERR_PARTIAL                        => 'Die Datei wurde nur teilweise übertragen.',
            default                                   => 'Der Upload ist fehlgeschlagen.',
        };
    }
    if ($file['size'] > GALERIE_MAX_BYTES) {
        return 'Die Datei ist größer als 8 MB.';
    }
    if (!is_uploaded_file($file['tmp_name'])) {
        return 'Ungültiger Upload.';
    }

    if (!function_exists('imagewebp')) {
        return 'Auf dem Server fehlt die Bildbibliothek GD mit WebP-Unterstützung. Bitte beim Hoster aktivieren lassen.';
    }

    $info = @getimagesize($file['tmp_name']);
    if ($info === false) {
        return 'Die Datei ist kein gültiges Bild.';
    }
    [$breite, $hoehe] = $info;
    $typ = $info[2];

    $quelle = match ($typ) {
        IMAGETYPE_JPEG => @imagecreatefromjpeg($file['tmp_name']),
        IMAGETYPE_PNG  => @imagecreatefrompng($file['tmp_name']),
        IMAGETYPE_WEBP => @imagecreatefromwebp($file['tmp_name']),
        default        => null,
    };
    if (!$quelle) {
        return 'Nur JPG, PNG oder WebP werden unterstützt.';
    }

    // EXIF-Rotation korrigieren, damit Handyfotos richtig herum stehen
    if ($typ === IMAGETYPE_JPEG && function_exists('exif_read_data')) {
        $exif = @exif_read_data($file['tmp_name']);
        $grad = match ($exif['Orientation'] ?? 1) { 3 => 180, 6 => -90, 8 => 90, default => 0 };
        if ($grad !== 0) {
            $gedreht = imagerotate($quelle, $grad, 0);
            if ($gedreht) {
                imagedestroy($quelle);
                $quelle = $gedreht;
                $breite = imagesx($quelle);
                $hoehe  = imagesy($quelle);
            }
        }
    }

    if (!is_dir(GALERIE_DIR) && !@mkdir(GALERIE_DIR, 0755, true)) {
        return 'Der Galerie-Ordner konnte nicht angelegt werden.';
    }

    $basis = date('Ymd') . '-' . bin2hex(random_bytes(8));
    $datei = $basis . '.webp';
    $thumb = $basis . '-thumb.webp';

    $gross = galerie_skalieren($quelle, GALERIE_MAX_KANTE);
    $klein = galerie_skalieren($quelle, GALERIE_THUMB_KANTE);
    imagedestroy($quelle);

    $ok = imagewebp($gross, GALERIE_DIR . '/' . $datei, 82)
       && imagewebp($klein, GALERIE_DIR . '/' . $thumb, 78);

    $out_breite = imagesx($gross);
    $out_hoehe  = imagesy($gross);
    imagedestroy($gross);
    imagedestroy($klein);

    if (!$ok) {
        @unlink(GALERIE_DIR . '/' . $datei);
        @unlink(GALERIE_DIR . '/' . $thumb);
        return 'Das Bild konnte nicht gespeichert werden.';
    }

    return ['datei' => $datei, 'thumb' => $thumb, 'breite' => $out_breite, 'hoehe' => $out_hoehe];
}

/** Skaliert proportional auf eine maximale Kantenlänge (vergrößert nie). */
function galerie_skalieren(GdImage $quelle, int $max_kante): GdImage {
    $b = imagesx($quelle);
    $h = imagesy($quelle);
    $faktor = min(1, $max_kante / max($b, $h));
    $nb = max(1, (int)round($b * $faktor));
    $nh = max(1, (int)round($h * $faktor));

    $ziel = imagecreatetruecolor($nb, $nh);
    imagealphablending($ziel, false);
    imagesavealpha($ziel, true);
    imagecopyresampled($ziel, $quelle, 0, 0, 0, 0, $nb, $nh, $b, $h);
    return $ziel;
}

/** Löscht Bild- und Thumbnail-Datei aus dem Galerie-Ordner. */
function galerie_dateien_loeschen(string $datei, string $thumb): void {
    foreach ([$datei, $thumb] as $name) {
        if ($name === '') continue;
        $pfad = GALERIE_DIR . '/' . basename($name);
        if (is_file($pfad)) @unlink($pfad);
    }
}
