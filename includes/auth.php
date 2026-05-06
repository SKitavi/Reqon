<?php
// session helpers
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
 
// ── Checks ────────────────────────────────────────────────────────────────
 
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
 * New role_names: 'System Admin', 'HR Admin', 'Approver', 'Requester'
 * Shorthand aliases also accepted: 'admin', 'approver', 'requester'
 */
function hasRole(string ...$roles): bool {
    $roleName = strtolower($_SESSION['user']['role_name'] ?? '');
    foreach ($roles as $r) {
        $r = strtolower($r);
        if ($r === $roleName) return true;
        // Accept shorthand aliases
        if ($r === 'admin'     && $roleName === 'system admin') return true;
        if ($r === 'approver'  && $roleName === 'approver')     return true;
        if ($r === 'requester' && $roleName === 'requester')    return true;
        if ($r === 'hr admin'  && $roleName === 'hr admin')     return true;
    }
    return false;
}
 
// ── Login ─────────────────────────────────────────────────────────────────
 
/**
 * Attempts login with email + password.
 * Returns ['ok' => true, 'user' => [...]] or ['ok' => false, 'error' => '...']
 */
function attemptLogin(string $email, string $password): array {
    $db   = getDB();
    $stmt = $db->prepare("
        SELECT u.*,
               r.role_name,
               d.department_name
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
 
    // Pre-compute approval level once at login so every page can use it cheaply
    $approvalLevel = _computeApprovalLevel(
        (int)$user['role_id'],
        $user['section'] ?? '',
        (int)$user['department_id']
    );
 
    $_SESSION['user_id'] = $user['user_id'];
    $_SESSION['user']    = [
        'user_id'         => $user['user_id'],
        'id'              => $user['user_id'],   
        'full_name'       => $user['full_name'],
        'name'            => $user['full_name'],  
        'email'           => $user['email'],
        'role_id'         => (int)$user['role_id'],
        'role_name'       => $user['role_name'] ?? '',
        'department_id'   => $user['department_id'],
        'department_name' => $user['department_name'] ?? '',
        'section'         => $user['section'] ?? '',
        'approval_level'  => $approvalLevel,
    ];
 
    // Update last_login timestamp
    $db->prepare("UPDATE users SET last_login = NOW() WHERE user_id = ?")
       ->execute([$user['user_id']]);
 
    return ['ok' => true, 'user' => $_SESSION['user']];
}
 
/**
 * Compute the 1-4 approval level from role + section + department.
 * Returns 0 if the user has no approval authority.
 *
 * Role mapping (from roles table seed data):
 *   role_id=1  System Admin  → 0  (manages users, not approvals)
 *   role_id=2  HR Admin      → 2  (Level 2 — HR Director)
 *   role_id=3  Approver      → 1 / 3 / 4  (distinguished by section + dept)
 *   role_id=4  Requester     → 0
 */
function _computeApprovalLevel(int $roleId, string $section, int $deptId): int {
    if ($roleId === 2) return 2; // HR Admin is always Level 2
 
    if ($roleId === 3) {
        // Section field contains 'Dept Head' for Level 1 approvers
        if ($section && stripos($section, 'Dept Head') !== false) return 1;
        // Finance dept (dept_id=3) without Dept Head section = Finance Director (Level 3)
        if ($deptId === 3) return 3;
        // Everything else with role Approver and no section = MD (Level 4)
        return 4;
    }
 
    return 0; // role_id 1 (Admin) or 4 (Requester) — no approval level
}
 
// ── Logout ────────────────────────────────────────────────────────────────
 
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
 
// ── Role → Dashboard redirect ─────────────────────────────────────────────
 
/**
 * After login, redirect approvers to the queue, everyone else to dashboard.
 */
function redirectAfterLogin(): void {
    $level = $_SESSION['user']['approval_level'] ?? 0;
    if ($level > 0) {
        header('Location: /reqon/approvals/queue.php');
    } else {
        header('Location: /reqon/dashboard.php');
    }
    exit;
}