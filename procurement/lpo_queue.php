<?php
// procurement/lpo_queue.php
// Access: Procurement Head (user 7) only. Admin gets view-only access.

require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../includes/auth.php';

requireLogin();

$user   = currentUser();
$uid    = (int)$user['user_id'];
$isAdmin = ($user['role_id'] ?? 0) == 1;
$isMary  = ($uid === APPROVER_PROCUREMENT_HEAD);

// Only Mary and Admin may access this page
if (!$isMary && !$isAdmin) {
    setFlash('error', 'Access denied. This page is for the Procurement Head only.');
    redirect(BASE_URL . '/dashboard.php');
}

// ── Filters ───────────────────────────────────────────────────────────────
$filterType   = get('type');
$filterStatus = get('lpo_status'); 
$search       = trim(get('q'));
$page         = max(1, (int)get('page', '1'));
$perPage      = 20;
$offset       = ($page - 1) * $perPage;

$where  = [
    "r.current_status = 'approved'",
    "r.requisition_type IN ('procurement','it_asset','merchandise')",
];
$params = [];

if ($search) {
    $where[]  = "(r.requisition_number LIKE ? OR u.full_name LIKE ? OR d.department_name LIKE ?)";
    $like     = "%{$search}%";
    $params[] = $like; $params[] = $like; $params[] = $like;
}
if ($filterType && in_array($filterType, ['procurement','it_asset','merchandise'])) {
    $where[]  = "r.requisition_type = ?";
    $params[] = $filterType;
}
if ($filterStatus === 'pending') {
    $where[] = "l.lpo_id IS NULL";
} elseif ($filterStatus === 'generated') {
    $where[] = "l.lpo_id IS NOT NULL";
}

$whereSQL = implode(' AND ', $where);

$total = (int)(fetchOne(
    "SELECT COUNT(*) AS cnt
       FROM requisitions r
       LEFT JOIN users u       ON u.user_id       = r.requester_id
       LEFT JOIN departments d ON d.department_id = r.department_id
       LEFT JOIN lpo_log l     ON l.requisition_id = r.requisition_id
      WHERE {$whereSQL}",
    $params
)['cnt'] ?? 0);

$reqs = fetchAll(
    "SELECT r.*,
            u.full_name       AS submitter_name,
            d.department_name AS dept_name,
            l.lpo_id,
            l.lpo_number,
            l.generated_at,
            lg.full_name      AS generated_by_name
       FROM requisitions r
       LEFT JOIN users u       ON u.user_id        = r.requester_id
       LEFT JOIN departments d ON d.department_id  = r.department_id
       LEFT JOIN lpo_log l     ON l.requisition_id = r.requisition_id
       LEFT JOIN users lg      ON lg.user_id        = l.generated_by
      WHERE {$whereSQL}
      ORDER BY l.lpo_id IS NULL DESC, r.final_decision_date DESC
      LIMIT {$perPage} OFFSET {$offset}",
    $params
);

$totalPages   = (int)ceil($total / $perPage);
$pendingCount = (int)(fetchOne(
    "SELECT COUNT(*) AS cnt
       FROM requisitions r
       LEFT JOIN lpo_log l ON l.requisition_id = r.requisition_id
      WHERE r.current_status = 'approved'
        AND r.requisition_type IN ('procurement','it_asset','merchandise')
        AND l.lpo_id IS NULL"
)['cnt'] ?? 0);

$generatedCount = (int)(fetchOne(
    "SELECT COUNT(*) AS cnt FROM lpo_log"
)['cnt'] ?? 0);

$pageTitle = 'LPO Queue';
include __DIR__ . '/../includes/header.php';
?>

<div class="page-wrap">

  <div class="page-header">
    <div>
      <h1 class="page-title">LPO Queue</h1>
      <p style="font-size:13px;color:var(--text-muted);margin-top:3px">
        Fully approved requisitions awaiting LPO generation
      </p>
    </div>
  </div>

  <?php renderFlash(); ?>

  <!-- Stat cards -->
  <section class="stat-grid" style="grid-template-columns:repeat(3,1fr);margin-bottom:28px" aria-label="LPO summary">
    <div class="stat-card <?= $pendingCount > 0 ? 'stat-card-warn' : '' ?>">
      <div class="stat-icon pending" aria-hidden="true">
        <svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round">
          <circle cx="12" cy="12" r="10"/><polyline points="12 6 12 12 16 14"/>
        </svg>
      </div>
      <span class="stat-label">Pending LPOs</span>
      <span class="stat-value"><?= number_format($pendingCount) ?></span>
      <span class="stat-sub">need generation</span>
    </div>
    <div class="stat-card">
      <div class="stat-icon approved" aria-hidden="true">
        <svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round">
          <path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/>
          <polyline points="14 2 14 8 20 8"/>
          <line x1="16" y1="13" x2="8" y2="13"/><line x1="16" y1="17" x2="8" y2="17"/>
        </svg>
      </div>
      <span class="stat-label">LPOs Generated</span>
      <span class="stat-value"><?= number_format($generatedCount) ?></span>
      <span class="stat-sub">all time</span>
    </div>
    <div class="stat-card">
      <div class="stat-icon total" aria-hidden="true">
        <svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round">
          <rect x="18" y="3" width="4" height="18"/><rect x="10" y="8" width="4" height="13"/><rect x="2" y="13" width="4" height="8"/>
        </svg>
      </div>
      <span class="stat-label">Total Approved</span>
      <span class="stat-value"><?= number_format($pendingCount + $generatedCount) ?></span>
      <span class="stat-sub">goods requisitions</span>
    </div>
  </section>

  <!-- Filters -->
  <form method="GET" action="" class="filter-bar" style="margin-bottom:24px">
    <div class="filter-search" style="flex:2;min-width:200px">
      <span class="search-icon" aria-hidden="true">
        <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round">
          <circle cx="11" cy="11" r="8"/><line x1="21" y1="21" x2="16.65" y2="16.65"/>
        </svg>
      </span>
      <input type="search" name="q" value="<?= e($search) ?>"
             placeholder="Search by REQ no., submitter, department…" aria-label="Search">
    </div>

    <select name="type" onchange="this.form.submit()">
      <option value="">All Types</option>
      <option value="procurement"  <?= $filterType==='procurement'  ? 'selected':'' ?>>Procurement</option>
      <option value="it_asset"     <?= $filterType==='it_asset'     ? 'selected':'' ?>>IT Asset</option>
      <option value="merchandise"  <?= $filterType==='merchandise'  ? 'selected':'' ?>>Merchandise</option>
    </select>

    <select name="lpo_status" onchange="this.form.submit()">
      <option value="">All</option>
      <option value="pending"   <?= $filterStatus==='pending'   ? 'selected':'' ?>>Pending LPO</option>
      <option value="generated" <?= $filterStatus==='generated' ? 'selected':'' ?>>LPO Generated</option>
    </select>

    <button type="submit" class="btn btn-outline">Filter</button>
    <?php if ($search || $filterType || $filterStatus): ?>
      <a href="<?= BASE_URL ?>/procurement/lpo_queue.php" class="btn btn-outline">Clear</a>
    <?php endif; ?>
  </form>

  <!-- Table -->
  <div class="card">
    <?php if (empty($reqs)): ?>
      <div class="empty-state">
        <div class="empty-icon" aria-hidden="true">📋</div>
        <p>No approved requisitions match your filters.</p>
      </div>
    <?php else: ?>
      <div class="table-wrap">
        <table class="req-table">
          <thead>
            <tr>
              <th>REQ No.</th>
              <th>Type</th>
              <th>Submitted By</th>
              <th>Department</th>
              <th style="text-align:right">Value (KES)</th>
              <th>Approved On</th>
              <th>LPO Status</th>
              <th>Actions</th>
            </tr>
          </thead>
          <tbody>
            <?php foreach ($reqs as $req): ?>
            <tr>
              <td class="req-id"><?= e($req['requisition_number']) ?></td>
              <td style="white-space:nowrap">
                <?= e(REQUISITION_TYPES[$req['requisition_type']] ?? ucfirst($req['requisition_type'])) ?>
              </td>
              <td><?= e($req['submitter_name'] ?? '—') ?></td>
              <td><?= e($req['dept_name'] ?? '—') ?></td>
              <td style="text-align:right;font-weight:500">
                <?= $req['total_amount'] > 0 ? number_format((float)$req['total_amount'], 2) : '—' ?>
              </td>
              <td class="text-muted" style="white-space:nowrap">
                <?= e(formatDate($req['final_decision_date'])) ?>
              </td>
              <td>
                <?php if ($req['lpo_id']): ?>
                  <span style="display:inline-flex;align-items:center;gap:5px;font-size:12px;color:var(--green-text);font-weight:600">
                    <svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round"><polyline points="20 6 9 17 4 12"/></svg>
                    <?= e($req['lpo_number']) ?>
                  </span>
                  <div style="font-size:11px;color:var(--text-muted);margin-top:2px">
                    <?= e(formatDate($req['generated_at'])) ?>
                    <?php if ($req['generated_by_name']): ?>
                      · <?= e($req['generated_by_name']) ?>
                    <?php endif; ?>
                  </div>
                <?php else: ?>
                  <span style="font-size:12px;color:var(--orange-text);font-weight:600">Pending</span>
                <?php endif; ?>
              </td>
              <td>
                <div style="display:flex;gap:6px;flex-wrap:wrap">
                  <a href="<?= BASE_URL ?>/requisitions/view.php?id=<?= (int)$req['requisition_id'] ?>"
                     class="btn btn-outline btn-sm">View</a>

                  <?php if ($isMary): ?>
                    <?php if (!$req['lpo_id']): ?>
                      <!-- Generate LPO: POST to record it, then open document -->
                      <form method="POST" action="<?= BASE_URL ?>/procurement/record_lpo.php"
                            onsubmit="return confirm('Generate LPO for <?= e($req['requisition_number']) ?>?')">
                        <input type="hidden" name="requisition_id" value="<?= (int)$req['requisition_id'] ?>">
                        <button type="submit" class="btn btn-primary btn-sm">Generate LPO</button>
                      </form>
                    <?php else: ?>
                      <a href="<?= BASE_URL ?>/api/generate_lpo.php?id=<?= (int)$req['requisition_id'] ?>"
                         target="_blank" class="btn btn-outline btn-sm">Re-print LPO</a>
                    <?php endif; ?>
                  <?php elseif ($isAdmin && $req['lpo_id']): ?>
                    <a href="<?= BASE_URL ?>/api/generate_lpo.php?id=<?= (int)$req['requisition_id'] ?>"
                       target="_blank" class="btn btn-outline btn-sm">View LPO</a>
                  <?php endif; ?>
                </div>
              </td>
            </tr>
            <?php endforeach; ?>
          </tbody>
        </table>
      </div>

      <!-- Pagination -->
      <?php if ($totalPages > 1): ?>
      <div style="display:flex;align-items:center;justify-content:space-between;padding:14px 20px;border-top:1px solid var(--border)">
        <span style="font-size:13px;color:var(--text-muted)">
          Showing <?= $offset + 1 ?>–<?= min($offset + $perPage, $total) ?> of <?= number_format($total) ?>
        </span>
        <div style="display:flex;gap:6px">
          <?php if ($page > 1): ?>
            <a href="?page=<?= $page-1 ?>&<?= http_build_query(array_filter(['q'=>$search,'type'=>$filterType,'lpo_status'=>$filterStatus])) ?>"
               class="btn btn-outline btn-sm">← Prev</a>
          <?php endif; ?>
          <?php if ($page < $totalPages): ?>
            <a href="?page=<?= $page+1 ?>&<?= http_build_query(array_filter(['q'=>$search,'type'=>$filterType,'lpo_status'=>$filterStatus])) ?>"
               class="btn btn-outline btn-sm">Next →</a>
          <?php endif; ?>
        </div>
      </div>
      <?php else: ?>
      <div class="card-footer">
        <span style="font-size:13px;color:var(--text-muted)"><?= number_format($total) ?> requisition<?= $total !== 1 ? 's' : '' ?></span>
      </div>
      <?php endif; ?>
    <?php endif; ?>
  </div>

</div>

<?php include __DIR__ . '/../includes/footer.php'; ?>
