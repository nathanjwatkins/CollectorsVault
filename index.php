<?php
ob_start();
session_start();
if (isset($_SESSION['user'])) { header('Location: scanner.php'); exit; }
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8"/>
<script>
(function(){var t=localStorage.getItem('cv_theme')||'light';document.documentElement.setAttribute('data-theme',t);}());
</script>
<meta name="theme-color" content="#111111" media="(prefers-color-scheme: dark)">
<meta name="theme-color" content="#F4F3F1" media="(prefers-color-scheme: light)">
<meta name="viewport" content="width=device-width, initial-scale=1.0, viewport-fit=cover"/>
<title>CollectorVault</title>
<link rel="preconnect" href="https://fonts.googleapis.com">
<link href="https://fonts.googleapis.com/css2?family=Geist+Mono:wght@300;400;500&family=Geist:wght@300;400;500;600&display=swap" rel="stylesheet">
<style>
*, *::before, *::after { box-sizing: border-box; margin: 0; padding: 0; }
:root {
  --bg:#F2F0EC; --surface:#FFFFFF; --ink:#0E0D0B; --ink2:#3A3832;
  --ink3:#8C8880; --border:#D8D5CF; --red:#C13528;
  --font-sans:'Geist','IBM Plex Sans',sans-serif;
  --font-mono:'Geist Mono','IBM Plex Mono',monospace;
}
[data-theme="dark"] {
  --bg:#0C0B09; --surface:#181512; --ink:#F0EDE7; --ink2:#C0BCB4;
  --ink3:#6A6660; --border:#2E2C28; --red:#E04035;
}
html, body { height:100%; background:var(--bg); font-family:var(--font-sans); color:var(--ink); -webkit-font-smoothing:antialiased; }

.page {
  min-height: 100dvh;
  display: flex; flex-direction: column;
  align-items: center; justify-content: center;
  padding: 40px 20px;
  background: var(--bg);
  position: relative;
}

/* Subtle grid background */
.page::before {
  content:''; position:fixed; inset:0; z-index:0;
  background-image: linear-gradient(var(--border) 1px, transparent 1px),
                    linear-gradient(90deg, var(--border) 1px, transparent 1px);
  background-size: 40px 40px;
  opacity: .35;
}

.card {
  position: relative; z-index: 1;
  background: var(--surface);
  border: 1px solid var(--border);
  border-radius: 14px;
  padding: 36px 32px;
  width: 100%; max-width: 400px;
  box-shadow: 0 4px 40px rgba(14,13,11,.1);
  transition: background-color .2s, border-color .2s;
}
/* Ensure links on index never go blue */
a, a:visited { color: inherit; text-decoration: none; }

/* Desktop: hero panel left, form right */
@media (min-width: 800px) {
  .page {
    flex-direction: row;
    align-items: stretch;
    padding: 0;
    overflow: hidden;
  }
  .page::before { display: none; }
  .hero-side {
    flex: 1;
    background: #0E0D0B;
    display: flex;
    flex-direction: column;
    justify-content: space-between;
    padding: 56px 64px;
    position: relative;
    overflow: hidden;
  }
  .hero-side::before {
    content: '';
    position: absolute; inset: 0;
    background-image:
      linear-gradient(rgba(240,237,231,.04) 1px, transparent 1px),
      linear-gradient(90deg, rgba(240,237,231,.04) 1px, transparent 1px);
    background-size: 48px 48px;
  }
  .hero-wordmark {
    font-family: var(--font-mono);
    font-size: 12px; letter-spacing: .12em; text-transform: uppercase;
    color: rgba(240,237,231,.7);
    display: flex; align-items: center; gap: 8px;
    position: relative; z-index: 1;
  }
  .hero-dot { width: 5px; height: 5px; background: rgba(240,237,231,.5); border-radius: 50%; }
  .hero-headline {
    font-family: var(--font-sans);
    font-size: clamp(36px, 3.5vw, 54px);
    font-weight: 500;
    color: #F0EDE7;
    line-height: 1.1;
    letter-spacing: -.03em;
    position: relative; z-index: 1;
  }
  .hero-sub {
    font-family: var(--font-mono);
    font-size: 10px; letter-spacing: .1em; text-transform: uppercase;
    color: rgba(240,237,231,.35);
    margin-top: 14px;
    position: relative; z-index: 1;
  }
  .hero-features {
    display: flex; flex-direction: column; gap: 10px;
    position: relative; z-index: 1;
  }
  .hero-feat {
    font-family: var(--font-mono); font-size: 10px;
    letter-spacing: .06em; text-transform: uppercase;
    color: rgba(240,237,231,.4);
    display: flex; align-items: center; gap: 10px;
  }
  .hero-feat::before {
    content: '';
    width: 4px; height: 4px;
    background: rgba(240,237,231,.25);
    border-radius: 50%; flex-shrink: 0;
  }
  .form-side {
    width: 480px;
    display: flex;
    flex-direction: column;
    align-items: center;
    justify-content: center;
    padding: 56px 64px;
    background: var(--bg);
  }
  .card {
    border: none;
    border-radius: 0;
    box-shadow: none;
    padding: 0;
    background: transparent;
    max-width: 100%;
    width: 100%;
  }
  .card .brand { display: none; }
  .footer { margin-top: 32px; text-align: center; }
}

.brand {
  margin-bottom: 32px;
  display: flex; flex-direction: column; align-items: flex-start; gap: 4px;
}
.brand-name {
  font-family: var(--font-mono);
  font-size: 14px; font-weight: 500; letter-spacing: .1em;
  text-transform: uppercase; color: var(--ink);
  display: flex; align-items: center; gap: 6px;
}
.brand-dot { width: 6px; height: 6px; background: var(--ink); border-radius: 50%; display: inline-block; }
.brand-tag {
  font-family: var(--font-mono); font-size: 9px;
  letter-spacing: .1em; text-transform: uppercase; color: var(--ink3);
}

/* Tabs */
.tabs { display: flex; gap: 0; margin-bottom: 24px; border-bottom: 1px solid var(--border); }
.tab {
  flex: 1; padding: 8px;
  font-family: var(--font-mono); font-size: 10px;
  letter-spacing: .06em; text-transform: uppercase;
  color: var(--ink3); background: transparent;
  border: none; cursor: pointer;
  border-bottom: 1.5px solid transparent;
  margin-bottom: -1px; transition: all .15s;
}
.tab.active { color: var(--ink); border-bottom-color: var(--ink); }

/* Fields */
.field { margin-bottom: 14px; }
.field label {
  display: block; font-family: var(--font-mono);
  font-size: 9px; letter-spacing: .1em; text-transform: uppercase;
  color: var(--ink3); margin-bottom: 6px;
}
.field input {
  width: 100%; background: var(--bg);
  border: 1px solid var(--border); border-radius: 6px;
  padding: 12px 14px;
  font-family: var(--font-sans); font-size: 14px; color: var(--ink);
  transition: border-color .15s, box-shadow .15s;
}
.field input:focus { outline: none; border-color: var(--ink); box-shadow: 0 0 0 3px rgba(14,13,11,.06); }
.field input::placeholder { color: var(--ink3); }

.btn-submit {
  width: 100%; padding: 13px;
  background: var(--ink); color: var(--surface);
  border: none; border-radius: 6px;
  font-family: var(--font-mono); font-size: 11px;
  font-weight: 500; letter-spacing: .08em; text-transform: uppercase;
  cursor: pointer; margin-top: 8px; transition: opacity .15s, transform .12s;
}
.btn-submit:hover { opacity: .88; transform: translateY(-1px); }
.btn-submit:active { transform: translateY(0); }
.btn-submit.loading { opacity: .6; pointer-events: none; }
.btn-submit.loading::after { content: ' …'; }

.msg { padding: 10px 12px; border-radius: 6px; font-size: 12px; margin-bottom: 14px; display: none; font-family: var(--font-sans); }
.msg.err { background: rgba(193,53,40,.08); border: 1px solid rgba(193,53,40,.2); color: var(--red); }
.msg.ok  { background: rgba(26,102,64,.08);  border: 1px solid rgba(26,102,64,.2);  color: #1A6640; }

.form-panel { display: none; }
.form-panel.active { display: block; }

.footer {
  position: relative; z-index: 1;
  margin-top: 20px;
  font-family: var(--font-mono); font-size: 9px;
  letter-spacing: .06em; text-transform: uppercase;
  color: var(--ink3);
}
.form-side-wrap {
  display: flex; flex-direction: column;
  align-items: center; justify-content: center;
  width: 100%;
}
</style>
</head>
<body>
<script>
(function(){
  const t = localStorage.getItem('cv_theme')||'light';
  document.documentElement.setAttribute('data-theme', t);
})();
</script>

<div class="page">

  <!-- Desktop hero panel (left side) -->
  <div class="hero-side" style="display:none" id="heroSide">
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

  <!-- Form side (right on desktop, full on mobile) -->
  <div class="form-side-wrap" id="formSide">
  <div class="card">
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
  </div><!-- /form-side-wrap -->
</div>

<script>
// Show hero panel on desktop
if (window.innerWidth >= 800) {
  document.getElementById('heroSide').style.display = 'flex';
  document.getElementById('formSide').className = 'form-side';
}
window.addEventListener('resize', function() {
  var hs = document.getElementById('heroSide');
  var fs = document.getElementById('formSide');
  if (window.innerWidth >= 800) {
    hs.style.display = 'flex';
    fs.className = 'form-side';
  } else {
    hs.style.display = 'none';
    fs.className = 'form-side-wrap';
  }
});
</script>

<script>
function switchTab(t) {
  document.querySelectorAll('.tab').forEach((el,i)=> el.classList.toggle('active',(i===0)===(t==='login')));
  document.querySelectorAll('.form-panel').forEach((el,i)=> el.classList.toggle('active',(i===0)===(t==='login')));
}

async function doLogin() {
  const btn=document.getElementById('login-btn'), err=document.getElementById('login-error');
  const user=document.getElementById('login-username').value.trim();
  const pass=document.getElementById('login-password').value;
  err.style.display='none';
  if (!user||!pass){showErr(err,'Please enter username and password');return;}
  btn.classList.add('loading');
  try {
    const fd=new FormData(); fd.append('action','login'); fd.append('username',user); fd.append('password',pass);
    const r=await fetch('api.php',{method:'POST',body:fd,credentials:'same-origin'});
    const d=await r.json();
    if (d.ok) { window.location='scanner.php'; }
    else showErr(err, d.error||'Login failed');
  } catch(e){ showErr(err,'Connection error — please try again'); }
  finally { btn.classList.remove('loading'); }
}

async function doRegister() {
  const btn=document.getElementById('reg-btn'), err=document.getElementById('reg-error'), ok=document.getElementById('reg-ok');
  const user=document.getElementById('reg-username').value.trim();
  const pass=document.getElementById('reg-password').value;
  const conf=document.getElementById('reg-confirm').value;
  err.style.display='none'; ok.style.display='none';
  if (!user||!pass){showErr(err,'All fields required');return;}
  if (pass!==conf){showErr(err,'Passwords do not match');return;}
  if (pass.length<6){showErr(err,'Password must be at least 6 characters');return;}
  btn.classList.add('loading');
  try {
    const fd=new FormData(); fd.append('action','register'); fd.append('username',user); fd.append('password',pass);
    const r=await fetch('api.php',{method:'POST',body:fd,credentials:'same-origin'});
    const d=await r.json();
    if (d.ok){ok.textContent='Account created — sign in now';ok.style.display='block';setTimeout(()=>switchTab('login'),1200);}
    else showErr(err,d.error||'Registration failed');
  } catch(e){ showErr(err,'Connection error'); }
  finally { btn.classList.remove('loading'); }
}

function showErr(el,msg){el.textContent=msg;el.style.display='block';}
document.addEventListener('keydown',e=>{
  if(e.key!=='Enter')return;
  if(document.getElementById('panel-login').classList.contains('active'))doLogin();
  else doRegister();
});
</script>
</body>
</html>
