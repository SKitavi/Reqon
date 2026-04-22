<?php
// Accepts POST only. Processes an approve or reject decision, then redirects.

require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../includes/auth.php';

requireLogin();

// Only accept POST
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    redirect(BASE_URL . '/approvals/queue.php');
}

$user     = currentUser();
$userRole = $user['role'] ?? '';

// Check the user actually has an approval role
$userLevel = getRoleLevel($userRole);
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
$req = fetchOne("SELECT * FROM requisitions WHERE id = ?", [$reqId]);

if (!$req) {
    setFlash('error', 'Requisition not found.');
    redirect(BASE_URL . '/approvals/queue.php');
}

// Make sure this req is actually at the user's level
if ((int)$req['current_approval_level'] !== $userLevel) {
    setFlash('error', 'This requisition is not currently at your approval level.');
    redirect(BASE_URL . '/approvals/queue.php');
}

// Make sure it's still pending
if ($req['status'] !== 'pending') {
    setFlash('error', 'This requisition has already been ' . $req['status'] . '.');
    redirect(BASE_URL . '/approvals/queue.php');
}

// Reject requires a reason
if ($action === 'reject' && empty(trim($comments))) {
    setFlash('error', 'Please provide a reason for rejecting.');
    redirect(BASE_URL . '/approvals/queue.php?highlight=' . $reqId);
}

// Process the decision
processApprovalDecision($action, $reqId, (int)$user['id'], $comments);

// Set a friendly flash message
$reqNumber = $req['req_number'];
if ($action === 'approve') {
    $nextLevel = (int)$req['current_approval_level'] + 1;
    if ($nextLevel > 4) {
        setFlash('success', "{$reqNumber} has been fully approved.");
    } else {
        setFlash('success', "{$reqNumber} approved and forwarded to " . approvalLevelLabel($nextLevel) . ".");
    }
} else {
    setFlash('success', "{$reqNumber} has been rejected.");
}

redirect(BASE_URL . '/approvals/queue.php');