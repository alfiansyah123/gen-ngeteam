<?php
header('Content-Type: application/json');
require_once 'config/cors.php';
require_once 'config/db.php';

try {
    $stmt = $pdo->query("SELECT url FROM domains WHERE active = true ORDER BY id ASC");
    $domains = $stmt->fetchAll(PDO::FETCH_COLUMN);
    
    echo json_encode(['domains' => $domains]);
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['error' => $e->getMessage()]);
}
?>
