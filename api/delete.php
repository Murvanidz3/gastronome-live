<?php
/**
 * API: Delete Products
 * POST /api/delete.php
 * Body: { "ids": [1, 2, 3] }  OR  { "all": true }
 */
header('Content-Type: application/json; charset=utf-8');

require_once __DIR__ . '/../config/database.php';

session_start();
if (!isset($_SESSION['admin_logged_in']) || $_SESSION['admin_logged_in'] !== true) {
    http_response_code(401);
    echo json_encode(['error' => 'Unauthorized']);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['error' => 'Method not allowed']);
    exit;
}

$input = json_decode(file_get_contents('php://input'), true);

if (!$input) {
    http_response_code(400);
    echo json_encode(['error' => 'Invalid request body']);
    exit;
}

$db = getDB();

// Delete all products
if (!empty($input['all'])) {
    $stmt = $db->exec('DELETE FROM products');
    echo json_encode(['success' => true, 'deleted' => $stmt]);
    exit;
}

// Delete specific IDs
$ids = $input['ids'] ?? [];
if (empty($ids) || !is_array($ids)) {
    http_response_code(400);
    echo json_encode(['error' => 'No product IDs provided']);
    exit;
}

// Sanitize IDs to integers
$ids = array_map('intval', $ids);
$placeholders = implode(',', array_fill(0, count($ids), '?'));

$stmt = $db->prepare("DELETE FROM products WHERE id IN ($placeholders)");
$stmt->execute($ids);

echo json_encode([
    'success' => true,
    'deleted' => $stmt->rowCount(),
]);
