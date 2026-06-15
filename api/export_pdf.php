<?php
// api/export_pdf.php
// Generates a print-ready HTML summary of any requisition.

require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../includes/auth.php';

requireLogin();

$user  = currentUser();
$reqId = (int)get('id');

if (!$reqId) { die('No requisition specified.'); }

$req = fetchOne(
    "SELECT r.*,
            u.full_name       AS submitter_name,
            u.email           AS submitter_email,
            u.section         AS submitter_section,
            d.department_name AS dept_name,
            fa.full_name      AS final_approver_name
       FROM requisitions r
       LEFT JOIN users u       ON u.user_id       = r.requester_id
       LEFT JOIN departments d ON d.department_id = r.department_id
       LEFT JOIN users fa      ON fa.user_id      = r.final_approver_id
      WHERE r.requisition_id = ?",
    [$reqId]
);

if (!$req) { die('Requisition not found.'); }

$isOwner    = (int)$req['requester_id'] === (int)$user['user_id'];
$isApprover = getRoleLevel($user) > 0;
$isAdmin    = ($user['role_id'] ?? 0) == 1;
if (!$isOwner && !$isApprover && !$isAdmin) { die('Access denied.'); }

$items = fetchAll(
    "SELECT * FROM requisition_items WHERE requisition_id = ? ORDER BY item_id",
    [$reqId]
);

$approvalHistory = fetchAll(
    "SELECT ah.*, u.full_name AS approver_name
       FROM approval_history ah
       LEFT JOIN users u ON u.user_id = ah.approver_id
      WHERE ah.requisition_id = ?
        AND ah.decision IN ('approved','rejected')
      ORDER BY ah.level_id ASC",
    [$reqId]
);

// Build the display chain to get correct labels
$chain = buildApprovalChain(
    $req['requisition_type'],
    (int)$req['requester_id'],
    (int)$req['department_id']
);
$chainLabels = [];
foreach ($chain as $c) {
    $chainLabels[$c['level']] = $c['label'];
}

$statusLabel = ucfirst(str_replace('_', ' ', $req['current_status']));
$typeLabel   = REQUISITION_TYPES[$req['requisition_type']] ?? ucfirst($req['requisition_type']);
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <title><?= e($req['requisition_number']) ?> — Reqon</title>
  <style>
    *, *::before, *::after { box-sizing: border-box; margin: 0; padding: 0; }
    body {
      font-family: 'Segoe UI', Arial, sans-serif;
      font-size: 13px;
      color: #1a1a1a;
      background: #f5f5f5;
      padding: 32px 24px;
    }

    .doc-page {
      background: #fff;
      max-width: 820px;
      margin: 0 auto;
      border: 1px solid #d0d5dd;
      border-radius: 8px;
      overflow: hidden;
    }

    /* Print bar */
    .print-bar {
      display: flex;
      align-items: center;
      justify-content: space-between;
      padding: 12px 24px;
      background: #f0f4f8;
      border-bottom: 1px solid #d0d5dd;
    }
    .print-bar span { font-size: 13px; color: #555; }
    .print-btn {
      background: #4A5568; color: #fff;
      border: none; border-radius: 6px;
      padding: 8px 20px; font-size: 14px;
      font-weight: 600; cursor: pointer;
      display: flex; align-items: center; gap: 7px;
    }
    .print-btn:hover { background: #3a4254; }

    .doc-body { padding: 36px 40px 44px; }

    /* Header */
    .doc-header {
      display: flex;
      justify-content: space-between;
      align-items: flex-start;
      margin-bottom: 24px;
      gap: 20px;
    }
    .doc-org { font-size: 19px; font-weight: 700; color: #C0392B; }
    .doc-org-sub { font-size: 12px; color: #666; margin-top: 3px; }
    .doc-title-right { text-align: right; }
    .doc-title-right h1 { font-size: 18px; font-weight: 700; color: #1a1a1a; }
    .doc-req-num { font-size: 15px; font-weight: 600; color: #C0392B; margin-top: 4px; }

    hr.rule { border: none; border-top: 2px solid #C0392B; margin: 0 0 22px; }

    /* Status pill */
    .status-pill {
      display: inline-block;
      padding: 3px 12px;
      border-radius: 20px;
      font-size: 12px;
      font-weight: 700;
      text-transform: uppercase;
      letter-spacing: .05em;
    }
    .status-pending   { background: #FEF3E2; color: #B7770D; }
    .status-approved  { background: #EBF7EF; color: #1E8449; }
    .status-rejected  { background: #FDECEA; color: #B03A2E; }
    .status-cancelled { background: #f0f0f0; color: #888; }
    .status-in_review { background: #FEF3E2; color: #B7770D; }

    /* Meta grid */
    .meta-grid {
      display: grid;
      grid-template-columns: 1fr 1fr;
      gap: 10px 32px;
      margin-bottom: 22px;
    }
    .meta-row { display: flex; flex-direction: column; gap: 2px; }
    .meta-label { font-size: 10px; font-weight: 700; letter-spacing: .08em; text-transform: uppercase; color: #888; }
    .meta-value { font-size: 13px; font-weight: 500; color: #1a1a1a; }

    /* Section headings */
    .section-title {
      font-size: 10px; font-weight: 700;
      letter-spacing: .08em; text-transform: uppercase;
      color: #888; margin-bottom: 10px; padding-bottom: 5px;
      border-bottom: 1px solid #e8ecf0;
    }
    .section { margin-bottom: 22px; }

    /* Items table */
    .items-tbl { width: 100%; border-collapse: collapse; font-size: 13px; }
    .items-tbl thead tr { background: #4A5568; color: #fff; }
    .items-tbl thead th {
      padding: 9px 12px; text-align: left;
      font-size: 11px; font-weight: 700;
      letter-spacing: .05em; text-transform: uppercase;
    }
    .items-tbl thead th:nth-child(n+2) { text-align: right; }
    .items-tbl tbody tr { border-bottom: 1px solid #e8ecf0; }
    .items-tbl tbody tr:last-child { border-bottom: none; }
    .items-tbl tbody td { padding: 9px 12px; vertical-align: top; }
    .items-tbl tbody td:nth-child(n+2) { text-align: right; }
    .items-tbl tfoot tr { background: #f7f8fa; border-top: 2px solid #d0d5dd; }
    .items-tbl tfoot td { padding: 10px 12px; font-weight: 700; }
    .items-tbl tfoot td:last-child { text-align: right; color: #C0392B; }

    /* Approval history table */
    .hist-tbl { width: 100%; border-collapse: collapse; font-size: 13px; }
    .hist-tbl thead tr { background: #f7f8fa; }
    .hist-tbl thead th {
      padding: 8px 12px; text-align: left;
      font-size: 11px; font-weight: 700;
      letter-spacing: .05em; text-transform: uppercase; color: #666;
      border-bottom: 1px solid #e8ecf0;
    }
    .hist-tbl tbody tr { border-bottom: 1px solid #e8ecf0; }
    .hist-tbl tbody tr:last-child { border-bottom: none; }
    .hist-tbl tbody td { padding: 9px 12px; }
    .decision-approved { color: #1E8449; font-weight: 600; }
    .decision-rejected { color: #B03A2E; font-weight: 600; }

    /* Description */
    .description-box {
      background: #f9fafb;
      border: 1px solid #e8ecf0;
      border-radius: 6px;
      padding: 14px 16px;
      font-size: 13px;
      color: #333;
      line-height: 1.7;
    }

    /* Footer */
    .doc-footer {
      text-align: center;
      font-size: 11px;
      color: #aaa;
      padding-top: 16px;
      border-top: 1px solid #e8ecf0;
      margin-top: 8px;
    }

    /* Print */
    @media print {
      body { background: #fff; padding: 0; font-size: 12px; }
      .print-bar { display: none !important; }
      .doc-page { border: none; border-radius: 0; max-width: 100%; }
      .doc-body { padding: 20px 24px 32px; }
      @page { margin: 15mm 12mm; size: A4; }
    }
  </style>
</head>
<body>

<div class="doc-page">

  <!-- Print bar -->
  <div class="print-bar">
    <span><?= e($req['requisition_number']) ?> · <?= e($typeLabel) ?> · <?= e($statusLabel) ?></span>
    <button class="print-btn" onclick="window.print()">
      <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round">
        <polyline points="6 9 6 2 18 2 18 9"/><path d="M6 18H4a2 2 0 0 1-2-2v-5a2 2 0 0 1 2-2h16a2 2 0 0 1 2 2v5a2 2 0 0 1-2 2h-2"/>
        <rect x="6" y="14" width="12" height="8"/>
      </svg>
      Print / Save as PDF
    </button>
  </div>

  <div class="doc-body">

    <!-- Header -->
    <div class="doc-header">
      <div>
        <div class="doc-org">Isuzu East Africa Limited</div>
        <div class="doc-org-sub">Requisition Management System — Reqon</div>
      </div>
      <div class="doc-title-right">
        <h1>Requisition Summary</h1>
        <div class="doc-req-num"><?= e($req['requisition_number']) ?></div>
      </div>
    </div>

    <hr class="rule">

    <!-- Status + type strip -->
    <div style="display:flex;align-items:center;gap:12px;margin-bottom:20px;flex-wrap:wrap">
      <span class="status-pill status-<?= e($req['current_status']) ?>"><?= e($statusLabel) ?></span>
      <span style="font-size:13px;color:#555">Type: <strong><?= e($typeLabel) ?></strong></span>
      <?php if (!empty($req['priority'])): ?>
      <span style="font-size:13px;color:#555">Priority: <strong><?= ucfirst(e($req['priority'])) ?></strong></span>
      <?php endif; ?>
    </div>

    <!-- Meta -->
    <div class="meta-grid">
      <div class="meta-row"><span class="meta-label">Requisition No.</span><span class="meta-value"><?= e($req['requisition_number']) ?></span></div>
      <div class="meta-row"><span class="meta-label">Submitted</span><span class="meta-value"><?= e(formatDate($req['submission_date'] ?? $req['created_at'])) ?></span></div>
      <div class="meta-row"><span class="meta-label">Department</span><span class="meta-value"><?= e($req['dept_name'] ?? '—') ?></span></div>
      <div class="meta-row"><span class="meta-label">Date Required</span><span class="meta-value"><?= e(formatDate($req['date_required'])) ?></span></div>
      <div class="meta-row"><span class="meta-label">Submitted By</span><span class="meta-value"><?= e($req['submitter_name'] ?? '—') ?></span></div>
      <?php if ($req['total_amount'] > 0): ?>
      <div class="meta-row"><span class="meta-label">Total Value</span><span class="meta-value">KES <?= number_format((float)$req['total_amount'], 2) ?></span></div>
      <?php endif; ?>
      <?php if (!empty($req['position_title'])): ?>
      <div class="meta-row"><span class="meta-label">Position Title</span><span class="meta-value"><?= e($req['position_title']) ?></span></div>
      <?php endif; ?>
      <?php if (!empty($req['employment_type'])): ?>
      <div class="meta-row"><span class="meta-label">Employment Type</span><span class="meta-value"><?= ucfirst(e($req['employment_type'])) ?></span></div>
      <?php endif; ?>
      <?php if (!empty($req['budget_code'])): ?>
      <div class="meta-row"><span class="meta-label">Budget Code</span><span class="meta-value"><?= e($req['budget_code']) ?></span></div>
      <?php endif; ?>
      <?php if ($req['current_status'] === 'approved' && !empty($req['final_approver_name'])): ?>
      <div class="meta-row"><span class="meta-label">Final Approver</span><span class="meta-value"><?= e($req['final_approver_name']) ?></span></div>
      <div class="meta-row"><span class="meta-label">Approved On</span><span class="meta-value"><?= e(formatDate($req['final_decision_date'])) ?></span></div>
      <?php endif; ?>
      <?php if ($req['current_status'] === 'rejected' && !empty($req['rejection_reason'])): ?>
      <div class="meta-row" style="grid-column:span 2"><span class="meta-label">Rejection Reason</span><span class="meta-value" style="color:#B03A2E"><?= e($req['rejection_reason']) ?></span></div>
      <?php endif; ?>
    </div>

    <!-- Description -->
    <?php if (!empty($req['description'])): ?>
    <div class="section">
      <div class="section-title">Description</div>
      <div class="description-box"><?= nl2br(e($req['description'])) ?></div>
    </div>
    <?php endif; ?>

    <!-- Items -->
    <?php if (!empty($items)): ?>
    <div class="section">
      <div class="section-title">Items</div>
      <table class="items-tbl">
        <thead>
          <tr>
            <th style="width:5%">#</th>
            <th style="width:50%;text-align:left">Item</th>
            <th style="width:10%">Qty</th>
            <th style="width:17%">Unit Cost</th>
            <th style="width:18%">Subtotal</th>
          </tr>
        </thead>
        <tbody>
          <?php foreach ($items as $i => $it): ?>
          <tr>
            <td><?= $i + 1 ?></td>
            <td style="text-align:left"><?= e($it['item_description']) ?></td>
            <td><?= (int)$it['quantity'] ?></td>
            <td>KES <?= number_format((float)$it['unit_cost'], 2) ?></td>
            <td>KES <?= number_format((float)$it['unit_cost'] * (int)$it['quantity'], 2) ?></td>
          </tr>
          <?php endforeach; ?>
        </tbody>
        <tfoot>
          <tr>
            <td colspan="4" style="text-align:right;font-size:13px;color:#555">Total</td>
            <td>KES <?= number_format((float)$req['total_amount'], 2) ?></td>
          </tr>
        </tfoot>
      </table>
    </div>
    <?php endif; ?>

    <!-- Approval history -->
    <?php if (!empty($approvalHistory)): ?>
    <div class="section">
      <div class="section-title">Approval Trail</div>
      <table class="hist-tbl">
        <thead>
          <tr>
            <th>Level</th>
            <th>Approver</th>
            <th>Decision</th>
            <th>Date</th>
            <th>Comments</th>
          </tr>
        </thead>
        <tbody>
          <?php foreach ($approvalHistory as $ah): ?>
          <tr>
            <td><?= e($chainLabels[(int)$ah['level_id']] ?? 'Level ' . $ah['level_id']) ?></td>
            <td><?= e($ah['approver_name'] ?? '—') ?></td>
            <td class="decision-<?= e($ah['decision']) ?>"><?= ucfirst(e($ah['decision'])) ?></td>
            <td><?= e(formatDate($ah['decision_date'], 'd/m/Y H:i')) ?></td>
            <td style="color:#555;font-size:12px"><?= e($ah['comments'] ?? '—') ?></td>
          </tr>
          <?php endforeach; ?>
        </tbody>
      </table>
    </div>
    <?php endif; ?>

    <!-- Footer -->
    <div class="doc-footer">
      Isuzu East Africa Limited · Reqon Requisition Management System ·
      Exported <?= date('d/m/Y H:i') ?> by <?= e($user['full_name'] ?? '') ?>
    </div>

  </div><!-- /doc-body -->
</div><!-- /doc-page -->

</body>
</html>
