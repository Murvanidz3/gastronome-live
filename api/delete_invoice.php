<?php
/**
 * API to delete an invoice record from history
 */
require_once __DIR__ . '/../auth/guard.php';
require_once __DIR__ . '/../config/database.php';

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['error' => 'Method not allowed']);
    exit;
}

$input = json_decode(file_get_contents('php://input'), true);

if (empty($input['id'])) {
    http_response_code(400);
    echo json_encode(['error' => 'Invalid parameters']);
    exit;
}

$invoice_id = (int) $input['id'];
$user_id = $_SESSION['user_id'];

try {
    $db = getDB();

    // Delete only if it belongs to the logged-in user
    $stmt = $db->prepare('DELETE FROM invoices WHERE id = :id AND user_id = :user_id');
    $stmt->execute([':id' => $invoice_id, ':user_id' => $user_id]);

    if ($stmt->rowCount() > 0) {
        echo json_encode(['success' => true]);
    } else {
        http_response_code(404);
        echo json_encode(['error' => 'Invoice not found or unauthorized']);
    }
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['error' => 'Database error']);
}
