<?php
// dashboard.php — staff + approver dashboard (admin goes to admin/dashboard.php)
require_once __DIR__ . '/config/config.php';
require_once __DIR__ . '/includes/auth.php';

requireLogin();

$user      = currentUser();
$uid       = (int)$user['user_id'];
$userLevel = getRoleLevel($user);
$roleId    = (int)($user['role_id'] ?? 0);
$deptId    = (int)($user['department_id'] ?? 0);

// Admin should not land here
if ($roleId === 1) {
    redirect(BASE_URL . '/admin/dashboard.php');
}

// ── Determine visibility scope ────────────────────────────────────────────
// Requester (level 0, role 4)  → own requisitions only
// Dept Head (level 1)          → own department
// Procurement Head (user 7)    → Procurement + IT Asset + Merchandise
// HR Director (level 2)        → all Personnel
// Finance Director (level 3)   → everything
// MD (level 4)                 → everything

$scopeWhere  = '1=1';
$scopeParams = [];

if ($userLevel === 0) {
    // Requester — own only
    $scopeWhere  = 'r.requester_id = ?';
    $scopeParams = [$uid];
} elseif ($userLevel === 1) {
    // Dept Head — own department
    $scopeWhere  = 'r.department_id = ?';
    $scopeParams = [$deptId];
} elseif ($userLevel === 2) {
    // HR Director — all Personnel
    $scopeWhere  = "r.requisition_type = 'personnel'";
    $scopeParams = [];
    // Special case: Procurement Head (user 7) sees Procurement + IT Asset + Merchandise
    if ($uid === APPROVER_PROCUREMENT_HEAD) {
        $scopeWhere  = "r.requisition_type IN ('procurement','it_asset','merchandise')";
        $scopeParams = [];
    }
}
// level 3 (Finance) and level 4 (MD) → default 1=1 (everything)

// ── Stats ─────────────────────────────────────────────────────────────────
$statsStmt = getDB()->prepare(
    "SELECT COUNT(*) AS total,
            SUM(current_status='pending')  AS pending,
            SUM(current_status='approved') AS approved,
            SUM(current_status='rejected') AS rejected
       FROM requisitions r
      WHERE {$scopeWhere}"
);
$statsStmt->execute($scopeParams);
$stats = $statsStmt->fetch();

// ── Recent requisitions (last 10) ─────────────────────────────────────────
$recentStmt = getDB()->prepare(
    "SELECT r.*,
            d.department_name AS dept_name,
            u.full_name       AS submitter_name
       FROM requisitions r
       LEFT JOIN departments d ON d.department_id = r.department_id
       LEFT JOIN users      u ON u.user_id         = r.requester_id
      WHERE {$scopeWhere}
      ORDER BY r.created_at DESC
      LIMIT 10"
);
$recentStmt->execute($scopeParams);
$recentReqs = $recentStmt->fetchAll();

$showSubmitter = ($userLevel > 0); // approvers see who submitted

$pageTitle = 'Dashboard';
include __DIR__ . '/includes/header.php';
?>

<div class="page-wrap">

  <div class="page-header">
    <h1 class="page-title">Dashboard</h1>
    <a href="<?= BASE_URL ?>/requisitions/new.php" class="btn btn-primary">
      <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" aria-hidden="true">
        <line x1="12" y1="5" x2="12" y2="19"/><line x1="5" y1="12" x2="19" y2="12"/>
      </svg>
      New Request
    </a>
  </div>

  <?php renderFlash(); ?>

  <?php
  // Extra stat cards for Mary — LPO tracking
  if ($uid === APPROVER_PROCUREMENT_HEAD):
    $pendingLpo = (int)(fetchOne(
        "SELECT COUNT(*) AS c FROM requisitions r
          LEFT JOIN lpo_log l ON l.requisition_id = r.requisition_id
         WHERE r.current_status = 'approved'
           AND r.requisition_type IN ('procurement','it_asset','merchandise')
           AND l.lpo_id IS NULL"
    )['c'] ?? 0);
    $generatedLpo = (int)(fetchOne("SELECT COUNT(*) AS c FROM lpo_log")['c'] ?? 0);
  ?>
  <section class="stat-grid" style="grid-template-columns:repeat(2,1fr);margin-bottom:16px" aria-label="LPO summary">
    <div class="stat-card <?= $pendingLpo > 0 ? 'stat-card-warn' : '' ?>">
      <div class="stat-icon pending" aria-hidden="true">
        <svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round">
          <circle cx="12" cy="12" r="10"/><polyline points="12 6 12 12 16 14"/>
        </svg>
      </div>
      <span class="stat-label">Pending LPOs</span>
      <span class="stat-value"><?= number_format($pendingLpo) ?></span>
      <span class="stat-sub">approved, not yet issued</span>
    </div>
    <div class="stat-card">
      <div class="stat-icon approved" aria-hidden="true">
        <svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round">
          <path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/>
          <polyline points="14 2 14 8 20 8"/>
          <line x1="16" y1="13" x2="8" y2="13"/>
          <line x1="16" y1="17" x2="8" y2="17"/>
        </svg>
      </div>
      <span class="stat-label">LPOs Generated</span>
      <span class="stat-value"><?= number_format($generatedLpo) ?></span>
      <span class="stat-sub">all time</span>
    </div>
  </section>

  <div style="margin-bottom:24px">
    <a href="<?= BASE_URL ?>/procurement/lpo_queue.php" class="btn btn-primary">
      <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" aria-hidden="true">
        <path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/>
        <polyline points="14 2 14 8 20 8"/>
      </svg>
      Open LPO Queue <?= $pendingLpo > 0 ? "({$pendingLpo} pending)" : '' ?>
    </a>
  </div>
  <?php endif; ?>

  <section class="stat-grid" aria-label="Requisition summary">
    <div class="stat-card">
      <div class="stat-icon total" aria-hidden="true">
        <svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round">
          <rect x="18" y="3" width="4" height="18"/><rect x="10" y="8" width="4" height="13"/><rect x="2" y="13" width="4" height="8"/>
        </svg>
      </div>
      <span class="stat-label">Total</span>
      <span class="stat-value"><?= (int)($stats['total'] ?? 0) ?></span>
    </div>
    <div class="stat-card">
      <div class="stat-icon pending" aria-hidden="true">
        <svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round">
          <circle cx="12" cy="12" r="10"/><polyline points="12 6 12 12 16 14"/>
        </svg>
      </div>
      <span class="stat-label">Pending</span>
      <span class="stat-value"><?= (int)($stats['pending'] ?? 0) ?></span>
    </div>
    <div class="stat-card">
      <div class="stat-icon approved" aria-hidden="true">
        <svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round">
          <path d="M22 11.08V12a10 10 0 1 1-5.93-9.14"/><polyline points="22 4 12 14.01 9 11.01"/>
        </svg>
      </div>
      <span class="stat-label">Approved</span>
      <span class="stat-value"><?= (int)($stats['approved'] ?? 0) ?></span>
    </div>
    <div class="stat-card">
      <div class="stat-icon rejected" aria-hidden="true">
        <svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round">
          <circle cx="12" cy="12" r="10"/><line x1="15" y1="9" x2="9" y2="15"/><line x1="9" y1="9" x2="15" y2="15"/>
        </svg>
      </div>
      <span class="stat-label">Rejected</span>
      <span class="stat-value"><?= (int)($stats['rejected'] ?? 0) ?></span>
    </div>
  </section>

  <div class="card">
    <div class="card-header">
      <h2 class="card-title">
        <?php
        if ($userLevel === 0)      echo 'My Recent Requisitions';
        elseif ($userLevel === 1)  echo 'Department Requisitions';
        else                       echo 'Recent Requisitions';
        ?>
      </h2>
    </div>
    <div class="table-wrap">
      <?php if (empty($recentReqs)): ?>
        <div class="empty-state">
          <div class="empty-icon" aria-hidden="true">📋</div>
          <p>No requisitions yet.
            <a href="<?= BASE_URL ?>/requisitions/new.php" class="text-green fw-500">Create your first request →</a>
          </p>
        </div>
      <?php else: ?>
        <table class="req-table" aria-label="Recent requisitions">
          <thead>
            <tr>
              <th scope="col">ID</th>
              <th scope="col">Department</th>
              <?php if ($showSubmitter): ?><th scope="col">Submitted By</th><?php endif; ?>
              <th scope="col">Type</th>
              <th scope="col">Status</th>
              <th scope="col">Date</th>
              <th scope="col">Action</th>
            </tr>
          </thead>
          <tbody>
            <?php foreach ($recentReqs as $req): ?>
            <tr>
              <td class="req-id"><?= e($req['requisition_number'] ?? ('REQ-' . str_pad($req['requisition_id'], 3, '0', STR_PAD_LEFT))) ?></td>
              <td><?= e($req['dept_name'] ?? '—') ?></td>
              <?php if ($showSubmitter): ?>
                <td><?= e($req['submitter_name'] ?? '—') ?></td>
              <?php endif; ?>
              <td style="text-transform:capitalize"><?= e(str_replace('_', ' ', $req['requisition_type'])) ?></td>
              <td><?= statusBadge($req['current_status']) ?></td>
              <td class="text-muted"><?= e(formatDate($req['submission_date'] ?? $req['created_at'])) ?></td>
              <td><a href="<?= BASE_URL ?>/requisitions/view.php?id=<?= (int)$req['requisition_id'] ?>" class="btn btn-outline btn-sm">View</a></td>
            </tr>
            <?php endforeach; ?>
          </tbody>
        </table>
      <?php endif; ?>
    </div>
    <?php if (!empty($recentReqs)): ?>
      <div class="card-footer">
        <a href="<?= BASE_URL ?>/requisitions/list.php">View All Requisitions →</a>
      </div>
    <?php endif; ?>
  </div>

</div>

<?php include __DIR__ . '/includes/footer.php'; ?>
