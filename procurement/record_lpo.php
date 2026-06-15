<?php
// POST handler: records the LPO generation in lpo_log, then redirects to the printable LPO document. 

require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../includes/auth.php';

requireLogin();

$user = currentUser();
$uid  = (int)$user['user_id'];

// Only Mary may generate LPOs
if ($uid !== APPROVER_PROCUREMENT_HEAD) {
    setFlash('error', 'Only the Procurement Head may generate LPOs.');
    redirect(BASE_URL . '/procurement/lpo_queue.php');
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    redirect(BASE_URL . '/procurement/lpo_queue.php');
}

$reqId = (int)post('requisition_id');
if (!$reqId) {
    setFlash('error', 'Invalid request.');
    redirect(BASE_URL . '/procurement/lpo_queue.php');
}

$req = fetchOne(
    "SELECT requisition_id, requisition_number, current_status, requisition_type
       FROM requisitions WHERE requisition_id = ?",
    [$reqId]
);

if (!$req) {
    setFlash('error', 'Requisition not found.');
    redirect(BASE_URL . '/procurement/lpo_queue.php');
}
if ($req['current_status'] !== 'approved') {
    setFlash('error', 'LPOs can only be generated for fully approved requisitions.');
    redirect(BASE_URL . '/procurement/lpo_queue.php');
}
if (!in_array($req['requisition_type'], ['procurement','it_asset','merchandise'])) {
    setFlash('error', 'LPOs are only for Procurement, IT Asset or Merchandise types.');
    redirect(BASE_URL . '/procurement/lpo_queue.php');
}

// Check not already generated
$existing = fetchOne(
    "SELECT lpo_id FROM lpo_log WHERE requisition_id = ?", [$reqId]
);

if ($existing) {
    // Already exists — just open the document
    redirect(BASE_URL . '/api/generate_lpo.php?id=' . $reqId);
}

// Record the LPO
$lpoNumber = str_replace('REQ-', 'LPO-', $req['requisition_number']);
query(
    "INSERT INTO lpo_log (requisition_id, lpo_number, generated_by) VALUES (?, ?, ?)",
    [$reqId, $lpoNumber, $uid]
);

auditLog('CREATE', 'lpo_log', $reqId, "Generated {$lpoNumber}");

// Notify the requester
$reqRow = fetchOne(
    "SELECT requester_id, requisition_number FROM requisitions WHERE requisition_id = ?",
    [$reqId]
);
if ($reqRow) {
    query(
        "INSERT INTO notifications (user_id, requisition_id, notification_type, message)
         VALUES (?, ?, 'approval', ?)",
        [
            $reqRow['requester_id'], $reqId,
            "LPO {$lpoNumber} has been generated for your requisition {$reqRow['requisition_number']}.",
        ]
    );
}

// Open the LPO document in a new tab via JS redirect
setFlash('success', "{$lpoNumber} generated successfully.");
?>
<!DOCTYPE html>
<html>
<head><meta charset="UTF-8"></head>
<body>
<script>
  // Open LPO document in new tab, redirect this tab back to queue
  window.open('<?= BASE_URL ?>/api/generate_lpo.php?id=<?= $reqId ?>', '_blank');
  window.location.href = '<?= BASE_URL ?>/procurement/lpo_queue.php';
</script>
<noscript>
  <p>LPO recorded. <a href="<?= BASE_URL ?>/api/generate_lpo.php?id=<?= $reqId ?>" target="_blank">Open LPO</a> |
  <a href="<?= BASE_URL ?>/procurement/lpo_queue.php">Back to queue</a></p>
</noscript>
</body>
</html>
<?php
exit;
