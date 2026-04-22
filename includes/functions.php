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
 * Format a number as Kenyan Shillings: KES 12,500.00
 */
function formatKES(float $amount): string {
    return 'KES ' . number_format($amount, 2);
}
 
// ── Badge HTML ────────────────────────────────────────────────────────────
 
/**
 * Return a styled <span> badge for a requisition status.
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
 * Return a styled <span> badge for priority.
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
 
/**
 * Human-readable name for each approval level (matches your 4-level chain).
 */
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
    $userId = $_SESSION['user']['id'] ?? null;
    $ip     = $_SERVER['REMOTE_ADDR'] ?? '';
    query(
        "INSERT INTO audit_log (user_id, action, table_name, record_id, details, ip_address)
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