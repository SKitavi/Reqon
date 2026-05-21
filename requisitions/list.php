<?php
// requisitions/list.php
// Full searchable, filterable list of requisitions.
// Staff see only their own. Approvers/admin see all.

require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../includes/auth.php';

requireLogin();

$user      = currentUser();
$userRole  = $user['role_name'] ?? '';
$userLevel = getRoleLevel($user);
$isAdmin   = strtolower($userRole) === 'system admin';
$isApprover = $userLevel > 0 || $isAdmin;
$uid       = (int)$user['user_id'];
$deptId    = (int)($user['department_id'] ?? 0);

// ── Filters ───────────────────────────────────────────────
$search         = trim(get('q'));
$filterType     = get('type');
$filterStatus   = get('status');
$filterPriority = get('priority');
$dateFrom       = get('date_from');
$dateTo         = get('date_to');
$page           = max(1, (int)get('page', '1'));
$perPage        = defined('ITEMS_PER_PAGE') ? ITEMS_PER_PAGE : 20;
$offset         = ($page - 1) * $perPage;

// ── Build WHERE with visibility scoping ──────────────────
$where  = ['1=1'];
$params = [];

if ($userLevel === 0 && !$isAdmin) {
    // Requester — own only
    $where[]  = 'r.requester_id = ?';
    $params[] = $uid;
} elseif ($userLevel === 1) {
    // Dept Head — own department
    $where[]  = 'r.department_id = ?';
    $params[] = $deptId;
} elseif ($userLevel === 2 && $uid !== APPROVER_PROCUREMENT_HEAD) {
    // HR Director — Personnel only
    $where[] = "r.requisition_type = 'personnel'";
} elseif ($userLevel === 2 && $uid === APPROVER_PROCUREMENT_HEAD) {
    // Procurement Head — Procurement + IT Asset + Merchandise
    $where[] = "r.requisition_type IN ('procurement','it_asset','merchandise')";
}
// Finance Director (level 3), MD (level 4), Admin → no extra scope (see all)

if ($search) {
    $where[]  = '(r.requisition_number LIKE ? OR u.full_name LIKE ? OR r.description LIKE ? OR d.department_name LIKE ?)';
    $like     = "%{$search}%";
    $params   = array_merge($params, [$like, $like, $like, $like]);
}

if ($filterType && array_key_exists($filterType, REQUISITION_TYPES)) {
    $where[]  = 'r.requisition_type = ?';
    $params[] = $filterType;
}

if ($filterStatus && array_key_exists($filterStatus, REQUISITION_STATUSES)) {
    $where[]  = 'r.current_status = ?';
    $params[] = $filterStatus;
}

if ($filterPriority && in_array($filterPriority, ['high','medium','low'])) {
    $where[]  = 'r.priority = ?';
    $params[] = $filterPriority;
}

if ($dateFrom) {
    $where[]  = 'r.submission_date >= ?';
    $params[] = $dateFrom . ' 00:00:00';
}

if ($dateTo) {
    $where[]  = 'r.submission_date <= ?';
    $params[] = $dateTo . ' 23:59:59';
}

$whereSQL = implode(' AND ', $where);

// ── Count + fetch ─────────────────────────────────────────
$totalRow = fetchOne(
    "SELECT COUNT(*) AS cnt
       FROM requisitions r
       LEFT JOIN users u       ON u.user_id = r.requester_id
       LEFT JOIN departments d ON d.department_id = r.department_id
      WHERE {$whereSQL}",
    $params
);
$total      = (int)($totalRow['cnt'] ?? 0);
$totalPages = (int)ceil($total / $perPage);

$reqs = fetchAll(
    "SELECT r.*,
            u.full_name       AS submitter_name,
            d.department_name AS dept_name
       FROM requisitions r
       LEFT JOIN users u       ON u.user_id = r.requester_id
       LEFT JOIN departments d ON d.department_id = r.department_id
      WHERE {$whereSQL}
      ORDER BY
        CASE r.current_status WHEN 'pending' THEN 0 ELSE 1 END ASC,
        CASE r.priority WHEN 'high' THEN 0 WHEN 'medium' THEN 1 ELSE 2 END ASC,
        r.submission_date DESC
      LIMIT {$perPage} OFFSET {$offset}",
    $params
);

// ── URL builder for pagination (preserves filters) ────────
function listUrl(array $extra = []): string {
    $p = array_merge($_GET, $extra);
    unset($p['page']);
    $qs = http_build_query(array_filter($p, fn($v) => $v !== ''));
    return BASE_URL . '/requisitions/list.php?' . $qs . ($qs ? '&' : '');
}

$hasFilters = $search || $filterType || $filterStatus || $filterPriority || $dateFrom || $dateTo;

$pageTitle = $isApprover ? 'All Requisitions' : 'My Requisitions';
include __DIR__ . '/../includes/header.php';
?>

<div class="page-wrap">

  <div class="page-header">
    <h1 class="page-title">
      <?= $isApprover ? 'All Requisitions' : 'My Requisitions' ?>
      <?php if ($total > 0): ?>
        <span style="font-size:14px;font-weight:400;color:var(--text-muted);margin-left:8px">
          <?= number_format($total) ?> result<?= $total !== 1 ? 's' : '' ?>
        </span>
      <?php endif; ?>
    </h1>
    <a href="<?= BASE_URL ?>/requisitions/new.php" class="btn btn-primary">
      <svg width="14" height="14" viewBox="0 0 24 24" fill="none"
           stroke="currentColor" stroke-width="2.5" stroke-linecap="round" aria-hidden="true">
        <line x1="12" y1="5" x2="12" y2="19"/><line x1="5" y1="12" x2="19" y2="12"/>
      </svg>
      New Request
    </a>
  </div>

  <?php renderFlash(); ?>

  <!-- Filter bar -->
  <form method="GET" action="" class="filter-bar" role="search">

    <div class="filter-search" style="flex:2;min-width:220px">
      <span class="search-icon" aria-hidden="true">
        <svg width="14" height="14" viewBox="0 0 24 24" fill="none"
             stroke="currentColor" stroke-width="2" stroke-linecap="round">
          <circle cx="11" cy="11" r="8"/><line x1="21" y1="21" x2="16.65" y2="16.65"/>
        </svg>
      </span>
      <input type="search" name="q"
             placeholder="Search by ID, name, department…"
             value="<?= e($search) ?>" aria-label="Search">
    </div>

    <select name="type" aria-label="Type" onchange="this.form.submit()">
      <option value="">All Types</option>
      <?php foreach (REQUISITION_TYPES as $k => $v): ?>
        <option value="<?= e($k) ?>" <?= $filterType === $k ? 'selected' : '' ?>><?= e($v) ?></option>
      <?php endforeach; ?>
    </select>

    <select name="status" aria-label="Status" onchange="this.form.submit()">
      <option value="">All Statuses</option>
      <?php foreach (REQUISITION_STATUSES as $k => $v): ?>
        <option value="<?= e($k) ?>" <?= $filterStatus === $k ? 'selected' : '' ?>><?= e($v) ?></option>
      <?php endforeach; ?>
    </select>

    <select name="priority" aria-label="Priority" onchange="this.form.submit()">
      <option value="">All Priorities</option>
      <option value="high"   <?= $filterPriority==='high'   ? 'selected':'' ?>>High</option>
      <option value="medium" <?= $filterPriority==='medium' ? 'selected':'' ?>>Medium</option>
      <option value="low"    <?= $filterPriority==='low'    ? 'selected':'' ?>>Low</option>
    </select>

    <input type="date" name="date_from" value="<?= e($dateFrom) ?>"
           title="From date" aria-label="Date from"
           style="padding:9px 12px;border:1px solid var(--border);border-radius:var(--radius);font-size:14px;outline:none;background:var(--white)">
    <input type="date" name="date_to" value="<?= e($dateTo) ?>"
           title="To date" aria-label="Date to"
           style="padding:9px 12px;border:1px solid var(--border);border-radius:var(--radius);font-size:14px;outline:none;background:var(--white)">

    <button type="submit" class="btn btn-outline">Filter</button>

    <?php if ($hasFilters): ?>
      <a href="<?= BASE_URL ?>/requisitions/list.php" class="btn btn-outline">Clear</a>
    <?php endif; ?>

  </form>

  <!-- Active filter pills -->
  <?php if ($hasFilters): ?>
  <div style="display:flex;flex-wrap:wrap;gap:6px;margin-bottom:16px;margin-top:-8px">
    <?php
    $pills = [];
    if ($search)         $pills[] = ['Search: "' . e($search) . '"',   'q'];
    if ($filterType)     $pills[] = [e(REQUISITION_TYPES[$filterType] ?? $filterType), 'type'];
    if ($filterStatus)   $pills[] = [e(REQUISITION_STATUSES[$filterStatus] ?? $filterStatus), 'status'];
    if ($filterPriority) $pills[] = [ucfirst(e($filterPriority)) . ' priority', 'priority'];
    if ($dateFrom)       $pills[] = ['From ' . e($dateFrom), 'date_from'];
    if ($dateTo)         $pills[] = ['To ' . e($dateTo), 'date_to'];
    foreach ($pills as [$label, $key]):
      $removeUrl = listUrl([$key => '']);
    ?>
    <a href="<?= $removeUrl ?>" class="filter-pill"
       style="display:inline-flex;align-items:center;gap:5px;padding:3px 10px;
              background:var(--bg);border:1px solid var(--border);border-radius:20px;
              font-size:12px;color:var(--text);text-decoration:none">
      <?= $label ?>
      <span style="color:var(--text-muted);font-size:14px;line-height:1">×</span>
    </a>
    <?php endforeach; ?>
  </div>
  <?php endif; ?>

  <!-- Results table -->
  <div class="card">

    <?php if (empty($reqs)): ?>

      <div class="empty-state">
        <div class="empty-icon" aria-hidden="true"><?= $hasFilters ? '🔍' : '📋' ?></div>
        <?php if ($hasFilters): ?>
          <p>No requisitions match your filters.</p>
          <a href="<?= BASE_URL ?>/requisitions/list.php"
             style="font-size:13px;color:var(--green);font-weight:500;margin-top:8px;display:inline-block">
            Clear all filters →
          </a>
        <?php else: ?>
          <p>No requisitions yet.</p>
          <a href="<?= BASE_URL ?>/requisitions/new.php"
             style="font-size:13px;color:var(--green);font-weight:500;margin-top:8px;display:inline-block">
            Create your first request →
          </a>
        <?php endif; ?>
      </div>

    <?php else: ?>

      <div class="table-wrap">
        <table class="req-table" aria-label="Requisitions list">
          <thead>
            <tr>
              <th scope="col">ID</th>
              <th scope="col">Type</th>
              <?php if ($isApprover): ?>
                <th scope="col">Submitted by</th>
              <?php endif; ?>
              <th scope="col">Department</th>
              <th scope="col">Priority</th>
              <th scope="col">Status</th>
              <th scope="col">Date Required</th>
              <th scope="col">Submitted</th>
              <th scope="col">Action</th>
            </tr>
          </thead>
          <tbody>
            <?php foreach ($reqs as $req): ?>
            <tr>
              <td class="req-id">
                <?= e($req['requisition_number'] ?? ('REQ-' . str_pad($req['requisition_id'], 3, '0', STR_PAD_LEFT))) ?>
              </td>
              <td style="white-space:nowrap">
                <?= e(REQUISITION_TYPES[$req['requisition_type']] ?? ucfirst(str_replace('_',' ',$req['requisition_type']))) ?>
              </td>
              <?php if ($isApprover): ?>
                <td><?= e($req['submitter_name'] ?? '—') ?></td>
              <?php endif; ?>
              <td><?= e($req['dept_name'] ?? '—') ?></td>
              <td><?= priorityBadge($req['priority'] ?? 'medium') ?></td>
              <td><?= statusBadge($req['current_status']) ?></td>
              <td class="text-muted" style="white-space:nowrap">
                <?= e(formatDate($req['date_required'])) ?>
              </td>
              <td class="text-muted" style="white-space:nowrap">
                <?= e(timeAgo($req['submission_date'] ?? $req['created_at'])) ?>
              </td>
              <td>
                <a href="<?= BASE_URL ?>/requisitions/view.php?id=<?= (int)$req['requisition_id'] ?>"
                   class="btn btn-outline btn-sm">View</a>
              </td>
            </tr>
            <?php endforeach; ?>
          </tbody>
        </table>
      </div>

      <!-- Pagination -->
      <?php if ($totalPages > 1): ?>
      <div style="display:flex;align-items:center;justify-content:space-between;
                  padding:14px 20px;border-top:1px solid var(--border);background:var(--bg)">
        <span style="font-size:13px;color:var(--text-muted)">
          Showing <?= $offset + 1 ?>–<?= min($offset + $perPage, $total) ?> of <?= number_format($total) ?>
        </span>
        <div style="display:flex;gap:6px">
          <?php if ($page > 1): ?>
            <a href="<?= listUrl() ?>page=<?= $page-1 ?>" class="btn btn-outline btn-sm">← Prev</a>
          <?php endif; ?>
          <?php
          // Show page numbers with ellipsis
          $range = 2;
          for ($p = 1; $p <= $totalPages; $p++):
            if ($p === 1 || $p === $totalPages || abs($p - $page) <= $range):
          ?>
            <a href="<?= listUrl() ?>page=<?= $p ?>"
               class="btn btn-sm <?= $p === $page ? 'btn-dark' : 'btn-outline' ?>"
               <?= $p === $page ? 'aria-current="page"' : '' ?>>
              <?= $p ?>
            </a>
          <?php elseif (abs($p - $page) === $range + 1): ?>
            <span style="padding:5px 4px;font-size:13px;color:var(--text-muted)">…</span>
          <?php endif; endfor; ?>
          <?php if ($page < $totalPages): ?>
            <a href="<?= listUrl() ?>page=<?= $page+1 ?>" class="btn btn-outline btn-sm">Next →</a>
          <?php endif; ?>
        </div>
      </div>
      <?php else: ?>
        <div class="card-footer">
          <span style="font-size:13px;color:var(--text-muted)">
            <?= number_format($total) ?> requisition<?= $total !== 1 ? 's' : '' ?> total
          </span>
        </div>
      <?php endif; ?>

    <?php endif; ?>
  </div>

</div>

<?php include __DIR__ . '/../includes/footer.php'; ?>