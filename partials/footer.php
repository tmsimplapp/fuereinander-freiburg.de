<?php
$active_page = $active_page ?? '';

function footer_link_classes(string $page, string $active): string {
    if ($page === $active) {
        return 'font-body text-xs font-semibold text-text-strong';
    }
    return 'footer-link font-body text-xs transition-colors text-text-footer';
}
?>
<footer class="footer-glass">
  <div class="w-full py-4 text-center">
    <nav aria-label="Footer-Navigation" class="mb-3">
      <ul class="flex flex-wrap gap-4 justify-center">
        <li>
          <a href="rechtliches.php" class="<?= footer_link_classes('rechtliches', $active_page) ?>" style="min-height: 32px; display: inline-flex; align-items: center">Rechtliches</a>
        </li>
        <li><span class="font-body text-xs text-text-footer">·</span></li>
        <li>
          <a href="ausstieg-folgen.php" class="<?= footer_link_classes('ausstieg-folgen', $active_page) ?>" style="min-height: 32px; display: inline-flex; align-items: center">Ausstiegsfolgen</a>
        </li>
        <li><span class="font-body text-xs text-text-footer">·</span></li>
        <li>
          <a href="angehoerige.php" class="<?= footer_link_classes('angehoerige', $active_page) ?>" style="min-height: 32px; display: inline-flex; align-items: center">Angehörige</a>
        </li>
        <li><span class="font-body text-xs text-text-footer">·</span></li>
        <li>
          <a href="termine.php" class="<?= footer_link_classes('termine', $active_page) ?>" style="min-height: 32px; display: inline-flex; align-items: center">Termine</a>
        </li>
        <li><span class="font-body text-xs text-text-footer">·</span></li>
        <li>
          <a href="partner.php" class="<?= footer_link_classes('partner', $active_page) ?>" style="min-height: 32px; display: inline-flex; align-items: center">Partner</a>
        </li>
      </ul>
    </nav>
    <p class="font-body text-xs text-text-footer">
      &copy; <span id="year"></span> Füreinander Freiburg · Selbsthilfegruppe für zweifelnde und ausgestiegene Zeugen Jehovas
    </p>
    <p class="font-body text-xs mt-1" style="color:#7a6550;">
      <time datetime="2026-06-20">Zuletzt aktualisiert: Juni 2026</time>
    </p>
  </div>
</footer>
