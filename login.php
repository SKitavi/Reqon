<?php
// login.php - Screen 1
require_once __DIR__ . '/includes/db.php';
require_once __DIR__ . '/includes/auth.php';

// If already logged in, redirect away
if (isLoggedIn()) {
    redirectAfterLogin();
}

$error   = '';
$success = '';

// Handle POST
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email    = trim($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';

    if (empty($email) || empty($password)) {
        $error = 'Please enter both email and password.';
    } else {
        $result = attemptLogin($email, $password);
        if ($result['ok']) {
            redirectAfterLogin();
        } else {
            $error = $result['error'];
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Login — Reqon</title>
  <link rel="stylesheet" href="/reqon/assets/css/style.css">
</head>
<body class="login-page">

  <!-- Brand header (top-left, matching prototype) -->
  <header class="login-brand">
    <div class="brand-logo-box">ISUZU EA</div>
    <div>
      <div class="brand-name">Reqon</div>
      <div class="brand-sub">Requisition Management System</div>
    </div>
  </header>

  <!-- Login card -->
  <main class="login-card-wrap">
    <div class="login-card">

      <?php if ($error): ?>
        <div class="alert alert-error" role="alert"><?= htmlspecialchars($error) ?></div>
      <?php endif; ?>

      <?php if ($success): ?>
        <div class="alert alert-success" role="alert"><?= htmlspecialchars($success) ?></div>
      <?php endif; ?>

      <form method="POST" action="" novalidate>

        <!-- Email -->
        <div class="form-group">
          <label for="email">Email</label>
          <div class="input-wrap">
            <input
              type="email"
              id="email"
              name="email"
              placeholder="Enter your email"
              value="<?= htmlspecialchars($_POST['email'] ?? '') ?>"
              autocomplete="email"
              required
            >
            <!-- envelope icon -->
            <span class="input-icon" aria-hidden="true">
              <svg width="16" height="16" viewBox="0 0 24 24" fill="none"
                   stroke="currentColor" stroke-width="1.8"
                   stroke-linecap="round" stroke-linejoin="round">
                <rect x="2" y="4" width="20" height="16" rx="2"/>
                <path d="M2 7l10 7 10-7"/>
              </svg>
            </span>
          </div>
        </div>

        <!-- Password -->
        <div class="form-group">
          <label for="password">Password</label>
          <div class="input-wrap">
            <input
              type="password"
              id="password"
              name="password"
              placeholder="Enter your password"
              autocomplete="current-password"
              required
            >
            <!-- eye toggle -->
            <button
              type="button"
              class="input-icon"
              onclick="togglePassword()"
              aria-label="Toggle password visibility"
              id="eye-btn"
            >
              <svg id="eye-icon" width="16" height="16" viewBox="0 0 24 24" fill="none"
                   stroke="currentColor" stroke-width="1.8"
                   stroke-linecap="round" stroke-linejoin="round">
                <path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"/>
                <circle cx="12" cy="12" r="3"/>
              </svg>
            </button>
          </div>
        </div>

        <!-- Remember me + Forgot password -->
        <div class="form-row-split">
          <label class="checkbox-label">
            <input type="checkbox" name="remember" value="1">
            Remember Me
          </label>
          <a href="#" class="forgot-link">Forgot Password?</a>
        </div>

        <button type="submit" class="btn-login">Login</button>

      </form>
    </div>
  </main>

  <!-- Footer -->
  <footer class="login-footer">
    <span>IT Department &copy;2026</span>
    <span>v1.0.0</span>
  </footer>

  <script>
    function togglePassword() {
      const input = document.getElementById('password');
      const isText = input.type === 'text';
      input.type = isText ? 'password' : 'text';

      // Swap icon: eye vs eye-off
      document.getElementById('eye-icon').innerHTML = isText
        ? `<path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"/>
           <circle cx="12" cy="12" r="3"/>`
        : `<path d="M17.94 17.94A10.07 10.07 0 0 1 12 20c-7 0-11-8-11-8
              a18.45 18.45 0 0 1 5.06-5.94"/>
           <path d="M9.9 4.24A9.12 9.12 0 0 1 12 4c7 0 11 8 11 8
              a18.5 18.5 0 0 1-2.16 3.19"/>
           <line x1="1" y1="1" x2="23" y2="23"/>`;
    }
  </script>

</body>
</html>