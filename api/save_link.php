<?php
header('Content-Type: application/json');
require_once 'config/cors.php';
require_once 'config/db.php';

// Get JSON input
$input = json_decode(file_get_contents('php://input'), true);

if (!$input) {
    http_response_code(400);
    echo json_encode(['error' => 'Invalid input']);
    exit;
}

$slug = $input['slug'] ?? '';
$original_url = $input['original_url'] ?? '';
$domain_url = $input['domain_url'] ?? '';
$title = $input['title'] ?? null;
$description = $input['description'] ?? null;
$image_url = $input['image_url'] ?? null;

if (empty($slug) || empty($original_url) || empty($domain_url)) {
    http_response_code(400);
    echo json_encode(['error' => 'Missing required fields']);
    exit;
}

try {
    // First find domain_id
    $stmt = $pdo->prepare("SELECT id FROM domains WHERE url = ? LIMIT 1");
    $stmt->execute([$domain_url]);
    $domainId = $stmt->fetchColumn();

    if (!$domainId) {
        // If domain doesn't exist in DB, maybe insert it or fail. For now, let's fail or default.
        // Or strictly we should only allow domains from our DB.
        throw new Exception("Invalid domain selected");
    }

    // Insert Link
    $sql = "INSERT INTO links (slug, original_url, title, description, image_url, domain_id) VALUES (?, ?, ?, ?, ?, ?)";
    $stmt = $pdo->prepare($sql);
    $stmt->execute([$slug, $original_url, $title, $description, $image_url, $domainId]);

    echo json_encode(['success' => true, 'slug' => $slug]);

} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['error' => 'Failed to save link: ' . $e->getMessage()]);
}
?>
