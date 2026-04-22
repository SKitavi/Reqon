<?php
// requisitions/new.php
// Step data is stored in $_SESSION['req_form'] between steps.

require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../includes/auth.php';

requireLogin();

$user = currentUser();
$db   = getDB();

// ── Load departments for dropdowns ────────────────────────────────────────
$departments = fetchAll("SELECT id, name FROM departments ORDER BY name");

// ── Determine current step ────────────────────────────────────────────────
// Step advances on valid POST. Defaults to 1 on first visit.
// A "Back" button decrements the step.
$step = (int)($_SESSION['req_form']['step'] ?? 1);

// "Back" button — just go back one step, no validation needed
if (isset($_POST['action']) && $_POST['action'] === 'back') {
    $step--;
    $_SESSION['req_form']['step'] = $step;
    // Re-render — don't fall through to validation below
    header('Location: ' . BASE_URL . '/requisitions/new.php');
    exit;
}

// "Cancel" — wipe form session and go to dashboard
if (isset($_POST['action']) && $_POST['action'] === 'cancel') {
    unset($_SESSION['req_form']);
    redirect(BASE_URL . '/dashboard.php');
}

$errors = [];

// ══════════════════════════════════════════════════════════
// STEP 1 POST — validate and advance
// ══════════════════════════════════════════════════════════
if ($_SERVER['REQUEST_METHOD'] === 'POST' && $step === 1) {

    $type       = post('type');
    $deptId     = (int)post('department_id');
    $sectionId  = (int)post('section_id');
    $dateReq    = post('date_required');
    $priority   = post('priority', 'medium');
    $title      = post('title'); // position title (personnel) or short description

    // Validation
    if (!$type || !array_key_exists($type, REQUISITION_TYPES)) {
        $errors[] = 'Please select a requisition type.';
    }
    if (!$deptId) $errors[] = 'Please select a department.';
    if (!$dateReq) {
        $errors[] = 'Please provide the date required.';
    } elseif (strtotime($dateReq) < strtotime(date('Y-m-d'))) {
        $errors[] = 'Date required cannot be in the past.';
    }
    if ($type === 'personnel' && !$title) {
        $errors[] = 'Position title is required for personnel requisitions.';
    }

    if (empty($errors)) {
        // Store step 1 data and advance
        $_SESSION['req_form'] = array_merge($_SESSION['req_form'] ?? [], [
            'step'          => 2,
            'type'          => $type,
            'department_id' => $deptId,
            'section_id'    => $sectionId,
            'date_required' => $dateReq,
            'priority'      => $priority,
            'title'         => $title,
        ]);
        redirect(BASE_URL . '/requisitions/new.php');
    }
    // If errors, stay on step 1 and show them
    $step = 1;
}

// ══════════════════════════════════════════════════════════
// STEP 2 POST — validate and advance
// ══════════════════════════════════════════════════════════
if ($_SERVER['REQUEST_METHOD'] === 'POST' && $step === 2) {

    $justification = post('justification');
    $type          = $_SESSION['req_form']['type'] ?? '';

    if (!$justification) {
        $errors[] = 'Please provide a justification.';
    }

    // Collect line items for procurement/it_asset/merchandise
    $items       = [];
    $totalAmount = 0;
    if (in_array($type, ['procurement', 'it_asset', 'merchandise'])) {
        $itemNames  = $_POST['item_name']   ?? [];
        $quantities = $_POST['quantity']    ?? [];
        $prices     = $_POST['unit_price']  ?? [];

        foreach ($itemNames as $i => $name) {
            $name = trim($name);
            if (!$name) continue; // skip blank rows
            $qty      = max(1, (int)($quantities[$i] ?? 1));
            $price    = max(0, (float)($prices[$i] ?? 0));
            $subtotal = $qty * $price;
            $totalAmount += $subtotal;
            $items[] = [
                'item_name'  => $name,
                'quantity'   => $qty,
                'unit_price' => $price,
                'subtotal'   => $subtotal,
            ];
        }
        if (empty($items)) {
            $errors[] = 'Please add at least one item.';
        }
    }

    // Personnel-specific fields
    $vacancies     = (int)post('vacancies', '1');
    $employmentType = post('employment_type');

    if (empty($errors)) {
        $_SESSION['req_form'] = array_merge($_SESSION['req_form'], [
            'step'            => 3,
            'justification'   => $justification,
            'items'           => $items,
            'total_amount'    => $totalAmount,
            'vacancies'       => $vacancies,
            'employment_type' => $employmentType,
        ]);
        redirect(BASE_URL . '/requisitions/new.php');
    }
    $step = 2;
}

// ══════════════════════════════════════════════════════════
// STEP 3 POST — final submit, save to database
// ══════════════════════════════════════════════════════════
if ($_SERVER['REQUEST_METHOD'] === 'POST' && $step === 3) {

    $form = $_SESSION['req_form'] ?? [];

    if (empty($form['type'])) {
        // Session expired or tampered — restart
        unset($_SESSION['req_form']);
        redirect(BASE_URL . '/requisitions/new.php');
    }

    try {
        // Generate REQ number BEFORE inserting
        $reqNumber = generateReqNumber();

        // Insert requisition
        query(
            "INSERT INTO requisitions
               (req_number, type, status, priority, submitted_by, department_id,
                section_id, date_required, justification, total_amount, current_approval_level)
             VALUES (?, ?, 'pending', ?, ?, ?, ?, ?, ?, ?, 1)",
            [
                $reqNumber,
                $form['type'],
                $form['priority'],
                $user['id'],
                $form['department_id'] ?: null,
                $form['section_id']    ?: null,
                $form['date_required'],
                $form['justification'],
                $form['total_amount']  ?? 0,
            ]
        );

        $reqId = lastInsertId();

        // Also save the req_number back into the row (race-condition-safe)
        query("UPDATE requisitions SET req_number = ? WHERE id = ?", [$reqNumber, $reqId]);

        // Insert line items (procurement / IT asset / merchandise)
        if (!empty($form['items'])) {
            foreach ($form['items'] as $item) {
                query(
                    "INSERT INTO requisition_items (requisition_id, item_name, quantity, unit_price)
                     VALUES (?, ?, ?, ?)",
                    [$reqId, $item['item_name'], $item['quantity'], $item['unit_price']]
                );
            }
        }

        // Create approval_history row for level 1 approver (Dept Head)
        // We look up the dept head for this department
        $deptHead = fetchOne(
            "SELECT id FROM users WHERE department_id = ? AND role = 'dept_head' LIMIT 1",
            [$form['department_id']]
        );
        $approverId = $deptHead['id'] ?? null;

        if ($approverId) {
            query(
                "INSERT INTO approval_history (requisition_id, approver_id, approval_level, decision)
                 VALUES (?, ?, 1, 'pending')",
                [$reqId, $approverId]
            );

            // Notify the dept head
            query(
                "INSERT INTO notifications (user_id, requisition_id, message)
                 VALUES (?, ?, ?)",
                [
                    $approverId,
                    $reqId,
                    "New requisition {$reqNumber} requires your approval.",
                ]
            );
        }

        // Audit log
        auditLog('CREATE', 'requisitions', $reqId, "Submitted {$reqNumber}");

        // Clear form session data
        unset($_SESSION['req_form']);

        setFlash('success', "Requisition {$reqNumber} submitted successfully and is pending approval.");
        redirect(BASE_URL . '/requisitions/view.php?id=' . $reqId);

    } catch (Exception $e) {
        $errors[] = 'Something went wrong saving your requisition. Please try again.';
        // In development, show the real error:
        if (defined('APP_DEBUG') && APP_DEBUG) {
            $errors[] = $e->getMessage();
        }
        $step = 3;
    }
}

// ── Load sections for the currently selected department (step 1) ──────────
$selectedDept = $_SESSION['req_form']['department_id'] ?? 0;
$sections = $selectedDept
    ? fetchAll("SELECT id, name FROM sections WHERE department_id = ? ORDER BY name", [$selectedDept])
    : [];

// ── Pull saved step data for pre-filling fields ───────────────────────────
$form = $_SESSION['req_form'] ?? [];

// ── Department name lookup for review step ────────────────────────────────
$deptName = '';
if (!empty($form['department_id'])) {
    $d = fetchOne("SELECT name FROM departments WHERE id = ?", [$form['department_id']]);
    $deptName = $d['name'] ?? '';
}
$sectionName = '';
if (!empty($form['section_id'])) {
    $s = fetchOne("SELECT name FROM sections WHERE id = ?", [$form['section_id']]);
    $sectionName = $s['name'] ?? '';
}

$pageTitle = 'New Requisition';
include __DIR__ . '/../includes/header.php';
?>

<div class="page-wrap">

  <!-- Breadcrumb -->
  <nav class="breadcrumb" aria-label="Breadcrumb">
    <a href="<?= BASE_URL ?>/dashboard.php">Dashboard</a>
    <span class="sep">›</span>
    <span class="current">New Requisition</span>
  </nav>

  <h1 class="page-title" style="margin-bottom:24px">Create New Requisition</h1>

  <div class="form-card">

    <!-- ── Step progress indicator ──────────────────────────── -->
    <div class="step-progress" role="list" aria-label="Form progress">

      <div class="step-item" role="listitem">
        <div class="step-dot <?= $step >= 1 ? ($step > 1 ? 'done' : 'active') : '' ?>"></div>
        <span class="step-label <?= $step === 1 ? 'active' : '' ?>">Type &amp; Details</span>
      </div>

      <div class="step-line <?= $step > 1 ? 'done' : '' ?>"></div>

      <div class="step-item" role="listitem">
        <div class="step-dot <?= $step >= 2 ? ($step > 2 ? 'done' : 'active') : '' ?>"></div>
        <span class="step-label <?= $step === 2 ? 'active' : '' ?>">
          <?= in_array($form['type'] ?? '', ['procurement','it_asset','merchandise']) ? 'Items' : 'Specifics' ?>
        </span>
      </div>

      <div class="step-line <?= $step > 2 ? 'done' : '' ?>"></div>

      <div class="step-item" role="listitem">
        <div class="step-dot <?= $step === 3 ? 'active' : '' ?>"></div>
        <span class="step-label <?= $step === 3 ? 'active' : '' ?>">Review &amp; Submit</span>
      </div>

    </div><!-- /step-progress -->

    <p class="step-counter">
      Step <?= $step ?> of 3
      <span class="step-counter-hint"><?= defined('REQUISITION_TYPES') ? (REQUISITION_TYPES[$form['type'] ?? ''] ?? '') : '' ?></span>
    </p>

    <!-- Validation errors -->
    <?php if (!empty($errors)): ?>
      <div class="alert alert-error" role="alert">
        <?php if (count($errors) === 1): ?>
          <?= e($errors[0]) ?>
        <?php else: ?>
          <ul style="margin:0;padding-left:18px">
            <?php foreach ($errors as $err): ?>
              <li><?= e($err) ?></li>
            <?php endforeach; ?>
          </ul>
        <?php endif; ?>
      </div>
    <?php endif; ?>


    <?php /* ════════════════════════════════════════════════
           STEP 1 — Requisition type + basic metadata
           ════════════════════════════════════════════════ */ ?>
    <?php if ($step === 1): ?>

    <form method="POST" action="" novalidate>
      <input type="hidden" name="action" value="step1">

      <!-- Requisition Type -->
      <div class="field">
        <label for="type">Select Requisition Type <span class="required">*</span></label>
        <select id="type" name="type" required onchange="handleTypeChange(this.value)">
          <option value="">— Choose type —</option>
          <?php foreach (REQUISITION_TYPES as $key => $label): ?>
            <option value="<?= e($key) ?>"
              <?= ($form['type'] ?? '') === $key ? 'selected' : '' ?>>
              <?= e($label) ?>
            </option>
          <?php endforeach; ?>
        </select>
      </div>

      <!-- Position title — visible for personnel only -->
      <div class="field type-section personnel-section" id="field-title">
        <label for="title">Position Title <span class="required">*</span></label>
        <input type="text" id="title" name="title"
               placeholder="e.g. Marketing Manager"
               value="<?= e($form['title'] ?? '') ?>">
      </div>

      <!-- Department -->
      <div class="field">
        <label for="department_id">Department <span class="required">*</span></label>
        <select id="department_id" name="department_id" required
                onchange="loadSections(this.value)">
          <option value="">Select Department</option>
          <?php foreach ($departments as $dept): ?>
            <option value="<?= $dept['id'] ?>"
              <?= (int)($form['department_id'] ?? 0) === (int)$dept['id'] ? 'selected' : '' ?>>
              <?= e($dept['name']) ?>
            </option>
          <?php endforeach; ?>
        </select>
      </div>

      <!-- Section (loaded dynamically by JS when dept selected) -->
      <div class="field">
        <label for="section_id">Section</label>
        <select id="section_id" name="section_id">
          <option value="">Select Section</option>
          <?php foreach ($sections as $sec): ?>
            <option value="<?= $sec['id'] ?>"
              <?= (int)($form['section_id'] ?? 0) === (int)$sec['id'] ? 'selected' : '' ?>>
              <?= e($sec['name']) ?>
            </option>
          <?php endforeach; ?>
        </select>
      </div>

      <!-- Date Required -->
      <div class="field">
        <label for="date_required">Date Required <span class="required">*</span></label>
        <input type="date" id="date_required" name="date_required"
               min="<?= date('Y-m-d') ?>"
               value="<?= e($form['date_required'] ?? '') ?>"
               onchange="showLeadTimeWarning(this.value)">
        <div id="lead-time-warning" class="lead-time-warning" style="display:none">
          <svg width="16" height="16" viewBox="0 0 24 24" fill="none"
               stroke="currentColor" stroke-width="2" stroke-linecap="round" aria-hidden="true">
            <path d="M10.29 3.86L1.82 18a2 2 0 0 0 1.71 3h16.94a2 2 0 0 0 1.71-3L13.71 3.86a2 2 0 0 0-3.42 0z"/>
            <line x1="12" y1="9" x2="12" y2="13"/><line x1="12" y1="17" x2="12.01" y2="17"/>
          </svg>
          <span id="lead-time-text">Recommended lead time: 30 days</span>
        </div>
      </div>

      <!-- Priority -->
      <div class="field">
        <label>Priority <span class="required">*</span></label>
        <div class="priority-group" role="radiogroup" aria-label="Priority">
          <?php
          $priorities = ['low' => 'Low', 'medium' => 'Medium', 'high' => 'High'];
          foreach ($priorities as $val => $label):
            $checked = ($form['priority'] ?? 'medium') === $val ? 'checked' : '';
          ?>
          <label class="priority-option">
            <input type="radio" name="priority" value="<?= $val ?>" <?= $checked ?> required>
            <span class="p-dot <?= $val ?>"></span>
            <?= $label ?>
          </label>
          <?php endforeach; ?>
        </div>
      </div>

      <!-- Actions -->
      <div class="form-actions">
        <button type="submit" name="action" value="cancel" class="btn btn-outline">
          Cancel
        </button>
        <button type="submit" class="btn btn-dark">
          Next Step →
        </button>
      </div>

    </form>


    <?php /* ════════════════════════════════════════════════
           STEP 2 — Type-specific details
           ════════════════════════════════════════════════ */ ?>
    <?php elseif ($step === 2):
      $reqType = $form['type'] ?? 'procurement';
    ?>

    <form method="POST" action="" novalidate id="step2-form">
      <input type="hidden" name="action" value="step2">

      <?php if (in_array($reqType, ['procurement', 'it_asset', 'merchandise'])): ?>
      <!-- ── LINE ITEMS TABLE ──────────────────────────────── -->
      <div class="field">
        <label>Items <span class="required">*</span></label>
        <table class="items-table" id="items-table">
          <thead>
            <tr>
              <th style="width:40%">Item Name</th>
              <th style="width:15%">Qty</th>
              <th style="width:22%">Unit Price (KES)</th>
              <th style="width:18%">Subtotal</th>
              <th style="width:5%"></th>
            </tr>
          </thead>
          <tbody id="items-body">
            <?php
            // Pre-fill if coming back from step 3
            $savedItems = $form['items'] ?? [['item_name'=>'','quantity'=>1,'unit_price'=>0]];
            foreach ($savedItems as $item): ?>
            <tr>
              <td><input type="text" name="item_name[]"
                         value="<?= e($item['item_name']) ?>"
                         placeholder="Describe the item"
                         oninput="recalcRow(this)" required></td>
              <td><input type="number" name="quantity[]" min="1"
                         value="<?= (int)$item['quantity'] ?>"
                         oninput="recalcRow(this)" style="text-align:center"></td>
              <td><input type="number" name="unit_price[]" min="0" step="0.01"
                         value="<?= number_format((float)$item['unit_price'], 2, '.', '') ?>"
                         oninput="recalcRow(this)" placeholder="0.00"></td>
              <td class="subtotal-cell">
                KES <?= number_format((float)($item['unit_price'] ?? 0) * (int)($item['quantity'] ?? 1), 2) ?>
              </td>
              <td><button type="button" class="remove-row" onclick="removeRow(this)" title="Remove">×</button></td>
            </tr>
            <?php endforeach; ?>
          </tbody>
        </table>

        <button type="button" class="add-item-btn" onclick="addItemRow()">
          <svg width="13" height="13" viewBox="0 0 24 24" fill="none"
               stroke="currentColor" stroke-width="2.5" stroke-linecap="round" aria-hidden="true">
            <line x1="12" y1="5" x2="12" y2="19"/><line x1="5" y1="12" x2="19" y2="12"/>
          </svg>
          Add item
        </button>

        <div class="total-row">
          <span class="total-label">Total Amount:</span>
          <span class="total-value" id="grand-total">
            KES <?= number_format((float)($form['total_amount'] ?? 0), 2) ?>
          </span>
        </div>
      </div>

      <?php elseif ($reqType === 'personnel'): ?>
      <!-- ── PERSONNEL SPECIFICS ───────────────────────────── -->
      <div class="field-row">
        <div class="field">
          <label for="vacancies">Number of Vacancies <span class="required">*</span></label>
          <input type="number" id="vacancies" name="vacancies" min="1"
                 value="<?= (int)($form['vacancies'] ?? 1) ?>">
        </div>
        <div class="field">
          <label for="employment_type">Employment Type</label>
          <select id="employment_type" name="employment_type">
            <option value="">— Select —</option>
            <?php
            $empTypes = ['permanent'=>'Permanent','contract'=>'Contract','internship'=>'Internship'];
            foreach ($empTypes as $v => $l):
              $sel = ($form['employment_type'] ?? '') === $v ? 'selected' : '';
            ?>
            <option value="<?= $v ?>" <?= $sel ?>><?= $l ?></option>
            <?php endforeach; ?>
          </select>
        </div>
      </div>
      <?php endif; ?>

      <!-- Justification (all types) -->
      <div class="field">
        <label for="justification">Justification <span class="required">*</span></label>
        <textarea id="justification" name="justification"
                  placeholder="Explain why this requisition is needed..."
                  rows="4"><?= e($form['justification'] ?? '') ?></textarea>
        <p class="field-hint">Be specific — this text is visible to all approvers.</p>
      </div>

      <!-- Actions -->
      <div class="form-actions">
        <button type="submit" name="action" value="back" class="btn btn-outline">
          ← Back
        </button>
        <button type="submit" class="btn btn-dark">
          Review →
        </button>
      </div>

    </form>


    <?php /* ════════════════════════════════════════════════
           STEP 3 — Review & Submit
           ════════════════════════════════════════════════ */ ?>
    <?php elseif ($step === 3): ?>

    <form method="POST" action="" novalidate>
      <input type="hidden" name="action" value="submit">

      <!-- Review: Basic details -->
      <div class="review-section">
        <h2 class="review-section-title">Requisition details</h2>
        <div class="review-grid">
          <div class="review-field">
            <span class="rf-label">Type</span>
            <span class="rf-value">
              <?= e(REQUISITION_TYPES[$form['type'] ?? ''] ?? ucfirst($form['type'] ?? '')) ?>
            </span>
          </div>
          <div class="review-field">
            <span class="rf-label">Priority</span>
            <span class="rf-value"><?= priorityBadge($form['priority'] ?? 'medium') ?></span>
          </div>
          <div class="review-field">
            <span class="rf-label">Department</span>
            <span class="rf-value"><?= e($deptName ?: '—') ?></span>
          </div>
          <div class="review-field">
            <span class="rf-label">Section</span>
            <span class="rf-value"><?= e($sectionName ?: '—') ?></span>
          </div>
          <div class="review-field">
            <span class="rf-label">Date Required</span>
            <span class="rf-value"><?= e(formatDate($form['date_required'] ?? '')) ?></span>
          </div>
          <?php if (!empty($form['title'])): ?>
          <div class="review-field">
            <span class="rf-label">Position Title</span>
            <span class="rf-value"><?= e($form['title']) ?></span>
          </div>
          <?php endif; ?>
          <?php if (!empty($form['vacancies'])): ?>
          <div class="review-field">
            <span class="rf-label">Vacancies</span>
            <span class="rf-value"><?= (int)$form['vacancies'] ?></span>
          </div>
          <?php endif; ?>
          <?php if (!empty($form['employment_type'])): ?>
          <div class="review-field">
            <span class="rf-label">Employment Type</span>
            <span class="rf-value"><?= e(ucfirst($form['employment_type'])) ?></span>
          </div>
          <?php endif; ?>
        </div>
      </div>

      <!-- Review: Line items (if applicable) -->
      <?php if (!empty($form['items'])): ?>
      <div class="review-section">
        <h2 class="review-section-title">Items</h2>
        <table class="review-items-table">
          <thead>
            <tr>
              <th>Item</th><th>Qty</th><th>Unit Price</th><th style="text-align:right">Subtotal</th>
            </tr>
          </thead>
          <tbody>
            <?php foreach ($form['items'] as $item): ?>
            <tr>
              <td><?= e($item['item_name']) ?></td>
              <td><?= (int)$item['quantity'] ?></td>
              <td><?= formatKES((float)$item['unit_price']) ?></td>
              <td style="text-align:right"><?= formatKES((float)$item['subtotal']) ?></td>
            </tr>
            <?php endforeach; ?>
          </tbody>
          <tfoot>
            <tr>
              <td colspan="3" style="text-align:right;font-weight:600;padding:10px">Total</td>
              <td style="text-align:right;font-weight:700;padding:10px">
                <?= formatKES((float)($form['total_amount'] ?? 0)) ?>
              </td>
            </tr>
          </tfoot>
        </table>
      </div>
      <?php endif; ?>

      <!-- Review: Justification -->
      <div class="review-section">
        <h2 class="review-section-title">Justification</h2>
        <p style="font-size:14px;line-height:1.7;color:var(--text)">
          <?= nl2br(e($form['justification'] ?? '—')) ?>
        </p>
      </div>

      <!-- Approval chain info -->
      <div class="review-section">
        <h2 class="review-section-title">Approval route</h2>
        <p style="font-size:13px;color:var(--text-muted);line-height:1.7">
          Once submitted, this requisition will follow a 4-level approval chain:
          <strong>Dept Head → HR Director → Finance Director → Managing Director.</strong>
          You will be notified at each stage.
        </p>
      </div>

      <!-- Actions -->
      <div class="form-actions">
        <button type="submit" name="action" value="back" class="btn btn-outline">
          ← Back
        </button>
        <button type="submit" class="btn btn-primary" id="submit-btn">
          <svg width="14" height="14" viewBox="0 0 24 24" fill="none"
               stroke="currentColor" stroke-width="2.5" stroke-linecap="round" aria-hidden="true">
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
// ── Type change: show/hide position title field ───────────────────────────
function handleTypeChange(type) {
  const titleField = document.getElementById('field-title');
  if (titleField) {
    titleField.style.display = type === 'personnel' ? 'block' : 'none';
  }
}

// ── Load sections via AJAX when department changes ────────────────────────
function loadSections(deptId) {
  const select = document.getElementById('section_id');
  if (!select) return;
  select.innerHTML = '<option value="">Loading…</option>';

  if (!deptId) {
    select.innerHTML = '<option value="">Select Section</option>';
    return;
  }

  fetch('<?= BASE_URL ?>/api/sections.php?department_id=' + encodeURIComponent(deptId))
    .then(r => r.json())
    .then(data => {
      select.innerHTML = '<option value="">Select Section</option>';
      data.forEach(sec => {
        const opt = document.createElement('option');
        opt.value = sec.id;
        opt.textContent = sec.name;
        select.appendChild(opt);
      });
    })
    .catch(() => {
      select.innerHTML = '<option value="">Select Section</option>';
    });
}

// ── Lead time warning ─────────────────────────────────────────────────────
function showLeadTimeWarning(dateVal) {
  const warning  = document.getElementById('lead-time-warning');
  const textEl   = document.getElementById('lead-time-text');
  if (!warning || !dateVal) return;

  const chosen    = new Date(dateVal);
  const today     = new Date();
  today.setHours(0,0,0,0);
  const daysAhead = Math.round((chosen - today) / 86400000);

  if (daysAhead < 30) {
    textEl.textContent = 'Recommended lead time is 30 days. Current selection is only ' + daysAhead + ' day(s) away.';
    warning.style.display = 'flex';
  } else {
    warning.style.display = 'none';
  }
}

// ── Line items: add row ───────────────────────────────────────────────────
function addItemRow() {
  const tbody = document.getElementById('items-body');
  if (!tbody) return;
  const row = document.createElement('tr');
  row.innerHTML = `
    <td><input type="text" name="item_name[]" placeholder="Describe the item"
               oninput="recalcRow(this)" required></td>
    <td><input type="number" name="quantity[]" min="1" value="1"
               oninput="recalcRow(this)" style="text-align:center"></td>
    <td><input type="number" name="unit_price[]" min="0" step="0.01" placeholder="0.00"
               oninput="recalcRow(this)"></td>
    <td class="subtotal-cell">KES 0.00</td>
    <td><button type="button" class="remove-row" onclick="removeRow(this)" title="Remove">×</button></td>`;
  tbody.appendChild(row);
}

// ── Line items: remove row ────────────────────────────────────────────────
function removeRow(btn) {
  const tbody = document.getElementById('items-body');
  if (tbody && tbody.rows.length <= 1) return; // keep at least 1 row
  btn.closest('tr').remove();
  recalcGrandTotal();
}

// ── Line items: recalc one row subtotal + grand total ─────────────────────
function recalcRow(input) {
  const row    = input.closest('tr');
  const qty    = parseFloat(row.querySelector('[name="quantity[]"]')?.value) || 0;
  const price  = parseFloat(row.querySelector('[name="unit_price[]"]')?.value) || 0;
  const sub    = qty * price;
  const cell   = row.querySelector('.subtotal-cell');
  if (cell) cell.textContent = 'KES ' + sub.toLocaleString('en-KE', {minimumFractionDigits:2, maximumFractionDigits:2});
  recalcGrandTotal();
}

function recalcGrandTotal() {
  let total = 0;
  document.querySelectorAll('#items-body tr').forEach(row => {
    const qty   = parseFloat(row.querySelector('[name="quantity[]"]')?.value) || 0;
    const price = parseFloat(row.querySelector('[name="unit_price[]"]')?.value) || 0;
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

// ── On page load: apply type visibility ──────────────────────────────────
document.addEventListener('DOMContentLoaded', function() {
  const typeSelect = document.getElementById('type');
  if (typeSelect && typeSelect.value) handleTypeChange(typeSelect.value);

  const dateInput = document.getElementById('date_required');
  if (dateInput && dateInput.value) showLeadTimeWarning(dateInput.value);
});
</script>

<?php include __DIR__ . '/../includes/footer.php'; ?>