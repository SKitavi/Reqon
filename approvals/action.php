<?php
// Accepts POST only. Processes an approve or reject decision, then redirects.

require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../includes/auth.php';

requireLogin();

// Only accept POST
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    redirect(BASE_URL . '/approvals/queue.php');
}

$user      = currentUser();
$userLevel = getRoleLevel($user);

// Check the user actually has an approval role
if ($userLevel === 0) {
    setFlash('error', 'You do not have permission to approve or reject requisitions.');
    redirect(BASE_URL . '/dashboard.php');
}

// Collect and validate inputs
$reqId    = (int)post('requisition_id');
$action   = post('action'); // 'approve' | 'reject'
$comments = post('comments');

if (!$reqId || !in_array($action, ['approve', 'reject'], true)) {
    setFlash('error', 'Invalid request.');
    redirect(BASE_URL . '/approvals/queue.php');
}

// Load the requisition
$req = fetchOne("SELECT * FROM requisitions WHERE requisition_id = ?", [$reqId]);

if (!$req) {
    setFlash('error', 'Requisition not found.');
    redirect(BASE_URL . '/approvals/queue.php');
}

// Use effective level (handles Mary's dual role)
$effectiveLevel = getEffectiveApprovalLevel($user, $req);

// Make sure this req is actually at the user's effective level
if ((int)$req['current_approval_level'] !== $effectiveLevel) {
    setFlash('error', 'This requisition is not currently at your approval level.');
    redirect(BASE_URL . '/approvals/queue.php');
}

// Make sure it's still pending
if ($req['current_status'] !== 'pending') {
    setFlash('error', 'This requisition has already been ' . $req['current_status'] . '.');
    redirect(BASE_URL . '/approvals/queue.php');
}

// Reject requires a reason
if ($action === 'reject' && empty(trim($comments))) {
    setFlash('error', 'Please provide a reason for rejecting.');
    redirect(BASE_URL . '/approvals/queue.php?highlight=' . $reqId);
}

// Process the decision
processApprovalDecision($action, $reqId, (int)$user['user_id'], $comments);

// Set a friendly flash message
$reqNumber = $req['requisition_number'];
if ($action === 'approve') {
    $nextLevel = $effectiveLevel + 1;
    $msg = $nextLevel > 4
        ? "{$reqNumber} has been fully approved."
        : "{$reqNumber} approved and forwarded to " . approvalLevelLabelForType($req['requisition_type'], $nextLevel) . ".";
} else {
    $msg = "{$reqNumber} has been rejected.";
}
setFlash('success', $msg);

$dest = ((int)($user['approval_level'] ?? 0) > 0)
    ? BASE_URL . '/approvals/queue.php'
    : BASE_URL . '/dashboard.php';
redirect($dest);