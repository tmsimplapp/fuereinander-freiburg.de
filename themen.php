<?php
header("Cache-Control: no-store, no-cache, must-revalidate, max-age=0");
header("Cache-Control: post-check=0, pre-check=0", false);
header("Pragma: no-cache");

// Wenn slug per GET, PATH_INFO oder REQUEST_URI übergeben wird (z. B. /themen/slug)
$slug_param = $_GET['slug'] ?? '';
if (empty($slug_param) && !empty($_SERVER['PATH_INFO'])) {
    $slug_param = trim($_SERVER['PATH_INFO'], '/');
}
if (empty($slug_param) && !empty($_SERVER['REQUEST_URI'])) {
    $req_path = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);
    if (preg_match('#^/themen/([^/]+)$#i', $req_path, $m) && $m[1] !== 'index.php') {
        $slug_param = urldecode($m[1]);
    }
}

if (!empty($slug_param)) {
    $_GET['slug'] = $slug_param;
    require __DIR__ . '/themen-detail.php';
    exit;
}

$active_page = 'themen';
require_once __DIR__ . '/buchung-config.php';
require_once __DIR__ . '/themen-helpers.php';

$pdo = new PDO(
    'mysql:host=' . DB_HOST . ';dbname=' . DB_NAME . ';charset=utf8mb4',
    DB_USER, DB_PASS,
    [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION, PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC]
);

$kat_filter = !empty($_GET['kategorie']) ? trim($_GET['kategorie']) : '';
$tag_filter = !empty($_GET['tag']) ? trim($_GET['tag']) : '';

if ($tag_filter !== '') {
    $stmt = $pdo->prepare(
        "SELECT id, slug, titel, teaser, kategorie, tags, lesedauer_min, created_at
         FROM artikel
         WHERE is_published = 1 AND FIND_IN_SET(?, REPLACE(tags, ', ', ',')) > 0
         ORDER BY created_at DESC"
    );
    $stmt->execute([$tag_filter]);
} elseif ($kat_filter !== '') {
    $stmt = $pdo->prepare(
        "SELECT id, slug, titel, teaser, kategorie, tags, lesedauer_min, created_at
         FROM artikel
         WHERE is_published = 1 AND kategorie = ?
         ORDER BY created_at DESC"
    );
    $stmt->execute([$kat_filter]);
} else {
    $stmt = $pdo->query(
        "SELECT id, slug, titel, teaser, kategorie, tags, lesedauer_min, created_at
         FROM artikel
         WHERE is_published = 1
         ORDER BY created_at DESC"
    );
}
$themen_db = $stmt->fetchAll();

// Alle Kategorien & Tags für Filter holen
$kat_stmt = $pdo->query("SELECT DISTINCT kategorie FROM artikel WHERE is_published = 1 ORDER BY kategorie ASC");
$kategorien = $kat_stmt->fetchAll(PDO::FETCH_COLUMN);

// Alle unterschiedlichen Tags ermitteln
$all_tags_raw = $pdo->query("SELECT tags FROM artikel WHERE is_published = 1 AND tags IS NOT NULL AND tags != ''")->fetchAll(PDO::FETCH_COLUMN);
$all_tags = [];
foreach ($all_tags_raw as $t_str) {
    foreach (explode(',', $t_str) as $single_t) {
        $single_t = trim($single_t);
        if ($single_t !== '' && !in_array($single_t, $all_tags, true)) {
            $all_tags[] = $single_t;
        }
    }
}
sort($all_tags);
?>
<!DOCTYPE html>
<html lang="de">
<head>
  <base href="/" />
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <title>Themen & Ratgeber – Füreinander Freiburg</title>
  <meta name="description" content="Informative Artikel, Ratgeber und Erfahrungswerte für zweifelnde und ausgestiegene Zeugen Jehovas sowie deren Angehörige." />
  <meta name="robots" content="index, follow" />
  <link rel="canonical" href="https://fuereinander-freiburg.de/themen" />

  <!-- Open Graph -->
  <meta property="og:title" content="Themen & Ratgeber – Füreinander Freiburg" />
  <meta property="og:description" content="Informationen und Orientierung zu Ausstiegsfolgen, Hilfsangeboten und Selbsthilfe." />
  <meta property="og:type" content="website" />
  <meta property="og:url" content="https://fuereinander-freiburg.de/themen" />
  <meta property="og:image" content="https://fuereinander-freiburg.de/grafik/og-image.png" />
  <meta property="og:site_name" content="Füreinander Freiburg" />
  <meta property="og:locale" content="de_DE" />

  <link rel="icon" href="grafik/F%C3%BCreinander%20Freiburg.svg" type="image/svg+xml" />
  <link rel="manifest" href="site.webmanifest" />
  <meta name="theme-color" content="#a9e2cc" />

  <link rel="stylesheet" href="tailwind.css?v=2" />
  <link rel="preconnect" href="https://fonts.googleapis.com" />
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin />
  <link href="https://fonts.googleapis.com/css2?family=Lato:ital,wght@0,300;0,400;0,700;1,300;1,400;1,700&display=swap" rel="stylesheet" />
  <link rel="stylesheet" href="styles.css?v=2" />
  <link rel="stylesheet" href="transitions.css?v=2" />

  <script type="application/ld+json">
  {
    "@context": "https://schema.org",
    "@type": "CollectionPage",
    "name": "Themen & Ratgeber – Füreinander Freiburg",
    "description": "Informative Beitragsübersicht zur Orientierung bei Ausstieg und Aufarbeitung.",
    "url": "https://fuereinander-freiburg.de/themen"
  }
  </script>
</head>

<body class="antialiased flex flex-col min-h-screen">

  <?php include __DIR__ . '/partials/nav.php'; ?>

  <main class="pt-8 md:pt-28 pb-20 flex-grow">
    <div class="max-w-4xl mx-auto px-6">

      <nav aria-label="Breadcrumb" class="mb-6 text-center md:hidden">
        <a href="index.php" class="nav-link font-body text-xs">Startseite</a>
        <span class="font-body text-xs mx-2 text-text-muted">/</span>
        <span class="font-body text-xs font-semibold text-text-strong">Themen</span>
      </nav>

      <div class="mb-10 text-center">
        <p class="font-body text-sm uppercase tracking-widest mb-3 text-text-muted">Wissen & Orientierung</p>
        <h1 class="font-display text-4xl md:text-5xl font-bold mb-4 text-text-strong text-balance">
          Themen & Ratgeber
        </h1>
        <div class="w-12 h-0.5 bg-mint mx-auto mb-4"></div>
        <p class="font-body text-base text-text-body max-w-2xl mx-auto">
          Informationen, Erfahrungswerte und Orientierungshilfen für Aussteiger, Zweifelnde und Angehörige.
        </p>
      </div>

      <!-- Filter nach Kategorie & Tags -->
      <div class="space-y-3 mb-10 text-center">
        <?php if (!empty($kategorien)): ?>
        <div class="flex flex-wrap justify-center gap-2">
          <a href="themen" class="px-4 py-2 rounded-full font-body text-xs md:text-sm font-semibold transition-colors <?= ($kat_filter === '' && $tag_filter === '') ? 'bg-mint text-text-strong' : 'bg-cream text-text-body hover:bg-lightyellow border border-tan' ?>">
            Alle Themen
          </a>
          <?php foreach ($kategorien as $kat): ?>
            <a href="themen?kategorie=<?= urlencode($kat) ?>" class="px-4 py-2 rounded-full font-body text-xs md:text-sm font-semibold transition-colors <?= $kat_filter === $kat ? 'bg-mint text-text-strong' : 'bg-cream text-text-body hover:bg-lightyellow border border-tan' ?>">
              <?= htmlspecialchars($kat, ENT_QUOTES, 'UTF-8') ?>
            </a>
          <?php endforeach; ?>
        </div>
        <?php endif; ?>

        <?php if (!empty($all_tags)): ?>
        <div class="flex flex-wrap justify-center gap-1.5 pt-2">
          <span class="font-body text-xs text-text-muted py-1">Tags:</span>
          <?php foreach ($all_tags as $t_name): ?>
            <a href="themen?tag=<?= urlencode($t_name) ?>" class="px-2.5 py-1 rounded-md font-body text-xs transition-colors <?= $tag_filter === $t_name ? 'bg-tan text-text-strong font-semibold' : 'bg-lightyellow text-text-body hover:bg-tan/50' ?>">
              #<?= htmlspecialchars($t_name, ENT_QUOTES, 'UTF-8') ?>
            </a>
          <?php endforeach; ?>
        </div>
        <?php endif; ?>
      </div>

      <!-- Artikel Grid -->
      <?php if (empty($themen_db)): ?>
        <div class="text-center py-16 bg-cream rounded-2xl border border-mint">
          <p class="font-body text-base text-text-muted">
            Aktuell sind keine Themenartikel unter dieser Auswahl vorhanden.
          </p>
          <?php if ($kat_filter !== '' || $tag_filter !== ''): ?>
            <a href="themen" class="inline-block mt-4 font-body text-sm font-semibold text-mint-dark hover:underline">Alle Themen anzeigen</a>
          <?php endif; ?>
        </div>
      <?php else: ?>
        <div class="grid gap-6 md:grid-cols-3">
          <?php foreach ($themen_db as $art): ?>
            <article class="rounded-2xl p-6 flex flex-col justify-between card-hover bg-cream border border-mint min-h-[380px]">
              <div>
                <div class="flex flex-wrap items-center justify-between gap-y-2 gap-x-3 text-xs font-body text-text-muted mb-3">
                  <span class="px-2.5 py-1 rounded-md bg-lightyellow border border-tan text-text-body font-medium whitespace-normal">
                    <?= htmlspecialchars($art['kategorie'], ENT_QUOTES, 'UTF-8') ?>
                  </span>
                  <span class="whitespace-nowrap"><?= (int)$art['lesedauer_min'] ?> Min. Lesedauer</span>
                </div>
                <h2 class="font-display text-xl font-bold mb-3 text-text-strong hover:text-mint-dark">
                  <a href="themen/<?= htmlspecialchars($art['slug'], ENT_QUOTES, 'UTF-8') ?>">
                    <?= htmlspecialchars($art['titel'], ENT_QUOTES, 'UTF-8') ?>
                  </a>
                </h2>
                <p class="font-body text-sm text-text-body mb-4 line-clamp-6">
                  <?= htmlspecialchars($art['teaser'], ENT_QUOTES, 'UTF-8') ?>
                </p>

                <?php if (!empty($art['tags'])): ?>
                <div class="flex flex-wrap gap-1 mb-4">
                  <?php foreach (explode(',', $art['tags']) as $tg): $tg = trim($tg); if(!$tg) continue; ?>
                    <a href="themen?tag=<?= urlencode($tg) ?>" class="text-[11px] font-body px-2 py-0.5 rounded bg-cream border border-tan/60 text-text-muted hover:text-text-strong">
                      #<?= htmlspecialchars($tg, ENT_QUOTES, 'UTF-8') ?>
                    </a>
                  <?php endforeach; ?>
                </div>
                <?php endif; ?>
              </div>

              <div>
                <a href="themen/<?= htmlspecialchars($art['slug'], ENT_QUOTES, 'UTF-8') ?>" class="inline-flex items-center gap-1.5 font-body text-sm font-semibold text-mint-dark hover:underline">
                  Artikel lesen
                  <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="M14 5l7 7m0 0l-7 7m7-7H3"/></svg>
                </a>
              </div>
            </article>
          <?php endforeach; ?>
        </div>
      <?php endif; ?>

      <!-- Vertraulicher Kontakt CTA -->
      <section class="mt-16 text-center bg-cream rounded-2xl p-8 border border-mint">
        <h2 class="font-display text-2xl font-bold mb-3 text-text-strong">Persönlicher Austausch erwünscht?</h2>
        <p class="font-body text-base max-w-xl mx-auto mb-6 text-text-body">
          Unsere Selbsthilfegruppe bietet Raum für vertraulichen Austausch ohne Druck oder Verurteilung.
        </p>
        <a href="index.php#kontakt" class="btn-primary glowing-border font-body text-sm font-semibold px-6 py-3 rounded-full inline-block">Rückruf buchen</a>
      </section>

    </div>
  </main>

  <?php include __DIR__ . '/partials/footer.php'; ?>
  <script src="main.js" defer></script>
  <?php include __DIR__ . '/partials/sticky-phone-bar.php'; ?>

</body>
</html>
