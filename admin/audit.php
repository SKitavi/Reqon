<?php
// admin/audit.php
// Displays the audit log. Accessible to approvers and admin role.

require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../includes/auth.php';

requireLogin();

// Only approvers and admin can view the audit log
if (!hasRole('dept_head','hr_director','finance_director','managing_director','admin')) {
    setFlash('error', 'Access denied.');
    redirect(BASE_URL . '/dashboard.php');
}

$page    = max(1, (int)get('page', '1'));
$perPage = 40;
$offset  = ($page - 1) * $perPage;

$filterAction = get('action_filter');
$filterUser   = get('user_filter');

$where  = ['1=1'];
$params = [];

if ($filterAction) {
    $where[]  = 'al.action = ?';
    $params[] = $filterAction;
}
if ($filterUser) {
    $where[]  = 'u.name LIKE ?';
    $params[] = "%{$filterUser}%";
}

$whereSQL = implode(' AND ', $where);

$total = (int)(fetchOne(
    "SELECT COUNT(*) AS cnt
       FROM audit_log al
       LEFT JOIN users u ON u.id = al.user_id
      WHERE {$whereSQL}",
    $params
)['cnt'] ?? 0);

$logs = fetchAll(
    "SELECT al.*, u.name AS actor_name
       FROM audit_log al
       LEFT JOIN users u ON u.id = al.user_id
      WHERE {$whereSQL}
      ORDER BY al.created_at DESC
      LIMIT {$perPage} OFFSET {$offset}",
    $params
);

$totalPages  = (int)ceil($total / $perPage);
$actionTypes = ['CREATE','APPROVE','REJECT','CANCEL','UPDATE','LOGIN'];

$pageTitle = 'Audit Log';
include __DIR__ . '/../includes/header.php';
?>

<div class="page-wrap">

  <div class="page-header">
    <h1 class="page-title">Audit Log</h1>
    <span style="font-size:13px;color:var(--text-muted)"><?= number_format($total) ?> total entries</span>
  </div>

  <!-- Filters -->
  <form method="GET" action="" style="display:flex;gap:10px;flex-wrap:wrap;margin-bottom:20px">
    <select name="action_filter" onchange="this.form.submit()"
            style="padding:8px 12px;border:1px solid var(--border);border-radius:var(--radius);font-size:14px;background:var(--white);outline:none">
      <option value="">All actions</option>
      <?php foreach ($actionTypes as $at): ?>
        <option value="<?= $at ?>" <?= $filterAction === $at ? 'selected' : '' ?>><?= $at ?></option>
      <?php endforeach; ?>
    </select>
    <input type="text" name="user_filter" value="<?= e($filterUser) ?>"
           placeholder="Filter by user name…"
           style="padding:8px 12px;border:1px solid var(--border);border-radius:var(--radius);font-size:14px;outline:none">
    <button type="submit" class="btn btn-outline">Filter</button>
    <?php if ($filterAction || $filterUser): ?>
      <a href="<?= BASE_URL ?>/admin/audit.php" class="btn btn-outline">Clear</a>
    <?php endif; ?>
  </form>

  <div class="card">
    <?php if (empty($logs)): ?>
      <div class="empty-state">
        <div class="empty-icon" aria-hidden="true">📋</div>
        <p>No audit log entries match your filters.</p>
      </div>
    <?php else: ?>
      <div class="table-wrap">
        <table class="audit-table">
          <thead>
            <tr>
              <th>Time</th>
              <th>User</th>
              <th>Action</th>
              <th>Record</th>
              <th>Details</th>
              <th>IP</th>
            </tr>
          </thead>
          <tbody>
            <?php foreach ($logs as $log): ?>
            <tr>
              <td style="white-space:nowrap;color:var(--text-muted)">
                <?= e(date('d/m/Y H:i', strtotime($log['created_at']))) ?>
              </td>
              <td><?= e($log['actor_name'] ?? 'System') ?></td>
              <td>
                <span class="audit-action <?= e($log['action']) ?>">
                  <?= e($log['action']) ?>
                </span>
              </td>
              <td>
                <?php if ($log['table_name'] === 'requisitions' && $log['record_id']): ?>
                  <a href="<?= BASE_URL ?>/requisitions/view.php?id=<?= (int)$log['record_id'] ?>"
                     style="color:#2980B9;font-size:13px">
                    <?= e($log['table_name']) ?> #<?= (int)$log['record_id'] ?>
                  </a>
                <?php else: ?>
                  <span style="font-size:13px;color:var(--text-muted)">
                    <?= e($log['table_name'] ?? '—') ?>
                    <?= $log['record_id'] ? '#' . (int)$log['record_id'] : '' ?>
                  </span>
                <?php endif; ?>
              </td>
              <td style="font-size:13px;color:var(--text-muted);max-width:240px">
                <?= e(mb_strimwidth($log['details'] ?? '', 0, 80, '…')) ?>
              </td>
              <td style="font-size:12px;color:var(--text-muted);font-family:monospace">
                <?= e($log['ip_address'] ?? '') ?>
              </td>
            </tr>
            <?php endforeach; ?>
          </tbody>
        </table>
      </div>

      <!-- Pagination -->
      <?php if ($totalPages > 1): ?>
        <div style="display:flex;align-items:center;justify-content:center;gap:8px;padding:16px;border-top:1px solid var(--border)">
          <?php if ($page > 1): ?>
            <a href="?page=<?= $page-1 ?>&action_filter=<?= urlencode($filterAction) ?>&user_filter=<?= urlencode($filterUser) ?>"
               class="btn btn-outline btn-sm">← Prev</a>
          <?php endif; ?>
          <span style="font-size:13px;color:var(--text-muted)">Page <?= $page ?> of <?= $totalPages ?></span>
          <?php if ($page < $totalPages): ?>
            <a href="?page=<?= $page+1 ?>&action_filter=<?= urlencode($filterAction) ?>&user_filter=<?= urlencode($filterUser) ?>"
               class="btn btn-outline btn-sm">Next →</a>
          <?php endif; ?>
        </div>
      <?php endif; ?>
    <?php endif; ?>
  </div>

</div>

<?php include __DIR__ . '/../includes/footer.php'; ?>