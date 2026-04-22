<?php
// requisitions/view.php — stub (Day 7 will complete this)
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../includes/auth.php';

requireLogin();

$id  = (int)($_GET['id'] ?? 0);
$req = $id ? fetchOne("SELECT * FROM requisitions WHERE id = ?", [$id]) : null;

if (!$req) {
    setFlash('error', 'Requisition not found.');
    redirect(BASE_URL . '/dashboard.php');
}

$pageTitle = $req['req_number'] . ' Details';
include __DIR__ . '/../includes/header.php';
?>

<div class="page-wrap">

  <nav class="breadcrumb">
    <a href="<?= BASE_URL ?>/dashboard.php">Dashboard</a>
    <span class="sep">›</span>
    <span class="current"><?= e($req['req_number']) ?></span>
  </nav>

  <?php renderFlash(); ?>

  <div class="card" style="padding:32px 36px; max-width:720px">
    <h1 class="page-title" style="margin-bottom:8px"><?= e($req['req_number']) ?> Details</h1>
    <p style="color:var(--text-muted);font-size:14px;margin-bottom:24px">
      Submitted <?= formatDate($req['created_at']) ?>
    </p>

    <div style="display:grid;grid-template-columns:1fr 1fr;gap:16px 32px;margin-bottom:24px">
      <div><span style="font-size:12px;color:var(--text-muted)">Status</span><br>
           <?= statusBadge($req['status']) ?></div>
      <div><span style="font-size:12px;color:var(--text-muted)">Priority</span><br>
           <?= priorityBadge($req['priority']) ?></div>
      <div><span style="font-size:12px;color:var(--text-muted)">Type</span><br>
           <strong><?= e(REQUISITION_TYPES[$req['type']] ?? ucfirst($req['type'])) ?></strong></div>
      <div><span style="font-size:12px;color:var(--text-muted)">Date Required</span><br>
           <strong><?= e(formatDate($req['date_required'])) ?></strong></div>
    </div>

    <p style="font-size:14px;color:var(--text-muted);margin-bottom:6px">Justification</p>
    <p style="font-size:14px;line-height:1.7"><?= nl2br(e($req['justification'] ?? '—')) ?></p>

    <div style="margin-top:28px">
      <a href="<?= BASE_URL ?>/dashboard.php" class="btn btn-outline">← Back to Dashboard</a>
    </div>
  </div>

</div>

<?php include __DIR__ . '/../includes/footer.php'; ?>