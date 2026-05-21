<?php
// api/catalog.php — returns catalog items for a given category, optionally filtered by search term
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../includes/auth.php';

header('Content-Type: application/json');

if (!isLoggedIn()) {
    http_response_code(401);
    echo json_encode([]);
    exit;
}

$category = get('category');
$q        = get('q');

$validCategories = ['it_asset', 'procurement', 'merchandise', 'personnel'];
if (!in_array($category, $validCategories, true)) {
    echo json_encode([]);
    exit;
}

$params = [$category];
$sql    = "SELECT catalog_id, item_name, standard_unit_cost, unit_label
             FROM item_catalog
            WHERE category = ? AND is_active = 1";

if ($q !== '') {
    $sql     .= " AND (item_name LIKE ? OR description LIKE ?)";
    $like     = "%{$q}%";
    $params[] = $like;
    $params[] = $like;
}

$sql .= " ORDER BY item_name LIMIT 50";

$rows = fetchAll($sql, $params);
echo json_encode($rows);
