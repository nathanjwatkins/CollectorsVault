<?php
ob_start();
header('Cache-Control: no-cache, must-revalidate, max-age=0');
header('Pragma: no-cache');
ini_set('session.cookie_samesite', 'Lax');
ini_set('session.cookie_secure', '1');
ini_set('session.cookie_httponly', '1');
session_start();
if (!isset($_SESSION['user'])) { header('Location: index.php'); exit; }
$username = htmlspecialchars($_SESSION['user']);
?>
<!DOCTYPE html>
<html lang="en" data-theme="dark">
<head>
<meta charset="UTF-8"/>
<meta name="theme-color" content="#050507">
<meta name="viewport" content="width=device-width, initial-scale=1.0, viewport-fit=cover"/>
<title>CollectorVault — Collection</title>
<link rel="preconnect" href="https://fonts.googleapis.com">
<link href="https://fonts.googleapis.com/css2?family=Outfit:wght@200;300;400;500;600;700;800;900&family=JetBrains+Mono:wght@300;400;500;700&display=swap" rel="stylesheet">
<?php include 'theme.php'; ?>
<link rel="stylesheet" href="shared.css?v=1777581959">
<style>
/* COLLECTION PAGE */
.stats-zone{display:grid;grid-template-columns:1fr 1fr;border-bottom:1px solid var(--border)}
@media(min-width:640px){.stats-zone{grid-template-columns:repeat(4,1fr)}}
.stat-block{padding:24px 20px 18px;border-right:1px solid var(--border);position:relative;overflow:hidden}
.stat-block:last-child{border-right:none}
.stat-block:nth-child(2){border-right:none}
@media(min-width:640px){.stat-block:nth-child(2){border-right:1px solid var(--border)}.stat-block:last-child{border-right:none}}
.stat-block::before{content:'';position:absolute;top:0;left:0;right:0;height:1px;background:linear-gradient(90deg,var(--acid) 0%,transparent 60%);opacity:.25}
.stat-label{font-family:var(--mono);font-size:7px;letter-spacing:.20em;text-transform:uppercase;color:var(--ink3);margin-bottom:10px}
.stat-value{font-family:var(--font);font-size:clamp(26px,4vw,48px);font-weight:200;letter-spacing:-.04em;color:var(--ink);line-height:1}
.stat-value.is-gain{color:var(--acid);text-shadow:0 0 40px rgba(200,255,0,.18)}
.stat-value.is-loss{color:var(--red)}

.coll-toolbar{display:flex;align-items:center;gap:8px;padding:10px 20px;border-bottom:1px solid var(--border);overflow-x:auto;scrollbar-width:none;background:var(--surface);position:sticky;top:56px;z-index:100;flex-shrink:0}
.coll-toolbar::-webkit-scrollbar{display:none}
@media(min-width:900px){.coll-toolbar{top:0}}

.cat-tab{display:flex;align-items:center;gap:5px;padding:5px 12px;border-radius:20px;font-family:var(--mono);font-size:8px;letter-spacing:.08em;text-transform:uppercase;color:var(--ink3);background:var(--surface2);border:1px solid var(--border);cursor:pointer;transition:all .15s;white-space:nowrap;flex-shrink:0}
.cat-tab svg{width:11px;height:11px;stroke:currentColor;fill:none;stroke-width:1.5}
.cat-tab:hover{color:var(--ink);border-color:var(--border2)}
.cat-tab.active{background:var(--acid-dim);border-color:rgba(200,255,0,.25);color:var(--acid)}
.cat-count{font-size:7px;opacity:.60}

.toolbar-gap{flex:1;min-width:8px}
.search-field{position:relative;flex-shrink:0;width:160px}
@media(min-width:640px){.search-field{width:200px}}
.search-field svg{position:absolute;left:10px;top:50%;transform:translateY(-50%);width:12px;height:12px;stroke:var(--ink3);fill:none;stroke-width:1.5;pointer-events:none}
.search-field input{width:100%;height:30px;padding:0 10px 0 30px;background:var(--surface2);border:1px solid var(--border);border-radius:20px;font-family:var(--mono);font-size:10px;color:var(--ink);outline:none;transition:border-color .15s;-webkit-appearance:none}
.search-field input::placeholder{color:var(--ink3)}
.search-field input:focus{border-color:rgba(200,255,0,.30)}
.sort-btn{height:30px;padding:0 12px;background:var(--surface2);border:1px solid var(--border);border-radius:var(--radius-md);font-family:var(--mono);font-size:9px;letter-spacing:.06em;color:var(--ink2);cursor:pointer;outline:none;-webkit-appearance:none;flex-shrink:0}
.view-toggle{display:flex;border:1px solid var(--border);border-radius:var(--radius-md);overflow:hidden;flex-shrink:0}
.view-btn{width:30px;height:30px;display:flex;align-items:center;justify-content:center;background:var(--surface2);border:none;color:var(--ink3);cursor:pointer;transition:all .15s;border-right:1px solid var(--border)}
.view-btn:last-child{border-right:none}
.view-btn svg{width:13px;height:13px;stroke:currentColor;fill:none;stroke-width:1.5}
.view-btn.active{background:var(--acid-dim);color:var(--acid)}

.price-bar{display:flex;align-items:center;justify-content:space-between;padding:6px 20px;border-bottom:1px solid var(--border);font-family:var(--mono);font-size:8px;letter-spacing:.08em;color:var(--ink3);text-transform:uppercase;flex-shrink:0}

.coll-body{padding:16px 20px}
.items-grid{display:grid;grid-template-columns:repeat(2,1fr);gap:10px}
@media(min-width:480px){.items-grid{grid-template-columns:repeat(3,1fr)}}
@media(min-width:700px){.items-grid{grid-template-columns:repeat(4,1fr)}}
@media(min-width:1000px){.items-grid{grid-template-columns:repeat(5,1fr)}}
@media(min-width:1300px){.items-grid{grid-template-columns:repeat(6,1fr)}}
.items-list{display:flex;flex-direction:column;gap:1px}

.item-card{background:var(--surface);border:1px solid var(--border);border-radius:var(--radius-md);overflow:hidden;cursor:pointer;transition:border-color .2s,transform .2s;position:relative;-webkit-tap-highlight-color:transparent}
.item-card:hover{border-color:rgba(200,255,0,.25);transform:translateY(-2px)}
.ic-index{position:absolute;top:8px;left:9px;font-family:var(--mono);font-size:8px;letter-spacing:.10em;color:var(--acid);opacity:.55;z-index:5;pointer-events:none;text-shadow:0 1px 4px rgba(0,0,0,.60)}
.ic-image-wrap{position:relative;width:100%;aspect-ratio:3/4;background:var(--surface2);overflow:hidden}
.ic-image{width:100%;height:100%;object-fit:cover;display:block;transition:transform .3s ease}
.item-card:hover .ic-image{transform:scale(1.04)}
.ic-image-placeholder{width:100%;height:100%;display:flex;align-items:center;justify-content:center}
.ic-image-placeholder svg{width:28px;height:28px;stroke:var(--ink4);fill:none;stroke-width:1.2}
.ic-overlay{position:absolute;inset:0;background:linear-gradient(to top,rgba(5,5,7,.90) 0%,rgba(5,5,7,.20) 45%,transparent 70%)}
.ic-foot{position:absolute;bottom:0;left:0;right:0;padding:10px 10px 8px;z-index:2}
.ic-cat{font-family:var(--mono);font-size:7px;letter-spacing:.10em;text-transform:uppercase;color:var(--acid);opacity:.70;margin-bottom:3px}
.ic-name{font-family:var(--font);font-size:12px;font-weight:600;color:var(--ink);letter-spacing:-.01em;line-height:1.2;white-space:nowrap;overflow:hidden;text-overflow:ellipsis}
.ic-price-row{display:flex;align-items:center;justify-content:space-between;margin-top:5px}
.ic-price{font-family:var(--mono);font-size:12px;font-weight:700;color:var(--ink)}
.ic-badge{font-family:var(--mono);font-size:7px;letter-spacing:.06em;padding:1px 5px;border-radius:var(--radius);border:1px solid rgba(200,255,0,.20);background:rgba(200,255,0,.08);color:var(--acid);text-transform:uppercase}
.ic-change{font-family:var(--mono);font-size:8px;letter-spacing:.04em}
.ic-change.up{color:var(--acid)}.ic-change.down{color:var(--red)}.ic-change.flat{color:var(--ink3)}

.item-row{display:flex;align-items:center;gap:12px;padding:10px 12px;background:var(--surface);border:1px solid var(--border);border-radius:var(--radius-md);cursor:pointer;transition:border-color .15s;-webkit-tap-highlight-color:transparent}
.item-row:hover{border-color:rgba(200,255,0,.20)}
.ir-thumb{width:44px;height:44px;border-radius:var(--radius-md);overflow:hidden;background:var(--surface2);flex-shrink:0;display:flex;align-items:center;justify-content:center}
.ir-thumb img{width:100%;height:100%;object-fit:cover}
.ir-info{flex:1;min-width:0}
.ir-name{font-family:var(--font);font-size:13px;font-weight:600;color:var(--ink);letter-spacing:-.01em;white-space:nowrap;overflow:hidden;text-overflow:ellipsis}
.ir-sub{font-family:var(--mono);font-size:9px;color:var(--ink3);margin-top:2px;letter-spacing:.02em;white-space:nowrap;overflow:hidden;text-overflow:ellipsis}
.ir-price{font-family:var(--mono);font-size:13px;font-weight:700;color:var(--ink);flex-shrink:0}

/* Modal */
#modalBg{display:none;position:fixed;inset:0;background:rgba(5,5,7,.85);z-index:500;backdrop-filter:blur(8px);-webkit-backdrop-filter:blur(8px);align-items:flex-end;justify-content:center;padding:0}
@media(min-width:640px){#modalBg{align-items:center;padding:16px}}
#modalBg.open{display:flex}
.modal-sheet{background:var(--surface);border:1px solid var(--border2);border-radius:var(--radius-lg) var(--radius-lg) 0 0;width:100%;max-height:92dvh;overflow-y:auto;position:relative;padding-bottom:env(safe-area-inset-bottom,0px)}
@media(min-width:640px){.modal-sheet{border-radius:var(--radius-lg);max-width:520px;max-height:88dvh}}
.modal-handle{width:36px;height:3px;background:var(--surface3);border-radius:2px;margin:12px auto 0}
@media(min-width:640px){.modal-handle{display:none}}
.modal-hero{position:relative;width:100%;height:240px;overflow:hidden;background:var(--surface2)}
.modal-hero img{width:100%;height:100%;object-fit:cover;display:block}
.modal-hero-grad{position:absolute;inset:0;background:linear-gradient(to top,rgba(12,12,16,.95) 0%,transparent 60%)}
.modal-close{position:absolute;top:12px;right:12px;width:30px;height:30px;border-radius:50%;background:rgba(5,5,7,.70);border:1px solid var(--border2);color:var(--ink2);display:flex;align-items:center;justify-content:center;cursor:pointer;font-size:18px;font-family:var(--font);transition:background .15s;z-index:1}
.modal-close:hover{background:rgba(5,5,7,.90);color:var(--ink)}
.modal-body{padding:20px 20px 28px}
.modal-overline{font-family:var(--mono);font-size:8px;letter-spacing:.16em;text-transform:uppercase;color:var(--acid);margin-bottom:6px}
.modal-title{font-family:var(--font);font-size:22px;font-weight:800;letter-spacing:-.03em;color:var(--ink);line-height:1.1;margin-bottom:4px}
.modal-sub{font-family:var(--mono);font-size:10px;color:var(--ink3);letter-spacing:.04em;margin-bottom:16px}
.modal-prices{display:grid;grid-template-columns:repeat(3,1fr);gap:1px;background:var(--border);border:1px solid var(--border);border-radius:var(--radius-md);overflow:hidden;margin-bottom:16px}
.modal-price-cell{background:var(--surface2);padding:12px 14px}
.modal-price-label{font-family:var(--mono);font-size:7px;letter-spacing:.14em;text-transform:uppercase;color:var(--ink3);margin-bottom:4px}
.modal-price-val{font-family:var(--mono);font-size:16px;font-weight:700;color:var(--ink);letter-spacing:-.01em}
.modal-price-val.highlight{color:var(--acid);text-shadow:var(--acid-glow-sm)}
.modal-fields{display:grid;grid-template-columns:1fr 1fr;gap:10px;margin-bottom:16px}
.modal-field-label{font-family:var(--mono);font-size:7px;letter-spacing:.14em;text-transform:uppercase;color:var(--ink3);margin-bottom:3px}
.modal-field-val{font-family:var(--font);font-size:13px;font-weight:500;color:var(--ink)}
.modal-actions{display:flex;gap:8px}
.modal-btn{flex:1;height:38px;display:flex;align-items:center;justify-content:center;gap:6px;font-family:var(--mono);font-size:9px;letter-spacing:.10em;text-transform:uppercase;border-radius:var(--radius-md);cursor:pointer;transition:all .15s;border:1px solid var(--border);background:var(--surface2);color:var(--ink2)}
.modal-btn:hover{color:var(--ink);border-color:var(--border2)}
.modal-btn svg{width:12px;height:12px;stroke:currentColor;fill:none;stroke-width:1.5}
.modal-btn.danger{color:var(--red);border-color:rgba(255,68,68,.20);background:rgba(255,68,68,.05)}
.modal-btn.danger:hover{background:rgba(255,68,68,.10)}

/* FAB */
.fab{position:fixed;right:20px;bottom:76px;width:48px;height:48px;background:var(--acid);color:var(--void);border-radius:50%;border:none;display:flex;align-items:center;justify-content:center;cursor:pointer;z-index:200;box-shadow:var(--acid-glow);text-decoration:none;transition:box-shadow .2s,transform .15s;-webkit-tap-highlight-color:transparent}
.fab:hover{box-shadow:0 0 40px rgba(200,255,0,.50),0 0 80px rgba(200,255,0,.20);transform:scale(1.06)}
.fab svg{width:20px;height:20px;stroke:currentColor;fill:none;stroke-width:2}
@media(min-width:900px){.fab{display:none}}

/* Mobile bottom nav */
.mobile-nav{position:fixed;bottom:0;left:0;right:0;height:60px;background:var(--surface);border-top:1px solid var(--border);display:flex;z-index:300;padding-bottom:env(safe-area-inset-bottom,0px)}
@media(min-width:900px){.mobile-nav{display:none}}
.mobile-nav-item{flex:1;display:flex;flex-direction:column;align-items:center;justify-content:center;gap:3px;color:var(--ink3);text-decoration:none;font-family:var(--mono);font-size:8px;letter-spacing:.06em;text-transform:uppercase;transition:color .15s;-webkit-tap-highlight-color:transparent}
.mobile-nav-item svg{width:20px;height:20px;stroke:currentColor;fill:none;stroke-width:1.5}
.mobile-nav-item.active{color:var(--acid)}
.mobile-nav-item.active svg{stroke:var(--acid)}
body{padding-bottom:60px}
@media(min-width:900px){body{padding-bottom:0}}

.empty-state{grid-column:1/-1;display:flex;flex-direction:column;align-items:center;gap:12px;padding:60px 20px;text-align:center}
.empty-state svg{width:48px;height:48px;stroke:var(--ink4);fill:none;stroke-width:1}
.empty-state h3{font-family:var(--font);font-size:18px;font-weight:600;color:var(--ink2);letter-spacing:-.02em}
.empty-state p{font-family:var(--mono);font-size:10px;color:var(--ink3);letter-spacing:.04em}
</style>
<style>@media(max-width:899px){.cv-mobile-wordmark{display:block!important}}</style>
</head>
<body>
<div class="cv-app">
  <aside class="cv-sidebar">
    <div class="cv-wordmark">
      <div class="cv-wordmark-text">Collector<em>Vault</em></div>
      <div class="cv-wordmark-tag">Collectibles Manager</div>
    </div>
    <div class="cv-mobile-wordmark" style="display:none">Collector<em>Vault</em></div>
    <nav class="cv-nav">
      <a href="/beta/scanner.php" class="cv-nav-item">
        <svg viewBox="0 0 24 24"><path d="M3 7V5a2 2 0 0 1 2-2h2M17 3h2a2 2 0 0 1 2 2v2M21 17v2a2 2 0 0 1-2 2h-2M7 21H5a2 2 0 0 1-2-2v-2"/><rect x="7" y="7" width="10" height="10" rx="1"/></svg>
        <span class="cv-nav-label">Scan</span>
      </a>
      <a href="/beta/collection.php" class="cv-nav-item active">
        <svg viewBox="0 0 24 24"><rect x="3" y="3" width="7" height="7" rx="1"/><rect x="14" y="3" width="7" height="7" rx="1"/><rect x="3" y="14" width="7" height="7" rx="1"/><rect x="14" y="14" width="7" height="7" rx="1"/></svg>
        <span class="cv-nav-label">Collection</span>
      </a>
    </nav>
    <div class="cv-sidebar-foot">
      <div class="cv-user-chip">
        <div class="cv-user-avatar"><?= strtoupper(substr($username,0,1)) ?></div>
        <div class="cv-user-name"><?= $username ?></div>
      </div>
      <div style="display:flex;gap:6px;margin-top:6px">
        <button class="cv-icon-btn" onclick="toggleTheme()" id="themeToggle" style="flex:1"><span id="themeIconWrap"></span></button>
        <a href="/beta/logout.php" class="cv-icon-btn" style="flex:1;text-decoration:none">
          <svg viewBox="0 0 24 24"><path d="M9 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h4"/><polyline points="16,17 21,12 16,7"/><line x1="21" y1="12" x2="9" y2="12"/></svg>
        </a>
      </div>
    </div>
    <div class="cv-mobile-controls">
      <button class="cv-icon-btn" onclick="toggleTheme()" id="themeToggleMobile"><span id="themeIconWrapMobile"></span></button>
      <a href="/beta/logout.php" class="cv-icon-btn" style="text-decoration:none">
        <svg viewBox="0 0 24 24"><path d="M9 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h4"/><polyline points="16,17 21,12 16,7"/><line x1="21" y1="12" x2="9" y2="12"/></svg>
      </a>
    </div>
  </aside>

  <main class="cv-main">
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
      <button class="btn btn-ghost" onclick="refreshAllPrices()" style="height:24px;font-size:7px;padding:0 10px;display:flex;align-items:center;gap:4px">
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
</div>

<a href="/beta/scanner.php" class="fab">
  <svg viewBox="0 0 24 24"><line x1="12" y1="5" x2="12" y2="19"/><line x1="5" y1="12" x2="19" y2="12"/></svg>
</a>

<nav class="mobile-nav">
  <a href="/beta/scanner.php" class="mobile-nav-item">
    <svg viewBox="0 0 24 24"><path d="M3 7V5a2 2 0 0 1 2-2h2M17 3h2a2 2 0 0 1 2 2v2M21 17v2a2 2 0 0 1-2 2h-2M7 21H5a2 2 0 0 1-2-2v-2"/><rect x="7" y="7" width="10" height="10" rx="1"/></svg>
    Scan
  </a>
  <a href="/beta/collection.php" class="mobile-nav-item active">
    <svg viewBox="0 0 24 24"><rect x="3" y="3" width="7" height="7" rx="1"/><rect x="14" y="3" width="7" height="7" rx="1"/><rect x="3" y="14" width="7" height="7" rx="1"/><rect x="14" y="14" width="7" height="7" rx="1"/></svg>
    Collection
  </a>
</nav>

<div id="modalBg" onclick="if(event.target===this)closeModal()">
  <div class="modal-sheet">
    <div class="modal-handle"></div>
    <div class="modal-hero">
      <img id="modalImg" src="" alt="">
      <div class="modal-hero-grad"></div>
      <button class="modal-close" onclick="closeModal()">×</button>
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

<div id="toast"></div>

<!-- Edit Modal -->
<div id="editBg" style="display:none;position:fixed;inset:0;background:rgba(5,5,7,.90);z-index:600;backdrop-filter:blur(8px);-webkit-backdrop-filter:blur(8px);align-items:center;justify-content:center;padding:16px" onclick="if(event.target===this)closeEdit()">
  <div style="background:var(--surface);border:1px solid var(--border2);border-radius:var(--radius-lg);width:100%;max-width:480px;max-height:88dvh;overflow-y:auto">
    <div style="display:flex;align-items:center;justify-content:space-between;padding:16px 20px;border-bottom:1px solid var(--border)">
      <div style="font-family:var(--mono);font-size:9px;letter-spacing:.16em;text-transform:uppercase;color:var(--acid);display:flex;align-items:center;gap:8px">
        <span style="width:5px;height:5px;border-radius:50%;background:var(--acid);display:inline-block;box-shadow:var(--acid-glow-sm)"></span>
        Edit Item
      </div>
      <button onclick="closeEdit()" style="width:28px;height:28px;border-radius:50%;background:var(--surface2);border:1px solid var(--border);color:var(--ink2);display:flex;align-items:center;justify-content:center;cursor:pointer;font-size:16px;font-family:var(--font)">×</button>
    </div>
    <div style="padding:20px;display:flex;flex-direction:column;gap:14px" id="editFields"></div>
    <div style="padding:0 20px 20px;display:flex;gap:8px">
      <button onclick="saveEdit()" style="flex:1;height:40px;background:var(--acid);color:var(--void);border:none;border-radius:var(--radius-md);font-family:var(--mono);font-size:9px;font-weight:700;letter-spacing:.12em;text-transform:uppercase;cursor:pointer;box-shadow:var(--acid-glow-sm)">Save Changes</button>
      <button onclick="closeEdit()" style="height:40px;padding:0 16px;background:var(--surface2);color:var(--ink2);border:1px solid var(--border);border-radius:var(--radius-md);font-family:var(--mono);font-size:9px;letter-spacing:.10em;text-transform:uppercase;cursor:pointer">Cancel</button>
    </div>
  </div>
</div>

<script>
let allItems=[],priceData={},currentTab='all',currentView='grid',currentModalId=null,toastT;

function _renderThemeIcon(t){const s='<svg viewBox="0 0 24 24" width="14" height="14" fill="none" stroke="currentColor" stroke-width="1.5"><circle cx="12" cy="12" r="4"/><path d="M12 2v2M12 20v2M4.93 4.93l1.41 1.41M17.66 17.66l1.41 1.41M2 12h2M20 12h2M4.93 19.07l1.41-1.41M17.66 6.34l1.41-1.41"/></svg>',m='<svg viewBox="0 0 24 24" width="14" height="14" fill="none" stroke="currentColor" stroke-width="1.5"><path d="M21 12.79A9 9 0 1 1 11.21 3 7 7 0 0 0 21 12.79z"/></svg>';document.querySelectorAll('#themeIconWrap,#themeIconWrapMobile').forEach(el=>{if(el)el.innerHTML=t==='dark'?s:m});}
function toggleTheme(){const t=document.documentElement.getAttribute('data-theme')==='dark'?'light':'dark';document.documentElement.setAttribute('data-theme',t);localStorage.setItem('cv_theme',t);_renderThemeIcon(t);}

const CATS={all:{label:'All',icon:'<svg viewBox="0 0 24 24" width="11" height="11" fill="none" stroke="currentColor" stroke-width="1.5"><rect x="3" y="3" width="7" height="7" rx="1"/><rect x="14" y="3" width="7" height="7" rx="1"/><rect x="3" y="14" width="7" height="7" rx="1"/><rect x="14" y="14" width="7" height="7" rx="1"/></svg>'},cards:{label:'Cards',icon:'<svg viewBox="0 0 24 24" width="11" height="11" fill="none" stroke="currentColor" stroke-width="1.5"><rect x="2" y="5" width="20" height="14" rx="2"/><path d="M2 10h20"/></svg>'},shirts:{label:'Shirts',icon:'<svg viewBox="0 0 24 24" width="11" height="11" fill="none" stroke="currentColor" stroke-width="1.5"><path d="M20.38 3.46L16 2a4 4 0 0 1-8 0L3.62 3.46a2 2 0 0 0-1.34 2.23l.58 3.57a1 1 0 0 0 .99.84H6v10c0 1.1.9 2 2 2h8a2 2 0 0 0 2-2V10h2.15a1 1 0 0 0 .99-.84l.58-3.57a2 2 0 0 0-1.34-2.23z"/></svg>'},games:{label:'Games',icon:'<svg viewBox="0 0 24 24" width="11" height="11" fill="none" stroke="currentColor" stroke-width="1.5"><rect x="2" y="6" width="20" height="12" rx="2"/><path d="M12 12h.01M7 12h.01M17 12h.01"/></svg>'},vinyl:{label:'Vinyl',icon:'<svg viewBox="0 0 24 24" width="11" height="11" fill="none" stroke="currentColor" stroke-width="1.5"><circle cx="12" cy="12" r="10"/><circle cx="12" cy="12" r="3"/></svg>'},other:{label:'Other',icon:'<svg viewBox="0 0 24 24" width="11" height="11" fill="none" stroke="currentColor" stroke-width="1.5"><circle cx="12" cy="12" r="10"/><line x1="12" y1="8" x2="12" y2="12"/><line x1="12" y1="16" x2="12.01" y2="16"/></svg>'}};

function buildToolbarTabs(){const bar=document.getElementById('toolbar');const gap=bar.querySelector('.toolbar-gap');Object.entries(CATS).forEach(([k,v])=>{const btn=document.createElement('button');btn.className='cat-tab'+(k==='all'?' active':'');btn.dataset.cat=k;btn.innerHTML=`${v.icon} ${v.label} <span class="cat-count" id="cnt_${k}">0</span>`;btn.onclick=()=>setTab(k);bar.insertBefore(btn,gap);});}
function setTab(t){currentTab=t;document.querySelectorAll('.cat-tab').forEach(b=>b.classList.toggle('active',b.dataset.cat===t));filterItems();setTimeout(loadImagesForVisible,100);}

document.addEventListener('DOMContentLoaded',()=>{const t=localStorage.getItem('cv_theme')||'dark';document.documentElement.setAttribute('data-theme',t);_renderThemeIcon(t);buildToolbarTabs();loadAll();});

async function loadAll(){
  try{
    const[cr,pr]=await Promise.all([fetch('/beta/api.php?action=collection&category=all',{credentials:'same-origin'}),fetch('/beta/api.php?action=getPrices',{credentials:'same-origin'})]);
    const cd=await cr.json();const pd=await pr.json();
    allItems=cd.ok?(cd.items||[]):[];priceData=pd.ok?(pd.prices||{}):{};
    updateCounts();filterItems();loadStats();autoRefreshPrices();setTimeout(loadImagesForVisible,300);
  }catch(e){document.getElementById('itemsGrid').innerHTML='<div class="empty-state"><p>Failed to load. Try refreshing.</p></div>';}
}

async function loadStats(){
  try{const r=await fetch('/beta/api.php?action=stats',{credentials:'same-origin'});const d=await r.json();if(!d.ok)return;const s=d.stats;
  document.getElementById('sTotal').textContent=s.total||0;
  document.getElementById('sValue').textContent=s.value?'£'+parseFloat(s.value).toFixed(0):'—';
  document.getElementById('sInvested').textContent=s.invested?'£'+parseFloat(s.invested).toFixed(0):'—';
  const gain=(s.value||0)-(s.invested||0);const gEl=document.getElementById('sGain');
  gEl.textContent=(gain>=0?'+':'')+'£'+Math.abs(gain).toFixed(0);
  gEl.className='stat-value '+(gain>0?'is-gain':gain<0?'is-loss':'');}catch(e){}
}

function updateCounts(){const counts={all:allItems.length};allItems.forEach(i=>{counts[i.category]=(counts[i.category]||0)+1;});Object.entries(counts).forEach(([k,v])=>{const el=document.getElementById('cnt_'+k);if(el)el.textContent=v;});}

function filterItems(){
  const q=document.getElementById('searchInput').value.toLowerCase().trim();
  const sort=document.getElementById('sortSelect').value;
  let items=allItems.filter(i=>{if(currentTab!=='all'&&i.category!==currentTab)return false;if(q&&![i.name,i.subtitle,i.series,i.item_type,i.year].join(' ').toLowerCase().includes(q))return false;return true;});
  items.sort((a,b)=>{if(sort==='name')return(a.name||'').localeCompare(b.name||'');if(sort==='oldest')return(a.created||'').localeCompare(b.created||'');if(sort==='value_desc'||sort==='value_asc'){const av=priceData[a.id]?.avg_10||0;const bv=priceData[b.id]?.avg_10||0;return sort==='value_desc'?bv-av:av-bv;}return(b.created||'').localeCompare(a.created||'');});
  renderItems(items);
}

function setView(v){currentView=v;document.getElementById('vGrid').classList.toggle('active',v==='grid');document.getElementById('vList').classList.toggle('active',v==='list');const g=document.getElementById('itemsGrid');g.className=v==='grid'?'items-grid':'items-list';filterItems();}

function renderItems(items){
  const g=document.getElementById('itemsGrid');
  if(!items.length){g.innerHTML='<div class="empty-state"><svg viewBox="0 0 24 24"><rect x="3" y="3" width="18" height="18" rx="2"/><circle cx="8.5" cy="8.5" r="1.5"/><polyline points="21,15 16,10 5,21"/></svg><h3>No items found</h3><p>Try a different filter or scan something new</p></div>';return;}
  g.innerHTML=items.map((item,idx)=>currentView==='grid'?renderGrid(item,idx):renderList(item)).join('');
}

function renderGrid(item,idx){
  const p=priceData[item.id];const price=p?.avg_10?'£'+parseFloat(p.avg_10).toFixed(2):'—';
  const badge=p?.change_pct?`<span class="ic-change ${p.direction||'flat'}">${p.direction==='up'?'▲':p.direction==='down'?'▼':'—'}${Math.abs(p.change_pct).toFixed(0)}%</span>`:'';
  const idxLabel=String(idx+1).padStart(2,'0');
  return`<div class="item-card" id="card-${esc(item.id)}" onclick="openModal('${esc(item.id)}')">
    <div class="ic-index">${idxLabel}</div>
    <div class="ic-image-wrap">
      <div class="ic-image-placeholder" id="img-${esc(item.id)}"><svg viewBox="0 0 24 24"><rect x="3" y="3" width="18" height="18" rx="2"/><circle cx="8.5" cy="8.5" r="1.5"/><polyline points="21,15 16,10 5,21"/></svg></div>
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
  const p=priceData[item.id];const price=p?.avg_10?'£'+parseFloat(p.avg_10).toFixed(2):'—';
  return`<div class="item-row" onclick="openModal('${esc(item.id)}')">
    <div class="ir-thumb" id="limg-${esc(item.id)}"><svg viewBox="0 0 24 24" style="width:20px;height:20px;stroke:var(--ink4);fill:none;stroke-width:1.2"><rect x="3" y="3" width="18" height="18" rx="2"/><circle cx="8.5" cy="8.5" r="1.5"/></svg></div>
    <div class="ir-info"><div class="ir-name">${esc(item.name)}</div><div class="ir-sub">${esc([item.subtitle,item.series,item.year].filter(Boolean).join(' · '))}</div></div>
    ${item.item_type?`<span class="ic-badge">${esc(item.item_type)}</span>`:''}
    <div class="ir-price">${price}</div>
  </div>`;
}

function buildQuery(item){return[item.name,item.subtitle,item.series,item.year].filter(Boolean).join(' ').replace(/['"]/g,'');}

async function loadImagesForVisible(){
  const visible=allItems.filter(i=>document.getElementById('img-'+i.id)||document.getElementById('limg-'+i.id));
  for(const item of visible.slice(0,20)){loadImg(item.id,buildQuery(item),item.category,item.name);}
}

async function loadImg(id,query,cat,fallback){
  try{const resp=await fetch('/beta/api.php?'+new URLSearchParams({action:'getImage',id,query,cat}),{credentials:'same-origin'});const d=await resp.json();if(d.url)setImgEl(id,d.url,fallback);}catch(e){}
}

function setImgEl(id,src,alt){
  const gEl=document.getElementById('img-'+id);
  if(gEl){const img=document.createElement('img');img.className='ic-image';img.src=src;img.alt=alt||'';img.onerror=()=>{};gEl.replaceWith(img);}
  const lEl=document.getElementById('limg-'+id);
  if(lEl){lEl.innerHTML=`<img src="${src}" alt="${alt||''}" style="width:100%;height:100%;object-fit:cover">`;}
}

async function autoRefreshPrices(){
  document.getElementById('priceStatus').textContent='Updating eBay prices…';
  const toRefresh=allItems.slice(0,30);let done=0;
  for(const item of toRefresh){
    try{const q=buildQuery(item);if(!q){done++;continue;}const fd=new FormData();fd.append('action','refreshPrices');fd.append('item_id',item.id);fd.append('query',q);
    const resp=await fetch('/beta/api.php',{method:'POST',body:fd,credentials:'same-origin'});const d=await resp.json();if(d.ok)priceData[item.id]=d.price;done++;
    document.getElementById('priceStatus').textContent=`Updating prices… ${done}/${toRefresh.length}`;}catch(e){done++;}
  }
  filterItems();loadStats();document.getElementById('priceStatus').textContent=`Prices updated — ${new Date().toLocaleTimeString()}`;
}

async function refreshAllPrices(){autoRefreshPrices();}

async function refreshSinglePrice(id){
  if(!id)return;const item=allItems.find(i=>i.id===id);if(!item)return;showToast('Refreshing price…');
  try{const fd=new FormData();fd.append('action','refreshPrices');fd.append('item_id',id);fd.append('query',buildQuery(item));
  const resp=await fetch('/beta/api.php',{method:'POST',body:fd,credentials:'same-origin'});const d=await resp.json();
  if(d.ok){priceData[id]=d.price;filterItems();openModal(id);showToast('Price updated');}}catch(e){showToast('Price refresh failed');}
}

function openModal(id){
  currentModalId=id;const item=allItems.find(i=>i.id===id);if(!item)return;
  document.getElementById('modalCat').textContent=item.category||'';
  document.getElementById('modalTitle').textContent=item.name||'—';
  document.getElementById('modalSub').textContent=[item.subtitle,item.series,item.year,item.condition].filter(Boolean).join(' · ');
  const p=priceData[id];
  document.getElementById('mAvg10').textContent=p?.avg_10?'£'+parseFloat(p.avg_10).toFixed(2):'—';
  document.getElementById('mAvg30').textContent=p?.avg_30?'£'+parseFloat(p.avg_30).toFixed(2):'—';
  document.getElementById('mPaid').textContent=item.price_paid?'£'+parseFloat(item.price_paid).toFixed(2):'—';
  const fields=['item_type','condition','manufacturer','year','series','card_number','platform','genre','region','artist','label','format','pressing','kit_type','size','signed'].filter(k=>item[k]);
  document.getElementById('modalFields').innerHTML=fields.map(k=>`<div><div class="modal-field-label">${k.replace(/_/g,' ')}</div><div class="modal-field-val">${esc(item[k])}</div></div>`).join('');
  document.getElementById('modalBg').classList.add('open');
  // Try existing loaded image first, then fetch
  const existingImg = document.getElementById('img-'+id);
  if (existingImg && existingImg.tagName === 'IMG' && existingImg.src) {
    document.getElementById('modalImg').src = existingImg.src;
  } else {
    document.getElementById('modalImg').src = '';
    fetch('/beta/api.php?'+new URLSearchParams({action:'getImage',id,query:buildQuery(item),cat:item.category}),{credentials:'same-origin'})
      .then(r=>r.json())
      .then(d=>{ if(d.url) document.getElementById('modalImg').src = d.url; })
      .catch(()=>{});
  }
}

function closeModal(){document.getElementById('modalBg').classList.remove('open');currentModalId=null;}
document.addEventListener('keydown',e=>{if(e.key==='Escape')closeModal();});

async function deleteItem(id){
  if(!id||!confirm('Delete this item from your vault?'))return;
  try{const fd=new FormData();fd.append('action','delete');fd.append('item_id',id);const resp=await fetch('/beta/api.php',{method:'POST',body:fd,credentials:'same-origin'});const d=await resp.json();
  if(d.ok){allItems=allItems.filter(i=>i.id!==id);delete priceData[id];closeModal();updateCounts();filterItems();loadStats();showToast('Item deleted');}}catch(e){showToast('Delete failed');}
}

function esc(s){return String(s||'').replace(/&/g,'&amp;').replace(/</g,'&lt;').replace(/>/g,'&gt;').replace(/"/g,'&quot;');}

/* ── Edit item ─────────────────────────────────────────────────────────── */
let editItemId = null;

function openEdit(id) {
  editItemId = id;
  const item = allItems.find(i => i.id === id);
  if (!item) return;

  // Close view modal first
  closeModal();

  const fields = document.getElementById('editFields');
  const editableKeys = ['name','subtitle','series','year','item_type','condition','manufacturer',
    'card_number','platform','genre','region','artist','label','format','pressing',
    'kit_type','size','signed','price_paid','ebay_query'];

  const labelMap = {name:'Name',subtitle:'Subtitle / Set',series:'Series',year:'Year',
    item_type:'Type',condition:'Condition',manufacturer:'Manufacturer',
    card_number:'Card Number',platform:'Platform',genre:'Genre',region:'Region',
    artist:'Artist',label:'Label',format:'Format',pressing:'Pressing',
    kit_type:'Kit Type',size:'Size',signed:'Signed',price_paid:'Paid (£)',
    ebay_query:'eBay Search Query'};

  fields.innerHTML = editableKeys.map(k => {
    const val = k === 'ebay_query'
      ? (item.ebay_query || buildQuery(item))
      : (item[k] || '');
    const hint = k === 'ebay_query'
      ? '<div style="font-family:var(--mono);font-size:8px;color:var(--ink3);margin-top:3px;letter-spacing:.04em">Override the search term used for eBay pricing</div>'
      : '';
    return `<div>
      <label style="font-family:var(--mono);font-size:8px;letter-spacing:.14em;text-transform:uppercase;color:var(--ink3);display:block;margin-bottom:4px">${labelMap[k]||k}</label>
      <input id="ef_${k}" type="${k==='price_paid'?'number':'text'}" value="${esc(val)}"
        style="width:100%;height:36px;padding:0 12px;background:var(--surface2);border:1px solid var(--border);border-radius:var(--radius-md);font-family:var(--font);font-size:13px;color:var(--ink);outline:none;transition:border-color .15s"
        onfocus="this.style.borderColor='rgba(200,255,0,.35)'"
        onblur="this.style.borderColor=''"
      >
      ${hint}
    </div>`;
  }).join('');

  const bg = document.getElementById('editBg');
  bg.style.display = 'flex';
}

function closeEdit() {
  document.getElementById('editBg').style.display = 'none';
  editItemId = null;
}

async function saveEdit() {
  if (!editItemId) return;
  const item = allItems.find(i => i.id === editItemId);
  if (!item) return;

  const editableKeys = ['name','subtitle','series','year','item_type','condition','manufacturer',
    'card_number','platform','genre','region','artist','label','format','pressing',
    'kit_type','size','signed','price_paid','ebay_query'];

  const updates = {};
  editableKeys.forEach(k => {
    const el = document.getElementById('ef_'+k);
    if (el) updates[k] = el.value;
  });

  try {
    const fd = new FormData();
    fd.append('action', 'update');
    fd.append('item_id', editItemId);
    fd.append('updates', JSON.stringify(updates));
    const resp = await fetch('/beta/api.php', {method:'POST', body:fd, credentials:'same-origin'});
    const d = await resp.json();
    if (d.ok) {
      // Update local data
      Object.assign(item, updates);
      closeEdit();
      filterItems();
      showToast('Item updated');
    } else {
      showToast(d.error || 'Update failed');
    }
  } catch(e) {
    showToast('Update failed');
  }
}
function showToast(msg){const el=document.getElementById('toast');el.textContent=msg;el.classList.add('show');clearTimeout(toastT);toastT=setTimeout(()=>el.classList.remove('show'),2800);}
</script>
</body>
</html>
