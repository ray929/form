<?php
/**
 * Query data page — scoped to a single form via /query/{form_id}
 * No form selector; only shows submissions for the URL-specified form.
 */
$lang = App::lang();
$langCode = App::langCode();
$title = $lang['query_title'];

// Form ID from route (set in index.php)
$formId = (int)($_GET['form_id'] ?? 0);
$formName = $_GET['form_name'] ?? '';

$search = trim($_GET['search'] ?? '');
$page = max(1, (int)($_GET['page'] ?? 1));
$perPage = 20;

// Build query — always scoped to formId
$where = ["s.form_id = ?"];
$params = [$formId];
if ($search !== '') {
    $where[] = "(s.name LIKE ? OR s.email LIKE ? OR s.phone LIKE ? OR s.content LIKE ?)";
    $sp = "%$search%";
    $params[] = $sp; $params[] = $sp; $params[] = $sp; $params[] = $sp;
}
$whereClause = 'WHERE ' . implode(' AND ', $where);

$countStmt = App::db()->prepare("SELECT COUNT(*) FROM submissions s $whereClause");
$countStmt->execute($params);
$total = $countStmt->fetchColumn();
$totalPages = max(1, (int)ceil($total / $perPage));

$offset = ($page - 1) * $perPage;
$stmt = App::db()->prepare("
    SELECT s.*
    FROM submissions s
    $whereClause
    ORDER BY s.created_at DESC
    LIMIT ? OFFSET ?
");
$allParams = array_merge($params, [$perPage, $offset]);
$stmt->execute($allParams);
$submissions = $stmt->fetchAll();
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title><?= h($formName ?: $lang['query_title']) ?></title>
<link rel="stylesheet" href="/css/app.css?v=1">
<link rel="icon" href="/favicon.ico" sizes="32x32">
<link rel="icon" href="/favicon.svg" type="image/svg+xml">
</head>
<body class="page-simple">
<div class="topbar-simple">
  <h1>
    <?= h($formName ?: $lang['query_title']) ?>
    <span class="text-muted" style="font-weight:400;">(<?= $total ?>)</span>
  </h1>
  <div class="topbar-right">
    <a href="/query/<?= $formId ?>/logout" class="btn btn-sm btn-secondary"><?= h($lang['logout']) ?></a>
  </div>
</div>
<div class="content">

<form method="GET" style="margin-bottom:16px;">
  <div class="form-row" style="align-items:flex-end;">
    <div class="form-group" style="flex:1;">
      <label><?= h($lang['query_search']) ?></label>
      <input type="search" name="search" value="<?= h($search) ?>" placeholder="Search name, email, phone, content...">
    </div>
    <div class="form-group" style="flex:0 0 auto;">
      <button type="submit" class="btn btn-primary"><?= h($lang['query_search']) ?></button>
    </div>
  </div>
</form>

<?php if ($submissions): ?>
<div class="table-wrap">
<table>
  <thead>
    <tr>
      <th><?= h($lang['submission_time']) ?></th>
      <th><?= h($lang['name']) ?></th>
      <th>Email</th>
      <th>Phone</th>
      <th><?= h($lang['content']) ?></th>
    </tr>
  </thead>
  <tbody>
    <?php foreach ($submissions as $s): ?>
    <tr>
      <td><?= h($s['created_at']) ?></td>
      <td><?= h($s['name']) ?></td>
      <td><?= h($s['email']) ?></td>
      <td><?= h($s['phone']) ?></td>
      <td><span class="content-preview" data-full="<?= h($s['content']) ?>" data-meta="<?= h($s['name']) ?>" onclick="var t=this;showContentModal(t.getAttribute('data-full'),t.getAttribute('data-meta'))"><?= truncate($s['content']) ?></span></td>
    </tr>
    <?php endforeach; ?>
  </tbody>
</table>
</div>

<?php if ($totalPages > 1): ?>
<div class="pagination">
  <?php if ($page > 1): ?>
    <a href="?<?= http_build_query(['search' => $search, 'page' => $page - 1]) ?>" class="btn btn-sm btn-secondary"><?= h($lang['prev']) ?></a>
  <?php endif; ?>
  <span><?= h($lang['page']) ?> <?= $page ?> <?= h($lang['of']) ?> <?= $totalPages ?></span>
  <?php if ($page < $totalPages): ?>
    <a href="?<?= http_build_query(['search' => $search, 'page' => $page + 1]) ?>" class="btn btn-sm btn-secondary"><?= h($lang['next']) ?></a>
  <?php endif; ?>
</div>
<?php endif; ?>

<?php else: ?>
<p class="text-muted"><?= h($lang['no_submissions']) ?></p>
<?php endif; ?>

</div>
<script src="/js/app.js?v=1"></script>
</body>
</html>
