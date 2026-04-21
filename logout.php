<?php
// logout.php
require_once __DIR__ . '/includes/db.php';
require_once __DIR__ . '/includes/auth.php';

logout(); // destroys session and redirects to login.php