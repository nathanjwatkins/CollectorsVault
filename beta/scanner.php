<?php
ob_start();
header('Cache-Control: no-cache, must-revalidate, max-age=0');
header('Pragma: no-cache');
session_name('CVBETA');
ini_set('session.cookie_path', '/beta/');
ini_set('session.cookie_samesite', 'Lax');
ini_set('session.cookie_secure', '1');
ini_set('session.cookie_httponly', '1');
ini_set('session.cookie_httponly',1); ini_set('session.cookie_secure',1); ini_set('session.cookie_samesite','Lax');
session_start();
if (!isset($_SESSION['user'])) { header('Location: /beta/index.php'); exit; }
$username = htmlspecialchars($_SESSION['user']);
?>
<!DOCTYPE html>
<html lang="en" data-theme="dark">
<head>
<meta charset="UTF-8"/>
<meta name="theme-color" content="#050507">
<meta name="viewport" content="width=device-width, initial-scale=1.0, viewport-fit=cover"/>
<title>CollectorVault — Scanner</title>
<link rel="preconnect" href="https://fonts.googleapis.com">
<link href="https://fonts.googleapis.com/css2?family=Outfit:wght@200;300;400;500;600;700;800;900&family=JetBrains+Mono:wght@300;400;500;700&display=swap" rel="stylesheet">
<?php include 'theme.php'; ?>
<link rel="stylesheet" href="shared.css?v=cv3_fix001">
<style>@media(max-width:899px){.cv-mobile-wordmark{display:block!important}}</style>
</head>
<body>
<style>
.dropzone-area input[type="file"] { display: none; }

.dropzone-icon {
  width: 36px;
  height: 36px;
  margin: 0 auto 12px;
  border: 1px solid rgba(200,255,0,.25);
  border-radius: var(--radius-md);
  display: flex;
  align-items: center;
  justify-content: center;
  color: var(--acid);
}

.dropzone-icon svg { width: 18px; height: 18px; stroke: currentColor; fill: none; stroke-width: 1.5; }

.dropzone-title {
  font-family: var(--font);
  font-size: 13px;
  font-weight: 600;
  color: var(--ink);
  margin-bottom: 4px;
}

.dropzone-sub {
  font-family: var(--mono);
  font-size: 9px;
  letter-spacing: .06em;
  color: var(--ink3);
  text-transform: uppercase;
}

/* Preview image */
#previewWrap {
  display: none;
  border-radius: var(--radius-md);
  overflow: hidden;
  border: 1px solid var(--border);
  position: relative;
}

#previewWrap img {
  width: 100%;
  height: 200px;
  object-fit: cover;
  display: block;
}

/* Scanning state */
#scanningState {
  display: none;
  align-items: center;
  gap: 10px;
  padding: 12px;
  background: var(--surface2);
  border: 1px solid var(--border);
  border-radius: var(--radius-md);
  font-family: var(--mono);
  font-size: 10px;
  color: var(--acid);
  letter-spacing: .06em;
}

.scan-spinner {
  width: 16px; height: 16px;
  border: 2px solid rgba(200,255,0,.20);
  border-top-color: var(--acid);
  border-radius: 50%;
  animation: spin .8s linear infinite;
  flex-shrink: 0;
}

@keyframes spin { to { transform: rotate(360deg); } }

/* ID block */
#idBlock {
  display: none;
  background: rgba(200,255,0,.03);
  border: 1px solid rgba(200,255,0,.15);
  border-radius: var(--radius-md);
  padding: 12px 14px;
}

.id-name {
  font-family: var(--font);
  font-size: 15px;
  font-weight: 700;
  color: var(--ink);
  letter-spacing: -.02em;
}

.id-meta {
  font-family: var(--mono);
  font-size: 9px;
  color: var(--ink3);
  margin-top: 4px;
  letter-spacing: .04em;
}

/* Error */
#errorBox {
  display: none;
  padding: 10px 12px;
  background: rgba(255,68,68,.08);
  border: 1px solid rgba(255,68,68,.20);
  border-radius: var(--radius-md);
  font-family: var(--mono);
  font-size: 10px;
  color: var(--red);
  letter-spacing: .04em;
}

/* Form fields */
.form-fields {
  display: none;
  flex-direction: column;
  gap: 10px;
}

.form-field input,
.form-field select {
  width: 100%;
  height: 36px;
  padding: 0 12px;
  background: var(--surface2);
  border: 1px solid var(--border);
  border-radius: var(--radius-md);
  font-family: var(--font);
  font-size: 13px;
  color: var(--ink);
  outline: none;
  transition: border-color .15s;
  -webkit-appearance: none;
}

.form-field input::placeholder { color: var(--ink3); }

.form-field input:focus,
.form-field select:focus {
  border-color: rgba(200,255,0,.35);
  box-shadow: 0 0 0 3px rgba(200,255,0,.07);
}

.form-field label {
  font-family: var(--mono);
  font-size: 8px;
  letter-spacing: .14em;
  text-transform: uppercase;
  color: var(--ink3);
  display: block;
  margin-bottom: 4px;
}

/* Price field */
.price-wrap {
  position: relative;
}

.price-wrap input { padding-left: 24px; }

.price-wrap::before {
  content: '£';
  position: absolute;
  left: 10px;
  top: 50%;
  transform: translateY(-50%);
  font-family: var(--mono);
  font-size: 12px;
  color: var(--ink3);
  pointer-events: none;
}

/* Save button */
#saveBtn {
  width: 100%;
  height: 42px;
  background: var(--acid);
  color: var(--void);
  border: none;
  border-radius: var(--radius-md);
  font-family: var(--mono);
  font-size: 10px;
  font-weight: 700;
  letter-spacing: .14em;
  text-transform: uppercase;
  cursor: pointer;
  display: none;
  align-items: center;
  justify-content: center;
  gap: 8px;
  transition: box-shadow .2s;
  box-shadow: var(--acid-glow-sm);
}

#saveBtn:hover { box-shadow: var(--acid-glow); }

#saveBtn.loading { opacity: .6; pointer-events: none; }

/* Right — recents */
.scan-right {
  flex: 1;
  display: flex;
  flex-direction: column;
  overflow: hidden;
}

.recents-header {
  display: flex;
  align-items: center;
  justify-content: space-between;
  padding: 14px 20px 14px 20px;
  border-bottom: 1px solid var(--border);
}

.recents-title {
  font-family: var(--mono);
  font-size: 9px;
  letter-spacing: .16em;
  text-transform: uppercase;
  color: var(--acid);
  display: flex;
  align-items: center;
  gap: 8px;
}

.recents-title::before {
  content: '';
  width: 5px; height: 5px;
  border-radius: 50%;
  background: var(--acid);
  box-shadow: var(--acid-glow-sm);
}

.recents-grid {
  flex: 1;
  overflow-y: auto;
  padding: 16px;
  display: grid;
  grid-template-columns: repeat(auto-fill, minmax(140px, 1fr));
  gap: 10px;
  align-content: start;
}

/* Recent item card */
.rc {
  background: var(--surface);
  border: 1px solid var(--border);
  border-radius: var(--radius-md);
  overflow: hidden;
  cursor: pointer;
  transition: border-color .2s, transform .2s;
  -webkit-tap-highlight-color: transparent;
}

.rc:hover {
  border-color: rgba(200,255,0,.25);
  transform: translateY(-2px);
}

.rc-img {
  width: 100%;
  aspect-ratio: 4/3;
  object-fit: cover;
  background: var(--surface2);
  display: flex;
  align-items: center;
  justify-content: center;
}

.rc-img-placeholder {
  width: 100%;
  aspect-ratio: 4/3;
  background: var(--surface2);
  display: flex;
  align-items: center;
  justify-content: center;
}

.rc-img-placeholder svg {
  width: 24px; height: 24px;
  stroke: var(--ink4); fill: none; stroke-width: 1.2;
}

.rc-body {
  padding: 8px 10px;
}

.rc-cat {
  font-family: var(--mono);
  font-size: 7px;
  letter-spacing: .10em;
  text-transform: uppercase;
  color: var(--acid);
  opacity: .70;
  margin-bottom: 3px;
}

.rc-name {
  font-family: var(--font);
  font-size: 11px;
  font-weight: 600;
  color: var(--ink);
  letter-spacing: -.01em;
  white-space: nowrap;
  overflow: hidden;
  text-overflow: ellipsis;
}

.rc-sub {
  font-family: var(--mono);
  font-size: 8px;
  color: var(--ink3);
  margin-top: 2px;
  white-space: nowrap;
  overflow: hidden;
  text-overflow: ellipsis;
}

.rc-foot {
  display: flex;
  align-items: center;
  justify-content: space-between;
  margin-top: 6px;
  padding-top: 6px;
  border-top: 1px solid var(--border);
}

.rc-price {
  font-family: var(--mono);
  font-size: 11px;
  font-weight: 700;
  color: var(--ink);
}

.rc-tag {
  font-family: var(--mono);
  font-size: 7px;
  letter-spacing: .08em;
  background: var(--acid-dim);
  border: 1px solid rgba(200,255,0,.15);
  color: var(--acid);
  padding: 1px 5px;
  border-radius: var(--radius);
  text-transform: uppercase;
}

/* ── MODAL ───────────────────────────────────────────────────────────────── */
#modalBg {
  display: none;
  position: fixed;
  inset: 0;
  background: rgba(5,5,7,.85);
  z-index: 500;
  backdrop-filter: blur(8px);
  align-items: center;
  justify-content: center;
  padding: 16px;
}

#modalBg.open { display: flex; }

.modal-card {
  background: var(--surface);
  border: 1px solid var(--border2);
  border-radius: var(--radius-lg);
  width: 100%;
  max-width: 480px;
  max-height: 90dvh;
  overflow-y: auto;
  position: relative;
}

.modal-img {
  width: 100%;
  height: 200px;
  object-fit: cover;
  border-radius: var(--radius-lg) var(--radius-lg) 0 0;
  display: block;
  background: var(--surface2);
}

.modal-body {
  padding: 20px;
}

.modal-cat {
  font-family: var(--mono);
  font-size: 8px;
  letter-spacing: .14em;
  text-transform: uppercase;
  color: var(--acid);
  margin-bottom: 6px;
}

.modal-title {
  font-family: var(--font);
  font-size: 20px;
  font-weight: 800;
  letter-spacing: -.03em;
  color: var(--ink);
  margin-bottom: 4px;
}

.modal-sub {
  font-family: var(--mono);
  font-size: 10px;
  color: var(--ink3);
  letter-spacing: .04em;
}

.modal-price-block {
  margin-top: 16px;
  padding: 14px;
  background: var(--surface2);
  border: 1px solid var(--border);
  border-radius: var(--radius-md);
}

.modal-price-label {
  font-family: var(--mono);
  font-size: 7px;
  letter-spacing: .16em;
  text-transform: uppercase;
  color: var(--ink3);
  margin-bottom: 4px;
}

.modal-price-val {
  font-family: var(--mono);
  font-size: 24px;
  font-weight: 700;
  color: var(--acid);
  letter-spacing: -.02em;
  text-shadow: var(--acid-glow-sm);
}

.modal-close {
  position: absolute;
  top: 12px;
  right: 12px;
  width: 30px;
  height: 30px;
  border-radius: 50%;
  background: rgba(5,5,7,.70);
  border: 1px solid var(--border2);
  color: var(--ink2);
  display: flex;
  align-items: center;
  justify-content: center;
  cursor: pointer;
  font-size: 18px;
  line-height: 1;
  font-family: var(--font);
  z-index: 1;
  transition: background .15s;
}

.modal-close:hover { background: rgba(5,5,7,.90); color: var(--ink); }
</style>
</head>

<body>
<?php include 'theme.php'; // Already included but theme.php sets data-theme before CSS ?>
<div class="cv-app">

  <!-- ── SIDEBAR ─────────────────────────────────────────────────────────── -->
  <aside class="cv-sidebar">
    <!-- Desktop wordmark -->
    <div class="cv-wordmark">
      <div class="cv-wordmark-text">Collector<em>Vault</em></div>
      <div class="cv-wordmark-tag">Collectibles Manager</div>
    </div>

    <!-- Mobile wordmark -->
    <div class="cv-mobile-wordmark" style="display:none">Collector<em>Vault</em></div>

    <!-- Nav items -->
    <nav class="cv-nav">
      <a href="/beta/scanner.php" class="cv-nav-item active">
        <svg viewBox="0 0 24 24"><path d="M3 7V5a2 2 0 0 1 2-2h2M17 3h2a2 2 0 0 1 2 2v2M21 17v2a2 2 0 0 1-2 2h-2M7 21H5a2 2 0 0 1-2-2v-2"/><rect x="7" y="7" width="10" height="10" rx="1"/></svg>
        <span class="cv-nav-label">Scan</span>
      </a>
      <a href="/beta/collection.php" class="cv-nav-item">
        <svg viewBox="0 0 24 24"><rect x="3" y="3" width="7" height="7" rx="1"/><rect x="14" y="3" width="7" height="7" rx="1"/><rect x="3" y="14" width="7" height="7" rx="1"/><rect x="14" y="14" width="7" height="7" rx="1"/></svg>
        <span class="cv-nav-label">Collection</span>
      </a>
    </nav>

    <!-- Desktop foot -->
    <div class="cv-sidebar-foot">
      <div class="cv-user-chip">
        <div class="cv-user-avatar"><?= strtoupper(substr($username,0,1)) ?></div>
        <div class="cv-user-name"><?= $username ?></div>
      </div>
      <div style="display:flex;gap:6px;margin-top:6px">
        <button class="cv-icon-btn" onclick="toggleTheme()" id="themeToggle" style="flex:1" aria-label="Toggle theme">
          <span id="themeIconWrap"></span>
        </button>
        <a href="/beta/logout.php" class="cv-icon-btn" style="flex:1;text-decoration:none" aria-label="Sign out">
          <svg viewBox="0 0 24 24"><path d="M9 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h4"/><polyline points="16,17 21,12 16,7"/><line x1="21" y1="12" x2="9" y2="12"/></svg>
        </a>
      </div>
    </div>

    <!-- Mobile controls -->
    <div class="cv-mobile-controls">
      <button class="cv-icon-btn" onclick="toggleTheme()" id="themeToggleMobile" aria-label="Toggle theme">
        <span id="themeIconWrapMobile"></span>
      </button>
      <a href="/beta/logout.php" class="cv-icon-btn" aria-label="Sign out">
        <svg viewBox="0 0 24 24"><path d="M9 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h4"/><polyline points="16,17 21,12 16,7"/><line x1="21" y1="12" x2="9" y2="12"/></svg>
      </a>
    </div>
  </aside>

  <!-- ── MAIN CONTENT ───────────────────────────────────────────────────── -->
  <main class="cv-main">
    <div class="scanner-wrap">

      <!-- ── PICKER VIEW ─────────────────────────────────────────────────── -->
      <div id="pickerView">
        <div class="picker-header">
          <div class="picker-overline">Scanner — AI Identification</div>
          <h1 class="picker-headline">What are you<br>cataloguing?</h1>
          <p class="picker-sub">Select a category. Photograph your item.<br>Gemini identifies it and prices it automatically.</p>
        </div>

        <div class="cat-grid">
          <!-- Cards -->
          <div class="cat-zone" onclick="selectCat('cards')">
            <img class="cat-zone-img" src="/images/card-cards.jpg" alt="" loading="eager">
            <div class="cat-zone-scrim"></div>
            <div class="cat-zone-num">
              <svg viewBox="0 0 24 24"><rect x="2" y="5" width="20" height="14" rx="2"/><path d="M2 10h20"/></svg>
              01
            </div>
            <div class="cat-zone-arrow">
              <svg viewBox="0 0 24 24"><polyline points="7,17 17,7"/><polyline points="7,7 17,7 17,17"/></svg>
            </div>
            <div class="cat-zone-content">
              <div class="cat-zone-name">Trading Cards</div>
              <div class="cat-zone-desc">Pokémon · Sports · TCG</div>
            </div>
          </div>

          <!-- Shirts -->
          <div class="cat-zone" onclick="selectCat('shirts')">
            <img class="cat-zone-img" src="/images/card-shirts.jpg" alt="" loading="lazy">
            <div class="cat-zone-scrim"></div>
            <div class="cat-zone-num">
              <svg viewBox="0 0 24 24"><path d="M20.38 3.46L16 2a4 4 0 0 1-8 0L3.62 3.46a2 2 0 0 0-1.34 2.23l.58 3.57a1 1 0 0 0 .99.84H6v10c0 1.1.9 2 2 2h8a2 2 0 0 0 2-2V10h2.15a1 1 0 0 0 .99-.84l.58-3.57a2 2 0 0 0-1.34-2.23z"/></svg>
              02
            </div>
            <div class="cat-zone-arrow">
              <svg viewBox="0 0 24 24"><polyline points="7,17 17,7"/><polyline points="7,7 17,7 17,17"/></svg>
            </div>
            <div class="cat-zone-content">
              <div class="cat-zone-name">Football Shirts</div>
              <div class="cat-zone-desc">Home · Away · Retro</div>
            </div>
          </div>

          <!-- Games -->
          <div class="cat-zone" onclick="selectCat('games')">
            <img class="cat-zone-img" src="/images/card-games.jpg" alt="" loading="lazy">
            <div class="cat-zone-scrim"></div>
            <div class="cat-zone-num">
              <svg viewBox="0 0 24 24"><rect x="2" y="6" width="20" height="12" rx="2"/><path d="M12 12h.01M7 12h.01M17 12h.01M12 7v10"/></svg>
              03
            </div>
            <div class="cat-zone-arrow">
              <svg viewBox="0 0 24 24"><polyline points="7,17 17,7"/><polyline points="7,7 17,7 17,17"/></svg>
            </div>
            <div class="cat-zone-content">
              <div class="cat-zone-name">Video Games</div>
              <div class="cat-zone-desc">Retro · Modern · CIB</div>
            </div>
          </div>

          <!-- Vinyl -->
          <div class="cat-zone" onclick="selectCat('vinyl')">
            <img class="cat-zone-img" src="/images/card-vinyl.jpg" alt="" loading="lazy">
            <div class="cat-zone-scrim"></div>
            <div class="cat-zone-num">
              <svg viewBox="0 0 24 24"><circle cx="12" cy="12" r="10"/><circle cx="12" cy="12" r="3"/></svg>
              04
            </div>
            <div class="cat-zone-arrow">
              <svg viewBox="0 0 24 24"><polyline points="7,17 17,7"/><polyline points="7,7 17,7 17,17"/></svg>
            </div>
            <div class="cat-zone-content">
              <div class="cat-zone-name">Vinyl & Music</div>
              <div class="cat-zone-desc">LP · 7" · CD · Cassette</div>
            </div>
          </div>

          <!-- Other -->
          <div class="cat-zone" onclick="selectCat('other')">
            <img class="cat-zone-img" src="/images/card-other.jpg" alt="" loading="lazy">
            <div class="cat-zone-scrim"></div>
            <div class="cat-zone-num">
              <svg viewBox="0 0 24 24"><circle cx="12" cy="12" r="10"/><line x1="12" y1="8" x2="12" y2="12"/><line x1="12" y1="16" x2="12.01" y2="16"/></svg>
              05
            </div>
            <div class="cat-zone-arrow">
              <svg viewBox="0 0 24 24"><polyline points="7,17 17,7"/><polyline points="7,7 17,7 17,17"/></svg>
            </div>
            <div class="cat-zone-content">
              <div class="cat-zone-name">Other</div>
              <div class="cat-zone-desc">Anything else</div>
            </div>
          </div>
        </div>
      </div>

      <!-- ── SCAN VIEW ───────────────────────────────────────────────────── -->
      <div id="scanView">
        <div class="scan-header">
          <div class="scan-breadcrumb" id="scanBreadcrumb">Scanner — Trading Cards</div>
          <button class="scan-change-btn" onclick="showPicker()">
            <svg viewBox="0 0 24 24"><polyline points="15,18 9,12 15,6"/></svg>
            Change
          </button>
        </div>

        <div class="cat-pills" id="catPills"></div>

        <div class="scan-body">
          <!-- Left: form -->
          <div class="scan-left">
            <div class="dropzone-area" id="dropzone"
              onclick="document.getElementById('fileInput').click()"
              ondragover="onDragOver(event)"
              ondragleave="onDragLeave()"
              ondrop="onDrop(event)">
              <input type="file" id="fileInput" accept="image/*" capture="environment" onchange="handleFile(event)">
              <div class="dropzone-icon">
                <svg viewBox="0 0 24 24"><path d="M23 19a2 2 0 0 1-2 2H3a2 2 0 0 1-2-2V8a2 2 0 0 1 2-2h4l2-3h6l2 3h4a2 2 0 0 1 2 2z"/><circle cx="12" cy="13" r="4"/></svg>
              </div>
              <div class="dropzone-title">Photograph your item</div>
              <div class="dropzone-sub">Tap to open camera · Drag & drop</div>
            </div>

            <div id="previewWrap">
              <img id="previewImg" src="" alt="Preview">
            </div>

            <div id="scanningState">
              <div class="scan-spinner"></div>
              Gemini is identifying…
            </div>

            <div id="errorBox"></div>
            <div id="idBlock">
              <div class="id-name" id="idName"></div>
              <div class="id-meta" id="idMeta"></div>
            </div>

            <div class="form-fields" id="formFields">
              <div id="fieldRows"></div>
              <div class="form-field">
                <label>Paid (£)</label>
                <div class="price-wrap">
                  <input type="number" id="pricePaid" placeholder="0.00" step="0.01" min="0">
                </div>
              </div>
              <button class="btn btn-acid" id="saveBtn" onclick="saveItem()">Save to Vault</button>
            </div>
          </div>

          <!-- Right: recents -->
          <div class="scan-right">
            <div class="recents-header">
              <div class="recents-title">Recent Scans</div>
              <a href="/beta/collection.php" class="btn btn-ghost" style="height:28px;font-size:8px">View All</a>
            </div>
            <div class="recents-grid" id="recentsGrid">
              <div style="grid-column:1/-1;text-align:center;padding:40px 0;font-family:var(--mono);font-size:9px;color:var(--ink3)">Loading…</div>
            </div>
          </div>
        </div>
      </div>

    </div>
  </main>

</div>

<!-- Modal -->
<div id="modalBg" onclick="if(event.target===this)closeModal()">
  <div class="modal-card" id="modalCard">
    <button class="modal-close" onclick="closeModal()">×</button>
    <img class="modal-img" id="modalImg" src="" alt="">
    <div class="modal-body">
      <div class="modal-cat" id="modalCat"></div>
      <div class="modal-title" id="modalTitle"></div>
      <div class="modal-sub" id="modalSub"></div>
      <div class="modal-price-block">
        <div class="modal-price-label">Market Value</div>
        <div class="modal-price-val" id="modalPrice">—</div>
      </div>
    </div>
  </div>
</div>

<div id="toast"></div>

<script>
<?php include 'categories.js.php'; ?>

let currentCat = 'cards', currentAI = null, currentB64 = null, currentMime = null;
let toastT;

/* Theme */
function _renderThemeIcon(t) {
  const svgSun = '<svg viewBox="0 0 24 24" width="14" height="14" fill="none" stroke="currentColor" stroke-width="1.5"><circle cx="12" cy="12" r="4"/><path d="M12 2v2M12 20v2M4.93 4.93l1.41 1.41M17.66 17.66l1.41 1.41M2 12h2M20 12h2M4.93 19.07l1.41-1.41M17.66 6.34l1.41-1.41"/></svg>';
  const svgMoon = '<svg viewBox="0 0 24 24" width="14" height="14" fill="none" stroke="currentColor" stroke-width="1.5"><path d="M21 12.79A9 9 0 1 1 11.21 3 7 7 0 0 0 21 12.79z"/></svg>';
  document.querySelectorAll('#themeIconWrap,#themeIconWrapMobile').forEach(el => {
    if (el) el.innerHTML = t === 'dark' ? svgSun : svgMoon;
  });
}
function toggleTheme() {
  const t = document.documentElement.getAttribute('data-theme') === 'dark' ? 'light' : 'dark';
  document.documentElement.setAttribute('data-theme', t);
  localStorage.setItem('cv_theme', t);
  _renderThemeIcon(t);
}
document.addEventListener('DOMContentLoaded', () => {
  const t = localStorage.getItem('cv_theme') || 'dark';
  document.documentElement.setAttribute('data-theme', t);
  _renderThemeIcon(t);
});

/* Category selection */
function selectCat(cat) {
  currentCat = cat;
  const def = CATEGORIES[cat];
  document.getElementById('scanBreadcrumb').textContent = `Scanner — ${def.label}`;
  document.getElementById('pickerView').style.display = 'none';
  document.getElementById('scanView').style.display = 'flex';
  document.getElementById('scanView').style.flexDirection = 'column';
  buildPills(cat);
  loadRecent();
}

function showPicker() {
  document.getElementById('pickerView').style.display = 'flex';
  document.getElementById('pickerView').style.flexDirection = 'column';
  document.getElementById('scanView').style.display = 'none';
  resetScan();
}

/* For compatibility with old function names */
function selectCatFromPicker(cat) { selectCat(cat); }

function buildPills(activeCat) {
  const pills = document.getElementById('catPills');
  pills.innerHTML = Object.entries(CATEGORIES).map(([k,v]) => `
    <button class="cat-pill-btn ${k===activeCat?'active':''}" onclick="setCat('${k}')">
      ${CAT_ICONS_SVG[k]||''}
      ${v.label}
    </button>
  `).join('');
}

function setCat(cat) {
  currentCat = cat;
  const def = CATEGORIES[cat];
  document.getElementById('scanBreadcrumb').textContent = `Scanner — ${def.label}`;
  document.querySelectorAll('.cat-pill-btn').forEach(b => {
    b.classList.toggle('active', b.textContent.trim().startsWith(def.label));
  });
}

// SVG icons per category
const CAT_ICONS_SVG = {
  cards:  '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" width="11" height="11"><rect x="2" y="5" width="20" height="14" rx="2"/><path d="M2 10h20"/></svg>',
  shirts: '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" width="11" height="11"><path d="M20.38 3.46L16 2a4 4 0 0 1-8 0L3.62 3.46a2 2 0 0 0-1.34 2.23l.58 3.57a1 1 0 0 0 .99.84H6v10c0 1.1.9 2 2 2h8a2 2 0 0 0 2-2V10h2.15a1 1 0 0 0 .99-.84l.58-3.57a2 2 0 0 0-1.34-2.23z"/></svg>',
  games:  '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" width="11" height="11"><rect x="2" y="6" width="20" height="12" rx="2"/><path d="M12 12h.01M7 12h.01M17 12h.01"/></svg>',
  vinyl:  '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" width="11" height="11"><circle cx="12" cy="12" r="10"/><circle cx="12" cy="12" r="3"/></svg>',
  other:  '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" width="11" height="11"><circle cx="12" cy="12" r="10"/><line x1="12" y1="8" x2="12" y2="12"/><line x1="12" y1="16" x2="12.01" y2="16"/></svg>',
};

/* Drag/drop */
function onDragOver(e){e.preventDefault();document.getElementById('dropzone').classList.add('drag-over');}
function onDragLeave(){document.getElementById('dropzone').classList.remove('drag-over');}
function onDrop(e){e.preventDefault();document.getElementById('dropzone').classList.remove('drag-over');const f=e.dataTransfer.files[0];if(f&&f.type.startsWith('image/'))processFile(f);}
function handleFile(e){const f=e.target.files[0];if(f)processFile(f);e.target.value='';}

async function processFile(file) {
  currentB64 = await toBase64(file);
  currentMime = file.type;
  const pw = document.getElementById('previewWrap');
  document.getElementById('previewImg').src = URL.createObjectURL(file);
  pw.style.display = 'block';
  document.getElementById('scanningState').style.display = 'flex';
  document.getElementById('errorBox').style.display = 'none';
  document.getElementById('idBlock').style.display = 'none';
  document.getElementById('formFields').style.display = 'none';
  document.getElementById('saveBtn').style.display = 'none';

  try {
    const fd = new FormData();
    fd.append('action','scan');
    fd.append('imageBase64', currentB64);
    fd.append('mediaType', currentMime);
    fd.append('prompt', CATEGORIES[currentCat].prompt);
    const resp = await fetch('/beta/api.php',{method:'POST',body:fd,credentials:'same-origin'});
    const data = await resp.json();
    if (!data.ok) throw new Error(data.error||'Scan failed');
    currentAI = parseGemini(data.text);
    buildForm(currentAI);
    document.getElementById('idBlock').style.display = 'block';
    document.getElementById('idName').textContent = currentAI.name || 'Item identified';
    document.getElementById('idMeta').textContent = [currentAI.subtitle, currentAI.year].filter(Boolean).join(' · ');
    document.getElementById('formFields').style.display = 'flex';
    document.getElementById('formFields').style.flexDirection = 'column';
    document.getElementById('saveBtn').style.display = 'flex';
  } catch(e) {
    showError(e.message);
  } finally {
    document.getElementById('scanningState').style.display = 'none';
  }
}

function toBase64(f){return new Promise((res,rej)=>{const r=new FileReader();r.onload=()=>res(r.result.split(',')[1]);r.onerror=rej;r.readAsDataURL(f);});}

function parseGemini(raw){
  const m=raw.match(/\{[\s\S]*\}/);
  if(!m)return{name:'Unknown',fields:{}};
  try{return JSON.parse(m[0]);}catch{return{name:'Unknown',fields:{}};}
}

function buildForm(ai) {
  const fields = CATEGORIES[currentCat].fields || [];
  const rows = document.getElementById('fieldRows');
  rows.innerHTML = fields.map(f => `
    <div class="form-field" style="margin-bottom:10px">
      <label>${f.label}</label>
      ${f.type==='select'
        ? `<select id="f_${f.key}" class="cv-input" style="height:36px">
            ${(f.options||[]).map(o=>`<option value="${o}" ${(ai[f.key]||'')==o?'selected':''}>${o}</option>`).join('')}
           </select>`
        : `<input type="${f.type||'text'}" id="f_${f.key}" class="cv-input" value="${ai[f.key]||''}" placeholder="${f.placeholder||f.label}">`
      }
    </div>
  `).join('');
  // Hidden name field
  if (!document.getElementById('f_name')) {
    const ni = document.createElement('input');
    ni.type = 'hidden';
    ni.id = 'f_name';
    ni.value = ai.name || '';
    rows.appendChild(ni);
  }
}

async function saveItem() {
  const btn = document.getElementById('saveBtn');
  btn.classList.add('loading');
  btn.textContent = 'Saving…';
  const fields = CATEGORIES[currentCat].fields || [];
  const item = { name: document.getElementById('f_name')?.value || currentAI?.name || 'Unknown', category: currentCat };
  fields.forEach(f => { const el = document.getElementById('f_'+f.key); if(el) item[f.key] = el.value; });
  item.price_paid = document.getElementById('pricePaid')?.value || '';
  try {
    const fd = new FormData();
    fd.append('action','save');
    fd.append('item', JSON.stringify(item));
    if (currentB64) { fd.append('imageBase64', currentB64); fd.append('mediaType', currentMime); }
    const resp = await fetch('/beta/api.php',{method:'POST',body:fd,credentials:'same-origin'});
    const data = await resp.json();
    if (!data.ok) throw new Error(data.error||'Save failed');
    showSaveSuccess(item.name);
    resetScan();
    loadRecent();
  } catch(e) {
    showError(e.message);
  } finally {
    btn.classList.remove('loading');
    btn.textContent = 'Save to Vault';
  }
}

function resetScan() {
  currentAI = null; currentB64 = null; currentMime = null;
  document.getElementById('previewWrap').style.display = 'none';
  document.getElementById('previewImg').src = '';
  document.getElementById('scanningState').style.display = 'none';
  document.getElementById('errorBox').style.display = 'none';
  document.getElementById('idBlock').style.display = 'none';
  document.getElementById('formFields').style.display = 'none';
  document.getElementById('saveBtn').style.display = 'none';
  document.getElementById('pricePaid').value = '';
  document.getElementById('fieldRows').innerHTML = '';
  document.getElementById('fileInput').value = '';
}

function showError(msg){const el=document.getElementById('errorBox');el.textContent='⚠ '+msg;el.style.display='block';}

/* Recents */
async function loadRecent() {
  try {
    const r = await fetch('/beta/api.php?action=collection&category=all',{credentials:'same-origin'});
    const d = await r.json();
    if (d.ok) renderRecent(d.items||[]);
  } catch(e) {}
}

function renderRecent(items) {
  const g = document.getElementById('recentsGrid');
  if (!items.length) {
    g.innerHTML = '<div style="grid-column:1/-1;text-align:center;padding:40px 0;font-family:var(--mono);font-size:9px;color:var(--ink3);letter-spacing:.08em;text-transform:uppercase">No items yet</div>';
    return;
  }
  g.innerHTML = items.slice(0,18).map(item => `
    <div class="rc" onclick="openRecentModal(${JSON.stringify(item).replace(/"/g,'&quot;')})">
      <div class="rc-img-placeholder" id="rc-img-${item.id}">
        <svg viewBox="0 0 24 24"><rect x="3" y="3" width="18" height="18" rx="2"/><circle cx="8.5" cy="8.5" r="1.5"/><polyline points="21,15 16,10 5,21"/></svg>
      </div>
      <div class="rc-body">
        <div class="rc-cat">${item.category||'—'}</div>
        <div class="rc-name">${esc(item.name)}</div>
        <div class="rc-sub">${esc(item.subtitle||item.series||'')}</div>
        <div class="rc-foot">
          <div class="rc-price">£—</div>
          ${item.item_type?`<div class="rc-tag">${esc(item.item_type)}</div>`:''}
        </div>
      </div>
    </div>
  `).join('');

  // Load images
  items.slice(0,18).forEach(item => {
    const q = [item.name,item.subtitle,item.series].filter(Boolean).join(' ');
    fetch('/beta/api.php?'+new URLSearchParams({action:'getImage',id:item.id,query:q,cat:item.category}),{credentials:'same-origin'})
      .then(r=>r.json()).then(d=>{
        if (d.url) {
          const el = document.getElementById('rc-img-'+item.id);
          if (el) { const img=document.createElement('img');img.className='rc-img';img.src=d.url;img.alt=item.name;img.style.width='100%';img.style.aspectRatio='4/3';img.style.objectFit='cover';el.replaceWith(img); }
        }
      }).catch(()=>{});
  });
}

function esc(s){return String(s||'').replace(/&/g,'&amp;').replace(/</g,'&lt;').replace(/>/g,'&gt;').replace(/"/g,'&quot;');}

/* Recent modal */
let recentItems = {};
function openRecentModal(item) {
  recentItems[item.id] = item;
  document.getElementById('modalCat').textContent = item.category || '';
  document.getElementById('modalTitle').textContent = item.name || '—';
  document.getElementById('modalSub').textContent = [item.subtitle,item.series,item.year].filter(Boolean).join(' · ');
  document.getElementById('modalImg').src = '';
  document.getElementById('modalPrice').textContent = '—';
  document.getElementById('modalBg').classList.add('open');
  const q = [item.name,item.subtitle,item.series].filter(Boolean).join(' ');
  fetch('/beta/api.php?'+new URLSearchParams({action:'getImage',id:item.id,query:q,cat:item.category}),{credentials:'same-origin'})
    .then(r=>r.json()).then(d=>{if(d.url)document.getElementById('modalImg').src=d.url;}).catch(()=>{});
  fetch('/beta/api.php?'+new URLSearchParams({action:'getPrices'}),{credentials:'same-origin'})
    .then(r=>r.json()).then(d=>{
      if(d.ok&&d.prices&&d.prices[item.id]){
        const p=d.prices[item.id];
        if(p.avg_10)document.getElementById('modalPrice').textContent='£'+parseFloat(p.avg_10).toFixed(2);
      }
    }).catch(()=>{});
}

function closeModal() { document.getElementById('modalBg').classList.remove('open'); }

document.addEventListener('keydown',e=>{ if(e.key==='Escape')closeModal(); });

/* Save success */
function showSaveSuccess(name) {
  const el = document.createElement('div');
  el.className = 'save-success-overlay';
  el.innerHTML = '<div class="save-success-circle"><svg viewBox="0 0 44 44"><polyline points="8,22 18,32 36,14" stroke-linecap="round" stroke-linejoin="round"/></svg></div>';
  document.body.appendChild(el);
  showToast(`"${name}" saved`);
  setTimeout(() => el.remove(), 2000);
}

function showToast(msg){
  const el=document.getElementById('toast');
  el.textContent=msg;
  el.classList.add('show');
  clearTimeout(toastT);
  toastT=setTimeout(()=>el.classList.remove('show'),2800);
}

loadRecent();
</script>
</body>
</html>
