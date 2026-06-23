<?php
/**
 * Dashboard page
 */
$lang = App::lang();
$title = $lang['dashboard'];
$current = 'dashboard';
require __DIR__ . '/../partials/header.php';

// Stats
$formCount = App::db()->query("SELECT COUNT(*) FROM forms")->fetchColumn();
$totalSubmissions = App::db()->query("SELECT COUNT(*) FROM submissions")->fetchColumn();
$activeForms = App::db()->query("SELECT COUNT(*) FROM forms WHERE status = 1")->fetchColumn();

// Email delivery rate
$totalSent = App::db()->query("SELECT COUNT(*) FROM submissions")->fetchColumn();
$failedSent = App::db()->query("SELECT COUNT(*) FROM submissions WHERE email_status = 'failed'")->fetchColumn();
$deliveryRate = $totalSent > 0 ? round((($totalSent - $failedSent) / $totalSent) * 100, 1) : 100;
?>

<div class="stats">
  <div class="stat">
    <div class="stat-num"><?= (int)$formCount ?></div>
    <div class="stat-label"><?= h($lang['total_forms']) ?></div>
  </div>
  <div class="stat">
    <div class="stat-num"><?= (int)$totalSubmissions ?></div>
    <div class="stat-label"><?= h($lang['total_submissions']) ?></div>
  </div>
  <div class="stat">
    <div class="stat-num"><?= (int)$activeForms ?></div>
    <div class="stat-label"><?= h($lang['active_forms']) ?></div>
  </div>
  <div class="stat">
    <div class="stat-num"><?= $deliveryRate ?>%</div>
    <div class="stat-label"><?= h($lang['email_delivery']) ?></div>
  </div>
</div>

<div class="section-title"><?= h($lang['recent_submissions']) ?></div>
<?php
$recent = App::db()->query("
    SELECT s.*, f.name as form_name
    FROM submissions s
    LEFT JOIN forms f ON s.form_id = f.id
    ORDER BY s.created_at DESC
    LIMIT 10
")->fetchAll();

if ($recent): ?>
<div class="table-wrap">
<table>
  <thead>
    <tr>
      <th><?= h($lang['submission_time']) ?></th>
      <th><?= h($lang['name']) ?></th>
      <th>Email</th>
      <th>Form</th>
      <th><?= h($lang['email_status']) ?></th>
    </tr>
  </thead>
  <tbody>
    <?php foreach ($recent as $r):
      $statusClass = match($r['email_status']) {
        'sent' => 'badge-green',
        'failed' => 'badge-red',
        default => 'badge-yellow',
      };
    ?>
    <tr>
      <td><?= h($r['created_at']) ?></td>
      <td><?= h($r['name']) ?></td>
      <td><?= h($r['email']) ?></td>
      <td><?= h($r['form_name'] ?? '—') ?></td>
      <td><span class="badge <?= $statusClass ?>"><?= h($lang[$r['email_status']] ?? $r['email_status']) ?></span></td>
    </tr>
    <?php endforeach; ?>
  </tbody>
</table>
</div>
<?php else: ?>
<p class="text-muted"><?= h($lang['no_submissions']) ?></p>
<?php endif; ?>

<?php require __DIR__ . '/../partials/footer.php'; ?>
