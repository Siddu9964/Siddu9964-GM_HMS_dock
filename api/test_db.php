<?php
require_once __DIR__ . '/../core/Autoloader.php';

use GM_HMS\Database\SecureDatabase;

header('Content-Type: application/json');

try {
    $db = SecureDatabase::getInstance();
    $ids = [13, 12, 11, 10];
    
    $placeholders = implode(',', array_fill(0, count($ids), '?'));
    $query = "SELECT id, receive_no, status FROM ph_stock_receive WHERE id IN ($placeholders)";
    
    $result = $db->fetchAll($query, array_values($ids));
    echo json_encode([
        'success' => true,
        'db_name' => 'hmsci',
        'result' => $result
    ]);
} catch (Exception $e) {
    echo json_encode([
        'success' => false,
        'error' => $e->getMessage()
    ]);
}
