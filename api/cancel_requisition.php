<?php
// api/cancel_requisition.php
// Allows the original requester to cancel their own pending requisition.

require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../includes/auth.php';

requireLogin();

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    redirect(BASE_URL . '/dashboard.php');
}

$user  = currentUser();
$reqId = (int)post('requisition_id');

if (!$reqId) {
    setFlash('error', 'Invalid request.');
    redirect(BASE_URL . '/dashboard.php');
}

$req = fetchOne("SELECT * FROM requisitions WHERE id = ?", [$reqId]);

if (!$req) {
    setFlash('error', 'Requisition not found.');
    redirect(BASE_URL . '/dashboard.php');
}

// Only the submitter can cancel, and only while pending
if ((int)$req['submitted_by'] !== (int)$user['id']) {
    setFlash('error', 'You can only cancel your own requisitions.');
    redirect(BASE_URL . '/requisitions/view.php?id=' . $reqId);
}

if ($req['status'] !== 'pending') {
    setFlash('error', 'Only pending requisitions can be cancelled.');
    redirect(BASE_URL . '/requisitions/view.php?id=' . $reqId);
}

query(
    "UPDATE requisitions SET status = 'cancelled', updated_at = NOW() WHERE id = ?",
    [$reqId]
);

auditLog('CANCEL', 'requisitions', $reqId, 'Cancelled by requester');

setFlash('success', $req['req_number'] . ' has been cancelled.');
redirect(BASE_URL . '/dashboard.php');