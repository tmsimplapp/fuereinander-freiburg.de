<?php
header("Cache-Control: no-store, no-cache, must-revalidate, max-age=0");
header("Cache-Control: post-check=0, pre-check=0", false);
header("Pragma: no-cache");

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

$active_page = 'themen';
require_once __DIR__ . '/buchung-config.php';
require_once __DIR__ . '/themen-helpers.php';

$slug = isset($_GET['slug']) ? trim($_GET['slug']) : '';

if ($slug === '') {
    header('Location: /themen');
    exit;
}

$pdo = new PDO(
    'mysql:host=' . DB_HOST . ';dbname=' . DB_NAME . ';charset=utf8mb4',
    DB_USER, DB_PASS,
    [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION, PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC]
);

$is_admin = !empty($_SESSION['admin_logged_in']);

if ($is_admin) {
    $stmt = $pdo->prepare("SELECT * FROM artikel WHERE slug = ?");
} else {
    $stmt = $pdo->prepare("SELECT * FROM artikel WHERE slug = ? AND is_published = 1");
}
$stmt->execute([$slug]);
$artikel = $stmt->fetch();

if (!$artikel) {
    http_response_code(404);
    ?>
    <!DOCTYPE html>
    <html lang="de">
    <head>
      <meta charset="UTF-8" />
      <meta name="viewport" content="width=device-width, initial-scale=1.0" />
      <title>Artikel nicht gefunden – Füreinander Freiburg</title>
      <meta name="robots" content="noindex, follow" />
      <link rel="icon" href="/grafik/F%C3%BCreinander%20Freiburg.svg" type="image/svg+xml" />
      <meta name="theme-color" content="#a9e2cc" />
      <link rel="stylesheet" href="/tailwind.css?v=1" />
      <link rel="stylesheet" href="/styles.css?v=1" />
    </head>
    <body class="antialiased flex flex-col min-h-screen">
      <?php include __DIR__ . '/partials/nav.php'; ?>
      <main class="pt-8 md:pt-28 pb-20 flex-grow flex items-center justify-center text-center px-6">
        <div>
          <h1 class="font-display text-3xl font-bold mb-4 text-text-strong">Artikel nicht gefunden</h1>
          <p class="mb-6 text-text-body font-body">Der gewünschte Themenartikel existiert nicht oder wurde noch nicht veröffentlicht.</p>
          <a href="/themen" class="btn-primary px-6 py-2 rounded-full inline-block font-body text-sm font-semibold">Zurück zur Themenübersicht</a>
        </div>
      </main>
      <?php include __DIR__ . '/partials/footer.php'; ?>
    </body>
    </html>
    <?php
    exit;
}

// Meta-Angaben bestimmen
$page_title = !empty($artikel['meta_title']) ? $artikel['meta_title'] : $artikel['titel'] . ' – Füreinander Freiburg';
$meta_desc = !empty($artikel['meta_description']) ? $artikel['meta_description'] : $artikel['teaser'];
$article_url = 'https://fuereinander-freiburg.de/themen/' . rawurlencode($artikel['slug']);

// Verwandte / Verlinkte Artikel abfragen
$verwandte_artikel = [];
if (!empty($artikel['related_ids'])) {
    $rel_ids = array_filter(array_map('intval', explode(',', $artikel['related_ids'])));
    if (!empty($rel_ids)) {
        $in_clause = implode(',', array_fill(0, count($rel_ids), '?'));
        $rel_stmt = $pdo->prepare(
            "SELECT slug, titel, teaser, lesedauer_min, kategorie
             FROM artikel
             WHERE id IN ($in_clause) AND is_published = 1
             LIMIT 3"
        );
        $rel_stmt->execute(array_values($rel_ids));
        $verwandte_artikel = $rel_stmt->fetchAll();
    }
}

// Fallback: Wenn keine manuellen Links ausgewählt wurden, lade Artikel der gleichen Kategorie
if (empty($verwandte_artikel)) {
    $rel_stmt = $pdo->prepare(
        "SELECT slug, titel, teaser, lesedauer_min, kategorie
         FROM artikel
         WHERE is_published = 1 AND kategorie = ? AND id != ?
         ORDER BY created_at DESC LIMIT 3"
    );
    $rel_stmt->execute([$artikel['kategorie'], $artikel['id']]);
    $verwandte_artikel = $rel_stmt->fetchAll();
}
?>
<!DOCTYPE html>
<html lang="de">
<head>
  <base href="/" />
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <title><?= htmlspecialchars($page_title, ENT_QUOTES, 'UTF-8') ?></title>
  <meta name="description" content="<?= htmlspecialchars($meta_desc, ENT_QUOTES, 'UTF-8') ?>" />
  <meta name="robots" content="index, follow" />
  <link rel="canonical" href="<?= $article_url ?>" />

  <!-- Open Graph Meta Tags -->
  <meta property="og:title" content="<?= htmlspecialchars($artikel['titel'], ENT_QUOTES, 'UTF-8') ?>" />
  <meta property="og:description" content="<?= htmlspecialchars($artikel['teaser'], ENT_QUOTES, 'UTF-8') ?>" />
  <meta property="og:type" content="article" />
  <meta property="og:url" content="<?= $article_url ?>" />
  <meta property="og:image" content="https://fuereinander-freiburg.de/grafik/og-image.png" />
  <meta property="og:site_name" content="Füreinander Freiburg" />
  <meta property="article:published_time" content="<?= date('c', strtotime($artikel['created_at'])) ?>" />

  <link rel="icon" href="/grafik/F%C3%BCreinander%20Freiburg.svg" type="image/svg+xml" />
  <link rel="manifest" href="/site.webmanifest" />
  <meta name="theme-color" content="#a9e2cc" />

  <link rel="stylesheet" href="/tailwind.css?v=2" />
  <link rel="preconnect" href="https://fonts.googleapis.com" />
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin />
  <link href="https://fonts.googleapis.com/css2?family=Lato:ital,wght@0,300;0,400;0,700;1,300;1,400;1,700&display=swap" rel="stylesheet" />
  <link rel="stylesheet" href="/styles.css?v=2" />
  <link rel="stylesheet" href="/transitions.css?v=2" />

  <!-- Schema.org BlogPosting -->
  <script type="application/ld+json">
  {
    "@context": "https://schema.org",
    "@type": "BlogPosting",
    "headline": <?= json_encode($artikel['titel']) ?>,
    "description": <?= json_encode($artikel['teaser']) ?>,
    "datePublished": <?= json_encode(date('c', strtotime($artikel['created_at']))) ?>,
    "dateModified": <?= json_encode(date('c', strtotime($artikel['updated_at']))) ?>,
    "mainEntityOfPage": {
      "@type": "WebPage",
      "@id": <?= json_encode($article_url) ?>
    },
    "publisher": {
      "@type": "Organization",
      "name": "Füreinander Freiburg",
      "url": "https://fuereinander-freiburg.de/"
    }
  }
  </script>
</head>

<body class="antialiased flex flex-col min-h-screen">

  <?php include __DIR__ . '/partials/nav.php'; ?>

  <main class="pt-8 md:pt-24 pb-20 flex-grow">
    <article class="max-w-3xl mx-auto px-6">

      <!-- Breadcrumbs -->
      <nav aria-label="Breadcrumb" class="mb-8 font-body text-xs text-text-muted">
        <a href="/index.php" class="hover:underline">Startseite</a>
        <span class="mx-2">/</span>
        <a href="/themen" class="hover:underline">Themen</a>
        <span class="mx-2">/</span>
        <span class="font-semibold text-text-strong"><?= htmlspecialchars($artikel['kategorie'], ENT_QUOTES, 'UTF-8') ?></span>
      </nav>

      <?php if (!$artikel['is_published']): ?>
        <div class="mb-6 p-4 rounded-xl bg-lightyellow border border-tan text-text-body font-body text-sm">
          <strong>Hinweis:</strong> Dieser Artikel ist derzeit ein <em>Entwurf</em> und nur für dich als Admin sichtbar.
        </div>
      <?php endif; ?>

      <!-- Header -->
      <header class="mb-10">
        <div class="flex flex-wrap items-center gap-3 mb-4">
          <span class="px-3 py-1 rounded-full bg-mint text-text-strong font-body text-xs font-semibold">
            <?= htmlspecialchars($artikel['kategorie'], ENT_QUOTES, 'UTF-8') ?>
          </span>
          <span class="font-body text-xs text-text-muted">
            <?= (int)$artikel['lesedauer_min'] ?> Min. Lesedauer
          </span>
        </div>
        <h1 class="font-display text-3xl md:text-5xl font-bold mb-6 text-text-strong leading-tight text-balance">
          <?= htmlspecialchars($artikel['titel'], ENT_QUOTES, 'UTF-8') ?>
        </h1>
        <p class="font-body text-lg md:text-xl text-text-body leading-relaxed border-l-4 border-mint pl-4 py-1 bg-cream rounded-r-lg italic break-words">
          <?= htmlspecialchars($artikel['teaser'], ENT_QUOTES, 'UTF-8') ?>
        </p>

        <?php if (!empty($artikel['tags'])): ?>
        <div class="flex flex-wrap gap-1.5 mt-4">
          <?php foreach (explode(',', $artikel['tags']) as $tg): $tg = trim($tg); if(!$tg) continue; ?>
            <a href="/themen?tag=<?= urlencode($tg) ?>" class="text-xs font-body px-2.5 py-1 rounded-md bg-lightyellow border border-tan text-text-body hover:bg-tan/40">
              #<?= htmlspecialchars($tg, ENT_QUOTES, 'UTF-8') ?>
            </a>
          <?php endforeach; ?>
        </div>
        <?php endif; ?>
      </header>

      <hr class="border-tan/40 my-8" />

      <!-- Hauptinhalt (Parsed Markdown) -->
      <div class="prose max-w-none font-body text-text-body leading-relaxed space-y-4 break-words [overflow-wrap:anywhere]">
        <?= parse_markdown($artikel['inhalt']) ?>
      </div>

      <hr class="border-tan/40 my-12" />

      <!-- Rückruf / Erstgespräch Box CTA -->
      <section class="rounded-2xl p-8 bg-cream border border-mint text-center my-10">
        <h2 class="font-display text-2xl font-bold mb-3 text-text-strong">Sprechen hilft weiter</h2>
        <p class="font-body text-base text-text-body max-w-xl mx-auto mb-6">
          Du steckst in einer ähnlichen Situation oder hast Fragen? In unserer Selbsthilfegruppe tauschen wir uns vertraulich und auf Augenhöhe aus.
        </p>
        <a href="/index.php#kontakt" class="btn-primary glowing-border font-body text-sm font-semibold px-6 py-3 rounded-full inline-block">
          Vertraulichen Rückruf buchen
        </a>
      </section>

      <?php if (!empty($verwandte_artikel)): ?>
      <!-- Verwandte / Passend verlinkte Artikel -->
      <section class="mt-12">
        <h2 class="font-display text-xl font-bold mb-6 text-text-strong">Passende weiterführende Themen</h2>
        <div class="grid gap-6 md:grid-cols-3">
          <?php foreach ($verwandte_artikel as $rel): ?>
            <div class="rounded-xl p-5 bg-cream border border-mint flex flex-col justify-between">
              <div>
                <span class="text-[11px] font-body text-text-muted block mb-1">
                  <?= htmlspecialchars($rel['kategorie'], ENT_QUOTES, 'UTF-8') ?>
                </span>
                <h3 class="font-display text-base font-bold mb-2 text-text-strong line-clamp-2">
                  <a href="/themen/<?= htmlspecialchars($rel['slug'], ENT_QUOTES, 'UTF-8') ?>" class="hover:text-mint-dark">
                    <?= htmlspecialchars($rel['titel'], ENT_QUOTES, 'UTF-8') ?>
                  </a>
                </h3>
                <p class="font-body text-xs text-text-body line-clamp-2 mb-4">
                  <?= htmlspecialchars($rel['teaser'], ENT_QUOTES, 'UTF-8') ?>
                </p>
              </div>
              <a href="/themen/<?= htmlspecialchars($rel['slug'], ENT_QUOTES, 'UTF-8') ?>" class="font-body text-xs font-semibold text-mint-dark hover:underline inline-flex items-center gap-1">
                Lesen &rarr;
              </a>
            </div>
          <?php endforeach; ?>
        </div>
      </section>
      <?php endif; ?>

    </article>
  </main>

  <?php include __DIR__ . '/partials/footer.php'; ?>
  <script src="/main.js" defer></script>
  <?php include __DIR__ . '/partials/sticky-phone-bar.php'; ?>

</body>
</html>
