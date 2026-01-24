<?php
// Prevent HTML error output
ini_set('display_errors', 0);
error_reporting(E_ALL);
header('Content-Type: application/json');

try {
    require_once 'config/cors.php';
    require_once 'config/db.php';

    // Get JSON input
    $input = json_decode(file_get_contents('php://input'), true);

    if (!$input || empty($input['url'])) {
        throw new Exception('Domain URL is required');
    }

    $url = trim($input['url']);

    // Basic validation to remove protocol if user pasted it
    $url = preg_replace('#^https?://#', '', $url);
    $url = rtrim($url, '/');

    // Check if exists
    $stmt = $pdo->prepare("SELECT id FROM domains WHERE url = ?");
    $stmt->execute([$url]);
    if ($stmt->fetch()) {
        echo json_encode(['success' => true, 'message' => 'Domain already exists', 'domain' => $url]);
        exit;
    }

    $stmt = $pdo->prepare("INSERT INTO domains (url, active) VALUES (?, true)");
    $stmt->execute([$url]);

    echo json_encode(['success' => true, 'domain' => $url]);

} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'error' => $e->getMessage()]);
}
?>
