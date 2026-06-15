<?php
// session, authentication, role helpers

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Checks 

function isLoggedIn(): bool {
    return isset($_SESSION['user_id']);
}

function requireLogin(): void {
    if (!isLoggedIn()) {
        header('Location: /reqon/login.php');
        exit;
    }
}

function currentUser(): array {
    return $_SESSION['user'] ?? [];
}

/**
 * Check role by role_name stored in session.
 * Accepts shorthand aliases: 'admin', 'approver', 'requester', 'hr admin'
 */
function hasRole(string ...$roles): bool {
    $roleName = strtolower($_SESSION['user']['role_name'] ?? '');
    foreach ($roles as $r) {
        $r = strtolower($r);
        if ($r === $roleName) return true;
        if ($r === 'admin'     && $roleName === 'system admin') return true;
        if ($r === 'approver'  && $roleName === 'approver')     return true;
        if ($r === 'requester' && $roleName === 'requester')    return true;
        if ($r === 'hr admin'  && $roleName === 'hr admin')     return true;
    }
    return false;
}

//  Login 

/**
 * Attempts login. Returns ['ok'=>true,'user'=>[...]] or ['ok'=>false,'error'=>'...']
 */
function attemptLogin(string $email, string $password): array {
    $db   = getDB();
    $stmt = $db->prepare("
        SELECT u.*, r.role_name, d.department_name
          FROM users u
          LEFT JOIN roles       r ON r.role_id       = u.role_id
          LEFT JOIN departments d ON d.department_id = u.department_id
         WHERE u.email = ?
         LIMIT 1
    ");
    $stmt->execute([trim($email)]);
    $user = $stmt->fetch();

    if (!$user) {
        return ['ok' => false, 'error' => 'Invalid email or password.'];
    }
    if (!password_verify($password, $user['password_hash'])) {
        return ['ok' => false, 'error' => 'Invalid email or password.'];
    }

    $roleId        = (int)$user['role_id'];
    $section       = $user['section'] ?? '';
    $deptId        = (int)($user['department_id'] ?? 0);
    $deptName      = $user['department_name'] ?? '';
    $approvalLevel = _computeApprovalLevel($roleId, $section, $deptId);
    $roleLabel     = _buildRoleLabelWithDept(_computeRoleLabel($roleId, $approvalLevel), $deptName);

    $_SESSION['user_id'] = $user['user_id'];
    $_SESSION['user']    = [
        'user_id'         => $user['user_id'],
        'id'              => $user['user_id'],
        'full_name'       => $user['full_name'],
        'name'            => $user['full_name'],
        'email'           => $user['email'],
        'role_id'         => $roleId,
        'role_name'       => $user['role_name'] ?? '',
        'role_label'      => $roleLabel,
        'department_id'   => $user['department_id'],
        'department_name' => $user['department_name'] ?? '',
        'section'         => $section,
        'approval_level'  => $approvalLevel,
    ];

    $db->prepare("UPDATE users SET last_login = NOW() WHERE user_id = ?")
       ->execute([$user['user_id']]);

    return ['ok' => true, 'user' => $_SESSION['user']];
}

/**
 * Compute approval level (1–4) from role + section + department.
 * Returns 0 if the user has no approval authority.
 *
 *   role_id=1  System Admin → 0
 *   role_id=2  HR Admin     → 2  (HR Director)
 *   role_id=3  Approver     → 1 (Dept Head) / 3 (Finance Dir) / 4 (MD)
 *   role_id=4  Requester    → 0
 */
function _computeApprovalLevel(int $roleId, string $section, int $deptId): int {
    if ($roleId === 2) return 2;
    if ($roleId === 3) {
        if ($section && stripos($section, 'Dept Head') !== false) return 1;
        if ($deptId === 3) return 3;
        return 4;
    }
    return 0;
}

/**
 * Role label for the header, prefixed with department name.
 * Admin (role_id=1) gets no prefix.
 */
function _computeRoleLabel(int $roleId, int $approvalLevel): string {
    if ($roleId === 1) return 'System Admin';
    if ($roleId === 2) return 'HR Director';
    if ($roleId === 4) return 'Requester';
    if ($roleId === 3) {
        if ($approvalLevel === 1) return 'Dept Head';
        if ($approvalLevel === 3) return 'Finance Director';
        if ($approvalLevel === 4) return 'Managing Director';
        return 'Approver';
    }
    return 'User';
}

/**
 * Prepend department name to a role label.
 * Called after session is built so department_name is available.
 * Admin gets no prefix. Empty dept falls back to role label only.
 */
function _buildRoleLabelWithDept(string $roleLabel, string $deptName): string {
    if ($roleLabel === 'System Admin') return 'System Admin';
    if ($deptName === '') return $roleLabel;
    return $deptName . ' · ' . $roleLabel;
}

// Logout

function logout(): void {
    $_SESSION = [];
    if (ini_get('session.use_cookies')) {
        $p = session_get_cookie_params();
        setcookie(session_name(), '', time() - 42000,
            $p['path'], $p['domain'], $p['secure'], $p['httponly']
        );
    }
    session_destroy();
    header('Location: /reqon/login.php');
    exit;
}

// Post-login redirect 

/**
 * Admin → admin/dashboard.php
 * Approvers → approvals/queue.php
 * Everyone else → dashboard.php
 */
function redirectAfterLogin(): void {
    $roleId = $_SESSION['user']['role_id']      ?? 0;
    $level  = $_SESSION['user']['approval_level'] ?? 0;
    if ($roleId === 1) {
        header('Location: /reqon/admin/dashboard.php');
    } elseif ($level > 0) {
        header('Location: /reqon/approvals/queue.php');
    } else {
        header('Location: /reqon/dashboard.php');
    }
    exit;
}
