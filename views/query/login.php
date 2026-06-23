<?php
/**
 * Query page login — AJAX submit (per-form password)
 * Route: /query/{form_id}/login
 */
$lang = App::lang();
$title = $lang['query_title'];
$turnstile_site_key = App::config()['turnstile_site_key'];
$formId = (int)($_GET['form_id'] ?? 0);

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
  <h1><?= h($lang['query_title']) ?></h1>
  <p class="login-desc"><?= h($lang['query_login_desc']) ?></p>
  <div id="login-error"></div>
  <form id="login-form" onsubmit="return false" data-form-id="<?= $formId ?>">
    <div class="form-row">
      <div class="form-group">
        <label><?= h($lang['password']) ?></label>
        <input type="password" name="password" id="login-password"
               autocomplete="current-password"
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
  var fid = document.getElementById('login-form').getAttribute('data-form-id');
  btn.disabled = true;
  btn.textContent = '...';

  fetch('/api/auth/query?form_id=' + fid, {
    method: 'POST',
    headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
    body: 'password=' + encodeURIComponent(document.getElementById('login-password').value)
      + '&turnstile_token=' + encodeURIComponent(token)
  })
  .then(function(r) { return r.json(); })
  .then(function(d) {
    if (d.success) {
      window.location.href = '/query/' + fid;
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
