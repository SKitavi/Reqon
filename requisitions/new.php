<?php
// requisitions/new.php — 3-step wizard with catalog picker + type-specific routing
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../includes/auth.php';

requireLogin();

$user        = currentUser();
$submitterId = (int)$user['user_id'];
$userDeptId  = (int)($user['department_id'] ?? 0);
$isITDept    = ($userDeptId === 1); // only IT dept (dept_id=1) sees IT Asset option

// Available types for this user
$availableTypes = [];
foreach (REQUISITION_TYPES as $k => $v) {
    if ($k === 'it_asset' && !$isITDept) continue;
    $availableTypes[$k] = $v;
}

$departments = fetchAll("SELECT department_id, department_name FROM departments ORDER BY department_name");

// ── Step management ───────────────────────────────────────────────────────
$step = (int)($_SESSION['req_form']['step'] ?? 1);

if (isset($_POST['action']) && $_POST['action'] === 'back') {
    $_SESSION['req_form']['step'] = max(1, $step - 1);
    header('Location: ' . BASE_URL . '/requisitions/new.php');
    exit;
}
if (isset($_POST['action']) && $_POST['action'] === 'cancel') {
    unset($_SESSION['req_form']);
    redirect(BASE_URL . '/dashboard.php');
}

$errors = [];

// ══════════════════════════════════════════════════════════
// STEP 1 POST
// ══════════════════════════════════════════════════════════
if ($_SERVER['REQUEST_METHOD'] === 'POST' && $step === 1) {
    $type     = post('type');
    $deptId   = (int)post('department_id');
    $dateReq  = post('date_required');
    $priority = post('priority', 'medium');
    $title    = post('title');

    if (!$type || !array_key_exists($type, $availableTypes)) {
        $errors[] = 'Please select a valid requisition type.';
    }
    if (!$deptId) $errors[] = 'Please select a department.';
    if (!$dateReq) {
        $errors[] = 'Please provide the date required.';
    } elseif (strtotime($dateReq) <= strtotime(date('Y-m-d'))) {
        $errors[] = 'Date required must be at least tomorrow.';
    }
    if ($type === 'personnel' && !$title) {
        $errors[] = 'Position title is required for personnel requisitions.';
    }

    if (empty($errors)) {
        $_SESSION['req_form'] = array_merge($_SESSION['req_form'] ?? [], [
            'step'          => 2,
            'type'          => $type,
            'department_id' => $deptId,
            'date_required' => $dateReq,
            'priority'      => $priority,
            'title'         => $title,
        ]);
        redirect(BASE_URL . '/requisitions/new.php');
    }
    $step = 1;
}

// ══════════════════════════════════════════════════════════
// STEP 2 POST
// ══════════════════════════════════════════════════════════
if ($_SERVER['REQUEST_METHOD'] === 'POST' && $step === 2) {
    $description = post('description');
    $type        = $_SESSION['req_form']['type'] ?? '';

    if (!$description) $errors[] = 'Please provide a description.';

    $items       = [];
    $totalAmount = 0;

    if (in_array($type, ['procurement', 'it_asset', 'merchandise', 'personnel'])) {
        $itemDescs  = $_POST['item_description'] ?? [];
        $quantities = $_POST['quantity']         ?? [];
        $prices     = $_POST['unit_cost']        ?? [];
        $catalogIds = $_POST['catalog_id']       ?? [];
        $isCustoms  = $_POST['is_custom']        ?? [];

        foreach ($itemDescs as $i => $name) {
            $name = trim($name);
            if (!$name) continue;
            $qty        = max(1, (int)($quantities[$i] ?? 1));
            $price      = max(0, (float)($prices[$i] ?? 0));
            $subtotal   = $qty * $price;
            $totalAmount += $subtotal;
            $items[] = [
                'item_description' => $name,
                'quantity'         => $qty,
                'unit_cost'        => $price,
                'subtotal'         => $subtotal,
                'catalog_id'       => ($catalogIds[$i] ?? '') !== '' ? (int)$catalogIds[$i] : null,
                'is_custom'        => (int)($isCustoms[$i] ?? 0),
            ];
        }
        if (empty($items)) $errors[] = 'Please add at least one item.';
    }

    $employmentType = post('employment_type');

    if (empty($errors)) {
        $_SESSION['req_form'] = array_merge($_SESSION['req_form'], [
            'step'            => 3,
            'description'     => $description,
            'items'           => $items,
            'total_amount'    => $totalAmount,
            'employment_type' => $employmentType,
        ]);
        redirect(BASE_URL . '/requisitions/new.php');
    }
    $step = 2;
}

// ══════════════════════════════════════════════════════════
// STEP 3 POST — final submit
// ══════════════════════════════════════════════════════════
if ($_SERVER['REQUEST_METHOD'] === 'POST' && $step === 3) {
    $form = $_SESSION['req_form'] ?? [];
    if (empty($form['type'])) {
        unset($_SESSION['req_form']);
        redirect(BASE_URL . '/requisitions/new.php');
    }

    try {
        $reqId = submitRequisition($form, $user);
        $reqNumber = fetchOne("SELECT requisition_number FROM requisitions WHERE requisition_id = ?", [$reqId])['requisition_number'] ?? '';
        unset($_SESSION['req_form']);
        setFlash('success', "Requisition {$reqNumber} submitted successfully.");
        redirect(BASE_URL . '/requisitions/view.php?id=' . $reqId);
    } catch (Exception $e) {
        $errors[] = 'Something went wrong. Please try again.';
        if (defined('APP_DEBUG') && APP_DEBUG) $errors[] = $e->getMessage();
        $step = 3;
    }
}

// ── Pre-fill data ─────────────────────────────────────────────────────────
$form = $_SESSION['req_form'] ?? [];

$deptName = '';
if (!empty($form['department_id'])) {
    $d = fetchOne("SELECT department_name FROM departments WHERE department_id = ?", [$form['department_id']]);
    $deptName = $d['department_name'] ?? '';
}

// Load catalog for step 2
$catalogItems = [];
if (!empty($form['type'])) {
    $catalogItems = fetchAll(
        "SELECT catalog_id, item_name, standard_unit_cost, unit_label
           FROM item_catalog
          WHERE category = ? AND is_active = 1
          ORDER BY item_name",
        [$form['type']]
    );
}

// Preview the approval chain for step 3
$chainPreview = [];
if (!empty($form['type']) && !empty($form['department_id'])) {
    $chainPreview = buildApprovalChain($form['type'], $submitterId, (int)$form['department_id']);
}

$pageTitle = 'New Requisition';
include __DIR__ . '/../includes/header.php';
?>
<div class="page-wrap">

  <nav class="breadcrumb" aria-label="Breadcrumb">
    <a href="<?= BASE_URL ?>/dashboard.php">Dashboard</a>
    <span class="sep">›</span>
    <span class="current">New Requisition</span>
  </nav>

  <h1 class="page-title" style="margin-bottom:24px">Create New Requisition</h1>

  <div class="form-card">

    <!-- Step progress -->
    <div class="step-progress" role="list" aria-label="Form progress">
      <div class="step-item" role="listitem">
        <div class="step-dot <?= $step >= 1 ? ($step > 1 ? 'done' : 'active') : '' ?>"></div>
        <span class="step-label <?= $step === 1 ? 'active' : '' ?>">Type &amp; Details</span>
      </div>
      <div class="step-line <?= $step > 1 ? 'done' : '' ?>"></div>
      <div class="step-item" role="listitem">
        <div class="step-dot <?= $step >= 2 ? ($step > 2 ? 'done' : 'active') : '' ?>"></div>
        <span class="step-label <?= $step === 2 ? 'active' : '' ?>">Items &amp; Details</span>
      </div>
      <div class="step-line <?= $step > 2 ? 'done' : '' ?>"></div>
      <div class="step-item" role="listitem">
        <div class="step-dot <?= $step === 3 ? 'active' : '' ?>"></div>
        <span class="step-label <?= $step === 3 ? 'active' : '' ?>">Review &amp; Submit</span>
      </div>
    </div>

    <p class="step-counter">Step <?= $step ?> of 3
      <span class="step-counter-hint"><?= e($availableTypes[$form['type'] ?? ''] ?? '') ?></span>
    </p>

    <?php if (!empty($errors)): ?>
      <div class="alert alert-error" role="alert">
        <?php if (count($errors) === 1): ?>
          <?= e($errors[0]) ?>
        <?php else: ?>
          <ul style="margin:0;padding-left:18px">
            <?php foreach ($errors as $err): ?><li><?= e($err) ?></li><?php endforeach; ?>
          </ul>
        <?php endif; ?>
      </div>
    <?php endif; ?>


    <?php /* ════ STEP 1 ════ */ if ($step === 1): ?>
    <form method="POST" action="" novalidate>
      <input type="hidden" name="action" value="step1">

      <div class="field">
        <label for="type">Requisition Type <span class="required">*</span></label>
        <select id="type" name="type" required onchange="handleTypeChange(this.value)">
          <option value="">— Choose type —</option>
          <?php foreach ($availableTypes as $key => $label): ?>
            <option value="<?= e($key) ?>" <?= ($form['type'] ?? '') === $key ? 'selected' : '' ?>>
              <?= e($label) ?>
            </option>
          <?php endforeach; ?>
        </select>
        <?php if (!$isITDept): ?>
          <p class="field-hint">IT Asset requisitions are only available to IT department staff.</p>
        <?php endif; ?>
      </div>

      <!-- Position title — personnel only -->
      <div class="field" id="field-title" style="display:none">
        <label for="title">Position Title <span class="required">*</span></label>
        <input type="text" id="title" name="title"
               placeholder="e.g. IT Analyst, HR Officer"
               value="<?= e($form['title'] ?? '') ?>">
        <p class="field-hint">Specify the actual role title. Use the seniority level picker in Step 2 for the salary band.</p>
      </div>

      <div class="field">
        <label for="department_id">Department <span class="required">*</span></label>
        <select id="department_id" name="department_id">
          <option value="">Select Department</option>
          <?php foreach ($departments as $dept): ?>
            <option value="<?= $dept['department_id'] ?>"
              <?= (int)($form['department_id'] ?? 0) === (int)$dept['department_id'] ? 'selected' : '' ?>>
              <?= e($dept['department_name']) ?>
            </option>
          <?php endforeach; ?>
        </select>
      </div>

      <div class="field">
        <label for="date_required">Date Required <span class="required">*</span></label>
        <input type="date" id="date_required" name="date_required"
               min="<?= date('Y-m-d', strtotime('+1 day')) ?>"
               value="<?= e($form['date_required'] ?? '') ?>"
               onchange="showLeadTimeWarning(this.value)">
        <div id="lead-time-warning" class="lead-time-warning" style="display:none">
          <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" aria-hidden="true">
            <path d="M10.29 3.86L1.82 18a2 2 0 0 0 1.71 3h16.94a2 2 0 0 0 1.71-3L13.71 3.86a2 2 0 0 0-3.42 0z"/>
            <line x1="12" y1="9" x2="12" y2="13"/><line x1="12" y1="17" x2="12.01" y2="17"/>
          </svg>
          <span id="lead-time-text">Recommended lead time: 30 days</span>
        </div>
      </div>

      <div class="field">
        <label>Priority <span class="required">*</span></label>
        <div class="priority-group" role="radiogroup" aria-label="Priority">
          <?php foreach (['low'=>'Low','medium'=>'Medium','high'=>'High'] as $val => $lbl): ?>
          <label class="priority-option">
            <input type="radio" name="priority" value="<?= $val ?>"
                   <?= ($form['priority'] ?? 'medium') === $val ? 'checked' : '' ?> required>
            <span class="p-dot <?= $val ?>"></span><?= $lbl ?>
          </label>
          <?php endforeach; ?>
        </div>
      </div>

      <div class="form-actions">
        <button type="submit" name="action" value="cancel" class="btn btn-outline">Cancel</button>
        <button type="submit" class="btn btn-dark">Next Step →</button>
      </div>
    </form>

    <?php /* ════ STEP 2 ════ */ elseif ($step === 2):
      $reqType = $form['type'] ?? 'procurement';
      $isPersonnel = ($reqType === 'personnel');
    ?>
    <form method="POST" action="" novalidate id="step2-form">
      <input type="hidden" name="action" value="step2">

      <!-- Catalog search + items table -->
      <div class="field">
        <label>
          <?= $isPersonnel ? 'Seniority Level' : 'Items' ?>
          <span class="required">*</span>
        </label>

        <?php if (!empty($catalogItems)): ?>
        <!-- Catalog search bar -->
        <div class="catalog-search-wrap" style="margin-bottom:10px">
          <input type="search" id="catalog-search"
                 placeholder="<?= $isPersonnel ? 'Search seniority level…' : 'Search catalog…' ?>"
                 autocomplete="off"
                 style="width:100%;padding:9px 12px;border:1px solid var(--border);border-radius:var(--radius);font-size:14px;outline:none">
          <div id="catalog-dropdown" class="catalog-dropdown" style="display:none"></div>
        </div>
        <?php endif; ?>

        <table class="items-table" id="items-table">
          <thead>
            <tr>
              <th style="width:38%"><?= $isPersonnel ? 'Seniority Level' : 'Item' ?></th>
              <th style="width:12%">Qty<?= $isPersonnel ? '/Months' : '' ?></th>
              <th style="width:22%">Unit Cost (KES)</th>
              <th style="width:18%">Subtotal</th>
              <th style="width:10%"></th>
            </tr>
          </thead>
          <tbody id="items-body">
            <?php
            $savedItems = $form['items'] ?? [['item_description'=>'','quantity'=>1,'unit_cost'=>0,'catalog_id'=>null,'is_custom'=>0]];
            foreach ($savedItems as $item): ?>
            <tr>
              <td>
                <input type="text" name="item_description[]"
                       value="<?= e($item['item_description']) ?>"
                       placeholder="<?= $isPersonnel ? 'e.g. Senior Associate' : 'Item description' ?>"
                       oninput="recalcRow(this)" required>
                <input type="hidden" name="catalog_id[]" value="<?= $item['catalog_id'] ?? '' ?>">
                <input type="hidden" name="is_custom[]"  value="<?= (int)($item['is_custom'] ?? 0) ?>">
              </td>
              <td><input type="number" name="quantity[]" min="1"
                         value="<?= (int)($item['quantity'] ?? 1) ?>"
                         oninput="recalcRow(this)" style="text-align:center"></td>
              <td><input type="number" name="unit_cost[]" min="0" step="0.01"
                         value="<?= number_format((float)($item['unit_cost'] ?? 0), 2, '.', '') ?>"
                         oninput="recalcRow(this)" placeholder="0.00"></td>
              <td class="subtotal-cell">KES <?= number_format((float)($item['unit_cost'] ?? 0) * (int)($item['quantity'] ?? 1), 2) ?></td>
              <td><button type="button" class="remove-row" onclick="removeRow(this)" title="Remove">×</button></td>
            </tr>
            <?php endforeach; ?>
          </tbody>
        </table>

        <div style="display:flex;align-items:center;justify-content:space-between;margin-top:8px;flex-wrap:wrap;gap:8px">
          <button type="button" class="add-item-btn" onclick="addItemRow()">
            <svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" aria-hidden="true">
              <line x1="12" y1="5" x2="12" y2="19"/><line x1="5" y1="12" x2="19" y2="12"/>
            </svg>
            Add <?= $isPersonnel ? 'another level' : 'item' ?>
          </button>
          <div class="total-row">
            <span class="total-label">Total:</span>
            <span class="total-value" id="grand-total">KES <?= number_format((float)($form['total_amount'] ?? 0), 2) ?></span>
          </div>
        </div>

        <?php if ($isPersonnel): ?>
        <p class="field-hint" style="margin-top:8px">
          Select the seniority band from the catalog. The unit cost is the monthly salary budget.
          Set quantity to the number of months (or 1 for a headcount budget line).
          Enter the actual role title in the Description field below.
        </p>
        <?php endif; ?>
      </div>

      <?php if ($isPersonnel): ?>
      <div class="field">
        <label for="employment_type">Employment Type</label>
        <select id="employment_type" name="employment_type">
          <option value="">— Select —</option>
          <?php foreach (['permanent'=>'Permanent','contract'=>'Contract','internship'=>'Internship'] as $v => $l): ?>
            <option value="<?= $v ?>" <?= ($form['employment_type'] ?? '') === $v ? 'selected' : '' ?>><?= $l ?></option>
          <?php endforeach; ?>
        </select>
      </div>
      <?php endif; ?>

      <div class="field">
        <label for="description">Description <span class="required">*</span></label>
        <textarea id="description" name="description"
                  placeholder="<?= $isPersonnel ? 'Describe the role, responsibilities, and why this position is needed…' : 'Explain why this requisition is needed…' ?>"
                  rows="4"><?= e($form['description'] ?? '') ?></textarea>
        <p class="field-hint">Visible to all approvers.</p>
      </div>

      <div class="form-actions">
        <button type="submit" name="action" value="back" class="btn btn-outline">← Back</button>
        <button type="submit" class="btn btn-dark">Review →</button>
      </div>
    </form>

    <?php /* ════ STEP 3 ════ */ elseif ($step === 3): ?>
    <form method="POST" action="" novalidate>
      <input type="hidden" name="action" value="submit">

      <div class="review-section">
        <h2 class="review-section-title">Requisition details</h2>
        <div class="review-grid">
          <div class="review-field"><span class="rf-label">Type</span>
            <span class="rf-value"><?= e($availableTypes[$form['type'] ?? ''] ?? ucfirst($form['type'] ?? '')) ?></span></div>
          <div class="review-field"><span class="rf-label">Priority</span>
            <span class="rf-value"><?= priorityBadge($form['priority'] ?? 'medium') ?></span></div>
          <div class="review-field"><span class="rf-label">Department</span>
            <span class="rf-value"><?= e($deptName ?: '—') ?></span></div>
          <div class="review-field"><span class="rf-label">Date Required</span>
            <span class="rf-value"><?= e(formatDate($form['date_required'] ?? '')) ?></span></div>
          <?php if (!empty($form['title'])): ?>
          <div class="review-field"><span class="rf-label">Position Title</span>
            <span class="rf-value"><?= e($form['title']) ?></span></div>
          <?php endif; ?>
          <?php if (!empty($form['employment_type'])): ?>
          <div class="review-field"><span class="rf-label">Employment Type</span>
            <span class="rf-value"><?= e(ucfirst($form['employment_type'])) ?></span></div>
          <?php endif; ?>
        </div>
      </div>

      <?php if (!empty($form['items'])): ?>
      <div class="review-section">
        <h2 class="review-section-title">Items</h2>
        <table class="review-items-table">
          <thead><tr><th>Item</th><th>Qty</th><th>Unit Cost</th><th style="text-align:right">Subtotal</th></tr></thead>
          <tbody>
            <?php foreach ($form['items'] as $item): ?>
            <tr>
              <td><?= e($item['item_description']) ?><?= !empty($item['is_custom']) ? ' <span style="font-size:11px;color:var(--text-muted)">(custom)</span>' : '' ?></td>
              <td><?= (int)$item['quantity'] ?></td>
              <td><?= formatKES((float)$item['unit_cost']) ?></td>
              <td style="text-align:right"><?= formatKES((float)$item['subtotal']) ?></td>
            </tr>
            <?php endforeach; ?>
          </tbody>
          <tfoot><tr>
            <td colspan="3" style="text-align:right;font-weight:600;padding:10px">Total</td>
            <td style="text-align:right;font-weight:700;padding:10px"><?= formatKES((float)($form['total_amount'] ?? 0)) ?></td>
          </tr></tfoot>
        </table>
      </div>
      <?php endif; ?>

      <div class="review-section">
        <h2 class="review-section-title">Description</h2>
        <p style="font-size:14px;line-height:1.7;color:var(--text)"><?= nl2br(e($form['description'] ?? '—')) ?></p>
      </div>

      <!-- Approval chain preview -->
      <div class="review-section">
        <h2 class="review-section-title">Approval route</h2>
        <?php if (empty($chainPreview)): ?>
          <p style="font-size:13px;color:var(--text-muted)">
            This requisition will be <strong>auto-approved</strong> as it is submitted by the Managing Director.
          </p>
        <?php else: ?>
          <div style="display:flex;flex-direction:column;gap:8px;margin-top:4px">
            <?php foreach ($chainPreview as $step_c): ?>
            <div style="display:flex;align-items:center;gap:10px;font-size:13px">
              <?php if ($step_c['skipped']): ?>
                <span style="width:20px;height:20px;border-radius:50%;background:var(--bg);border:1px solid var(--border);display:inline-flex;align-items:center;justify-content:center;color:var(--text-muted);font-size:10px;flex-shrink:0">—</span>
                <span style="color:var(--text-muted);text-decoration:line-through"><?= e($step_c['label']) ?></span>
                <span style="font-size:11px;color:var(--text-muted);font-style:italic">Skipped — <?= e($step_c['skip_reason']) ?></span>
              <?php else: ?>
                <span style="width:20px;height:20px;border-radius:50%;background:var(--green);display:inline-flex;align-items:center;justify-content:center;color:#fff;font-size:10px;flex-shrink:0">✓</span>
                <span style="color:var(--text)"><?= e($step_c['label']) ?></span>
              <?php endif; ?>
            </div>
            <?php endforeach; ?>
          </div>
          <p style="font-size:12px;color:var(--text-muted);margin-top:10px">You will be notified at each stage.</p>
        <?php endif; ?>
      </div>

      <div class="form-actions">
        <button type="submit" name="action" value="back" class="btn btn-outline">← Back</button>
        <button type="submit" class="btn btn-primary" id="submit-btn">
          <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" aria-hidden="true">
            <polyline points="20 6 9 17 4 12"/>
          </svg>
          Submit Requisition
        </button>
      </div>
    </form>
    <?php endif; ?>

  </div><!-- /form-card -->
</div><!-- /page-wrap -->

<script>
// ── Catalog data from PHP ─────────────────────────────────────────────────
const CATALOG = <?= json_encode(array_map(fn($c) => [
    'id'    => $c['catalog_id'],
    'name'  => $c['item_name'],
    'cost'  => (float)$c['standard_unit_cost'],
    'unit'  => $c['unit_label'],
], $catalogItems)) ?>;

// ── Type change (step 1) ──────────────────────────────────────────────────
function handleTypeChange(type) {
  const f = document.getElementById('field-title');
  if (f) f.style.display = type === 'personnel' ? 'block' : 'none';
}

// ── Lead time warning ─────────────────────────────────────────────────────
function showLeadTimeWarning(dateVal) {
  const warning = document.getElementById('lead-time-warning');
  const textEl  = document.getElementById('lead-time-text');
  if (!warning || !dateVal) return;
  const daysAhead = Math.round((new Date(dateVal) - new Date()) / 86400000);
  if (daysAhead < 30) {
    textEl.textContent = 'Recommended lead time is 30 days. Your selection is only ' + daysAhead + ' day(s) away.';
    warning.style.display = 'flex';
  } else {
    warning.style.display = 'none';
  }
}

// ── Catalog search dropdown ───────────────────────────────────────────────
const searchInput = document.getElementById('catalog-search');
const dropdown    = document.getElementById('catalog-dropdown');

if (searchInput) {
  searchInput.addEventListener('input', function() {
    const q = this.value.trim().toLowerCase();
    if (!q) { dropdown.style.display = 'none'; return; }

    const matches = CATALOG.filter(c => c.name.toLowerCase().includes(q));
    // Always append "Other (unlisted item)" at the bottom
    const otherEntry = { id: null, name: 'Other (unlisted item)', cost: 0, unit: 'unit' };
    const results = [...matches, otherEntry];

    if (!results.length) { dropdown.style.display = 'none'; return; }

    dropdown.innerHTML = results.map(c =>
      `<div class="catalog-option" data-id="${c.id ?? ''}" data-name="${c.name}" data-cost="${c.cost}" data-unit="${c.unit}">
        <span class="co-name">${c.name}</span>
        ${c.id ? `<span class="co-cost">KES ${c.cost.toLocaleString('en-KE', {minimumFractionDigits:2})}</span>` : '<span class="co-cost" style="color:var(--text-muted)">custom price</span>'}
       </div>`
    ).join('');
    dropdown.style.display = 'block';

    dropdown.querySelectorAll('.catalog-option').forEach(opt => {
      opt.addEventListener('click', function() {
        const isCustom = !this.dataset.id;
        addItemRow(this.dataset.name, parseFloat(this.dataset.cost) || 0,
                   this.dataset.id || '', isCustom ? 1 : 0, isCustom);
        searchInput.value = '';
        dropdown.style.display = 'none';
      });
    });
  });

  document.addEventListener('click', e => {
    if (!searchInput.contains(e.target) && !dropdown.contains(e.target))
      dropdown.style.display = 'none';
  });
}

// ── Add item row ──────────────────────────────────────────────────────────
function addItemRow(name = '', cost = 0, catalogId = '', isCustom = 0, focusName = false) {
  const tbody = document.getElementById('items-body');
  if (!tbody) return;
  const row = document.createElement('tr');
  const readonly = (catalogId && !isCustom) ? 'readonly style="background:var(--bg)"' : '';
  row.innerHTML = `
    <td>
      <input type="text" name="item_description[]" value="${name}"
             placeholder="Item description" oninput="recalcRow(this)" required ${focusName ? '' : ''}>
      <input type="hidden" name="catalog_id[]" value="${catalogId}">
      <input type="hidden" name="is_custom[]"  value="${isCustom}">
    </td>
    <td><input type="number" name="quantity[]" min="1" value="1"
               oninput="recalcRow(this)" style="text-align:center"></td>
    <td><input type="number" name="unit_cost[]" min="0" step="0.01" value="${cost.toFixed(2)}"
               oninput="recalcRow(this)" placeholder="0.00" ${readonly}></td>
    <td class="subtotal-cell">KES ${(cost).toLocaleString('en-KE',{minimumFractionDigits:2})}</td>
    <td><button type="button" class="remove-row" onclick="removeRow(this)" title="Remove">×</button></td>`;
  tbody.appendChild(row);
  if (focusName) row.querySelector('input[name="item_description[]"]').focus();
  recalcGrandTotal();
}

function removeRow(btn) {
  const tbody = document.getElementById('items-body');
  if (tbody && tbody.rows.length <= 1) return;
  btn.closest('tr').remove();
  recalcGrandTotal();
}

function recalcRow(input) {
  const row   = input.closest('tr');
  const qty   = parseFloat(row.querySelector('[name="quantity[]"]')?.value) || 0;
  const price = parseFloat(row.querySelector('[name="unit_cost[]"]')?.value) || 0;
  const cell  = row.querySelector('.subtotal-cell');
  if (cell) cell.textContent = 'KES ' + (qty * price).toLocaleString('en-KE', {minimumFractionDigits:2, maximumFractionDigits:2});
  recalcGrandTotal();
}

function recalcGrandTotal() {
  let total = 0;
  document.querySelectorAll('#items-body tr').forEach(row => {
    const qty   = parseFloat(row.querySelector('[name="quantity[]"]')?.value) || 0;
    const price = parseFloat(row.querySelector('[name="unit_cost[]"]')?.value) || 0;
    total += qty * price;
  });
  const el = document.getElementById('grand-total');
  if (el) el.textContent = 'KES ' + total.toLocaleString('en-KE', {minimumFractionDigits:2, maximumFractionDigits:2});
}

// ── Prevent double-submit ─────────────────────────────────────────────────
const submitBtn = document.getElementById('submit-btn');
if (submitBtn) {
  submitBtn.closest('form').addEventListener('submit', function() {
    submitBtn.disabled = true;
    submitBtn.textContent = 'Submitting…';
  });
}

// ── On load ───────────────────────────────────────────────────────────────
document.addEventListener('DOMContentLoaded', function() {
  const typeSelect = document.getElementById('type');
  if (typeSelect && typeSelect.value) handleTypeChange(typeSelect.value);
  const dateInput = document.getElementById('date_required');
  if (dateInput && dateInput.value) showLeadTimeWarning(dateInput.value);
  recalcGrandTotal();
});
</script>

<style>
.catalog-search-wrap { position:relative }
.catalog-dropdown {
  position:absolute; top:100%; left:0; right:0; z-index:200;
  background:var(--white); border:1px solid var(--border);
  border-radius:var(--radius); box-shadow:0 4px 16px rgba(0,0,0,.1);
  max-height:260px; overflow-y:auto;
}
.catalog-option {
  display:flex; align-items:center; justify-content:space-between;
  padding:9px 14px; cursor:pointer; font-size:13px; gap:12px;
}
.catalog-option:hover { background:var(--bg) }
.co-name { flex:1 }
.co-cost { color:var(--green); font-weight:600; white-space:nowrap }
</style>

<?php include __DIR__ . '/../includes/footer.php'; ?>
