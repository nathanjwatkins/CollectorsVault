<?php
ob_start();
header('Cache-Control: no-cache, must-revalidate, max-age=0');
header('Pragma: no-cache');
session_start();
if (isset($_SESSION['user'])) { header('Location: scanner.php'); exit; }
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8"/>
<script>(function(){var t=localStorage.getItem('cv_theme')||'dark';document.documentElement.setAttribute('data-theme',t);}());</script>
<meta name="theme-color" content="#111111" media="(prefers-color-scheme: dark)">
<meta name="theme-color" content="#F4F3F1" media="(prefers-color-scheme: light)">
<meta name="viewport" content="width=device-width, initial-scale=1.0, viewport-fit=cover"/>
<title>CollectorVault</title>
<link rel="preconnect" href="https://fonts.googleapis.com">
<link href="https://fonts.googleapis.com/css2?family=Geist+Mono:wght@300;400;500&family=Geist:wght@300;400;500;600&display=swap" rel="stylesheet">
<style>
*, *::before, *::after { box-sizing: border-box; margin: 0; padding: 0; }

:root {
  --bg:        #F4F3F1;
  --surface:   #FFFFFF;
  --ink:       #141414;
  --ink2:      #3D3D3D;
  --ink3:      #888888;
  --border:    #DCDCDC;
  --red:       #C13528;
  --green:     #1A6640;
  --font-sans: 'Geist', 'IBM Plex Sans', sans-serif;
  --font-mono: 'Geist Mono', 'IBM Plex Mono', monospace;
}
[data-theme="dark"] {
  --bg:        #0E0E0E;
  --surface:   #1A1A1A;
  --ink:       #F0EDE8;
  --ink2:      #BEBEBE;
  --ink3:      #707070;
  --border:    #2E2E2E;
  --red:       #E04035;
  --green:     #2DA05C;
}

html, body {
  height: 100%;
  font-family: var(--font-sans);
  color: var(--ink);
  -webkit-font-smoothing: antialiased;
  background: #111111;
}

a, a:visited { color: inherit; text-decoration: none; }

/* ── Full-bleed background photo ─────────────────────────────────────────── */
.page {
  min-height: 100dvh;
  display: flex;
  position: relative;
  isolation: isolate;
}

/* Blurred photo layer */
.page::before {
  content: '';
  position: fixed;
  inset: 0;
  z-index: -2;
  background-image: url('/images/bg-dark.jpg');
  background-size: cover;
  background-position: center;
  filter: blur(8px) saturate(0.80) brightness(0.40);
  transform: scale(1.06);
}
[data-theme="light"] .page::before {
  background-image: url('/images/bg-light.jpg');
  filter: blur(8px) saturate(0.85) brightness(0.55);
}

/* Grain layer */
.page::after {
  content: '';
  position: fixed;
  inset: 0;
  z-index: -1;
  background-image: url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' width='280' height='280'%3E%3Cfilter id='n'%3E%3CfeTurbulence type='fractalNoise' baseFrequency='0.72' numOctaves='4' stitchTiles='stitch'/%3E%3C/filter%3E%3Crect width='280' height='280' filter='url(%23n)' opacity='1'/%3E%3C/svg%3E");
  background-size: 280px 280px;
  opacity: 0.12;
  pointer-events: none;
}

/* Dark vignette over photo */
.vignette {
  position: fixed;
  inset: 0;
  z-index: -1;
  background:
    radial-gradient(ellipse 80% 60% at 50% 0%,   rgba(5,5,5,.80) 0%, transparent 70%),
    radial-gradient(ellipse 60% 80% at 100% 50%,  rgba(5,5,5,.65) 0%, transparent 65%),
    radial-gradient(ellipse 60% 80% at 0% 50%,    rgba(5,5,5,.65) 0%, transparent 65%),
    radial-gradient(ellipse 80% 60% at 50% 100%,  rgba(0,0,0,.88) 0%, transparent 70%),
    radial-gradient(ellipse 50% 50% at 0% 100%,   rgba(0,0,0,.92) 0%, transparent 58%);
  pointer-events: none;
}
[data-theme="light"] .vignette {
  background:
    radial-gradient(ellipse 80% 60% at 50% 0%,   rgba(15,15,15,.65) 0%, transparent 70%),
    radial-gradient(ellipse 60% 80% at 100% 50%,  rgba(12,12,12,.50) 0%, transparent 65%),
    radial-gradient(ellipse 60% 80% at 0% 50%,    rgba(12,12,12,.50) 0%, transparent 65%),
    radial-gradient(ellipse 80% 60% at 50% 100%,  rgba(8,8,8,.75) 0%, transparent 70%),
    radial-gradient(ellipse 50% 50% at 0% 100%,   rgba(8,8,8,.85) 0%, transparent 60%);
}

/* ── Layout ──────────────────────────────────────────────────────────────── */
.hero-side {
  display: none;
}

.form-side {
  flex: 1;
  display: flex;
  flex-direction: column;
  align-items: center;
  justify-content: center;
  padding: 40px 20px;
  min-height: 100dvh;
}

@media (min-width: 820px) {
  .hero-side {
    flex: 1;
    display: flex;
    flex-direction: column;
    justify-content: space-between;
    padding: 64px 72px;
    position: relative;
  }
  .form-side {
    width: 480px;
    flex: none;
    padding: 64px 72px;
  }
}

/* ── Hero content ─────────────────────────────────────────────────────────── */
.hero-wordmark {
  font-family: var(--font-mono);
  font-size: 11px;
  letter-spacing: .14em;
  text-transform: uppercase;
  color: rgba(245,245,245,.55);
  display: flex;
  align-items: center;
  gap: 8px;
}
.hero-dot {
  width: 5px; height: 5px;
  background: rgba(245,245,245,.35);
  border-radius: 50%;
}
.hero-headline {
  font-size: clamp(42px, 4vw, 64px);
  font-weight: 500;
  color: #F5F5F5;
  line-height: 1.05;
  letter-spacing: -.03em;
}
.hero-sub {
  font-family: var(--font-mono);
  font-size: 10px;
  letter-spacing: .1em;
  text-transform: uppercase;
  color: rgba(245,245,245,.30);
  margin-top: 16px;
}
.hero-features {
  display: flex;
  flex-direction: column;
  gap: 12px;
}
.hero-feat {
  font-family: var(--font-mono);
  font-size: 10px;
  letter-spacing: .06em;
  text-transform: uppercase;
  color: rgba(245,245,245,.35);
  display: flex;
  align-items: center;
  gap: 10px;
}
.hero-feat::before {
  content: '';
  width: 3px; height: 3px;
  background: rgba(245,245,245,.25);
  border-radius: 50%;
  flex-shrink: 0;
}

/* ── Glass card ───────────────────────────────────────────────────────────── */
.glass-card {
  width: 100%;
  max-width: 380px;
  background: rgba(255,255,255,.10);
  border: 1px solid rgba(255,255,255,.18);
  border-radius: 16px;
  padding: 36px 32px;
  backdrop-filter: blur(24px) saturate(1.4) brightness(1.15);
  -webkit-backdrop-filter: blur(24px) saturate(1.4) brightness(1.15);
  box-shadow:
    inset 0 1px 0 rgba(255,255,255,.18),
    0 8px 48px rgba(0,0,0,.40),
    0 2px 8px rgba(0,0,0,.20);
  /* Specular highlight */
  position: relative;
}
.glass-card::before {
  content: '';
  position: absolute;
  top: 0; left: 10%; right: 10%;
  height: 1px;
  background: linear-gradient(90deg, transparent, rgba(255,255,255,.55) 50%, transparent);
  border-radius: 0;
}

[data-theme="light"] .glass-card {
  background: rgba(255,255,255,.18);
  border-color: rgba(255,255,255,.45);
  box-shadow:
    inset 0 1px 0 rgba(255,255,255,.55),
    0 8px 48px rgba(0,0,0,.22),
    0 2px 8px rgba(0,0,0,.12);
}

/* ── Brand mark ───────────────────────────────────────────────────────────── */
.brand {
  margin-bottom: 28px;
  display: flex;
  flex-direction: column;
  gap: 4px;
}
.brand-name {
  font-family: var(--font-mono);
  font-size: 13px;
  font-weight: 500;
  letter-spacing: .12em;
  text-transform: uppercase;
  color: rgba(245,245,245,.90);
  display: flex;
  align-items: center;
  gap: 7px;
}
.brand-dot {
  width: 5px; height: 5px;
  background: rgba(245,245,245,.60);
  border-radius: 50%;
}
.brand-tag {
  font-family: var(--font-mono);
  font-size: 9px;
  letter-spacing: .1em;
  text-transform: uppercase;
  color: rgba(245,245,245,.35);
}

/* ── Tabs ─────────────────────────────────────────────────────────────────── */
.tabs {
  display: flex;
  margin-bottom: 24px;
  border-bottom: 1px solid rgba(255,255,255,.12);
}
.tab {
  flex: 1;
  padding: 8px;
  font-family: var(--font-mono);
  font-size: 10px;
  letter-spacing: .07em;
  text-transform: uppercase;
  color: rgba(245,245,245,.35);
  background: transparent;
  border: none;
  cursor: pointer;
  border-bottom: 1.5px solid transparent;
  margin-bottom: -1px;
  transition: all .15s;
}
.tab.active {
  color: rgba(245,245,245,.90);
  border-bottom-color: rgba(245,245,245,.70);
}
.tab:hover:not(.active) {
  color: rgba(245,245,245,.60);
}

/* ── Fields ───────────────────────────────────────────────────────────────── */
.field { margin-bottom: 14px; }
.field label {
  display: block;
  font-family: var(--font-mono);
  font-size: 9px;
  letter-spacing: .1em;
  text-transform: uppercase;
  color: rgba(245,245,245,.40);
  margin-bottom: 6px;
}
.field input {
  width: 100%;
  background: rgba(255,255,255,.07);
  border: 1px solid rgba(255,255,255,.14);
  border-radius: 8px;
  padding: 12px 14px;
  font-family: var(--font-sans);
  font-size: 14px;
  color: rgba(245,245,245,.90);
  transition: border-color .15s, box-shadow .15s;
}
.field input:focus {
  outline: none;
  border-color: rgba(255,255,255,.40);
  box-shadow: 0 0 0 3px rgba(255,255,255,.06);
}
.field input::placeholder { color: rgba(245,245,245,.22); }

/* ── Submit button ────────────────────────────────────────────────────────── */
.btn-submit {
  width: 100%;
  padding: 13px;
  background: rgba(245,245,245,.92);
  color: #111111;
  border: none;
  border-radius: 8px;
  font-family: var(--font-mono);
  font-size: 11px;
  font-weight: 500;
  letter-spacing: .09em;
  text-transform: uppercase;
  cursor: pointer;
  margin-top: 8px;
  transition: opacity .15s, transform .12s;
}
.btn-submit:hover { opacity: .88; transform: translateY(-1px); }
.btn-submit:active { transform: translateY(0); }
.btn-submit.loading { opacity: .5; pointer-events: none; }
.btn-submit.loading::after { content: ' …'; }

/* ── Messages ─────────────────────────────────────────────────────────────── */
.msg {
  padding: 10px 12px;
  border-radius: 8px;
  font-size: 12px;
  margin-bottom: 14px;
  display: none;
  font-family: var(--font-sans);
}
.msg.err {
  background: rgba(193,53,40,.15);
  border: 1px solid rgba(193,53,40,.25);
  color: #ff8880;
}
.msg.ok {
  background: rgba(26,102,64,.15);
  border: 1px solid rgba(26,102,64,.25);
  color: #60d090;
}

.form-panel { display: none; }
.form-panel.active { display: block; }

/* ── Footer ───────────────────────────────────────────────────────────────── */
.footer {
  margin-top: 20px;
  font-family: var(--font-mono);
  font-size: 9px;
  letter-spacing: .07em;
  text-transform: uppercase;
  color: rgba(245,245,245,.25);
  text-align: center;
}

/* ── Theme toggle ─────────────────────────────────────────────────────────── */
.theme-toggle {
  position: fixed;
  top: 20px; right: 20px;
  width: 34px; height: 34px;
  border-radius: 50%;
  background: rgba(255,255,255,.10);
  border: 1px solid rgba(255,255,255,.16);
  backdrop-filter: blur(10px);
  cursor: pointer;
  display: flex; align-items: center; justify-content: center;
  transition: background .2s;
  z-index: 10;
}
.theme-toggle:hover { background: rgba(255,255,255,.18); }
.theme-toggle svg { width: 16px; height: 16px; stroke: rgba(245,245,245,.70); fill: none; stroke-width: 1.5; }
</style>
</head>
<body>

<div class="page">
  <div class="vignette"></div>

  <!-- Theme toggle -->
  <button class="theme-toggle" onclick="toggleTheme()" id="themeBtn" aria-label="Toggle theme">
    <svg viewBox="0 0 24 24"><circle cx="12" cy="12" r="4"/><path d="M12 2v2M12 20v2M4.93 4.93l1.41 1.41M17.66 17.66l1.41 1.41M2 12h2M20 12h2M6.34 17.66l-1.41 1.41M19.07 4.93l-1.41 1.41"/></svg>
  </button>

  <!-- Hero (desktop left) -->
  <div class="hero-side" id="heroSide">
    <div class="hero-wordmark"><span class="hero-dot"></span> CollectorVault</div>
    <div>
      <div class="hero-headline">Scan.<br>Catalogue.<br>Track.</div>
      <div class="hero-sub">AI-powered collectibles manager</div>
    </div>
    <div class="hero-features">
      <div class="hero-feat">Gemini AI identification</div>
      <div class="hero-feat">Live eBay market pricing</div>
      <div class="hero-feat">Cards, shirts, games, vinyl &amp; more</div>
    </div>
  </div>

  <!-- Form side -->
  <div class="form-side" id="formSide">
    <div class="glass-card">
      <div class="brand">
        <div class="brand-name"><span class="brand-dot"></span> CollectorVault</div>
        <div class="brand-tag">Universal Collectibles Scanner</div>
      </div>

      <div class="tabs">
        <button class="tab active" onclick="switchTab('login')">Sign In</button>
        <button class="tab" onclick="switchTab('register')">Register</button>
      </div>

      <!-- Login -->
      <div class="form-panel active" id="panel-login">
        <div class="msg err" id="login-error"></div>
        <div class="field"><label>Username</label><input type="text" id="login-username" placeholder="username" autocomplete="username"/></div>
        <div class="field"><label>Password</label><input type="password" id="login-password" placeholder="••••••••" autocomplete="current-password"/></div>
        <button class="btn-submit" id="login-btn" onclick="doLogin()">Sign In →</button>
      </div>

      <!-- Register -->
      <div class="form-panel" id="panel-register">
        <div class="msg err" id="reg-error"></div>
        <div class="msg ok"  id="reg-ok"></div>
        <div class="field"><label>Username</label><input type="text" id="reg-username" placeholder="choose a username" autocomplete="username"/></div>
        <div class="field"><label>Password</label><input type="password" id="reg-password" placeholder="min 6 characters" autocomplete="new-password"/></div>
        <div class="field"><label>Confirm</label><input type="password" id="reg-confirm" placeholder="repeat password" autocomplete="new-password"/></div>
        <button class="btn-submit" id="reg-btn" onclick="doRegister()">Create Account →</button>
      </div>
    </div>
    <div class="footer">CollectorVault &copy; 2025</div>
  </div>

</div>

<script>
// Theme
function toggleTheme() {
  const t = document.documentElement.getAttribute('data-theme') === 'dark' ? 'light' : 'dark';
  document.documentElement.setAttribute('data-theme', t);
  localStorage.setItem('cv_theme', t);
}

// Layout
function applyLayout() {
  const hero = document.getElementById('heroSide');
  const form = document.getElementById('formSide');
  if (window.innerWidth >= 820) {
    hero.style.display = 'flex';
    form.style.flex = 'none';
  } else {
    hero.style.display = 'none';
    form.style.flex = '1';
  }
}
applyLayout();
window.addEventListener('resize', applyLayout);

// Tabs
function switchTab(t) {
  document.querySelectorAll('.tab').forEach((el,i) => el.classList.toggle('active', (i===0) === (t==='login')));
  document.querySelectorAll('.form-panel').forEach((el,i) => el.classList.toggle('active', (i===0) === (t==='login')));
}

// Login
async function doLogin() {
  const btn = document.getElementById('login-btn');
  const err = document.getElementById('login-error');
  const user = document.getElementById('login-username').value.trim();
  const pass = document.getElementById('login-password').value;
  err.style.display = 'none';
  if (!user || !pass) { showErr(err, 'Please enter username and password'); return; }
  btn.classList.add('loading');
  try {
    const fd = new FormData();
    fd.append('action','login'); fd.append('username',user); fd.append('password',pass);
    const r = await fetch('/beta/api.php', { method:'POST', body:fd, credentials:'same-origin' });
    const d = await r.json();
    if (d.ok) { window.location = '/beta/scanner.php'; }
    else showErr(err, d.error || 'Login failed');
  } catch(e) { showErr(err, 'Connection error — please try again'); }
  finally { btn.classList.remove('loading'); }
}

// Register
async function doRegister() {
  const btn = document.getElementById('reg-btn');
  const err = document.getElementById('reg-error');
  const ok  = document.getElementById('reg-ok');
  const user = document.getElementById('reg-username').value.trim();
  const pass = document.getElementById('reg-password').value;
  const conf = document.getElementById('reg-confirm').value;
  err.style.display = 'none'; ok.style.display = 'none';
  if (!user || !pass) { showErr(err,'All fields required'); return; }
  if (pass !== conf)  { showErr(err,'Passwords do not match'); return; }
  if (pass.length < 6){ showErr(err,'Password must be at least 6 characters'); return; }
  btn.classList.add('loading');
  try {
    const fd = new FormData();
    fd.append('action','register'); fd.append('username',user); fd.append('password',pass);
    const r = await fetch('/beta/api.php', { method:'POST', body:fd, credentials:'same-origin' });
    const d = await r.json();
    if (d.ok) { ok.textContent = 'Account created — sign in now'; ok.style.display = 'block'; setTimeout(() => switchTab('login'), 1200); }
    else showErr(err, d.error || 'Registration failed');
  } catch(e) { showErr(err, 'Connection error'); }
  finally { btn.classList.remove('loading'); }
}

function showErr(el, msg) { el.textContent = msg; el.style.display = 'block'; }

document.addEventListener('keydown', e => {
  if (e.key !== 'Enter') return;
  if (document.getElementById('panel-login').classList.contains('active')) doLogin();
  else doRegister();
});
</script>
</body>
</html>
