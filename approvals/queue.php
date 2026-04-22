<?php
// Shows requisitions waiting for the logged-in approver's decision.
// Only accessible to roles with an approval level (dept_head → managing_director).

require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../includes/auth.php';

requireLogin();

$user      = currentUser();
$userRole  = $user['role'] ?? '';
$userLevel = getRoleLevel($userRole);

// Non-approvers have no business here
if ($userLevel === 0) {
    setFlash('error', 'You do not have access to the approval queue.');
    redirect(BASE_URL . '/dashboard.php');
}

// ── Filter inputs (all GET so they're bookmarkable) ───────────────────────
$search    = trim(get('q'));
$filterType     = get('type');
$filterPriority = get('priority');
$dateFrom  = get('date_from');
$dateTo    = get('date_to');
$page      = max(1, (int)(get('page', '1')));
$perPage   = defined('ITEMS_PER_PAGE') ? ITEMS_PER_PAGE : 20;
$highlight = (int)get('highlight'); // scroll to this card after a redirect

// ── Build query ───────────────────────────────────────────────────────────
$where  = ["r.status = 'pending'", "r.current_approval_level = ?"];
$params = [$userLevel];

if ($search) {
    $where[]  = "(r.req_number LIKE ? OR u.name LIKE ? OR r.justification LIKE ?)";
    $like     = "%{$search}%";
    $params[] = $like;
    $params[] = $like;
    $params[] = $like;
}

if ($filterType && array_key_exists($filterType, REQUISITION_TYPES)) {
    $where[]  = "r.type = ?";
    $params[] = $filterType;
}

if ($filterPriority && in_array($filterPriority, ['high','medium','low'])) {
    $where[]  = "r.priority = ?";
    $params[] = $filterPriority;
}

if ($dateFrom) {
    $where[]  = "r.created_at >= ?";
    $params[] = $dateFrom . ' 00:00:00';
}

if ($dateTo) {
    $where[]  = "r.created_at <= ?";
    $params[] = $dateTo . ' 23:59:59';
}

$whereSQL = implode(' AND ', $where);

// Total count for pagination
$totalRow   = fetchOne(
    "SELECT COUNT(*) AS cnt
       FROM requisitions r
       LEFT JOIN users u ON u.id = r.submitted_by
      WHERE {$whereSQL}",
    $params
);
$total      = (int)($totalRow['cnt'] ?? 0);
$totalPages = (int)ceil($total / $perPage);
$offset     = ($page - 1) * $perPage;

// Fetch the current page
$reqs = fetchAll(
    "SELECT
         r.*,
         u.name          AS submitter_name,
         u.email         AS submitter_email,
         d.name          AS dept_name,
         s.name          AS section_name,
         (SELECT COUNT(*) FROM requisition_items ri WHERE ri.requisition_id = r.id)  AS item_count,
         (SELECT ri.item_name FROM requisition_items ri WHERE ri.requisition_id = r.id LIMIT 1) AS first_item
       FROM requisitions r
       LEFT JOIN users u ON u.id = r.submitted_by
       LEFT JOIN departments d ON d.id = r.department_id
       LEFT JOIN sections s ON s.id = r.section_id
      WHERE {$whereSQL}
      ORDER BY
         CASE r.priority WHEN 'high' THEN 1 WHEN 'medium' THEN 2 ELSE 3 END ASC,
         r.created_at ASC
      LIMIT {$perPage} OFFSET {$offset}",
    $params
);

// ── Icons by type ─────────────────────────────────────────────────────────
function typeIcon(string $type): string {
    $icons = [
        'personnel'   => '<svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round"><path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"/><circle cx="9" cy="7" r="4"/><path d="M23 21v-2a4 4 0 0 0-3-3.87"/><path d="M16 3.13a4 4 0 0 1 0 7.75"/></svg>',
        'procurement' => '<svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round"><path d="M6 2L3 6v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2V6l-3-4z"/><line x1="3" y1="6" x2="21" y2="6"/><path d="M16 10a4 4 0 0 1-8 0"/></svg>',
        'it_asset'    => '<svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round"><rect x="2" y="3" width="20" height="14" rx="2"/><line x1="8" y1="21" x2="16" y2="21"/><line x1="12" y1="17" x2="12" y2="21"/></svg>',
        'merchandise' => '<svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round"><path d="M20.59 13.41l-7.17 7.17a2 2 0 0 1-2.83 0L2 12V2h10l8.59 8.59a2 2 0 0 1 0 2.82z"/><line x1="7" y1="7" x2="7.01" y2="7"/></svg>',
    ];
    return $icons[$type] ?? $icons['procurement'];
}

// Build current URL params for pagination links (without 'page')
function paginationUrl(array $extra = []): string {
    $params = array_merge($_GET, $extra);
    unset($params['page']);
    $qs = http_build_query(array_filter($params));
    return BASE_URL . '/approvals/queue.php?' . $qs . ($qs ? '&' : '');
}

$pageTitle = 'Approval Queue';
include __DIR__ . '/../includes/header.php';
?>

<div class="page-wrap">

  <!-- Page header -->
  <div class="page-header">
    <h1 class="page-title">
      Approval Queue
      <?php if ($total > 0): ?>
        <span style="font-size:14px;font-weight:400;color:var(--text-muted);margin-left:8px">
          <?= $total ?> waiting
        </span>
      <?php endif; ?>
    </h1>
    <span style="font-size:13px;color:var(--text-muted)">
      Your level: <strong><?= e(approvalLevelLabel($userLevel)) ?></strong>
    </span>
  </div>

  <?php renderFlash(); ?>

  <!-- Filter bar (matches Balsamiq: search + Type + Status/Priority + Date Range) -->
  <form method="GET" action="" class="filter-bar" role="search">

    <div class="filter-search">
      <span class="search-icon" aria-hidden="true">
        <svg width="14" height="14" viewBox="0 0 24 24" fill="none"
             stroke="currentColor" stroke-width="2" stroke-linecap="round">
          <circle cx="11" cy="11" r="8"/><line x1="21" y1="21" x2="16.65" y2="16.65"/>
        </svg>
      </span>
      <input type="search" name="q" placeholder="Search by ID, name or description…"
             value="<?= e($search) ?>" aria-label="Search requisitions">
    </div>

    <select name="type" aria-label="Filter by type" onchange="this.form.submit()">
      <option value="">All Types</option>
      <?php foreach (REQUISITION_TYPES as $key => $label): ?>
        <option value="<?= e($key) ?>" <?= $filterType === $key ? 'selected' : '' ?>>
          <?= e($label) ?>
        </option>
      <?php endforeach; ?>
    </select>

    <select name="priority" aria-label="Filter by priority" onchange="this.form.submit()">
      <option value="">All Priorities</option>
      <option value="high"   <?= $filterPriority === 'high'   ? 'selected' : '' ?>>High</option>
      <option value="medium" <?= $filterPriority === 'medium' ? 'selected' : '' ?>>Medium</option>
      <option value="low"    <?= $filterPriority === 'low'    ? 'selected' : '' ?>>Low</option>
    </select>

    <input type="date" name="date_from" value="<?= e($dateFrom) ?>"
           title="From date" aria-label="Date from">
    <input type="date" name="date_to" value="<?= e($dateTo) ?>"
           title="To date" aria-label="Date to">

    <button type="submit" class="btn btn-outline" style="flex-shrink:0">Filter</button>

    <?php if ($search || $filterType || $filterPriority || $dateFrom || $dateTo): ?>
      <a href="<?= BASE_URL ?>/approvals/queue.php" class="btn btn-outline" style="flex-shrink:0">
        Clear
      </a>
    <?php endif; ?>

  </form>

  <!-- Queue list -->
  <?php if (empty($reqs)): ?>

    <div class="empty-state">
      <div class="empty-icon" aria-hidden="true">✅</div>
      <p>
        <?php if ($search || $filterType || $filterPriority): ?>
          No requisitions match your filters.
        <?php else: ?>
          Your approval queue is empty — nothing is waiting for your action right now.
        <?php endif; ?>
      </p>
    </div>

  <?php else: ?>

    <div class="queue-list" role="list">

      <?php foreach ($reqs as $req):
        $reqId     = (int)$req['id'];
        $isHighlight = ($highlight === $reqId);
      ?>

      <article
        class="queue-card"
        id="req-<?= $reqId ?>"
        role="listitem"
        <?= $isHighlight ? 'style="border-color:var(--brand);box-shadow:0 0 0 2px rgba(192,57,43,.15)"' : '' ?>
      >

        <!-- Header row: icon + id + type + priority badge -->
        <div class="queue-card-header">
          <div class="queue-card-title">
            <span class="type-icon" aria-hidden="true"><?= typeIcon($req['type']) ?></span>
            <span class="req-id"><?= e($req['req_number']) ?></span>
            <span class="req-type">
              <?= e(REQUISITION_TYPES[$req['type']] ?? ucfirst($req['type'])) ?> Requisition
            </span>
          </div>
          <?= priorityBadge($req['priority']) ?>
        </div>

        <!-- Meta: who, when, what -->
        <div class="queue-card-meta">
          Submitted by: <strong><?= e($req['submitter_name'] ?? '—') ?></strong>
          (<?= e($req['dept_name'] ?? '—') ?>)
          &nbsp;·&nbsp;
          Date: <?= e(formatDate($req['created_at'])) ?>
          <?php
            // Build a short description snippet
            if ($req['type'] === 'personnel' && !empty($req['justification'])) {
              $snippet = mb_strimwidth($req['justification'], 0, 80, '…');
            } elseif ($req['item_count'] > 0) {
              $snippet = e($req['first_item']);
              if ($req['item_count'] > 1) $snippet .= ' + ' . ($req['item_count'] - 1) . ' more item(s)';
              if ($req['total_amount'] > 0) $snippet .= ' — ' . formatKES((float)$req['total_amount']);
            } else {
              $snippet = mb_strimwidth($req['justification'] ?? '', 0, 80, '…');
            }
          ?>
          <?php if ($snippet): ?>
            <div class="desc"><?= e($snippet) ?></div>
          <?php endif; ?>
        </div>

        <!-- Approval progress dots (4 levels) -->
        <div class="approval-progress" aria-label="Approval progress: level <?= $req['current_approval_level'] ?> of 4">
          <?php for ($lvl = 1; $lvl <= 4; $lvl++): ?>
            <?php
              $dotClass = 'waiting';
              if ($lvl < $req['current_approval_level'])   $dotClass = 'done';
              if ($lvl === $req['current_approval_level']) $dotClass = 'current';
            ?>
            <div class="ap-dot <?= $dotClass ?>" title="<?= e(approvalLevelLabel($lvl)) ?>"></div>
            <?php if ($lvl < 4): ?>
              <div class="ap-line <?= $lvl < $req['current_approval_level'] ? 'done' : '' ?>"></div>
            <?php endif; ?>
          <?php endfor; ?>
          <span class="ap-label">
            Level <?= $req['current_approval_level'] ?> of 4
            — <?= e(approvalLevelLabel($req['current_approval_level'])) ?>
          </span>
        </div>

        <!-- Action buttons -->
        <div class="queue-card-actions">

          <a href="<?= BASE_URL ?>/requisitions/view.php?id=<?= $reqId ?>"
             class="btn btn-outline btn-sm">
            <svg width="13" height="13" viewBox="0 0 24 24" fill="none"
                 stroke="currentColor" stroke-width="2" stroke-linecap="round" aria-hidden="true">
              <path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"/>
              <circle cx="12" cy="12" r="3"/>
            </svg>
            View Details
          </a>

          <!-- Approve -->
          <form method="POST" action="<?= BASE_URL ?>/approvals/action.php"
                onsubmit="return confirmApprove(event, '<?= e($req['req_number']) ?>')">
            <input type="hidden" name="requisition_id" value="<?= $reqId ?>">
            <input type="hidden" name="action" value="approve">
            <input type="hidden" name="comments" value="">
            <button type="submit" class="btn btn-dark btn-sm">
              <svg width="13" height="13" viewBox="0 0 24 24" fill="none"
                   stroke="currentColor" stroke-width="2.5" stroke-linecap="round" aria-hidden="true">
                <polyline points="20 6 9 17 4 12"/>
              </svg>
              Approve
            </button>
          </form>

          <!-- Reject (opens inline modal) -->
          <button type="button" class="btn btn-outline btn-sm"
                  style="color:var(--brand);border-color:var(--brand)"
                  onclick="openRejectModal(<?= $reqId ?>, '<?= e($req['req_number']) ?>')">
            <svg width="13" height="13" viewBox="0 0 24 24" fill="none"
                 stroke="currentColor" stroke-width="2.5" stroke-linecap="round" aria-hidden="true">
              <circle cx="12" cy="12" r="10"/>
              <line x1="15" y1="9" x2="9" y2="15"/><line x1="9" y1="9" x2="15" y2="15"/>
            </svg>
            Reject
          </button>

        </div><!-- /queue-card-actions -->

      </article>

      <?php endforeach; ?>

    </div><!-- /queue-list -->

    <!-- Pagination -->
    <?php if ($totalPages > 1): ?>
      <div style="display:flex;align-items:center;justify-content:center;gap:8px;margin-top:28px">
        <?php if ($page > 1): ?>
          <a href="<?= paginationUrl() ?>page=<?= $page - 1 ?>" class="btn btn-outline btn-sm">← Prev</a>
        <?php endif; ?>

        <span style="font-size:13px;color:var(--text-muted)">
          Page <?= $page ?> of <?= $totalPages ?>
        </span>

        <?php if ($page < $totalPages): ?>
          <a href="<?= paginationUrl() ?>page=<?= $page + 1 ?>" class="btn btn-outline btn-sm">Next →</a>
        <?php endif; ?>
      </div>
    <?php elseif ($total > 0): ?>
      <p class="queue-end">All <?= $total ?> item<?= $total !== 1 ? 's' : '' ?> shown.</p>
    <?php endif; ?>

  <?php endif; ?>

</div><!-- /page-wrap -->


<!-- ── Reject modal ─────────────────────────────────────────────────── -->
<div class="reject-overlay" id="reject-overlay" role="dialog"
     aria-modal="true" aria-labelledby="reject-modal-title">
  <div class="reject-modal">
    <h3 id="reject-modal-title">Reject <span id="modal-req-num"></span></h3>
    <form method="POST" action="<?= BASE_URL ?>/approvals/action.php" id="reject-form">
      <input type="hidden" name="requisition_id" id="modal-req-id">
      <input type="hidden" name="action" value="reject">
      <label for="reject-comments" style="font-size:13px;color:var(--text-muted);display:block;margin-bottom:6px">
        Reason for rejection <span style="color:var(--brand)">*</span>
      </label>
      <textarea name="comments" id="reject-comments" rows="4"
                placeholder="Provide a clear reason so the requester knows what to change…"
                required></textarea>
      <div class="modal-actions">
        <button type="button" class="btn btn-outline btn-sm"
                onclick="closeRejectModal()">Cancel</button>
        <button type="submit" class="btn btn-danger btn-sm">Confirm Rejection</button>
      </div>
    </form>
  </div>
</div>


<script>
// ── Approve confirmation ──────────────────────────────────────────────────
function confirmApprove(e, reqNum) {
  return confirm('Approve ' + reqNum + '?\n\nThis will forward it to the next approval level.');
}

// ── Reject modal ──────────────────────────────────────────────────────────
function openRejectModal(reqId, reqNum) {
  document.getElementById('modal-req-id').value  = reqId;
  document.getElementById('modal-req-num').textContent = reqNum;
  document.getElementById('reject-comments').value = '';
  document.getElementById('reject-overlay').classList.add('open');
  document.getElementById('reject-comments').focus();
}

function closeRejectModal() {
  document.getElementById('reject-overlay').classList.remove('open');
}

// Close overlay when clicking outside the modal box
document.getElementById('reject-overlay').addEventListener('click', function(e) {
  if (e.target === this) closeRejectModal();
});

// Close on Escape key
document.addEventListener('keydown', function(e) {
  if (e.key === 'Escape') closeRejectModal();
});

// ── Scroll to highlighted card after redirect ─────────────────────────────
<?php if ($highlight): ?>
const highlighted = document.getElementById('req-<?= $highlight ?>');
if (highlighted) {
  highlighted.scrollIntoView({ behavior: 'smooth', block: 'center' });
}
<?php endif; ?>
</script>

<?php include __DIR__ . '/../includes/footer.php'; ?>