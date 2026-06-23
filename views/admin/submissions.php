<?php
/**
 * Submissions list page (admin)
 */
$lang = App::lang();
$title = $lang['submissions'];
$current = 'submissions';

// Filters
$formId = isset($_GET['form_id']) ? (int)$_GET['form_id'] : 0;
$search = trim($_GET['search'] ?? '');
$page = max(1, (int)($_GET['page'] ?? 1));
$perPage = 20;

require __DIR__ . '/../partials/header.php';

// Get forms for filter dropdown
$forms = App::db()->query("SELECT id, name FROM forms ORDER BY name")->fetchAll();
?>

<form method="GET" style="margin-bottom:16px;">
  <div class="form-row" style="align-items:flex-end;">
    <div class="form-group" style="flex:0 0 180px;">
      <label><?= h($lang['query_form_select']) ?></label>
      <select name="form_id">
        <option value="0"><?= h($lang['query_all_forms']) ?></option>
        <?php foreach ($forms as $f): ?>
          <option value="<?= $f['id'] ?>" <?= $formId === (int)$f['id'] ? 'selected' : '' ?>><?= h($f['name']) ?></option>
        <?php endforeach; ?>
      </select>
    </div>
    <div class="form-group" style="flex:1;">
      <label><?= h($lang['query_search']) ?></label>
      <input type="search" name="search" value="<?= h($search) ?>" placeholder="Search name, email, content...">
    </div>
    <div class="form-group" style="flex:0 0 auto;">
      <button type="submit" class="btn btn-primary"><?= h($lang['query_search']) ?></button>
    </div>
  </div>
</form>

<?php
// Build query
$where = [];
$params = [];
if ($formId > 0) {
    $where[] = "s.form_id = ?";
    $params[] = $formId;
}
if ($search !== '') {
    $where[] = "(s.name LIKE ? OR s.email LIKE ? OR s.phone LIKE ? OR s.content LIKE ?)";
    $searchParam = "%$search%";
    $params[] = $searchParam;
    $params[] = $searchParam;
    $params[] = $searchParam;
    $params[] = $searchParam;
}
$whereClause = $where ? 'WHERE ' . implode(' AND ', $where) : '';

// Count
$countStmt = App::db()->prepare("SELECT COUNT(*) FROM submissions s $whereClause");
$countStmt->execute($params);
$total = $countStmt->fetchColumn();
$totalPages = max(1, (int)ceil($total / $perPage));

// Fetch
$offset = ($page - 1) * $perPage;
$stmt = App::db()->prepare("
    SELECT s.*, f.name as form_name
    FROM submissions s
    LEFT JOIN forms f ON s.form_id = f.id
    $whereClause
    ORDER BY s.created_at DESC
    LIMIT ? OFFSET ?
");
$allParams = array_merge($params, [$perPage, $offset]);
$stmt->execute($allParams);
$submissions = $stmt->fetchAll();
?>

<div class="section-title"><?= h($lang['recent_submissions']) ?> <span class="text-muted">(<?= $total ?>)</span></div>

<?php if ($submissions): ?>
<div class="table-wrap">
<table>
  <thead>
    <tr>
      <th><?= h($lang['submission_time']) ?></th>
      <th>Form</th>
      <th><?= h($lang['name']) ?></th>
      <th>Email</th>
      <th>Phone</th>
      <th><?= h($lang['content']) ?></th>
      <th><?= h($lang['email_status']) ?></th>
    </tr>
  </thead>
  <tbody>
    <?php foreach ($submissions as $s):
      $statusClass = match($s['email_status']) {
        'sent' => 'badge-green',
        'failed' => 'badge-red',
        default => 'badge-yellow',
      };
    ?>
    <tr>
      <td style="white-space:nowrap;font-size:12px;"><?= h($s['created_at']) ?></td>
      <td><?= h($s['form_name'] ?? '—') ?></td>
      <td><?= h($s['name']) ?></td>
      <td><?= h($s['email']) ?></td>
      <td><?= h($s['phone']) ?></td>
      <td><span class="content-preview" data-full="<?= h($s['content']) ?>" data-meta="<?= h($s['name']) ?>" onclick="var t=this;showContentModal(t.getAttribute('data-full'),t.getAttribute('data-meta'))"><?= truncate($s['content']) ?></span></td>
      <td><span class="badge <?= $statusClass ?>"><?= h($lang[$s['email_status']] ?? $s['email_status']) ?></span></td>
    </tr>
    <?php endforeach; ?>
  </tbody>
</table>
</div>

<?php if ($totalPages > 1): ?>
<div class="pagination">
  <?php if ($page > 1): ?>
    <a href="?<?= http_build_query(['form_id' => $formId, 'search' => $search, 'page' => $page - 1]) ?>" class="btn btn-sm btn-secondary"><?= h($lang['prev']) ?></a>
  <?php endif; ?>
  <span><?= h($lang['page']) ?> <?= $page ?> <?= h($lang['of']) ?> <?= $totalPages ?></span>
  <?php if ($page < $totalPages): ?>
    <a href="?<?= http_build_query(['form_id' => $formId, 'search' => $search, 'page' => $page + 1]) ?>" class="btn btn-sm btn-secondary"><?= h($lang['next']) ?></a>
  <?php endif; ?>
</div>
<?php endif; ?>

<?php else: ?>
<p class="text-muted"><?= h($lang['no_submissions']) ?></p>
<?php endif; ?>

<?php require __DIR__ . '/../partials/footer.php'; ?>
