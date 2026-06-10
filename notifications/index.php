<?php
// notifications/index.php
// Lists all notifications for the current user, newest first.
// Unread items are highlighted; clicking marks them read via AJAX.

require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../includes/auth.php';

requireLogin();

$user = currentUser();
$uid  = (int)$user['user_id'];

// Mark all as read if requested (form POST)
if ($_SERVER['REQUEST_METHOD'] === 'POST' && post('action') === 'mark_all_read') {
    query("UPDATE notifications SET read_status = 'read' WHERE user_id = ?", [$uid]);
    setFlash('success', 'All notifications marked as read.');
    redirect(BASE_URL . '/notifications/index.php');
}

// Pagination
$page    = max(1, (int)get('page', '1'));
$perPage = 30;
$offset  = ($page - 1) * $perPage;

$total = (int)(fetchOne(
    "SELECT COUNT(*) AS cnt FROM notifications WHERE user_id = ?", [$uid]
)['cnt'] ?? 0);

$notifications = fetchAll(
    "SELECT n.*, r.requisition_number   
       FROM notifications n
       LEFT JOIN requisitions r ON r.requisition_id = n.requisition_id
      WHERE n.user_id = ?
      ORDER BY n.sent_date DESC         
      LIMIT ? OFFSET ?",
    [$uid, $perPage, $offset]
);

$unreadCount = (int)(fetchOne(
    "SELECT COUNT(*) AS cnt FROM notifications WHERE user_id = ? AND read_status = 'unread'",
    [$uid]
)['cnt'] ?? 0);

$totalPages = (int)ceil($total / $perPage);

$pageTitle = 'Notifications';
include __DIR__ . '/../includes/header.php';
?>

<div class="page-wrap">

  <div class="page-header">
    <h1 class="page-title">
      Notifications
      <?php if ($unreadCount > 0): ?>
        <span style="font-size:13px;font-weight:400;color:var(--text-muted);margin-left:8px">
          <?= $unreadCount ?> unread
        </span>
      <?php endif; ?>
    </h1>

    <?php if ($unreadCount > 0): ?>
      <form method="POST" action="">
        <input type="hidden" name="action" value="mark_all_read">
        <button type="submit" class="mark-all-btn">Mark all as read</button>
      </form>
    <?php endif; ?>
  </div>

  <?php renderFlash(); ?>

  <div class="card">

    <?php if (empty($notifications)): ?>
      <div class="empty-state">
        <div class="empty-icon" aria-hidden="true">🔔</div>
        <p>No notifications yet.</p>
      </div>

    <?php else: ?>

      <div class="notif-list" role="list">
        <?php foreach ($notifications as $n):
          $isUnread = ($n['read_status'] === 'unread');
          $reqId    = $n['requisition_id'];
          $reqNum   = $n['requisition_number'] ?? null;    
          $link     = $reqId
              ? BASE_URL . '/requisitions/view.php?id=' . $reqId
              : '#';
        ?>

        <a href="<?= e($link) ?>"
           class="notif-item <?= $isUnread ? 'unread' : '' ?>"
           role="listitem"
           data-notif-id="<?= (int)$n['notification_id'] ?>"
           onclick="markRead(<?= (int)$n['notification_id'] ?>)"
           aria-label="<?= $isUnread ? 'Unread: ' : '' ?><?= e($n['message']) ?>">

          <div class="notif-dot" aria-hidden="true"></div>

          <div class="notif-content">
            <div class="notif-message"><?= e($n['message']) ?></div>
            <div class="notif-time"><?= e(timeAgo($n['sent_date'])) ?></div>
          </div>

          <?php if ($reqNum): ?>
            <span class="notif-req-link"><?= e($reqNum) ?> →</span>
          <?php endif; ?>

        </a>

        <?php endforeach; ?>
      </div>

      <!-- Pagination -->
      <?php if ($totalPages > 1): ?>
        <div style="display:flex;align-items:center;justify-content:center;gap:8px;padding:16px;border-top:1px solid var(--border)">
          <?php if ($page > 1): ?>
            <a href="?page=<?= $page-1 ?>" class="btn btn-outline btn-sm">← Prev</a>
          <?php endif; ?>
          <span style="font-size:13px;color:var(--text-muted)">Page <?= $page ?> of <?= $totalPages ?></span>
          <?php if ($page < $totalPages): ?>
            <a href="?page=<?= $page+1 ?>" class="btn btn-outline btn-sm">Next →</a>
          <?php endif; ?>
        </div>
      <?php endif; ?>

    <?php endif; ?>

  </div>
</div>

<script>
// Mark a single notification as read silently via fetch, no page reload.
// The visual change (remove unread class) is immediate.
function markRead(notifId) {
  const el = document.querySelector('[data-notif-id="' + notifId + '"]');
  if (!el || !el.classList.contains('unread')) return;

  el.classList.remove('unread');
  el.querySelector('.notif-dot').style.background = 'var(--border)';

  fetch('<?= BASE_URL ?>/api/notifications.php', {
    method: 'POST',
    headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
    body: 'action=mark_read&id=' + notifId
  });
}
</script>

<?php include __DIR__ . '/../includes/footer.php'; ?>