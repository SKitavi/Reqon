<?php
// includes/db.php
// Thin shim — the real connection is handled by config/database.php.
// This file is kept for backward compatibility only.
// config/config.php (which loads config/database.php) must be loaded first.
if (!function_exists('getDB')) {
    require_once __DIR__ . '/../config/config.php';
}
