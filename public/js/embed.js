/**
 * Embed Form — external JS (CSP-safe, no inline scripts)
 * Reads configuration from data-* attributes on #embed-form.
 */
(function () {
  'use strict';

  var form = document.getElementById('embed-form');
  if (!form) return;

  var slug = form.getAttribute('data-form-id');
  var msgValidation = form.getAttribute('data-msg-validation');
  var msgContactRequired = form.getAttribute('data-msg-contact-required');
  var msgSuccess = form.getAttribute('data-msg-success');
  var msgError = form.getAttribute('data-msg-error');
  var btnLabel = form.getAttribute('data-btn-label');
  var turnstileKey = form.getAttribute('data-turnstile-key');

  var btn = document.getElementById('ef-submit');
  var msg = document.getElementById('ef-msg');
  var nameEl = document.getElementById('ef-name');
  var emailEl = document.getElementById('ef-email');
  var phoneEl = document.getElementById('ef-phone');
  var contentEl = document.getElementById('ef-content');
  var overlay = document.getElementById('turnstile-overlay');

  var turnstileWidgetId = null;
  var captchaToken = '';

  function handleSubmit() {
    if (!nameEl.value.trim() || !contentEl.value.trim()) {
      msg.textContent = msgValidation;
      msg.className = 'msg msg-error';
      return;
    }
    if (!emailEl.value.trim() && !phoneEl.value.trim()) {
      msg.textContent = msgContactRequired;
      msg.className = 'msg msg-error';
      return;
    }
    msg.className = 'msg';
    overlay.classList.add('show');

    turnstile.ready(function () {
      if (turnstileWidgetId) { turnstile.remove(turnstileWidgetId); }
      turnstileWidgetId = turnstile.render('#turnstile-widget', {
        sitekey: turnstileKey,
        callback: onCaptcha,
        'expired-callback': function () {
          turnstile.remove(turnstileWidgetId);
          turnstileWidgetId = null;
          captchaToken = '';
        }
      });
    });
  }

  function onCaptcha(token) {
    captchaToken = token;
    overlay.classList.remove('show');
    submitForm();
  }

  function submitForm() {
    msg.className = 'msg';
    var fd = new FormData(form);
    fd.append('turnstile_token', captchaToken);
    btn.disabled = true;
    btn.textContent = '...';
    fetch('/api/submit/' + slug, { method: 'POST', body: fd })
      .then(function (r) { return r.json(); })
      .then(function (d) {
        if (d.success) {
          msg.textContent = msgSuccess;
          msg.className = 'msg msg-success';
          form.reset();
          captchaToken = '';
          btn.disabled = false;
          btn.textContent = btnLabel;
        } else {
          msg.textContent = d.error || msgError;
          msg.className = 'msg msg-error';
          captchaToken = '';
          btn.disabled = false;
          btn.textContent = btnLabel;
        }
      })
      .catch(function () {
        msg.textContent = msgError;
        msg.className = 'msg msg-error';
        btn.disabled = false;
        btn.textContent = btnLabel;
      });
  }

  btn.addEventListener('click', handleSubmit);
})();
