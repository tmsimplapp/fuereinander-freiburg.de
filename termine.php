<?php
require_once __DIR__ . '/buchung-config.php';

$pdo = new PDO(
    'mysql:host=' . DB_HOST . ';dbname=' . DB_NAME . ';charset=utf8mb4',
    DB_USER, DB_PASS,
    [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION, PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC]
);

$stmt = $pdo->query(
    "SELECT termin_datum, uhrzeiten, slot_laenge_min, bemerkung, max_teilnehmer
     FROM slot_konfiguration
     WHERE aktiv = 1 AND termin_datum >= CURDATE()
     ORDER BY termin_datum ASC"
);
$termine_db = $stmt->fetchAll();

$wochentage = ['Montag','Dienstag','Mittwoch','Donnerstag','Freitag','Samstag','Sonntag'];
$monate     = ['Januar','Februar','März','April','Mai','Juni','Juli','August','September','Oktober','November','Dezember'];

function format_datum(string $datum, array $wochentage, array $monate): string {
    $ts  = strtotime($datum);
    $dow = (int)date('N', $ts) - 1;
    $mon = (int)date('n', $ts) - 1;
    return $wochentage[$dow] . ', ' . (int)date('j', $ts) . '. ' . $monate[$mon] . ' ' . date('Y', $ts);
}

function format_zeitraum(string $uhrzeiten_json, int $dauer_min): string {
    $arr = json_decode($uhrzeiten_json, true);
    if (empty($arr)) return '';
    $start = $arr[0];
    [$h, $m] = explode(':', $start);
    $end_min = (int)$h * 60 + (int)$m + $dauer_min;
    return $start . ' – ' . sprintf('%02d:%02d', intdiv($end_min, 60), $end_min % 60) . ' Uhr';
}
?>
<!DOCTYPE html>
<html lang="de">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <title>Termine Selbsthilfegruppe Zeugen Jehovas in Freiburg</title>
  <meta name="description" content="Aktuelle Termine der monatlichen Treffen für ehemalige Zeugen Jehovas im Selbsthilfebüro Freiburg. Unverbindliche Teilnahme und geschützter Rahmen." />
  <meta name="keywords" content="Termine Zeugen Jehovas Aussteiger, Treffen Selbsthilfegruppe Freiburg, monatlicher Austausch" />
  <meta name="author" content="Selbsthilfegruppe Füreinander Freiburg" />
  <meta name="robots" content="index, follow" />
  <link rel="canonical" href="https://fuereinander-freiburg.de/termine.php" />

  <!-- Open Graph -->
  <meta property="og:title" content="Termine – Füreinander Freiburg" />
  <meta property="og:description" content="Nächste Treffen der Selbsthilfegruppe Füreinander Freiburg – monatlich im Selbsthilfebüro Freiburg. Kostenlos, anonym, ohne Verpflichtung." />
  <meta property="og:type" content="website" />
  <meta property="og:url" content="https://fuereinander-freiburg.de/termine.php" />
  <meta property="og:image" content="https://fuereinander-freiburg.de/grafik/og-image.png" />
  <meta property="og:image:width" content="1200" />
  <meta property="og:image:height" content="630" />
  <meta property="og:site_name" content="Füreinander Freiburg" />
  <meta property="og:locale" content="de_DE" />

  <link rel="icon" href="grafik/F%C3%BCreinander%20Freiburg.svg" type="image/svg+xml" />
  <link rel="apple-touch-icon" href="grafik/apple-touch-icon.png" />
  <meta name="mobile-web-app-capable" content="yes" />
  <meta name="apple-mobile-web-app-capable" content="yes" />
  <meta name="apple-mobile-web-app-status-bar-style" content="black-translucent" />
  <link rel="manifest" href="site.webmanifest" />
  <meta name="theme-color" content="#a9e2cc" />

  <link rel="stylesheet" href="tailwind.css?v=1" />

  <link rel="preconnect" href="https://fonts.googleapis.com" />
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin />
  <link href="https://fonts.googleapis.com/css2?family=Lato:ital,wght@0,300;0,400;0,700;1,300;1,400;1,700&display=swap" rel="stylesheet" />

  <link rel="stylesheet" href="styles.css?v=1" />
  <link rel="stylesheet" href="transitions.css?v=1" />

  <script type="application/ld+json">
  {
    "@context": "https://schema.org",
    "@type": "EventSeries",
    "name": "Monatliche Treffen der Selbsthilfegruppe Füreinander Freiburg",
    "description": "Regelmäßige Treffen für zweifelnde und ausgestiegene Zeugen Jehovas in Freiburg.",
    "location": {
      "@type": "Place",
      "name": "Selbsthilfebüro Freiburg",
      "address": {
        "@type": "PostalAddress",
        "addressLocality": "Freiburg im Breisgau",
        "addressCountry": "DE"
      }
    },
    "organizer": {
      "@type": "Organization",
      "name": "Füreinander Freiburg",
      "url": "https://fuereinander-freiburg.de/"
    },
    "image": "https://fuereinander-freiburg.de/grafik/og-image.webp",
    "offers": {
      "@type": "Offer",
      "price": "0",
      "priceCurrency": "EUR",
      "availability": "https://schema.org/InStock",
      "url": "https://fuereinander-freiburg.de/termine.php"
    },
    "isAccessibleForFree": true
  }
  </script>
</head>

<body class="antialiased">

  <div aria-live="polite" class="sr-only" id="menu-status"></div>

  <!-- NAVIGATION -->
  <nav class="fixed bottom-0 md:bottom-auto md:top-0 left-0 right-0 z-50 bg-cream/80 backdrop-blur-md border-t md:border-t-0 md:border-b border-tan/20" style="padding-bottom: env(safe-area-inset-bottom, 0px);">
    <div class="max-w-5xl mx-auto px-6 py-4 flex items-center justify-center md:justify-between">
      <a href="index.html" aria-label="Füreinander Freiburg – Startseite" class="flex items-center">
        <img src="grafik/F%C3%BCreinander%20Freiburg.svg" alt="Logo der Selbsthilfegruppe Füreinander Freiburg" class="h-8 w-auto opacity-90" width="120" height="32">
      </a>
      <div class="hidden md:flex items-center gap-6">
        <a href="index.html" class="nav-link font-body text-sm">Startseite</a>
        <a href="ausstieg-folgen.html" class="nav-link font-body text-sm">Ausstiegsfolgen</a>
        <a href="angehoerige.html" class="nav-link font-body text-sm">Angehörige</a>
        <a href="partner.html" class="nav-link font-body text-sm">Partner</a>
        <a href="termine.php" class="nav-link font-body text-sm font-semibold text-text-strong">Termine</a>
        <a href="index.html#kontakt" class="btn-primary glowing-border font-body text-sm font-semibold px-5 py-2.5 rounded-full active:scale-95">Kontakt</a>
      </div>
    </div>
  </nav>

  <!-- Floating Action Button (FAB) – nur Mobile -->
  <button id="nav-toggle-fab"
          class="md:hidden mobile-fab"
          aria-label="Menü öffnen"
          aria-expanded="false"
          aria-controls="mobile-menu">
    <span class="block w-6 h-0.5 transition-all bg-dark"></span>
    <span class="block w-6 h-0.5 transition-all bg-dark"></span>
    <span class="block w-6 h-0.5 transition-all bg-dark"></span>
  </button>

  <!-- Mobile Menü -->
  <div id="mobile-menu" class="mobile-menu-overlay md:hidden hidden">
    <div class="mobile-menu-sheet">
      <div class="flex flex-col gap-2 items-stretch">
        <a href="index.html" class="mobile-nav-link font-body text-base font-medium py-3 px-6 rounded-full transition-all text-center text-text-body bg-lightyellow border border-tan" style="min-height: 44px; display: flex; align-items: center; justify-content: center">Startseite</a>
        <a href="ausstieg-folgen.html" class="mobile-nav-link font-body text-base font-medium py-3 px-6 rounded-full transition-all text-center text-text-body bg-lightyellow border border-tan" style="min-height: 44px; display: flex; align-items: center; justify-content: center">Ausstiegsfolgen</a>
        <a href="angehoerige.html" class="mobile-nav-link font-body text-base font-medium py-3 px-6 rounded-full transition-all text-center text-text-body bg-lightyellow border border-tan" style="min-height: 44px; display: flex; align-items: center; justify-content: center">Angehörige</a>
        <a href="partner.html" class="mobile-nav-link font-body text-base font-medium py-3 px-6 rounded-full transition-all text-center text-text-body bg-lightyellow border border-tan" style="min-height: 44px; display: flex; align-items: center; justify-content: center">Partner</a>
        <a href="termine.php" aria-current="page" class="mobile-nav-link font-body text-base font-semibold py-3 px-6 rounded-full transition-all text-center bg-mint border border-mint" style="min-height: 44px; display: flex; align-items: center; justify-content: center; color:#1a2820">Termine</a>
        <a href="rechtliches.html" class="mobile-nav-link font-body text-base font-medium py-3 px-6 rounded-full transition-all text-center text-text-body bg-lightyellow border border-tan" style="min-height: 44px; display: flex; align-items: center; justify-content: center">Rechtliches</a>
        <a href="index.html#kontaktformular" class="font-body text-base font-bold py-3 px-6 rounded-full transition-all text-center mt-2 shadow-sm bg-mint border border-mint-dark" style="min-height: 44px; display: flex; align-items: center; justify-content: center; color:#1a2820">Kontaktformular</a>
        <div class="my-2 bg-tan" style="height:1px"></div>
        <a href="tel:+4915567465016" class="font-body text-base font-semibold py-3 px-6 rounded-full transition-all text-center flex items-center justify-center gap-3 bg-mint border border-mint-dark" style="min-height: 48px; color:#1a2820" aria-label="Anrufen: 01556 7465016">
          <svg class="w-5 h-5" fill="none" stroke="currentColor" stroke-width="1.8" viewBox="0 0 24 24" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="M3 5a2 2 0 012-2h3.28a1 1 0 01.948.684l1.498 4.493a1 1 0 01-.502 1.21l-2.257 1.13a11.042 11.042 0 005.516 5.516l1.13-2.257a1 1 0 011.21-.502l4.493 1.498a1 1 0 01.684.949V19a2 2 0 01-2 2h-1C9.716 21 3 14.284 3 6V5z"/></svg>
          <span>01556 / 7465016</span>
        </a>
        <div class="grid grid-cols-2 gap-3 mt-3">
          <a href="https://wa.me/4915567465016" target="_blank" rel="noopener noreferrer" class="flex flex-col items-center justify-center gap-2 py-4 px-3 rounded-2xl transition-all bg-lightyellow border border-tan" style="min-height:88px">
            <svg class="w-8 h-8" fill="#3d3225" viewBox="0 0 24 24" aria-hidden="true"><path d="M17.472 14.382c-.297-.149-1.758-.867-2.03-.967-.273-.099-.471-.148-.67.15-.197.297-.767.966-.94 1.164-.173.199-.347.223-.644.075-.297-.15-1.255-.463-2.39-1.475-.883-.788-1.48-1.761-1.653-2.059-.173-.297-.018-.458.13-.606.134-.133.298-.347.446-.52.149-.174.198-.298.298-.497.099-.198.05-.371-.025-.52-.075-.149-.669-1.612-.916-2.207-.242-.579-.487-.5-.669-.51-.173-.008-.371-.01-.57-.01-.198 0-.52.074-.792.372-.272.297-1.04 1.016-1.04 2.479 0 1.462 1.065 2.875 1.213 3.074.149.198 2.096 3.2 5.077 4.487.709.306 1.262.489 1.694.625.712.227 1.36.195 1.871.118.571-.085 1.758-.719 2.006-1.413.248-.694.248-1.289.173-1.413-.074-.124-.272-.198-.57-.347m-5.421 7.403h-.004a9.87 9.87 0 01-5.031-1.378l-.361-.214-3.741.982.998-3.648-.235-.374a9.86 9.86 0 01-1.51-5.26c.001-5.45 4.436-9.884 9.888-9.884 2.64 0 5.122 1.03 6.988 2.898a9.825 9.825 0 012.893 6.994c-.003 5.45-4.437 9.884-9.885 9.884m8.413-18.297A11.815 11.815 0 0012.05 0C5.495 0 .16 5.335.157 11.892c0 2.096.547 4.142 1.588 5.945L.057 24l6.305-1.654a11.882 11.882 0 005.683 1.448h.005c6.554 0 11.89-5.335 11.893-11.893a11.821 11.821 0 00-3.48-8.413z"/></svg>
            <span class="font-body text-sm font-medium text-center text-text-strong">WhatsApp</span>
          </a>
          <a href="tel:+4915567465016" class="flex flex-col items-center justify-center gap-2 py-4 px-3 rounded-2xl transition-all bg-tan border border-tan" style="min-height:88px" aria-label="Anrufen: 01556 7465016">
            <svg class="w-8 h-8" fill="none" stroke="#3d3225" stroke-width="1.8" viewBox="0 0 24 24" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="M3 5a2 2 0 012-2h3.28a1 1 0 01.948.684l1.498 4.493a1 1 0 01-.502 1.21l-2.257 1.13a11.042 11.042 0 005.516 5.516l1.13-2.257a1 1 0 011.21-.502l4.493 1.498a1 1 0 01.684.949V19a2 2 0 01-2 2h-1C9.716 21 3 14.284 3 6V5z"/></svg>
            <span class="font-body text-sm font-medium text-center text-text-strong">Anrufen</span>
          </a>
          <a href="https://t.me/+4915567465016" target="_blank" rel="noopener noreferrer" class="flex flex-col items-center justify-center gap-2 py-4 px-3 rounded-2xl transition-all bg-lightyellow border border-tan" style="min-height:88px">
            <svg class="w-8 h-8" fill="#3d3225" viewBox="0 0 24 24" aria-hidden="true"><path d="M11.944 0A12 12 0 0 0 0 12a12 12 0 0 0 12 12 12 12 0 0 0 12-12A12 12 0 0 0 12 0a12 12 0 0 0-.056 0zm4.962 7.224c.1-.002.321.023.465.14a.506.506 0 0 1 .171.325c.016.093.036.306.02.472-.18 1.898-.962 6.502-1.36 8.627-.168.9-.499 1.201-.82 1.23-.696.065-1.225-.46-1.9-.902-1.056-.693-1.653-1.124-2.678-1.8-1.185-.78-.417-1.21.258-1.91.177-.184 3.247-2.977 3.307-3.23.007-.032.014-.15-.056-.212s-.174-.041-.249-.024c-.106.024-1.793 1.14-5.061 3.345-.48.33-.913.49-1.302.48-.428-.008-1.252-.241-1.865-.44-.752-.245-1.349-.374-1.297-.789.027-.216.325-.437.893-.663 3.498-1.524 5.83-2.529 6.998-3.014 3.332-1.386 4.025-1.627 4.476-1.635z"/></svg>
            <span class="font-body text-sm font-medium text-center text-text-strong">Telegram</span>
          </a>
          <a href="mailto:kontakt@fuereinander-freiburg.de" class="flex flex-col items-center justify-center gap-2 py-4 px-3 rounded-2xl transition-all bg-tan border border-tan" style="min-height:88px">
            <svg class="w-8 h-8" fill="none" stroke="#3d3225" stroke-width="1.8" viewBox="0 0 24 24" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"/></svg>
            <span class="font-body text-sm font-medium text-center text-text-strong">E-Mail</span>
          </a>
        </div>
      </div>
    </div>
  </div>

  <!-- HAUPTINHALT -->
  <main class="pt-8 md:pt-28 pb-20 min-h-screen">
    <div class="max-w-3xl mx-auto px-6">

      <nav aria-label="Breadcrumb" class="mb-6 text-center md:hidden">
        <a href="index.html" class="nav-link font-body text-xs">Startseite</a>
        <span class="font-body text-xs mx-2 text-text-muted">/</span>
        <span class="font-body text-xs font-semibold text-text-strong">Termine</span>
      </nav>

      <div class="mb-12 text-center">
        <p class="font-body text-sm uppercase tracking-widest mb-3 text-text-muted">Nächste Treffen</p>
        <h1 class="font-display text-4xl md:text-5xl font-bold mb-4 text-text-strong">
          <span class="sr-only">Termine der Selbsthilfegruppe Freiburg: </span>Termine
        </h1>
        <div class="w-12 h-0.5 bg-mint mx-auto"></div>
      </div>

      <section class="mb-8" aria-labelledby="termine-heading">
        <h2 id="termine-heading" class="sr-only">Nächste Treffen</h2>
        <div class="grid gap-6">
          <?php if (empty($termine_db)): ?>
            <p class="font-body text-base text-text-muted text-center py-8">
              Aktuell sind keine Termine eingetragen. Bitte melde dich direkt bei uns.
            </p>
          <?php else: ?>
            <?php foreach ($termine_db as $t): ?>
            <div class="rounded-2xl p-6 sm:p-8 card-hover bg-cream border border-mint">
              <?php if (!empty($t['max_teilnehmer'])): ?>
              <span class="inline-flex items-center gap-1.5 font-body text-sm px-3 py-1 rounded-full bg-lightyellow border border-tan text-text-body mb-4">
                <svg class="w-4 h-4 flex-shrink-0" fill="none" stroke="currentColor" stroke-width="1.8" viewBox="0 0 24 24" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0z"/></svg>
                Kleine Gruppe · max. <?= (int)$t['max_teilnehmer'] ?> Personen
              </span>
              <?php endif; ?>
              <div class="flex items-center gap-4 mb-4">
                <div class="w-12 h-12 rounded-full flex items-center justify-center bg-mint">
                  <svg class="w-6 h-6" fill="none" stroke="#1a2820" stroke-width="1.8" viewBox="0 0 24 24" aria-hidden="true">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"/>
                  </svg>
                </div>
                <div>
                  <strong class="font-display text-lg block text-text-strong">
                    <?= htmlspecialchars(format_datum($t['termin_datum'], $wochentage, $monate), ENT_QUOTES, 'UTF-8') ?>
                  </strong>
                  <p class="font-body text-base text-text-body">
                    <?= htmlspecialchars(format_zeitraum($t['uhrzeiten'], (int)$t['slot_laenge_min']), ENT_QUOTES, 'UTF-8') ?>
                  </p>
                </div>
              </div>
              <p class="font-body text-sm mt-4 text-text-muted">Ort: Freiburg im Breisgau (genauer Ort nach Anmeldung)</p>
              <?php if (!empty($t['bemerkung'])): ?>
                <p class="font-body text-sm mt-4" style="background:#fff4d6;border:1px solid #E2C2A2;border-radius:10px;padding:.65rem .9rem;color:#5c4e3a">
                  <?= htmlspecialchars($t['bemerkung'], ENT_QUOTES, 'UTF-8') ?>
                </p>
              <?php endif; ?>
            </div>
            <?php endforeach; ?>
          <?php endif; ?>
        </div>
      </section>

      <section class="mt-16 text-center" aria-labelledby="anmeldung-heading">
        <h2 id="anmeldung-heading" class="font-display text-xl font-semibold mb-4 text-text-strong">Möchtest du am nächsten Treffen teilnehmen?</h2>
        <p class="font-body text-base max-w-xl mx-auto mb-4 text-text-body">
          Um den geschützten Rahmen der Gruppe zu wahren und dir die genauen Raumdetails zuzusenden, bitten wir dich um eine kurze Anmeldung vor deinem ersten Besuch. Spontanes Erscheinen ist leider nicht möglich.
        </p>
        <p class="font-body text-sm max-w-xl mx-auto mb-6 text-text-muted">Unsere Treffen sind klein, damit du dich vom ersten Moment an aufgehoben fühlst.</p>
        <a href="index.html#kontakt" class="btn-primary glowing-border font-body text-sm font-semibold px-6 py-3 rounded-full inline-block active:scale-95">Jetzt anmelden</a>
      </section>

    </div>
  </main>

  <footer class="footer-glass">
    <div class="w-full py-4 text-center">
      <nav aria-label="Footer-Navigation" class="mb-3">
        <ul class="flex flex-wrap gap-4 justify-center">
          <li><a href="rechtliches.html" class="footer-link font-body text-xs">Rechtliches</a></li>
          <li><span class="font-body text-xs text-text-footer">·</span></li>
          <li><a href="ausstieg-folgen.html" class="footer-link font-body text-xs">Ausstiegsfolgen</a></li>
          <li><span class="font-body text-xs text-text-footer">·</span></li>
          <li><a href="angehoerige.html" class="footer-link font-body text-xs">Angehörige</a></li>
          <li><span class="font-body text-xs text-text-footer">·</span></li>
          <li><a href="termine.php" class="font-body text-xs font-semibold text-text-strong">Termine</a></li>
          <li><span class="font-body text-xs text-text-footer">·</span></li>
          <li><a href="partner.html" class="footer-link font-body text-xs">Partner</a></li>
        </ul>
      </nav>
      <p class="font-body text-xs text-text-footer">
        &copy; <span id="year"></span> Füreinander Freiburg · Selbsthilfegruppe für zweifelnde und ausgestiegene Zeugen Jehovas
      </p>
    </div>
  </footer>

  <script src="main.js" defer></script>

  <aside id="sticky-phone-bar" class="sticky-phone-bar" role="complementary" aria-label="Schnellkontakt">
    <div class="sticky-phone-bar-content">
      <a href="tel:+4915567465016" class="sticky-phone-bar-link md:hidden" aria-label="Jetzt anrufen: 01556 7465016">
        <div class="sticky-phone-number">
          <svg class="w-5 h-5 flex-shrink-0" fill="none" stroke="currentColor" stroke-width="1.8" viewBox="0 0 24 24" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="M3 5a2 2 0 012-2h3.28a1 1 0 01.948.684l1.498 4.493a1 1 0 01-.502 1.21l-2.257 1.13a11.042 11.042 0 005.516 5.516l1.13-2.257a1 1 0 011.21-.502l4.493 1.498a1 1 0 01.684.949V19a2 2 0 01-2 2h-1C9.716 21 3 14.284 3 6V5z"/></svg>
          <span class="font-body">01556 / 7465016</span>
        </div>
      </a>
      <div class="sticky-phone-number hidden md:flex">
        <svg class="w-6 h-6 flex-shrink-0" fill="none" stroke="currentColor" stroke-width="1.8" viewBox="0 0 24 24" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="M3 5a2 2 0 012-2h3.28a1 1 0 01.948.684l1.498 4.493a1 1 0 01-.502 1.21l-2.257 1.13a11.042 11.042 0 005.516 5.516l1.13-2.257a1 1 0 011.21-.502l4.493 1.498a1 1 0 01.684.949V19a2 2 0 01-2 2h-1C9.716 21 3 14.284 3 6V5z"/></svg>
        <span class="font-body">01556 / 7465016</span>
      </div>
      <a href="tel:+4915567465016" class="sticky-phone-button font-body hidden md:flex" aria-label="Jetzt anrufen: 01556 7465016">
        <svg class="w-5 h-5 flex-shrink-0" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="M3 5a2 2 0 012-2h3.28a1 1 0 01.948.684l1.498 4.493a1 1 0 01-.502 1.21l-2.257 1.13a11.042 11.042 0 005.516 5.516l1.13-2.257a1 1 0 011.21-.502l4.493 1.498a1 1 0 01.684.949V19a2 2 0 01-2 2h-1C9.716 21 3 14.284 3 6V5z"/></svg>
        <span>Anrufen</span>
      </a>
    </div>
  </aside>

</body>
</html>
