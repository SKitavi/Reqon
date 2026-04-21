<?php
//to generate requisition number
function generateReqNumber(PDO $db): string {
    $stmt = $db->query("SELECT COUNT(*) FROM requisitions");
    $count = $stmt->fetchColumn();
    return 'REQ-' . str_pad($count + 1, 3, '0', STR_PAD_LEFT);
}