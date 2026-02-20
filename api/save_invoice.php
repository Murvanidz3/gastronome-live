<?php
/**
 * API to save an invoice record to history
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

if (empty($input['company_id']) || empty($input['invoice_number'])) {
    http_response_code(400);
    echo json_encode(['error' => 'Invalid parameters']);
    exit;
}

$company_id = (int) $input['company_id'];
$invoice_number = $input['invoice_number'];
$total_amount = (float) $input['total_amount'];
$user_id = $_SESSION['user_id'];

try {
    $db = getDB();

    // Check if the company belongs to the user
    $stmt = $db->prepare('SELECT id FROM companies WHERE id = :id AND user_id = :user_id');
    $stmt->execute([':id' => $company_id, ':user_id' => $user_id]);

    if (!$stmt->fetch()) {
        http_response_code(403);
        echo json_encode(['error' => 'Unauthorized or company not found']);
        exit;
    }

    // Check if invoice already logged to prevent duplicates if they press print multiple times
    $check = $db->prepare('SELECT id FROM invoices WHERE invoice_number = :inv AND company_id = :cid');
    $check->execute([':inv' => $invoice_number, ':cid' => $company_id]);

    if (!$check->fetch()) {
        $insert = $db->prepare('INSERT INTO invoices (user_id, company_id, invoice_number, total_amount) VALUES (:uid, :cid, :inv, :total)');
        $insert->execute([
            ':uid' => $user_id,
            ':cid' => $company_id,
            ':inv' => $invoice_number,
            ':total' => $total_amount
        ]);
    }

    echo json_encode(['success' => true]);
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['error' => 'Database error']);
}
