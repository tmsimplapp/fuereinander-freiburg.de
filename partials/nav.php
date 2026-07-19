<?php
/**
 * Nav-Partial. Erwartet optional $active_page (z.B. 'index', 'angehoerige', ...)
 * gesetzt von der einbindenden Seite, um den aktiven Link hervorzuheben.
 */
$active_page = $active_page ?? '';

function nav_link_classes(string $page, string $active): string {
    $base = 'nav-link font-body text-sm';
    return $page === $active ? $base . ' font-semibold text-text-strong' : $base;
}

function mobile_link_classes(string $page, string $active): string {
    if ($page === $active) {
        return 'mobile-nav-link font-body text-base font-semibold py-3 px-6 rounded-full transition-all text-center bg-mint border border-mint';
    }
    return 'mobile-nav-link font-body text-base font-medium py-3 px-6 rounded-full transition-all text-center text-text-body bg-lightyellow border border-tan';
}
?>
<div aria-live="polite" class="sr-only" id="menu-status"></div>

<!-- NAVIGATION -->
<nav class="fixed bottom-0 md:bottom-auto md:top-0 left-0 right-0 z-50 bg-cream/80 backdrop-blur-md border-t md:border-t-0 md:border-b border-tan/20" style="padding-bottom: env(safe-area-inset-bottom, 0px);">
  <div class="max-w-5xl mx-auto px-6 py-4 flex items-center justify-center md:justify-between">
    <a href="index.php" aria-label="Füreinander Freiburg – Startseite" class="flex items-center">
      <img src="grafik/F%C3%BCreinander%20Freiburg.svg" alt="Logo der Selbsthilfegruppe Füreinander Freiburg" class="h-8 w-auto opacity-90" width="120" height="32">
    </a>

    <!-- Desktop-Navigation -->
    <div class="hidden md:flex items-center gap-6">
      <a href="index.php" class="<?= nav_link_classes('index', $active_page) ?>">Startseite</a>
      <a href="ausstieg-folgen.php" class="<?= nav_link_classes('ausstieg-folgen', $active_page) ?>">Ausstiegsfolgen</a>
      <a href="angehoerige.php" class="<?= nav_link_classes('angehoerige', $active_page) ?>">Angehörige</a>
      <a href="partner.php" class="<?= nav_link_classes('partner', $active_page) ?>">Partner</a>
      <a href="termine.php" class="<?= nav_link_classes('termine', $active_page) ?>">Termine</a>
      <a href="index.php#kontakt" class="btn-primary glowing-border font-body text-sm font-semibold px-5 py-2.5 rounded-full active:scale-95">Kontakt</a>
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

<!-- Mobile Menü (Bottom Sheet) -->
<div id="mobile-menu" class="mobile-menu-overlay md:hidden hidden">
  <div class="mobile-menu-sheet">
    <div class="flex flex-col gap-2 items-stretch">
      <a href="index.php" <?= $active_page === 'index' ? 'aria-current="page"' : '' ?> class="<?= mobile_link_classes('index', $active_page) ?>" style="min-height: 44px; display: flex; align-items: center; justify-content: center<?= $active_page === 'index' ? '; color:#1a2820' : '' ?>">Startseite</a>
      <a href="ausstieg-folgen.php" <?= $active_page === 'ausstieg-folgen' ? 'aria-current="page"' : '' ?> class="<?= mobile_link_classes('ausstieg-folgen', $active_page) ?>" style="min-height: 44px; display: flex; align-items: center; justify-content: center<?= $active_page === 'ausstieg-folgen' ? '; color:#1a2820' : '' ?>">Ausstiegsfolgen</a>
      <a href="angehoerige.php" <?= $active_page === 'angehoerige' ? 'aria-current="page"' : '' ?> class="<?= mobile_link_classes('angehoerige', $active_page) ?>" style="min-height: 44px; display: flex; align-items: center; justify-content: center<?= $active_page === 'angehoerige' ? '; color:#1a2820' : '' ?>">Angehörige</a>
      <a href="partner.php" <?= $active_page === 'partner' ? 'aria-current="page"' : '' ?> class="<?= mobile_link_classes('partner', $active_page) ?>" style="min-height: 44px; display: flex; align-items: center; justify-content: center<?= $active_page === 'partner' ? '; color:#1a2820' : '' ?>">Partner</a>
      <a href="termine.php" <?= $active_page === 'termine' ? 'aria-current="page"' : '' ?> class="<?= mobile_link_classes('termine', $active_page) ?>" style="min-height: 44px; display: flex; align-items: center; justify-content: center<?= $active_page === 'termine' ? '; color:#1a2820' : '' ?>">Termine</a>
      <a href="rechtliches.php" <?= $active_page === 'rechtliches' ? 'aria-current="page"' : '' ?> class="<?= mobile_link_classes('rechtliches', $active_page) ?>" style="min-height: 44px; display: flex; align-items: center; justify-content: center<?= $active_page === 'rechtliches' ? '; color:#1a2820' : '' ?>">Rechtliches</a>
      <a href="index.php#kontaktformular" class="font-body text-base font-bold py-3 px-6 rounded-full transition-all text-center mt-2 shadow-sm bg-mint border border-mint-dark" style="min-height: 44px; display: flex; align-items: center; justify-content: center; color:#1a2820">Kontaktformular</a>

      <!-- Separator -->
      <div class="my-2 bg-tan" style="height:1px"></div>

      <!-- Telefon-Button (Primär) -->
      <a href="tel:+4915567465016"
         class="font-body text-base font-semibold py-3 px-6 rounded-full transition-all text-center flex items-center justify-center gap-3 bg-mint border border-mint-dark" style="min-height: 48px;  color:#1a2820"
         aria-label="Anrufen: 01556 7465016">
        <svg class="w-5 h-5" fill="none" stroke="currentColor" stroke-width="1.8" viewBox="0 0 24 24" aria-hidden="true">
          <path stroke-linecap="round" stroke-linejoin="round"
                d="M3 5a2 2 0 012-2h3.28a1 1 0 01.948.684l1.498 4.493a1 1 0 01-.502 1.21l-2.257 1.13a11.042 11.042 0 005.516 5.516l1.13-2.257a1 1 0 011.21-.502l4.493 1.498a1 1 0 01.684.949V19a2 2 0 01-2 2h-1C9.716 21 3 14.284 3 6V5z"/>
        </svg>
        <span>01556 / 7465016</span>
      </a>

      <!-- Kontakt-Grid: 2×2 mit Icon + Label -->
      <div class="grid grid-cols-2 gap-3 mt-3">

        <!-- WhatsApp -->
        <a href="https://wa.me/4915567465016"
           target="_blank" rel="noopener noreferrer"
           class="flex flex-col items-center justify-center gap-2 py-4 px-3 rounded-2xl transition-all bg-lightyellow border border-tan" style="min-height:88px">
          <svg class="w-8 h-8" fill="#3d3225" viewBox="0 0 24 24" aria-hidden="true">
            <path d="M17.472 14.382c-.297-.149-1.758-.867-2.03-.967-.273-.099-.471-.148-.67.15-.197.297-.767.966-.94 1.164-.173.199-.347.223-.644.075-.297-.15-1.255-.463-2.39-1.475-.883-.788-1.48-1.761-1.653-2.059-.173-.297-.018-.458.13-.606.134-.133.298-.347.446-.52.149-.174.198-.298.298-.497.099-.198.05-.371-.025-.52-.075-.149-.669-1.612-.916-2.207-.242-.579-.487-.5-.669-.51-.173-.008-.371-.01-.57-.01-.198 0-.52.074-.792.372-.272.297-1.04 1.016-1.04 2.479 0 1.462 1.065 2.875 1.213 3.074.149.198 2.096 3.2 5.077 4.487.709.306 1.262.489 1.694.625.712.227 1.36.195 1.871.118.571-.085 1.758-.719 2.006-1.413.248-.694.248-1.289.173-1.413-.074-.124-.272-.198-.57-.347m-5.421 7.403h-.004a9.87 9.87 0 01-5.031-1.378l-.361-.214-3.741.982.998-3.648-.235-.374a9.86 9.86 0 01-1.51-5.26c.001-5.45 4.436-9.884 9.888-9.884 2.64 0 5.122 1.03 6.988 2.898a9.825 9.825 0 012.893 6.994c-.003 5.45-4.437 9.884-9.885 9.884m8.413-18.297A11.815 11.815 0 0012.05 0C5.495 0 .16 5.335.157 11.892c0 2.096.547 4.142 1.588 5.945L.057 24l6.305-1.654a11.882 11.882 0 005.683 1.448h.005c6.554 0 11.89-5.335 11.893-11.893a11.821 11.821 0 00-3.48-8.413z"/>
          </svg>
          <span class="font-body text-sm font-medium text-center text-text-strong">WhatsApp</span>
        </a>

        <!-- Anrufen (alternative) -->
        <a href="tel:+4915567465016"
           class="flex flex-col items-center justify-center gap-2 py-4 px-3 rounded-2xl transition-all bg-tan border border-tan" style="min-height:88px"
           aria-label="Anrufen: 01556 7465016">
          <svg class="w-8 h-8" fill="none" stroke="#3d3225" stroke-width="1.8" viewBox="0 0 24 24" aria-hidden="true">
            <path stroke-linecap="round" stroke-linejoin="round" d="M3 5a2 2 0 012-2h3.28a1 1 0 01.948.684l1.498 4.493a1 1 0 01-.502 1.21l-2.257 1.13a11.042 11.042 0 005.516 5.516l1.13-2.257a1 1 0 011.21-.502l4.493 1.498a1 1 0 01.684.949V19a2 2 0 01-2 2h-1C9.716 21 3 14.284 3 6V5z"/>
          </svg>
          <span class="font-body text-sm font-medium text-center text-text-strong">Anrufen</span>
        </a>

        <!-- Telegram -->
        <a href="https://t.me/+4915567465016"
           target="_blank" rel="noopener noreferrer"
           class="flex flex-col items-center justify-center gap-2 py-4 px-3 rounded-2xl transition-all bg-lightyellow border border-tan" style="min-height:88px">
          <svg class="w-8 h-8" fill="#3d3225" viewBox="0 0 24 24" aria-hidden="true">
            <path d="M11.944 0A12 12 0 0 0 0 12a12 12 0 0 0 12 12 12 12 0 0 0 12-12A12 12 0 0 0 12 0a12 12 0 0 0-.056 0zm4.962 7.224c.1-.002.321.023.465.14a.506.506 0 0 1 .171.325c.016.093.036.306.02.472-.18 1.898-.962 6.502-1.36 8.627-.168.9-.499 1.201-.82 1.23-.696.065-1.225-.46-1.9-.902-1.056-.693-1.653-1.124-2.678-1.8-1.185-.78-.417-1.21.258-1.91.177-.184 3.247-2.977 3.307-3.23.007-.032.014-.15-.056-.212s-.174-.041-.249-.024c-.106.024-1.793 1.14-5.061 3.345-.48.33-.913.49-1.302.48-.428-.008-1.252-.241-1.865-.44-.752-.245-1.349-.374-1.297-.789.027-.216.325-.437.893-.663 3.498-1.524 5.83-2.529 6.998-3.014 3.332-1.386 4.025-1.627 4.476-1.635z"/>
          </svg>
          <span class="font-body text-sm font-medium text-center text-text-strong">Telegram</span>
        </a>

        <!-- E-Mail -->
        <a href="mailto:kontakt@fuereinander-freiburg.de"
           class="flex flex-col items-center justify-center gap-2 py-4 px-3 rounded-2xl transition-all bg-tan border border-tan" style="min-height:88px">
          <svg class="w-8 h-8" fill="none" stroke="#3d3225" stroke-width="1.8" viewBox="0 0 24 24" aria-hidden="true">
            <path stroke-linecap="round" stroke-linejoin="round" d="M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"/>
          </svg>
          <span class="font-body text-sm font-medium text-center text-text-strong">E-Mail</span>
        </a>

      </div>

      <!-- Datenschutz-Hinweis (Mobile) -->
      <p class="font-body text-sm text-center mt-4 flex items-center justify-center gap-2 text-text-muted">
        <svg class="w-4 h-4 flex-shrink-0" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24" aria-hidden="true">
          <path stroke-linecap="round" stroke-linejoin="round" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
        </svg>
        <em>Diese Links öffnen externe Apps</em>
      </p>

      <?php if (!empty($show_all_contacts_button)): ?>
      <!-- "Alle Kontaktmöglichkeiten"-Button -->
      <a href="index.php#kontakt" class="btn-primary glowing-border font-body text-base font-semibold px-6 py-3 rounded-full text-center mt-1 active:scale-95" style="min-height: 48px; display: flex; align-items: center; justify-content: center;">Alle Kontaktmöglichkeiten</a>
      <?php endif; ?>
    </div>
  </div>
</div>
