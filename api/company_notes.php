<?php
/**
 * API to update company notes
 */
require_once __DIR__ . '/../auth/guard.php';
require_once __DIR__ . '/../config/database.php';

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo JSON_encode(['error' => 'Method not allowed']);
    exit;
}

$input = json_decode(file_get_contents('php://input'), true);

if (!isset($input['id']) || !isset($input['notes'])) {
    http_response_code(400);
    echo json_encode(['error' => 'Invalid parameters']);
    exit;
}

$company_id = (int) $input['id'];
$notes = $input['notes'];
$user_id = $_SESSION['user_id'];

try {
    $db = getDB();

    // Verify ownership
    $stmt = $db->prepare('SELECT id FROM companies WHERE id = :id AND user_id = :user_id');
    $stmt->execute([':id' => $company_id, ':user_id' => $user_id]);

    if (!$stmt->fetch()) {
        http_response_code(403);
        echo json_encode(['error' => 'Unauthorized or company not found']);
        exit;
    }

    $update = $db->prepare('UPDATE companies SET notes = :notes WHERE id = :id');
    $update->execute([':notes' => $notes, ':id' => $company_id]);

    echo json_encode(['success' => true]);
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['error' => 'Database error']);
}
