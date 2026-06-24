<?php
// Shared utility functions — loaded automatically by config/config.php

// Output & Security 

function e(string $value): string {
    return htmlspecialchars($value, ENT_QUOTES, 'UTF-8');
}

function redirect(string $url): void {
    header('Location: ' . $url);
    exit;
}

// REQ Number 

function generateReqNumber(): string {
    $row  = fetchOne("SELECT COUNT(*) AS total FROM requisitions");
    $next = (int)($row['total'] ?? 0) + 1;
    return 'REQ-' . str_pad($next, 3, '0', STR_PAD_LEFT);
}

// Date helpers

function formatDate(?string $date, string $format = null): string {
    if (!$date) return '—';
    $format = $format ?? (defined('DISPLAY_DATE_FORMAT') ? DISPLAY_DATE_FORMAT : 'd/m/Y');
    return date($format, strtotime($date));
}

function timeAgo(string $date): string {
    $diff = time() - strtotime($date);
    if ($diff < 60)     return 'just now';
    if ($diff < 3600)   return floor($diff / 60) . 'm ago';
    if ($diff < 86400)  return floor($diff / 3600) . 'h ago';
    if ($diff < 604800) return floor($diff / 86400) . 'd ago';
    return formatDate($date);
}

//  Currency

function formatKES(float $amount): string {
    return 'KES ' . number_format($amount, 2);
}

//  Badge HTML

function statusBadge(string $status): string {
    $classes = [
        'pending'   => 'badge-pending',
        'in_review' => 'badge-pending',
        'approved'  => 'badge-approved',
        'rejected'  => 'badge-rejected',
        'cancelled' => 'badge-cancelled',
    ];
    $cls   = $classes[$status] ?? 'badge-pending';
    $label = ucfirst(str_replace('_', ' ', $status));
    return '<span class="badge ' . $cls . '">' . e($label) . '</span>';
}

function priorityBadge(string $priority): string {
    $classes = [
        'high'   => 'badge-priority-high',
        'medium' => 'badge-priority-medium',
        'low'    => 'badge-priority-low',
    ];
    $cls = $classes[$priority] ?? 'badge-priority-medium';
    return '<span class="badge ' . $cls . '">PRIORITY: ' . strtoupper(e($priority)) . '</span>';
}

//  Approval level labels

function approvalLevelLabel(int $level): string {
    $labels = [
        1 => 'Dept Head',
        2 => 'HR Director / Procurement Head',
        3 => 'Finance Director',
        4 => 'Managing Director',
    ];
    return $labels[$level] ?? 'Level ' . $level;
}

//  Flash messages 

function setFlash(string $type, string $message): void {
    $_SESSION['flash'] = ['type' => $type, 'message' => $message];
}

function renderFlash(): void {
    if (!isset($_SESSION['flash'])) return;
    $f   = $_SESSION['flash'];
    $cls = $f['type'] === 'success' ? 'alert-success' : 'alert-error';
    echo '<div class="alert ' . $cls . '" role="alert">' . e($f['message']) . '</div>';
    unset($_SESSION['flash']);
}

// Audit log 

function auditLog(string $action, string $table, int $recordId, string $details = ''): void {
    $userId = $_SESSION['user']['user_id'] ?? $_SESSION['user']['id'] ?? null;
    $ip     = $_SERVER['REMOTE_ADDR'] ?? '';
    query(
        "INSERT INTO audit_log (user_id, action_type, table_affected, record_id, description, ip_address)
         VALUES (?, ?, ?, ?, ?, ?)",
        [$userId, $action, $table, $recordId, $details, $ip]
    );
}

// Input helpers 

function post(string $key, string $default = ''): string {
    return trim($_POST[$key] ?? $default);
}

function get(string $key, string $default = ''): string {
    return trim($_GET[$key] ?? $default);
}

// Role / approval level

/**
 * Returns the approval level (1–4) for a user, or 0 if none.
 * Reads pre-computed value from session; falls back to live computation.
 */
function getRoleLevel(array $user): int {
    if (isset($user['approval_level'])) return (int)$user['approval_level'];

    $roleId  = (int)($user['role_id'] ?? 0);
    $section = $user['section'] ?? '';
    $deptId  = (int)($user['department_id'] ?? 0);

    if ($roleId === 2) return 2; // HR Admin → Level 2
    if ($roleId === 3) {
        if ($section && stripos($section, 'Dept Head') !== false) return 1;
        if ($deptId === 3) return 3; // Finance dept → Finance Director
        return 4;                    // otherwise MD
    }
    return 0;
}

// Requisition Type-specific routing engine 

/**
 * Known fixed approver user_ids (from seed data).
 * These are used for skip-self detection and fixed-slot routing.
 */
const APPROVER_IT_DEPT_HEAD   = 5;   // Elizabeth Wanjiku  — IT dept head
const APPROVER_PROCUREMENT_HEAD = 7; // Mary Wambua        — Procurement dept head
const APPROVER_HR_DIRECTOR    = 8;   // Grace Odhiambo     — HR Director (role_id=2)
const APPROVER_FINANCE_DIR    = 9;   // David Kariuki      — Finance Director
const APPROVER_MD             = 10;  // James Ngugi        — Managing Director

/**
 * Build the ordered approval chain for a requisition.
 *
 * Returns an array of steps, each:
 *   ['level' => int, 'approver_id' => int, 'label' => string, 'skipped' => bool, 'skip_reason' => string]
 *
 * Rules applied:
 *  - IT Asset: Level 1 is always IT Dept Head (user 5), regardless of submitter dept.
 *  - All other types: Level 1 is the submitter's own dept head.
 *  - Skip-self: if the submitter IS the approver at a level, that level is silently removed.
 *  - Mary (user 7) submitting Procurement/IT Asset/Merchandise → skips both L1 and L2.
 *  - MD (user 10) submitting anything → auto-approved, empty chain returned.
 *  - No dept head found for a dept → Level 1 skipped automatically.
 *
 * @param string $reqType      requisition_type enum value
 * @param int    $submitterId  user_id of the person submitting
 * @param int    $deptId       department_id of the requisition
 * @return array               ordered list of chain steps (skipped ones included for tracker display)
 */
function buildApprovalChain(string $reqType, int $submitterId, int $deptId): array {
    if ($submitterId === APPROVER_MD) {
        return [];
    }

    // Determine the 4 raw approver slots based on type
    switch ($reqType) {
        case 'it_asset':
            $slots = [
                1 => APPROVER_IT_DEPT_HEAD,      // always Elizabeth
                2 => APPROVER_PROCUREMENT_HEAD,  // Mary
                3 => APPROVER_FINANCE_DIR,        // David
                4 => APPROVER_MD,                 // James
            ];
            break;

        case 'personnel':
            $l1 = _getDeptHead($deptId);
            $slots = [
                1 => $l1,
                2 => APPROVER_HR_DIRECTOR,        // Grace
                3 => APPROVER_FINANCE_DIR,
                4 => APPROVER_MD,
            ];
            break;

        case 'procurement':
            // Procurement skips the submitter's dept head entirely.
            // Chain is: Procurement Head → Finance Director → MD (3 active levels).
            // level_id 9 (Submitter Dept Head for procurement) is intentionally unused.
            $slots = [
                1 => null,                           // no dept head slot for procurement
                2 => APPROVER_PROCUREMENT_HEAD,      // Mary — always L2 slot
                3 => APPROVER_FINANCE_DIR,
                4 => APPROVER_MD,
            ];
            // Mark L1 as structurally absent (not a skip-self, just not part of the chain)
            break;

        case 'merchandise':
            $l1 = _getDeptHead($deptId);
            $slots = [
                1 => $l1,
                2 => APPROVER_PROCUREMENT_HEAD,   // Mary
                3 => APPROVER_FINANCE_DIR,
                4 => APPROVER_MD,
            ];
            break;
    }

    // Apply skip-self rules. null slots are structurally absent (e.g. procurement has no L1).
    $chain = [];
    foreach ($slots as $level => $approverId) {
        $skipped     = false;
        $skipReason  = '';

        if ($approverId === null) {
            $skipped    = true;
            $skipReason = 'Not part of this requisition type\'s chain';
        } elseif ($approverId === $submitterId) {
            // Submitter IS this approver
            $skipped    = true;
            $skipReason = 'Submitted by approver';
        }

        $chain[] = [
            'level'       => $level,
            'approver_id' => $skipped ? null : $approverId,
            'label'       => _chainLevelLabel($reqType, $level),
            'skipped'     => $skipped,
            'skip_reason' => $skipReason,
        ];
    }

    return $chain;
}

/**
 * Returns the first active level number in the chain that is NOT skipped.
 * Returns null if the entire chain is skipped (auto-approve case).
 */
function firstActiveLevel(array $chain): ?int {
    foreach ($chain as $step) {
        if (!$step['skipped']) return $step['level'];
    }
    return null;
}

/**
 * Returns the approver_id for a given level in a built chain.
 */
function approverAtLevel(array $chain, int $level): ?int {
    foreach ($chain as $step) {
        if ($step['level'] === $level) return $step['approver_id'];
    }
    return null;
}

/**
 * Returns the next non-skipped level after $currentLevel, or null if done.
 */
function nextActiveLevel(array $chain, int $currentLevel): ?int {
    $found = false;
    foreach ($chain as $step) {
        if ($found && !$step['skipped']) return $step['level'];
        if ($step['level'] === $currentLevel) $found = true;
    }
    return null;
}

/**
 * Look up the dept head user_id for a given department.
 * Returns null if none found.
 */
function _getDeptHead(int $deptId): ?int {
    $row = fetchOne(
        "SELECT user_id FROM users
          WHERE role_id = 3 AND department_id = ? AND section LIKE '%Dept Head%'
          LIMIT 1",
        [$deptId]
    );
    return $row ? (int)$row['user_id'] : null;
}

/**
 * Human-readable label for a chain level, specific to requisition type.
 */
function _chainLevelLabel(string $reqType, int $level): string {
    $map = [
        'it_asset' => [
            1 => 'IT Dept Head',
            2 => 'Procurement Head',
            3 => 'Finance Director',
            4 => 'Managing Director',
        ],
        'personnel' => [
            1 => 'Dept Head',
            2 => 'HR Director',
            3 => 'Finance Director',
            4 => 'Managing Director',
        ],
        'procurement' => [
            // L1 is structurally absent; chain starts at L2 (Procurement Head)
            2 => 'Procurement Head',
            3 => 'Finance Director',
            4 => 'Managing Director',
        ],
        'merchandise' => [
            1 => 'Dept Head',
            2 => 'Procurement Head',
            3 => 'Finance Director',
            4 => 'Managing Director',
        ],
    ];
    return $map[$reqType][$level] ?? approvalLevelLabel($level);
}

/**
 * Public version of _chainLevelLabel for use in views.
 * Returns the correct level 2 label based on requisition type:
 *   personnel → "HR Director"
 *   all others → "Procurement Head"
 */
function approvalLevelLabelForType(string $reqType, int $level): string {
    return _chainLevelLabel($reqType, $level);
}

/**
 * Get the effective approval level for a user on a specific requisition.
 * Normally this equals getRoleLevel(), but Mary (Procurement Head, user 7)
 * acts at level 2 for Procurement/IT Asset/Merchandise reqs even though
 * her stored approval_level is 1 (Dept Head of Procurement dept).
 *
 * Returns the level this user should act at for this req, or 0 if none.
 */
function getEffectiveApprovalLevel(array $user, array $req): int {
    $uid      = (int)$user['user_id'];
    $baseLevel = getRoleLevel($user);

    // Mary special case: she is the Procurement Head slot (level 2)
    // for Procurement, IT Asset and Merchandise requisitions
    if ($uid === APPROVER_PROCUREMENT_HEAD
        && in_array($req['requisition_type'], ['procurement', 'it_asset', 'merchandise'])
    ) {
        return 2;
    }

    return $baseLevel;
}

/**
 * Find the next approver for a given level, using the type-specific chain.
 * This replaces the old generic getNextApprover().
 */
function getNextApproverFromChain(array $chain, int $nextLevel): ?array {
    $approverId = approverAtLevel($chain, $nextLevel);
    if (!$approverId) return null;
    return fetchOne(
        "SELECT user_id, full_name, email FROM users WHERE user_id = ?",
        [$approverId]
    ) ?: null;
}

/**
 * Legacy shim — kept so any code still calling getNextApprover() doesn't break.
 * Prefer getNextApproverFromChain() for new code.
 */
function getNextApprover(int $level, int $deptId): ?array {
    if ($level === 1) {
        $id = _getDeptHead($deptId);
        return $id ? fetchOne("SELECT user_id, full_name, email FROM users WHERE user_id = ?", [$id]) ?: null : null;
    }
    if ($level === 2) {
        return fetchOne("SELECT user_id, full_name, email FROM users WHERE role_id = 2 LIMIT 1") ?: null;
    }
    if ($level === 3) {
        return fetchOne(
            "SELECT user_id, full_name, email FROM users
              WHERE role_id = 3 AND department_id = 3
                AND (section IS NULL OR section NOT LIKE '%Dept Head%')
              LIMIT 1"
        ) ?: null;
    }
    if ($level === 4) {
        return fetchOne(
            "SELECT user_id, full_name, email FROM users
              WHERE role_id = 3
                AND (section IS NULL OR section NOT LIKE '%Dept Head%')
                AND department_id != 3
              LIMIT 1"
        ) ?: null;
    }
    return null;
}

// Approval decision engine 

/**
 * Process an approve or reject decision.
 * Uses the type-specific chain to determine the next approver.
 *
 * @param string $action     'approve' | 'reject'
 * @param int    $reqId
 * @param int    $approverId  user_id of the person deciding
 * @param string $comments
 */
function processApprovalDecision(string $action, int $reqId, int $approverId, string $comments = ''): void {
    $req = fetchOne("SELECT * FROM requisitions WHERE requisition_id = ?", [$reqId]);
    if (!$req) return;

    $currentLevel = (int)$req['current_approval_level'];
    $reqNumber    = $req['requisition_number'];
    $submitterId  = (int)$req['requester_id'];
    $deptId       = (int)$req['department_id'];
    $reqType      = $req['requisition_type'];

    // Build the chain so we know what comes next
    $chain = buildApprovalChain($reqType, $submitterId, $deptId);

    // 1. Record decision — update existing pending row
    $updated = query(
        "UPDATE approval_history
            SET decision = ?, comments = ?, decision_date = NOW()
          WHERE requisition_id = ? AND level_id = ? AND decision = 'pending'",
        [$action === 'approve' ? 'approved' : 'rejected', $comments, $reqId, $currentLevel]
    );

    // Safety net: insert if row didn't exist
    if ($updated->rowCount() === 0) {
        query(
            "INSERT INTO approval_history
               (requisition_id, approver_id, level_id, decision, comments, decision_date)
             VALUES (?, ?, ?, ?, ?, NOW())",
            [$reqId, $approverId, $currentLevel,
             $action === 'approve' ? 'approved' : 'rejected', $comments]
        );
    }

    if ($action === 'reject') {
        query(
            "UPDATE requisitions
                SET current_status = 'rejected', rejection_reason = ?, updated_at = NOW()
              WHERE requisition_id = ?",
            [$comments, $reqId]
        );
        query(
            "INSERT INTO notifications (user_id, requisition_id, notification_type, message)
             VALUES (?, ?, 'rejection', ?)",
            [
                $submitterId, $reqId,
                "Your requisition {$reqNumber} was rejected at "
                . _chainLevelLabel($reqType, $currentLevel) . ".",
            ]
        );
        auditLog('REJECT', 'requisitions', $reqId, "Rejected at level {$currentLevel}");

    } else {
        // Approved — find next active level in chain
        $nextLevel = nextActiveLevel($chain, $currentLevel);

        if ($nextLevel === null) {
            // No more levels — fully approved
            query(
                "UPDATE requisitions
                    SET current_status = 'approved',
                        final_approver_id = ?,
                        final_decision_date = NOW(),
                        updated_at = NOW()
                  WHERE requisition_id = ?",
                [$approverId, $reqId]
            );
            query(
                "INSERT INTO notifications (user_id, requisition_id, notification_type, message)
                 VALUES (?, ?, 'approval', ?)",
                [
                    $submitterId, $reqId,
                    "Your requisition {$reqNumber} has been fully approved.",
                ]
            );
            auditLog('APPROVE', 'requisitions', $reqId, "Final approval — fully approved");

        } else {
            // Advance to next level
            $nextApprover = getNextApproverFromChain($chain, $nextLevel);

            query(
                "UPDATE requisitions SET current_approval_level = ?, updated_at = NOW()
                  WHERE requisition_id = ?",
                [$nextLevel, $reqId]
            );

            if ($nextApprover) {
                query(
                    "INSERT INTO approval_history (requisition_id, approver_id, level_id, decision)
                     VALUES (?, ?, ?, 'pending')",
                    [$reqId, $nextApprover['user_id'], $nextLevel]
                );
                query(
                    "INSERT INTO notifications (user_id, requisition_id, notification_type, message)
                     VALUES (?, ?, 'approval', ?)",
                    [
                        $nextApprover['user_id'], $reqId,
                        "{$reqNumber} approved by " . _chainLevelLabel($reqType, $currentLevel)
                        . " — requires your approval (Level {$nextLevel}).",
                    ]
                );
            }
            auditLog('APPROVE', 'requisitions', $reqId,
                "Approved at level {$currentLevel}, advanced to {$nextLevel}");
        }
    }
}

/**
 * Submit a new requisition: inserts the row, builds the chain, creates the
 * first approval_history row, notifies the first approver (or auto-approves
 * if the chain is empty — MD submitting).
 *
 * Returns the new requisition_id.
 */
function submitRequisition(array $form, array $user): int {
    $reqNumber   = generateReqNumber();
    $submitterId = (int)$user['user_id'];
    $deptId      = (int)$form['department_id'];
    $reqType     = $form['type'];

    // Build chain to determine starting level
    $chain      = buildApprovalChain($reqType, $submitterId, $deptId);
    $firstLevel = firstActiveLevel($chain);

    // MD submitting → immediately approved
    $initialStatus = ($firstLevel === null) ? 'approved' : 'pending';
    $startLevel    = $firstLevel ?? 4; // store 4 as a placeholder for auto-approved

    query(
        "INSERT INTO requisitions
           (requisition_number, requisition_type, current_status, priority,
            requester_id, department_id, date_required, description,
            total_amount, current_approval_level,
            position_title, employment_type, budget_code)
         VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)",
        [
            $reqNumber,
            $reqType,
            $initialStatus,
            $form['priority'],
            $submitterId,
            $deptId ?: null,
            $form['date_required'],
            $form['description'],
            $form['total_amount'] ?? 0,
            $startLevel,
            $form['title']                                                    ?? null,
            ($form['employment_type'] ?? '') !== '' ? $form['employment_type'] : null,
            $form['budget_code']                                              ?? null,
        ]
    );
    $reqId = (int)lastInsertId();

    // Insert line items
    if (!empty($form['items'])) {
        foreach ($form['items'] as $item) {
            query(
                "INSERT INTO requisition_items
                   (requisition_id, item_description, quantity, unit_cost, catalog_id, is_custom)
                 VALUES (?, ?, ?, ?, ?, ?)",
                [
                    $reqId,
                    $item['item_description'],
                    $item['quantity'],
                    $item['unit_cost'],
                    $item['catalog_id'] ?? null,
                    $item['is_custom']  ?? 0,
                ]
            );
        }
    }

    if ($firstLevel === null) {
        // Auto-approved (MD submitted)
        query(
            "UPDATE requisitions
                SET final_approver_id = ?, final_decision_date = NOW()
              WHERE requisition_id = ?",
            [$submitterId, $reqId]
        );
        auditLog('APPROVE', 'requisitions', $reqId,
            "Auto-approved — submitted by Managing Director");
    } else {
        // Create pending row for first approver
        $firstApproverId = approverAtLevel($chain, $firstLevel);
        if ($firstApproverId) {
            query(
                "INSERT INTO approval_history (requisition_id, approver_id, level_id, decision)
                 VALUES (?, ?, ?, 'pending')",
                [$reqId, $firstApproverId, $firstLevel]
            );
            query(
                "INSERT INTO notifications (user_id, requisition_id, notification_type, message)
                 VALUES (?, ?, 'submission', ?)",
                [
                    $firstApproverId, $reqId,
                    "New requisition {$reqNumber} requires your approval.",
                ]
            );
        }
        auditLog('CREATE', 'requisitions', $reqId, "Submitted {$reqNumber}");
    }

    return $reqId;
}
