<?php
/**
 * Forms list page
 */
$lang = App::lang();
$title = $lang['your_forms'];
$current = 'forms';
require __DIR__ . '/../partials/header.php';

$forms = App::db()->query("SELECT * FROM forms ORDER BY created_at DESC")->fetchAll();
?>

<div style="margin-bottom:12px;">
  <a href="/admin/forms/create" class="btn btn-primary">+ <?= h($lang['create_form']) ?></a>
</div>

<?php if ($forms): ?>
<div class="table-wrap">
<table>
  <thead>
    <tr>
      <th>ID</th>
      <th><?= h($lang['form_name']) ?></th>
      <th><?= h($lang['recipient_email']) ?></th>
      <th><?= h($lang['status']) ?></th>
      <th><?= h($lang['actions']) ?></th>
    </tr>
  </thead>
  <tbody>
    <?php foreach ($forms as $f):
      $statusClass = $f['status'] ? 'badge-green' : 'badge-gray';
      $statusLabel = $f['status'] ? $lang['active'] : $lang['inactive'];
      $recipient = $f['recipient_email'] ?: App::config()['default_recipient'];
    ?>
    <tr>
      <td><code><?= $f['id'] ?></code></td>
      <td><?= h($f['name']) ?></td>
      <td><?= h($recipient) ?></td>
      <td><span class="badge <?= $statusClass ?>"><?= h($statusLabel) ?></span></td>
      <td>
        <div class="btn-group">
          <a href="/admin/forms/edit?id=<?= $f['id'] ?>" class="btn btn-sm btn-secondary"><?= h($lang['edit']) ?></a>
          <a href="/embed/<?= $f['id'] ?>" target="_blank" class="btn btn-sm btn-secondary">Preview</a>
          <a href="/admin/embed?form_id=<?= $f['id'] ?>" class="btn btn-sm btn-secondary">Embed</a>
          <a href="/query/<?= $f['id'] ?>" target="_blank" class="btn btn-sm btn-secondary">Data</a>
          <button class="btn btn-sm btn-danger" onclick="deleteForm(<?= $f['id'] ?>,'<?= h(addslashes($f['name'])) ?>')"><?= h($lang['delete']) ?></button>
        </div>
      </td>
    </tr>
    <?php endforeach; ?>
  </tbody>
</table>
</div>
<?php else: ?>
<p class="text-muted"><?= h($lang['no_forms']) ?></p>
<?php endif; ?>

<script>
function deleteForm(id, name) {
  if (!confirm('Delete "' + name + '"? This action cannot be undone.')) return;
  fetch('/api/forms?action=delete&id=' + id)
    .then(function(r) { return r.json(); })
    .then(function(d) {
      if (d.success) {
        window.location.reload();
      } else {
        alert(d.error || 'Delete failed');
      }
    })
    .catch(function() {
      alert('Network error');
    });
}
</script>

<?php require __DIR__ . '/../partials/footer.php'; ?>
