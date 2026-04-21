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
 
function hasRole(string ...$roles): bool {
    return in_array($_SESSION['user']['role'] ?? '', $roles);
}
 
// ── Login ─────────────────────────────────────────────────────────────────
 
/**
 * Attempts login with email + password.
 * Returns ['ok' => true, 'user' => [...]] or ['ok' => false, 'error' => '...']
 */
function attemptLogin(string $email, string $password): array {
    $db = getDB();
 
    $stmt = $db->prepare("
        SELECT u.*, d.name AS department_name
        FROM users u
        LEFT JOIN departments d ON d.id = u.department_id
        WHERE u.email = ?
        LIMIT 1
    ");
    $stmt->execute([trim($email)]);
    $user = $stmt->fetch();
 
    if (!$user) {
        return ['ok' => false, 'error' => 'Invalid email or password.'];
    }
 
    if (!password_verify($password, $user['password'])) {
        return ['ok' => false, 'error' => 'Invalid email or password.'];
    }
 
    // Store safe fields in session (never store raw password)
    $_SESSION['user_id']  = $user['id'];
    $_SESSION['user']     = [
        'id'              => $user['id'],
        'name'            => $user['name'],
        'email'           => $user['email'],
        'role'            => $user['role'],
        'department_id'   => $user['department_id'],
        'department_name' => $user['department_name'],
    ];
 
    return ['ok' => true, 'user' => $_SESSION['user']];
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
 * After login, send the user to the right page based on their role.
 * Approvers land on the approval queue; everyone else on the dashboard.
 */
function redirectAfterLogin(): void {
    $approverRoles = ['dept_head','hr_director','finance_director','managing_director'];
    if (hasRole(...$approverRoles)) {
        header('Location: /reqon/approvals/queue.php');
    } else {
        header('Location: /reqon/dashboard.php');
    }
    exit;
}