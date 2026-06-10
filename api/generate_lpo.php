<?php
// api/generate_lpo.php
// Generates a printable Local Purchase Order for fully approved
// procurement / it_asset / merchandise requisitions.
// Opens in a new tab; user prints or saves as PDF via the browser.

require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../includes/auth.php';

requireLogin();

$user  = currentUser();
$reqId = (int)get('id');

if (!$reqId) {
    die('No requisition specified.');
}

$req = fetchOne(
    "SELECT r.*,
            u.full_name     AS submitter_name,
            u.email         AS submitter_email,
            d.department_name AS dept_name,
            d.budget_code   AS dept_budget_code,
            fa.full_name    AS final_approver_name
       FROM requisitions r
       LEFT JOIN users u       ON u.user_id       = r.requester_id
       LEFT JOIN departments d ON d.department_id = r.department_id
       LEFT JOIN users fa      ON fa.user_id      = r.final_approver_id
      WHERE r.requisition_id = ?",
    [$reqId]
);

if (!$req) { die('Requisition not found.'); }

// Access control: owner, any approver, or admin
$isOwner    = (int)$req['requester_id'] === (int)$user['user_id'];
$isApprover = getRoleLevel($user) > 0;
$isAdmin    = ($user['role_id'] ?? 0) == 1;
if (!$isOwner && !$isApprover && !$isAdmin) { die('Access denied.'); }

// LPO only makes sense for approved goods-type requisitions
if ($req['current_status'] !== 'approved') {
    die('LPO can only be generated for fully approved requisitions.');
}
if (!in_array($req['requisition_type'], ['procurement', 'it_asset', 'merchandise'])) {
    die('LPO is only applicable to Procurement, IT Asset, and Merchandise requisitions.');
}

$items = fetchAll(
    "SELECT * FROM requisition_items WHERE requisition_id = ? ORDER BY item_id",
    [$reqId]
);

// LPO number derived from requisition number: REQ-001 → LPO-001
$lpoNumber = str_replace('REQ-', 'LPO-', $req['requisition_number']);
$today     = date('d/m/Y');
$deptName  = $req['dept_name'] ?? '—';
$budgetCode = $req['budget_code'] ?? $req['dept_budget_code'] ?? '—';
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <title>LPO <?= e($lpoNumber) ?> — Reqon</title>
  <style>
    /* ── Reset ── */
    *, *::before, *::after { box-sizing: border-box; margin: 0; padding: 0; }
    body {
      font-family: 'Segoe UI', Arial, sans-serif;
      font-size: 13px;
      color: #1a1a1a;
      background: #f5f5f5;
      padding: 32px 24px;
    }

    /* ── Wrapper ── */
    .lpo-page {
      background: #fff;
      max-width: 860px;
      margin: 0 auto;
      border: 1px solid #d0d5dd;
      border-radius: 8px;
      overflow: hidden;
    }

    /* ── Print button bar (hidden on print) ── */
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
      background: #27AE60; color: #fff;
      border: none; border-radius: 6px;
      padding: 8px 20px; font-size: 14px;
      font-weight: 600; cursor: pointer;
      display: flex; align-items: center; gap: 7px;
    }
    .print-btn:hover { background: #229954; }

    /* ── Document body ── */
    .lpo-body { padding: 36px 40px 44px; }

    /* ── Header band ── */
    .lpo-header {
      display: flex;
      align-items: flex-start;
      justify-content: space-between;
      margin-bottom: 28px;
      gap: 20px;
    }
    .lpo-org-name {
      font-size: 20px;
      font-weight: 700;
      color: #C0392B;
      letter-spacing: -0.02em;
    }
    .lpo-org-sub {
      font-size: 12px;
      color: #666;
      margin-top: 3px;
    }
    .lpo-doc-title {
      text-align: right;
    }
    .lpo-doc-title h1 {
      font-size: 22px;
      font-weight: 700;
      color: #1a1a1a;
      letter-spacing: .05em;
      text-transform: uppercase;
    }
    .lpo-doc-title .lpo-num {
      font-size: 14px;
      font-weight: 600;
      color: #C0392B;
      margin-top: 4px;
    }

    /* ── Divider ── */
    hr.doc-rule { border: none; border-top: 2px solid #C0392B; margin: 0 0 24px; }

    /* ── Meta grid ── */
    .lpo-meta {
      display: grid;
      grid-template-columns: 1fr 1fr;
      gap: 12px 32px;
      margin-bottom: 28px;
    }
    .meta-row { display: flex; flex-direction: column; gap: 2px; }
    .meta-label { font-size: 10px; font-weight: 700; letter-spacing: .08em; text-transform: uppercase; color: #888; }
    .meta-value { font-size: 13px; font-weight: 500; color: #1a1a1a; }

    /* ── Items table ── */
    .lpo-items { width: 100%; border-collapse: collapse; margin-bottom: 16px; }
    .lpo-items thead tr {
      background: #C0392B;
      color: #fff;
    }
    .lpo-items thead th {
      padding: 10px 14px;
      text-align: left;
      font-size: 11px;
      font-weight: 700;
      letter-spacing: .06em;
      text-transform: uppercase;
    }
    .lpo-items thead th:last-child,
    .lpo-items thead th:nth-child(3),
    .lpo-items thead th:nth-child(4) { text-align: right; }

    .lpo-items tbody tr { border-bottom: 1px solid #e8ecf0; }
    .lpo-items tbody tr:last-child { border-bottom: none; }
    .lpo-items tbody td {
      padding: 10px 14px;
      font-size: 13px;
      color: #1a1a1a;
      vertical-align: top;
    }
    .lpo-items tbody td:nth-child(2),
    .lpo-items tbody td:nth-child(3),
    .lpo-items tbody td:nth-child(4) { text-align: right; }

    .lpo-items tfoot tr { background: #f7f8fa; border-top: 2px solid #d0d5dd; }
    .lpo-items tfoot td {
      padding: 12px 14px;
      font-weight: 700;
      font-size: 14px;
    }
    .lpo-items tfoot td:last-child { text-align: right; color: #C0392B; }

    /* ── Totals summary ── */
    .lpo-totals {
      display: flex;
      justify-content: flex-end;
      margin-bottom: 28px;
    }
    .totals-box {
      border: 1px solid #e8ecf0;
      border-radius: 6px;
      overflow: hidden;
      min-width: 280px;
    }
    .totals-row {
      display: flex;
      justify-content: space-between;
      padding: 8px 16px;
      font-size: 13px;
      border-bottom: 1px solid #e8ecf0;
    }
    .totals-row:last-child { border-bottom: none; background: #fef9f9; }
    .totals-row .t-label { color: #555; }
    .totals-row .t-value { font-weight: 600; }
    .totals-row.grand .t-label { font-weight: 700; color: #1a1a1a; }
    .totals-row.grand .t-value { font-weight: 700; color: #C0392B; font-size: 15px; }

    /* ── Terms & conditions ── */
    .lpo-terms {
      background: #f7f8fa;
      border: 1px solid #e8ecf0;
      border-radius: 6px;
      padding: 14px 18px;
      margin-bottom: 28px;
    }
    .lpo-terms h4 {
      font-size: 11px; font-weight: 700;
      letter-spacing: .07em; text-transform: uppercase;
      color: #888; margin-bottom: 8px;
    }
    .lpo-terms ol {
      padding-left: 18px;
      display: flex; flex-direction: column; gap: 4px;
    }
    .lpo-terms li { font-size: 12px; color: #444; line-height: 1.5; }

    /* ── Signature block ── */
    .sig-grid {
      display: grid;
      grid-template-columns: 1fr 1fr 1fr;
      gap: 20px;
      margin-bottom: 32px;
    }
    .sig-box { display: flex; flex-direction: column; gap: 6px; }
    .sig-box .sig-label { font-size: 10px; font-weight: 700; letter-spacing: .08em; text-transform: uppercase; color: #888; }
    .sig-box .sig-line { border-bottom: 1px solid #aaa; height: 32px; }
    .sig-box .sig-name { font-size: 12px; color: #555; margin-top: 3px; }

    /* ── Footer ── */
    .lpo-footer {
      text-align: center;
      font-size: 11px;
      color: #aaa;
      padding-top: 16px;
      border-top: 1px solid #e8ecf0;
    }

    /* ── Print styles ── */
    @media print {
      body { background: #fff; padding: 0; font-size: 12px; }
      .print-bar { display: none !important; }
      .lpo-page { border: none; border-radius: 0; max-width: 100%; }
      .lpo-body { padding: 20px 24px 32px; }
      @page { margin: 15mm 12mm; size: A4; }
    }
  </style>
</head>
<body>

<div class="lpo-page">

  <!-- Print bar -->
  <div class="print-bar no-print">
    <span>LPO <?= e($lpoNumber) ?> · <?= e($req['requisition_number']) ?> · <?= e($deptName) ?></span>
    <button class="print-btn" onclick="window.print()">
      <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round">
        <polyline points="6 9 6 2 18 2 18 9"/><path d="M6 18H4a2 2 0 0 1-2-2v-5a2 2 0 0 1 2-2h16a2 2 0 0 1 2 2v5a2 2 0 0 1-2 2h-2"/>
        <rect x="6" y="14" width="12" height="8"/>
      </svg>
      Print / Save as PDF
    </button>
  </div>

  <div class="lpo-body">

    <!-- Header -->
    <div class="lpo-header">
      <div>
        <div class="lpo-org-name">Isuzu East Africa Limited</div>
        <div class="lpo-org-sub">Requisition Management System — Reqon</div>
      </div>
      <div class="lpo-doc-title">
        <h1>Local Purchase Order</h1>
        <div class="lpo-num"><?= e($lpoNumber) ?></div>
      </div>
    </div>

    <hr class="doc-rule">

    <!-- Meta -->
    <div class="lpo-meta">
      <div class="meta-row">
        <span class="meta-label">LPO Number</span>
        <span class="meta-value"><?= e($lpoNumber) ?></span>
      </div>
      <div class="meta-row">
        <span class="meta-label">Issue Date</span>
        <span class="meta-value"><?= $today ?></span>
      </div>
      <div class="meta-row">
        <span class="meta-label">Requisition Reference</span>
        <span class="meta-value"><?= e($req['requisition_number']) ?></span>
      </div>
      <div class="meta-row">
        <span class="meta-label">Date Required</span>
        <span class="meta-value"><?= e(formatDate($req['date_required'])) ?></span>
      </div>
      <div class="meta-row">
        <span class="meta-label">Requesting Department</span>
        <span class="meta-value"><?= e($deptName) ?></span>
      </div>
      <div class="meta-row">
        <span class="meta-label">Budget Code</span>
        <span class="meta-value"><?= e($budgetCode) ?></span>
      </div>
      <div class="meta-row">
        <span class="meta-label">Requested By</span>
        <span class="meta-value"><?= e($req['submitter_name'] ?? '—') ?></span>
      </div>
      <div class="meta-row">
        <span class="meta-label">Final Approver</span>
        <span class="meta-value"><?= e($req['final_approver_name'] ?? '—') ?></span>
      </div>
      <div class="meta-row">
        <span class="meta-label">Approved On</span>
        <span class="meta-value"><?= e(formatDate($req['final_decision_date'])) ?></span>
      </div>
      <div class="meta-row">
        <span class="meta-label">Requisition Type</span>
        <span class="meta-value"><?= e(REQUISITION_TYPES[$req['requisition_type']] ?? ucfirst($req['requisition_type'])) ?></span>
      </div>
    </div>

    <!-- Items table -->
    <table class="lpo-items">
      <thead>
        <tr>
          <th style="width:5%">#</th>
          <th style="width:48%">Description</th>
          <th style="width:10%">Qty</th>
          <th style="width:18%">Unit Cost (KES)</th>
          <th style="width:19%">Total (KES)</th>
        </tr>
      </thead>
      <tbody>
        <?php foreach ($items as $i => $it): ?>
        <tr>
          <td><?= $i + 1 ?></td>
          <td><?= e($it['item_description']) ?><?= !empty($it['specifications']) ? '<br><span style="font-size:11px;color:#777">' . e($it['specifications']) . '</span>' : '' ?></td>
          <td><?= (int)$it['quantity'] ?></td>
          <td><?= number_format((float)$it['unit_cost'], 2) ?></td>
          <td><?= number_format((float)$it['unit_cost'] * (int)$it['quantity'], 2) ?></td>
        </tr>
        <?php endforeach; ?>
      </tbody>
      <tfoot>
        <tr>
          <td colspan="4" style="text-align:right;color:#555;font-size:13px">Total Amount</td>
          <td>KES <?= number_format((float)$req['total_amount'], 2) ?></td>
        </tr>
      </tfoot>
    </table>

    <!-- Totals summary -->
    <div class="lpo-totals">
      <div class="totals-box">
        <div class="totals-row">
          <span class="t-label">Subtotal</span>
          <span class="t-value">KES <?= number_format((float)$req['total_amount'], 2) ?></span>
        </div>
        <div class="totals-row">
          <span class="t-label">VAT (16%)</span>
          <span class="t-value">KES <?= number_format((float)$req['total_amount'] * 0.16, 2) ?></span>
        </div>
        <div class="totals-row grand">
          <span class="t-label">Grand Total (incl. VAT)</span>
          <span class="t-value">KES <?= number_format((float)$req['total_amount'] * 1.16, 2) ?></span>
        </div>
      </div>
    </div>

    <!-- Description / justification -->
    <?php if (!empty($req['description'])): ?>
    <div style="margin-bottom:24px">
      <div style="font-size:10px;font-weight:700;letter-spacing:.08em;text-transform:uppercase;color:#888;margin-bottom:6px">
        Justification / Description
      </div>
      <p style="font-size:13px;color:#444;line-height:1.7"><?= nl2br(e($req['description'])) ?></p>
    </div>
    <?php endif; ?>

    <!-- Terms -->
    <div class="lpo-terms">
      <h4>Terms &amp; Conditions</h4>
      <ol>
        <li>This LPO is valid only when signed by an authorised signatory of Isuzu East Africa Limited.</li>
        <li>All goods must be delivered to the address specified within the required date stated above.</li>
        <li>Invoices must quote this LPO number. Payment will only be processed against a valid invoice.</li>
        <li>Goods not conforming to specification will be returned at the supplier's cost.</li>
        <li>Isuzu EA reserves the right to cancel this order with 48 hours notice if delivery is not met.</li>
      </ol>
    </div>

    <!-- Signatures -->
    <div class="sig-grid">
      <div class="sig-box">
        <span class="sig-label">Prepared By</span>
        <div class="sig-line"></div>
        <span class="sig-name"><?= e($req['submitter_name'] ?? '—') ?><br><?= e($deptName) ?></span>
      </div>
      <div class="sig-box">
        <span class="sig-label">Approved By (MD)</span>
        <div class="sig-line"></div>
        <span class="sig-name"><?= e($req['final_approver_name'] ?? 'Managing Director') ?><br><?= e(formatDate($req['final_decision_date'])) ?></span>
      </div>
      <div class="sig-box">
        <span class="sig-label">Supplier Acknowledgement</span>
        <div class="sig-line"></div>
        <span class="sig-name">Signature &amp; Stamp<br>Date: _______________</span>
      </div>
    </div>

    <!-- Footer -->
    <div class="lpo-footer">
      Isuzu East Africa Limited · Reqon Requisition Management System ·
      Generated <?= date('d/m/Y H:i') ?> by <?= e($user['full_name'] ?? '') ?>
    </div>

  </div><!-- /lpo-body -->
</div><!-- /lpo-page -->

<script>
// Auto-trigger print dialog after a short delay so the page fully renders
window.addEventListener('load', function() {
  // Small delay to let fonts/layout settle
  setTimeout(function() {
    // Only auto-print if ?print=1 is in the URL
    if (new URLSearchParams(window.location.search).get('print') === '1') {
      window.print();
    }
  }, 400);
});
</script>

</body>
</html>
