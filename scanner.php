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
<title>CollectorVault — Scanner</title>
<link rel="preconnect" href="https://fonts.googleapis.com">
<link href="https://fonts.googleapis.com/css2?family=Geist+Mono:wght@300;400;500&family=Geist:wght@300;400;500;600&display=swap" rel="stylesheet">
<script>
/* ── Scanner theme management ───────────────────────────────────────────────
   The carousel glass scene is ALWAYS dark. The scan form respects user pref.
   We handle this entirely in JS — no CSS cascade fights.
   ────────────────────────────────────────────────────────────────────────── */
(function() {
  // Apply user theme immediately — theme.php handles this via localStorage
  var t = localStorage.getItem('cv_theme') || 'dark';
  document.documentElement.setAttribute('data-theme', t);
})();
</script>
<link rel="stylesheet" href="shared.css?v=1776992000">
<style>
/* ── LAYOUT ──────────────────────────────────────────────────────────────── */
.app { display:flex; flex-direction:column; min-height:calc(100dvh - var(--nav-h, 52px) - 42px); }

/* ── LEFT: SCANNER ───────────────────────────────────────────────────────── */
.left {
  background: rgba(244,243,241,.55);
  backdrop-filter: blur(20px) saturate(1.2);
  -webkit-backdrop-filter: blur(20px) saturate(1.2);
  border-bottom: 1px solid rgba(255,255,255,.20);
  padding:16px; display:flex; flex-direction:column; gap:14px;
}
[data-theme="dark"] .left,
html[data-theme="dark"] .left {
  background: rgba(10,10,10,.62);
  border-bottom: 1px solid rgba(255,255,255,.08);
}

/* Drop zone */
.dropzone {
  border:2px dashed rgba(255,255,255,.28); border-radius:var(--radius-lg);
  padding:36px 16px; text-align:center; cursor:pointer;
  background:rgba(255,255,255,.10); transition:all .2s;
  -webkit-tap-highlight-color:transparent;
}
.dropzone:hover { border-color:rgba(255,255,255,.60); background:rgba(255,255,255,.20); }
.dropzone:active { transform:scale(.99); }
.dropzone input { display:none; }
.dz-icon { margin-bottom:12px; display:flex; justify-content:center; }
.dz-icon svg { width:36px; height:36px; stroke:var(--ink3); fill:none; stroke-width:1; }
.dz-title { font-family:var(--font-sans); font-size:15px; font-weight:500; margin-bottom:4px; color:var(--ink); }
.dz-sub { font-family:var(--font-mono); font-size:10px; color:var(--ink3); letter-spacing:.03em; }

/* Preview */
#previewWrap { display:none; }
#previewImg { width:100%; border-radius:var(--radius); border:1px solid var(--border); max-height:180px; object-fit:contain; display:block; }

/* Scanning */
#scanningState { display:none; background:var(--ink); border-radius:var(--radius-lg); padding:20px; text-align:center; }
.scan-title { font-family:var(--font-sans); font-size:14px; font-weight:500; color:var(--surface); margin-bottom:4px; }
.scan-sub { font-family:var(--font-mono); font-size:9px; color:var(--ink3); margin-bottom:12px; letter-spacing:.04em; }
.progress { height:2px; background:rgba(255,255,255,.12); border-radius:2px; overflow:hidden; }
.progress-bar { height:100%; background:var(--surface); border-radius:2px; animation:prog 2.2s ease-in-out infinite; }
@keyframes prog { 0%{width:5%;margin-left:0} 50%{width:55%;margin-left:20%} 100%{width:5%;margin-left:95%} }

/* Error */
#errorBox { display:none; background:rgba(193,53,40,.08); border:1px solid rgba(193,53,40,.2); border-radius:var(--radius); padding:10px 12px; font-size:12px; color:var(--red); font-family:var(--font-sans); }

/* Result form */
#resultForm { display:none; }
.id-block { background:rgba(255,255,255,.15); border:1px solid rgba(255,255,255,.22); border-radius:var(--radius); padding:12px 14px; margin-bottom:12px; backdrop-filter:blur(10px); -webkit-backdrop-filter:blur(10px); }
.id-name { font-family:var(--font-sans); font-size:18px; font-weight:500; color:var(--ink); margin-bottom:3px; }
.id-meta { font-family:var(--font-mono); font-size:10px; color:var(--ink3); display:flex; align-items:center; gap:8px; flex-wrap:wrap; letter-spacing:.03em; }
.conf-tag { padding:1px 6px; border-radius:3px; font-size:8px; font-weight:500; letter-spacing:.08em; text-transform:uppercase; font-family:var(--font-mono); }
.conf-high { background:rgba(26,102,64,.1); color:var(--green); }
.conf-med  { background:rgba(155,122,26,.1); color:var(--gold); }
.conf-low  { background:rgba(193,53,40,.1); color:var(--red); }

/* Fields */
#dynamicFields { display:flex; flex-direction:column; gap:8px; margin-bottom:10px; }
.frow { display:grid; grid-template-columns:1fr 1fr; gap:8px; }
.frow.full { grid-template-columns:1fr; }
.fg { display:flex; flex-direction:column; gap:3px; }
.fg label { font-family:var(--font-mono); font-size:8px; letter-spacing:.1em; text-transform:uppercase; color:var(--ink3); }
.fg input, .fg select {
  background:rgba(255,255,255,.18); border:1px solid rgba(255,255,255,.25);
  border-radius:var(--radius); padding:7px 9px;
  font-family:var(--font-sans); font-size:12px; font-weight:400; color:var(--ink); width:100%;
  transition:border-color .15s;
}
.fg input:focus, .fg select:focus { outline:none; border-color:var(--ink); }
.fg input::placeholder { color:var(--ink3); }

/* Price row */
.price-row { display:grid; grid-template-columns:1fr 1fr; gap:8px; margin-bottom:12px; }
.pg label { font-family:var(--font-mono); font-size:8px; letter-spacing:.1em; text-transform:uppercase; color:var(--gold); display:block; margin-bottom:3px; }
.pi-wrap { position:relative; }
.pi-wrap::before { content:'£'; position:absolute; left:9px; top:50%; transform:translateY(-50%); font-family:var(--font-mono); font-size:12px; color:var(--gold); pointer-events:none; }
.pi-wrap input { padding-left:20px; background:rgba(255,255,255,.18); border:1px solid rgba(255,255,255,.25); border-radius:var(--radius); font-family:var(--font-mono); font-size:12px; font-weight:500; color:var(--gold); width:100%; padding-top:7px; padding-bottom:7px; }
.pi-wrap input:focus { outline:none; border-color:var(--gold); }

/* Action btns */
.form-actions { display:flex; gap:8px; }
.btn-save { flex:1; padding:14px; background:var(--ink); color:var(--surface); border:none; border-radius:var(--radius-lg); font-family:var(--font-mono); font-size:10px; letter-spacing:.06em; text-transform:uppercase; cursor:pointer; transition:opacity .15s, transform .12s; -webkit-tap-highlight-color:transparent; }
.btn-save:hover { opacity:.88; transform:translateY(-1px); }
.btn-save:active { transform:translateY(0); }
.btn-save.loading { opacity:.6; pointer-events:none; }
.btn-reset { flex:0 0 auto; padding:12px 14px; background:transparent; border:1px solid var(--border); border-radius:var(--radius); font-family:var(--font-mono); font-size:10px; color:var(--ink3); cursor:pointer; transition:all .15s; -webkit-tap-highlight-color:transparent; }
.btn-reset:hover { border-color:var(--ink); color:var(--ink); }

/* ── RIGHT: RECENTS ──────────────────────────────────────────────────────── */
.right { flex:1; padding:16px; padding-bottom:80px; background:transparent; }
.right-hdr { display:flex; align-items:center; justify-content:space-between; margin-bottom:16px; padding-bottom:12px; border-bottom:1px solid rgba(255,255,255,.15); }
.right-title { font-family:var(--font-sans); font-size:16px; font-weight:500; color:var(--ink); }

.recent-grid { display:grid; grid-template-columns:repeat(2,1fr); gap:8px; }
.recent-card { min-height:0; }
.recent-card {
  background:var(--surface); border:1px solid var(--border);
  border-radius:var(--radius-lg); overflow:hidden; cursor:pointer;
  transition:transform .15s, box-shadow .15s, border-color .15s;
  -webkit-tap-highlight-color:transparent;
}
.recent-card:hover { transform:translateY(-1px); box-shadow:var(--shadow); border-color:var(--ink2); }
.recent-card:active { transform:scale(.98); }
.rc-thumb {
  width:100%; height:120px; background:var(--surface2);
  display:flex; align-items:center; justify-content:center; overflow:hidden;
  flex-shrink:0;
}
.rc-thumb img { width:100%; height:100%; object-fit:cover; }
.rc-thumb .rc-icon { width:32px; height:32px; opacity:.3; }
.rc-thumb .rc-icon svg { width:100%; height:100%; stroke:var(--ink); fill:none; stroke-width:1; }
.rc-body { padding:8px 10px; }
.rc-name { font-weight:500; font-size:11px; color:var(--ink); white-space:nowrap; overflow:hidden; text-overflow:ellipsis; }
.rc-meta { font-size:10px; color:var(--ink3); margin-top:1px; white-space:nowrap; overflow:hidden; text-overflow:ellipsis; }
.rc-foot { display:flex; justify-content:space-between; align-items:center; margin-top:5px; }
.rc-tag { background:var(--surface2); border:1px solid var(--border); border-radius:3px; padding:1px 5px; font-family:var(--font-mono); font-size:8px; color:var(--ink2); }
.rc-val { font-family:var(--font-mono); font-size:10px; font-weight:500; color:var(--gold); }

.empty-scan { padding:40px 20px; text-align:center; }
.empty-scan svg { width:36px; height:36px; stroke:var(--border); fill:none; stroke-width:1; margin-bottom:10px; }
.empty-scan p { font-size:12px; color:var(--ink3); font-family:var(--font-mono); }

/* ── DESKTOP ──────────────────────────────────────────────────────────────── */
@media (min-width:768px) {
  .app { flex-direction:row; min-height:calc(100dvh - var(--nav-h) - var(--cat-h, 40px)); }
  .left { width:360px; flex-shrink:0; border-bottom:none; border-right:1px solid var(--border); position:sticky; top:0; height:calc(100dvh - var(--nav-h) - var(--cat-h, 40px)); overflow-y:auto; padding:20px; }
  .right { padding:20px 28px; }
  .recent-grid { grid-template-columns:repeat(auto-fill,minmax(160px,1fr)); gap:10px; }
}
@media (min-width:960px) {
  .left { width:380px; padding:24px; }
  .right { padding:24px 40px; }
}

/* ── CATEGORY PICKER — glassmorphism carousel ───────────────────────────── */

/* body.picker-open light/dark handled in shared.css */

#catPicker {
  display: flex;
  flex-direction: column;
  height: calc(100dvh - var(--nav-h));
  overflow: hidden;
  position: relative;
}

/* Bokeh background handled by .glass-scene in shared.css */

/* ── Header — colours handled by .glass-scene in shared.css ────────────── */
.picker-hero {
  position: relative;
  z-index: 1;
  padding: 24px 20px 16px;
  display: flex;
  align-items: flex-start;
  justify-content: space-between;
  gap: 16px;
  flex-shrink: 0;
}
.picker-hero-left {
  display: flex;
  align-items: center;
  gap: 7px;
}
.picker-eyebrow {
  font-family: var(--font-mono);
  font-size: 10px;
  letter-spacing: .12em;
  text-transform: uppercase;
}
.picker-eyebrow-icon {
  display: flex;
  align-items: center;
}
.picker-eyebrow-icon svg {
  width: 11px; height: 11px;
  stroke: currentColor; fill: none; stroke-width: 1.5;
}
.picker-headline { display: none; }
.picker-hero-right {
  font-family: var(--font-sans);
  font-size: 11px;
  line-height: 1.55;
  text-align: right;
  max-width: 200px;
  flex-shrink: 0;
}
.picker-hero-right strong { font-weight: 500; }

/* ── Carousel track ───────────────────────────────────────────────────────── */
.picker-carousel-wrap {
  flex: 1;
  position: relative;
  z-index: 1;
  overflow: hidden;
  margin-left: 40px;
  /* Fade right edge only — left is already inset by margin */
  -webkit-mask-image: linear-gradient(to right, black 84%, transparent 100%);
  mask-image: linear-gradient(to right, black 84%, transparent 100%);
}

.picker-carousel {
  display: flex;
  gap: 10px;
  padding: 12px 0 20px 0;
  overflow-x: auto;
  scroll-snap-type: x mandatory;
  -webkit-overflow-scrolling: touch;
  scrollbar-width: none;
  cursor: grab;
  /* flex-start so cards don't stretch to fill extra height */
  align-items: flex-start;
}
.picker-carousel:active { cursor: grabbing; }
.picker-carousel::-webkit-scrollbar { display: none; }

/* ── Each card — uses shared .glass-card for colours/blur ────────────────── */
.picker-card {
  /* Mobile: show ~1.15 cards so next card peeks in */
  flex: 0 0 85vw;
  max-width: none;
  min-width: 200px;
  height: clamp(360px, 62vh, 540px);
  border-radius: 16px;
  overflow: hidden;
  position: relative;
  cursor: pointer;
  scroll-snap-align: start;
  transition: transform .2s, box-shadow .2s;
  -webkit-tap-highlight-color: transparent;
  flex-shrink: 0;
  animation: cardIn .5s cubic-bezier(.22,.68,0,1.1) both;
}
.picker-card:nth-child(1) { animation-delay: .05s; }
.picker-card:nth-child(2) { animation-delay: .12s; }
.picker-card:nth-child(3) { animation-delay: .19s; }
.picker-card:nth-child(4) { animation-delay: .26s; }
.picker-card:nth-child(5) { animation-delay: .33s; }

@keyframes cardIn {
  from { opacity:0; transform:translateX(28px) scale(.95); }
  to   { opacity:1; transform:translateX(0) scale(1); }
}

.picker-card:hover { transform: translateY(-3px); }
.picker-card:active { transform: scale(.98); }

/* Ghost icon */
.card-big-icon {
  position: absolute;
  top: 44%;
  left: 50%;
  transform: translate(-50%, -60%);
  width: 50%;
  pointer-events: none;
}
.card-big-icon svg {
  width: 100%; height: 100%;
  stroke: currentColor; fill: none; stroke-width: .5;
}
.card-bg { display: none; }
.card-pattern { display: none; }

/* Top-left number + icon label */
.card-num {
  position: absolute;
  top: 16px; left: 16px;
  display: flex;
  align-items: center;
  gap: 5px;
  font-family: var(--font-mono);
  font-size: 9px;
  letter-spacing: .1em;
}
.card-num-icon {
  width: 10px; height: 10px;
  opacity: .6;
  flex-shrink: 0;
}
.card-num-icon svg {
  width: 100%; height: 100%;
  fill: none; stroke-width: 1.5;
}

/* Bottom foot */
.card-foot {
  position: absolute;
  bottom: 0; left: 0; right: 0;
  padding: 20px 18px 20px;
}
.card-name {
  font-family: var(--font-sans);
  font-size: 17px;
  font-weight: 500;
  letter-spacing: -.01em;
  margin-bottom: 4px;
}
.card-desc {
  font-family: var(--font-sans);
  font-size: 11px;
  line-height: 1.45;
  margin-bottom: 0;
}
.card-count-pill { display: none; }

/* Arrow */
.card-arrow {
  position: absolute;
  top: 14px; right: 14px;
  width: 24px; height: 24px;
  border-radius: 50%;
  display: flex; align-items: center; justify-content: center;
  font-size: 11px;
  transition: background .18s, color .18s, transform .18s;
}
.picker-card:hover .card-arrow { transform: translate(1px,-1px); }

/* ── Dot indicators — colours handled by .glass-scene in shared.css ──────── */
.picker-dots {
  position: relative;
  z-index: 1;
  display: flex;
  justify-content: center;
  gap: 5px;
  padding: 10px 0 20px;
  flex-shrink: 0;
}
.picker-dot {
  width: 4px; height: 4px;
  border-radius: 50%;
  transition: background .2s, width .2s;
  cursor: pointer;
}
.picker-dot.active {
  width: 16px;
  border-radius: 2px;
}

@media (min-width: 540px) {
  /* Tablet: show ~2 cards + peek */
  .picker-card { flex: 0 0 46vw; max-width: none; }
}
@media (min-width: 900px) {
  .picker-hero { padding: 32px 48px 20px; }
  .picker-carousel { padding: 12px 0 28px 0; gap: 14px; }
  /* Desktop: exactly 3 cards visible, 4th peeks ~15% off the right edge
     Formula: (100vw - left-padding - 2*gap) / 3.15  */
  .picker-card {
    flex: 0 0 calc((100vw - 48px - 14px * 2) / 3.15);
    max-width: none;
    min-width: 240px;
    height: clamp(400px, 68vh, 580px);
  }
  .picker-carousel-wrap {
    -webkit-mask-image: linear-gradient(to right, black 86%, transparent 100%);
    mask-image: linear-gradient(to right, black 86%, transparent 100%);
  }
}
@media (min-width: 1400px) {
  .picker-hero { padding: 36px 64px 22px; }
  .picker-carousel { padding: 12px 0 28px 0; gap: 16px; }
  .picker-card {
    flex: 0 0 calc((100vw - 64px - 16px * 2) / 3.15);
    max-width: 480px;
  }
}
</style>
</head>
<body class="picker-open">
<?php include 'nav.php'; ?>

<!-- ── CATEGORY PICKER — glassmorphism carousel ─────────────────────────── -->
<div id="catPicker" class="glass-scene">
  <div class="picker-hero">
    <div class="picker-hero-left">
      <div class="picker-eyebrow-icon">
        <svg viewBox="0 0 24 24"><polygon points="12 2 15.09 8.26 22 9.27 17 14.14 18.18 21.02 12 17.77 5.82 21.02 7 14.14 2 9.27 8.91 8.26 12 2"/></svg>
      </div>
      <div class="picker-eyebrow">Scanner</div>
    </div>
    <div class="picker-hero-right">
      <strong>Scan any collectible.</strong> AI identifies it<br>and looks up market value automatically.
    </div>
  </div>

  <div class="picker-carousel-wrap">
    <div class="picker-carousel" id="pickerCarousel">

      <!-- Cards -->
      <div class="picker-card glass-card" data-cat="cards" onclick="selectCatFromPicker('cards')">
        <div class="card-num"><span class="card-num-icon"><svg viewBox="0 0 24 24"><rect x="2" y="5" width="20" height="14" rx="2"/><path d="M2 10h20"/></svg></span>01</div>
        <div class="card-arrow">↗</div>
        <div class="card-foot">
          <div class="card-name">Trading Cards</div>
          <div class="card-desc">Pokémon · Sports · TCG</div>
          <div class="card-count-pill" id="ptile-cards">0 items</div>
        </div>
      </div>

      <div class="picker-card glass-card" data-cat="shirts" onclick="selectCatFromPicker('shirts')">
        <div class="card-num"><span class="card-num-icon"><svg viewBox="0 0 24 24"><path d="M20.38 3.46L16 2a4 4 0 01-8 0L3.62 3.46a2 2 0 00-1.34 2.23l.58 3.57a1 1 0 00.99.84H6v10a2 2 0 002 2h8a2 2 0 002-2V10h2.15a1 1 0 00.99-.84l.58-3.57a2 2 0 00-1.34-2.23z"/></svg></span>02</div>
        <div class="card-arrow">↗</div>
        <div class="card-foot">
          <div class="card-name">Football Shirts</div>
          <div class="card-desc">Home · Away · Retro</div>
          <div class="card-count-pill" id="ptile-shirts">0 items</div>
        </div>
      </div>

      <div class="picker-card glass-card" data-cat="games" onclick="selectCatFromPicker('games')">
        <div class="card-num"><span class="card-num-icon"><svg viewBox="0 0 24 24"><rect x="2" y="6" width="20" height="12" rx="4"/><path d="M6 12h4m-2-2v4M15 11h.01M17 13h.01"/></svg></span>03</div>
        <div class="card-arrow">↗</div>
        <div class="card-foot">
          <div class="card-name">Video Games</div>
          <div class="card-desc">Retro · Modern · CIB</div>
          <div class="card-count-pill" id="ptile-games">0 items</div>
        </div>
      </div>

      <div class="picker-card glass-card" data-cat="vinyl" onclick="selectCatFromPicker('vinyl')">
        <div class="card-num"><span class="card-num-icon"><svg viewBox="0 0 24 24"><circle cx="12" cy="12" r="10"/><circle cx="12" cy="12" r="3"/></svg></span>04</div>
        <div class="card-arrow">↗</div>
        <div class="card-foot">
          <div class="card-name">Vinyl &amp; Music</div>
          <div class="card-desc">LP · 7&quot; · CD · Cassette</div>
          <div class="card-count-pill" id="ptile-vinyl">0 items</div>
        </div>
      </div>

      <div class="picker-card glass-card" data-cat="other" onclick="selectCatFromPicker('other')">
        <div class="card-num"><span class="card-num-icon"><svg viewBox="0 0 24 24"><path d="M21 16V8a2 2 0 00-1-1.73l-7-4a2 2 0 00-2 0l-7 4A2 2 0 003 8v8a2 2 0 001 1.73l7 4a2 2 0 002 0l7-4A2 2 0 0021 16z"/></svg></span>05</div>
        <div class="card-arrow">↗</div>
        <div class="card-foot">
          <div class="card-name">Other Collectibles</div>
          <div class="card-desc">Toys · Art · Memorabilia</div>
          <div class="card-count-pill" id="ptile-other">0 items</div>
        </div>
      </div>

    </div><!-- /carousel -->
  </div><!-- /wrap -->

  <div class="picker-dots" id="pickerDots">
    <div class="picker-dot active" onclick="scrollCarouselTo(0)"></div>
    <div class="picker-dot" onclick="scrollCarouselTo(1)"></div>
    <div class="picker-dot" onclick="scrollCarouselTo(2)"></div>
    <div class="picker-dot" onclick="scrollCarouselTo(3)"></div>
    <div class="picker-dot" onclick="scrollCarouselTo(4)"></div>
  </div>
</div>

<div class="cat-bar" id="catBar" style="display:none">
  <button class="cat-btn active" data-cat="cards"  onclick="setCat('cards')"  style="--cat-accent:var(--ink)">
    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5"><rect x="2" y="5" width="20" height="14" rx="2"/><path d="M2 10h20"/></svg> Cards <span class="cat-pill" id="pill-cards">0</span>
  </button>
  <button class="cat-btn" data-cat="shirts" onclick="setCat('shirts')" style="--cat-accent:var(--ink)">
    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5"><path d="M20.38 3.46L16 2a4 4 0 01-8 0L3.62 3.46a2 2 0 00-1.34 2.23l.58 3.57a1 1 0 00.99.84H6v10a2 2 0 002 2h8a2 2 0 002-2V10h2.15a1 1 0 00.99-.84l.58-3.57a2 2 0 00-1.34-2.23z"/></svg> Shirts <span class="cat-pill" id="pill-shirts">0</span>
  </button>
  <button class="cat-btn" data-cat="games"  onclick="setCat('games')"  style="--cat-accent:var(--ink)">
    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5"><rect x="2" y="6" width="20" height="12" rx="4"/><path d="M6 12h4m-2-2v4M15 11h.01M17 13h.01"/></svg> Games <span class="cat-pill" id="pill-games">0</span>
  </button>
  <button class="cat-btn" data-cat="vinyl"  onclick="setCat('vinyl')"  style="--cat-accent:var(--ink)">
    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5"><circle cx="12" cy="12" r="10"/><circle cx="12" cy="12" r="3"/></svg> Vinyl <span class="cat-pill" id="pill-vinyl">0</span>
  </button>
  <button class="cat-btn" data-cat="other"  onclick="setCat('other')"  style="--cat-accent:var(--ink)">
    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5"><path d="M21 16V8a2 2 0 00-1-1.73l-7-4a2 2 0 00-2 0l-7 4A2 2 0 003 8v8a2 2 0 001 1.73l7 4a2 2 0 002 0l7-4A2 2 0 0021 16z"/></svg> Other <span class="cat-pill" id="pill-other">0</span>
  </button>
</div>

</div><!-- /cat-bar -->

<div class="app" id="scannerApp" style="display:none">
  <div class="left">
    <div style="display:flex;align-items:center;justify-content:space-between;margin-bottom:2px">
      <div class="panel-label" id="panelLabel">Scan — Trading Cards</div>
      <button onclick="showPicker()" style="background:none;border:none;cursor:pointer;font-family:var(--font-mono);font-size:9px;color:var(--ink3);letter-spacing:.06em;text-transform:uppercase;padding:4px 0;transition:color .15s" onmouseover="this.style.color='var(--ink)'" onmouseout="this.style.color='var(--ink3)'">← Change</button>
    </div>

    <div class="dropzone" id="dropzone"
         onclick="document.getElementById('fileInput').click()"
         ondragover="onDragOver(event)" ondragleave="onDragLeave()" ondrop="onDrop(event)">
      <input type="file" id="fileInput" accept="image/*" capture="environment" onchange="handleFile(event)"/>
      <div class="dz-icon" id="dzIcon">
        <svg viewBox="0 0 24 24"><rect x="2" y="5" width="20" height="14" rx="2"/><path d="M2 10h20"/></svg>
      </div>
      <div class="dz-title" id="dzTitle">Drop card here</div>
      <div class="dz-sub">Tap to photograph — AI identifies automatically</div>
    </div>

    <div id="previewWrap"><img id="previewImg" alt="Preview"/></div>
    <div id="scanningState">
      <div class="scan-title">Identifying…</div>
      <div class="scan-sub">Gemini AI is analysing your image</div>
      <div class="progress"><div class="progress-bar"></div></div>
    </div>
    <div id="errorBox"></div>

    <div id="resultForm">
      <div class="id-block">
        <div class="id-name" id="rName">—</div>
        <div class="id-meta"><span id="rMeta">—</span><span class="conf-tag" id="rConf">—</span></div>
      </div>
      <div id="dynamicFields"></div>
      <div class="price-row">
        <div class="pg"><label>Paid</label><div class="pi-wrap"><input id="rBought" type="number" step="0.01" min="0" placeholder="0.00"/></div></div>
        <div class="pg"><label>Value</label><div class="pi-wrap"><input id="rValue" type="number" step="0.01" min="0" placeholder="0.00"/></div></div>
      </div>
      <div class="form-actions">
        <button class="btn-reset" onclick="resetScan()">↩</button>
        <button class="btn-save" id="saveBtn" onclick="saveItem()">Save to Collection →</button>
      </div>
    </div>
  </div>

  <div class="right">
    <div class="right-hdr">
      <div class="right-title">Recent Scans</div>
      <a href="collection.php" class="btn-sm btn-outline">View All →</a>
    </div>
    <div id="recentGrid" class="recent-grid">
      <div class="empty-scan">
        <svg viewBox="0 0 24 24"><circle cx="12" cy="12" r="10"/><path d="M12 8v8M8 12h8"/></svg>
        <p>Scan your first item</p>
      </div>
    </div>
  </div>
</div>

<div id="toast"></div>
<!-- FAB: go to collection, mobile only -->
<a href="collection.php" class="fab" aria-label="View collection"
   style="background:var(--surface);color:var(--ink);border:1px solid var(--border);box-shadow:0 2px 12px rgba(14,13,11,.1)">
  <svg viewBox="0 0 24 24"><rect x="3" y="3" width="7" height="7" rx="1"/><rect x="14" y="3" width="7" height="7" rx="1"/><rect x="3" y="14" width="7" height="7" rx="1"/><rect x="14" y="14" width="7" height="7" rx="1"/></svg>
</a>

<script>
<?php include 'categories.js.php' /* v=1776877263 */; ?>

// SVG icons per category
const CAT_ICONS_SVG = {
  cards:  '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5"><rect x="2" y="5" width="20" height="14" rx="2"/><path d="M2 10h20"/></svg>',
  shirts: '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5"><path d="M20.38 3.46L16 2a4 4 0 01-8 0L3.62 3.46a2 2 0 00-1.34 2.23l.58 3.57a1 1 0 00.99.84H6v10a2 2 0 002 2h8a2 2 0 002-2V10h2.15a1 1 0 00.99-.84l.58-3.57a2 2 0 00-1.34-2.23z"/></svg>',
  games:  '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5"><rect x="2" y="6" width="20" height="12" rx="4"/><path d="M6 12h4m-2-2v4M15 11h.01M17 13h.01"/></svg>',
  vinyl:  '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5"><circle cx="12" cy="12" r="10"/><circle cx="12" cy="12" r="3"/></svg>',
  other:  '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5"><path d="M21 16V8a2 2 0 00-1-1.73l-7-4a2 2 0 00-2 0l-7 4A2 2 0 003 8v8a2 2 0 001 1.73l7 4a2 2 0 002 0l7-4A2 2 0 0021 16z"/></svg>',
};
const CAT_LABELS = {cards:'Trading Cards',shirts:'Football Shirts',games:'Video Games',vinyl:'Vinyl & Music',other:'Other'};

let currentCat='cards', currentAI=null, currentB64=null, currentMime=null;

loadRecent(); loadPills();

function setCat(cat) {
  currentCat=cat;
  const def=CATEGORIES[cat];
  document.querySelectorAll('.cat-btn').forEach(b=>b.classList.toggle('active',b.dataset.cat===cat));
  document.getElementById('panelLabel').textContent='Scan — '+def.label;
  document.getElementById('dzIcon').innerHTML=CAT_ICONS_SVG[cat]||CAT_ICONS_SVG.other;
  document.getElementById('dzTitle').textContent='Drop '+def.label.toLowerCase()+' here';
  resetScan();
}

function selectCatFromPicker(cat) {
  // Guard: don't fire if we just finished a drag
  if (window._carouselDragged) { window._carouselDragged = false; return; }
  const picker = document.getElementById('catPicker');
  picker.style.transition = 'opacity .2s ease, transform .2s ease';
  picker.style.opacity = '0';
  picker.style.transform = 'translateY(-8px)';
  setTimeout(() => {
    picker.style.display = 'none';
    document.body.classList.remove('picker-open');
    const catBar = document.getElementById('catBar');
    const app    = document.getElementById('scannerApp');
    catBar.style.display = '';
    app.style.display    = '';
    app.style.opacity = '0';
    app.style.transition = 'opacity .25s ease';
    requestAnimationFrame(() => requestAnimationFrame(() => { app.style.opacity = '1'; }));
    setCat(cat);
    setTimeout(() => document.getElementById('dropzone')?.focus(), 300);
  }, 180);
}

function showPicker() {
  const picker = document.getElementById('catPicker');
  const catBar = document.getElementById('catBar');
  const app    = document.getElementById('scannerApp');
  catBar.style.display = 'none';
  app.style.display    = 'none';
  picker.style.display = '';
  document.body.classList.add('picker-open');
  picker.style.opacity = '0';
  picker.style.transition = 'opacity .28s ease';
  requestAnimationFrame(() => requestAnimationFrame(() => { picker.style.opacity = '1'; }));
  resetScan();
}

// ── Carousel: drag-to-scroll + dot sync ─────────────────────────────────────
(function initCarousel() {
  const carousel = document.getElementById('pickerCarousel');
  const dots = document.querySelectorAll('.picker-dot');
  if (!carousel) return;
  carousel.scrollLeft = 0;
  requestAnimationFrame(() => { carousel.scrollLeft = 0; }); // Lock to left edge after paint

  // Sync dots on scroll
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
    isDown = false;
    carousel.style.userSelect = '';
    // If moved more than 6px treat as drag, suppress click
    if (moved) window._carouselDragged = true;
    setTimeout(() => { window._carouselDragged = false; }, 50);
  });
  carousel.addEventListener('mousemove', e => {
    if (!isDown) return;
    e.preventDefault();
    const x = e.pageX - carousel.offsetLeft;
    const walk = (x - startX) * 1.2;
    if (Math.abs(walk) > 6) moved = true;
    carousel.scrollLeft = scrollLeft - walk;
  });
})();

function scrollCarouselTo(idx) {
  const carousel = document.getElementById('pickerCarousel');
  const cards = carousel.querySelectorAll('.picker-card');
  if (cards[idx]) {
    cards[idx].scrollIntoView({ behavior: 'smooth', block: 'nearest', inline: 'start' });
  }
}

function onDragOver(e){e.preventDefault();document.getElementById('dropzone').style.borderColor='var(--ink)';}
function onDragLeave(){document.getElementById('dropzone').style.borderColor='';}
function onDrop(e){e.preventDefault();document.getElementById('dropzone').style.borderColor='';const f=e.dataTransfer.files[0];if(f&&f.type.startsWith('image/'))processFile(f);}
function handleFile(e){const f=e.target.files[0];if(f)processFile(f);e.target.value='';}

async function processFile(file) {
  document.getElementById('previewImg').src=URL.createObjectURL(file);
  document.getElementById('previewWrap').style.display='block';
  document.getElementById('resultForm').style.display='none';
  document.getElementById('errorBox').style.display='none';
  document.getElementById('scanningState').style.display='block';
  try {
    const b64=await toBase64(file); currentB64=b64; currentMime=file.type;
    const def=CATEGORIES[currentCat];
    const fd=new FormData();
    fd.append('action','scan');fd.append('base64',b64);
    fd.append('mediaType',file.type);fd.append('prompt',def.prompt);fd.append('category',currentCat);
    const resp=await fetch('api.php',{method:'POST',body:fd,credentials:'same-origin'});
    const data=await resp.json();
    if(!data.ok)throw new Error(data.error||'Scan failed');
    buildForm(parseGemini(data.text));
  } catch(e){showError(e.message);}
  finally{document.getElementById('scanningState').style.display='none';}
}

function toBase64(f){return new Promise((res,rej)=>{const r=new FileReader();r.onload=()=>res(r.result.split(',')[1]);r.onerror=rej;r.readAsDataURL(f);});}

function parseGemini(raw){
  let c=raw.replace(/```json/gi,'').replace(/```/g,'').trim();
  const s=c.indexOf('{'),e=c.lastIndexOf('}');
  if(s===-1||e===-1)throw new Error('Could not read response — try a clearer photo.');
  const p=JSON.parse(c.slice(s,e+1));
  if(typeof p.estimatedValue==='string')p.estimatedValue=parseFloat(p.estimatedValue.replace(/[^0-9.]/g,''))||0;
  p.name=p.name||p.playerName||p.title||'Unknown';
  p.subtitle=p.subtitle||p.team||p.artist||p.platform||'';
  p.confidence=p.confidence||'Low';
  return p;
}

function buildForm(ai) {
  currentAI=ai;
  document.getElementById('rName').textContent=ai.name||'Unknown';
  document.getElementById('rMeta').textContent=ai.subtitle||'';
  const conf=ai.confidence||'Low';
  const confEl=document.getElementById('rConf');
  confEl.className='conf-tag '+({High:'conf-high',Medium:'conf-med',Low:'conf-low'}[conf]||'conf-low');
  confEl.textContent=conf;
  const container=document.getElementById('dynamicFields');
  container.innerHTML='';
  const def=CATEGORIES[currentCat];
  def.fields.forEach(rowDef=>{
    const row=document.createElement('div');row.className=rowDef.full?'frow full':'frow';
    rowDef.row.forEach(field=>{
      const fg=document.createElement('div');fg.className='fg';
      const lbl=document.createElement('label');lbl.textContent=field.label;fg.appendChild(lbl);
      let input;
      if(field.type==='select'){
        input=document.createElement('select');
        field.options.forEach(opt=>{const o=document.createElement('option');o.value=o.textContent=opt;input.appendChild(o);});
        const v=(ai[field.id]||'').toLowerCase();
        for(const o of input.options){if(o.value.toLowerCase()===v){input.value=o.value;break;}}
      }else{input=document.createElement('input');input.type='text';input.placeholder=field.placeholder||'';input.value=ai[field.id]||'';}
      input.id='f_'+field.id;fg.appendChild(input);row.appendChild(fg);
    });
    container.appendChild(row);
  });
  document.getElementById('rValue').value=ai.estimatedValue||'';
  document.getElementById('rBought').value='';
  document.getElementById('resultForm').style.display='block';
}

async function saveItem(){
  const btn=document.getElementById('saveBtn');
  btn.classList.add('loading');btn.textContent='Saving…';
  const def=CATEGORIES[currentCat];
  const item={
    category:currentCat,
    name:document.getElementById('rName').textContent,
    subtitle:document.getElementById('rMeta').textContent,
    bought:parseFloat(document.getElementById('rBought').value)||'',
    value:parseFloat(document.getElementById('rValue').value)||'',
    notes:currentAI?.notes||'',
  };
  def.fields.forEach(rd=>rd.row.forEach(f=>{const el=document.getElementById('f_'+f.id);if(el)item[f.id]=el.value;}));
  const ids=def.fields.flatMap(r=>r.row.map(f=>f.id));
  item.item_type=item[ids[1]]||'';item.series=item[ids[0]]||item.series||'';
  item.year=item.year||item.season||'';item.condition=item.condition||'';
  item.extra1=item[ids[2]]||'';item.extra2=item[ids[3]]||'';item.extra3=item[ids[4]]||'';item.extra4=item[ids[5]]||'';
  try{
    const fd=new FormData();fd.append('action','save');fd.append('item',JSON.stringify(item));fd.append('thumbnail','');
    const resp=await fetch('api.php',{method:'POST',body:fd,credentials:'same-origin'});
    const data=await resp.json();
    if(!data.ok)throw new Error(data.error||'Save failed');
    showSaveSuccess(item.name); resetScan(); loadRecent(); loadPills();
document.body.classList.add('picker-open');
  }catch(e){showError(e.message);}
  finally{btn.classList.remove('loading');btn.textContent='Save to Collection →';}
}

function resetScan(){
  ['resultForm','previewWrap','scanningState','errorBox'].forEach(id=>document.getElementById(id).style.display='none');
  document.getElementById('rBought').value='';document.getElementById('rValue').value='';
  document.getElementById('dynamicFields').innerHTML='';
  currentAI=null;currentB64=null;currentMime=null;
}
function showError(msg){const el=document.getElementById('errorBox');el.textContent='⚠ '+msg;el.style.display='block';}

async function loadRecent(){
  try{
    const r=await fetch('api.php?action=collection&category=all',{credentials:'same-origin'});
    const d=await r.json();if(!d.ok)return;
    renderRecent(d.items.slice(0,12));
  }catch(e){}
}

function renderRecent(items){
  const grid=document.getElementById('recentGrid');
  if(!items.length){
    grid.innerHTML='<div class="empty-scan"><svg viewBox="0 0 24 24"><circle cx="12" cy="12" r="10"/><path d="M12 8v8M8 12h8"/></svg><p>Scan your first item</p></div>';
    return;
  }
  grid.innerHTML=items.map(item=>{
    const icon=CAT_ICONS_SVG[item.category]||CAT_ICONS_SVG.other;
    const thumb=item.thumbnail
      ?`<img src="${item.thumbnail}" alt="${item.name}" loading="lazy" onerror="this.style.display='none'">`
      :`<div class="rc-icon">${icon}</div>`;
    const val=item.value?'£'+parseFloat(item.value).toFixed(2):'';
    return `<div class="recent-card" onclick="openRecentModal(${JSON.stringify(item).replace(/"/g,'&quot;')})">
      <div class="rc-thumb">${thumb}</div>
      <div class="rc-body">
        <div class="rc-name">${item.name}</div>
        <div class="rc-meta">${item.subtitle||CAT_LABELS[item.category]||'—'}</div>
        <div class="rc-foot">
          <span class="rc-tag">${item.item_type||item.series||item.category}</span>
          ${val?`<span class="rc-val">${val}</span>`:''}
        </div>
      </div>
    </div>`;
  }).join('');
}

function openRecentModal(item){
  const existing=document.getElementById('recentModal');if(existing)existing.remove();
  const icon=CAT_ICONS_SVG[item.category]||CAT_ICONS_SVG.other;
  const thumb=item.thumbnail
    ?`<img src="${item.thumbnail}" style="width:100%;height:160px;object-fit:cover;display:block" onerror="this.style.display='none'">`
    :`<div style="height:100px;display:flex;align-items:center;justify-content:center;opacity:.2">${icon.replace('width="1.5"','width="1"').replace('stroke-width="1.5"','stroke-width="1"')}</div>`;

  const fields=[
    ['Category',CAT_LABELS[item.category]||item.category],
    ['Series',item.series||'—'],['Type',item.item_type||'—'],
    ['Year',item.year||'—'],['Condition',item.condition||'—'],
    ['Purchased',item.bought?'£'+parseFloat(item.bought).toFixed(2):'—'],
    ['Value',item.value?'£'+parseFloat(item.value).toFixed(2):'—'],
    ['Added',item.saved_at?item.saved_at.split(' ')[0]:'—'],
  ].filter(([,v])=>v&&v!=='—');

  const overlay=document.createElement('div');
  overlay.id='recentModal';
  overlay.style.cssText='position:fixed;inset:0;background:rgba(14,13,11,.72);z-index:400;display:flex;align-items:flex-end;justify-content:center';
  overlay.onclick=e=>{if(e.target===overlay){overlay.remove();document.body.style.overflow='';}};

  const sheet=document.createElement('div');
  sheet.style.cssText='background:var(--surface,#fff);width:100%;max-width:500px;border-radius:16px 16px 0 0;border:1px solid var(--border,#D8D5CF);border-bottom:none;max-height:85dvh;overflow-y:auto;';
  sheet.innerHTML=`
    <div style="width:32px;height:3px;background:var(--border);border-radius:2px;margin:10px auto 0"></div>
    ${thumb}
    <div style="padding:16px 18px 36px">
      <div style="font-family:var(--font-sans);font-size:19px;font-weight:500;color:var(--ink);margin-bottom:3px">${item.name}</div>
      <div style="font-family:var(--font-mono);font-size:10px;color:var(--ink3);margin-bottom:14px;letter-spacing:.03em">${item.subtitle||CAT_LABELS[item.category]||''}</div>
      <div style="display:grid;grid-template-columns:1fr 1fr;gap:10px">
        ${fields.map(([l,v])=>`<div><div style="font-family:var(--font-mono);font-size:8px;letter-spacing:.1em;text-transform:uppercase;color:var(--ink3);margin-bottom:2px">${l}</div><div style="font-size:13px;color:var(--ink);font-family:var(--font-sans)">${v}</div></div>`).join('')}
      </div>
      <a href="collection.php" style="display:flex;align-items:center;justify-content:center;gap:6px;margin-top:16px;background:var(--ink);color:var(--surface);border-radius:6px;padding:12px;font-family:var(--font-mono);font-size:10px;letter-spacing:.06em;text-decoration:none;text-transform:uppercase">View Full Collection →</a>
    </div>`;

  overlay.appendChild(sheet);
  document.body.appendChild(overlay);
  document.body.style.overflow='hidden';
}

async function loadPills(){
  try{
    const r=await fetch('api.php?action=stats',{credentials:'same-origin'});
    const d=await r.json();if(!d.ok)return;
    Object.entries(d.stats.by_cat||{}).forEach(([cat,n])=>{
      const pill=document.getElementById('pill-'+cat);if(pill)pill.textContent=n;
      const tile=document.getElementById('ptile-'+cat);
      if(tile)tile.textContent=n+' item'+(n===1?'':'s');
    });
  }catch(e){}
}

function showSaveSuccess(name) {
  const el = document.createElement('div');
  el.className = 'save-success-overlay';
  el.innerHTML = '<div class="save-success-circle"><svg viewBox="0 0 24 24"><polyline points="20 6 9 17 4 12"/></svg></div>';
  document.body.appendChild(el);
  setTimeout(() => el.parentNode && el.parentNode.removeChild(el), 2000);
  showToast(name + ' saved');
}

let toastT;
function showToast(msg){const el=document.getElementById('toast');el.textContent=msg;el.classList.add('show');clearTimeout(toastT);toastT=setTimeout(()=>el.classList.remove('show'),2800);}
</script>
</body>
</html>
