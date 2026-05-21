<?php
// requisitions/view.php — full detail view matching Balsamiq Screen 5
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../includes/auth.php';

requireLogin();

$user      = currentUser();
$userRole  = $user['role_name'] ?? '';
$userLevel = getRoleLevel($user);
$reqId     = (int)(get('id'));

if (!$reqId) {
    setFlash('error', 'No requisition specified.');
    redirect(BASE_URL . '/dashboard.php');
}

$req = fetchOne(
    "SELECT r.*, u.full_name AS submitter_name, u.email AS submitter_email,
            u.section AS submitter_section,
            d.department_name AS dept_name
       FROM requisitions r
       LEFT JOIN users u ON u.user_id = r.requester_id
       LEFT JOIN departments d ON d.department_id = r.department_id
      WHERE r.requisition_id = ?",
    [$reqId]
);

if (!$req) {
    setFlash('error', 'Requisition not found.');
    redirect(BASE_URL . '/dashboard.php');
}

$isOwner    = (int)$req['requester_id'] === (int)$user['user_id'];
$isApprover = $userLevel > 0;
$isAdmin    = strtolower($userRole) === 'system admin';

if (!$isOwner && !$isApprover && !$isAdmin) {
    setFlash('error', 'You do not have permission to view this requisition.');
    redirect(BASE_URL . '/dashboard.php');
}

$items = fetchAll(
    "SELECT * FROM requisition_items WHERE requisition_id = ? ORDER BY item_id",
    [$reqId]
);

$approvalHistory = fetchAll(
    "SELECT ah.*, u.full_name AS approver_name, r.role_name AS approver_role
       FROM approval_history ah
       LEFT JOIN users u ON u.user_id = ah.approver_id
       LEFT JOIN roles r ON r.role_id = u.role_id
      WHERE ah.requisition_id = ?
      ORDER BY ah.level_id ASC, ah.approval_id ASC",
    [$reqId]
);

$approvalByLevel = [];
foreach ($approvalHistory as $ah) {
    $approvalByLevel[(int)$ah['level_id']] = $ah;
}

$canDecide = (
    $req['current_status'] === 'pending'
    && $userLevel > 0
    && (int)$req['current_approval_level'] === $userLevel
);

// Handle inline approve/reject POST
if ($_SERVER['REQUEST_METHOD'] === 'POST' && $canDecide) {
    $action   = post('action');
    $comments = post('comments');

    if ($action === 'reject' && empty(trim($comments))) {
        setFlash('error', 'Please provide a reason for rejecting.');
        redirect(BASE_URL . '/requisitions/view.php?id=' . $reqId);
    }

    if (in_array($action, ['approve', 'reject'], true)) {
        processApprovalDecision($action, $reqId, (int)$user['id'], $comments);
        $nextLvl = (int)$req['current_approval_level'] + 1;
        $msg = $action === 'approve'
            ? ($nextLvl > 4 ? $req['requisition_number'] . ' fully approved.' : $req['requisition_number'] . ' approved — forwarded to ' . approvalLevelLabel($nextLvl) . '.')
            : $req['requisition_number'] . ' has been rejected.';
        setFlash('success', $msg);
        redirect(BASE_URL . '/requisitions/view.php?id=' . $reqId);
    }
}

// Build track data — load the chain to know which levels are skipped
$chain = buildApprovalChain(
    $req['requisition_type'],
    (int)$req['requester_id'],
    (int)$req['department_id']
);

$trackSteps = [];
foreach ($chain as $step_c) {
    $lvl = $step_c['level'];
    $ah  = $approvalByLevel[$lvl] ?? null;

    if ($step_c['skipped']) {
        $status = 'skipped';
    } elseif ($ah && $ah['decision'] === 'approved') {
        $status = 'done';
    } elseif ($ah && $ah['decision'] === 'rejected') {
        $status = 'rejected';
    } elseif ($lvl === (int)$req['current_approval_level'] && $req['current_status'] === 'pending') {
        $status = 'current';
    } else {
        $status = 'waiting';
    }

    $trackSteps[$lvl] = [
        'label'          => $step_c['label'],
        'current_status' => $status,
        'approver'       => $ah['approver_name'] ?? null,
        'date'           => $ah['decision_date'] ?? null,
        'comment'        => $ah['comments']      ?? null,
        'skip_reason'    => $step_c['skip_reason'] ?? '',
    ];
}

$historyWithComments = array_filter($approvalHistory, fn($ah) => !empty(trim($ah['comments'] ?? '')));

$pageTitle = ($req['requisition_number'] ?? 'REQ') . ' Details';
include __DIR__ . '/../includes/header.php';
?>
<div class="page-wrap">
<?php renderFlash(); ?>

<!-- Top bar -->
<div class="detail-topbar">
  <button class="back-btn" onclick="history.back()">
    <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round"><line x1="19" y1="12" x2="5" y2="12"/><polyline points="12 19 5 12 12 5"/></svg>
    Back
  </button>
  <h1><?= e($req['requisition_number']) ?> Details</h1>
  <?php if ($isOwner && $req['current_status'] === 'pending'): ?>
  <div class="actions-menu">
    <button class="actions-menu-btn" onclick="document.getElementById('actions-dropdown').classList.toggle('open')">
      <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round"><circle cx="12" cy="5" r="1"/><circle cx="12" cy="12" r="1"/><circle cx="12" cy="19" r="1"/></svg>
      Actions
    </button>
    <div class="actions-dropdown" id="actions-dropdown">
      <form method="POST" action="<?= BASE_URL ?>/api/cancel_requisition.php"
            onsubmit="return confirm('Cancel <?= e($req['requisition_number']) ?>?')">
        <input type="hidden" name="requisition_id" value="<?= $reqId ?>">
        <button type="submit" class="danger">Cancel Requisition</button>
      </form>
    </div>
  </div>
  <?php endif; ?>
</div>

<!-- Two-column layout -->
<div class="detail-layout">

  <!-- LEFT -->
  <div>
    <div class="detail-card">

      <!-- Status strip -->
      <div class="detail-section" style="display:flex;align-items:center;gap:14px;flex-wrap:wrap;padding:16px 26px">
        <?= statusBadge($req['current_status']) ?>
        <span style="color:var(--border)">|</span>
        <span style="font-size:13px;color:var(--text-muted)">Type: <strong><?= e(REQUISITION_TYPES[$req['requisition_type']] ?? ucfirst($req['requisition_type'])) ?></strong></span>
        <span style="color:var(--border)">|</span>
        <?= priorityBadge($req['priority'] ?? 'medium') ?>
        <span style="color:var(--border)">|</span>
        <span style="font-size:13px;color:var(--text-muted)">Required: <strong><?= e(formatDate($req['date_required'])) ?></strong></span>
      </div>

      <!-- Meta grid -->
      <div class="detail-section">
        <h2 class="detail-section-title">Requisition details</h2>
        <div class="detail-meta-grid">
          <div class="detail-field"><span class="df-label">Department</span><span class="df-value"><?= e($req['dept_name'] ?? '—') ?></span></div>
          <div class="detail-field"><span class="df-label">Section</span><span class="df-value"><?= e($req['submitter_section'] ?? '—') ?></span></div>
          <div class="detail-field"><span class="df-label">Submitted by</span><span class="df-value"><?= e($req['submitter_name'] ?? '—') ?></span></div>
          <div class="detail-field"><span class="df-label">Date submitted</span><span class="df-value"><?= e(formatDate($req['created_at'])) ?></span></div>
          <div class="detail-field"><span class="df-label">Date required</span><span class="df-value"><?= e(formatDate($req['date_required'])) ?></span></div>
          <?php if ($req['total_amount'] > 0): ?>
          <div class="detail-field"><span class="df-label">Total value</span><span class="df-value"><?= formatKES((float)$req['total_amount']) ?></span></div>
          <?php endif; ?>
        </div>
      </div>

      <!-- Items -->
      <?php if (!empty($items)): ?>
      <div class="detail-section">
        <h2 class="detail-section-title">Items</h2>
        <table class="detail-items-table">
          <thead><tr><th>Item</th><th style="text-align:center">Qty</th><th style="text-align:right">Unit Price</th><th style="text-align:right">Subtotal</th></tr></thead>
          <tbody>
            <?php foreach ($items as $it): ?>
            <tr>
              <td><?= e($it['item_description']) ?></td>
              <td style="text-align:center"><?= (int)$it['quantity'] ?></td>
              <td style="text-align:right"><?= formatKES((float)$it['unit_cost']) ?></td>
              <td style="text-align:right"><?= formatKES((float)$it['unit_cost'] * (int)$it['quantity']) ?></td>
            </tr>
            <?php endforeach; ?>
          </tbody>
          <tfoot><tr><td colspan="3" style="text-align:right;padding:12px">Total</td><td style="text-align:right;padding:12px"><?= formatKES((float)$req['total_amount']) ?></td></tr></tfoot>
        </table>
      </div>
      <?php endif; ?>

      <!-- description -->
      <div class="detail-section">
        <h2 class="detail-section-title">Description</h2>
        <p style="font-size:14px;line-height:1.8"><?= nl2br(e($req['description'] ?? '—')) ?></p>
      </div>

      <!-- Comments history -->
      <?php if (!empty($historyWithComments)): ?>
      <div class="detail-section">
        <h2 class="detail-section-title">Comments history</h2>
        <div class="comment-list">
          <?php foreach ($historyWithComments as $c): ?>
          <div class="comment-item">
            <div class="comment-header">
              <span class="comment-author"><?= e($c['approver_name'] ?? '—') ?></span>
              <span class="comment-date"><?= e(formatDate($c['decision_date'], 'd/m/Y H:i')) ?></span>
            </div>
            <div class="comment-body">"<?= nl2br(e($c['comments'])) ?>"</div>
          </div>
          <?php endforeach; ?>
        </div>
      </div>
      <?php endif; ?>

    </div>
  </div><!-- /left -->

  <!-- RIGHT -->
  <div>

    <!-- Approval tracker -->
    <div class="sidebar-card">
      <div class="sidebar-card-header">
        <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round"><polyline points="9 11 12 14 22 4"/><path d="M21 12v7a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h11"/></svg>
        Approval progress
      </div>
      <div class="sidebar-card-body">
        <div class="approval-track">
          <?php foreach ($trackSteps as $lvl => $step):
            $isSkipped  = $step['current_status'] === 'skipped';
            $isRejected = $step['current_status'] === 'rejected';
            $dotIcon = match($step['current_status']) {
                'done'    => '✓',
                'rejected'=> '✕',
                'current' => '→',
                'skipped' => '—',
                default   => '',
            };
            $dotCls = match($step['current_status']) {
                'done','rejected' => 'done',
                'current'         => 'current',
                'skipped'         => 'skipped',
                default           => 'waiting',
            };
          ?>
          <div class="approval-step <?= $step['current_status'] === 'done' ? 'done' : '' ?>">
            <div class="ap-step-dot <?= $dotCls ?>"
                 <?= $isRejected ? 'style="background:var(--brand);border-color:var(--brand)"' : '' ?>
                 <?= $isSkipped  ? 'style="background:var(--bg);border-color:var(--border);color:var(--text-muted)"' : '' ?>>
              <?= $dotIcon ?>
            </div>
            <div class="ap-step-info">
              <div class="ap-step-role <?= $dotCls ?>" <?= $isSkipped ? 'style="color:var(--text-muted)"' : '' ?>>
                <?= e($step['label']) ?>
                <?php if ($step['approver'] && in_array($step['current_status'], ['done','rejected'])): ?>
                  <span style="font-weight:400;color:var(--text-muted)"> — <?= e($step['approver']) ?></span>
                <?php endif; ?>
                <?php if ($step['current_status'] === 'current'): ?>
                  <span style="font-weight:400;font-size:11px;color:var(--orange-text)"> (Pending)</span>
                <?php endif; ?>
              </div>
              <?php if ($isSkipped): ?>
                <div class="ap-step-meta" style="color:var(--text-muted);font-style:italic">
                  Skipped — <?= e($step['skip_reason']) ?>
                </div>
              <?php elseif ($step['date']): ?>
                <div class="ap-step-meta"><?= e(formatDate($step['date'], 'd/m/Y H:i')) ?></div>
              <?php elseif ($step['current_status'] === 'waiting'): ?>
                <div class="ap-step-meta">Awaiting previous approval</div>
              <?php endif; ?>
              <?php if ($step['comment']): ?>
                <div class="ap-step-comment"><?= e(mb_strimwidth($step['comment'], 0, 120, '…')) ?></div>
              <?php endif; ?>
            </div>
          </div>
          <?php endforeach; ?>
        </div>
      </div>

      <!-- Decision panel -->
      <?php if ($canDecide): ?>
      <div class="decision-panel">
        <div class="decision-panel-title">
          <svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round"><rect x="3" y="11" width="18" height="11" rx="2"/><path d="M7 11V7a5 5 0 0 1 10 0v4"/></svg>
          Approvers only — your decision
        </div>
        <form method="POST" action="">
          <textarea name="comments" placeholder="Add a comment (required when rejecting)…" rows="3"></textarea>
          <div class="decision-btns">
            <button type="submit" name="action" value="approve" class="btn btn-dark"
                    onclick="return confirm('Approve <?= e($req['requisition_number']) ?>?')">
              <svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round"><polyline points="20 6 9 17 4 12"/></svg>
              Approve
            </button>
            <button type="submit" name="action" value="reject" class="btn btn-danger"
                    onclick="return validateReject(this.form)">
              <svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round"><circle cx="12" cy="12" r="10"/><line x1="15" y1="9" x2="9" y2="15"/><line x1="9" y1="9" x2="15" y2="15"/></svg>
              Reject
            </button>
          </div>
        </form>
      </div>
      <?php endif; ?>
    </div>

    <!-- Quick info -->
    <div class="sidebar-card">
      <div class="sidebar-card-header">Quick info</div>
      <div class="sidebar-card-body" style="display:flex;flex-direction:column;gap:12px">
        <div class="detail-field"><span class="df-label">Requisition ID</span><span class="df-value" style="font-family:monospace"><?= e($req['requisition_number']) ?></span></div>
        <div class="detail-field"><span class="df-label">Current level</span>
          <span class="df-value"><?= $req['current_status']==='pending' ? 'Level '.(int)$req['current_approval_level'].' — '.e(approvalLevelLabel((int)$req['current_approval_level'])) : ucfirst($req['current_status']) ?></span>
        </div>
        <div class="detail-field"><span class="df-label">Last updated</span><span class="df-value"><?= e(timeAgo($req['updated_at'] ?? $req['created_at'])) ?></span></div>
        <?php if ($req['total_amount'] > 0): ?>
        <div class="detail-field"><span class="df-label">Total value</span><span class="df-value"><?= formatKES((float)$req['total_amount']) ?></span></div>
        <?php endif; ?>
      </div>
    </div>

  </div><!-- /right -->
</div><!-- /detail-layout -->
</div><!-- /page-wrap -->

<script>
function validateReject(form) {
  if (!form.querySelector('textarea[name="comments"]').value.trim()) {
    alert('Please enter a reason before rejecting.');
    form.querySelector('textarea').focus();
    return false;
  }
  return confirm('Reject <?= e($req['requisition_number']) ?>? This will notify the requester.');
}
document.addEventListener('click', function(e) {
  const menu = document.querySelector('.actions-menu');
  if (menu && !menu.contains(e.target))
    document.getElementById('actions-dropdown')?.classList.remove('open');
});
</script>
<?php include __DIR__ . '/../includes/footer.php'; ?>