<?php
ob_start();
ini_set('session.cookie_httponly',1); ini_set('session.cookie_secure',1); ini_set('session.cookie_samesite','Lax');
session_start();
if (!isset($_SESSION['user'])) { header('Location: index.php'); exit; }
$username = htmlspecialchars($_SESSION['user']);
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8"/>
<meta name="theme-color" content="#111111" media="(prefers-color-scheme: dark)">
<meta name="theme-color" content="#F4F3F1" media="(prefers-color-scheme: light)">
<meta name="viewport" content="width=device-width,initial-scale=1.0,viewport-fit=cover"/>
<meta name="mobile-web-app-capable" content="yes"/>
<title>CollectorVault — Collection</title>
<link rel="preconnect" href="https://fonts.googleapis.com">
<link href="https://fonts.googleapis.com/css2?family=Geist+Mono:wght@300;400;500&family=Geist:wght@300;400;500;600&display=swap" rel="stylesheet">
<?php include 'theme.php'; ?>
<link rel="stylesheet" href="shared.css?v=1776987593">
<style>
/* ── HERO ──────────────────────────────────────────────────────────────────── */
.hero {
  background: rgba(10,10,10,.45);
  backdrop-filter: blur(20px) saturate(1.2);
  -webkit-backdrop-filter: blur(20px) saturate(1.2);
  padding: 20px 16px 16px;
  border-bottom: 1px solid rgba(255,255,255,.10);
}
.hero-inner { max-width: 1200px; margin: 0 auto; }
.hero-label {
  font-family: var(--font-mono); font-size: 9px;
  letter-spacing: .12em; text-transform: uppercase;
  color: rgba(245,245,245,.35); margin-bottom: 6px;
}
.hero-title {
  font-family: var(--font-sans); font-size: 26px; font-weight: 500;
  color: #F5F5F5; margin-bottom: 20px; letter-spacing: -.03em;
}

/* ── BENTO STAT GRID ── */
.stats-bento {
  display: grid;
  grid-template-columns: 1fr 1fr;
  gap: 8px;
}
.stat-card {
  background: rgba(255,255,255,.06);
  border: 1px solid rgba(255,255,255,.1);
  border-radius: 10px;
  padding: 14px 16px;
  backdrop-filter: blur(8px);
  -webkit-backdrop-filter: blur(8px);
  transition: background .2s;
  position: relative;
  overflow: hidden;
}
.stat-card::before {
  content: '';
  position: absolute;
  inset: 0;
  background: linear-gradient(135deg, rgba(255,255,255,.04) 0%, transparent 60%);
  pointer-events: none;
}
.stat-card:hover { background: rgba(255,255,255,.09); }
.stat-card.stat-gain-pos { border-color: rgba(45,160,92,.3); background: rgba(45,160,92,.08); }
.stat-card.stat-gain-neg { border-color: rgba(224,64,53,.3); background: rgba(224,64,53,.08); }
.stat-n {
  font-family: var(--font-mono);
  font-size: 26px; font-weight: 400;
  color: #F5F5F5; line-height: 1;
  letter-spacing: -.02em;
  display: block;
}
.stat-l {
  font-family: var(--font-mono); font-size: 8px;
  letter-spacing: .1em; text-transform: uppercase;
  color: rgba(245,245,245,.4); margin-top: 6px;
  display: block;
}

/* ── FLOATING PILL CATEGORY NAV ─────────────────────────────────────────────── */
.cat-tabs {
  padding: 8px 12px;
  display: flex; overflow-x: auto; gap: 6px;
  position: sticky; top: var(--nav-h, 52px); z-index: 90;
  -webkit-overflow-scrolling: touch; scrollbar-width: none;
  background: rgba(244,243,241,.55);
  backdrop-filter: blur(20px) saturate(1.2);
  -webkit-backdrop-filter: blur(20px) saturate(1.2);
  border-bottom: 1px solid rgba(255,255,255,.22);
}
[data-theme="dark"] .cat-tabs,
html[data-theme="dark"] .cat-tabs {
  background: rgba(10,10,10,.65);
  border-bottom: 1px solid rgba(255,255,255,.08);
}
[data-theme="dark"] .cat-tabs {
  background: rgba(12,11,9,.85);
  border-bottom-color: rgba(46,44,40,.8);
}
.cat-tabs::-webkit-scrollbar { display: none; }
.cat-tab {
  display: inline-flex; align-items: center; gap: 5px;
  padding: 0 12px; height: 30px;
  font-family: var(--font-mono); font-size: 9px;
  letter-spacing: .06em; text-transform: uppercase;
  cursor: pointer; border: none; white-space: nowrap;
  border-radius: 20px; flex-shrink: 0;
  transition: background .15s, color .15s, transform .1s;
  -webkit-tap-highlight-color: transparent;
  background: transparent; color: var(--ink3);
}
.cat-tab svg { width: 12px; height: 12px; stroke: currentColor; fill: none; stroke-width: 1.5; }
.cat-tab:hover { background: var(--surface2); color: var(--ink); transform: translateY(-1px); }
.cat-tab.active {
  background: var(--ink); color: var(--surface);
  box-shadow: 0 2px 8px rgba(14,13,11,.2);
}
.cat-tab:active { transform: scale(.96); }
.ct-count {
  background: rgba(255,255,255,.2); border-radius: 20px;
  padding: 1px 5px; font-size: 8px;
}
.cat-tab.active .ct-count { background: rgba(255,255,255,.18); color: rgba(245,245,245,.8); }

/* ── CONTROLS ──────────────────────────────────────────────────────────────── */
.controls-bar {
  background: rgba(244,243,241,.55);
  backdrop-filter: blur(20px) saturate(1.2);
  -webkit-backdrop-filter: blur(20px) saturate(1.2);
  border-bottom: 1px solid rgba(255,255,255,.20);
  padding: 8px 12px; display: flex; align-items: center; gap: 6px;
}
[data-theme="dark"] .controls-bar,
html[data-theme="dark"] .controls-bar {
  background: rgba(10,10,10,.60);
  border-bottom: 1px solid rgba(255,255,255,.07);
}
.search-wrap { position: relative; flex: 1; min-width: 0; }
.search-wrap::before {
  content:''; position:absolute; left:10px; top:50%; transform:translateY(-50%);
  width:13px; height:13px;
  background: no-repeat center/contain url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 24 24' fill='none' stroke='%238C8880' stroke-width='1.5'%3E%3Ccircle cx='11' cy='11' r='8'/%3E%3Cpath d='m21 21-4.35-4.35'/%3E%3C/svg%3E");
  pointer-events: none;
}
.search-wrap input {
  width:100%; padding:7px 10px 7px 28px;
  background: rgba(255,255,255,.22);
  border: 1px solid rgba(255,255,255,.30);
  border-radius:var(--radius); font-family:var(--font-sans);
  font-size:13px; color:var(--ink);
}
[data-theme="dark"] .search-wrap input,
html[data-theme="dark"] .search-wrap input {
  background: rgba(255,255,255,.08);
  border: 1px solid rgba(255,255,255,.14);
}
.search-wrap input::placeholder { color:var(--ink3); }
.search-wrap input:focus { outline:none; border-color:var(--ink); }
.sort-select {
  background: rgba(255,255,255,.22);
  border: 1px solid rgba(255,255,255,.30);
  border-radius:var(--radius); padding:7px 8px;
  font-family:var(--font-mono); font-size:9px; letter-spacing:.04em;
  color:var(--ink); cursor:pointer; flex-shrink:0;
}
[data-theme="dark"] .sort-select,
html[data-theme="dark"] .sort-select {
  background: rgba(255,255,255,.08);
  border: 1px solid rgba(255,255,255,.14);
}
.view-toggle {
  display:flex; border:1px solid var(--border);
  border-radius:var(--radius); overflow:hidden; flex-shrink:0;
}
.vt-btn {
  padding:6px 9px;
  background: rgba(255,255,255,.22);
  border:none;
  cursor:pointer; color:var(--ink2); transition:all .15s;
  -webkit-tap-highlight-color:transparent;
  display:flex; align-items:center; justify-content:center;
}
[data-theme="dark"] .vt-btn,
html[data-theme="dark"] .vt-btn {
  background: rgba(255,255,255,.08);
  color: var(--ink3);
}
.vt-btn svg { width:13px; height:13px; stroke:currentColor; fill:none; stroke-width:1.5; }
.vt-btn.active { background: rgba(255,255,255,.88); color:#111; }
[data-theme="dark"] .vt-btn.active,
html[data-theme="dark"] .vt-btn.active {
  background: rgba(255,255,255,.22); color:#fff;
}

/* ── REFRESH BAR ──────────────────────────────────────────────────────────── */
.refresh-bar {
  background: rgba(244,243,241,.45);
  backdrop-filter: blur(16px);
  -webkit-backdrop-filter: blur(16px);
  border-bottom: 1px solid rgba(255,255,255,.15);
  padding:5px 12px; display:flex; align-items:center; gap:8px;
  font-family:var(--font-mono); font-size:9px; color:var(--ink3);
}
.refresh-bar .last-updated { flex:1; }
.refresh-btn {
  background:var(--ink); color:var(--surface); border:none;
  border-radius:var(--radius); padding:5px 10px;
  font-family:var(--font-mono); font-size:9px; letter-spacing:.06em;
  text-transform:uppercase; cursor:pointer; display:flex; align-items:center; gap:4px;
  white-space:nowrap; flex-shrink:0; -webkit-tap-highlight-color:transparent;
}
.refresh-btn svg { width:11px; height:11px; stroke:currentColor; fill:none; stroke-width:1.5; }
.refresh-btn.spinning svg { animation:spin360 .8s linear infinite; }
@keyframes spin360 { to{transform:rotate(360deg)} }

/* ── BODY ──────────────────────────────────────────────────────────────────── */
.col-body { max-width:1200px; margin:0 auto; padding:12px 12px 100px; background:transparent; }
.items-grid { display:grid; grid-template-columns:repeat(2,1fr); gap:8px; }
/* First card in bento grid spans full width */
.items-grid .item-card:first-child { grid-column: 1 / -1; }
.items-grid .item-card:first-child .ic-image { height:220px; }
.items-list { display:flex; flex-direction:column; gap:6px; }

/* ── CARD — full-bleed image with gradient overlay ─────────────────────────── */
.item-card {
  background:var(--surface2); border:1px solid var(--border);
  border-radius:var(--radius-lg); overflow:hidden; cursor:pointer;
  transition:transform .2s cubic-bezier(.34,1.2,.64,1), box-shadow .2s, border-color .15s;
  -webkit-tap-highlight-color:transparent;
  position:relative;
}
.item-card:hover { transform:translateY(-4px); box-shadow:0 14px 40px rgba(14,13,11,.16); border-color:transparent; }
.item-card:active { transform:scale(.97); }

/* Full-bleed image wrapper — the whole card is the image */
.ic-image {
  width:100%; height:180px; background:var(--surface2);
  position:relative; overflow:hidden;
  display:flex; align-items:center; justify-content:center;
}
.ic-image img { width:100%; height:100%; object-fit:cover; display:block; }
.ic-image .fallback-icon {
  display:flex; align-items:center; justify-content:center;
  width:44px; height:44px; opacity:.2; color:var(--ink3);
}
.ic-image .fallback-icon svg { width:100%; height:100%; stroke:currentColor; fill:none; stroke-width:1; }

/* Gradient overlay — content lives here */
.ic-overlay {
  position:absolute; bottom:0; left:0; right:0;
  background:linear-gradient(to top, rgba(10,9,8,.92) 0%, rgba(10,9,8,.5) 50%, transparent 100%);
  padding:36px 10px 10px;
}
.ic-cat-tag {
  position:absolute; top:8px; left:8px;
  background:rgba(14,13,11,.6);
  backdrop-filter:blur(6px); -webkit-backdrop-filter:blur(6px);
  color:rgba(240,237,231,.85);
  font-family:var(--font-mono); font-size:7px;
  letter-spacing:.1em; text-transform:uppercase;
  padding:3px 7px; border-radius:20px;
  border:1px solid rgba(255,255,255,.12);
}
.ic-name { font-weight:500; font-size:12px; color:#F0EDE7; white-space:nowrap; overflow:hidden; text-overflow:ellipsis; line-height:1.3; }
.ic-sub  { font-size:10px; color:rgba(240,237,231,.55); margin-top:1px; white-space:nowrap; overflow:hidden; text-overflow:ellipsis; }
.ic-foot { display:flex; align-items:center; justify-content:space-between; gap:4px; margin-top:5px; }
.ic-value { font-family:var(--font-mono); font-size:12px; font-weight:600; color:#F0EDE7; white-space:nowrap; }

/* Delete — top right corner on hover */
.ic-del-btn {
  position:absolute; top:8px; right:8px;
  background:rgba(14,13,11,.55);
  backdrop-filter:blur(6px); -webkit-backdrop-filter:blur(6px);
  border:1px solid rgba(255,255,255,.12);
  border-radius:50%; width:26px; height:26px;
  display:flex; align-items:center; justify-content:center;
  color:rgba(240,237,231,.7); cursor:pointer; font-size:14px;
  opacity:0; transition:opacity .15s, background .15s;
  -webkit-tap-highlight-color:transparent; line-height:1;
}
.item-card:hover .ic-del-btn { opacity:1; }
.ic-del-btn:hover { background:rgba(193,53,40,.7); color:#fff; border-color:transparent; }

/* Image refresh — bottom right */
.img-refresh-btn {
  position:absolute; bottom:44px; right:8px;
  background:rgba(14,13,11,.55); color:rgba(240,237,231,.7); border:none;
  border-radius:20px; padding:3px 7px; font-size:8px;
  font-family:var(--font-mono); cursor:pointer;
  opacity:0; transition:opacity .15s;
  -webkit-tap-highlight-color:transparent;
  display:flex; align-items:center; gap:3px;
  backdrop-filter:blur(4px); -webkit-backdrop-filter:blur(4px);
}
.img-refresh-btn svg { width:9px; height:9px; stroke:currentColor; fill:none; stroke-width:1.5; }
.item-card:hover .img-refresh-btn { opacity:1; }

/* Keep .ic-body hidden — all content is in overlay now */
.ic-body { display:none; }

/* ── PRICE BADGES ─────────────────────────────────────────────────────────── */
.price-badge {
  display:inline-flex; align-items:center; gap:3px;
  padding:3px 8px; border-radius:20px;
  font-family:var(--font-mono); font-size:10px; font-weight:600; white-space:nowrap;
}
.price-badge svg { width:10px; height:10px; stroke:currentColor; fill:none; stroke-width:2.5; }
.price-badge.up   { background:rgba(26,102,64,.1);  color:var(--green); }
.price-badge.down { background:rgba(193,53,40,.1);  color:var(--red); }
.price-badge.flat { background:var(--surface2); color:var(--ink3); }

/* ── SHIMMER ──────────────────────────────────────────────────────────────── */
/* Shimmer: animate background directly — no ::after overlay stacking issues */
@keyframes shimmer {
  0%   { background-color: var(--surface2); }
  50%  { background-color: var(--border); }
  100% { background-color: var(--surface2); }
}
.ic-image.loading { animation: shimmer 1.4s ease-in-out infinite; }
.ic-image:not(.loading) { animation: none; }

/* ── SWIPE TO DELETE ── */
.item-card {
  position: relative;
  touch-action: pan-y; /* allow vertical scroll, intercept horizontal */
}
.swipe-delete-zone {
  position:absolute; top:0; right:0; bottom:0;
  width:72px; background:var(--red);
  display:flex; align-items:center; justify-content:center;
  flex-direction:column; gap:4px;
  color:#fff; font-family:var(--font-mono);
  font-size:8px; letter-spacing:.06em; text-transform:uppercase;
  opacity:0; transition:opacity .15s;
  border-radius:0 var(--radius-lg) var(--radius-lg) 0;
  pointer-events:none;
}
.swipe-delete-zone svg { width:16px; height:16px; stroke:#fff; fill:none; stroke-width:1.5; }
.item-card.swiping .swipe-delete-zone { opacity:1; pointer-events:auto; }
.ic-swipe-content {
  transition: transform .2s ease;
}
.item-card.swiping .ic-swipe-content {
  transform: translateX(-72px);
}

/* ── MICRO-ANIMATIONS ── */
/* Card entrance — staggered fade-up */
@keyframes cardIn {
  from { opacity: 0; transform: translateY(12px); }
  to   { opacity: 1; transform: translateY(0); }
}
.item-card {
  animation: cardIn .28s ease both;
}
/* Stagger via nth-child */
.item-card:nth-child(1)  { animation-delay: .03s; }
.item-card:nth-child(2)  { animation-delay: .06s; }
.item-card:nth-child(3)  { animation-delay: .09s; }
.item-card:nth-child(4)  { animation-delay: .12s; }
.item-card:nth-child(5)  { animation-delay: .15s; }
.item-card:nth-child(6)  { animation-delay: .18s; }
.item-card:nth-child(7)  { animation-delay: .21s; }
.item-card:nth-child(8)  { animation-delay: .24s; }
.item-card:nth-child(n+9){ animation-delay: .27s; }

/* List row entrance */
@keyframes rowIn {
  from { opacity: 0; transform: translateX(-8px); }
  to   { opacity: 1; transform: translateX(0); }
}
.item-row {
  animation: rowIn .22s ease both;
}
.item-row:nth-child(1)  { animation-delay: .03s; }
.item-row:nth-child(2)  { animation-delay: .06s; }
.item-row:nth-child(3)  { animation-delay: .09s; }
.item-row:nth-child(4)  { animation-delay: .12s; }
.item-row:nth-child(5)  { animation-delay: .15s; }
.item-row:nth-child(n+6){ animation-delay: .18s; }

/* Image fade in on load */
.ic-image img {
  animation: imgFadeIn .3s ease;
}
@keyframes imgFadeIn {
  from { opacity: 0; }
  to   { opacity: 1; }
}

/* Button press — solid button */
.btn-solid:active { transform: scale(.96); }
.btn-sm:active    { transform: scale(.96); }

/* Stat card number count-up hint — pulse once on load */
@keyframes statPop {
  0%   { transform: scale(.9); opacity: 0; }
  60%  { transform: scale(1.04); }
  100% { transform: scale(1); opacity: 1; }
}
.stat-n.loaded { animation: statPop .4s cubic-bezier(.34,1.4,.64,1) both; }

/* ── LIST ROW ──────────────────────────────────────────────────────────────── */
.item-row {
  background:var(--surface); border:1px solid var(--border);
  border-radius:var(--radius-lg); padding:12px 14px;
  display:flex; align-items:center; gap:12px; cursor:pointer;
  transition:transform .15s, box-shadow .15s, border-color .15s; -webkit-tap-highlight-color:transparent;
}
.item-row:hover { border-color:var(--ink2); transform:translateX(2px); box-shadow:var(--shadow); }
.ir-thumb {
  width:52px; height:52px; border-radius:var(--radius);
  background:var(--surface2); flex-shrink:0;
  display:flex; align-items:center; justify-content:center; overflow:hidden;
}
.ir-thumb img { width:100%; height:100%; object-fit:cover; }
.ir-thumb svg { width:20px; height:20px; stroke:var(--ink3); fill:none; stroke-width:1.5; opacity:.5; }
.ir-info { flex:1; min-width:0; }
.ir-name { font-weight:500; font-size:13px; color:var(--ink); white-space:nowrap; overflow:hidden; text-overflow:ellipsis; }
.ir-sub  { font-size:11px; color:var(--ink3); }
.ir-type { background:var(--surface2); border:1px solid var(--border); border-radius:3px; padding:1px 5px; font-family:var(--font-mono); font-size:8px; color:var(--ink2); margin-top:2px; display:inline-block; }
.ir-right { text-align:right; flex-shrink:0; }
.ir-value { font-family:var(--font-mono); font-size:13px; font-weight:600; color:var(--ink); }
.ir-date  { font-size:9px; color:var(--ink3); font-family:var(--font-mono); margin-top:2px; }
.ir-del { background:transparent; border:none; color:var(--border); font-size:18px; cursor:pointer; padding:4px 6px; transition:color .15s; flex-shrink:0; }
.ir-del:hover { color:var(--red); }

/* ── EMPTY ──────────────────────────────────────────────────────────────────── */
.empty-col {
  text-align:center; padding:80px 20px;
  grid-column:1/-1;
  display:flex; flex-direction:column; align-items:center;
}
@keyframes emptyFloat {
  0%,100% { transform: translateY(0); }
  50%     { transform: translateY(-6px); }
}
.ec-icon {
  width:72px; height:72px;
  background: linear-gradient(135deg, var(--surface2) 0%, var(--border) 100%);
  border:1px solid var(--border); border-radius:20px;
  display:flex; align-items:center; justify-content:center;
  margin-bottom:24px;
  animation: emptyFloat 3s ease-in-out infinite;
  box-shadow: 0 8px 24px rgba(14,13,11,.06);
}
.ec-icon svg { width:30px; height:30px; stroke:var(--ink3); fill:none; stroke-width:1.5; }
.ec-title {
  font-family:var(--font-sans); font-size:20px; font-weight:500;
  margin-bottom:10px; letter-spacing:-.02em; color:var(--ink);
}
.ec-sub {
  font-size:13px; color:var(--ink3);
  margin-bottom:28px; line-height:1.75; max-width:240px;
}
.ec-cta {
  display:inline-flex; align-items:center; gap:6px;
  background:var(--ink); color:var(--surface);
  font-family:var(--font-mono); font-size:10px;
  letter-spacing:.06em; text-transform:uppercase;
  padding:10px 20px; border-radius:var(--radius);
  text-decoration:none; transition:opacity .15s, transform .15s;
}
.ec-cta:hover { opacity:.85; transform:translateY(-1px); }
.ec-cta:visited { color:var(--surface); }
.ec-cta svg { width:12px; height:12px; stroke:currentColor; fill:none; stroke-width:2; }

/* ── MODAL ──────────────────────────────────────────────────────────────────── */
.modal-bg {
  display:none; position:fixed; inset:0;
  background:rgba(14,13,11,.6);
  backdrop-filter: blur(12px);
  -webkit-backdrop-filter: blur(12px);
  z-index:300;
  align-items:flex-end; justify-content:center;
  transition: opacity .2s;
}
.modal-bg.open { display:flex; }

/* Animate the overlay backdrop */
@keyframes overlayIn { from{opacity:0} to{opacity:1} }
.modal-bg.open { animation: overlayIn .2s ease; }

.modal {
  background: var(--surface);
  width:100%; max-width:560px;
  border-radius:20px 20px 0 0;
  border:1px solid var(--border);
  border-bottom:none;
  max-height:88dvh;
  overflow-y:auto;
  animation:sheetUp .32s cubic-bezier(.22,1,.36,1);
  overscroll-behavior:contain;
  -webkit-overflow-scrolling:touch;
  position:relative;
  /* Subtle inner glow on top edge */
  box-shadow: 0 -4px 40px rgba(14,13,11,.12), inset 0 1px 0 rgba(255,255,255,.08);
}
@keyframes sheetUp { from{transform:translateY(60px);opacity:0} to{transform:translateY(0);opacity:1} }
.modal-handle {
  width:40px; height:4px;
  background:var(--border); border-radius:2px;
  margin:12px auto 0;
  transition: background .2s;
}
.modal:hover .modal-handle { background: var(--ink3); }

.modal-img {
  width:100%; height:200px;
  background:var(--surface2);
  display:flex; align-items:center; justify-content:center;
  overflow:hidden; position:relative; flex-shrink:0;
}
.modal-img img { width:100%; height:100%; object-fit:cover; display:block; }
.modal-img .big-icon { width:52px; height:52px; }
.modal-img .big-icon svg { width:100%; height:100%; stroke:var(--ink3); fill:none; stroke-width:1; opacity:.3; }
.modal-close {
  position:absolute; top:12px; right:12px;
  background:rgba(14,13,11,.5);
  backdrop-filter: blur(8px); -webkit-backdrop-filter: blur(8px);
  color:#fff; border: 1px solid rgba(255,255,255,.15);
  border-radius:50%; width:30px; height:30px; font-size:16px;
  cursor:pointer; display:flex; align-items:center; justify-content:center;
  -webkit-tap-highlight-color:transparent; z-index:1; line-height:1;
  transition: background .15s, transform .15s;
}
.modal-close:hover { background:rgba(14,13,11,.7); transform:scale(1.08); }
/* Tap-to-zoom overlay */
.modal-img { cursor:zoom-in; }
.img-zoom-overlay {
  position:fixed; inset:0; z-index:900;
  background:rgba(0,0,0,.95);
  display:flex; align-items:center; justify-content:center;
  cursor:zoom-out;
  animation:overlayIn .18s ease;
}
.img-zoom-overlay img {
  max-width:96vw; max-height:96vh;
  object-fit:contain; border-radius:4px;
  animation:zoomIn .2s cubic-bezier(.34,1.2,.64,1);
}
@keyframes zoomIn { from{transform:scale(.88)} to{transform:scale(1)} }

.modal-body { padding:20px 20px 44px; }
.modal-name { font-family:var(--font-sans); font-size:22px; font-weight:500; color:var(--ink); margin-bottom:4px; letter-spacing:-.02em; }
.modal-sub  { font-family:var(--font-mono); font-size:10px; color:var(--ink3); margin-bottom:18px; letter-spacing:.04em; }

.detail-grid { display:grid; grid-template-columns:1fr 1fr; gap:8px; margin-bottom:14px; }
.detail-item {
  background:var(--bg); border:1px solid var(--border);
  border-radius:var(--radius); padding:10px 12px;
  transition: border-color .15s;
}
.detail-item:hover { border-color: var(--ink3); }
.detail-item label { font-family:var(--font-mono); font-size:8px; letter-spacing:.1em; text-transform:uppercase; color:var(--ink3); display:block; margin-bottom:4px; }
.detail-item span { font-size:13px; font-weight:400; color:var(--ink); font-family:var(--font-sans); }

.ebay-block {
  background:var(--bg); border:1px solid var(--border);
  border-radius:var(--radius-lg); padding:14px 16px; margin-top:4px;
}
.ebay-title { font-family:var(--font-mono); font-size:8px; letter-spacing:.1em; text-transform:uppercase; color:var(--ink3); margin-bottom:12px; }
.ebay-stats { display:grid; grid-template-columns:1fr 1fr 1fr; gap:8px; }
.ebay-stat {
  background: var(--surface); border: 1px solid var(--border);
  border-radius: var(--radius); padding: 10px 12px;
}
.ebay-stat label { font-family:var(--font-mono); font-size:8px; letter-spacing:.06em; text-transform:uppercase; color:var(--ink3); display:block; margin-bottom:4px; }
.ebay-stat span { font-family:var(--font-mono); font-size:16px; font-weight:500; color:var(--ink); }
.ebay-vs { font-family:var(--font-mono); font-size:9px; color:var(--ink3); margin-top:10px; padding-top:10px; border-top:1px solid var(--border); display:flex; align-items:center; gap:6px; }
.ebay-vs-bar { flex:1; height:4px; background:var(--surface2); border-radius:2px; overflow:hidden; }
.ebay-vs-fill { height:100%; background:var(--green); border-radius:2px; transition:width .6s ease; }
.ebay-vs-fill.down { background:var(--red); }
.ebay-trend { display:flex; align-items:center; gap:8px; flex-wrap:wrap; margin-top:12px; padding-top:12px; border-top:1px solid var(--border); font-size:11px; color:var(--ink3); }
.ebay-refresh {
  margin-left:auto; background:transparent;
  border:1px solid var(--border); border-radius:20px;
  padding:4px 10px; font-family:var(--font-mono); font-size:8px;
  cursor:pointer; color:var(--ink3);
  transition: border-color .15s, color .15s;
  letter-spacing:.04em;
}
.ebay-refresh:hover { border-color:var(--ink); color:var(--ink); }

/* ── DARK MODE GLASSMORPHISM ── */
[data-theme="dark"] .modal-bg {
  background: rgba(0,0,0,.55);
}
[data-theme="dark"] .modal {
  background: rgba(18,16,13,.85);
  backdrop-filter: blur(32px) saturate(160%);
  -webkit-backdrop-filter: blur(32px) saturate(160%);
  border-color: rgba(255,255,255,.08);
  box-shadow: 0 -4px 40px rgba(0,0,0,.4), inset 0 1px 0 rgba(255,255,255,.06);
}
[data-theme="dark"] .detail-item { background: rgba(255,255,255,.04); border-color: rgba(255,255,255,.07); }
[data-theme="dark"] .ebay-block { background: rgba(255,255,255,.03); border-color: rgba(255,255,255,.07); }
[data-theme="dark"] .ebay-stat { background: rgba(255,255,255,.05); border-color: rgba(255,255,255,.07); }

/* ── RESPONSIVE ───────────────────────────────────────────────────────────── */
/* ── DARK MODE DEPTH ── */
[data-theme="dark"] .item-card { background:#1A1816; border-color:#252220; }
[data-theme="dark"] .item-card:hover { border-color:#3A3632; box-shadow:0 14px 40px rgba(0,0,0,.4); }
[data-theme="dark"] .item-row { background:#1A1816; border-color:#252220; }
[data-theme="dark"] .item-row:hover { border-color:#3A3632; }
[data-theme="dark"] .controls-bar { background:#0F0E0C; }
[data-theme="dark"] .col-body .skeleton-card { background:#1A1816; border-color:#252220; }
[data-theme="dark"] .stat-card { background:rgba(255,255,255,.04); border-color:rgba(255,255,255,.08); }

@media (min-width:640px) {
  .hero { padding:32px 24px 24px; }
  .hero-title { font-size:30px; }
  .stats-bento { grid-template-columns: repeat(4, 1fr); gap: 10px; }
  .stat-n { font-size: 30px; }
  .cat-tabs { top:var(--nav-h); padding:0 24px; }
  .controls-bar { padding:8px 24px; }
  .col-body { padding:16px 24px 80px; }
  .refresh-bar { padding:5px 24px; }
  .items-grid { grid-template-columns:repeat(auto-fill,minmax(180px,1fr)); gap:14px; }
}
@media (min-width:960px) {
  .hero { padding:44px 40px 28px; }
  .hero-title { font-size:36px; }
  .stat-n { font-size: 34px; }
  .stat-card { padding: 18px 20px; }
  .cat-tabs { top:var(--nav-h); padding:0 40px; }
  .controls-bar { padding:8px 40px; }
  .col-body { padding:18px 40px 60px; }
  .refresh-bar { padding:5px 40px; }
  .items-grid { grid-template-columns:repeat(auto-fill,minmax(200px,1fr)); gap:16px; }
  /* Desktop: centred modal with fade animation */
  .modal-bg { align-items:center; padding:24px; }
  .modal {
    border-radius:12px;
    border:1px solid var(--border);
    border-bottom:1px solid var(--border);
    max-width:500px;
    max-height:85vh;
    width:100%;
  }
  .modal-handle { display:none; }
  @keyframes sheetUp { from{opacity:0;transform:scale(.96) translateY(8px)} to{opacity:1;transform:scale(1) translateY(0)} }
}

/* ── CATEGORY CAROUSEL ──────────────────────────────────────────────────────── */
#catCarousel {
  display: none;
  flex-direction: column;
  /* Fill exactly the viewport below the nav */
  height: calc(100dvh - var(--nav-h));
  overflow: hidden;
}
#catCarousel.open { display: flex; }

/* Hide main collection content when carousel is open */
body.cat-carousel-open .hero,
body.cat-carousel-open .cat-tabs,
body.cat-carousel-open .controls-bar,
body.cat-carousel-open .refresh-bar,
body.cat-carousel-open .col-body,
body.cat-carousel-open .modal-bg,
body.cat-carousel-open .fab {
  display: none !important;
}

/* Reuse shared glass-scene bokeh + glass-card from shared.css */
#catCarousel .picker-hero {
  position: relative; z-index: 1;
  padding: 20px 20px 14px;
  display: flex; align-items: flex-start;
  justify-content: space-between; gap: 16px; flex-shrink: 0;
}
#catCarousel .picker-hero-left { display: flex; align-items: center; gap: 7px; }
#catCarousel .picker-eyebrow {
  font-family: var(--font-mono); font-size: 10px;
  letter-spacing: .12em; text-transform: uppercase;
}
#catCarousel .picker-eyebrow-icon { display: flex; align-items: center; }
#catCarousel .picker-eyebrow-icon svg { width:11px; height:11px; stroke:currentColor; fill:none; stroke-width:1.5; }
#catCarousel .picker-hero-right {
  font-family: var(--font-sans); font-size: 11px;
  line-height: 1.55; text-align: right; max-width: 180px; flex-shrink: 0;
}
#catCarousel .picker-hero-right strong { font-weight: 500; }

/* Close button — styles handled by shared.css .carousel-close */

/* Carousel track — same as scanner */
#catCarousel .picker-carousel-wrap {
  flex: 1; position: relative; z-index: 1; overflow: hidden;
  margin-left: 40px;
  -webkit-mask-image: linear-gradient(to right, black 84%, transparent 100%);
  mask-image: linear-gradient(to right, black 84%, transparent 100%);
}
#catCarousel .picker-carousel {
  display: flex; gap: 10px; padding: 12px 0 20px 16px;
  overflow-x: auto; scroll-snap-type: x mandatory;
  -webkit-overflow-scrolling: touch; scrollbar-width: none;
  cursor: grab; align-items: flex-start;
}
#catCarousel .picker-carousel:active { cursor: grabbing; }
#catCarousel .picker-carousel::-webkit-scrollbar { display: none; }

#catCarousel .picker-card {
  /* Mobile: show ~1.15 cards so next peeks in */
  flex: 0 0 85vw;
  max-width: none;
  min-width: 200px;
  height: clamp(360px, 62vh, 540px);
  border-radius: 16px; overflow: hidden; position: relative;
  cursor: pointer; scroll-snap-align: start; flex-shrink: 0;
  transition: transform .2s, box-shadow .2s;
  -webkit-tap-highlight-color: transparent;
  animation: colCardIn .45s cubic-bezier(.22,.68,0,1.1) both;
}
#catCarousel .picker-card:nth-child(1) { animation-delay: .04s; }
#catCarousel .picker-card:nth-child(2) { animation-delay: .10s; }
#catCarousel .picker-card:nth-child(3) { animation-delay: .16s; }
#catCarousel .picker-card:nth-child(4) { animation-delay: .22s; }
#catCarousel .picker-card:nth-child(5) { animation-delay: .28s; }
#catCarousel .picker-card:nth-child(6) { animation-delay: .34s; }
@keyframes colCardIn {
  from { opacity:0; transform:translateX(20px) scale(.96); }
  to   { opacity:1; transform:translateX(0) scale(1); }
}
#catCarousel .picker-card:hover { transform: translateY(-3px); }
#catCarousel .picker-card:active { transform: scale(.98); }

/* Reuse shared card internals */
#catCarousel .card-big-icon {
  position: absolute; top: 44%; left: 50%;
  transform: translate(-50%, -60%); width: 50%; pointer-events: none;
}
#catCarousel .card-big-icon svg { width:100%; height:100%; stroke:currentColor; fill:none; stroke-width:.5; }
#catCarousel .card-bg { display: none; }
#catCarousel .card-num {
  position: absolute; top: 16px; left: 16px;
  display: flex; align-items: center; gap: 5px;
  font-family: var(--font-mono); font-size: 9px; letter-spacing: .1em;
}
#catCarousel .card-num-icon { width:10px; height:10px; opacity:.6; flex-shrink:0; }
#catCarousel .card-num-icon svg { width:100%; height:100%; fill:none; stroke-width:1.5; }
#catCarousel .card-foot {
  position: absolute; bottom: 0; left: 0; right: 0; padding: 20px 18px;
}
#catCarousel .card-name {
  font-family: var(--font-sans); font-size: 17px; font-weight: 500;
  letter-spacing: -.01em; margin-bottom: 5px;
}
#catCarousel .card-count-label {
  font-family: var(--font-mono); font-size: 10px; opacity: .55; margin-bottom: 2px;
}
#catCarousel .card-value-label {
  font-family: var(--font-mono); font-size: 13px; font-weight: 500;
  color: rgba(245,245,245,.85); letter-spacing: -.01em;
  margin-top: 6px;
}
#catCarousel .card-arrow {
  position: absolute; top: 14px; right: 14px;
  width: 24px; height: 24px; border-radius: 50%;
  display: flex; align-items: center; justify-content: center;
  font-size: 11px; transition: background .18s, color .18s, transform .18s;
}
#catCarousel .picker-card:hover .card-arrow { transform: translate(1px,-1px); }

/* Dots */
#catCarousel .picker-dots {
  position: relative; z-index: 1;
  display: flex; justify-content: center; gap: 5px;
  padding: 8px 0 16px; flex-shrink: 0;
}
#catCarousel .picker-dot {
  width: 4px; height: 4px; border-radius: 50%;
  transition: background .2s, width .2s; cursor: pointer;
}
#catCarousel .picker-dot.active { width: 16px; border-radius: 2px; }

/* Active filter indicator on the category filter button */
.cat-filter-btn {
  display: inline-flex; align-items: center; gap: 5px;
  padding: 0 12px; height: 30px;
  font-family: var(--font-mono); font-size: 9px;
  letter-spacing: .06em; text-transform: uppercase;
  cursor: pointer; border: 1px solid var(--border);
  border-radius: 20px; flex-shrink: 0;
  background: var(--surface); color: var(--ink3);
  transition: all .15s; -webkit-tap-highlight-color: transparent;
}
.cat-filter-btn:hover { background: var(--surface2); color: var(--ink); }
.cat-filter-btn.filtering { background: var(--ink); color: var(--surface); border-color: var(--ink); }
.cat-filter-btn svg { width:12px; height:12px; stroke:currentColor; fill:none; stroke-width:1.5; }

@media (min-width: 540px) {
  /* Tablet: ~2 cards + peek */
  #catCarousel .picker-card { flex: 0 0 46vw; max-width: none; }
}
@media (min-width: 900px) {
  #catCarousel .picker-hero { padding: 32px 48px 20px; }
  #catCarousel .picker-carousel { padding: 12px 0 28px 0; gap: 14px; }
  /* Desktop: 3 cards + ~15% peek of 4th */
  #catCarousel .picker-card {
    flex: 0 0 calc((100vw - 48px - 14px * 2) / 3.15);
    max-width: none;
    min-width: 240px;
    height: clamp(400px, 68vh, 580px);
  }
  #catCarousel .picker-carousel-wrap {
    -webkit-mask-image: linear-gradient(to right, black 86%, transparent 100%);
    mask-image: linear-gradient(to right, black 86%, transparent 100%);
  }
}
@media (min-width: 1400px) {
  #catCarousel .picker-hero { padding: 36px 64px 22px; }
  #catCarousel .picker-carousel { padding: 12px 0 28px 0; gap: 16px; }
  #catCarousel .picker-card {
    flex: 0 0 calc((100vw - 64px - 16px * 2) / 3.15);
    max-width: 480px;
  }
}
</style>
</head>
<body>
<?php include 'nav.php'; ?>

<!-- ── CATEGORY CAROUSEL OVERLAY ──────────────────────────────────────── -->
<div id="catCarousel" class="glass-scene">
  <div class="picker-hero">
    <div class="picker-hero-left">
      <div class="picker-eyebrow-icon">
        <svg viewBox="0 0 24 24"><polygon points="12 2 15.09 8.26 22 9.27 17 14.14 18.18 21.02 12 17.77 5.82 21.02 7 14.14 2 9.27 8.91 8.26 12 2"/></svg>
      </div>
      <div class="picker-eyebrow">Filter by Category</div>
    </div>
    <div class="picker-hero-right">
      <strong>Browse by type.</strong> Tap a card to<br>filter your collection.
    </div>
  </div>
  <button class="carousel-close" onclick="closeCarousel()">×</button>

  <div class="picker-carousel-wrap">
    <div class="picker-carousel" id="colCarousel">
      <div class="picker-card glass-card" data-cat="all" onclick="selectCarouselCat('all')">
        <div class="card-num"><span class="card-num-icon"><svg viewBox="0 0 24 24"><rect x="3" y="3" width="7" height="7" rx="1"/><rect x="14" y="3" width="7" height="7" rx="1"/><rect x="3" y="14" width="7" height="7" rx="1"/><rect x="14" y="14" width="7" height="7" rx="1"/></svg></span>00</div>
        <div class="card-arrow">↗</div>
        <div class="card-foot">
          <div class="card-name">All Items</div>
          <div class="card-count-label" id="cc-all">— items</div>
          <div class="card-value-label" id="cv-all"></div>
        </div>
      </div>
      <div class="picker-card glass-card" data-cat="cards" onclick="selectCarouselCat('cards')">
        <div class="card-num"><span class="card-num-icon"><svg viewBox="0 0 24 24"><rect x="2" y="5" width="20" height="14" rx="2"/><path d="M2 10h20"/></svg></span>01</div>
        <div class="card-arrow">↗</div>
        <div class="card-foot">
          <div class="card-name">Trading Cards</div>
          <div class="card-count-label" id="cc-cards">— items</div>
          <div class="card-value-label" id="cv-cards"></div>
        </div>
      </div>
      <div class="picker-card glass-card" data-cat="shirts" onclick="selectCarouselCat('shirts')">
        <div class="card-num"><span class="card-num-icon"><svg viewBox="0 0 24 24"><path d="M20.38 3.46L16 2a4 4 0 01-8 0L3.62 3.46a2 2 0 00-1.34 2.23l.58 3.57a1 1 0 00.99.84H6v10a2 2 0 002 2h8a2 2 0 002-2V10h2.15a1 1 0 00.99-.84l.58-3.57a2 2 0 00-1.34-2.23z"/></svg></span>02</div>
        <div class="card-arrow">↗</div>
        <div class="card-foot">
          <div class="card-name">Football Shirts</div>
          <div class="card-count-label" id="cc-shirts">— items</div>
          <div class="card-value-label" id="cv-shirts"></div>
        </div>
      </div>
      <div class="picker-card glass-card" data-cat="games" onclick="selectCarouselCat('games')">
        <div class="card-num"><span class="card-num-icon"><svg viewBox="0 0 24 24"><rect x="2" y="6" width="20" height="12" rx="4"/><path d="M6 12h4m-2-2v4M15 11h.01M17 13h.01"/></svg></span>03</div>
        <div class="card-arrow">↗</div>
        <div class="card-foot">
          <div class="card-name">Video Games</div>
          <div class="card-count-label" id="cc-games">— items</div>
          <div class="card-value-label" id="cv-games"></div>
        </div>
      </div>
      <div class="picker-card glass-card" data-cat="vinyl" onclick="selectCarouselCat('vinyl')">
        <div class="card-num"><span class="card-num-icon"><svg viewBox="0 0 24 24"><circle cx="12" cy="12" r="10"/><circle cx="12" cy="12" r="3"/></svg></span>04</div>
        <div class="card-arrow">↗</div>
        <div class="card-foot">
          <div class="card-name">Vinyl &amp; Music</div>
          <div class="card-count-label" id="cc-vinyl">— items</div>
          <div class="card-value-label" id="cv-vinyl"></div>
        </div>
      </div>
      <div class="picker-card glass-card" data-cat="other" onclick="selectCarouselCat('other')">
        <div class="card-num"><span class="card-num-icon"><svg viewBox="0 0 24 24"><path d="M21 16V8a2 2 0 00-1-1.73l-7-4a2 2 0 00-2 0l-7 4A2 2 0 003 8v8a2 2 0 001 1.73l7 4a2 2 0 002 0l7-4A2 2 0 0021 16z"/></svg></span>05</div>
        <div class="card-arrow">↗</div>
        <div class="card-foot">
          <div class="card-name">Other Collectibles</div>
          <div class="card-count-label" id="cc-other">— items</div>
          <div class="card-value-label" id="cv-other"></div>
        </div>
      </div>
    </div>
  </div>

  <div class="picker-dots" id="colCarouselDots">
    <div class="picker-dot active" onclick="scrollColCarouselTo(0)"></div>
    <div class="picker-dot" onclick="scrollColCarouselTo(1)"></div>
    <div class="picker-dot" onclick="scrollColCarouselTo(2)"></div>
    <div class="picker-dot" onclick="scrollColCarouselTo(3)"></div>
    <div class="picker-dot" onclick="scrollColCarouselTo(4)"></div>
    <div class="picker-dot" onclick="scrollColCarouselTo(5)"></div>
  </div>
</div>

<div class="hero">
  <div class="hero-inner">
    <div class="hero-label"><?= $username ?></div>
    <div class="hero-title">My Collection</div>
    <div class="stats-bento">
      <div class="stat-card">
        <span class="stat-n" id="sTotal">—</span>
        <span class="stat-l">Items</span>
      </div>
      <div class="stat-card">
        <span class="stat-n" id="sValue">—</span>
        <span class="stat-l">Market Value</span>
      </div>
      <div class="stat-card">
        <span class="stat-n" id="sInvested">—</span>
        <span class="stat-l">Invested</span>
      </div>
      <div class="stat-card" id="sGainCard">
        <span class="stat-n" id="sGain">—</span>
        <span class="stat-l">Gain / Loss</span>
      </div>
    </div>
  </div>
</div>

<div class="cat-tabs">
  <button class="cat-tab active" data-cat="all"   onclick="setTab('all')">All <span class="ct-count" id="ct-all">0</span></button>
  <button class="cat-tab" data-cat="cards"  onclick="setTab('cards')"><svg viewBox="0 0 24 24"><rect x="2" y="5" width="20" height="14" rx="2"/><path d="M2 10h20"/></svg> Cards <span class="ct-count" id="ct-cards">0</span></button>
  <button class="cat-tab" data-cat="shirts" onclick="setTab('shirts')"><svg viewBox="0 0 24 24"><path d="M20.38 3.46L16 2a4 4 0 01-8 0L3.62 3.46a2 2 0 00-1.34 2.23l.58 3.57a1 1 0 00.99.84H6v10a2 2 0 002 2h8a2 2 0 002-2V10h2.15a1 1 0 00.99-.84l.58-3.57a2 2 0 00-1.34-2.23z"/></svg> Shirts <span class="ct-count" id="ct-shirts">0</span></button>
  <button class="cat-tab" data-cat="games"  onclick="setTab('games')"><svg viewBox="0 0 24 24"><rect x="2" y="6" width="20" height="12" rx="4"/><path d="M6 12h4m-2-2v4M15 11h.01M17 13h.01"/></svg> Games <span class="ct-count" id="ct-games">0</span></button>
  <button class="cat-tab" data-cat="vinyl"  onclick="setTab('vinyl')"><svg viewBox="0 0 24 24"><circle cx="12" cy="12" r="10"/><circle cx="12" cy="12" r="3"/><circle cx="12" cy="12" r="1" fill="currentColor"/></svg> Vinyl <span class="ct-count" id="ct-vinyl">0</span></button>
  <button class="cat-tab" data-cat="other"  onclick="setTab('other')"><svg viewBox="0 0 24 24"><path d="M21 16V8a2 2 0 00-1-1.73l-7-4a2 2 0 00-2 0l-7 4A2 2 0 003 8v8a2 2 0 001 1.73l7 4a2 2 0 002 0l7-4A2 2 0 0021 16z"/></svg> Other <span class="ct-count" id="ct-other">0</span></button>
  <button class="cat-filter-btn" id="browseCarouselBtn" onclick="openCarousel()" title="Browse categories">
    <svg viewBox="0 0 24 24"><rect x="3" y="3" width="7" height="7" rx="1"/><rect x="14" y="3" width="7" height="7" rx="1"/><rect x="3" y="14" width="7" height="7" rx="1"/><rect x="14" y="14" width="7" height="7" rx="1"/></svg>
    Browse
  </button>
</div>

<div class="controls-bar">
  <div class="search-wrap"><input type="text" id="searchInput" placeholder="Search collection…" oninput="debouncedFilter()" autocomplete="off"/></div>
  <select class="sort-select" id="sortSelect" onchange="filterItems()">
    <option value="newest">Newest</option>
    <option value="oldest">Oldest</option>
    <option value="name">A–Z</option>
    <option value="value_desc">Highest Value</option>
  </select>
  <div class="view-toggle">
    <button class="vt-btn active" id="vt-grid" onclick="setView('grid')">
      <svg viewBox="0 0 24 24"><rect x="3" y="3" width="7" height="7" rx="1"/><rect x="14" y="3" width="7" height="7" rx="1"/><rect x="3" y="14" width="7" height="7" rx="1"/><rect x="14" y="14" width="7" height="7" rx="1"/></svg>
    </button>
    <button class="vt-btn" id="vt-list" onclick="setView('list')">
      <svg viewBox="0 0 24 24"><path d="M8 6h13M8 12h13M8 18h13M3 6h.01M3 12h.01M3 18h.01"/></svg>
    </button>
  </div>
  <a href="scanner.php" class="btn-sm btn-solid" style="margin-left:auto;flex-shrink:0">
    <svg viewBox="0 0 24 24"><circle cx="12" cy="12" r="10"/><path d="M12 8v8M8 12h8"/></svg> Scan
  </a>
</div>

<div class="refresh-bar">
  <span class="last-updated" id="lastUpdated">Fetching eBay prices…</span>
  <button class="refresh-btn" id="refreshBtn" onclick="refreshAllPrices()">
    <svg viewBox="0 0 24 24"><path d="M1 4v6h6M23 20v-6h-6"/><path d="M20.49 9A9 9 0 005.64 5.64L1 10M23 14l-4.64 4.36A9 9 0 013.51 15"/></svg>
    <span class="btn-label">Refresh</span>
  </button>
</div>

<div class="col-body">
  <div id="itemsContainer" class="items-grid"></div>
</div>

<div class="modal-bg" id="modalBg" onclick="handleModalBgClick(event)">
  <div class="modal">
    <div class="modal-handle"></div>
    <div class="modal-img" id="modalImg"><button class="modal-close" onclick="closeModal()">×</button></div>
    <div class="modal-body">
      <div class="modal-name" id="modalName"></div>
      <div class="modal-sub"  id="modalSub"></div>
      <div class="detail-grid" id="modalDetails"></div>
      <div id="modalEbay"></div>
    </div>
  </div>
</div>

<div id="toast"></div>
<!-- FAB: mobile-only scan shortcut, hidden ≥640px via shared.css -->
<a href="scanner.php" class="fab" aria-label="Scan new item">
  <svg viewBox="0 0 24 24"><circle cx="12" cy="12" r="10"/><path d="M12 8v8M8 12h8"/></svg>
</a>

<script>
// ── SVG icon map (outline, single-colour) ─────────────────────────────────
const ICONS = {
  cards:  '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5"><rect x="2" y="5" width="20" height="14" rx="2"/><path d="M2 10h20"/></svg>',
  shirts: '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5"><path d="M20.38 3.46L16 2a4 4 0 01-8 0L3.62 3.46a2 2 0 00-1.34 2.23l.58 3.57a1 1 0 00.99.84H6v10a2 2 0 002 2h8a2 2 0 002-2V10h2.15a1 1 0 00.99-.84l.58-3.57a2 2 0 00-1.34-2.23z"/></svg>',
  games:  '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5"><rect x="2" y="6" width="20" height="12" rx="4"/><path d="M6 12h4m-2-2v4M15 11h.01M17 13h.01"/></svg>',
  vinyl:  '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5"><circle cx="12" cy="12" r="10"/><circle cx="12" cy="12" r="3"/></svg>',
  other:  '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5"><path d="M21 16V8a2 2 0 00-1-1.73l-7-4a2 2 0 00-2 0l-7 4A2 2 0 003 8v8a2 2 0 001 1.73l7 4a2 2 0 002 0l7-4A2 2 0 0021 16z"/></svg>',
};
const CAT_LABELS = {cards:'Trading Cards',shirts:'Football Shirts',games:'Video Games',vinyl:'Vinyl & Music',other:'Other',all:'All'};

let allItems = [], priceData = {}, imageCache = {}, currentTab = 'all', currentView = 'grid';

renderSkeletons(); loadAll(); loadStats();

async function loadAll() {
  try {
    const [cr, pr] = await Promise.all([
      fetch('api.php?action=collection&category=all', {credentials:'same-origin'}),
      fetch('api.php?action=getPrices', {credentials:'same-origin'})
    ]);
    const col = await cr.json(), price = await pr.json();
    if (!col.ok) throw new Error(col.error||'Load failed');
    allItems  = col.items;
    priceData = price.ok ? price.prices : {};
    updateCounts(); filterItems();
    autoRefreshPrices();
    setTimeout(loadImagesForVisible, 500);
  } catch(e) {
    document.getElementById('itemsContainer').innerHTML =
      `<div class="empty-col"><p style="color:var(--ink3)">${e.message}</p></div>`;
  }
}

async function loadStats() {
  try {
    const r = await fetch('api.php?action=stats',{credentials:'same-origin'});
    const d = await r.json(); if (!d.ok) return;
    const s = d.stats;
    ['sTotal','sInvested','sValue','sGain'].forEach(id=>{
      const el=document.getElementById(id); if(el){el.classList.remove('loaded'); void el.offsetWidth;}
    });
    document.getElementById('sTotal').textContent    = s.total;
    document.getElementById('sInvested').textContent = s.invested ? '£'+s.invested.toFixed(0) : '£0';
    document.getElementById('sValue').textContent    = s.value    ? '£'+s.value.toFixed(0)    : '£0';
    ['sTotal','sInvested','sValue'].forEach(id=>{
      const el=document.getElementById(id); if(el) el.classList.add('loaded');
    });
    const g = s.value - s.invested, ge = document.getElementById('sGain');
    const gc = document.getElementById('sGainCard');
    if (s.invested > 0 && s.value > 0) {
      ge.textContent = (g>=0?'+':'−')+'£'+Math.abs(g).toFixed(0);
      ge.style.color = g>=0 ? '#4ade80' : '#f87171';
      if (gc) gc.className = 'stat-card ' + (g>=0 ? 'stat-gain-pos' : 'stat-gain-neg');
    }
  } catch(e){}
}

function setTab(t) { currentTab=t; document.querySelectorAll('.cat-tab').forEach(b=>b.classList.toggle('active',b.dataset.cat===t)); filterItems(); setTimeout(loadImagesForVisible,200); }
function updateCounts() {
  const c={all:allItems.length}; allItems.forEach(i=>{ c[i.category]=(c[i.category]||0)+1; });
  Object.entries(c).forEach(([k,n])=>{ const el=document.getElementById('ct-'+k); if(el)el.textContent=n; });
}
function filterItems() {
  const q=document.getElementById('searchInput').value.toLowerCase();
  const s=document.getElementById('sortSelect').value;
  let items=allItems.filter(i=>{
    if(currentTab!=='all'&&i.category!==currentTab)return false;
    if(q&&![i.name,i.subtitle,i.series,i.item_type,i.year].join(' ').toLowerCase().includes(q))return false;
    return true;
  });
  items.sort((a,b)=>{
    switch(s){
      case 'oldest': return a.saved_at.localeCompare(b.saved_at);
      case 'name':   return a.name.localeCompare(b.name);
      case 'value_desc': return parseFloat(b.value||0)-parseFloat(a.value||0);
      default: return b.saved_at.localeCompare(a.saved_at);
    }
  });
  renderItems(items);
}
function setView(v) {
  currentView=v;
  document.getElementById('vt-grid').classList.toggle('active',v==='grid');
  document.getElementById('vt-list').classList.toggle('active',v==='list');
  document.getElementById('itemsContainer').className=v==='grid'?'items-grid':'items-list';
  filterItems();
}

// Price badge with SVG arrow
function priceBadge(id) {
  const p=priceData[id]; if(!p||!p.avg_10)return '';
  const dir=p.direction||'flat', pct=parseFloat(p.change_pct||0);
  const arrow = dir==='up'
    ? '<svg viewBox="0 0 24 24"><path d="M18 15l-6-6-6 6"/></svg>'
    : dir==='down'
    ? '<svg viewBox="0 0 24 24"><path d="M6 9l6 6 6-6"/></svg>'
    : '—';
  const label = Math.abs(pct)>0 ? Math.abs(pct).toFixed(1)+'%' : '';
  return `<span class="price-badge ${dir}">${dir!=='flat'?arrow:''}${label||dir}</span>`;
}

function renderSkeletons() {
  const el = document.getElementById('itemsContainer');
  if (!el || el.className !== 'items-grid') return;
  el.innerHTML = Array(6).fill(0).map((_,i) =>
    `<div class="skeleton-card" style="${i===0?'grid-column:1/-1':''}"><div class="skeleton-img" style="height:${i===0?220:150}px"></div>` +
    `<div style="padding:9px 10px;display:flex;flex-direction:column;gap:6px">` +
    `<div style="height:10px;width:72%;background:var(--surface2);border-radius:4px;animation:skeleton-pulse 1.6s ease-in-out infinite"></div>` +
    `<div style="height:9px;width:48%;background:var(--surface2);border-radius:4px;animation:skeleton-pulse 1.6s ease-in-out infinite .15s"></div>` +
    `</div></div>`
  ).join('');
}

function renderItems(items) {
  const el=document.getElementById('itemsContainer');
  if(!items.length) {
    const EMPTY_COPY = {
    all:    {title:'Your vault is empty',    sub:'Start scanning to build your collection. Gemini AI will identify anything you photograph.'},
    cards:  {title:'No cards yet',           sub:'Photograph any trading card and AI will identify the set, year and condition instantly.'},
    shirts: {title:'No shirts catalogued',   sub:'Scan a football shirt to log the season, kit type and estimated market value.'},
    games:  {title:'No games added',         sub:'Point your camera at any game and AI will identify the platform, publisher and region.'},
    vinyl:  {title:'No records here',        sub:'Scan vinyl or CDs to log the pressing, label and current eBay resale value.'},
    other:  {title:'Nothing else yet',       sub:'Use the Other category for antiques, toys, memorabilia and more.'},
  };
  const ec = EMPTY_COPY[currentTab] || EMPTY_COPY.all;
  el.innerHTML=`<div class="empty-col">
    <div class="ec-icon">${ICONS[currentTab]||ICONS.other}</div>
    <div class="ec-title">${ec.title}</div>
    <div class="ec-sub">${ec.sub}</div>
    <a href="scanner.php" class="ec-cta">
      <svg viewBox="0 0 24 24"><path d="M12 5v14M5 12h14"/></svg>
      Scan your first ${currentTab==='all'?'item':CAT_LABELS[currentTab]?.toLowerCase().replace(/s$/,'')||'item'}
    </a>
  </div>`;
    return;
  }
  el.innerHTML=items.map(i=>currentView==='grid'?renderGrid(i):renderList(i)).join('');
  setTimeout(loadImagesForVisible,100);
}

function renderGrid(item) {
  const icon=ICONS[item.category]||ICONS.other;
  const img=imageCache[item.id];
  const imgEl=img?`<img src="${img}" alt="${esc(item.name)}" loading="lazy" onerror="imgErr(this,'${item.id}')"/>`:`<div class="fallback-icon">${icon}</div>`;
  const val=item.value?'£'+parseFloat(item.value).toFixed(2):'—';
  const badge=priceBadge(item.id);
  const p=priceData[item.id];
  const ebayVal=p&&p.avg_10?`<span style="font-family:var(--font-mono);font-size:9px;color:rgba(240,237,231,.55)">£${parseFloat(p.avg_10).toFixed(0)} eBay</span>`:'';
  const html=`<div class="item-card" id="card-${esc(item.id)}" onclick="openModal('${esc(item.id)}')">
    <div class="swipe-delete-zone">
      <svg viewBox="0 0 24 24"><polyline points="3 6 5 6 21 6"/><path d="M19 6l-1 14H6L5 6"/><path d="M10 11v6M14 11v6"/></svg>
      Delete
    </div>
    <div class="ic-image loading ic-swipe-content" id="iw-${esc(item.id)}">
      ${imgEl}
      <span class="ic-cat-tag">${CAT_LABELS[item.category]||item.category}</span>
      <button class="ic-del-btn" onclick="event.stopPropagation();deleteItem('${esc(item.id)}','${esc(item.name)}')" title="Remove">×</button>
      <button class="img-refresh-btn" onclick="event.stopPropagation();refreshImg('${item.id}','${esc(buildQuery(item))}','${item.category}')">
        <svg viewBox="0 0 24 24"><path d="M1 4v6h6M23 20v-6h-6"/><path d="M20.49 9A9 9 0 005.64 5.64L1 10M23 14l-4.64 4.36A9 9 0 013.51 15"/></svg> Refresh
      </button>
      <div class="ic-overlay">
        <div class="ic-name">${esc(item.name)}</div>
        <div class="ic-sub">${esc(item.subtitle||'—')}</div>
        <div class="ic-foot">
          <span class="ic-value">${val}</span>
          ${badge||ebayVal||''}
        </div>
      </div>
    </div>
  </div>`;
  // init swipe after render (deferred)
  setTimeout(()=>{
    const el=document.getElementById('card-'+item.id);
    if(el) initSwipe(el, item.id, item.name);
  },0);
  return html;
}

function renderList(item) {
  const icon=ICONS[item.category]||ICONS.other;
  const img=imageCache[item.id];
  const thumb=img?`<img src="${img}" alt="${esc(item.name)}" loading="lazy" onerror="this.style.display='none'">`:`<div style="display:flex;align-items:center;justify-content:center;width:100%;height:100%;opacity:.4">${icon}</div>`;
  const val=item.value?'£'+parseFloat(item.value).toFixed(2):'—';
  const date=item.saved_at?item.saved_at.split(' ')[0]:'';
  const badge=priceBadge(item.id);
  return `<div class="item-row" onclick="openModal('${esc(item.id)}')">
    <div class="ir-thumb">${thumb}</div>
    <div class="ir-info"><div class="ir-name">${esc(item.name)}</div><div class="ir-sub">${esc(item.subtitle||'—')}</div><span class="ir-type">${esc(item.item_type||item.series||item.category)}</span></div>
    <div class="ir-right"><div class="ir-value">${val}</div>${badge?`<div style="margin-top:3px">${badge}</div>`:`<div class="ir-date">${date}</div>`}</div>
    <button class="ir-del" onclick="event.stopPropagation();deleteItem('${esc(item.id)}','${esc(item.name)}')" title="Remove">×</button>
  </div>`;
}

// Images
function buildQuery(item){return[item.name,item.subtitle,item.series,item.year].filter(Boolean).join(' ').replace(/['"]/g,'');}
async function loadImagesForVisible(){
  const vis=allItems.filter(i=>document.getElementById('iw-'+i.id)&&!imageCache[i.id]);
  for(const item of vis){await loadImg(item.id,buildQuery(item),item.category,item.thumbnail);await new Promise(r=>setTimeout(r,80));}
}
async function loadImg(id,query,cat,fallback){
  const wrap=document.getElementById('iw-'+id);
  try{
    const resp=await fetch('api.php?'+new URLSearchParams({action:'getImage',id,query,cat}),{credentials:'same-origin'});
    const data=await resp.json();
    const src=(data.ok&&data.url)?data.url:(fallback||null);
    if(src){imageCache[id]=src;setImgEl(wrap,src,query,id);}
    else if(wrap)wrap.classList.remove('loading');
  }catch(e){if(fallback){imageCache[id]=fallback;setImgEl(wrap,fallback,query,id);}else if(wrap)wrap.classList.remove('loading');}
}
function setImgEl(wrap,src,alt,id){
  if(!wrap)return;
  wrap.classList.remove('loading');
  const img=document.createElement('img');
  img.src=src;img.alt=alt;img.loading='lazy';
  img.style.cssText='width:100%;height:100%;object-fit:cover;display:block';
  img.onerror=()=>imgErr(img,id);
  const old=wrap.querySelector('img,.fallback-icon');
  if(old)old.replaceWith(img);else wrap.insertBefore(img,wrap.firstChild);
}
async function refreshImg(id,query,cat){
  const wrap=document.getElementById('iw-'+id);
  if(wrap)wrap.classList.add('loading');
  delete imageCache[id];
  const resp=await fetch('api.php?'+new URLSearchParams({action:'getImage',id,query,cat,refresh:'1'}),{credentials:'same-origin'});
  const data=await resp.json();
  if(data.ok&&data.url){imageCache[id]=data.url;setImgEl(wrap,data.url,query,id);}
  else{if(wrap)wrap.classList.remove('loading');showToast('No image found');}
}
function imgErr(el,id){
  el.style.display='none';
  const wrap=document.getElementById('iw-'+id);
  if(wrap&&!wrap.querySelector('.fallback-icon')){
    const d=document.createElement('div');d.className='fallback-icon';
    const item=allItems.find(i=>i.id===id);
    d.innerHTML=item?(ICONS[item.category]||ICONS.other):ICONS.other;
    wrap.insertBefore(d,wrap.firstChild);
  }
}

// eBay prices
async function autoRefreshPrices(){
  if(!allItems.length)return;
  document.getElementById('lastUpdated').textContent='Updating eBay prices…';
  let done=0;
  for(const item of allItems){
    try{
      const query=[item.name,item.subtitle,item.series].filter(Boolean).join(' ');
      const fd=new FormData();fd.append('action','refreshPrices');fd.append('item_id',item.id);fd.append('query',query);
      const resp=await fetch('api.php',{method:'POST',body:fd,credentials:'same-origin'});
      const data=await resp.json();
      if(data.ok){priceData[item.id]=data.price;done++;}
      await new Promise(r=>setTimeout(r,500));
    }catch(e){}
  }
  const t=new Date().toLocaleTimeString('en-GB',{hour:'2-digit',minute:'2-digit'});
  document.getElementById('lastUpdated').textContent=`${done} prices updated · ${t}`;
  filterItems();
}
async function refreshAllPrices(){
  const btn=document.getElementById('refreshBtn');
  btn.classList.add('spinning');
  btn.querySelector('.btn-label').textContent='…';
  await autoRefreshPrices();
  btn.classList.remove('spinning');
  btn.querySelector('.btn-label').textContent='Refresh';
  showToast('Prices updated');
}
async function refreshSinglePrice(id){
  const item=allItems.find(i=>i.id===id);if(!item)return;
  const query=[item.name,item.subtitle,item.series].filter(Boolean).join(' ');
  const fd=new FormData();fd.append('action','refreshPrices');fd.append('item_id',id);fd.append('query',query);
  const resp=await fetch('api.php',{method:'POST',body:fd,credentials:'same-origin'});
  const data=await resp.json();
  if(data.ok){priceData[id]=data.price;filterItems();const bg=document.getElementById('modalBg');if(bg.classList.contains('open'))openModal(id);showToast('Price updated');}
}

async function ebayFallbackSearch(id){
  const item=allItems.find(i=>i.id===id);if(!item)return;
  const block=document.getElementById('ebayNoData-'+id);
  if(block) block.innerHTML=`<div class="ebay-title">eBay Market Data</div><div style="font-size:12px;color:var(--ink3);padding:4px 0">Searching eBay…</div>`;

  // Build a simplified query: name + series + year only
  const parts=[item.name,item.series,item.year].filter(Boolean);
  const query=parts.join(' ');

  const fd=new FormData();fd.append('action','searchEbay');fd.append('query',query);
  try{
    const resp=await fetch('api.php',{method:'POST',body:fd,credentials:'same-origin'});
    const data=await resp.json();
    const el=document.getElementById('ebayNoData-'+id);
    if(!el)return;
    if(!data.ok||data.no_results){
      el.innerHTML=`<div class="ebay-title">eBay Market Data</div>
        <div style="font-size:12px;color:var(--ink3);display:flex;align-items:center;justify-content:space-between">
          <span>No listings found for "${esc(query)}"</span>
          <button class="ebay-refresh" onclick="event.stopPropagation();refreshSinglePrice('${id}')">↺ retry</button>
        </div>`;
      return;
    }
    // Show the preview card for user confirmation
    const priceStr=data.price&&data.price.count>0
      ? `<span style="color:var(--gold);font-weight:600">~£${parseFloat(data.price.avg_10).toFixed(2)}</span> <span style="color:var(--ink3);font-size:11px">(${data.price.count} sales)</span>`
      : `<span style="color:var(--ink3);font-size:11px">price unknown</span>`;
    el.innerHTML=`
      <div class="ebay-title">Does this match your item?</div>
      <div class="ebay-preview" style="display:flex;gap:10px;align-items:flex-start;margin:8px 0">
        ${data.image?`<img src="${esc(data.image)}" alt="" style="width:64px;height:64px;object-fit:cover;border-radius:6px;flex-shrink:0;background:var(--surface2)">`:'<div style="width:64px;height:64px;border-radius:6px;background:var(--surface2);flex-shrink:0"></div>'}
        <div style="flex:1;min-width:0">
          <div style="font-size:12px;font-weight:600;color:var(--ink);line-height:1.3;margin-bottom:4px">${esc(data.title||query)}</div>
          <div style="font-size:12px;margin-bottom:6px">${priceStr}</div>
          <div style="font-size:11px;color:var(--ink3);margin-bottom:8px">Query: "${esc(data.query_used)}"</div>
          <div style="display:flex;gap:6px">
            <button class="ebay-refresh" style="background:var(--ink);color:var(--surface);border-color:var(--ink);flex:1"
              onclick="event.stopPropagation();ebayConfirmLink('${id}','${esc(data.query_used).replace(/'/g,"\\'")}',${JSON.stringify(data.price||null)})">
              ✓ Yes, use this
            </button>
            <button class="ebay-refresh" style="flex:1"
              onclick="event.stopPropagation();ebayRejectLink('${id}')">
              ✗ No match
            </button>
          </div>
        </div>
      </div>`;
  }catch(e){
    const el=document.getElementById('ebayNoData-'+id);
    if(el)el.innerHTML=`<div class="ebay-title">eBay Market Data</div><div style="font-size:12px;color:var(--red)">Search failed. Try again.</div>`;
  }
}

async function ebayConfirmLink(id, query, previewPrice){
  const el=document.getElementById('ebayNoData-'+id);
  if(el)el.innerHTML=`<div class="ebay-title">eBay Market Data</div><div style="font-size:12px;color:var(--ink3)">Saving…</div>`;
  const fd=new FormData();
  fd.append('action','linkEbayQuery');
  fd.append('item_id',id);
  fd.append('ebay_query',query);
  try{
    const resp=await fetch('api.php',{method:'POST',body:fd,credentials:'same-origin'});
    const data=await resp.json();
    if(data.ok){
      priceData[id]=data.price;
      filterItems();
      if(document.getElementById('modalBg').classList.contains('open')) openModal(id);
      showToast('eBay link saved — prices will use this search');
    }
  }catch(e){showToast('Error saving link');}
}

function ebayRejectLink(id){
  const el=document.getElementById('ebayNoData-'+id);
  if(!el)return;
  el.innerHTML=`<div class="ebay-title">eBay Market Data</div>
    <div style="font-size:12px;color:var(--ink3);display:flex;align-items:center;justify-content:space-between">
      <span>No match found</span>
      <button class="ebay-refresh" onclick="event.stopPropagation();refreshSinglePrice('${id}')">↺ retry</button>
    </div>`;
}

// Modal
function openModal(id){
  const item=allItems.find(i=>i.id===id);if(!item)return;
  const icon=ICONS[item.category]||ICONS.other;
  const modalImg=document.getElementById('modalImg');
  // Ensure solid background on modal image slot before anything renders
  modalImg.style.background = getComputedStyle(document.documentElement).getPropertyValue('--surface2').trim() || '#ECEAE5';
  modalImg.innerHTML=`<button class="modal-close" onclick="closeModal()">×</button>`;
  const imgUrl=imageCache[id];
  if(imgUrl){
    const img=document.createElement('img');
    img.src=imgUrl; img.alt=item.name;
    img.style.cssText='width:100%;height:100%;object-fit:cover;display:block;position:absolute;inset:0;';
    img.onerror=()=>{ img.remove(); };
    modalImg.insertBefore(img,modalImg.firstChild);
  } else {
    const d=document.createElement('div'); d.className='big-icon'; d.innerHTML=icon;
    modalImg.insertBefore(d,modalImg.firstChild);
  }
  document.getElementById('modalName').textContent=item.name;
  document.getElementById('modalSub').textContent=[item.subtitle,CAT_LABELS[item.category]].filter(Boolean).join(' · ');
  const p=priceData[id];
  const details=[
    ['Category',CAT_LABELS[item.category]||item.category],
    ['Series',item.series||'—'],['Type',item.item_type||'—'],
    ['Year',item.year||'—'],['Condition',item.condition||'—'],
    ['Purchased',item.bought?'£'+parseFloat(item.bought).toFixed(2):'—'],
    ['My Value',item.value?'£'+parseFloat(item.value).toFixed(2):'—'],
    ['Gain/Loss',(item.bought&&item.value)?gainStr(item):'—'],
    ['Added',item.saved_at?item.saved_at.split(' ')[0]:'—'],
    ['Notes',item.notes||'—'],
  ];
  document.getElementById('modalDetails').innerHTML=details.map(([l,v])=>`<div class="detail-item"><label>${l}</label><span>${esc(String(v))}</span></div>`).join('');
  // p exists if we've attempted a fetch; p.count>0 means we have real sales data
  const hasData = p && parseInt(p.count) > 0;
  const wasFetched = p && parseInt(p.count) === 0; // fetched but 0 results
  document.getElementById('modalEbay').innerHTML=hasData?`
    <div class="ebay-block">
      <div class="ebay-title">eBay Market Data (${p.count} sale${p.count==1?'':'s'})</div>
      <div class="ebay-stats">
        <div class="ebay-stat"><label>${parseInt(p.count)<10?'Avg ('+p.count+')':'Avg 10'}</label><span>£${parseFloat(p.avg_10).toFixed(2)}</span></div>
        <div class="ebay-stat"><label>${parseInt(p.count)<30?'Avg ('+p.count+')':'Avg 30'}</label><span>£${parseFloat(p.avg_30).toFixed(2)}</span></div>
        <div class="ebay-stat"><label>Range</label><span>£${parseFloat(p.min).toFixed(0)}–£${parseFloat(p.max).toFixed(0)}</span></div>
      </div>
      ${(p.avg_10&&p.avg_30)?`<div class="ebay-vs"><span>10-sale avg vs 30-sale avg</span><div class="ebay-vs-bar"><div class="ebay-vs-fill ${parseFloat(p.avg_10)>=parseFloat(p.avg_30)?'':'down'}" style="width:${Math.min(100,Math.round((parseFloat(p.avg_10)/parseFloat(p.avg_30))*100))}%"></div></div><span>${parseFloat(p.avg_10)>=parseFloat(p.avg_30)?'▲':'▼'}</span></div>`:''}
      <div class="ebay-trend">${priceBadge(id)}<span>vs previous · ${p.updated_at?p.updated_at.split(' ')[0]:''}</span>
        <button class="ebay-refresh" onclick="event.stopPropagation();refreshSinglePrice('${id}')">↺ refresh</button>
      </div>
    </div>`:
    `<div class="ebay-block" id="ebayNoData-${id}"><div class="ebay-title">eBay Market Data</div>
      <div style="display:flex;align-items:center;justify-content:space-between;font-size:12px;color:var(--ink3)">
        <span>${wasFetched?'No exact listings found':'No data yet'}</span>
        <button class="ebay-refresh" style="background:var(--ink);color:var(--surface);border-color:var(--ink)"
          onclick="event.stopPropagation();${wasFetched?`ebayFallbackSearch('${id}')`:`refreshSinglePrice('${id}')`}">
          ${wasFetched?'Search eBay →':'Fetch →'}</button>
      </div>
    </div>`;
  // Auto-trigger fallback search if this was already fetched with no results
  if(wasFetched) setTimeout(()=>ebayFallbackSearch('${id}'), 200);
  document.getElementById('modalBg').classList.add('open');
  document.body.style.overflow='hidden';
  // Tap-to-zoom on modal image
  const mImg=document.getElementById('modalImg');
  mImg.onclick=function(){
    const src=this.querySelector('img')?.src; if(!src) return;
    const ov=document.createElement('div');
    ov.className='img-zoom-overlay';
    const zi=document.createElement('img'); zi.src=src;
    ov.appendChild(zi);
    ov.onclick=()=>document.body.removeChild(ov);
    document.body.appendChild(ov);
  };
}
function closeModal(){document.getElementById('modalBg').classList.remove('open');document.body.style.overflow='';}
function handleModalBgClick(e){if(e.target===document.getElementById('modalBg'))closeModal();}
function gainStr(item){const g=parseFloat(item.value)-parseFloat(item.bought);return(g>=0?'+':'')+'£'+g.toFixed(2);}

async function deleteItem(id,name){
  if(!confirm(`Remove "${name}"?`))return;
  try{
    const fd=new FormData();fd.append('action','delete');fd.append('id',id);
    const r=await fetch('api.php',{method:'POST',body:fd,credentials:'same-origin'});
    const d=await r.json();if(!d.ok)throw new Error(d.error);
    allItems=allItems.filter(i=>i.id!==id);delete priceData[id];delete imageCache[id];
    updateCounts();filterItems();loadStats();closeModal();showToast(`${name} removed`);
  }catch(e){showToast('Error: '+e.message);}
}
function esc(s){return String(s||'').replace(/&/g,'&amp;').replace(/</g,'&lt;').replace(/>/g,'&gt;').replace(/"/g,'&quot;').replace(/'/g,'&#39;');}

// ── SWIPE TO DELETE ──────────────────────────────────────────────
function initSwipe(card, itemId, itemName) {
  let startX=0, startY=0, dx=0, swiping=false, confirmed=false;
  card.addEventListener('touchstart', e=>{
    startX=e.touches[0].clientX; startY=e.touches[0].clientY;
    dx=0; swiping=false; confirmed=false;
  },{passive:true});
  card.addEventListener('touchmove', e=>{
    const cx=e.touches[0].clientX, cy=e.touches[0].clientY;
    if(!swiping && Math.abs(cx-startX)<Math.abs(cy-startY)*1.2) return; // vertical scroll
    swiping=true;
    dx=Math.min(0,cx-startX);
    if(dx<-8) e.preventDefault();
    const pct=Math.min(1,Math.abs(dx)/72);
    const inner=card.querySelector('.ic-swipe-content')||card.querySelector('.ic-image');
    if(inner) inner.style.transform=`translateX(${Math.max(-72,dx)}px)`;
    const zone=card.querySelector('.swipe-delete-zone');
    if(zone) zone.style.opacity=pct;
    card.classList.toggle('swiping', dx<-16);
  },{passive:false});
  card.addEventListener('touchend', ()=>{
    if(!swiping) return;
    const inner=card.querySelector('.ic-swipe-content')||card.querySelector('.ic-image');
    if(dx<-52){
      // confirmed — animate out and delete
      card.style.transition='transform .3s ease, opacity .3s ease, max-height .3s ease';
      card.style.transform='translateX(-110%)'; card.style.opacity='0';
      setTimeout(()=>deleteItem(itemId,itemName),280);
    } else {
      // snap back
      if(inner){inner.style.transition='transform .2s ease';inner.style.transform='';}
      const zone=card.querySelector('.swipe-delete-zone');
      if(zone) zone.style.opacity='0';
      card.classList.remove('swiping');
      setTimeout(()=>{if(inner)inner.style.transition='';},200);
    }
    swiping=false;
  });
}
let _ft;
function debouncedFilter(){clearTimeout(_ft);_ft=setTimeout(filterItems,160);}


// ── Category Carousel (collection page) ─────────────────────────────────────
function openCarousel() {
  const carousel = document.getElementById('catCarousel');
  // Animate in
  carousel.style.opacity = '0';
  carousel.classList.add('open');
  document.body.classList.add('cat-carousel-open');
  carousel.style.transition = 'opacity .25s ease';
  requestAnimationFrame(() => requestAnimationFrame(() => { carousel.style.opacity = '1'; }));
  // Update counts and init drag
  updateCarouselCounts();
  initColCarousel();
  // Scroll to top so carousel is visible
  window.scrollTo({ top: 0, behavior: 'smooth' });
}

function closeCarousel() {
  const carousel = document.getElementById('catCarousel');
  carousel.style.opacity = '0';
  carousel.style.transition = 'opacity .2s ease';
  setTimeout(() => {
    carousel.classList.remove('open');
    carousel.style.opacity = '';
    carousel.style.transition = '';
    document.body.classList.remove('cat-carousel-open');
  }, 180);
}

function selectCarouselCat(cat) {
  if (window._colCarouselDragged) { window._colCarouselDragged = false; return; }
  const carousel = document.getElementById('catCarousel');
  carousel.style.opacity = '0';
  carousel.style.transform = 'translateY(-8px)';
  carousel.style.transition = 'opacity .2s ease, transform .2s ease';
  setTimeout(() => {
    carousel.classList.remove('open');
    carousel.style.opacity = '';
    carousel.style.transform = '';
    carousel.style.transition = '';
    document.body.classList.remove('cat-carousel-open');
    setTab(cat);
    // Update Browse button highlight if filtering
    const btn = document.getElementById('browseCarouselBtn');
    if (btn) btn.classList.toggle('filtering', cat !== 'all');
    // Fade collection back in
    const hero = document.querySelector('.hero');
    if (hero) { hero.style.opacity='0'; hero.style.transition='opacity .25s ease'; requestAnimationFrame(()=>requestAnimationFrame(()=>{hero.style.opacity='1';})); }
  }, 180);
}

function updateCarouselCounts() {
  const counts = {}, values = {};
  allItems.forEach(i => {
    counts[i.category] = (counts[i.category]||0) + 1;
    values[i.category] = (values[i.category]||0) + (parseFloat(i.value)||0);
  });
  const total = allItems.length;
  const totalVal = Object.values(values).reduce((a,b)=>a+b, 0);

  const setCount = (id, n) => { const e=document.getElementById(id); if(e) e.textContent = n+' item'+(n===1?'':'s'); };
  const setVal   = (id, v) => { const e=document.getElementById(id); if(e) e.textContent = v > 0 ? '£'+v.toFixed(2) : ''; };

  setCount('cc-all', total);   setVal('cv-all', totalVal);
  ['cards','shirts','games','vinyl','other'].forEach(cat => {
    setCount('cc-'+cat, counts[cat]||0);
    setVal('cv-'+cat, values[cat]||0);
  });
}

let _colCarouselInited = false;
function initColCarousel() {
  if (_colCarouselInited) return;
  _colCarouselInited = true;
  const carousel = document.getElementById('colCarousel');
  const dots = document.querySelectorAll('#colCarouselDots .picker-dot');
  if (!carousel) return;

  // Dot sync on scroll
  let dotTimer;
  carousel.addEventListener('scroll', () => {
    clearTimeout(dotTimer);
    dotTimer = setTimeout(() => {
      const cards = carousel.querySelectorAll('.picker-card');
      let closest = 0, minDist = Infinity;
      cards.forEach((c, i) => {
        const dist = Math.abs(c.getBoundingClientRect().left - carousel.getBoundingClientRect().left);
        if (dist < minDist) { minDist = dist; closest = i; }
      });
      dots.forEach((d, i) => d.classList.toggle('active', i === closest));
    }, 60);
  }, { passive: true });

  // Mouse drag
  let isDown = false, startX, scrollLeft, moved = false;
  carousel.addEventListener('mousedown', e => {
    isDown = true; moved = false;
    startX = e.pageX - carousel.offsetLeft;
    scrollLeft = carousel.scrollLeft;
    carousel.style.userSelect = 'none';
  });
  carousel.addEventListener('mouseleave', () => { isDown = false; });
  carousel.addEventListener('mouseup', () => {
    isDown = false; carousel.style.userSelect = '';
    if (moved) window._colCarouselDragged = true;
    setTimeout(() => { window._colCarouselDragged = false; }, 50);
  });
  carousel.addEventListener('mousemove', e => {
    if (!isDown) return; e.preventDefault();
    const x = e.pageX - carousel.offsetLeft;
    const walk = (x - startX) * 1.2;
    if (Math.abs(walk) > 6) moved = true;
    carousel.scrollLeft = scrollLeft - walk;
  });
}

function scrollColCarouselTo(idx) {
  const carousel = document.getElementById('colCarousel');
  const cards = carousel ? carousel.querySelectorAll('.picker-card') : [];
  if (cards[idx]) cards[idx].scrollIntoView({ behavior: 'smooth', block: 'nearest', inline: 'start' });
}

// Close carousel on Escape
document.addEventListener('keydown', e => {
  if (e.key === 'Escape' && document.getElementById('catCarousel').classList.contains('open')) closeCarousel();
});

let toastT;
function showToast(msg){const el=document.getElementById('toast');el.textContent=msg;el.classList.add('show');clearTimeout(toastT);toastT=setTimeout(()=>el.classList.remove('show'),2800);}
</script>
</body>
</html>
