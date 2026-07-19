<?php
$active_page = 'termine';
require_once __DIR__ . '/buchung-config.php';

$pdo = new PDO(
    'mysql:host=' . DB_HOST . ';dbname=' . DB_NAME . ';charset=utf8mb4',
    DB_USER, DB_PASS,
    [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION, PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC]
);

$stmt = $pdo->query(
    "SELECT termin_datum, uhrzeiten, slot_laenge_min, bemerkung, max_teilnehmer, ausgebucht
     FROM gruppentermine
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

<body class="antialiased flex flex-col min-h-screen">

  <?php include __DIR__ . '/partials/nav.php'; ?>

  <!-- HAUPTINHALT -->
  <main class="pt-8 md:pt-28 pb-20 flex-grow">
    <div class="max-w-3xl mx-auto px-6">

      <nav aria-label="Breadcrumb" class="mb-6 text-center md:hidden">
        <a href="index.php" class="nav-link font-body text-xs">Startseite</a>
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
              <?php if (!empty($t['ausgebucht'])): ?>
              <span class="inline-flex items-center gap-1.5 font-body text-sm px-4 py-1 rounded-full border mb-4 font-semibold" style="background:#fff0f0;border-color:#ffcdd2;color:#c62828">
                <svg class="w-4 h-4 flex-shrink-0" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="M18.364 18.364A9 9 0 005.636 5.636m12.728 12.728A9 9 0 015.636 5.636m12.728 12.728L5.636 5.636"/></svg>
                Ausgebucht
              </span>
              <?php elseif (!empty($t['max_teilnehmer'])): ?>
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
        <a href="index.php#kontakt" class="btn-primary glowing-border font-body text-sm font-semibold px-6 py-3 rounded-full inline-block active:scale-95">Jetzt anmelden</a>
      </section>

    </div>
  </main>

  <?php include __DIR__ . '/partials/footer.php'; ?>

  <script src="main.js" defer></script>

  <?php include __DIR__ . '/partials/sticky-phone-bar.php'; ?>

</body>
</html>
