<?php
/**
 * Cross-origin embeddable form — CSP-safe (no inline styles/scripts)
 * Params: form_id (URL path), ?theme=auto|light|dark, ?lang=en|zh
 */
$lang = App::lang();
$langCode = App::langCode();
$formId = (int)($_GET['form_id'] ?? 0);
if (!$formId) {
    http_response_code(404);
    echo 'Form not found';
    exit;
}

$stmt = App::db()->prepare("SELECT * FROM forms WHERE id = ? AND status = 1");
$stmt->execute([$formId]);
$form = $stmt->fetch();

if (!$form) {
    http_response_code(404);
    echo 'Form not found';
    exit;
}

$theme = $_GET['theme'] ?? 'auto';
if (!in_array($theme, ['auto', 'light', 'dark'])) {
    $theme = 'auto';
}

$embedLang = $lang;
$embedCode = $langCode;
?>
<!DOCTYPE html>
<html lang="<?= $embedCode ?>" data-theme="<?= $theme ?>">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title><?= h($form['name']) ?></title>
<link rel="icon" href="/favicon.ico" sizes="32x32">
<link rel="icon" href="/favicon.svg" type="image/svg+xml">
<link rel="stylesheet" href="/css/embed.css">
</head>
<body>

<form id="embed-form"
      data-form-id="<?= $formId ?>"
      data-turnstile-key="<?= h(App::config()['turnstile_site_key']) ?>"
      data-msg-validation="<?= h($embedLang['form_validation_error']) ?>"
      data-msg-contact-required="<?= h($embedLang['form_contact_type_required']) ?>"
      data-msg-success="<?= h($embedLang['form_submit_success']) ?>"
      data-msg-error="<?= h($embedLang['form_submit_error']) ?>"
      data-btn-label="<?= h($embedLang['form_submit']) ?>">

  <label for="ef-name"><?= h($embedLang['form_name_label']) ?> <span class="req">*</span></label>
  <input type="text" id="ef-name" name="name" placeholder="<?= h($embedLang['form_name_placeholder']) ?>" required>

  <label><?= h($embedLang['form_contact']) ?> <span class="req">*</span></label>
  <div class="contact-row">
    <div class="form-group">
      <input type="email" id="ef-email" name="email" placeholder="<?= h($embedLang['form_contact_placeholder_email']) ?>">
    </div>
    <div class="form-group">
      <input type="tel" id="ef-phone" name="phone" placeholder="<?= h($embedLang['form_contact_placeholder_phone']) ?>">
    </div>
  </div>
  <p style="font-size:11px;color:var(--text-muted);margin:-8px 0 12px;"><?= h($embedLang['form_contact_hint']) ?></p>

  <label for="ef-content"><?= h($embedLang['form_content_label']) ?> <span class="req">*</span></label>
  <textarea id="ef-content" name="content" placeholder="<?= h($embedLang['form_content_placeholder']) ?>" required></textarea>

  <button type="button" class="btn-submit" id="ef-submit"><?= h($embedLang['form_submit']) ?></button>
  <div class="msg" id="ef-msg"></div>
</form>

<div class="turnstile-overlay" id="turnstile-overlay">
  <div class="turnstile-modal">
    <p>Please verify you are human</p>
    <div id="turnstile-widget"></div>
  </div>
</div>

<script src="https://challenges.cloudflare.com/turnstile/v0/api.js"></script>
<script src="/js/embed.js"></script>
</body>
</html>
