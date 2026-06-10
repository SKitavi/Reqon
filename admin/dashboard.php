<?php
// admin/dashboard.php — System Admin landing page: insight cards + full audit log
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../includes/auth.php';

requireLogin();

$user   = currentUser();
$roleId = (int)($user['role_id'] ?? 0);

// Only System Admin (role_id=1) may access this page
if ($roleId !== 1) {
    setFlash('error', 'Access denied.');
    redirect(BASE_URL . '/dashboard.php');
}

// ── Insight cards ─────────────────────────────────────────────────────────

// 1. Total requisitions (all time)
$totalReqs = (int)(fetchOne("SELECT COUNT(*) AS c FROM requisitions")['c'] ?? 0);

// 2. Pending right now (across all levels)
$pendingNow = (int)(fetchOne("SELECT COUNT(*) AS c FROM requisitions WHERE current_status = 'pending'")['c'] ?? 0);

// 3. Approved this month — count + total KES
$approvedMonth = fetchOne(
    "SELECT COUNT(*) AS cnt, COALESCE(SUM(total_amount),0) AS total_kes
       FROM requisitions
      WHERE current_status = 'approved'
        AND MONTH(final_decision_date) = MONTH(NOW())
        AND YEAR(final_decision_date)  = YEAR(NOW())"
);

// 4. Average approval time (days from submission to final decision, approved only)
$avgApproval = fetchOne(
    "SELECT ROUND(AVG(TIMESTAMPDIFF(DAY, submission_date, final_decision_date)), 1) AS avg_days
       FROM requisitions
      WHERE current_status = 'approved' AND final_decision_date IS NOT NULL"
);

// 5. Stale requests — pending > 7 days
$staleCount = (int)(fetchOne(
    "SELECT COUNT(*) AS c FROM requisitions
      WHERE current_status = 'pending'
        AND submission_date < DATE_SUB(NOW(), INTERVAL 7 DAY)"
)['c'] ?? 0);

// 6. Active users this week
$activeUsers = (int)(fetchOne(
    "SELECT COUNT(*) AS c FROM users
      WHERE last_login >= DATE_SUB(NOW(), INTERVAL 7 DAY)"
)['c'] ?? 0);

// 7. Top spending department this month
$topDept = fetchOne(
    "SELECT d.department_name, COALESCE(SUM(r.total_amount),0) AS total_kes
       FROM requisitions r
       JOIN departments d ON d.department_id = r.department_id
      WHERE r.current_status = 'approved'
        AND MONTH(r.final_decision_date) = MONTH(NOW())
        AND YEAR(r.final_decision_date)  = YEAR(NOW())
      GROUP BY r.department_id
      ORDER BY total_kes DESC
      LIMIT 1"
);

// 8. Audit events today
$auditToday = (int)(fetchOne(
    "SELECT COUNT(*) AS c FROM audit_log WHERE DATE(timestamp) = CURDATE()"
)['c'] ?? 0);

// ── Audit log (filterable) ────────────────────────────────────────────────
$page    = max(1, (int)get('page', '1'));
$perPage = 40;
$offset  = ($page - 1) * $perPage;

$filterAction  = get('action_filter');
$filterDept    = get('dept_filter');
$filterUser    = get('user_filter');
$filterReqType = get('type_filter');
$filterDateFrom= get('date_from');
$filterDateTo  = get('date_to');

$where  = ['1=1'];
$params = [];

if ($filterAction) {
    $where[]  = 'al.action_type = ?';
    $params[] = $filterAction;
}
if ($filterUser) {
    $where[]  = 'u.full_name LIKE ?';
    $params[] = "%{$filterUser}%";
}
if ($filterDept) {
    $where[]  = 'd.department_id = ?';
    $params[] = (int)$filterDept;
}
if ($filterReqType) {
    $where[]  = 'r.requisition_type = ?';
    $params[] = $filterReqType;
}
if ($filterDateFrom) {
    $where[]  = 'al.timestamp >= ?';
    $params[] = $filterDateFrom . ' 00:00:00';
}
if ($filterDateTo) {
    $where[]  = 'al.timestamp <= ?';
    $params[] = $filterDateTo . ' 23:59:59';
}

$whereSQL = implode(' AND ', $where);

$total = (int)(fetchOne(
    "SELECT COUNT(*) AS cnt
       FROM audit_log al
       LEFT JOIN users       u ON u.user_id       = al.user_id
       LEFT JOIN requisitions r ON r.requisition_id = al.record_id AND al.table_affected = 'requisitions'
       LEFT JOIN departments  d ON d.department_id  = r.department_id
      WHERE {$whereSQL}",
    $params
)['cnt'] ?? 0);

$logs = fetchAll(
    "SELECT al.*, u.full_name AS actor_name,
            r.requisition_number, r.requisition_type,
            d.department_name
       FROM audit_log al
       LEFT JOIN users        u ON u.user_id        = al.user_id
       LEFT JOIN requisitions r ON r.requisition_id = al.record_id AND al.table_affected = 'requisitions'
       LEFT JOIN departments  d ON d.department_id  = r.department_id
      WHERE {$whereSQL}
      ORDER BY al.timestamp DESC
      LIMIT {$perPage} OFFSET {$offset}",
    $params
);

$totalPages  = (int)ceil($total / $perPage);
$actionTypes = ['CREATE','APPROVE','REJECT','CANCEL','UPDATE','LOGIN'];
$departments = fetchAll("SELECT department_id, department_name FROM departments ORDER BY department_name");

$pageTitle = 'Admin Dashboard';
include __DIR__ . '/../includes/header.php';
?>

<div class="page-wrap">

  <div class="page-header" style="flex-wrap:wrap;gap:10px">
    <h1 class="page-title">Admin Dashboard</h1>
    <div style="display:flex;gap:8px">
      <a href="<?= BASE_URL ?>/admin/users.php"   class="btn btn-outline btn-sm">👤 Manage Users</a>
      <a href="<?= BASE_URL ?>/admin/catalog.php" class="btn btn-outline btn-sm">📦 Manage Catalog</a>
    </div>
  </div>

  <?php renderFlash(); ?>

  <!-- ── Insight cards ──────────────────────────────────────────────────── -->
  <section class="stat-grid stat-grid-wide" aria-label="Admin insights" style="grid-template-columns:repeat(auto-fill,minmax(200px,1fr));margin-bottom:28px">

    <div class="stat-card">
      <div class="stat-icon total" aria-hidden="true">
        <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round">
          <rect x="18" y="3" width="4" height="18"/><rect x="10" y="8" width="4" height="13"/><rect x="2" y="13" width="4" height="8"/>
        </svg>
      </div>
      <span class="stat-label">Total Requisitions</span>
      <span class="stat-value"><?= number_format($totalReqs) ?></span>
    </div>

    <div class="stat-card <?= $pendingNow > 0 ? 'stat-card-warn' : '' ?>">
      <div class="stat-icon pending" aria-hidden="true">
        <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round">
          <circle cx="12" cy="12" r="10"/><polyline points="12 6 12 12 16 14"/>
        </svg>
      </div>
      <span class="stat-label">Pending Now</span>
      <span class="stat-value"><?= number_format($pendingNow) ?></span>
    </div>

    <div class="stat-card">
      <div class="stat-icon approved" aria-hidden="true">
        <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round">
          <path d="M22 11.08V12a10 10 0 1 1-5.93-9.14"/><polyline points="22 4 12 14.01 9 11.01"/>
        </svg>
      </div>
      <span class="stat-label">Approved This Month</span>
      <span class="stat-value"><?= number_format((int)($approvedMonth['cnt'] ?? 0)) ?></span>
      <span class="stat-sub"><?= formatKES((float)($approvedMonth['total_kes'] ?? 0)) ?></span>
    </div>

    <div class="stat-card">
      <div class="stat-icon total" aria-hidden="true">
        <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round">
          <circle cx="12" cy="12" r="10"/><polyline points="12 6 12 12 16 14"/>
        </svg>
      </div>
      <span class="stat-label">Avg. Approval Time</span>
      <span class="stat-value"><?= $avgApproval['avg_days'] ?? '—' ?></span>
      <span class="stat-sub">days</span>
    </div>

    <div class="stat-card <?= $staleCount > 0 ? 'stat-card-danger' : '' ?>">
      <div class="stat-icon rejected" aria-hidden="true">
        <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round">
          <circle cx="12" cy="12" r="10"/><line x1="12" y1="8" x2="12" y2="12"/><line x1="12" y1="16" x2="12.01" y2="16"/>
        </svg>
      </div>
      <span class="stat-label">Stale (&gt;7 days)</span>
      <span class="stat-value"><?= number_format($staleCount) ?></span>
      <span class="stat-sub">needs attention</span>
    </div>

    <div class="stat-card">
      <div class="stat-icon total" aria-hidden="true">
        <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round">
          <path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"/><circle cx="9" cy="7" r="4"/>
          <path d="M23 21v-2a4 4 0 0 0-3-3.87"/><path d="M16 3.13a4 4 0 0 1 0 7.75"/>
        </svg>
      </div>
      <span class="stat-label">Active Users</span>
      <span class="stat-value"><?= number_format($activeUsers) ?></span>
      <span class="stat-sub">this week</span>
    </div>

    <div class="stat-card">
      <div class="stat-icon approved" aria-hidden="true">
        <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round">
          <rect x="2" y="7" width="20" height="14" rx="2"/><path d="M16 21V5a2 2 0 0 0-2-2h-4a2 2 0 0 0-2 2v16"/>
        </svg>
      </div>
      <span class="stat-label">Top Spending Dept</span>
      <span class="stat-value" style="font-size:16px"><?= e($topDept['department_name'] ?? '—') ?></span>
      <span class="stat-sub"><?= $topDept ? formatKES((float)$topDept['total_kes']) : 'this month' ?></span>
    </div>

    <div class="stat-card">
      <div class="stat-icon pending" aria-hidden="true">
        <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round">
          <path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/>
          <polyline points="14 2 14 8 20 8"/><line x1="16" y1="13" x2="8" y2="13"/><line x1="16" y1="17" x2="8" y2="17"/>
        </svg>
      </div>
      <span class="stat-label">Audit Events Today</span>
      <span class="stat-value"><?= number_format($auditToday) ?></span>
    </div>

  </section>

  <!-- ── Audit log ──────────────────────────────────────────────────────── -->
  <div class="card">
    <div class="card-header" style="flex-wrap:wrap;gap:10px">
      <h2 class="card-title">Audit Log</h2>
      <span style="font-size:13px;color:var(--text-muted)"><?= number_format($total) ?> entries</span>
    </div>

    <!-- Filters -->
    <form method="GET" action="" style="display:flex;gap:8px;flex-wrap:wrap;padding:14px 20px;border-bottom:1px solid var(--border)">

      <select name="action_filter" onchange="this.form.submit()"
              style="padding:7px 10px;border:1px solid var(--border);border-radius:var(--radius);font-size:13px;background:var(--white);outline:none">
        <option value="">All actions</option>
        <?php foreach ($actionTypes as $at): ?>
          <option value="<?= $at ?>" <?= $filterAction === $at ? 'selected' : '' ?>><?= $at ?></option>
        <?php endforeach; ?>
      </select>

      <select name="dept_filter" onchange="this.form.submit()"
              style="padding:7px 10px;border:1px solid var(--border);border-radius:var(--radius);font-size:13px;background:var(--white);outline:none">
        <option value="">All departments</option>
        <?php foreach ($departments as $dept): ?>
          <option value="<?= $dept['department_id'] ?>" <?= (int)$filterDept === (int)$dept['department_id'] ? 'selected' : '' ?>>
            <?= e($dept['department_name']) ?>
          </option>
        <?php endforeach; ?>
      </select>

      <select name="type_filter" onchange="this.form.submit()"
              style="padding:7px 10px;border:1px solid var(--border);border-radius:var(--radius);font-size:13px;background:var(--white);outline:none">
        <option value="">All types</option>
        <?php foreach (REQUISITION_TYPES as $k => $v): ?>
          <option value="<?= $k ?>" <?= $filterReqType === $k ? 'selected' : '' ?>><?= e($v) ?></option>
        <?php endforeach; ?>
      </select>

      <input type="text" name="user_filter" value="<?= e($filterUser) ?>"
             placeholder="Filter by user…"
             style="padding:7px 10px;border:1px solid var(--border);border-radius:var(--radius);font-size:13px;outline:none;min-width:140px">

      <input type="date" name="date_from" value="<?= e($filterDateFrom) ?>"
             style="padding:7px 10px;border:1px solid var(--border);border-radius:var(--radius);font-size:13px;outline:none">
      <input type="date" name="date_to" value="<?= e($filterDateTo) ?>"
             style="padding:7px 10px;border:1px solid var(--border);border-radius:var(--radius);font-size:13px;outline:none">

      <button type="submit" class="btn btn-outline btn-sm">Filter</button>
      <?php if ($filterAction || $filterUser || $filterDept || $filterReqType || $filterDateFrom || $filterDateTo): ?>
        <a href="<?= BASE_URL ?>/admin/dashboard.php" class="btn btn-outline btn-sm">Clear</a>
      <?php endif; ?>
    </form>

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
              <th>Requisition</th>
              <th>Type</th>
              <th>Department</th>
              <th>Details</th>
              <th>IP</th>
            </tr>
          </thead>
          <tbody>
            <?php foreach ($logs as $log): ?>
            <tr>
              <td style="white-space:nowrap;color:var(--text-muted);font-size:12px">
                <?= e(date('d/m/Y H:i', strtotime($log['timestamp']))) ?>
              </td>
              <td style="font-size:13px"><?= e($log['actor_name'] ?? 'System') ?></td>
              <td>
                <span class="audit-action <?= e($log['action_type']) ?>">
                  <?= e($log['action_type']) ?>
                </span>
              </td>
              <td style="font-size:13px">
                <?php if ($log['table_affected'] === 'requisitions' && $log['record_id']): ?>
                  <a href="<?= BASE_URL ?>/requisitions/view.php?id=<?= (int)$log['record_id'] ?>"
                     style="color:var(--green)">
                    <?= e($log['requisition_number'] ?? '#' . $log['record_id']) ?>
                  </a>
                <?php else: ?>
                  <span style="color:var(--text-muted)"><?= e($log['table_affected'] ?? '—') ?></span>
                <?php endif; ?>
              </td>
              <td style="font-size:12px;color:var(--text-muted)">
                <?= $log['requisition_type'] ? e(REQUISITION_TYPES[$log['requisition_type']] ?? $log['requisition_type']) : '—' ?>
              </td>
              <td style="font-size:12px;color:var(--text-muted)"><?= e($log['department_name'] ?? '—') ?></td>
              <td style="font-size:12px;color:var(--text-muted);max-width:220px">
                <?= e(mb_strimwidth($log['description'] ?? '', 0, 80, '…')) ?>
              </td>
              <td style="font-size:11px;color:var(--text-muted);font-family:monospace">
                <?= e($log['ip_address'] ?? '') ?>
              </td>
            </tr>
            <?php endforeach; ?>
          </tbody>
        </table>
      </div>

      <?php if ($totalPages > 1): ?>
        <div style="display:flex;align-items:center;justify-content:center;gap:8px;padding:16px;border-top:1px solid var(--border)">
          <?php if ($page > 1): ?>
            <a href="?page=<?= $page-1 ?>&<?= http_build_query(array_filter(['action_filter'=>$filterAction,'dept_filter'=>$filterDept,'type_filter'=>$filterReqType,'user_filter'=>$filterUser,'date_from'=>$filterDateFrom,'date_to'=>$filterDateTo])) ?>"
               class="btn btn-outline btn-sm">← Prev</a>
          <?php endif; ?>
          <span style="font-size:13px;color:var(--text-muted)">Page <?= $page ?> of <?= $totalPages ?></span>
          <?php if ($page < $totalPages): ?>
            <a href="?page=<?= $page+1 ?>&<?= http_build_query(array_filter(['action_filter'=>$filterAction,'dept_filter'=>$filterDept,'type_filter'=>$filterReqType,'user_filter'=>$filterUser,'date_from'=>$filterDateFrom,'date_to'=>$filterDateTo])) ?>"
               class="btn btn-outline btn-sm">Next →</a>
          <?php endif; ?>
        </div>
      <?php endif; ?>
    <?php endif; ?>
  </div>

</div>

<?php include __DIR__ . '/../includes/footer.php'; ?>
