<?php
$page = basename($_SERVER['PHP_SELF'], '.php');
?>
<nav>
  <a href="scanner.php" class="nav-logo">Collector<em>Vault</em></a>
  <div class="nav-divider"></div>
  <div class="nav-links">
    <a href="scanner.php" class="nav-link <?= $page === 'scanner' ? 'active' : '' ?>">
      <svg viewBox="0 0 24 24"><path d="M3 7V5a2 2 0 012-2h2M17 3h2a2 2 0 012 2v2M21 17v2a2 2 0 01-2 2h-2M7 21H5a2 2 0 01-2-2v-2"/><rect x="9" y="9" width="6" height="6" rx="1"/></svg>
      Scan
    </a>
    <a href="collection.php" class="nav-link <?= $page === 'collection' ? 'active' : '' ?>">
      <svg viewBox="0 0 24 24"><rect x="3" y="3" width="7" height="7" rx="1"/><rect x="14" y="3" width="7" height="7" rx="1"/><rect x="3" y="14" width="7" height="7" rx="1"/><rect x="14" y="14" width="7" height="7" rx="1"/></svg>
      Collection
    </a>
  </div>
  <div class="nav-right">
    <span class="nav-user"><?= htmlspecialchars($username) ?></span>
    <button class="theme-toggle" id="themeToggle" onclick="toggleTheme()" aria-label="Toggle theme">
      <span id="themeIconWrap"></span>
    </button>
    <a href="logout.php" class="sign-out-btn">
      <svg viewBox="0 0 24 24"><path d="M9 21H5a2 2 0 01-2-2V5a2 2 0 012-2h4"/><polyline points="16 17 21 12 16 7"/><line x1="21" y1="12" x2="9" y2="12"/></svg>
      <span class="sign-out-label">Sign out</span>
    </a>
  </div>
</nav>

<!-- Bottom nav: mobile only, hidden ≥640px via shared.css -->
<nav class="bottom-nav">
  <a href="scanner.php" class="bn-item <?= $page === 'scanner' ? 'active' : '' ?>">
    <svg viewBox="0 0 24 24"><path d="M3 7V5a2 2 0 012-2h2M17 3h2a2 2 0 012 2v2M21 17v2a2 2 0 01-2 2h-2M7 21H5a2 2 0 01-2-2v-2"/><rect x="9" y="9" width="6" height="6" rx="1"/></svg>
    Scan
  </a>
  <a href="collection.php" class="bn-item <?= $page === 'collection' ? 'active' : '' ?>">
    <svg viewBox="0 0 24 24"><rect x="3" y="3" width="7" height="7" rx="1"/><rect x="14" y="3" width="7" height="7" rx="1"/><rect x="3" y="14" width="7" height="7" rx="1"/><rect x="14" y="14" width="7" height="7" rx="1"/></svg>
    Collection
  </a>
</nav>
