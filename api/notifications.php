<?php
// api/notifications.php
// Lightweight AJAX endpoint. Called by the notifications page JS.
// Accepts POST with action=mark_read&id=N

require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../includes/auth.php';

header('Content-Type: application/json');

if (!isLoggedIn()) {
    http_response_code(401);
    echo json_encode(['ok' => false, 'error' => 'Unauthenticated']);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['ok' => false, 'error' => 'POST required']);
    exit;
}

$uid    = (int)currentUser()['user_id'];
$action = post('action');

if ($action === 'mark_read') {
    $id = (int)post('id');
    if (!$id) {
        echo json_encode(['ok' => false, 'error' => 'Missing id']);
        exit;
    }
    // Only mark notifications that belong to this user
    query(
        "UPDATE notifications SET read_status = 'read'
          WHERE notification_id = ? AND user_id = ?",
        [$id, $uid]
    );
    echo json_encode(['ok' => true]);

} elseif ($action === 'unread_count') {
    // Used by header bell badge — call this on page load if you want live count
    $row = fetchOne(
        "SELECT COUNT(*) AS cnt FROM notifications WHERE user_id = ? AND read_status = 'unread'",
        [$uid]
    );
    echo json_encode(['ok' => true, 'count' => (int)($row['cnt'] ?? 0)]);
 
} else {
    http_response_code(400);
    echo json_encode(['ok' => false, 'error' => 'Unknown action']);
}