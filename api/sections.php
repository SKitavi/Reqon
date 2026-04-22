<?php
// api/sections.php
// Returns JSON array of sections for a department.
// Called by the department dropdown in new.php via fetch().

require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../includes/auth.php';

header('Content-Type: application/json');

// Must be logged in
if (!isLoggedIn()) {
    http_response_code(401);
    echo json_encode([]);
    exit;
}

$deptId = (int)($_GET['department_id'] ?? 0);

if (!$deptId) {
    echo json_encode([]);
    exit;
}

$sections = fetchAll(
    "SELECT id, name FROM sections WHERE department_id = ? ORDER BY name",
    [$deptId]
);

echo json_encode($sections);