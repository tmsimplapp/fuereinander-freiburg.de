<?php
// Sidebar-Navigation – wird von jeder Admin-Hauptseite eingebunden.
// Erwartet optional $active_nav (z. B. 'termine', 'community', 'profil').
$active_nav = $active_nav ?? '';
$community_sub_active = in_array($active_nav, ['community-tags', 'community-regionen'], true);
?>
<div class="sidebar">
  <div class="sidebar-brand">
    <img src="../grafik/F%C3%BCreinander%20Freiburg.svg" alt="Logo">
    <span>Admin</span>
  </div>
  <nav class="sidebar-nav">
    <a href="termine.php" class="sidebar-link <?= $active_nav === 'termine' ? 'active' : '' ?>" <?= $active_nav === 'termine' ? 'aria-current="page"' : '' ?>>Termine</a>

    <a href="community.php" class="sidebar-link <?= $active_nav === 'community' ? 'active' : '' ?>" <?= $active_nav === 'community' ? 'aria-current="page"' : '' ?>>Community</a>
    <details class="sidebar-submenu" <?= $community_sub_active ? 'open' : '' ?>>
      <summary>Community verwalten</summary>
      <a href="community-tags.php" class="sidebar-link sidebar-sublink <?= $active_nav === 'community-tags' ? 'active' : '' ?>" <?= $active_nav === 'community-tags' ? 'aria-current="page"' : '' ?>>Tags</a>
      <a href="community-regionen.php" class="sidebar-link sidebar-sublink <?= $active_nav === 'community-regionen' ? 'active' : '' ?>" <?= $active_nav === 'community-regionen' ? 'aria-current="page"' : '' ?>>Regionen</a>
    </details>

    <a href="profil.php" class="sidebar-link <?= $active_nav === 'profil' ? 'active' : '' ?>" <?= $active_nav === 'profil' ? 'aria-current="page"' : '' ?>>Profil</a>
  </nav>
  <form method="post" action="logout.php" class="sidebar-logout">
    <button type="submit" class="btn-logout">Abmelden</button>
  </form>
</div>
