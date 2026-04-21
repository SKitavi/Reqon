<?php
// dashboard.php
require_once __DIR__ . '/includes/db.php';
require_once __DIR__ . '/includes/auth.php';

requireLogin(); // redirects to login if no session

$user = currentUser();
$db   = getDB();
$uid  = $user['id'];

// ── Stats ─────────────────────────────────────────────────
// For approvers: show org-wide stats. For staff: show only their own.
$isApprover = hasRole('dept_head','hr_director','finance_director','managing_director','admin');

if ($isApprover) {
    $statsStmt = $db->query("
        SELECT
          COUNT(*)                                        AS total,
          SUM(status = 'pending')                         AS pending,
          SUM(status = 'approved')                        AS approved,
          SUM(status = 'rejected')                        AS rejected
        FROM requisitions
    ");
} else {
    $statsStmt = $db->prepare("
        SELECT
          COUNT(*)                                        AS total,
          SUM(status = 'pending')                         AS pending,
          SUM(status = 'approved')                        AS approved,
          SUM(status = 'rejected')                        AS rejected
        FROM requisitions
        WHERE submitted_by = ?
    ");
    $statsStmt->execute([$uid]);
}
$stats = $statsStmt->fetch();

// ── Recent requisitions (last 10) ─────────────────────────
if ($isApprover) {
    $recentStmt = $db->query("
        SELECT r.*, d.name AS dept_name, u.name AS submitter_name
        FROM requisitions r
        LEFT JOIN departments d ON d.id = r.department_id
        LEFT JOIN users u       ON u.id = r.submitted_by
        ORDER BY r.created_at DESC
        LIMIT 10
    ");
} else {
    $recentStmt = $db->prepare("
        SELECT r.*, d.name AS dept_name, u.name AS submitter_name
        FROM requisitions r
        LEFT JOIN departments d ON d.id = r.department_id
        LEFT JOIN users u       ON u.id = r.submitted_by
        WHERE r.submitted_by = ?
        ORDER BY r.created_at DESC
        LIMIT 10
    ");
    $recentStmt->execute([$uid]);
}
$recentReqs = $recentStmt->fetchAll();

// ── Helpers ───────────────────────────────────────────────
function statusBadge(string $status): string {
    $map = [
        'pending'   => 'badge-pending',
        'approved'  => 'badge-approved',
        'rejected'  => 'badge-rejected',
        'cancelled' => 'badge-cancelled',
    ];
    $cls = $map[$status] ?? 'badge-pending';
    return '<span class="badge ' . $cls . '">' . ucfirst($status) . '</span>';
}

$pageTitle = 'Dashboard';
include __DIR__ . '/includes/header.php';
?>

<div class="page-wrap">

  <!-- Page header -->
  <div class="page-header">
    <h1 class="page-title">Dashboard</h1>
    <a href="/reqon/requisitions/new.php" class="btn btn-primary">
      <!-- plus icon -->
      <svg width="14" height="14" viewBox="0 0 24 24" fill="none"
           stroke="currentColor" stroke-width="2.5" stroke-linecap="round" aria-hidden="true">
        <line x1="12" y1="5" x2="12" y2="19"/><line x1="5" y1="12" x2="19" y2="12"/>
      </svg>
      New Request
    </a>
  </div>

  <!-- Stat cards -->
  <section class="stat-grid" aria-label="Requisition summary">

    <div class="stat-card">
      <div class="stat-icon total" aria-hidden="true">
        <!-- bar chart icon -->
        <svg width="22" height="22" viewBox="0 0 24 24" fill="none"
             stroke="currentColor" stroke-width="2" stroke-linecap="round">
          <rect x="18" y="3" width="4" height="18"/>
          <rect x="10" y="8" width="4" height="13"/>
          <rect x="2"  y="13" width="4" height="8"/>
        </svg>
      </div>
      <span class="stat-label">Total</span>
      <span class="stat-value"><?= (int)($stats['total'] ?? 0) ?></span>
    </div>

    <div class="stat-card">
      <div class="stat-icon pending" aria-hidden="true">
        <!-- clock icon -->
        <svg width="22" height="22" viewBox="0 0 24 24" fill="none"
             stroke="currentColor" stroke-width="2" stroke-linecap="round">
          <circle cx="12" cy="12" r="10"/>
          <polyline points="12 6 12 12 16 14"/>
        </svg>
      </div>
      <span class="stat-label">Pending</span>
      <span class="stat-value"><?= (int)($stats['pending'] ?? 0) ?></span>
    </div>

    <div class="stat-card">
      <div class="stat-icon approved" aria-hidden="true">
        <!-- check-circle icon -->
        <svg width="22" height="22" viewBox="0 0 24 24" fill="none"
             stroke="currentColor" stroke-width="2" stroke-linecap="round">
          <path d="M22 11.08V12a10 10 0 1 1-5.93-9.14"/>
          <polyline points="22 4 12 14.01 9 11.01"/>
        </svg>
      </div>
      <span class="stat-label">Approved</span>
      <span class="stat-value"><?= (int)($stats['approved'] ?? 0) ?></span>
    </div>

    <div class="stat-card">
      <div class="stat-icon rejected" aria-hidden="true">
        <!-- x-circle icon -->
        <svg width="22" height="22" viewBox="0 0 24 24" fill="none"
             stroke="currentColor" stroke-width="2" stroke-linecap="round">
          <circle cx="12" cy="12" r="10"/>
          <line x1="15" y1="9" x2="9" y2="15"/>
          <line x1="9"  y1="9" x2="15" y2="15"/>
        </svg>
      </div>
      <span class="stat-label">Rejected</span>
      <span class="stat-value"><?= (int)($stats['rejected'] ?? 0) ?></span>
    </div>

  </section>

  <!-- Recent requisitions table -->
  <div class="card">

    <div class="card-header">
      <h2 class="card-title">
        <?= $isApprover ? 'Recent Requisitions' : 'My Recent Requisitions' ?>
      </h2>
    </div>

    <div class="table-wrap">
      <?php if (empty($recentReqs)): ?>
        <div class="empty-state">
          <div class="empty-icon" aria-hidden="true">📋</div>
          <p>No requisitions yet.
            <a href="/reqon/requisitions/new.php" class="text-green fw-500">Create your first request →</a>
          </p>
        </div>
      <?php else: ?>
        <table class="req-table" aria-label="Recent requisitions">
          <thead>
            <tr>
              <th scope="col">ID</th>
              <th scope="col">Department</th>
              <?php if ($isApprover): ?><th scope="col">Submitted By</th><?php endif; ?>
              <th scope="col">Type</th>
              <th scope="col">Status</th>
              <th scope="col">Date</th>
              <th scope="col">Action</th>
            </tr>
          </thead>
          <tbody>
            <?php foreach ($recentReqs as $req): ?>
              <tr>
                <td class="req-id">
                  <?= htmlspecialchars($req['req_number'] ?? ('REQ-' . str_pad($req['id'], 3, '0', STR_PAD_LEFT))) ?>
                </td>
                <td><?= htmlspecialchars($req['dept_name'] ?? '—') ?></td>
                <?php if ($isApprover): ?>
                  <td><?= htmlspecialchars($req['submitter_name'] ?? '—') ?></td>
                <?php endif; ?>
                <td style="text-transform: capitalize">
                  <?= htmlspecialchars(str_replace('_', ' ', $req['type'])) ?>
                </td>
                <td><?= statusBadge($req['status']) ?></td>
                <td class="text-muted">
                  <?= htmlspecialchars(date('Y-m-d', strtotime($req['created_at']))) ?>
                </td>
                <td>
                  <a href="/reqon/requisitions/view.php?id=<?= $req['id'] ?>"
                     class="btn btn-outline btn-sm">View</a>
                </td>
              </tr>
            <?php endforeach; ?>
          </tbody>
        </table>
      <?php endif; ?>
    </div>

    <?php if (!empty($recentReqs)): ?>
      <div class="card-footer">
        <a href="/reqon/requisitions/list.php">View All Requisitions →</a>
      </div>
    <?php endif; ?>

  </div><!-- /card -->

</div><!-- /page-wrap -->

<?php include __DIR__ . '/includes/footer.php'; ?>