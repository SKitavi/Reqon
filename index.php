<?php
/**
 * Main entry point — redirects to login or dashboard.
 */
require_once __DIR__ . '/config/config.php';
require_once __DIR__ . '/includes/auth.php';

if (isLoggedIn()) {
    redirectAfterLogin();
} else {
    header('Location: /reqon/login.php');
    exit;
}
