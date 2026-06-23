/**
 * Forms — Global JavaScript
 * Minimal, zero-dependency. Handles Turnstile, toasts, form interactions.
 */

// === Toast ===
const Toast = {
  _timer: null,
  show(msg, duration) {
    duration = duration || 2000;
    let el = document.getElementById('toast');
    if (!el) {
      el = document.createElement('div');
      el.id = 'toast';
      el.className = 'toast';
      document.body.appendChild(el);
    }
    el.textContent = msg;
    el.classList.add('show');
    clearTimeout(this._timer);
    this._timer = setTimeout(function () { el.classList.remove('show'); }, duration);
  }
};

// === Turnstile Callback ===
// Used by Cloudflare Turnstile widget
function onTurnstileSuccess(token) {
  var hidden = document.getElementById('turnstile-token');
  if (hidden) {
    hidden.value = token;
  }
  // Auto-submit if there's a form with data-auto-submit
  var form = document.querySelector('form[data-turnstile-auto]');
  if (form && hidden && hidden.value) {
    form.requestSubmit();
  }
}

// === Turnstile Reset ===
function resetTurnstile() {
  if (typeof turnstile !== 'undefined') {
    turnstile.reset();
  }
  var hidden = document.getElementById('turnstile-token');
  if (hidden) hidden.value = '';
}

// === Language Switcher ===
function switchLang(lang) {
  var url = new URL(window.location.href);
  url.searchParams.set('lang', lang);
  window.location.href = url.toString();
}

// === Copy to clipboard ===
function copyToClipboard(text) {
  if (navigator.clipboard) {
    navigator.clipboard.writeText(text).then(function () {
      Toast.show('Copied!', 1500);
    });
  } else {
    // Fallback
    var ta = document.createElement('textarea');
    ta.value = text;
    ta.style.position = 'fixed';
    ta.style.left = '-9999px';
    document.body.appendChild(ta);
    ta.select();
    document.execCommand('copy');
    document.body.removeChild(ta);
    Toast.show('Copied!', 1500);
  }
}

// === Delete confirmation ===
function confirmDelete(url, msg) {
  msg = msg || 'Are you sure?';
  if (confirm(msg)) {
    window.location.href = url;
  }
}

// === Embed code generator ===
function updateEmbedCode(formId) {
  var theme = document.getElementById('embed-theme').value;
  var lang = document.getElementById('embed-lang').value;
  var base = window.location.origin;
  var src = base + '/embed/' + formId + '?theme=' + theme + '&lang=' + lang;
  var code = '<iframe src="' + src + '"\n'
    + '  style="border:none;width:100%;height:450px;"\n'
    + '  allow="transparency">\n'
    + '</iframe>';
  document.getElementById('embed-code-content').textContent = code;
}

// === Content Modal ===
function showContentModal(content, meta) {
  meta = meta || '';
  var overlay = document.createElement('div');
  overlay.className = 'content-modal-overlay';
  overlay.innerHTML =
    '<div class="content-modal">' +
    '<button class="close-btn" onclick="this.closest(\'.content-modal-overlay\').remove()">\u00d7</button>' +
    '<h3>Submission Content' + (meta ? ' \u2014 ' + meta : '') + '</h3>' +
    '<div class="modal-body">' + escapeHtml(content) + '</div>' +
    '</div>';
  overlay.addEventListener('click', function(e) {
    if (e.target === overlay) overlay.remove();
  });
  document.addEventListener('keydown', function esc(e) {
    if (e.key === 'Escape') { overlay.remove(); document.removeEventListener('keydown', esc); }
  });
  document.body.appendChild(overlay);
}

function escapeHtml(str) {
  var div = document.createElement('div');
  div.appendChild(document.createTextNode(str));
  return div.innerHTML;
}
