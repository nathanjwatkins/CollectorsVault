<?php
ob_start();
header('Cache-Control: no-cache, no-store, must-revalidate, max-age=0');
header('Pragma: no-cache');
header('Expires: 0');
header('X-Frame-Options: SAMEORIGIN');
header('Referrer-Policy: same-origin');
ini_set('session.cookie_samesite','Lax'); ini_set('session.cookie_secure','1'); ini_set('session.cookie_httponly','1');
session_start();
if (!isset($_SESSION['user'])) { header('Location: /index.php'); exit; }
$username = htmlspecialchars($_SESSION['user']);
?>
<!DOCTYPE html>
<html lang="en" data-theme="dark">
<head>
<meta charset="UTF-8"/>
<meta name="theme-color" content="#0c0c10">
<meta name="viewport" content="width=device-width, initial-scale=1.0, viewport-fit=cover"/>
<meta name="mobile-web-app-capable" content="yes"/>
<meta name="apple-mobile-web-app-capable" content="yes">
<meta name="apple-mobile-web-app-status-bar-style" content="black-translucent">
<meta name="apple-mobile-web-app-title" content="CollectorVault">
<link rel="manifest" href="/manifest.json">
<title>CollectorVault — Collection</title>
<link rel="preconnect" href="https://fonts.googleapis.com">
<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
<link href="https://fonts.googleapis.com/css2?family=Geist+Mono:wght@300;400;500&family=Geist:wght@300;400;500;600&display=swap" rel="stylesheet">
<?php include 'theme.php'; ?>
<link rel="stylesheet" href="shared.css?v=20260513">
<style>
/* ── COLLECTION PAGE LAYOUT ──────────────────────────────────────────────── */

/* Stats hero */
.stats-zone {
  display: grid;
  grid-template-columns: 1fr 1fr;
  border-bottom: 1px solid rgba(255,255,255,.08);
  background: rgba(10,10,10,.60);
  backdrop-filter: blur(24px);
  -webkit-backdrop-filter: blur(24px);
}
@media(min-width:640px){ .stats-zone { grid-template-columns: repeat(4,1fr); } }

.stat-block {
  padding: 24px 20px 18px;
  border-right: 1px solid rgba(255,255,255,.08);
  position: relative; overflow: hidden;
}
.stat-block:last-child  { border-right: none; }
.stat-block:nth-child(2){ border-right: none; }
@media(min-width:640px){
  .stat-block:nth-child(2){ border-right: 1px solid rgba(255,255,255,.08); }
  .stat-block:last-child  { border-right: none; }
}
/* Acid accent line */
.stat-block::before {
  content: ''; position: absolute; top: 0; left: 0; right: 0; height: 1px;
  background: linear-gradient(90deg, var(--acid) 0%, transparent 60%); opacity: .20;
}
.stat-label { font-family: var(--font-mono); font-size: 7px; letter-spacing: .20em; text-transform: uppercase; color: var(--ink3); margin-bottom: 10px; }
.stat-value { font-family: var(--font-sans); font-size: clamp(26px,4vw,48px); font-weight: 200; letter-spacing: -.04em; color: var(--ink); line-height: 1; }
.stat-value.is-gain { color: var(--acid); }
.stat-value.is-loss { color: var(--red); }

/* Toolbar */
.coll-toolbar {
  display: flex; align-items: center; gap: 8px;
  padding: 10px 16px;
  border-bottom: 1px solid rgba(255,255,255,.08);
  overflow-x: auto; scrollbar-width: none;
  background: rgba(10,10,10,.75);
  backdrop-filter: blur(20px);
  -webkit-backdrop-filter: blur(20px);
  position: sticky; top: var(--nav-h); z-index: 100; flex-shrink: 0;
}
.coll-toolbar::-webkit-scrollbar { display: none; }

/* Category tabs */
.cat-tab {
  display: flex; align-items: center; gap: 5px;
  padding: 5px 12px; border-radius: 20px;
  font-family: var(--font-mono); font-size: 8px; letter-spacing: .08em; text-transform: uppercase;
  color: var(--ink3); background: rgba(255,255,255,.06); border: 1px solid rgba(255,255,255,.10);
  cursor: pointer; transition: all .15s; white-space: nowrap; flex-shrink: 0;
}
.cat-tab svg { width: 11px; height: 11px; stroke: currentColor; fill: none; stroke-width: 1.5; }
.cat-tab:hover  { color: var(--ink); border-color: rgba(255,255,255,.22); }
.cat-tab.active { background: var(--acid-dim); border-color: rgba(206,255,46,.25); color: var(--acid); }
.cat-count { font-size: 7px; opacity: .60; }

.toolbar-gap { flex: 1; min-width: 8px; }

/* Search */
.search-field { position: relative; flex-shrink: 0; width: 160px; }
@media(min-width:640px){ .search-field { width: 200px; } }
.search-field svg { position: absolute; left: 10px; top: 50%; transform: translateY(-50%); width: 12px; height: 12px; stroke: var(--ink3); fill: none; stroke-width: 1.5; pointer-events: none; }
.search-field input {
  width: 100%; height: 30px; padding: 0 10px 0 30px;
  background: rgba(255,255,255,.08); border: 1px solid rgba(255,255,255,.12); border-radius: 20px;
  font-family: var(--font-mono); font-size: 10px; color: var(--ink); outline: none;
  transition: border-color .15s; -webkit-appearance: none;
}
.search-field input::placeholder { color: var(--ink3); }
.search-field input:focus { border-color: rgba(206,255,46,.30); }

/* Sort + view toggle */
.sort-btn {
  height: 30px; padding: 0 12px;
  background: rgba(255,255,255,.08); border: 1px solid rgba(255,255,255,.12); border-radius: var(--radius-md);
  font-family: var(--font-mono); font-size: 9px; letter-spacing: .06em; color: var(--ink2);
  cursor: pointer; outline: none; -webkit-appearance: none; flex-shrink: 0;
}
.view-toggle { display: flex; border: 1px solid rgba(255,255,255,.12); border-radius: var(--radius-md); overflow: hidden; flex-shrink: 0; }
.view-btn {
  width: 30px; height: 30px; display: flex; align-items: center; justify-content: center;
  background: rgba(255,255,255,.06); border: none; color: var(--ink3); cursor: pointer;
  transition: all .15s; border-right: 1px solid rgba(255,255,255,.10);
}
.view-btn:last-child { border-right: none; }
.view-btn svg { width: 13px; height: 13px; stroke: currentColor; fill: none; stroke-width: 1.5; }
.view-btn.active { background: rgba(245,245,245,.90); color: #111; }

/* Price bar */
.price-bar {
  display: flex; align-items: center; justify-content: space-between;
  padding: 6px 20px;
  border-bottom: 1px solid rgba(255,255,255,.06);
  background: rgba(10,10,10,.55);
  backdrop-filter: blur(16px);
  -webkit-backdrop-filter: blur(16px);
  font-family: var(--font-mono); font-size: 8px; letter-spacing: .08em; color: var(--ink3); text-transform: uppercase; flex-shrink: 0;
}

/* ── Items grid / list ─────────────────────────────────────────────────────── */
.coll-body { padding: 16px 20px; background: transparent; }

.items-grid { display: grid; grid-template-columns: repeat(2,1fr); gap: 10px; }
@media(min-width:480px){ .items-grid { grid-template-columns: repeat(3,1fr); } }
@media(min-width:700px){ .items-grid { grid-template-columns: repeat(4,1fr); } }
@media(min-width:1000px){ .items-grid { grid-template-columns: repeat(5,1fr); } }
@media(min-width:1300px){ .items-grid { grid-template-columns: repeat(6,1fr); } }

.items-list { display: flex; flex-direction: column; gap: 1px; }

/* ic-* item card internals → shared.css §12 */

/* List row */
.item-row {
  display: flex; align-items: center; gap: 12px; padding: 10px 12px;
  background: rgba(255,255,255,.06); border: 1px solid rgba(255,255,255,.10); border-radius: var(--radius-md);
  cursor: pointer; transition: border-color .15s; -webkit-tap-highlight-color: transparent;
}
.item-row:hover { border-color: rgba(206,255,46,.22); }
.ir-thumb { width: 44px; height: 44px; border-radius: var(--radius-md); overflow: hidden; background: rgba(255,255,255,.06); flex-shrink: 0; display: flex; align-items: center; justify-content: center; }
.ir-thumb img { width: 100%; height: 100%; object-fit: cover; }
.ir-info  { flex: 1; min-width: 0; }
.ir-name  { font-family: var(--font-sans); font-size: 13px; font-weight: 600; color: var(--ink); letter-spacing: -.01em; white-space: nowrap; overflow: hidden; text-overflow: ellipsis; }
.ir-sub   { font-family: var(--font-mono); font-size: 9px; color: var(--ink3); margin-top: 2px; letter-spacing: .02em; white-space: nowrap; overflow: hidden; text-overflow: ellipsis; }
.ir-price { font-family: var(--font-mono); font-size: 13px; font-weight: 700; color: var(--ink); flex-shrink: 0; }

/* Empty state */
.empty-state { grid-column: 1/-1; display: flex; flex-direction: column; align-items: center; gap: 12px; padding: 60px 20px; text-align: center; }
.empty-state svg { width: 48px; height: 48px; stroke: var(--ink3); fill: none; stroke-width: 1; }
.empty-state h3  { font-family: var(--font-sans); font-size: 18px; font-weight: 600; color: var(--ink2); letter-spacing: -.02em; }
.empty-state p   { font-family: var(--font-mono); font-size: 10px; color: var(--ink3); letter-spacing: .04em; }

/* ── Item modal ──────────────────────────────────────────────────────────── */
#modalBg {
  display: none; position: fixed; inset: 0;
  background: rgba(5,5,7,.85); z-index: 500;
  backdrop-filter: blur(8px); -webkit-backdrop-filter: blur(8px);
  align-items: flex-end; justify-content: center; padding: 0;
}
#modalBg.open { display: flex; }
@media(min-width:640px){ #modalBg { align-items: center; padding: 16px; } }

.modal-sheet {
  background: var(--surface); border: 1px solid rgba(255,255,255,.12);
  border-radius: var(--radius-lg) var(--radius-lg) 0 0;
  width: 100%; max-height: 92dvh; overflow-y: auto; position: relative;
  padding-bottom: env(safe-area-inset-bottom, 0px);
}
@media(min-width:640px){ .modal-sheet { border-radius: var(--radius-lg); max-width: 520px; max-height: 88dvh; } }

.modal-handle { width: 36px; height: 3px; background: rgba(255,255,255,.16); border-radius: 2px; margin: 12px auto 0; }
@media(min-width:640px){ .modal-handle { display: none; } }

.modal-hero { position: relative; width: 100%; height: 240px; overflow: hidden; background: rgba(255,255,255,.06); }
.modal-hero img { width: 100%; height: 100%; object-fit: cover; display: block; }
.modal-hero-grad { position: absolute; inset: 0; background: linear-gradient(to top, rgba(12,12,16,.95) 0%, transparent 60%); }
.modal-close { position: absolute; top: 12px; right: 12px; width: 30px; height: 30px; border-radius: 50%; background: rgba(5,5,7,.70); border: 1px solid rgba(255,255,255,.14); color: var(--ink2); display: flex; align-items: center; justify-content: center; cursor: pointer; font-size: 18px; font-family: var(--font-sans); transition: background .15s; z-index: 1; }
.modal-close:hover { background: rgba(5,5,7,.90); color: var(--ink); }

.modal-body { padding: 20px 20px 28px; }
.modal-overline { font-family: var(--font-mono); font-size: 8px; letter-spacing: .16em; text-transform: uppercase; color: var(--acid); margin-bottom: 6px; }
.modal-title    { font-family: var(--font-sans); font-size: 22px; font-weight: 600; letter-spacing: -.03em; color: var(--ink); line-height: 1.1; margin-bottom: 4px; }
.modal-sub      { font-family: var(--font-mono); font-size: 10px; color: var(--ink3); letter-spacing: .04em; margin-bottom: 16px; }

.modal-prices { display: grid; grid-template-columns: repeat(3,1fr); gap: 1px; background: rgba(255,255,255,.06); border: 1px solid rgba(255,255,255,.10); border-radius: var(--radius-md); overflow: hidden; margin-bottom: 16px; }
.modal-price-cell  { background: rgba(255,255,255,.05); padding: 12px 14px; }
.modal-price-label { font-family: var(--font-mono); font-size: 7px; letter-spacing: .14em; text-transform: uppercase; color: var(--ink3); margin-bottom: 4px; }
.modal-price-val   { font-family: var(--font-mono); font-size: 16px; font-weight: 700; color: var(--ink); letter-spacing: -.01em; }
.modal-price-val.highlight { color: var(--acid); }

.modal-fields { display: grid; grid-template-columns: 1fr 1fr; gap: 10px; margin-bottom: 16px; }
.modal-field-label { font-family: var(--font-mono); font-size: 7px; letter-spacing: .14em; text-transform: uppercase; color: var(--ink3); margin-bottom: 3px; }
.modal-field-val   { font-family: var(--font-sans); font-size: 13px; font-weight: 500; color: var(--ink); }

.modal-actions { display: flex; gap: 8px; }
.modal-btn {
  flex: 1; height: 38px; display: flex; align-items: center; justify-content: center; gap: 6px;
  font-family: var(--font-mono); font-size: 9px; letter-spacing: .10em; text-transform: uppercase;
  border-radius: var(--radius-md); cursor: pointer; transition: all .15s;
  border: 1px solid rgba(255,255,255,.12); background: rgba(255,255,255,.06); color: var(--ink2);
}
.modal-btn:hover { color: var(--ink); border-color: rgba(255,255,255,.22); }
.modal-btn svg { width: 12px; height: 12px; stroke: currentColor; fill: none; stroke-width: 1.5; }
.modal-btn.danger { color: var(--red); border-color: rgba(255,68,68,.20); background: rgba(255,68,68,.05); }
.modal-btn.danger:hover { background: rgba(255,68,68,.12); }

/* ── Confirm delete ──────────────────────────────────────────────────────── */
#confirmBg { display: none; position: fixed; inset: 0; background: rgba(5,5,7,.80); z-index: 600; backdrop-filter: blur(6px); -webkit-backdrop-filter: blur(6px); align-items: center; justify-content: center; }
#confirmBg.open { display: flex; }
.confirm-box { background: var(--surface); border: 1px solid rgba(255,255,255,.12); border-radius: var(--radius-lg); padding: 28px 24px 20px; max-width: 340px; width: calc(100% - 32px); }
.confirm-title { font-family: var(--font-mono); font-size: 13px; font-weight: 600; color: var(--ink); margin-bottom: 8px; letter-spacing: .02em; }
.confirm-body  { font-size: 13px; color: var(--ink3); margin-bottom: 24px; line-height: 1.5; }
.confirm-actions { display: flex; gap: 10px; }
.confirm-actions button { flex: 1; height: 40px; border: none; border-radius: var(--radius-md); font-family: var(--font-mono); font-size: 11px; font-weight: 600; letter-spacing: .06em; text-transform: uppercase; cursor: pointer; transition: opacity .15s; }
.confirm-cancel { background: rgba(255,255,255,.08); color: var(--ink2); }
.confirm-cancel:hover { opacity: .75; }
.confirm-delete { background: #c13528; color: #fff; }
.confirm-delete:hover { opacity: .85; }

/* ── eBay match picker ───────────────────────────────────────────────────── */
.ebay-picker-row    { display: flex; align-items: center; justify-content: space-between; margin-bottom: 10px; gap: 12px; }
.ebay-picker-actions { display: flex; align-items: center; gap: 6px; flex-shrink: 0; }
.ebay-cand-grid     { display: grid; grid-template-columns: repeat(3,1fr); gap: 8px; }
@media(max-width:520px){
  .ebay-picker-row     { flex-direction: column; align-items: stretch; gap: 10px; }
  .ebay-picker-actions { justify-content: flex-start; flex-wrap: wrap; }
  .ebay-cand-grid      { grid-template-columns: repeat(2,1fr); }
}
.ebay-picker-btn { white-space: nowrap; }

/* Edit modal — slide up on mobile, centred on tablet+ */
#editBg { display: none; position: fixed; inset: 0; background: rgba(5,5,7,.90); z-index: 600; backdrop-filter: blur(8px); -webkit-backdrop-filter: blur(8px); align-items: center; justify-content: center; padding: 16px; }
@media(max-width:639px){
  #editBg { padding: 0; align-items: flex-end; }
  #editBg > div { border-radius: var(--radius-lg) var(--radius-lg) 0 0; max-height: 92dvh; }
}
</style>
</head>
<body>
<?php include 'nav.php'; ?>

<main>
  <div class="stats-zone">
    <div class="stat-block"><div class="stat-label">Items</div><div class="stat-value" id="sTotal">—</div></div>
    <div class="stat-block"><div class="stat-label">Market Value</div><div class="stat-value" id="sValue">—</div></div>
    <div class="stat-block"><div class="stat-label">Invested</div><div class="stat-value" id="sInvested">—</div></div>
    <div class="stat-block"><div class="stat-label">Gain / Loss</div><div class="stat-value" id="sGain">—</div></div>
  </div>

  <div class="coll-toolbar" id="toolbar">
    <div class="toolbar-gap"></div>
    <div class="search-field">
      <svg viewBox="0 0 24 24"><circle cx="11" cy="11" r="8"/><line x1="21" y1="21" x2="16.65" y2="16.65"/></svg>
      <input type="text" id="searchInput" placeholder="Search…" oninput="filterItems()">
    </div>
    <select class="sort-btn" id="sortSelect" onchange="filterItems()">
      <option value="newest">Newest</option>
      <option value="oldest">Oldest</option>
      <option value="name">A → Z</option>
      <option value="value_desc">Value ↓</option>
      <option value="value_asc">Value ↑</option>
    </select>
    <div class="view-toggle">
      <button class="view-btn active" id="vGrid" onclick="setView('grid')">
        <svg viewBox="0 0 24 24"><rect x="3" y="3" width="7" height="7" rx="1"/><rect x="14" y="3" width="7" height="7" rx="1"/><rect x="3" y="14" width="7" height="7" rx="1"/><rect x="14" y="14" width="7" height="7" rx="1"/></svg>
      </button>
      <button class="view-btn" id="vList" onclick="setView('list')">
        <svg viewBox="0 0 24 24"><line x1="8" y1="6" x2="21" y2="6"/><line x1="8" y1="12" x2="21" y2="12"/><line x1="8" y1="18" x2="21" y2="18"/><line x1="3" y1="6" x2="3.01" y2="6"/><line x1="3" y1="12" x2="3.01" y2="12"/><line x1="3" y1="18" x2="3.01" y2="18"/></svg>
      </button>
    </div>
  </div>

  <div class="price-bar">
    <span id="priceStatus">Loading prices…</span>
    <button class="btn-sm btn-outline" onclick="refreshAllPrices()" style="height:24px;font-size:7px;padding:0 10px;display:flex;align-items:center;gap:4px">
      <svg viewBox="0 0 24 24" style="width:10px;height:10px;stroke:currentColor;fill:none;stroke-width:1.8"><polyline points="23,4 23,11 16,11"/><path d="M20.49 15a9 9 0 1 1-2.12-9.36L23 11"/></svg>
      Refresh
    </button>
  </div>

  <div class="coll-body">
    <div class="items-grid" id="itemsGrid">
      <div class="empty-state"><p>Loading your vault…</p></div>
    </div>
  </div>
</main>

<!-- Confirm delete -->
<div id="confirmBg">
  <div class="confirm-box">
    <div class="confirm-title">Delete Item</div>
    <div class="confirm-body" id="confirmBody">Are you sure you want to remove this item from your vault? This cannot be undone.</div>
    <div class="confirm-actions">
      <button class="confirm-cancel" onclick="closeConfirm()">Cancel</button>
      <button class="confirm-delete" id="confirmDeleteBtn">Delete</button>
    </div>
  </div>
</div>

<!-- Item view modal -->
<div id="modalBg" onclick="if(event.target===this)closeModal()">
  <div class="modal-sheet">
    <div class="modal-handle"></div>
    <div class="modal-hero">
      <img id="modalImg" src="" alt="">
      <div class="modal-hero-grad"></div>
      <button class="modal-close" onclick="closeModal()" aria-label="Close">×</button>
    </div>
    <div class="modal-body">
      <div class="modal-overline" id="modalCat"></div>
      <div class="modal-title" id="modalTitle"></div>
      <div class="modal-sub" id="modalSub"></div>
      <div class="modal-prices">
        <div class="modal-price-cell"><div class="modal-price-label">Avg 10</div><div class="modal-price-val highlight" id="mAvg10">—</div></div>
        <div class="modal-price-cell"><div class="modal-price-label">Avg 30</div><div class="modal-price-val" id="mAvg30">—</div></div>
        <div class="modal-price-cell"><div class="modal-price-label">Invested</div><div class="modal-price-val" id="mPaid">—</div></div>
      </div>
      <div class="modal-fields" id="modalFields"></div>
      <div class="modal-actions">
        <button class="modal-btn" onclick="openEdit(currentModalId)">
          <svg viewBox="0 0 24 24"><path d="M11 4H4a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2v-7"/><path d="M18.5 2.5a2.121 2.121 0 0 1 3 3L12 15l-4 1 1-4 9.5-9.5z"/></svg>
          Edit
        </button>
        <button class="modal-btn" onclick="refreshSinglePrice(currentModalId)">
          <svg viewBox="0 0 24 24"><polyline points="23,4 23,11 16,11"/><path d="M20.49 15a9 9 0 1 1-2.12-9.36L23 11"/></svg>
          Refresh
        </button>
        <button class="modal-btn danger" onclick="deleteItem(currentModalId)">
          <svg viewBox="0 0 24 24"><polyline points="3,6 5,6 21,6"/><path d="M19,6l-1,14H6L5,6"/><path d="M10,11v6M14,11v6"/><path d="M9,6V4h6v2"/></svg>
          Delete
        </button>
      </div>
    </div>
  </div>
</div>

<!-- Edit modal -->
<div id="editBg" onclick="if(event.target===this)closeEdit()">
  <div style="background:var(--surface);border:1px solid rgba(255,255,255,.12);border-radius:var(--radius-lg);width:100%;max-width:560px;max-height:88dvh;overflow-y:auto">
    <div style="display:flex;align-items:center;justify-content:space-between;padding:16px 20px;border-bottom:1px solid rgba(255,255,255,.08)">
      <div style="font-family:var(--font-mono);font-size:9px;letter-spacing:.16em;text-transform:uppercase;color:var(--acid)">Edit Item</div>
      <button onclick="closeEdit()" aria-label="Close" style="width:28px;height:28px;border-radius:50%;background:rgba(255,255,255,.08);border:1px solid rgba(255,255,255,.12);color:var(--ink2);display:flex;align-items:center;justify-content:center;cursor:pointer;font-size:16px">×</button>
    </div>

    <!-- eBay match picker -->
    <div id="ebayPicker" style="padding:16px 20px;border-bottom:1px solid rgba(255,255,255,.08)">
      <div class="ebay-picker-row">
        <div style="min-width:0">
          <div style="font-family:var(--font-mono);font-size:8px;letter-spacing:.14em;text-transform:uppercase;color:var(--ink3);margin-bottom:3px">eBay match</div>
          <div id="ebayPickerStatus" style="font-family:var(--font-sans);font-size:11px;color:var(--ink2);overflow-wrap:anywhere">No match selected — using auto.</div>
        </div>
        <div class="ebay-picker-actions">
          <div id="ebayModeToggle" style="display:inline-flex;background:rgba(255,255,255,.06);border:1px solid rgba(255,255,255,.12);border-radius:var(--radius-md);overflow:hidden;height:32px">
            <button type="button" data-mode="sold" onclick="setEbayMode('sold')"
              style="padding:0 10px;background:var(--acid);color:var(--void);border:none;font-family:var(--font-mono);font-size:9px;font-weight:700;letter-spacing:.10em;text-transform:uppercase;cursor:pointer;transition:background .15s">Sold</button>
            <button type="button" data-mode="live" onclick="setEbayMode('live')"
              style="padding:0 10px;background:transparent;color:var(--ink2);border:none;font-family:var(--font-mono);font-size:9px;letter-spacing:.10em;text-transform:uppercase;cursor:pointer;transition:background .15s">Live</button>
          </div>
          <button id="ebayPickerBtn" class="ebay-picker-btn" onclick="loadEbayCandidates()"
            style="height:32px;padding:0 12px;background:rgba(255,255,255,.08);color:var(--ink);border:1px solid rgba(255,255,255,.12);border-radius:var(--radius-md);font-family:var(--font-mono);font-size:9px;letter-spacing:.10em;text-transform:uppercase;cursor:pointer;display:flex;align-items:center;gap:6px;flex-shrink:0">
            <svg viewBox="0 0 24 24" width="11" height="11" fill="none" stroke="currentColor" stroke-width="1.8" style="display:block"><circle cx="11" cy="11" r="7"/><path d="M21 21l-4.35-4.35"/></svg>
            Find on eBay
          </button>
        </div>
      </div>
      <div id="ebayCandidates" style="display:none;margin-top:8px"></div>
    </div>

    <div style="padding:20px;display:flex;flex-direction:column;gap:14px" id="editFields"></div>
    <div style="padding:0 20px 20px;display:flex;gap:8px">
      <button onclick="saveEdit()" style="flex:1;height:40px;background:var(--acid);color:var(--void);border:none;border-radius:var(--radius-md);font-family:var(--font-mono);font-size:9px;font-weight:700;letter-spacing:.12em;text-transform:uppercase;cursor:pointer">Save Changes</button>
      <button onclick="closeEdit()" style="height:40px;padding:0 16px;background:rgba(255,255,255,.08);color:var(--ink2);border:1px solid rgba(255,255,255,.12);border-radius:var(--radius-md);font-family:var(--font-mono);font-size:9px;letter-spacing:.10em;text-transform:uppercase;cursor:pointer">Cancel</button>
    </div>
  </div>
</div>

<div id="toast"></div>

<script>
let allItems=[],priceData={},imageCache={},currentTab='all',currentView='grid',currentModalId=null,editItemId=null,pendingChosenImage=null,ebayMode='sold',toastT;

function buildToolbarTabs(){
  const CATS={
    all:   {label:'All',   icon:'<svg viewBox="0 0 24 24" width="11" height="11" fill="none" stroke="currentColor" stroke-width="1.5"><rect x="3" y="3" width="7" height="7" rx="1"/><rect x="14" y="3" width="7" height="7" rx="1"/><rect x="3" y="14" width="7" height="7" rx="1"/><rect x="14" y="14" width="7" height="7" rx="1"/></svg>'},
    cards: {label:'Cards', icon:'<svg viewBox="0 0 24 24" width="11" height="11" fill="none" stroke="currentColor" stroke-width="1.5"><rect x="2" y="5" width="20" height="14" rx="2"/><path d="M2 10h20"/></svg>'},
    shirts:{label:'Shirts',icon:'<svg viewBox="0 0 24 24" width="11" height="11" fill="none" stroke="currentColor" stroke-width="1.5"><path d="M20.38 3.46L16 2a4 4 0 0 1-8 0L3.62 3.46a2 2 0 0 0-1.34 2.23l.58 3.57a1 1 0 0 0 .99.84H6v10c0 1.1.9 2 2 2h8a2 2 0 0 0 2-2V10h2.15a1 1 0 0 0 .99-.84l.58-3.57a2 2 0 0 0-1.34-2.23z"/></svg>'},
    games: {label:'Games', icon:'<svg viewBox="0 0 24 24" width="11" height="11" fill="none" stroke="currentColor" stroke-width="1.5"><rect x="2" y="6" width="20" height="12" rx="2"/><path d="M12 12h.01M7 12h.01M17 12h.01"/></svg>'},
    vinyl: {label:'Vinyl', icon:'<svg viewBox="0 0 24 24" width="11" height="11" fill="none" stroke="currentColor" stroke-width="1.5"><circle cx="12" cy="12" r="10"/><circle cx="12" cy="12" r="3"/></svg>'},
    other: {label:'Other', icon:'<svg viewBox="0 0 24 24" width="11" height="11" fill="none" stroke="currentColor" stroke-width="1.5"><circle cx="12" cy="12" r="10"/><line x1="12" y1="8" x2="12" y2="12"/><line x1="12" y1="16" x2="12.01" y2="16"/></svg>'}
  };
  const bar=document.getElementById('toolbar');
  const gap=bar.querySelector('.toolbar-gap');
  Object.entries(CATS).forEach(([k,v])=>{
    const btn=document.createElement('button');
    btn.className='cat-tab'+(k==='all'?' active':'');
    btn.dataset.cat=k;
    btn.innerHTML=`${v.icon} ${v.label} <span class="cat-count" id="cnt_${k}">0</span>`;
    btn.onclick=()=>setTab(k);
    bar.insertBefore(btn,gap);
  });
}
function setTab(t){currentTab=t;document.querySelectorAll('.cat-tab').forEach(b=>b.classList.toggle('active',b.dataset.cat===t));filterItems();setTimeout(loadImagesForVisible,100);}

document.addEventListener('DOMContentLoaded',()=>{buildToolbarTabs();loadAll();});

async function loadAll(){
  try{
    const[cr,pr]=await Promise.all([
      fetch('/api.php?action=collection&category=all',{credentials:'same-origin'}),
      fetch('/api.php?action=getPrices',{credentials:'same-origin'})
    ]);
    const cd=await cr.json(); const pd=await pr.json();
    allItems=cd.ok?(cd.items||[]): []; priceData=pd.ok?(pd.prices||{}):{};
    updateCounts(); filterItems(); loadStats(); autoRefreshPrices(); setTimeout(loadImagesForVisible,300);
  }catch(e){document.getElementById('itemsGrid').innerHTML='<div class="empty-state"><p>Failed to load. Try refreshing.</p></div>';}
}

async function loadStats(){
  try{
    const r=await fetch('/api.php?action=stats',{credentials:'same-origin'}); const d=await r.json(); if(!d.ok)return;
    const s=d.stats;
    document.getElementById('sTotal').textContent=s.total||0;
    document.getElementById('sValue').textContent=s.value?'£'+parseFloat(s.value).toFixed(0):'—';
    document.getElementById('sInvested').textContent=s.invested?'£'+parseFloat(s.invested).toFixed(0):'—';
    const gain=(s.value||0)-(s.invested||0);
    const gEl=document.getElementById('sGain');
    gEl.textContent=(gain>=0?'+':'')+'£'+Math.abs(gain).toFixed(0);
    gEl.className='stat-value '+(gain>0?'is-gain':gain<0?'is-loss':'');
  }catch(e){}
}

function updateCounts(){const counts={all:allItems.length};allItems.forEach(i=>{counts[i.category]=(counts[i.category]||0)+1;});Object.entries(counts).forEach(([k,v])=>{const el=document.getElementById('cnt_'+k);if(el)el.textContent=v;});}

function filterItems(){
  const q=document.getElementById('searchInput').value.toLowerCase().trim();
  const sort=document.getElementById('sortSelect').value;
  let items=allItems.filter(i=>{
    if(currentTab!=='all'&&i.category!==currentTab)return false;
    if(q&&![i.name,i.subtitle,i.series,i.item_type,i.year].join(' ').toLowerCase().includes(q))return false;
    return true;
  });
  items.sort((a,b)=>{
    if(sort==='name')return(a.name||'').localeCompare(b.name||'');
    if(sort==='oldest')return(a.created||'').localeCompare(b.created||'');
    if(sort==='value_desc'||sort==='value_asc'){const av=priceData[a.id]?.avg_10||0,bv=priceData[b.id]?.avg_10||0;return sort==='value_desc'?bv-av:av-bv;}
    return(b.created||'').localeCompare(a.created||'');
  });
  renderItems(items);
}

function setView(v){currentView=v;document.getElementById('vGrid').classList.toggle('active',v==='grid');document.getElementById('vList').classList.toggle('active',v==='list');const g=document.getElementById('itemsGrid');g.className=v==='grid'?'items-grid':'items-list';filterItems();}

function renderItems(items){
  const g=document.getElementById('itemsGrid');
  if(!items.length){g.innerHTML='<div class="empty-state"><svg viewBox="0 0 24 24"><rect x="3" y="3" width="18" height="18" rx="2"/><circle cx="8.5" cy="8.5" r="1.5"/><polyline points="21,15 16,10 5,21"/></svg><h3>No items found</h3><p>Try a different filter or scan something new</p></div>';return;}
  g.innerHTML=items.map((item,idx)=>currentView==='grid'?renderGrid(item,idx):renderList(item)).join('');
  loadImagesForVisible();
}

function esc(s){return String(s||'').replace(/&/g,'&amp;').replace(/</g,'&lt;').replace(/>/g,'&gt;').replace(/"/g,'&quot;');}

function renderGrid(item,idx){
  const p=priceData[item.id]; const price=p?.avg_10?'£'+parseFloat(p.avg_10).toFixed(2):'—';
  const badge=p?.change_pct?`<span class="ic-change ${p.direction||'flat'}">${p.direction==='up'?'▲':p.direction==='down'?'▼':'—'}${Math.abs(p.change_pct).toFixed(0)}%</span>`:'';
  const cachedUrl=imageCache[item.id];
  const imgHtml=cachedUrl
    ?`<img class="ic-image" id="img-${esc(item.id)}" src="${esc(cachedUrl)}" alt="${esc(item.name||'')}" loading="lazy" decoding="async" onerror="this.onerror=null;this.outerHTML='<div class=\\"ic-image-placeholder\\" id=\\"img-${esc(item.id)}\\"><svg viewBox=\\"0 0 24 24\\"><rect x=\\"3\\" y=\\"3\\" width=\\"18\\" height=\\"18\\" rx=\\"2\\"/><circle cx=\\"8.5\\" cy=\\"8.5\\" r=\\"1.5\\"/><polyline points=\\"21,15 16,10 5,21\\"/></svg></div>'">`
    :`<div class="ic-image-placeholder" id="img-${esc(item.id)}"><svg viewBox="0 0 24 24"><rect x="3" y="3" width="18" height="18" rx="2"/><circle cx="8.5" cy="8.5" r="1.5"/><polyline points="21,15 16,10 5,21"/></svg></div>`;
  return`<div class="item-card" id="card-${esc(item.id)}" onclick="openModal('${esc(item.id)}')">
    <div class="ic-index">${String(idx+1).padStart(2,'0')}</div>
    <div class="ic-image-wrap">
      ${imgHtml}
      <div class="ic-overlay"></div>
      <div class="ic-foot">
        <div class="ic-cat">${esc(item.category||'')}</div>
        <div class="ic-name">${esc(item.name)}</div>
        <div class="ic-price-row"><div class="ic-price">${price}</div>${item.item_type?`<span class="ic-badge">${esc(item.item_type)}</span>`:badge}</div>
      </div>
    </div>
  </div>`;
}

function renderList(item){
  const p=priceData[item.id]; const price=p?.avg_10?'£'+parseFloat(p.avg_10).toFixed(2):'—';
  const cachedUrl=imageCache[item.id];
  const thumbHtml=cachedUrl
    ?`<div class="ir-thumb" id="limg-${esc(item.id)}"><img src="${esc(cachedUrl)}" alt="${esc(item.name||'')}" loading="lazy" decoding="async" style="width:100%;height:100%;object-fit:cover"></div>`
    :`<div class="ir-thumb" id="limg-${esc(item.id)}"><svg viewBox="0 0 24 24" style="width:20px;height:20px;stroke:var(--ink3);fill:none;stroke-width:1.2"><rect x="3" y="3" width="18" height="18" rx="2"/><circle cx="8.5" cy="8.5" r="1.5"/></svg></div>`;
  return`<div class="item-row" onclick="openModal('${esc(item.id)}')">
    ${thumbHtml}
    <div class="ir-info"><div class="ir-name">${esc(item.name)}</div><div class="ir-sub">${esc([item.subtitle,item.series,item.year].filter(Boolean).join(' · '))}</div></div>
    ${item.item_type?`<span class="ic-badge">${esc(item.item_type)}</span>`:''}
    <div class="ir-price">${price}</div>
  </div>`;
}

function buildQuery(item){return[item.name,item.subtitle,item.series,item.year].filter(Boolean).join(' ').replace(/['"]/g,'');}
function searchQuery(item){return(item.ebay_query&&item.ebay_query.trim())?item.ebay_query.trim():buildQuery(item);}

async function loadImagesForVisible(){
  const visible=allItems.filter(i=>!imageCache[i.id]&&(document.getElementById('img-'+i.id)||document.getElementById('limg-'+i.id)));
  for(const item of visible.slice(0,20)){
    if(item.thumbnail){const proxied='api.php?action=imgProxy&url='+encodeURIComponent(item.thumbnail);imageCache[item.id]=proxied;setImgEl(item.id,proxied,item.name);continue;}
    loadImg(item.id,searchQuery(item),item.category,item.name);
  }
}

async function loadImg(id,query,cat,fallback){
  try{
    const resp=await fetch('/api.php?'+new URLSearchParams({action:'getImage',id,query,cat}),{credentials:'same-origin'});
    const d=await resp.json();
    if(d.url){const proxied='api.php?action=imgProxy&url='+encodeURIComponent(d.url);imageCache[id]=proxied;setImgEl(id,proxied,fallback);}
  }catch(e){}
}

function setImgEl(id,src,alt){
  const gEl=document.getElementById('img-'+id);
  if(gEl&&gEl.tagName!=='IMG'){const img=document.createElement('img');img.className='ic-image';img.id='img-'+id;img.src=src;img.alt=alt||'';img.loading='lazy';img.decoding='async';img.onerror=()=>{};gEl.replaceWith(img);}
  const lEl=document.getElementById('limg-'+id);
  if(lEl&&!lEl.querySelector('img')){lEl.innerHTML=`<img src="${src}" alt="${alt||''}" loading="lazy" decoding="async" style="width:100%;height:100%;object-fit:cover">`;}
}

async function autoRefreshPrices(){
  document.getElementById('priceStatus').textContent='Updating eBay prices…';
  const toRefresh=allItems.slice(0,30); let done=0;
  for(const item of toRefresh){
    try{
      const q=searchQuery(item); if(!q){done++;continue;}
      const fd=new FormData(); fd.append('action','refreshPrices'); fd.append('item_id',item.id); fd.append('query',q); fd.append('category',item.category||'');
      const resp=await fetch('/api.php',{method:'POST',body:fd,credentials:'same-origin'}); const d=await resp.json();
      if(d.ok)priceData[item.id]=d.price; done++;
      document.getElementById('priceStatus').textContent=`Updating prices… ${done}/${toRefresh.length}`;
    }catch(e){done++;}
  }
  filterItems(); loadStats(); document.getElementById('priceStatus').textContent=`Prices updated — ${new Date().toLocaleTimeString()}`;
}
async function refreshAllPrices(){autoRefreshPrices();}

async function refreshSinglePrice(id){
  if(!id)return; const item=allItems.find(i=>i.id===id); if(!item)return; showToast('Refreshing price…');
  try{
    const fd=new FormData(); fd.append('action','refreshPrices'); fd.append('item_id',id); fd.append('query',searchQuery(item)); fd.append('category',item.category||'');
    const resp=await fetch('/api.php',{method:'POST',body:fd,credentials:'same-origin'}); const d=await resp.json();
    if(d.ok){priceData[id]=d.price;filterItems();openModal(id);showToast('Price updated');}
  }catch(e){showToast('Price refresh failed');}
}

function openModal(id){
  currentModalId=id; const item=allItems.find(i=>i.id===id); if(!item)return;
  document.getElementById('modalCat').textContent=item.category||'';
  document.getElementById('modalTitle').textContent=item.name||'—';
  document.getElementById('modalSub').textContent=[item.subtitle,item.series,item.year,item.condition].filter(Boolean).join(' · ');
  const p=priceData[id];
  document.getElementById('mAvg10').textContent=p?.avg_10?'£'+parseFloat(p.avg_10).toFixed(2):'—';
  document.getElementById('mAvg30').textContent=p?.avg_30?'£'+parseFloat(p.avg_30).toFixed(2):'—';
  document.getElementById('mPaid').textContent=item.price_paid?'£'+parseFloat(item.price_paid).toFixed(2):'—';
  const fields=['item_type','condition','manufacturer','year','series','card_number','parallel','numbered','autograph','platform','genre','region','artist','label','format','pressing','kit_type','size','signed','notes'].filter(k=>item[k]);
  document.getElementById('modalFields').innerHTML=fields.map(k=>`<div><div class="modal-field-label">${k.replace(/_/g,' ')}</div><div class="modal-field-val">${esc(item[k])}</div></div>`).join('');
  document.getElementById('modalBg').classList.add('open');
  const cachedSrc=imageCache[id];
  if(cachedSrc){document.getElementById('modalImg').src=cachedSrc;}
  else{
    const existingImg=document.getElementById('img-'+id);
    if(existingImg&&existingImg.tagName==='IMG'&&existingImg.src){document.getElementById('modalImg').src=existingImg.src;imageCache[id]=existingImg.src;}
    else{
      document.getElementById('modalImg').src='';
      fetch('/api.php?'+new URLSearchParams({action:'getImage',id,query:searchQuery(item),cat:item.category}),{credentials:'same-origin'})
        .then(r=>r.json()).then(d=>{if(d.url){imageCache[id]=d.url;document.getElementById('modalImg').src=d.url;}}).catch(()=>{});
    }
  }
}
function closeModal(){document.getElementById('modalBg').classList.remove('open');currentModalId=null;}
document.addEventListener('keydown',e=>{if(e.key==='Escape'){closeModal();closeConfirm();}});

function deleteItem(id){
  if(!id)return;
  const item=allItems.find(i=>i.id===id);
  document.getElementById('confirmBody').textContent='Remove "'+(item?item.name:'this item')+'" from your vault? This cannot be undone.';
  document.getElementById('confirmDeleteBtn').onclick=()=>confirmDelete(id);
  document.getElementById('confirmBg').classList.add('open');
}
function closeConfirm(){document.getElementById('confirmBg').classList.remove('open');}
async function confirmDelete(id){
  closeConfirm();
  try{
    const item=allItems.find(i=>i.id===id);
    const fd=new FormData(); fd.append('action','delete'); fd.append('id',id);
    if(item&&item.category)fd.append('category',item.category);
    const resp=await fetch('/api.php',{method:'POST',body:fd,credentials:'same-origin'}); const d=await resp.json();
    if(d.ok){allItems=allItems.filter(i=>i.id!==id);delete priceData[id];delete imageCache[id];closeModal();updateCounts();filterItems();loadStats();showToast('Item deleted');}
    else{showToast('Delete failed: '+(d.error||'unknown error'));}
  }catch(e){showToast('Delete failed');}
}

/* ── eBay picker ───────────────────────────────────────────────────────────── */
function setEbayMode(mode){
  if(mode!=='sold'&&mode!=='live')return; ebayMode=mode;
  const toggle=document.getElementById('ebayModeToggle');
  if(toggle)toggle.querySelectorAll('button').forEach(b=>{const active=b.dataset.mode===mode;b.style.background=active?'var(--acid)':'transparent';b.style.color=active?'var(--void)':'var(--ink2)';b.style.fontWeight=active?'700':'normal';});
  const cands=document.getElementById('ebayCandidates');
  if(cands&&cands.style.display!=='none'&&cands.innerHTML.trim()!=='')loadEbayCandidates();
}

async function loadEbayCandidates(){
  const item=allItems.find(i=>i.id===editItemId); if(!item)return;
  const queryInput=document.getElementById('ef_ebay_query');
  const query=(queryInput&&queryInput.value.trim())||buildQuery(item);
  const btn=document.getElementById('ebayPickerBtn'); const cands=document.getElementById('ebayCandidates'); const status=document.getElementById('ebayPickerStatus');
  if(btn){btn.disabled=true;btn.style.opacity='.6';}
  if(status)status.textContent=`Searching ${ebayMode} listings for "${query}"…`;
  if(cands){cands.style.display='block';cands.innerHTML='<div style="font-family:var(--font-mono);font-size:9px;color:var(--ink3);letter-spacing:.10em;padding:14px 0;text-align:center">Loading candidates…</div>';}
  try{
    const r=await fetch('api.php?action=searchEbayCandidates&limit=6&mode='+ebayMode+'&query='+encodeURIComponent(query),{credentials:'same-origin'});
    const d=await r.json();
    if(d.blocked){cands.innerHTML='<div style="font-family:var(--font-mono);font-size:9px;color:var(--ink3);letter-spacing:.06em;padding:14px 8px;text-align:center;line-height:1.5">eBay temporarily rate-limited the search.<br>Try again in 5–10 minutes.</div>';if(status)status.textContent='eBay rate-limited — try again shortly.';return;}
    if(!d.ok||!d.candidates||!d.candidates.length){const tryOther=ebayMode==='sold'?' Try the Live toggle.':' Try the Sold toggle.';cands.innerHTML=`<div style="font-family:var(--font-mono);font-size:9px;color:var(--ink3);letter-spacing:.06em;padding:14px 0;text-align:center">No ${ebayMode} candidates found.${tryOther}</div>`;if(status)status.textContent=`No ${ebayMode} matches.`;return;}
    renderEbayCandidates(d.candidates);
    if(status)status.textContent=`${d.candidates.length} ${ebayMode} matches — click one to lock in.`;
  }catch(e){cands.innerHTML=`<div style="font-family:var(--font-mono);font-size:9px;color:var(--red);padding:14px 0;text-align:center">Search failed: ${esc(e.message||e)}</div>`;if(status)status.textContent='Search failed.';}
  finally{if(btn){btn.disabled=false;btn.style.opacity='1';}}
}

function renderEbayCandidates(list){
  const cands=document.getElementById('ebayCandidates'); if(!cands)return;
  cands.innerHTML=`<div class="ebay-cand-grid">`+list.map((c,i)=>{
    const proxied='api.php?action=imgProxy&url='+encodeURIComponent(c.image);
    return`<div onclick="pickEbayCandidate(${i})" data-cand-idx="${i}"
      style="cursor:pointer;background:rgba(255,255,255,.06);border:1px solid rgba(255,255,255,.10);border-radius:var(--radius-md);overflow:hidden;display:flex;flex-direction:column;transition:border-color .15s,transform .1s"
      onmouseover="this.style.borderColor='rgba(206,255,46,.40)'" onmouseout="this.style.borderColor=''"
      onmousedown="this.style.transform='scale(.98)'" onmouseup="this.style.transform=''">
      <div style="position:relative;width:100%;aspect-ratio:1/1;background:rgba(0,0,0,.18);overflow:hidden">
        <img src="${proxied}" alt="" loading="lazy" style="width:100%;height:100%;object-fit:cover;opacity:0;transition:opacity .2s" onload="this.style.opacity='1'" onerror="this.style.opacity='0'">
      </div>
      <div style="padding:6px 8px 8px;display:flex;flex-direction:column;gap:2px;min-height:54px">
        <div style="font-family:var(--font-sans);font-size:10px;line-height:1.25;color:var(--ink);display:-webkit-box;-webkit-line-clamp:2;-webkit-box-orient:vertical;overflow:hidden">${esc(c.title)}</div>
        ${c.price?`<div style="font-family:var(--font-mono);font-size:9px;color:var(--acid);letter-spacing:.04em;margin-top:auto">${esc(c.price)}</div>`:''}
      </div>
    </div>`;
  }).join('')+`</div>`;
  cands._candidates=list;
}

function pickEbayCandidate(idx){
  const cands=document.getElementById('ebayCandidates'); if(!cands||!cands._candidates)return;
  const chosen=cands._candidates[idx]; if(!chosen)return;
  pendingChosenImage=chosen.image;
  const queryInput=document.getElementById('ef_ebay_query');
  if(queryInput){const words=(chosen.title||'').replace(/\s+/g,' ').trim().split(' ').slice(0,10).join(' ');queryInput.value=words;}
  const status=document.getElementById('ebayPickerStatus');
  if(status){const proxied='api.php?action=imgProxy&url='+encodeURIComponent(chosen.image);status.innerHTML=`<span style="display:inline-flex;align-items:center;gap:8px"><img src="${proxied}" alt="" style="width:32px;height:32px;border-radius:6px;object-fit:cover;border:1px solid rgba(255,255,255,.12)"><span style="color:var(--acid)">Match selected — save to lock</span></span>`;}
  cands.style.display='none'; cands.innerHTML='';
}

function openEdit(id){
  editItemId=id; const item=allItems.find(i=>i.id===id); if(!item)return;
  closeModal();
  pendingChosenImage=null; setEbayMode('sold');
  const status=document.getElementById('ebayPickerStatus'); const cands=document.getElementById('ebayCandidates');
  if(cands){cands.style.display='none';cands.innerHTML='';}
  if(status){
    if(item.thumbnail){const proxied='api.php?action=imgProxy&url='+encodeURIComponent(item.thumbnail);status.innerHTML=`<span style="display:inline-flex;align-items:center;gap:8px"><img src="${proxied}" alt="" style="width:32px;height:32px;border-radius:6px;object-fit:cover;border:1px solid rgba(255,255,255,.12)"><span style="color:var(--acid)">Match locked</span></span>`;}
    else{status.textContent='No match selected — using auto.';}
  }
  const btn=document.getElementById('ebayPickerBtn'); if(btn){btn.disabled=false;btn.style.opacity='1';}
  const editableKeys=['name','subtitle','series','year','item_type','condition','manufacturer','card_number','parallel','numbered','autograph','platform','genre','region','artist','label','format','pressing','kit_type','size','signed','price_paid','ebay_query','notes'];
  const labelMap={name:'Name',subtitle:'Subtitle / Set',series:'Series',year:'Year',item_type:'Type',condition:'Condition',manufacturer:'Manufacturer',card_number:'Card Number',platform:'Platform',genre:'Genre',region:'Region',artist:'Artist',label:'Label',format:'Format',pressing:'Pressing',kit_type:'Kit Type',size:'Size',signed:'Signed',price_paid:'Paid (£)',ebay_query:'eBay Search Query',notes:'Notes',numbered:'Numbered',autograph:'Autograph',parallel:'Parallel'};
  const fields=document.getElementById('editFields');
  fields.innerHTML=editableKeys.map(k=>{
    const val=(k==='ebay_query'&&!item.ebay_query)?buildQuery(item):(item[k]||'');
    const hint=k==='ebay_query'?'<div style="font-family:var(--font-mono);font-size:8px;color:var(--ink3);margin-top:3px;letter-spacing:.04em">Edit to override the auto query, or pick a candidate above to set automatically.</div>':'';
    const isTextarea=k==='notes'||k==='ebay_query';
    return`<div>
      <label style="font-family:var(--font-mono);font-size:8px;letter-spacing:.14em;text-transform:uppercase;color:var(--ink3);display:block;margin-bottom:4px">${labelMap[k]||k}</label>
      ${isTextarea
        ?`<textarea id="ef_${k}" rows="${k==='notes'?3:2}" style="width:100%;padding:8px 12px;background:rgba(255,255,255,.08);border:1px solid rgba(255,255,255,.12);border-radius:var(--radius-md);font-family:var(--font-sans);font-size:13px;color:var(--ink);outline:none;transition:border-color .15s;resize:vertical" onfocus="this.style.borderColor='rgba(206,255,46,.35)'" onblur="this.style.borderColor=''">${esc(val)}</textarea>`
        :`<input id="ef_${k}" type="${k==='price_paid'?'number':'text'}" value="${esc(val)}" style="width:100%;height:36px;padding:0 12px;background:rgba(255,255,255,.08);border:1px solid rgba(255,255,255,.12);border-radius:var(--radius-md);font-family:var(--font-sans);font-size:13px;color:var(--ink);outline:none;transition:border-color .15s" onfocus="this.style.borderColor='rgba(206,255,46,.35)'" onblur="this.style.borderColor=''">`}
      ${hint}
    </div>`;
  }).join('');
  document.getElementById('editBg').style.display='flex';
}
function closeEdit(){document.getElementById('editBg').style.display='none';editItemId=null;}

async function saveEdit(){
  if(!editItemId)return; const item=allItems.find(i=>i.id===editItemId); if(!item)return;
  const savedId=editItemId;
  const editableKeys=['name','subtitle','series','year','item_type','condition','manufacturer','card_number','parallel','numbered','autograph','platform','genre','region','artist','label','format','pressing','kit_type','size','signed','price_paid','ebay_query','notes'];
  const updates={};
  editableKeys.forEach(k=>{const el=document.getElementById('ef_'+k);if(!el)return;const newVal=el.value,oldVal=(item[k]==null?'':String(item[k]));if(newVal===''&&oldVal!=='')return;if(newVal!==oldVal)updates[k]=newVal;});
  if(pendingChosenImage&&pendingChosenImage!==item.thumbnail)updates.thumbnail=pendingChosenImage;
  if(Object.keys(updates).length===0){closeEdit();openModal(savedId);return;}
  try{
    const fd=new FormData(); fd.append('action','update'); fd.append('item_id',savedId); fd.append('updates',JSON.stringify(updates));
    const resp=await fetch('/api.php',{method:'POST',body:fd,credentials:'same-origin'}); const d=await resp.json();
    if(d.ok){
      const oldSearch=searchQuery(item); Object.assign(item,updates); const newSearch=searchQuery(item);
      closeEdit(); filterItems(); showToast('Item updated'); openModal(savedId);
      if(oldSearch!==newSearch){delete imageCache[savedId];refreshItemImage(savedId,newSearch,item.category,item.name);refreshSinglePrice(savedId);}
    }else{showToast(d.error||'Update failed');}
  }catch(e){showToast('Update failed');}
}

async function refreshItemImage(id,query,cat,alt){
  if(!id||!query)return;
  try{
    const resp=await fetch('/api.php?'+new URLSearchParams({action:'getImage',id,query,cat:cat||'',refresh:'1'}),{credentials:'same-origin'});
    const d=await resp.json();
    if(d.url){imageCache[id]=d.url;if(currentModalId===id)document.getElementById('modalImg').src=d.url;filterItems();}
  }catch(e){}
}

function showToast(msg){const el=document.getElementById('toast');el.textContent=msg;el.classList.add('show');clearTimeout(toastT);toastT=setTimeout(()=>el.classList.remove('show'),2800);}
</script>
</body>
</html>
