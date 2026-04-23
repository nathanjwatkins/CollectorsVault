<script>
/* Theme init — runs before paint to prevent flash of wrong theme */
(function () {
  var t = localStorage.getItem('cv_theme') || 'dark';
  document.documentElement.setAttribute('data-theme', t);
})();

function toggleTheme() {
  var cur  = document.documentElement.getAttribute('data-theme') || 'light';
  var next = cur === 'light' ? 'dark' : 'light';
  document.documentElement.setAttribute('data-theme', next);
  localStorage.setItem('cv_theme', next);
  _renderThemeIcon(next);
}

function _renderThemeIcon(t) {
  var wrap = document.getElementById('themeIconWrap');
  if (!wrap) return;
  if (t === 'dark') {
    wrap.innerHTML = '<svg viewBox="0 0 24 24" width="13" height="13" fill="none" stroke="currentColor" stroke-width="1.5" pointer-events="none"><circle cx="12" cy="12" r="4"/><path d="M12 2v2M12 20v2M4.93 4.93l1.41 1.41M17.66 17.66l1.41 1.41M2 12h2M20 12h2M4.93 19.07l1.41-1.41M17.66 6.34l1.41-1.41"/></svg>';
  } else {
    wrap.innerHTML = '<svg viewBox="0 0 24 24" width="13" height="13" fill="none" stroke="currentColor" stroke-width="1.5" pointer-events="none"><path d="M21 12.79A9 9 0 1 1 11.21 3 7 7 0 0 0 21 12.79z"/></svg>';
  }
}

/* Render icon once DOM is ready */
document.addEventListener('DOMContentLoaded', function () {
  _renderThemeIcon(document.documentElement.getAttribute('data-theme') || 'light');
});
</script>
