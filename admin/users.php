<?php
// admin/users.php — System Admin: view, add, and edit users
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../includes/auth.php';

requireLogin();
if (($user = currentUser())['role_id'] !== 1) {
    setFlash('error', 'Access denied.');
    redirect(BASE_URL . '/dashboard.php');
}

$departments = fetchAll("SELECT department_id, department_name FROM departments ORDER BY department_name");
$roles       = fetchAll("SELECT role_id, role_name FROM roles ORDER BY role_id");
$errors      = [];
$success     = '';

// ── Handle POST (add or edit) ─────────────────────────────────────────────
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $editId       = (int)post('edit_user_id');
    $fullName     = post('full_name');
    $username     = post('username');
    $email        = post('email');
    $password     = post('password');
    $roleId       = (int)post('role_id');
    $deptId       = (int)post('department_id') ?: null;
    $section      = post('section');
    $phone        = post('phone_number');
    $status       = post('status', 'active');

    // Validation
    if (!$fullName)  $errors[] = 'Full name is required.';
    if (!$username)  $errors[] = 'Username is required.';
    if (!$email || !filter_var($email, FILTER_VALIDATE_EMAIL)) $errors[] = 'A valid email is required.';
    if (!$roleId)    $errors[] = 'Please select a role.';
    if (!$editId && !$password) $errors[] = 'Password is required for new users.';
    if ($password && strlen($password) < 8) $errors[] = 'Password must be at least 8 characters.';

    if (empty($errors)) {
        if ($editId) {
            // Check username/email uniqueness (excluding self)
            $dup = fetchOne("SELECT user_id FROM users WHERE (username = ? OR email = ?) AND user_id != ?", [$username, $email, $editId]);
            if ($dup) { $errors[] = 'Username or email already taken by another user.'; }
            else {
                if ($password) {
                    query("UPDATE users SET full_name=?, username=?, email=?, password_hash=?,
                                role_id=?, department_id=?, section=?, phone_number=?, status=?
                           WHERE user_id=?",
                        [$fullName, $username, $email, password_hash($password, PASSWORD_BCRYPT, ['cost'=>12]),
                         $roleId, $deptId, $section ?: null, $phone ?: null, $status, $editId]);
                } else {
                    query("UPDATE users SET full_name=?, username=?, email=?,
                                role_id=?, department_id=?, section=?, phone_number=?, status=?
                           WHERE user_id=?",
                        [$fullName, $username, $email,
                         $roleId, $deptId, $section ?: null, $phone ?: null, $status, $editId]);
                }
                auditLog('UPDATE', 'users', $editId, "Updated user: {$username}");
                setFlash('success', "User {$fullName} updated.");
                redirect(BASE_URL . '/admin/users.php');
            }
        } else {
            $dup = fetchOne("SELECT user_id FROM users WHERE username = ? OR email = ?", [$username, $email]);
            if ($dup) { $errors[] = 'Username or email already exists.'; }
            else {
                query("INSERT INTO users (full_name, username, email, password_hash, role_id, department_id, section, phone_number, status)
                       VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)",
                    [$fullName, $username, $email,
                     password_hash($password, PASSWORD_BCRYPT, ['cost'=>12]),
                     $roleId, $deptId, $section ?: null, $phone ?: null, $status]);
                $newId = (int)lastInsertId();
                auditLog('CREATE', 'users', $newId, "Created user: {$username}");
                setFlash('success', "User {$fullName} created successfully.");
                redirect(BASE_URL . '/admin/users.php');
            }
        }
    }
}

// Pre-fill form for editing
$editUser = null;
if ($editId = (int)get('edit')) {
    $editUser = fetchOne("SELECT * FROM users WHERE user_id = ?", [$editId]);
}

// Users list
$users = fetchAll(
    "SELECT u.*, r.role_name, d.department_name
       FROM users u
       LEFT JOIN roles r ON r.role_id = u.role_id
       LEFT JOIN departments d ON d.department_id = u.department_id
      ORDER BY u.full_name"
);

$pageTitle = 'User Management';
include __DIR__ . '/../includes/header.php';
?>
<div class="page-wrap">

  <div class="page-header">
    <h1 class="page-title">User Management</h1>
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

  <div style="display:grid;grid-template-columns:1fr 360px;gap:24px;align-items:start">

    <!-- Users table -->
    <div class="card">
      <div class="card-header"><h2 class="card-title">All Users (<?= count($users) ?>)</h2></div>
      <div class="table-wrap">
        <table class="req-table">
          <thead>
            <tr>
              <th>Name</th>
              <th>Username</th>
              <th>Email</th>
              <th>Role</th>
              <th>Department</th>
              <th>Status</th>
              <th>Last Login</th>
              <th></th>
            </tr>
          </thead>
          <tbody>
            <?php foreach ($users as $u): ?>
            <tr <?= $u['status'] === 'inactive' ? 'style="opacity:.55"' : '' ?>>
              <td><?= e($u['full_name']) ?></td>
              <td style="font-family:monospace;font-size:12px"><?= e($u['username']) ?></td>
              <td style="font-size:12px"><?= e($u['email']) ?></td>
              <td><span class="badge badge-pending"><?= e($u['role_name']) ?></span></td>
              <td style="font-size:12px"><?= e($u['department_name'] ?? '—') ?></td>
              <td>
                <?php if ($u['status'] === 'active'): ?>
                  <span style="color:#27ae60;font-size:12px;font-weight:600">● Active</span>
                <?php else: ?>
                  <span style="color:#e74c3c;font-size:12px;font-weight:600">● Inactive</span>
                <?php endif; ?>
              </td>
              <td style="font-size:12px;color:var(--text-muted)"><?= $u['last_login'] ? e(formatDate($u['last_login'], 'd/m/Y')) : '—' ?></td>
              <td>
                <a href="?edit=<?= $u['user_id'] ?>" class="btn btn-outline btn-sm">Edit</a>
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
        <?= $editUser ? 'Edit User' : 'Add New User' ?>
      </h2>
      <form method="POST" action="">
        <?php if ($editUser): ?>
          <input type="hidden" name="edit_user_id" value="<?= $editUser['user_id'] ?>">
        <?php endif; ?>

        <div class="field">
          <label>Full Name <span class="required">*</span></label>
          <input type="text" name="full_name" value="<?= e($editUser['full_name'] ?? post('full_name')) ?>" required>
        </div>
        <div class="field">
          <label>Username <span class="required">*</span></label>
          <input type="text" name="username" value="<?= e($editUser['username'] ?? post('username')) ?>" required>
        </div>
        <div class="field">
          <label>Email <span class="required">*</span></label>
          <input type="email" name="email" value="<?= e($editUser['email'] ?? post('email')) ?>" required>
        </div>
        <div class="field">
          <label>Password <?= $editUser ? '' : '<span class="required">*</span>' ?></label>
          <input type="password" name="password" autocomplete="new-password"
                 placeholder="<?= $editUser ? 'Leave blank to keep current' : 'Min. 8 characters' ?>">
        </div>
        <div class="field">
          <label>Role <span class="required">*</span></label>
          <select name="role_id" required>
            <option value="">— Select role —</option>
            <?php foreach ($roles as $r): ?>
              <option value="<?= $r['role_id'] ?>"
                <?= (int)($editUser['role_id'] ?? 0) === (int)$r['role_id'] ? 'selected' : '' ?>>
                <?= e($r['role_name']) ?>
              </option>
            <?php endforeach; ?>
          </select>
        </div>
        <div class="field">
          <label>Department</label>
          <select name="department_id">
            <option value="">— None —</option>
            <?php foreach ($departments as $d): ?>
              <option value="<?= $d['department_id'] ?>"
                <?= (int)($editUser['department_id'] ?? 0) === (int)$d['department_id'] ? 'selected' : '' ?>>
                <?= e($d['department_name']) ?>
              </option>
            <?php endforeach; ?>
          </select>
        </div>
        <div class="field">
          <label>Section <span style="font-size:11px;color:var(--text-muted)">(e.g. "IT Dept Head")</span></label>
          <input type="text" name="section" value="<?= e($editUser['section'] ?? '') ?>"
                 placeholder="e.g. IT Dept Head, Supply Chain">
          <p class="field-hint">Used to determine approval level for Approver role.</p>
        </div>
        <div class="field">
          <label>Phone Number</label>
          <input type="text" name="phone_number" value="<?= e($editUser['phone_number'] ?? '') ?>"
                 placeholder="+254700000000">
        </div>
        <div class="field">
          <label>Status</label>
          <select name="status">
            <option value="active"   <?= ($editUser['status'] ?? 'active') === 'active'   ? 'selected' : '' ?>>Active</option>
            <option value="inactive" <?= ($editUser['status'] ?? '') === 'inactive' ? 'selected' : '' ?>>Inactive</option>
          </select>
        </div>

        <div class="form-actions" style="justify-content:flex-end;gap:8px;margin-top:4px">
          <?php if ($editUser): ?>
            <a href="<?= BASE_URL ?>/admin/users.php" class="btn btn-outline btn-sm">Cancel</a>
          <?php endif; ?>
          <button type="submit" class="btn btn-dark">
            <?= $editUser ? 'Save Changes' : 'Create User' ?>
          </button>
        </div>
      </form>
    </div>

  </div>
</div>
<?php include __DIR__ . '/../includes/footer.php'; ?>
