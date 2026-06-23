<?php
/**
 * Create / Edit form page — AJAX submit
 */
$lang = App::lang();
$isEdit = !empty($_GET['id']);
$title = $isEdit ? $lang['edit_form'] : $lang['create_form'];
$current = 'forms';

$form = ['name' => '', 'recipient_email' => '', 'query_password' => '', 'status' => 1];
if ($isEdit) {
    $stmt = App::db()->prepare("SELECT * FROM forms WHERE id = ?");
    $stmt->execute([(int)$_GET['id']]);
    $form = $stmt->fetch();
    if (!$form) {
        redirect('/admin/forms');
    }
}

require __DIR__ . '/../partials/header.php';
?>

<div id="form-error" class="alert alert-error" style="display:none"></div>
<div id="form-success" class="alert alert-success" style="display:none"></div>

<div class="form-section">
  <form id="form-editor" onsubmit="return false">
    <?php if ($isEdit): ?>
      <input type="hidden" name="id" value="<?= h($form['id']) ?>">
    <?php endif; ?>
    <div class="form-row">
      <div class="form-group">
        <label><?= h($lang['form_name']) ?></label>
        <input type="text" name="name" value="<?= h($form['name']) ?>" placeholder="<?= h($lang['form_name_placeholder']) ?>" required>
      </div>
    </div>
    <div class="form-row">
      <div class="form-group">
        <label><?= h($lang['recipient_email']) ?></label>
        <input type="email" name="recipient_email" value="<?= h($form['recipient_email']) ?>" placeholder="<?= h($lang['recipient_placeholder']) ?>">
      </div>
      <div class="form-group">
        <label><?= h($lang['query_password']) ?></label>
        <input type="text" name="query_password" value="" placeholder="<?= h($lang['query_password_placeholder']) ?>" autocomplete="off">
        <span class="text-muted"><?= h($lang['query_password_hint']) ?></span>
      </div>
    </div>
    <div class="form-row">
      <div class="form-group">
        <label><?= h($lang['status']) ?></label>
        <div class="radio-row" style="padding-top:6px;">
          <label><input type="radio" name="status" value="1" <?= $form['status'] == 1 ? 'checked' : '' ?>> <?= h($lang['active']) ?></label>
          <label><input type="radio" name="status" value="0" <?= $form['status'] == 0 ? 'checked' : '' ?>> <?= h($lang['inactive']) ?></label>
        </div>
      </div>
    </div>
    <div class="form-row">
      <div class="btn-group">
        <button type="submit" class="btn btn-primary" id="save-btn" onclick="saveForm()"><?= h($isEdit ? $lang['save'] : $lang['create']) ?></button>
        <a href="/admin/forms" class="btn btn-secondary"><?= h($lang['cancel']) ?></a>
      </div>
    </div>
  </form>
</div>

<script>
function saveForm() {
  var errEl = document.getElementById('form-error');
  var succEl = document.getElementById('form-success');
  var btn = document.getElementById('save-btn');
  errEl.style.display = 'none';
  succEl.style.display = 'none';

  var fd = new FormData(document.getElementById('form-editor'));
  btn.disabled = true;
  btn.textContent = '...';

  fetch('<?= $isEdit ? "/api/forms?action=update" : "/api/forms?action=create" ?>', {
    method: 'POST',
    body: fd
  })
  .then(function(r) { return r.json(); })
  .then(function(d) {
    if (d.success) {
      window.location.href = '/admin/forms';
    } else {
      errEl.textContent = d.error || 'Save failed';
      errEl.style.display = 'block';
      btn.disabled = false;
      btn.textContent = '<?= h($isEdit ? $lang['save'] : $lang['create']) ?>';
    }
  })
  .catch(function() {
    errEl.textContent = 'Network error';
    errEl.style.display = 'block';
    btn.disabled = false;
    btn.textContent = '<?= h($isEdit ? $lang['save'] : $lang['create']) ?>';
  });
}
</script>

<?php require __DIR__ . '/../partials/footer.php'; ?>
