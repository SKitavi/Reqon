<?php
// admin/catalog.php — System Admin: view, add, edit and toggle catalog items
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../includes/auth.php';

requireLogin();
if (($user = currentUser())['role_id'] !== 1) {
    setFlash('error', 'Access denied.');
    redirect(BASE_URL . '/dashboard.php');
}

$errors = [];

// ── Handle POST ───────────────────────────────────────────────────────────
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $editId    = (int)post('edit_catalog_id');
    $itemName  = post('item_name');
    $category  = post('category');
    $desc      = post('description');
    $unitCost  = (float)post('standard_unit_cost');
    $unitLabel = post('unit_label', 'unit');
    $isActive  = (int)post('is_active', '1');

    $validCats = ['it_asset','procurement','merchandise','personnel'];

    if (!$itemName)                            $errors[] = 'Item name is required.';
    if (!in_array($category, $validCats))      $errors[] = 'Please select a valid category.';
    if ($unitCost < 0)                         $errors[] = 'Unit cost cannot be negative.';
    if (!$unitLabel)                           $errors[] = 'Unit label is required.';

    if (empty($errors)) {
        if ($editId) {
            query(
                "UPDATE item_catalog SET item_name=?, category=?, description=?,
                        standard_unit_cost=?, unit_label=?, is_active=?
                  WHERE catalog_id=?",
                [$itemName, $category, $desc ?: null, $unitCost, $unitLabel, $isActive, $editId]
            );
            auditLog('UPDATE', 'item_catalog', $editId, "Updated catalog item: {$itemName}");
            setFlash('success', "Item '{$itemName}' updated.");
        } else {
            query(
                "INSERT INTO item_catalog (item_name, category, description, standard_unit_cost, unit_label, is_active)
                 VALUES (?, ?, ?, ?, ?, ?)",
                [$itemName, $category, $desc ?: null, $unitCost, $unitLabel, $isActive]
            );
            $newId = (int)lastInsertId();
            auditLog('CREATE', 'item_catalog', $newId, "Added catalog item: {$itemName}");
            setFlash('success', "Item '{$itemName}' added to catalog.");
        }
        redirect(BASE_URL . '/admin/catalog.php');
    }
}

// Toggle active status (quick action)
if (get('toggle')) {
    $toggleId = (int)get('toggle');
    $item = fetchOne("SELECT is_active FROM item_catalog WHERE catalog_id = ?", [$toggleId]);
    if ($item) {
        $newStatus = $item['is_active'] ? 0 : 1;
        query("UPDATE item_catalog SET is_active = ? WHERE catalog_id = ?", [$newStatus, $toggleId]);
        auditLog('UPDATE', 'item_catalog', $toggleId, $newStatus ? 'Enabled catalog item' : 'Disabled catalog item');
    }
    redirect(BASE_URL . '/admin/catalog.php');
}

// Pre-fill for edit
$editItem = null;
if ($editId = (int)get('edit')) {
    $editItem = fetchOne("SELECT * FROM item_catalog WHERE catalog_id = ?", [$editId]);
}

// Filter
$filterCat = get('cat');
$validCats = ['it_asset','procurement','merchandise','personnel'];
$catParam  = ($filterCat && in_array($filterCat, $validCats)) ? $filterCat : null;

$items = $catParam
    ? fetchAll("SELECT * FROM item_catalog WHERE category = ? ORDER BY item_name", [$catParam])
    : fetchAll("SELECT * FROM item_catalog ORDER BY category, item_name");

$catCounts = [];
foreach ($validCats as $c) {
    $row = fetchOne("SELECT COUNT(*) AS n FROM item_catalog WHERE category = ?", [$c]);
    $catCounts[$c] = (int)($row['n'] ?? 0);
}

$categoryLabels = [
    'it_asset'    => 'IT Asset',
    'procurement' => 'Procurement',
    'merchandise' => 'Merchandise',
    'personnel'   => 'Personnel',
];

$pageTitle = 'Catalog Management';
include __DIR__ . '/../includes/header.php';
?>
<div class="page-wrap">

  <div class="page-header">
    <h1 class="page-title">Catalog Management</h1>
    <a href="<?= BASE_URL ?>/admin/dashboard.php" class="btn btn-outline btn-sm">← Admin Dashboard</a>
  </div>

  <?php renderFlash(); ?>

  <?php if (!empty($errors)): ?>
    <div class="alert alert-error" role="alert">
      <ul style="margin:0;padding-left:18px">
        <?php foreach ($errors as $e): ?><li><?= e($e) ?></li><?php endforeach; ?>
      </ul>
    </div>
  <?php endif; ?>

  <div style="display:grid;grid-template-columns:1fr 340px;gap:24px;align-items:start">

    <!-- Items list -->
    <div class="card">
      <div class="card-header" style="flex-wrap:wrap;gap:10px">
        <h2 class="card-title">Catalog Items (<?= count($items) ?>)</h2>
        <!-- Category filter pills -->
        <div style="display:flex;gap:6px;flex-wrap:wrap">
          <a href="<?= BASE_URL ?>/admin/catalog.php"
             class="btn btn-sm <?= !$catParam ? 'btn-dark' : 'btn-outline' ?>">All</a>
          <?php foreach ($categoryLabels as $k => $v): ?>
            <a href="?cat=<?= $k ?>"
               class="btn btn-sm <?= $catParam === $k ? 'btn-dark' : 'btn-outline' ?>">
              <?= e($v) ?> <span style="opacity:.7">(<?= $catCounts[$k] ?>)</span>
            </a>
          <?php endforeach; ?>
        </div>
      </div>
      <div class="table-wrap">
        <table class="req-table">
          <thead>
            <tr>
              <th>Item Name</th>
              <th>Category</th>
              <th style="text-align:right">Unit Cost (KES)</th>
              <th>Unit</th>
              <th>Status</th>
              <th></th>
            </tr>
          </thead>
          <tbody>
            <?php foreach ($items as $it): ?>
            <tr <?= !$it['is_active'] ? 'style="opacity:.5"' : '' ?>>
              <td>
                <?= e($it['item_name']) ?>
                <?php if ($it['description']): ?>
                  <div style="font-size:11px;color:var(--text-muted);margin-top:2px"><?= e(mb_strimwidth($it['description'], 0, 70, '…')) ?></div>
                <?php endif; ?>
              </td>
              <td><span class="badge badge-pending"><?= e($categoryLabels[$it['category']] ?? $it['category']) ?></span></td>
              <td style="text-align:right;font-weight:500"><?= number_format((float)$it['standard_unit_cost'], 2) ?></td>
              <td style="font-size:12px;color:var(--text-muted)"><?= e($it['unit_label']) ?></td>
              <td>
                <a href="?toggle=<?= $it['catalog_id'] ?>" title="Toggle active"
                   style="font-size:12px;font-weight:600;color:<?= $it['is_active'] ? '#27ae60' : '#e74c3c' ?>">
                  <?= $it['is_active'] ? '● Active' : '● Inactive' ?>
                </a>
              </td>
              <td>
                <a href="?edit=<?= $it['catalog_id'] ?><?= $catParam ? '&cat='.$catParam : '' ?>"
                   class="btn btn-outline btn-sm">Edit</a>
              </td>
            </tr>
            <?php endforeach; ?>
          </tbody>
        </table>
      </div>
    </div>

    <!-- Add / Edit form -->
    <div class="card" style="padding:20px">
      <h2 class="card-title" style="margin-bottom:16px">
        <?= $editItem ? 'Edit Item' : 'Add Catalog Item' ?>
      </h2>
      <form method="POST" action="">
        <?php if ($editItem): ?>
          <input type="hidden" name="edit_catalog_id" value="<?= $editItem['catalog_id'] ?>">
        <?php endif; ?>

        <div class="field">
          <label>Item Name <span class="required">*</span></label>
          <input type="text" name="item_name"
                 value="<?= e($editItem['item_name'] ?? post('item_name')) ?>" required>
        </div>
        <div class="field">
          <label>Category <span class="required">*</span></label>
          <select name="category" required>
            <option value="">— Select —</option>
            <?php foreach ($categoryLabels as $k => $v): ?>
              <option value="<?= $k ?>"
                <?= ($editItem['category'] ?? $catParam ?? '') === $k ? 'selected' : '' ?>>
                <?= e($v) ?>
              </option>
            <?php endforeach; ?>
          </select>
        </div>
        <div class="field">
          <label>Description</label>
          <textarea name="description" rows="2"
                    placeholder="Specifications, brand, model…"><?= e($editItem['description'] ?? '') ?></textarea>
        </div>
        <div class="field">
          <label>Unit Cost (KES) <span class="required">*</span></label>
          <input type="number" name="standard_unit_cost" min="0" step="0.01"
                 value="<?= number_format((float)($editItem['standard_unit_cost'] ?? 0), 2, '.', '') ?>"
                 required>
        </div>
        <div class="field">
          <label>Unit Label <span class="required">*</span></label>
          <input type="text" name="unit_label"
                 value="<?= e($editItem['unit_label'] ?? 'unit') ?>"
                 placeholder="unit, ream, box, month, point…">
          <p class="field-hint">Shown next to quantity in the form (e.g. "3 reams").</p>
        </div>
        <div class="field">
          <label>Status</label>
          <select name="is_active">
            <option value="1" <?= (int)($editItem['is_active'] ?? 1) === 1 ? 'selected' : '' ?>>Active</option>
            <option value="0" <?= (int)($editItem['is_active'] ?? 1) === 0 ? 'selected' : '' ?>>Inactive (hidden from form)</option>
          </select>
        </div>

        <div class="form-actions" style="justify-content:flex-end;gap:8px;margin-top:4px">
          <?php if ($editItem): ?>
            <a href="<?= BASE_URL ?>/admin/catalog.php<?= $catParam ? '?cat='.$catParam : '' ?>"
               class="btn btn-outline btn-sm">Cancel</a>
          <?php endif; ?>
          <button type="submit" class="btn btn-dark">
            <?= $editItem ? 'Save Changes' : 'Add Item' ?>
          </button>
        </div>
      </form>
    </div>

  </div>
</div>
<?php include __DIR__ . '/../includes/footer.php'; ?>
