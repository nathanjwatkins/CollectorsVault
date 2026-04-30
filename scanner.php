<?php

ob_start();
header('Cache-Control: no-cache, must-revalidate, max-age=0');
header('Pragma: no-cache');
ini_set('session.cookie_samesite', 'Lax');
ini_set('session.cookie_secure', '1');
ini_set('session.cookie_httponly', '1');
ini_set('session.cookie_httponly',1); ini_set('session.cookie_secure',1); ini_set('session.cookie_samesite','Lax');
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
<title>CollectorVault — Scanner</title>
<link rel="preconnect" href="https://fonts.googleapis.com">
<link href="https://fonts.googleapis.com/css2?family=Outfit:wght@200;300;400;500;600;700;800;900&family=JetBrains+Mono:wght@300;400;500;700&display=swap" rel="stylesheet">
<?php include 'theme.php'; ?>
<style>

/* ══ SCANNER PAGE LAYOUT ════════════════════════════════════════════════════ */

.scanner-wrap {
  display: flex;
  flex-direction: column;
  flex: 1;
  min-height: 0;
}

/* ── PICKER VIEW — category selection grid ──────────────────────────────── */
#pickerView {
  flex: 1;
  display: flex;
  flex-direction: column;
}

.picker-header {
  padding: 28px 28px 0;
  display: flex;
  flex-direction: column;
  gap: 4px;
}

.picker-overline {
  font-family: var(--mono);
  font-size: 8px;
  letter-spacing: .22em;
  text-transform: uppercase;
  color: var(--acid);
  display: flex;
  align-items: center;
  gap: 8px;
}

.picker-overline::before {
  content: '';
  display: block;
  width: 5px;
  height: 5px;
  border-radius: 50%;
  background: var(--acid);
  box-shadow: var(--acid-glow-sm);
  animation: pulse 2s ease-in-out infinite;
}

@keyframes pulse {
  0%,100% { opacity: 1; }
  50%      { opacity: .4; }
}

.picker-headline {
  font-family: var(--font);
  font-size: clamp(28px, 4vw, 44px);
  font-weight: 800;
  letter-spacing: -.04em;
  color: var(--ink);
  line-height: 1.0;
}

.picker-sub {
  font-family: var(--mono);
  font-size: 11px;
  color: var(--ink3);
  margin-top: 8px;
  line-height: 1.6;
}

/* Category grid */
.cat-grid {
  display: grid;
  grid-template-columns: 1fr 1fr;
  gap: 1px;
  background: var(--border);
  border-top: 1px solid var(--border);
  margin-top: 24px;
  flex: 1;
}

@media (min-width: 600px) {
  .cat-grid { grid-template-columns: repeat(3, 1fr); }
}

@media (min-width: 900px) {
  .cat-grid { grid-template-columns: repeat(5, 1fr); }
}

.cat-zone {
  position: relative;
  background: var(--surface);
  cursor: pointer;
  overflow: hidden;
  height: 220px;
  min-height: 180px;
  max-height: 280px;
  display: flex;
  flex-direction: column;
  justify-content: flex-end;
  padding: 16px;
  transition: background .2s;
  -webkit-tap-highlight-color: transparent;
}

.cat-zone:hover {
  background: var(--surface2);
}

.cat-zone:hover .cat-zone-img {
  opacity: .35;
  transform: scale(1.04);
}

.cat-zone:hover .cat-zone-arrow {
  border-color: rgba(200,255,0,.40);
  color: var(--acid);
  box-shadow: var(--acid-glow-sm);
}

/* Full bleed image — if present, takes priority over gradient art */
.cat-zone-img {
  position: absolute;
  inset: 0;
  width: 100%;
  height: 100%;
  object-fit: cover;
  opacity: .40;
  transition: opacity .4s ease, transform .4s ease;
  z-index: 1;
}

/* CSS gradient art — shows when image is absent or fails */
.cat-zone-art {
  position: absolute;
  inset: 0;
  opacity: .55;
  transition: opacity .4s ease, transform .4s ease;
  z-index: 0;
}

.cat-zone:hover .cat-zone-art { opacity: .35; transform: scale(1.04); }

/* Per-category gradients */
.cat-zone-art--cards {
  background:
    radial-gradient(ellipse 60% 80% at 80% 20%, rgba(200,255,0,.35) 0%, transparent 65%),
    radial-gradient(ellipse 50% 60% at 20% 80%, rgba(100,200,255,.20) 0%, transparent 60%),
    linear-gradient(135deg, #0a0f05 0%, #111a08 100%);
}

.cat-zone-art--shirts {
  background:
    radial-gradient(ellipse 60% 80% at 75% 25%, rgba(255,120,80,.30) 0%, transparent 65%),
    radial-gradient(ellipse 50% 60% at 25% 75%, rgba(200,80,255,.15) 0%, transparent 60%),
    linear-gradient(135deg, #0f0805 0%, #1a0c08 100%);
}

.cat-zone-art--games {
  background:
    radial-gradient(ellipse 60% 80% at 70% 30%, rgba(80,160,255,.35) 0%, transparent 65%),
    radial-gradient(ellipse 50% 60% at 30% 70%, rgba(200,255,0,.15) 0%, transparent 60%),
    linear-gradient(135deg, #050810 0%, #080c1a 100%);
}

.cat-zone-art--vinyl {
  background:
    radial-gradient(ellipse 70% 70% at 50% 50%, rgba(180,100,255,.25) 0%, transparent 70%),
    radial-gradient(ellipse 40% 60% at 80% 20%, rgba(255,80,160,.20) 0%, transparent 55%),
    linear-gradient(135deg, #08050f 0%, #10081a 100%);
}

.cat-zone-art--other {
  background:
    radial-gradient(ellipse 60% 80% at 65% 35%, rgba(255,200,50,.28) 0%, transparent 65%),
    radial-gradient(ellipse 50% 60% at 35% 65%, rgba(80,255,200,.12) 0%, transparent 60%),
    linear-gradient(135deg, #0f0a03 0%, #1a1005 100%);
}

/* Dark gradient scrim */
.cat-zone-scrim {
  position: absolute;
  inset: 0;
  background: linear-gradient(
    to top,
    rgba(5,5,7,.95) 0%,
    rgba(5,5,7,.30) 60%,
    transparent 100%
  );
}

/* Zone number */
.cat-zone-num {
  position: absolute;
  top: 12px;
  left: 14px;
  font-family: var(--mono);
  font-size: 9px;
  letter-spacing: .12em;
  color: var(--acid);
  opacity: .60;
  z-index: 1;
  display: flex;
  align-items: center;
  gap: 6px;
}

.cat-zone-num svg {
  width: 12px; height: 12px;
  stroke: var(--acid);
  fill: none;
  stroke-width: 1.5;
  opacity: .60;
}

/* Zone arrow */
.cat-zone-arrow {
  position: absolute;
  top: 12px;
  right: 12px;
  width: 28px;
  height: 28px;
  border: 1px solid var(--border2);
  border-radius: 50%;
  display: flex;
  align-items: center;
  justify-content: center;
  color: var(--ink3);
  z-index: 1;
  transition: all .2s;
}

.cat-zone-arrow svg {
  width: 12px; height: 12px;
  stroke: currentColor;
  fill: none;
  stroke-width: 1.8;
}

/* Zone content */
.cat-zone-content {
  position: relative;
  z-index: 1;
}

.cat-zone-name {
  font-family: var(--font);
  font-size: 17px;
  font-weight: 700;
  letter-spacing: -.02em;
  color: var(--ink);
  line-height: 1.1;
}

.cat-zone-desc {
  font-family: var(--mono);
  font-size: 8px;
  letter-spacing: .06em;
  color: var(--ink3);
  margin-top: 4px;
  text-transform: uppercase;
}

/* ── SCAN VIEW — after category selected ─────────────────────────────────── */
#scanView {
  display: none;
  flex: 1;
  flex-direction: column;
}

/* Scan header */
.scan-header {
  display: flex;
  align-items: center;
  gap: 12px;
  padding: 12px 20px;
  border-bottom: 1px solid var(--border);
  background: var(--surface2);
}

.scan-breadcrumb {
  font-family: var(--mono);
  font-size: 8px;
  letter-spacing: .14em;
  text-transform: uppercase;
  color: var(--acid);
  display: flex;
  align-items: center;
  gap: 6px;
}

.scan-breadcrumb::before {
  content: '';
  width: 5px; height: 5px;
  border-radius: 50%;
  background: var(--acid);
  box-shadow: var(--acid-glow-sm);
}

.scan-change-btn {
  font-family: var(--mono);
  font-size: 8px;
  letter-spacing: .10em;
  text-transform: uppercase;
  color: var(--ink3);
  background: none;
  border: 1px solid var(--border);
  border-radius: var(--radius-md);
  padding: 4px 10px;
  cursor: pointer;
  transition: color .15s, border-color .15s;
  display: flex;
  align-items: center;
  gap: 5px;
  margin-left: auto;
}

.scan-change-btn:hover { color: var(--ink); border-color: var(--border2); }

.scan-change-btn svg {
  width: 11px; height: 11px;
  stroke: currentColor; fill: none; stroke-width: 1.8;
}

/* Cat pills */
.cat-pills {
  display: flex;
  gap: 4px;
  padding: 10px 20px;
  border-bottom: 1px solid var(--border);
  overflow-x: auto;
  scrollbar-width: none;
}

.cat-pills::-webkit-scrollbar { display: none; }

.cat-pill-btn {
  display: flex;
  align-items: center;
  gap: 5px;
  padding: 5px 12px;
  border-radius: 20px;
  font-family: var(--mono);
  font-size: 8px;
  letter-spacing: .08em;
  text-transform: uppercase;
  color: var(--ink3);
  background: var(--surface2);
  border: 1px solid var(--border);
  cursor: pointer;
  transition: all .15s;
  white-space: nowrap;
  flex-shrink: 0;
}

.cat-pill-btn svg { width: 11px; height: 11px; stroke: currentColor; fill: none; stroke-width: 1.5; }
.cat-pill-btn:hover { color: var(--ink); border-color: var(--border2); }
.cat-pill-btn.active {
  background: var(--acid-dim);
  border-color: rgba(200,255,0,.25);
  color: var(--acid);
}

/* Scan body — left + right split */
.scan-body {
  display: flex;
  flex-direction: column;
  flex: 1;
  min-height: 0;
  overflow: hidden;
}

@media (min-width: 700px) {
  .scan-body { flex-direction: row; }
}

/* Left — dropzone + form */
.scan-left {
  padding: 20px 20px 20px 20px;
  display: flex;
  flex-direction: column;
  gap: 16px;
  overflow-y: auto;
  background: var(--surface2);
  border-right: 1px solid var(--border);
}

@media (min-width: 700px) {
  .scan-left {
    width: 340px;
    flex-shrink: 0;
  }
}

@media (min-width: 1100px) {
  .scan-left { width: 380px; }
}

/* Dropzone */
.dropzone-area {
  border: 1.5px dashed rgba(200,255,0,.25);
  border-radius: var(--radius-md);
  padding: 32px 16px;
  text-align: center;
  cursor: pointer;
  transition: all .2s;
  position: relative;
  background: rgba(200,255,0,.02);
}

.dropzone-area:hover,
.dropzone-area.drag-over {
  border-color: rgba(200,255,0,.55);
  background: rgba(200,255,0,.04);
  box-shadow: 0 0 24px rgba(200,255,0,.08);
}

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

</style>
</head>

<body>
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
      <a href="/scanner.php" class="cv-nav-item active">
        <svg viewBox="0 0 24 24"><path d="M3 7V5a2 2 0 0 1 2-2h2M17 3h2a2 2 0 0 1 2 2v2M21 17v2a2 2 0 0 1-2 2h-2M7 21H5a2 2 0 0 1-2-2v-2"/><rect x="7" y="7" width="10" height="10" rx="1"/></svg>
        <span class="cv-nav-label">Scan</span>
      </a>
      <a href="/collection.php" class="cv-nav-item">
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
        <a href="/logout.php" class="cv-icon-btn" style="flex:1;text-decoration:none" aria-label="Sign out">
          <svg viewBox="0 0 24 24"><path d="M9 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h4"/><polyline points="16,17 21,12 16,7"/><line x1="21" y1="12" x2="9" y2="12"/></svg>
        </a>
      </div>
    </div>

    <!-- Mobile controls -->
    <div class="cv-mobile-controls">
      <button class="cv-icon-btn" onclick="toggleTheme()" id="themeToggleMobile" aria-label="Toggle theme">
        <span id="themeIconWrapMobile"></span>
      </button>
      <a href="/logout.php" class="cv-icon-btn" aria-label="Sign out">
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
            <img class="cat-zone-img" src="/images/card-cards.jpg" alt="" loading="eager" onerror="this.style.display='none'">
            <div class="cat-zone-art cat-zone-art--cards"></div>
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
            <img class="cat-zone-img" src="/images/card-shirts.jpg" alt="" loading="lazy" onerror="this.style.display='none'">
            <div class="cat-zone-art cat-zone-art--shirts"></div>
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
            <img class="cat-zone-img" src="/images/card-games.jpg" alt="" loading="lazy" onerror="this.style.display='none'">
            <div class="cat-zone-art cat-zone-art--games"></div>
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
            <img class="cat-zone-img" src="/images/card-vinyl.jpg" alt="" loading="lazy" onerror="this.style.display='none'">
            <div class="cat-zone-art cat-zone-art--vinyl"></div>
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
            <img class="cat-zone-img" src="/images/card-other.jpg" alt="" loading="lazy" onerror="this.style.display='none'">
            <div class="cat-zone-art cat-zone-art--other"></div>
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
              <a href="/collection.php" class="btn btn-ghost" style="height:28px;font-size:8px">View All</a>
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
// Theme functions provided by theme.php (included in <head>)
// Extend _renderThemeIcon to also update mobile icon wrapper
const _origRenderThemeIcon = typeof _renderThemeIcon === 'function' ? _renderThemeIcon : null;
function _renderThemeIcon(t) {
  const icon = t === 'dark'
    ? '<svg viewBox="0 0 24 24" width="13" height="13" fill="none" stroke="currentColor" stroke-width="1.5"><circle cx="12" cy="12" r="4"/><path d="M12 2v2M12 20v2M4.93 4.93l1.41 1.41M17.66 17.66l1.41 1.41M2 12h2M20 12h2M4.93 19.07l1.41-1.41M17.66 6.34l1.41-1.41"/></svg>'
    : '<svg viewBox="0 0 24 24" width="13" height="13" fill="none" stroke="currentColor" stroke-width="1.5"><path d="M21 12.79A9 9 0 1 1 11.21 3 7 7 0 0 0 21 12.79z"/></svg>';
  ['themeIconWrap','themeIconWrapMobile'].forEach(id => {
    const el = document.getElementById(id);
    if (el) el.innerHTML = icon;
  });
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
    const resp = await fetch('/api.php',{method:'POST',body:fd,credentials:'same-origin'});
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
    const resp = await fetch('/api.php',{method:'POST',body:fd,credentials:'same-origin'});
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
    const r = await fetch('/api.php?action=collection&category=all',{credentials:'same-origin'});
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
    fetch('/api.php?'+new URLSearchParams({action:'getImage',id:item.id,query:q,cat:item.category}),{credentials:'same-origin'})
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
  fetch('/api.php?'+new URLSearchParams({action:'getImage',id:item.id,query:q,cat:item.category}),{credentials:'same-origin'})
    .then(r=>r.json()).then(d=>{if(d.url)document.getElementById('modalImg').src=d.url;}).catch(()=>{});
  fetch('/api.php?'+new URLSearchParams({action:'getPrices'}),{credentials:'same-origin'})
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
