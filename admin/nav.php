<?php
// Sidebar-Navigation – wird von jeder Admin-Hauptseite eingebunden.
// Erwartet optional $active_nav (z. B. 'dashboard', 'termine', 'community', 'profil').
$active_nav = $active_nav ?? '';
$verwaltung_sub_active = in_array($active_nav, ['community-tags', 'community-regionen'], true);

$stmt = admin_db()->query("SELECT wert FROM statistiken WHERE name = 'seitenaufrufe'");
$seitenaufrufe = (int) $stmt->fetchColumn();
?>
<div class="sidebar">
  <div class="sidebar-brand">
    <img src="../grafik/F%C3%BCreinander%20Freiburg.svg" alt="Logo">
    <span>Admin</span>
  </div>
  <nav class="sidebar-nav">
    <a href="index.php" class="sidebar-link <?= $active_nav === 'dashboard' ? 'active' : '' ?>" <?= $active_nav === 'dashboard' ? 'aria-current="page"' : '' ?>>
      <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><rect x="3" y="3" width="7" height="9" rx="1"/><rect x="14" y="3" width="7" height="5" rx="1"/><rect x="14" y="12" width="7" height="9" rx="1"/><rect x="3" y="16" width="7" height="5" rx="1"/></svg>
      Dashboard
    </a>
    <a href="termine.php" class="sidebar-link <?= $active_nav === 'termine' ? 'active' : '' ?>" <?= $active_nav === 'termine' ? 'aria-current="page"' : '' ?>>
      <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><rect width="18" height="18" x="3" y="4" rx="2"/><path d="M3 10h18M8 2v4M16 2v4"/></svg>
      Termine
    </a>
    <a href="community.php" class="sidebar-link <?= $active_nav === 'community' ? 'active' : '' ?>" <?= $active_nav === 'community' ? 'aria-current="page"' : '' ?>>
      <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M16 21v-2a4 4 0 0 0-4-4H6a4 4 0 0 0-4 4v2"/><circle cx="9" cy="7" r="4"/><path d="M22 21v-2a4 4 0 0 0-3-3.87"/><path d="M16 3.13a4 4 0 0 1 0 7.75"/></svg>
      Community
    </a>

    <details class="sidebar-submenu" <?= $verwaltung_sub_active ? 'open' : '' ?>>
      <summary>
        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M14.7 6.3a1 1 0 0 0 0 1.4l1.6 1.6a1 1 0 0 0 1.4 0l3.77-3.77a6 6 0 0 1-7.94 7.94l-6.91 6.91a2.12 2.12 0 0 1-3-3l6.91-6.91a6 6 0 0 1 7.94-7.94l-3.76 3.76z"/></svg>
        <span>Verwaltung</span>
        <svg class="sidebar-submenu-chevron" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="m6 9 6 6 6-6"/></svg>
      </summary>
      <a href="community-tags.php" class="sidebar-link sidebar-sublink <?= $active_nav === 'community-tags' ? 'active' : '' ?>" <?= $active_nav === 'community-tags' ? 'aria-current="page"' : '' ?>>Tags</a>
      <a href="community-regionen.php" class="sidebar-link sidebar-sublink <?= $active_nav === 'community-regionen' ? 'active' : '' ?>" <?= $active_nav === 'community-regionen' ? 'aria-current="page"' : '' ?>>Regionen</a>
    </details>

    <hr class="sidebar-divider">

    <a href="profil.php" class="sidebar-link <?= $active_nav === 'profil' ? 'active' : '' ?>" <?= $active_nav === 'profil' ? 'aria-current="page"' : '' ?>>
      <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M19 21v-2a4 4 0 0 0-4-4H9a4 4 0 0 0-4 4v2"/><circle cx="12" cy="7" r="4"/></svg>
      Profil
    </a>
  </nav>
  <div class="sidebar-stats">
    <svg width="15" height="15" fill="none" stroke="currentColor" stroke-width="1.8" viewBox="0 0 24 24" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/><path stroke-linecap="round" stroke-linejoin="round" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/></svg>
    Seitenaufrufe: <strong><?= number_format($seitenaufrufe, 0, ',', '.') ?></strong>
  </div>
  <form method="post" action="logout.php" class="sidebar-logout">
    <button type="submit" class="btn-logout">
      <svg width="15" height="15" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" viewBox="0 0 24 24" aria-hidden="true"><path d="M9 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h4"/><polyline points="16 17 21 12 16 7"/><line x1="21" y1="12" x2="9" y2="12"/></svg>
      Abmelden
    </button>
  </form>
</div>
