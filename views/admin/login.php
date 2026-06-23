<?php
/**
 * Admin Login Page — AJAX submit
 */
$lang = App::lang();
$title = $lang['login'];
$turnstile_site_key = App::config()['turnstile_site_key'];

require __DIR__ . '/../partials/header_simple.php';
?>
<style>
.turnstile-overlay {
  position: fixed; inset: 0; z-index: 999;
  background: rgba(0,0,0,.45);
  display: none; align-items: center; justify-content: center;
}
.turnstile-overlay.show { display: flex; }
.turnstile-modal {
  background: #fff; border-radius: 8px; padding: 28px;
  text-align: center; max-width: 340px; width: 90%;
  box-shadow: 0 4px 24px rgba(0,0,0,.15);
}
.turnstile-modal p { margin-bottom: 16px; font-size: 14px; color: #1d1d1f; }
</style>
</head>
<body class="login-page">
<div class="login-box">
  <h1><?= h($lang['site_name']) ?></h1>
  <p class="login-desc"><?= h($lang['login']) ?></p>
  <div id="login-error"></div>
  <form id="login-form" onsubmit="return false">
    <div class="form-row">
      <div class="form-group">
        <label><?= h($lang['password']) ?></label>
        <input type="password" name="password" id="login-password"
               placeholder="<?= h($lang['password_placeholder']) ?>" required autofocus>
      </div>
    </div>
    <div class="form-row">
      <button type="button" class="btn btn-primary" id="login-btn" onclick="showTurnstile()"><?= h($lang['login']) ?></button>
    </div>
  </form>
</div>

<!-- Turnstile Overlay -->
<div class="turnstile-overlay" id="turnstile-overlay">
  <div class="turnstile-modal">
    <p>Please verify you are human</p>
    <div id="turnstile-widget"></div>
  </div>
</div>

<script src="https://challenges.cloudflare.com/turnstile/v0/api.js"></script>
<script>
var turnstileWidgetId = null;

// Enter key triggers Turnstile
document.getElementById('login-password').addEventListener('keydown', function(e) {
  if (e.key === 'Enter') { e.preventDefault(); showTurnstile(); }
});

function showTurnstile() {
  var pwd = document.getElementById('login-password');
  var errEl = document.getElementById('login-error');
  if (!pwd.value.trim()) {
    errEl.innerHTML = '<div class="alert alert-error">Please enter password.</div>';
    pwd.focus();
    return;
  }
  errEl.innerHTML = '';
  document.getElementById('turnstile-overlay').classList.add('show');

  turnstile.ready(function() {
    if (turnstileWidgetId) { turnstile.remove(turnstileWidgetId); }
    turnstileWidgetId = turnstile.render('#turnstile-widget', {
      sitekey: '<?= h($turnstile_site_key) ?>',
      callback: doLogin,
      'expired-callback': function() {
        turnstile.remove(turnstileWidgetId);
        turnstileWidgetId = null;
      }
    });
  });
}

function doLogin(token) {
  document.getElementById('turnstile-overlay').classList.remove('show');
  var btn = document.getElementById('login-btn');
  var errEl = document.getElementById('login-error');
  btn.disabled = true;
  btn.textContent = '...';

  fetch('/api/auth', {
    method: 'POST',
    headers: { 'Content-Type': 'application/json' },
    body: JSON.stringify({
      password: document.getElementById('login-password').value,
      turnstile_token: token
    })
  })
  .then(function(r) { return r.json(); })
  .then(function(d) {
    if (d.success) {
      window.location.href = '/admin';
    } else {
      errEl.innerHTML = '<div class="alert alert-error">' + (d.error || 'Login failed') + '</div>';
      btn.disabled = false;
      btn.textContent = '<?= h($lang['login']) ?>';
    }
  })
  .catch(function() {
    errEl.innerHTML = '<div class="alert alert-error">Network error</div>';
    btn.disabled = false;
    btn.textContent = '<?= h($lang['login']) ?>';
  });
}
</script>
<?php require __DIR__ . '/../partials/footer_simple.php'; ?>
