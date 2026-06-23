<?php
/**
 * Embed code generator page (admin)
 */
$lang = App::lang();
$title = $lang['embed_code'];
$current = 'embed';

require __DIR__ . '/../partials/header.php';

$forms = App::db()->query("SELECT * FROM forms ORDER BY name")->fetchAll();
$selectedFormId = isset($_GET['form_id']) ? (int)$_GET['form_id'] : ($forms[0]['id'] ?? 0);
$selectedForm = null;
foreach ($forms as $f) {
    if ((int)$f['id'] === $selectedFormId) {
        $selectedForm = $f;
        break;
    }
}
?>

<form method="GET" style="margin-bottom:16px;">
  <div class="form-row" style="align-items:flex-end;">
    <div class="form-group" style="flex:0 0 200px;">
      <label>Select Form</label>
      <select name="form_id" onchange="this.form.submit()">
        <?php foreach ($forms as $f): ?>
          <option value="<?= $f['id'] ?>" <?= $selectedFormId === (int)$f['id'] ? 'selected' : '' ?>><?= h($f['name']) ?></option>
        <?php endforeach; ?>
      </select>
    </div>
  </div>
</form>

<?php if ($selectedForm): ?>
<div class="form-section">
  <div class="form-row">
    <div class="form-group">
      <label><?= h($lang['theme']) ?></label>
      <select id="embed-theme" onchange="updateEmbedCode(<?= $selectedForm['id'] ?>)">
        <option value="auto"><?= h($lang['auto']) ?></option>
        <option value="light"><?= h($lang['light']) ?></option>
        <option value="dark"><?= h($lang['dark']) ?></option>
      </select>
    </div>
    <div class="form-group">
      <label><?= h($lang['language']) ?></label>
      <select id="embed-lang" onchange="updateEmbedCode(<?= $selectedForm['id'] ?>)">
        <option value="en">English</option>
        <option value="zh">中文</option>
      </select>
    </div>
  </div>

  <p class="text-muted mb-1"><?= h($lang['embed_instructions']) ?></p>
  <div class="code-block">
    <button class="btn btn-sm btn-primary copy-btn-pos" onclick="copyToClipboard(document.getElementById('embed-code-content').textContent)"><?= h($lang['copy']) ?></button>
    <pre id="embed-code-content">&lt;iframe src="http<?= ($_SERVER['HTTPS'] ?? '') === 'on' ? 's' : '' ?>://<?= h($_SERVER['HTTP_HOST']) ?>/embed/<?= $selectedForm['id'] ?>?theme=auto&amp;lang=en"
  style="border:none;width:100%;height:450px;"&gt;
&lt;/iframe&gt;</pre>
  </div>
</div>
<?php elseif (empty($forms)): ?>
<p class="text-muted"><?= h($lang['no_forms']) ?></p>
<?php endif; ?>

<?php require __DIR__ . '/../partials/footer.php'; ?>
