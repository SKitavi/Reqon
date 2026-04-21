<?php
// include this at the top of every authenticated page AFTER calling requireLogin()
// The $pageTitle variable should be set before including this file.

$pageTitle = $pageTitle ?? 'Reqon';
$user      = currentUser();

// Unread notification count (simple query)
$db              = getDB();
$notifStmt       = $db->prepare("SELECT COUNT(*) FROM notifications WHERE user_id = ? AND is_read = 0");
$notifStmt->execute([$user['id']]);
$unreadCount     = (int) $notifStmt->fetchColumn();
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
    <a href="/reqon/dashboard.php" class="brand-name">Reqon</a>
  </div>

  <!-- Search -->
  <div class="nav-search" role="search">
    <span class="search-icon" aria-hidden="true">
      <svg width="14" height="14" viewBox="0 0 24 24" fill="none"
           stroke="currentColor" stroke-width="2" stroke-linecap="round">
        <circle cx="11" cy="11" r="8"/><line x1="21" y1="21" x2="16.65" y2="16.65"/>
      </svg>
    </span>
    <input
      type="search"
      placeholder="Search requisitions..."
      aria-label="Search requisitions"
      id="global-search"
      onkeydown="if(event.key==='Enter') window.location='/reqon/requisitions/list.php?q='+encodeURIComponent(this.value)"
    >
  </div>

  <!-- Right side -->
  <div class="nav-right">

    <!-- Notification bell -->
    <a href="#" class="nav-bell" aria-label="Notifications (<?= $unreadCount ?> unread)">
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
      <span class="user-name"><?= htmlspecialchars($user['name']) ?></span>
      <span class="chevron" aria-hidden="true">▾</span>

      <div class="nav-dropdown" id="user-dropdown" role="menu">
        <a href="/reqon/dashboard.php" role="menuitem">Dashboard</a>
        <?php if (hasRole('dept_head','hr_director','finance_director','managing_director')): ?>
          <a href="/reqon/approvals/queue.php" role="menuitem">Approval Queue</a>
        <?php endif; ?>
        <div class="divider"></div>
        <a href="/reqon/logout.php" class="logout-link" role="menuitem">Log out</a>
      </div>
    </div>

  </div>
</nav>

<main>