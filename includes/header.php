<?php
// includes/header.php
$pageTitle = $pageTitle ?? 'Reqon';
$user      = currentUser();

// Unread notification count
$notifStmt = getDB()->prepare(
    "SELECT COUNT(*) FROM notifications WHERE user_id = ? AND read_status = 'unread'"
);
$notifStmt->execute([$user['user_id']]);
$unreadCount = (int)$notifStmt->fetchColumn();

$userLevel  = getRoleLevel($user);
$isApprover = $userLevel > 0;
$isAdmin    = ($user['role_id'] ?? 0) == 1;

// Role label shown next to name 
$roleLabel  = $user['role_label'] ?? ($isAdmin ? 'Admin' : ($isApprover ? 'Approver' : 'Requester'));
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title><?= htmlspecialchars($pageTitle) ?> — Reqon</title>
  <link rel="stylesheet" href="/reqon/assets/css/style.css">
</head>
<body>

<nav class="topnav" role="navigation" aria-label="Main navigation">

  <!-- Brand -->
  <div class="nav-brand">
    <span class="logo-box">ISUZU EA</span>
    <a href="<?= $isAdmin ? BASE_URL . '/admin/dashboard.php' : BASE_URL . '/dashboard.php' ?>"
       class="brand-name">Reqon</a>
  </div>

  <!-- Spacer (search removed — lives on list pages only) -->
  <div class="nav-spacer" style="flex:1"></div>

  <!-- Right side -->
  <div class="nav-right">

    <!-- Notification bell -->
    <a href="<?= BASE_URL ?>/notifications/index.php" class="nav-bell"
       aria-label="Notifications (<?= $unreadCount ?> unread)">
      <svg width="20" height="20" viewBox="0 0 24 24" fill="none"
           stroke="currentColor" stroke-width="1.8" stroke-linecap="round">
        <path d="M18 8A6 6 0 0 0 6 8c0 7-3 9-3 9h18s-3-2-3-9"/>
        <path d="M13.73 21a2 2 0 0 1-3.46 0"/>
      </svg>
      <?php if ($unreadCount > 0): ?>
        <span class="badge"><?= $unreadCount > 9 ? '9+' : $unreadCount ?></span>
      <?php endif; ?>
    </a>

    <!-- User dropdown -->
    <div class="nav-user" id="user-menu-btn"
         onclick="toggleUserMenu()" aria-haspopup="true" aria-expanded="false">
      <div class="user-icon" aria-hidden="true">
        <svg width="16" height="16" viewBox="0 0 24 24" fill="none"
             stroke="currentColor" stroke-width="1.8" stroke-linecap="round">
          <path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"/>
          <circle cx="12" cy="7" r="4"/>
        </svg>
      </div>
      <!-- Name + role label -->
      <span class="user-name">
        <?= e($user['full_name'] ?? $user['name'] ?? '') ?>
        <span class="user-role-label">&middot; <?= e($roleLabel) ?></span>
      </span>
      <span class="chevron" aria-hidden="true">▾</span>

      <div class="nav-dropdown" id="user-dropdown" role="menu">
        <?php if ($isAdmin): ?>
          <a href="<?= BASE_URL ?>/admin/dashboard.php" role="menuitem">Admin Dashboard</a>
        <?php else: ?>
          <a href="<?= BASE_URL ?>/dashboard.php" role="menuitem">Dashboard</a>
        <?php endif; ?>
        <a href="<?= BASE_URL ?>/notifications/index.php" role="menuitem">
          Notifications<?= $unreadCount > 0 ? ' (' . $unreadCount . ')' : '' ?>
        </a>
        <?php if ($isApprover && !$isAdmin): ?>
          <a href="<?= BASE_URL ?>/approvals/queue.php" role="menuitem">Approval Queue</a>
        <?php endif; ?>
        <?php if ((int)($user['user_id'] ?? 0) === APPROVER_PROCUREMENT_HEAD || $isAdmin): ?>
          <a href="<?= BASE_URL ?>/procurement/lpo_queue.php" role="menuitem">LPO Queue</a>
        <?php endif; ?>
        <div class="divider"></div>
        <a href="<?= BASE_URL ?>/logout.php" class="logout-link" role="menuitem">Log out</a>
      </div>
    </div>

  </div>
</nav>

<main>
