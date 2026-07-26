<?php
$active_page = 'galerie';
require_once __DIR__ . '/galerie-helpers.php';

$bilder = galerie_bilder_aktiv(galerie_db());

function g_e(string $s): string {
    return htmlspecialchars($s, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
}
?>
<!DOCTYPE html>
<html lang="de">
<head>
  <base href="/" />
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <title>Bildergalerie – Treffen der Selbsthilfegruppe Füreinander Freiburg</title>
  <meta name="description" content="Eindrücke von den Treffen der Selbsthilfegruppe Füreinander Freiburg für zweifelnde und ausgestiegene Zeugen Jehovas." />
  <meta name="author" content="Selbsthilfegruppe Füreinander Freiburg" />
  <meta name="robots" content="index, follow" />
  <link rel="canonical" href="https://fuereinander-freiburg.de/galerie.php" />

  <!-- Open Graph -->
  <meta property="og:title" content="Bildergalerie – Füreinander Freiburg" />
  <meta property="og:description" content="Eindrücke von den Treffen der Selbsthilfegruppe Füreinander Freiburg." />
  <meta property="og:type" content="website" />
  <meta property="og:url" content="https://fuereinander-freiburg.de/galerie.php" />
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
</head>
<body class="antialiased flex flex-col min-h-screen">

  <?php include __DIR__ . '/partials/nav.php'; ?>

  <!-- HAUPTINHALT -->
  <main class="pt-8 md:pt-28 pb-20 flex-grow">
    <div class="max-w-5xl mx-auto px-6">

      <nav aria-label="Breadcrumb" class="mb-6 text-center md:hidden">
        <a href="index.php" class="nav-link font-body text-xs">Startseite</a>
        <span class="font-body text-xs mx-2 text-text-muted">/</span>
        <span class="font-body text-xs font-semibold text-text-strong">Galerie</span>
      </nav>

      <div class="mb-12 text-center">
        <p class="font-body text-sm uppercase tracking-widest mb-3 text-text-muted">Eindrücke</p>
        <h1 class="font-display text-4xl md:text-5xl font-bold mb-4 text-text-strong">
          <span class="sr-only">Bildergalerie der Selbsthilfegruppe Freiburg: </span>Galerie
        </h1>
        <div class="w-12 h-0.5 bg-mint mx-auto"></div>
        <p class="font-body text-base max-w-xl mx-auto mt-6 text-text-body">
          Ein paar Eindrücke von unseren Treffen. Klicke auf ein Bild, um es größer zu sehen und durchzublättern.
        </p>
      </div>

      <?php if (empty($bilder)): ?>
        <p class="font-body text-base text-text-muted text-center py-8">
          Aktuell sind noch keine Fotos hinterlegt. Schau gern später wieder vorbei.
        </p>
      <?php else: ?>
      <section aria-labelledby="galerie-heading">
        <h2 id="galerie-heading" class="sr-only">Fotos von unseren Treffen</h2>

        <ul class="galerie-grid" id="galerie-grid">
          <?php foreach ($bilder as $i => $b):
            $alt   = $b['alt_text'] !== '' ? $b['alt_text'] : ($b['titel'] !== '' ? $b['titel'] : 'Foto von einem Treffen der Selbsthilfegruppe');
            $thumb = $b['thumb'] !== '' ? $b['thumb'] : $b['datei'];
          ?>
          <li>
            <button type="button" class="galerie-item card-hover"
                    data-index="<?= (int)$i ?>"
                    data-voll="<?= GALERIE_URL ?>/<?= g_e($b['datei']) ?>"
                    data-alt="<?= g_e($alt) ?>"
                    data-titel="<?= g_e($b['titel']) ?>"
                    aria-label="Bild <?= (int)$i + 1 ?> von <?= count($bilder) ?> groß anzeigen<?= $b['titel'] !== '' ? ': ' . g_e($b['titel']) : '' ?>">
              <img src="<?= GALERIE_URL ?>/<?= g_e($thumb) ?>" alt="<?= g_e($alt) ?>"
                   loading="<?= $i < 3 ? 'eager' : 'lazy' ?>" decoding="async"
                   width="<?= (int)$b['breite'] ?: 600 ?>" height="<?= (int)$b['hoehe'] ?: 400 ?>">
              <?php if ($b['titel'] !== ''): ?>
                <span class="galerie-item-titel font-body text-sm"><?= g_e($b['titel']) ?></span>
              <?php endif; ?>
            </button>
          </li>
          <?php endforeach; ?>
        </ul>
      </section>

      <!-- Lightbox -->
      <div class="lightbox" id="lightbox" role="dialog" aria-modal="true" aria-label="Bildansicht" hidden>
        <button type="button" class="lightbox-close" id="lightbox-close" aria-label="Bildansicht schließen">
          <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" aria-hidden="true"><path d="M18 6 6 18M6 6l12 12"/></svg>
        </button>
        <button type="button" class="lightbox-nav lightbox-prev" id="lightbox-prev" aria-label="Vorheriges Bild">
          <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="m15 18-6-6 6-6"/></svg>
        </button>
        <figure class="lightbox-figur">
          <img id="lightbox-bild" src="" alt="">
          <figcaption class="lightbox-caption font-body text-sm">
            <span id="lightbox-titel"></span>
            <span class="lightbox-zaehler" id="lightbox-zaehler"></span>
          </figcaption>
        </figure>
        <button type="button" class="lightbox-nav lightbox-next" id="lightbox-next" aria-label="Nächstes Bild">
          <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="m9 18 6-6-6-6"/></svg>
        </button>
        <p class="sr-only" aria-live="polite" id="lightbox-status"></p>
      </div>
      <?php endif; ?>

      <section class="mt-16 text-center" aria-labelledby="galerie-cta-heading">
        <h2 id="galerie-cta-heading" class="font-display text-xl font-semibold mb-4 text-text-strong">Neugierig geworden?</h2>
        <p class="font-body text-base max-w-xl mx-auto mb-6 text-text-body">
          Unsere Treffen finden monatlich in Freiburg statt – kostenlos, vertraulich und ohne Verpflichtung.
        </p>
        <a href="termine.php" class="btn-primary glowing-border font-body text-sm font-semibold px-6 py-3 rounded-full inline-block active:scale-95">Termine ansehen</a>
      </section>

    </div>
  </main>

  <?php include __DIR__ . '/partials/footer.php'; ?>

  <script src="main.js" defer></script>
  <script src="galerie.js" defer></script>

  <?php include __DIR__ . '/partials/sticky-phone-bar.php'; ?>

</body>
</html>
