<?php
// Shared utility functions — loaded automatically by config/config.php
 
// ── Output & Security ──────────────────────────────────────────────────────
 
/**
 * Sanitize a value for safe HTML output.
 * Always use this when echoing user-supplied data.
 */
function e(string $value): string {
    return htmlspecialchars($value, ENT_QUOTES, 'UTF-8');
}
 
/**
 * Redirect to a URL and stop execution.
 */
function redirect(string $url): void {
    header('Location: ' . $url);
    exit;
}
 
// ── REQ Number ────────────────────────────────────────────────────────────
 
/**
 * Generate the next sequential REQ number: REQ-001, REQ-002 …
 * Pads to 3 digits; once you pass 999 it keeps growing (REQ-1000).
 */
function generateReqNumber(): string {
    $row = fetchOne("SELECT COUNT(*) AS total FROM requisitions");
    $next = (int)($row['total'] ?? 0) + 1;
    return 'REQ-' . str_pad($next, 3, '0', STR_PAD_LEFT);
}
 
// ── Date helpers ──────────────────────────────────────────────────────────
 
/**
 * Format a MySQL datetime string for display.
 * Default output: 14/04/2026 (DISPLAY_DATE_FORMAT from config)
 */
function formatDate(?string $date, string $format = null): string {
    if (!$date) return '—';
    $format = $format ?? (defined('DISPLAY_DATE_FORMAT') ? DISPLAY_DATE_FORMAT : 'd/m/Y');
    return date($format, strtotime($date));
}
 
/**
 * How long ago was this date? Returns "2 days ago", "just now" etc.
 */
function timeAgo(string $date): string {
    $diff = time() - strtotime($date);
    if ($diff < 60)     return 'just now';
    if ($diff < 3600)   return floor($diff / 60) . 'm ago';
    if ($diff < 86400)  return floor($diff / 3600) . 'h ago';
    if ($diff < 604800) return floor($diff / 86400) . 'd ago';
    return formatDate($date);
}
 
// ── Currency ──────────────────────────────────────────────────────────────
 
/**
 * Format a number as Kenyan Shillings eg. KES 12,500.00
 */
function formatKES(float $amount): string {
    return 'KES ' . number_format($amount, 2);
}
 
// ── Badge HTML ────────────────────────────────────────────────────────────
 
/**
 * Return a styled badge for a requisition status.
 */
function statusBadge(string $status): string {
    $classes = [
        'pending'    => 'badge-pending',
        'in_review'  => 'badge-pending',
        'approved'   => 'badge-approved',
        'rejected'   => 'badge-rejected',
        'cancelled'  => 'badge-cancelled',
    ];
    $cls   = $classes[$status] ?? 'badge-pending';
    $label = ucfirst(str_replace('_', ' ', $status));
    return '<span class="badge ' . $cls . '">' . e($label) . '</span>';
}
 
/**
 * Return a styled badge for priority.
 */
function priorityBadge(string $priority): string {
    $classes = [
        'high'   => 'badge-priority-high',
        'medium' => 'badge-priority-medium',
        'low'    => 'badge-priority-low',
    ];
    $cls = $classes[$priority] ?? 'badge-priority-medium';
    return '<span class="badge ' . $cls . '">PRIORITY: ' . strtoupper(e($priority)) . '</span>';
}
 
// ── Approval level labels ─────────────────────────────────────────────────
 
function approvalLevelLabel(int $level): string {
    $labels = [
        1 => 'Dept Head',
        2 => 'HR Director',
        3 => 'Finance Director',
        4 => 'Managing Director',
    ];
    return $labels[$level] ?? 'Level ' . $level;
}
 
// ── Flash messages ────────────────────────────────────────────────────────
 
/**
 * Store a one-time message in session to display on next page load.
 * Usage: setFlash('success', 'Requisition submitted!'); redirect(...);
 */
function setFlash(string $type, string $message): void {
    $_SESSION['flash'] = ['type' => $type, 'message' => $message];
}
 
/**
 * Render and clear any stored flash message.
 * Call this once near the top of every page body.
 */
function renderFlash(): void {
    if (!isset($_SESSION['flash'])) return;
    $f   = $_SESSION['flash'];
    $cls = $f['type'] === 'success' ? 'alert-success' : 'alert-error';
    echo '<div class="alert ' . $cls . '" role="alert">' . e($f['message']) . '</div>';
    unset($_SESSION['flash']);
}
 
// ── Audit log ─────────────────────────────────────────────────────────────
 
/**
 * Write one row to audit_log.
 * Call this whenever something important changes in the system.
 */
function auditLog(string $action, string $table, int $recordId, string $details = ''): void {
    $userId = $_SESSION['user']['user_id'] ?? $_SESSION['user']['id'] ?? null;
    $ip     = $_SERVER['REMOTE_ADDR'] ?? '';
    query(
        "INSERT INTO audit_log (user_id, action_type, table_affected, record_id, description, ip_address)
         VALUES (?, ?, ?, ?, ?, ?)",
        [$userId, $action, $table, $recordId, $details, $ip]
    );
}
 
// ── Input helpers ─────────────────────────────────────────────────────────
 
/**
 * Return a POST value safely, or a default if it isn't set.
 */
function post(string $key, string $default = ''): string {
    return trim($_POST[$key] ?? $default);
}
 
/**
 * Return a GET value safely.
 */
function get(string $key, string $default = ''): string {
    return trim($_GET[$key] ?? $default);
}

// ── Approval helpers ──────────────────────────────────────────────────────
 
/**
 * Get a user's approval level (1-4) from their user array.
 * Reads the pre-computed value stored in session, or computes it fresh.
 * Returns 0 if the user has no approval authority.
 *
 * Role logic (matches seed data):
 *   role_id=2 (HR Admin)  → Level 2
 *   role_id=3 (Approver)  → Level 1 if section contains 'Dept Head'
 *                         → Level 3 if department_id = 3 (Finance)
 *                         → Level 4 otherwise (MD)
 *   role_id=1,4           → 0 (no approval authority)
 */
function getRoleLevel(array $user): int {
    // Fast path: pre-computed at login
    if (isset($user['approval_level'])) return (int)$user['approval_level'];
 
    $roleId  = (int)($user['role_id'] ?? 0);
    $section = $user['section'] ?? '';
    $deptId  = (int)($user['department_id'] ?? 0);
 
    if ($roleId === 2) return 2;
    if ($roleId === 3) {
        if ($section && stripos($section, 'Dept Head') !== false) return 1;
        if ($deptId === 3) return 3;
        return 4;
    }
    return 0;
}
 
/**
 * Find the next approver user for a given level.
 * SCHEMA: users columns are user_id, full_name, email, role_id, section, department_id
 */
function getNextApprover(int $level, int $deptId): ?array {
    if ($level === 1) {
        // Dept Head — scoped to the requisition's department
        return fetchOne(
            "SELECT user_id, full_name, email FROM users
              WHERE role_id = 3 AND department_id = ? AND section LIKE '%Dept Head%'
              LIMIT 1",
            [$deptId]
        ) ?: null;
    }
    if ($level === 2) {
        // HR Admin (role_id = 2)
        return fetchOne(
            "SELECT user_id, full_name, email FROM users WHERE role_id = 2 LIMIT 1"
        ) ?: null;
    }
    if ($level === 3) {
        // Finance Director: Approver in Finance dept (dept_id=3) with no Dept Head section
        return fetchOne(
            "SELECT user_id, full_name, email FROM users
              WHERE role_id = 3 AND department_id = 3
                AND (section IS NULL OR section NOT LIKE '%Dept Head%')
              LIMIT 1"
        ) ?: null;
    }
    if ($level === 4) {
        // Managing Director: Approver, not Finance, no Dept Head section
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
 
/**
 * Process an approve or reject decision.
 * SCHEMA changes from old:
 *   requisitions:      id→requisition_id, requisition_number→requisition_number,
 *                      submitted_by→requester_id, status→current_status
 *   approval_history:  approval_level→level_id, decided_at→decision_date
 *   notifications:     added notification_type column
 *   audit_log:         action→action_type, table_name→table_affected, details→description
 */
function processApprovalDecision(string $action, int $reqId, int $approverId, string $comments = ''): void {
 
    // Load the requisition using new PK column name
    $req = fetchOne("SELECT * FROM requisitions WHERE requisition_id = ?", [$reqId]);
    if (!$req) return;
 
    $currentLevel = (int)$req['current_approval_level'];
    $reqNumber    = $req['requisition_number'];   // was requisition_number
    $submitterId  = (int)$req['requester_id'];     // was submitted_by
    $deptId       = (int)$req['department_id'];
 
    // 1. Record decision — update existing pending row in approval_history
    //    level_id now maps 1:1 to level number (level_id=1 = level 1, etc.)
    $updated = query(
        "UPDATE approval_history
            SET decision = ?, comments = ?, decision_date = NOW()   -- was decided_at
          WHERE requisition_id = ? AND level_id = ? AND decision = 'pending'",  // was approval_level
        [$action === 'approve' ? 'approved' : 'rejected', $comments, $reqId, $currentLevel]
    );
 
    // Safety net: insert row if it didn't exist
    if ($updated->rowCount() === 0) {
        query(
            "INSERT INTO approval_history
               (requisition_id, approver_id, level_id, decision, comments, decision_date)
             VALUES (?, ?, ?, ?, ?, NOW())",
            [$reqId, $approverId, $currentLevel, $action === 'approve' ? 'approved' : 'rejected', $comments]
        );
    }
 
    // 2. Branch on decision
    if ($action === 'reject') {
 
        // Update status + surface rejection_reason at requisition level
        query(
            "UPDATE requisitions
                SET current_status = 'rejected', rejection_reason = ?, updated_at = NOW()
              WHERE requisition_id = ?",
            [$comments, $reqId]
        );
        // Notify requester — notification_type added in new schema
        query(
            "INSERT INTO notifications (user_id, requisition_id, notification_type, message)
             VALUES (?, ?, 'rejection', ?)",
            [
                $submitterId, $reqId,
                "Your requisition {$reqNumber} was rejected at " . approvalLevelLabel($currentLevel) . ".",
            ]
        );
        auditLog('REJECT', 'requisitions', $reqId, "Rejected at level {$currentLevel}");
 
    } else {
        // Approved
        if ($currentLevel < 4) {
 
            $nextLevel    = $currentLevel + 1;
            $nextApprover = getNextApprover($nextLevel, $deptId);
 
            query(
                "UPDATE requisitions SET current_approval_level = ?, updated_at = NOW()
                  WHERE requisition_id = ?",
                [$nextLevel, $reqId]
            );
 
            if ($nextApprover) {
                // Pending row for next approver — level_id = level number (1:1 in seed)
                query(
                    "INSERT INTO approval_history (requisition_id, approver_id, level_id, decision)
                     VALUES (?, ?, ?, 'pending')",
                    [$reqId, $nextApprover['user_id'], $nextLevel]  // was $nextApprover['id']
                );
                query(
                    "INSERT INTO notifications (user_id, requisition_id, notification_type, message)
                     VALUES (?, ?, 'approval', ?)",
                    [
                        $nextApprover['user_id'], $reqId,
                        "{$reqNumber} approved by " . approvalLevelLabel($currentLevel)
                        . " — requires your approval (Level {$nextLevel}).",
                    ]
                );
            }
            auditLog('APPROVE', 'requisitions', $reqId, "Approved at level {$currentLevel}, advanced to {$nextLevel}");
 
        } else {
            // Level 4 — final approval
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
                    "Congratulations! Your requisition {$reqNumber} has been fully approved.",
                ]
            );
            auditLog('APPROVE', 'requisitions', $reqId, "Final approval — fully approved");
        }
    }
}