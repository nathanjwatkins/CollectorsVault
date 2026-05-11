<?php
ob_start();
header('Cache-Control: no-cache, no-store, must-revalidate, max-age=0');
header('Pragma: no-cache');
header('Expires: 0');
header('X-LiteSpeed-Cache-Control: no-cache');
ini_set('session.cookie_samesite', 'Lax');
ini_set('session.cookie_secure', '1');
ini_set('session.cookie_httponly', '1');
session_start();
if (!isset($_SESSION['user'])) { header('Location: /index.php'); exit; }
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
<script>document.documentElement.setAttribute('data-theme','dark');</script>
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

<style>
@import url('https://fonts.googleapis.com/css2?family=Outfit:wght@200;300;400;500;600;700;800;900&family=JetBrains+Mono:wght@300;400;500;700&display=swap');

/* ═══════════════════════════════════════════════════════════════════════════
   COLLECTORVAULT — COMPLETE REDESIGN
   Aesthetic: Precision dark instrument. Brutalist data meets luxury finish.
   Fonts: Outfit (display/UI) · JetBrains Mono (data/labels/prices)
   Palette: #050507 void · #C8FF00 acid · #FAFAFA white · #111116 surface
   Language: Zero-radius edges, 1px borders, zone-based layout, data density
   ═══════════════════════════════════════════════════════════════════════════ */

/* ── Reset ───────────────────────────────────────────────────────────────── */
*, *::before, *::after { box-sizing: border-box; margin: 0; padding: 0; }
a { color: inherit; text-decoration: none; }
button { cursor: pointer; font-family: inherit; }
input, select, textarea { font-family: inherit; }
img { display: block; }

/* ── Design tokens ───────────────────────────────────────────────────────── */
:root {
  /* Base */
  --void:      #050507;
  --surface:   #0C0C10;
  --surface2:  #111116;
  --surface3:  #18181F;
  --border:    rgba(255,255,255,.07);
  --border2:   rgba(255,255,255,.12);

  /* Type */
  --ink:       #FAFAFA;
  --ink2:      #888896;
  --ink3:      #444452;
  --ink4:      #2A2A35;

  /* Accent */
  --acid:      #C8FF00;
  --acid-dim:  rgba(200,255,0,.12);
  --acid-glow: 0 0 24px rgba(200,255,0,.30), 0 0 80px rgba(200,255,0,.10);
  --acid-glow-sm: 0 0 12px rgba(200,255,0,.25);
  --red:       #FF4444;
  --green:     #C8FF00;

  /* Type system */
  --font:      'Outfit', system-ui, sans-serif;
  --mono:      'JetBrains Mono', monospace;

  /* Layout */
  --nav-w:     220px;        /* sidebar width desktop */
  --nav-h:     56px;         /* top bar height mobile */
  --radius:    2px;
  --radius-md: 4px;
  --radius-lg: 8px;
}

/* ── Base ────────────────────────────────────────────────────────────────── */
html {
  font-size: 16px;
  background: var(--void);
  color-scheme: dark;
  height: 100%;
}

body {
  font-family: var(--font);
  background: var(--void);
  color: var(--ink);
  min-height: 100dvh;
  -webkit-font-smoothing: antialiased;
  -moz-osx-font-smoothing: grayscale;
  line-height: 1.5;
}

/* ── Noise texture overlay ───────────────────────────────────────────────── */
body::before {
  content: '';
  position: fixed;
  inset: 0;
  background-image: url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' width='300' height='300'%3E%3Cfilter id='n'%3E%3CfeTurbulence type='fractalNoise' baseFrequency='0.80' numOctaves='4' stitchTiles='stitch'/%3E%3C/filter%3E%3Crect width='300' height='300' filter='url(%23n)' opacity='1'/%3E%3C/svg%3E");
  background-size: 300px 300px;
  opacity: 0.018;
  pointer-events: none;
  z-index: 1000;
}

/* ══════════════════════════════════════════════════════════════════════════
   LAYOUT SYSTEM — SIDEBAR + CONTENT
   ══════════════════════════════════════════════════════════════════════════ */

/* ── App shell ───────────────────────────────────────────────────────────── */
.cv-app {
  display: flex;
  flex-direction: column;
  min-height: 100dvh;
}

@media (min-width: 900px) {
  .cv-app {
    flex-direction: row;
  }
}

/* ── Sidebar nav ─────────────────────────────────────────────────────────── */
.cv-sidebar {
  width: 100%;
  height: var(--nav-h);
  background: var(--surface);
  border-bottom: 1px solid var(--border);
  display: flex;
  align-items: center;
  padding: 0 16px;
  gap: 16px;
  position: sticky;
  top: 0;
  z-index: 200;
  flex-shrink: 0;
}

@media (min-width: 900px) {
  .cv-sidebar {
    width: var(--nav-w);
    height: 100dvh;
    flex-direction: column;
    align-items: stretch;
    padding: 0;
    border-bottom: none;
    border-right: 1px solid var(--border);
    position: sticky;
    top: 0;
    overflow-y: auto;
    gap: 0;
  }
}

/* Sidebar wordmark */
.cv-wordmark {
  padding: 32px 22px 24px;
  display: none;
  flex-direction: column;
  gap: 2px;
  border-bottom: 1px solid var(--border);
  flex-shrink: 0;
}

@media (min-width: 900px) {
  .cv-wordmark { display: flex; }
}

.cv-wordmark-text {
  font-family: var(--font);
  font-size: 15px;
  font-weight: 700;
  letter-spacing: -.02em;
  color: var(--ink);
  line-height: 1;
}

.cv-wordmark-text em {
  font-style: normal;
  color: var(--acid);
}

.cv-wordmark-tag {
  font-family: var(--mono);
  font-size: 8px;
  letter-spacing: .14em;
  color: var(--ink3);
  text-transform: uppercase;
  margin-top: 6px;
}

/* Sidebar nav items */
.cv-nav {
  display: flex;
  align-items: center;
  gap: 4px;
  margin-left: 8px;
}

@media (min-width: 900px) {
  .cv-nav {
    flex-direction: column;
    align-items: stretch;
    padding: 16px 12px;
    gap: 2px;
    margin-left: 0;
    flex: 1;
  }
}

.cv-nav-item {
  display: flex;
  align-items: center;
  gap: 10px;
  padding: 8px 10px;
  border-radius: var(--radius-md);
  font-family: var(--mono);
  font-size: 9px;
  letter-spacing: .10em;
  text-transform: uppercase;
  color: var(--ink3);
  text-decoration: none;
  transition: color .15s, background .15s;
  white-space: nowrap;
  border: 1px solid transparent;
  -webkit-tap-highlight-color: transparent;
}

.cv-nav-item svg {
  width: 16px;
  height: 16px;
  stroke: currentColor;
  fill: none;
  stroke-width: 1.5;
  flex-shrink: 0;
}

.cv-nav-item:hover {
  color: var(--ink);
  background: var(--surface2);
}

.cv-nav-item.active {
  color: var(--acid);
  background: var(--acid-dim);
  border-color: rgba(200,255,0,.18);
}

.cv-nav-item.active svg { stroke: var(--acid); }

/* Sidebar label */
.cv-nav-label {
  display: none;
}

@media (min-width: 900px) {
  .cv-nav-label {
    display: block;
  }
}

/* Mobile nav label */
@media (max-width: 899px) {
  .cv-nav-item {
    padding: 8px 12px;
    font-size: 8px;
  }
}

/* Sidebar bottom — user + theme */
.cv-sidebar-foot {
  display: none;
  flex-direction: column;
  gap: 1px;
  padding: 12px;
  border-top: 1px solid var(--border);
  margin-top: auto;
  flex-shrink: 0;
}

@media (min-width: 900px) {
  .cv-sidebar-foot { display: flex; }
}

/* Mobile right controls */
.cv-mobile-controls {
  display: flex;
  align-items: center;
  gap: 6px;
  margin-left: auto;
}

@media (min-width: 900px) {
  .cv-mobile-controls { display: none; }
}

/* Mobile wordmark — hidden on desktop where sidebar wordmark takes over */
.cv-mobile-wordmark {
  font-family: var(--font);
  font-size: 13px;
  font-weight: 700;
  letter-spacing: -.01em;
  color: var(--ink);
  white-space: nowrap;
}

@media (min-width: 900px) {
  .cv-mobile-wordmark { display: none; }
}

.cv-mobile-wordmark em {
  font-style: normal;
  color: var(--acid);
}

/* ── Icon button ─────────────────────────────────────────────────────────── */
.cv-icon-btn {
  width: 32px;
  height: 32px;
  display: flex;
  align-items: center;
  justify-content: center;
  background: var(--surface2);
  border: 1px solid var(--border);
  border-radius: var(--radius-md);
  color: var(--ink2);
  transition: color .15s, border-color .15s;
  flex-shrink: 0;
  -webkit-tap-highlight-color: transparent;
}

.cv-icon-btn:hover {
  color: var(--ink);
  border-color: var(--border2);
}

.cv-icon-btn svg {
  width: 14px;
  height: 14px;
  stroke: currentColor;
  fill: none;
  stroke-width: 1.5;
  pointer-events: none;
}

/* ── User chip ───────────────────────────────────────────────────────────── */
.cv-user-chip {
  display: flex;
  align-items: center;
  gap: 8px;
  padding: 8px 10px;
  border-radius: var(--radius-md);
  background: var(--surface2);
  border: 1px solid var(--border);
}

.cv-user-avatar {
  width: 24px;
  height: 24px;
  border-radius: 50%;
  background: var(--acid-dim);
  border: 1px solid rgba(200,255,0,.25);
  display: flex;
  align-items: center;
  justify-content: center;
  font-family: var(--mono);
  font-size: 9px;
  font-weight: 700;
  color: var(--acid);
  flex-shrink: 0;
}

.cv-user-name {
  font-family: var(--mono);
  font-size: 9px;
  letter-spacing: .06em;
  color: var(--ink2);
  text-transform: uppercase;
}

/* ── Main content area ───────────────────────────────────────────────────── */
.cv-main {
  flex: 1;
  min-width: 0;
  display: flex;
  flex-direction: column;
}

/* ══════════════════════════════════════════════════════════════════════════
   SHARED UTILITY
   ══════════════════════════════════════════════════════════════════════════ */

/* Mono label */
.mono-label {
  font-family: var(--mono);
  font-size: 8px;
  letter-spacing: .16em;
  text-transform: uppercase;
  color: var(--ink3);
}

/* Acid dot */
.acid-dot::before {
  content: '';
  display: inline-block;
  width: 5px;
  height: 5px;
  border-radius: 50%;
  background: var(--acid);
  margin-right: 8px;
  box-shadow: var(--acid-glow-sm);
  vertical-align: middle;
}

/* Zone header */
.zone-header {
  display: flex;
  align-items: center;
  justify-content: space-between;
  padding: 16px 20px;
  border-bottom: 1px solid var(--border);
  background: var(--surface);
}

.zone-title {
  font-family: var(--mono);
  font-size: 9px;
  letter-spacing: .18em;
  text-transform: uppercase;
  color: var(--acid);
  display: flex;
  align-items: center;
  gap: 8px;
}

.zone-title::before {
  content: '';
  display: block;
  width: 5px;
  height: 5px;
  border-radius: 50%;
  background: var(--acid);
  box-shadow: var(--acid-glow-sm);
}

/* Thin divider */
.divider {
  height: 1px;
  background: var(--border);
}

/* ── Buttons ─────────────────────────────────────────────────────────────── */
.btn {
  display: inline-flex;
  align-items: center;
  gap: 6px;
  padding: 0 14px;
  height: 32px;
  font-family: var(--mono);
  font-size: 9px;
  letter-spacing: .10em;
  text-transform: uppercase;
  border-radius: var(--radius-md);
  border: 1px solid var(--border);
  background: var(--surface2);
  color: var(--ink2);
  transition: all .15s;
  white-space: nowrap;
  flex-shrink: 0;
  -webkit-tap-highlight-color: transparent;
}

.btn:hover { color: var(--ink); border-color: var(--border2); }

.btn svg {
  width: 12px; height: 12px;
  stroke: currentColor; fill: none; stroke-width: 1.5;
}

.btn-acid {
  background: var(--acid);
  color: var(--void);
  border-color: var(--acid);
  font-weight: 700;
  box-shadow: var(--acid-glow-sm);
}

.btn-acid:hover {
  box-shadow: var(--acid-glow);
  color: var(--void);
}

.btn-ghost {
  background: transparent;
  border-color: var(--border);
  color: var(--ink3);
}

.btn-ghost:hover { color: var(--ink); border-color: var(--border2); background: var(--surface2); }

/* ── Tag / badge ─────────────────────────────────────────────────────────── */
.tag {
  display: inline-flex;
  align-items: center;
  padding: 2px 7px;
  border-radius: var(--radius);
  font-family: var(--mono);
  font-size: 7px;
  letter-spacing: .10em;
  text-transform: uppercase;
  background: var(--surface3);
  border: 1px solid var(--border);
  color: var(--ink3);
}

.tag-acid {
  background: var(--acid-dim);
  border-color: rgba(200,255,0,.20);
  color: var(--acid);
}

/* ── Input ───────────────────────────────────────────────────────────────── */
.cv-input {
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
  transition: border-color .15s, box-shadow .15s;
  -webkit-appearance: none;
}

.cv-input::placeholder { color: var(--ink3); }

.cv-input:focus {
  border-color: rgba(200,255,0,.35);
  box-shadow: 0 0 0 3px rgba(200,255,0,.07);
}

/* ── Select ──────────────────────────────────────────────────────────────── */
.cv-select {
  height: 32px;
  padding: 0 10px;
  background: var(--surface2);
  border: 1px solid var(--border);
  border-radius: var(--radius-md);
  font-family: var(--mono);
  font-size: 9px;
  letter-spacing: .06em;
  color: var(--ink2);
  outline: none;
  cursor: pointer;
  -webkit-appearance: none;
}

.cv-select:focus { border-color: rgba(200,255,0,.35); }

/* ── Form group ──────────────────────────────────────────────────────────── */
.form-group {
  display: flex;
  flex-direction: column;
  gap: 5px;
}

.form-label {
  font-family: var(--mono);
  font-size: 8px;
  letter-spacing: .14em;
  text-transform: uppercase;
  color: var(--ink3);
}

/* ── Toast ───────────────────────────────────────────────────────────────── */
#toast {
  position: fixed;
  bottom: 80px;
  left: 50%;
  transform: translateX(-50%) translateY(8px);
  background: var(--surface);
  border: 1px solid rgba(200,255,0,.30);
  color: var(--acid);
  font-family: var(--mono);
  font-size: 10px;
  letter-spacing: .08em;
  padding: 10px 20px;
  border-radius: var(--radius-md);
  opacity: 0;
  pointer-events: none;
  white-space: nowrap;
  z-index: 600;
  transition: opacity .22s, transform .22s;
  box-shadow: var(--acid-glow-sm);
}

#toast.show {
  opacity: 1;
  transform: translateX(-50%) translateY(0);
}

@media (min-width: 900px) {
  #toast { bottom: 28px; }
}

/* ── Save success ────────────────────────────────────────────────────────── */
@keyframes cv-check { to { stroke-dashoffset: 0; } }
@keyframes cv-pop { from { transform: scale(0); opacity: 0; } to { transform: scale(1); opacity: 1; } }
@keyframes cv-fade { 0%{opacity:0} 12%{opacity:1} 75%{opacity:1} 100%{opacity:0} }

.save-success-overlay {
  position: fixed;
  inset: 0;
  background: rgba(5,5,7,.70);
  z-index: 700;
  display: flex;
  align-items: center;
  justify-content: center;
  animation: cv-fade 1.9s ease forwards;
  pointer-events: none;
  backdrop-filter: blur(4px);
}

.save-success-circle {
  width: 88px;
  height: 88px;
  background: var(--acid);
  border-radius: 50%;
  display: flex;
  align-items: center;
  justify-content: center;
  animation: cv-pop .32s cubic-bezier(.34,1.56,.64,1) forwards;
  box-shadow: var(--acid-glow);
}

.save-success-circle svg {
  width: 44px;
  height: 44px;
  stroke: var(--void);
  fill: none;
  stroke-width: 2.5;
  stroke-linecap: round;
  stroke-linejoin: round;
  stroke-dasharray: 60;
  stroke-dashoffset: 60;
  animation: cv-check .38s ease .22s forwards;
}

/* ── Scrollbar ───────────────────────────────────────────────────────────── */
::-webkit-scrollbar { width: 3px; height: 3px; }
::-webkit-scrollbar-track { background: transparent; }
::-webkit-scrollbar-thumb { background: var(--ink4); border-radius: 2px; }
::-webkit-scrollbar-thumb:hover { background: var(--ink3); }

/* ── Selection ───────────────────────────────────────────────────────────── */
::selection { background: rgba(200,255,0,.20); color: var(--void); }


/* Theme toggle hidden — these pages are dark-only */
#themeToggle, #themeToggleMobile { display: none !important; }

/* ── eBay match picker ──────────────────────────────────────────────────── */
/*
  The picker has three responsive concerns:
  1) The header row (label + Sold/Live toggle + Find button) overflows on
     narrow phones — stack the buttons below the label there.
  2) The candidate grid fits 3 columns on tablet/desktop but the cards get
     too cramped for title text at 360px wide — drop to 2 columns on phones.
  3) Find button text wraps awkwardly at very narrow widths — keep it
     single-line and let the magnifier icon stay aligned.
*/
.ebay-picker-row { display:flex; align-items:center; justify-content:space-between; margin-bottom:10px; gap:12px; }
.ebay-picker-actions { display:flex; align-items:center; gap:6px; flex-shrink:0; }
.ebay-picker-actions > * { flex-shrink:0; }
.ebay-cand-grid { display:grid; grid-template-columns:repeat(3,1fr); gap:8px; }
@media (max-width: 520px) {
  .ebay-picker-row { flex-direction:column; align-items:stretch; gap:10px; }
  .ebay-picker-actions { justify-content:flex-start; flex-wrap:wrap; }
  .ebay-cand-grid { grid-template-columns:repeat(2,1fr); }
}
.ebay-picker-btn { white-space:nowrap; }

/* Edit modal: full-bleed sheet on phones, centred panel on tablet+ */
@media (max-width: 639px) {
  #editBg { padding: 0 !important; align-items: flex-end !important; }
  #editBg > div {
    border-radius: var(--radius-lg) var(--radius-lg) 0 0 !important;
    max-height: 92dvh !important;
  }
}
</style>
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
      <a href="/scanner.php" class="cv-nav-item">
        <svg viewBox="0 0 24 24"><path d="M3 7V5a2 2 0 0 1 2-2h2M17 3h2a2 2 0 0 1 2 2v2M21 17v2a2 2 0 0 1-2 2h-2M7 21H5a2 2 0 0 1-2-2v-2"/><rect x="7" y="7" width="10" height="10" rx="1"/></svg>
        <span class="cv-nav-label">Scan</span>
      </a>
      <a href="/collection.php" class="cv-nav-item active">
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
        
        <a href="/logout.php" class="cv-icon-btn" style="flex:1;text-decoration:none">
          <svg viewBox="0 0 24 24"><path d="M9 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h4"/><polyline points="16,17 21,12 16,7"/><line x1="21" y1="12" x2="9" y2="12"/></svg>
        </a>
      </div>
    </div>
    <div class="cv-mobile-controls">
      
      <a href="/logout.php" class="cv-icon-btn" style="text-decoration:none">
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

<a href="/scanner.php" class="fab">
  <svg viewBox="0 0 24 24"><line x1="12" y1="5" x2="12" y2="19"/><line x1="5" y1="12" x2="19" y2="12"/></svg>
</a>

<nav class="mobile-nav">
  <a href="/scanner.php" class="mobile-nav-item">
    <svg viewBox="0 0 24 24"><path d="M3 7V5a2 2 0 0 1 2-2h2M17 3h2a2 2 0 0 1 2 2v2M21 17v2a2 2 0 0 1-2 2h-2M7 21H5a2 2 0 0 1-2-2v-2"/><rect x="7" y="7" width="10" height="10" rx="1"/></svg>
    Scan
  </a>
  <a href="/collection.php" class="mobile-nav-item active">
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
  <div style="background:var(--surface);border:1px solid var(--border2);border-radius:var(--radius-lg);width:100%;max-width:560px;max-height:88dvh;overflow-y:auto">
    <div style="display:flex;align-items:center;justify-content:space-between;padding:16px 20px;border-bottom:1px solid var(--border)">
      <div style="font-family:var(--mono);font-size:9px;letter-spacing:.16em;text-transform:uppercase;color:var(--acid);display:flex;align-items:center;gap:8px">
        <span style="width:5px;height:5px;border-radius:50%;background:var(--acid);display:inline-block;box-shadow:var(--acid-glow-sm)"></span>
        Edit Item
      </div>
      <button onclick="closeEdit()" style="width:28px;height:28px;border-radius:50%;background:var(--surface2);border:1px solid var(--border);color:var(--ink2);display:flex;align-items:center;justify-content:center;cursor:pointer;font-size:16px;font-family:var(--font)">×</button>
    </div>

    <!-- ── eBay match picker ────────────────────────────────────────────── -->
    <div id="ebayPicker" style="padding:16px 20px;border-bottom:1px solid var(--border)">
      <div class="ebay-picker-row">
        <div style="min-width:0">
          <div style="font-family:var(--mono);font-size:8px;letter-spacing:.14em;text-transform:uppercase;color:var(--ink3);margin-bottom:3px">eBay match</div>
          <div id="ebayPickerStatus" style="font-family:var(--font);font-size:11px;color:var(--ink2);overflow-wrap:anywhere">No match selected — using auto.</div>
        </div>
        <div class="ebay-picker-actions">
          <!--
            Sold/Live toggle. Default 'sold' (real completed-sale prices,
            matches the price-refresh logic so picked items reflect actual
            value). 'live' falls back to current active listings — better
            images for rare items, but asking prices not realised prices.
          -->
          <div id="ebayModeToggle" style="display:inline-flex;background:var(--surface2);border:1px solid var(--border);border-radius:var(--radius-md);overflow:hidden;height:32px">
            <button type="button" data-mode="sold" onclick="setEbayMode('sold')"
              style="padding:0 10px;background:var(--acid);color:var(--void);border:none;font-family:var(--mono);font-size:9px;font-weight:700;letter-spacing:.10em;text-transform:uppercase;cursor:pointer;transition:background .15s">Sold</button>
            <button type="button" data-mode="live" onclick="setEbayMode('live')"
              style="padding:0 10px;background:transparent;color:var(--ink2);border:none;font-family:var(--mono);font-size:9px;letter-spacing:.10em;text-transform:uppercase;cursor:pointer;transition:background .15s">Live</button>
          </div>
          <button id="ebayPickerBtn" class="ebay-picker-btn" onclick="loadEbayCandidates()" style="height:32px;padding:0 12px;background:var(--surface2);color:var(--ink);border:1px solid var(--border);border-radius:var(--radius-md);font-family:var(--mono);font-size:9px;letter-spacing:.10em;text-transform:uppercase;cursor:pointer;display:flex;align-items:center;gap:6px;flex-shrink:0">
            <svg viewBox="0 0 24 24" width="11" height="11" fill="none" stroke="currentColor" stroke-width="1.8" style="display:block"><circle cx="11" cy="11" r="7"/><path d="M21 21l-4.35-4.35"/></svg>
            Find on eBay
          </button>
        </div>
      </div>
      <div id="ebayCandidates" style="display:none;margin-top:8px"></div>
    </div>

    <div style="padding:20px;display:flex;flex-direction:column;gap:14px" id="editFields"></div>
    <div style="padding:0 20px 20px;display:flex;gap:8px">
      <button onclick="saveEdit()" style="flex:1;height:40px;background:var(--acid);color:var(--void);border:none;border-radius:var(--radius-md);font-family:var(--mono);font-size:9px;font-weight:700;letter-spacing:.12em;text-transform:uppercase;cursor:pointer;box-shadow:var(--acid-glow-sm)">Save Changes</button>
      <button onclick="closeEdit()" style="height:40px;padding:0 16px;background:var(--surface2);color:var(--ink2);border:1px solid var(--border);border-radius:var(--radius-md);font-family:var(--mono);font-size:9px;letter-spacing:.10em;text-transform:uppercase;cursor:pointer">Cancel</button>
    </div>
  </div>
</div>

<script>
let allItems=[],priceData={},imageCache={},currentTab='all',currentView='grid',currentModalId=null,editItemId=null,pendingChosenImage=null,ebayMode='sold',toastT;



function buildToolbarTabs(){const CATS={all:{label:'All',icon:'<svg viewBox="0 0 24 24" width="11" height="11" fill="none" stroke="currentColor" stroke-width="1.5"><rect x="3" y="3" width="7" height="7" rx="1"/><rect x="14" y="3" width="7" height="7" rx="1"/><rect x="3" y="14" width="7" height="7" rx="1"/><rect x="14" y="14" width="7" height="7" rx="1"/></svg>'},cards:{label:'Cards',icon:'<svg viewBox="0 0 24 24" width="11" height="11" fill="none" stroke="currentColor" stroke-width="1.5"><rect x="2" y="5" width="20" height="14" rx="2"/><path d="M2 10h20"/></svg>'},shirts:{label:'Shirts',icon:'<svg viewBox="0 0 24 24" width="11" height="11" fill="none" stroke="currentColor" stroke-width="1.5"><path d="M20.38 3.46L16 2a4 4 0 0 1-8 0L3.62 3.46a2 2 0 0 0-1.34 2.23l.58 3.57a1 1 0 0 0 .99.84H6v10c0 1.1.9 2 2 2h8a2 2 0 0 0 2-2V10h2.15a1 1 0 0 0 .99-.84l.58-3.57a2 2 0 0 0-1.34-2.23z"/></svg>'},games:{label:'Games',icon:'<svg viewBox="0 0 24 24" width="11" height="11" fill="none" stroke="currentColor" stroke-width="1.5"><rect x="2" y="6" width="20" height="12" rx="2"/><path d="M12 12h.01M7 12h.01M17 12h.01"/></svg>'},vinyl:{label:'Vinyl',icon:'<svg viewBox="0 0 24 24" width="11" height="11" fill="none" stroke="currentColor" stroke-width="1.5"><circle cx="12" cy="12" r="10"/><circle cx="12" cy="12" r="3"/></svg>'},other:{label:'Other',icon:'<svg viewBox="0 0 24 24" width="11" height="11" fill="none" stroke="currentColor" stroke-width="1.5"><circle cx="12" cy="12" r="10"/><line x1="12" y1="8" x2="12" y2="12"/><line x1="12" y1="16" x2="12.01" y2="16"/></svg>'}};const bar=document.getElementById('toolbar');const gap=bar.querySelector('.toolbar-gap');Object.entries(CATS).forEach(([k,v])=>{const btn=document.createElement('button');btn.className='cat-tab'+(k==='all'?' active':'');btn.dataset.cat=k;btn.innerHTML=`${v.icon} ${v.label} <span class="cat-count" id="cnt_${k}">0</span>`;btn.onclick=()=>setTab(k);bar.insertBefore(btn,gap);});}
function setTab(t){currentTab=t;document.querySelectorAll('.cat-tab').forEach(b=>b.classList.toggle('active',b.dataset.cat===t));filterItems();setTimeout(loadImagesForVisible,100);}

document.addEventListener('DOMContentLoaded',()=>{document.documentElement.setAttribute('data-theme','dark');buildToolbarTabs();loadAll();});

async function loadAll(){
  try{
    const[cr,pr]=await Promise.all([fetch('/api.php?action=collection&category=all',{credentials:'same-origin'}),fetch('/api.php?action=getPrices',{credentials:'same-origin'})]);
    const cd=await cr.json();const pd=await pr.json();
    allItems=cd.ok?(cd.items||[]):[];priceData=pd.ok?(pd.prices||{}):{};
    updateCounts();filterItems();loadStats();autoRefreshPrices();setTimeout(loadImagesForVisible,300);
  }catch(e){document.getElementById('itemsGrid').innerHTML='<div class="empty-state"><p>Failed to load. Try refreshing.</p></div>';}
}

async function loadStats(){
  try{const r=await fetch('/api.php?action=stats',{credentials:'same-origin'});const d=await r.json();if(!d.ok)return;const s=d.stats;
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
  // Fetch any images that are still missing from cache
  loadImagesForVisible();
}

function renderGrid(item,idx){
  const p=priceData[item.id];const price=p?.avg_10?'£'+parseFloat(p.avg_10).toFixed(2):'—';
  const badge=p?.change_pct?`<span class="ic-change ${p.direction||'flat'}">${p.direction==='up'?'▲':p.direction==='down'?'▼':'—'}${Math.abs(p.change_pct).toFixed(0)}%</span>`:'';
  const idxLabel=String(idx+1).padStart(2,'0');
  const cachedUrl=imageCache[item.id];
  const imgHtml=cachedUrl
    ? `<img class="ic-image" id="img-${esc(item.id)}" src="${esc(cachedUrl)}" alt="${esc(item.name||'')}" loading="lazy" decoding="async" onerror="this.onerror=null;this.outerHTML='&lt;div class=&quot;ic-image-placeholder&quot; id=&quot;img-${esc(item.id)}&quot;&gt;&lt;svg viewBox=&quot;0 0 24 24&quot;&gt;&lt;rect x=&quot;3&quot; y=&quot;3&quot; width=&quot;18&quot; height=&quot;18&quot; rx=&quot;2&quot;/&gt;&lt;circle cx=&quot;8.5&quot; cy=&quot;8.5&quot; r=&quot;1.5&quot;/&gt;&lt;polyline points=&quot;21,15 16,10 5,21&quot;/&gt;&lt;/svg&gt;&lt;/div&gt;'">`
    : `<div class="ic-image-placeholder" id="img-${esc(item.id)}"><svg viewBox="0 0 24 24"><rect x="3" y="3" width="18" height="18" rx="2"/><circle cx="8.5" cy="8.5" r="1.5"/><polyline points="21,15 16,10 5,21"/></svg></div>`;
  return`<div class="item-card" id="card-${esc(item.id)}" onclick="openModal('${esc(item.id)}')">
    <div class="ic-index">${idxLabel}</div>
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
  const p=priceData[item.id];const price=p?.avg_10?'£'+parseFloat(p.avg_10).toFixed(2):'—';
  const cachedUrl=imageCache[item.id];
  const thumbHtml=cachedUrl
    ? `<div class="ir-thumb" id="limg-${esc(item.id)}"><img src="${esc(cachedUrl)}" alt="${esc(item.name||'')}" loading="lazy" decoding="async" style="width:100%;height:100%;object-fit:cover"></div>`
    : `<div class="ir-thumb" id="limg-${esc(item.id)}"><svg viewBox="0 0 24 24" style="width:20px;height:20px;stroke:var(--ink4);fill:none;stroke-width:1.2"><rect x="3" y="3" width="18" height="18" rx="2"/><circle cx="8.5" cy="8.5" r="1.5"/></svg></div>`;
  return`<div class="item-row" onclick="openModal('${esc(item.id)}')">
    ${thumbHtml}
    <div class="ir-info"><div class="ir-name">${esc(item.name)}</div><div class="ir-sub">${esc([item.subtitle,item.series,item.year].filter(Boolean).join(' · '))}</div></div>
    ${item.item_type?`<span class="ic-badge">${esc(item.item_type)}</span>`:''}
    <div class="ir-price">${price}</div>
  </div>`;
}

function buildQuery(item){return[item.name,item.subtitle,item.series,item.year].filter(Boolean).join(' ').replace(/['"]/g,'');}
// Use the user-pinned ebay_query if set, otherwise the auto-built one.
// All server lookups (image + price) should use this so a custom query
// drives both the image and the price.
function searchQuery(item){return (item.ebay_query && item.ebay_query.trim()) ? item.ebay_query.trim() : buildQuery(item);}

async function loadImagesForVisible(){
  const visible=allItems.filter(i=>!imageCache[i.id]&&(document.getElementById('img-'+i.id)||document.getElementById('limg-'+i.id)));
  for(const item of visible.slice(0,20)){
    // If the item has a locked-in thumbnail (user picked an eBay candidate
    // via the edit screen), use it directly — no scrape needed.
    if (item.thumbnail) {
      const proxied = 'api.php?action=imgProxy&url=' + encodeURIComponent(item.thumbnail);
      imageCache[item.id] = proxied;
      setImgEl(item.id, proxied, item.name);
      continue;
    }
    loadImg(item.id,searchQuery(item),item.category,item.name);
  }
}

async function loadImg(id,query,cat,fallback){
  try{
    const resp=await fetch('/api.php?'+new URLSearchParams({action:'getImage',id,query,cat}),{credentials:'same-origin'});
    const d=await resp.json();
    if(d.url){
      // eBay refuses direct hotlinks, so route every image through our proxy.
      const proxied='api.php?action=imgProxy&url='+encodeURIComponent(d.url);
      imageCache[id]=proxied;
      setImgEl(id,proxied,fallback);
    }
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
  const toRefresh=allItems.slice(0,30);let done=0;
  for(const item of toRefresh){
    try{const q=searchQuery(item);if(!q){done++;continue;}const fd=new FormData();fd.append('action','refreshPrices');fd.append('item_id',item.id);fd.append('query',q);fd.append('category',item.category||'');
    const resp=await fetch('/api.php',{method:'POST',body:fd,credentials:'same-origin'});const d=await resp.json();if(d.ok)priceData[item.id]=d.price;done++;
    document.getElementById('priceStatus').textContent=`Updating prices… ${done}/${toRefresh.length}`;}catch(e){done++;}
  }
  filterItems();loadStats();document.getElementById('priceStatus').textContent=`Prices updated — ${new Date().toLocaleTimeString()}`;
}

async function refreshAllPrices(){autoRefreshPrices();}

async function refreshSinglePrice(id){
  if(!id)return;const item=allItems.find(i=>i.id===id);if(!item)return;showToast('Refreshing price…');
  try{const fd=new FormData();fd.append('action','refreshPrices');fd.append('item_id',id);fd.append('query',searchQuery(item));fd.append('category',item.category||'');
  const resp=await fetch('/api.php',{method:'POST',body:fd,credentials:'same-origin'});const d=await resp.json();
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
  const fields=['item_type','condition','manufacturer','year','series','card_number','parallel','numbered','autograph','platform','genre','region','artist','label','format','pressing','kit_type','size','signed','notes'].filter(k=>item[k]);
  document.getElementById('modalFields').innerHTML=fields.map(k=>`<div><div class="modal-field-label">${k.replace(/_/g,' ')}</div><div class="modal-field-val">${esc(item[k])}</div></div>`).join('');
  document.getElementById('modalBg').classList.add('open');
  // Prefer imageCache (set on first successful fetch) over DOM lookup —
  // re-renders may have replaced the IMG tag but the URL is still in JS state.
  const cachedSrc = imageCache[id];
  if (cachedSrc) {
    document.getElementById('modalImg').src = cachedSrc;
  } else {
    const existingImg = document.getElementById('img-'+id);
    if (existingImg && existingImg.tagName === 'IMG' && existingImg.src) {
      document.getElementById('modalImg').src = existingImg.src;
      imageCache[id] = existingImg.src;
    } else {
      document.getElementById('modalImg').src = '';
      fetch('/api.php?'+new URLSearchParams({action:'getImage',id,query:searchQuery(item),cat:item.category}),{credentials:'same-origin'})
        .then(r=>r.json())
        .then(d=>{ if(d.url){ imageCache[id]=d.url; document.getElementById('modalImg').src = d.url; } })
        .catch(()=>{});
    }
  }
}

function closeModal(){document.getElementById('modalBg').classList.remove('open');currentModalId=null;}
document.addEventListener('keydown',e=>{if(e.key==='Escape')closeModal();});

async function deleteItem(id){
  if(!id||!confirm('Delete this item from your vault?'))return;
  try{const fd=new FormData();fd.append('action','delete');fd.append('item_id',id);const resp=await fetch('/api.php',{method:'POST',body:fd,credentials:'same-origin'});const d=await resp.json();
  if(d.ok){allItems=allItems.filter(i=>i.id!==id);delete priceData[id];delete imageCache[id];closeModal();updateCounts();filterItems();loadStats();showToast('Item deleted');}}catch(e){showToast('Delete failed');}
}

function esc(s){return String(s||'').replace(/&/g,'&amp;').replace(/</g,'&lt;').replace(/>/g,'&gt;').replace(/"/g,'&quot;');}

/* ── Edit item ─────────────────────────────────────────────────────────── */

// ── eBay candidate picker ────────────────────────────────────────────────
// Fires when the user clicks "Find on eBay" inside the edit modal. Uses
// the current ebay_query input (or the auto-built query if empty) to fetch
// 6 listing candidates with images, titles, prices. Clicking a candidate
// stores its image URL in pendingChosenImage and copies the listing title
// into the ebay_query input — saveEdit picks both up.
// Switch the picker between sold/completed listings (default — real sale
// prices, matches what price refresh uses) and live active listings (better
// images for rare items, asking prices though). Re-fires the search if
// candidates are already on screen so the user sees results in the new mode.
function setEbayMode(mode) {
  if (mode !== 'sold' && mode !== 'live') return;
  ebayMode = mode;
  // Update toggle button styles
  const toggle = document.getElementById('ebayModeToggle');
  if (toggle) {
    toggle.querySelectorAll('button').forEach(b => {
      const active = b.dataset.mode === mode;
      b.style.background = active ? 'var(--acid)' : 'transparent';
      b.style.color = active ? 'var(--void)' : 'var(--ink2)';
      b.style.fontWeight = active ? '700' : 'normal';
    });
  }
  // If the candidates panel is already open with results, re-fetch in the
  // new mode so the toggle feels responsive.
  const cands = document.getElementById('ebayCandidates');
  if (cands && cands.style.display !== 'none' && cands.innerHTML.trim() !== '') {
    loadEbayCandidates();
  }
}

async function loadEbayCandidates() {
  const item = allItems.find(i => i.id === editItemId);
  if (!item) return;

  const queryInput = document.getElementById('ef_ebay_query');
  const query = (queryInput && queryInput.value.trim()) || buildQuery(item);

  const btn = document.getElementById('ebayPickerBtn');
  const cands = document.getElementById('ebayCandidates');
  const status = document.getElementById('ebayPickerStatus');
  if (btn) { btn.disabled = true; btn.style.opacity = '.6'; }
  if (status) status.textContent = `Searching ${ebayMode} listings for "${query}"…`;
  if (cands) {
    cands.style.display = 'block';
    cands.innerHTML = '<div style="font-family:var(--mono);font-size:9px;color:var(--ink3);letter-spacing:.10em;padding:14px 0;text-align:center">Loading 6 candidates…</div>';
  }

  try {
    const r = await fetch('api.php?action=searchEbayCandidates&limit=6&mode=' + ebayMode + '&query=' + encodeURIComponent(query), {credentials: 'same-origin'});
    const d = await r.json();
    if (d.blocked) {
      // eBay is rate-limiting our IP. Surface this clearly so the user
      // doesn't think the query is bad — it's a temporary network issue.
      cands.innerHTML = '<div style="font-family:var(--mono);font-size:9px;color:var(--ink3);letter-spacing:.06em;padding:14px 8px;text-align:center;line-height:1.5">eBay temporarily rate-limited the search.<br>Try again in 5–10 minutes, or pick a slightly different query.</div>';
      if (status) status.textContent = 'eBay rate-limited — try again shortly.';
      return;
    }
    if (!d.ok || !d.candidates || !d.candidates.length) {
      const tryOther = ebayMode === 'sold'
        ? ' Try the Live toggle for active listings.'
        : ' Try the Sold toggle for completed sales.';
      cands.innerHTML = `<div style="font-family:var(--mono);font-size:9px;color:var(--ink3);letter-spacing:.06em;padding:14px 0;text-align:center">No ${ebayMode} candidates found.${tryOther}</div>`;
      if (status) status.textContent = `No ${ebayMode} matches.`;
      return;
    }
    renderEbayCandidates(d.candidates);
    const label = ebayMode === 'sold' ? 'sold' : 'live';
    if (status) status.textContent = `${d.candidates.length} ${label} matches — click one to lock in.`;
  } catch (e) {
    cands.innerHTML = `<div style="font-family:var(--mono);font-size:9px;color:var(--red);padding:14px 0;text-align:center">Search failed: ${esc(e.message || e)}</div>`;
    if (status) status.textContent = 'Search failed.';
  } finally {
    if (btn) { btn.disabled = false; btn.style.opacity = '1'; }
  }
}

function renderEbayCandidates(list) {
  const cands = document.getElementById('ebayCandidates');
  if (!cands) return;
  cands.innerHTML = `<div class="ebay-cand-grid">` +
    list.map((c, i) => {
      const proxied = 'api.php?action=imgProxy&url=' + encodeURIComponent(c.image);
      const titleSafe = esc(c.title);
      const priceSafe = esc(c.price || '');
      // Embed all data we need on click as data-* attrs to keep the inline onclick simple.
      return `
        <div onclick="pickEbayCandidate(${i})" data-cand-idx="${i}"
             style="cursor:pointer;background:var(--surface2);border:1px solid var(--border);border-radius:var(--radius-md);overflow:hidden;display:flex;flex-direction:column;transition:border-color .15s,transform .1s"
             onmouseover="this.style.borderColor='rgba(200,255,0,.45)'"
             onmouseout="this.style.borderColor=''"
             onmousedown="this.style.transform='scale(.98)'"
             onmouseup="this.style.transform=''">
          <div style="position:relative;width:100%;aspect-ratio:1/1;background:rgba(0,0,0,.18);overflow:hidden">
            <img src="${proxied}" alt="" loading="lazy"
                 style="width:100%;height:100%;object-fit:cover;opacity:0;transition:opacity .2s"
                 onload="this.style.opacity='1'"
                 onerror="this.style.opacity='0'">
          </div>
          <div style="padding:6px 8px 8px;display:flex;flex-direction:column;gap:2px;min-height:54px">
            <div style="font-family:var(--font);font-size:10px;line-height:1.25;color:var(--ink);display:-webkit-box;-webkit-line-clamp:2;-webkit-box-orient:vertical;overflow:hidden;word-break:break-word">${titleSafe}</div>
            ${priceSafe ? `<div style="font-family:var(--mono);font-size:9px;color:var(--acid);letter-spacing:.04em;margin-top:auto">${priceSafe}</div>` : ''}
          </div>
        </div>`;
    }).join('') +
    `</div>`;
  // Stash the list on the element so pickEbayCandidate can grab it.
  cands._candidates = list;
}

function pickEbayCandidate(idx) {
  const cands = document.getElementById('ebayCandidates');
  if (!cands || !cands._candidates) return;
  const chosen = cands._candidates[idx];
  if (!chosen) return;

  pendingChosenImage = chosen.image;
  // Use the listing's title as the ebay_query so price refreshes lock onto
  // the same kind of listing in future. Truncate aggressive titles down to
  // the first ~10 words — eBay search is happier with shorter queries.
  const queryInput = document.getElementById('ef_ebay_query');
  if (queryInput) {
    const words = (chosen.title || '').replace(/\s+/g, ' ').trim().split(' ').slice(0, 10).join(' ');
    queryInput.value = words;
  }

  // Update status row with a thumbnail preview of the new pick.
  const status = document.getElementById('ebayPickerStatus');
  if (status) {
    const proxied = 'api.php?action=imgProxy&url=' + encodeURIComponent(chosen.image);
    status.innerHTML = `
      <span style="display:inline-flex;align-items:center;gap:8px">
        <img src="${proxied}" alt="" style="width:32px;height:32px;border-radius:6px;object-fit:cover;border:1px solid var(--border)">
        <span style="color:var(--acid)">Match selected — save to lock</span>
      </span>`;
  }
  // Collapse the candidates grid to keep the modal tidy.
  cands.style.display = 'none';
  cands.innerHTML = '';
}

// Default query builder used when the user hasn't set ebay_query explicitly.
// (See buildQuery() defined earlier for the actual implementation.)

function openEdit(id) {
  editItemId = id;
  const item = allItems.find(i => i.id === id);
  if (!item) return;

  // Close view modal first
  closeModal();

  // ── Reset eBay picker ────────────────────────────────────────────────
  // If the item already has a chosen thumbnail, render it as a preview so
  // the user can see what's currently locked in. Otherwise show the
  // "no match selected" hint.
  pendingChosenImage = null; // populated when a candidate is clicked
  setEbayMode('sold');       // always start in sold/completed mode
  const status = document.getElementById('ebayPickerStatus');
  const cands  = document.getElementById('ebayCandidates');
  if (cands) { cands.style.display = 'none'; cands.innerHTML = ''; }
  if (status) {
    if (item.thumbnail) {
      const proxied = 'api.php?action=imgProxy&url=' + encodeURIComponent(item.thumbnail);
      status.innerHTML = `
        <span style="display:inline-flex;align-items:center;gap:8px">
          <img src="${proxied}" alt="" style="width:32px;height:32px;border-radius:6px;object-fit:cover;border:1px solid var(--border)">
          <span style="color:var(--acid)">Match locked</span>
        </span>`;
    } else {
      status.textContent = 'No match selected — using auto.';
    }
  }
  const btn = document.getElementById('ebayPickerBtn');
  if (btn) { btn.disabled = false; btn.style.opacity = '1'; }

  const fields = document.getElementById('editFields');
  const editableKeys = ['name','subtitle','series','year','item_type','condition','manufacturer',
    'card_number','parallel','numbered','autograph','platform','genre','region','artist','label','format','pressing',
    'kit_type','size','signed','price_paid','ebay_query','notes'];

  const labelMap = {name:'Name',subtitle:'Subtitle / Set',series:'Series',year:'Year',
    item_type:'Type',condition:'Condition',manufacturer:'Manufacturer',
    card_number:'Card Number',platform:'Platform',genre:'Genre',region:'Region',
    artist:'Artist',label:'Label',format:'Format',pressing:'Pressing',
    kit_type:'Kit Type',size:'Size',signed:'Signed',price_paid:'Paid (£)',
    ebay_query:'eBay Search Query',notes:'Notes',numbered:'Numbered',autograph:'Autograph',parallel:'Parallel'};

  fields.innerHTML = editableKeys.map(k => {
    // For ebay_query, pre-fill the input with the auto-built query when no
    // override is set, so the user can edit it directly rather than typing
    // from scratch. saveEdit only writes ebay_query if it actually differs
    // from the current value, so this doesn't unintentionally pin queries.
    const val = (k === 'ebay_query' && !item.ebay_query)
      ? buildQuery(item)
      : (item[k] || '');
    const hint = k === 'ebay_query'
      ? '<div style="font-family:var(--mono);font-size:8px;color:var(--ink3);margin-top:3px;letter-spacing:.04em">Edit to override the auto-generated query, or pick a candidate above to set automatically.</div>'
      : '';
    const isTextarea = k === 'notes' || k === 'ebay_query';
    return `<div>
      <label style="font-family:var(--mono);font-size:8px;letter-spacing:.14em;text-transform:uppercase;color:var(--ink3);display:block;margin-bottom:4px">${labelMap[k]||k}</label>
      ${isTextarea
        ? `<textarea id="ef_${k}" rows="${k==='notes'?3:2}"
            style="width:100%;padding:8px 12px;background:var(--surface2);border:1px solid var(--border);border-radius:var(--radius-md);font-family:var(--font);font-size:13px;color:var(--ink);outline:none;transition:border-color .15s;resize:vertical"
            onfocus="this.style.borderColor='rgba(200,255,0,.35)'"
            onblur="this.style.borderColor=''">${esc(val)}</textarea>`
        : `<input id="ef_${k}" type="${k==='price_paid'?'number':'text'}" value="${esc(val)}"
            style="width:100%;height:36px;padding:0 12px;background:var(--surface2);border:1px solid var(--border);border-radius:var(--radius-md);font-family:var(--font);font-size:13px;color:var(--ink);outline:none;transition:border-color .15s"
            onfocus="this.style.borderColor='rgba(200,255,0,.35)'"
            onblur="this.style.borderColor=''"
          >`}
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
  const savedId = editItemId;

  const editableKeys = ['name','subtitle','series','year','item_type','condition','manufacturer',
    'card_number','parallel','numbered','autograph','platform','genre','region','artist','label','format','pressing',
    'kit_type','size','signed','price_paid','ebay_query','notes'];

  // Only send fields the user actually changed or non-empty fields,
  // and only assign back values that are present so we never clobber
  // an existing value (e.g. price_paid) with an empty string from a
  // pre-populated input the user didn't touch.
  const updates = {};
  editableKeys.forEach(k => {
    const el = document.getElementById('ef_'+k);
    if (!el) return;
    const newVal = el.value;
    const oldVal = (item[k] == null ? '' : String(item[k]));
    // If the input is empty and there was an existing value, skip it
    // (don't overwrite). If user genuinely wants to clear a field they
    // can use the edit endpoint directly — common UX pattern.
    if (newVal === '' && oldVal !== '') return;
    if (newVal !== oldVal) updates[k] = newVal;
  });

  // If a new eBay candidate was picked in the picker, save its image URL
  // into the thumbnail column. This bypasses the auto-scrape on next view
  // and locks the picture to the user's choice. Also include it even if
  // it's the only change — the early-return-when-nothing-changed check
  // below sees it and won't bail.
  if (pendingChosenImage && pendingChosenImage !== item.thumbnail) {
    updates.thumbnail = pendingChosenImage;
  }

  // If nothing changed, just close edit and reopen view modal
  if (Object.keys(updates).length === 0) {
    closeEdit();
    openModal(savedId);
    return;
  }

  try {
    const fd = new FormData();
    fd.append('action', 'update');
    fd.append('item_id', savedId);
    fd.append('updates', JSON.stringify(updates));
    const resp = await fetch('/api.php', {method:'POST', body:fd, credentials:'same-origin'});
    const d = await resp.json();
    if (d.ok) {
      // Detect whether the effective eBay search query changed. This is
      // either a direct edit to ebay_query, or — if no custom ebay_query
      // is in use — any change to the fields buildQuery() relies on.
      const oldSearch = searchQuery(item);
      Object.assign(item, updates);
      const newSearch = searchQuery(item);
      const searchChanged = oldSearch !== newSearch;

      closeEdit();
      filterItems();
      showToast('Item updated');
      // Re-open the view modal so the user sees the updated card
      // (including unchanged price data which lives in priceData[])
      openModal(savedId);

      // Search query changed → invalidate the cached image and refetch
      // with refresh=1 so the server re-scrapes eBay using the new
      // query. Also refresh the price for the same reason.
      if (searchChanged) {
        delete imageCache[savedId];
        refreshItemImage(savedId, newSearch, item.category, item.name);
        refreshSinglePrice(savedId);
      }
    } else {
      showToast(d.error || 'Update failed');
    }
  } catch(e) {
    showToast('Update failed');
  }
}

// Force-refetch the image for a single item, bypassing the server cache.
async function refreshItemImage(id, query, cat, alt) {
  if (!id || !query) return;
  try {
    const resp = await fetch('/api.php?' + new URLSearchParams({
      action: 'getImage', id, query, cat: cat || '', refresh: '1'
    }), { credentials: 'same-origin' });
    const d = await resp.json();
    if (d.url) {
      imageCache[id] = d.url;
      // Update the modal hero if it's currently showing this item
      if (currentModalId === id) {
        document.getElementById('modalImg').src = d.url;
      }
      // Re-render the grid so cards pick up the new cached URL inline.
      // imageCache is consulted by renderGrid/renderList, so this swaps
      // the placeholder (or the old image) for the new one.
      filterItems();
    }
  } catch (e) {}
}
function showToast(msg){const el=document.getElementById('toast');el.textContent=msg;el.classList.add('show');clearTimeout(toastT);toastT=setTimeout(()=>el.classList.remove('show'),2800);}
</script>
</body>
</html>
