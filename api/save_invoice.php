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

// New fields
$invoice_date = $input['invoice_date'] ?? null;
$due_date = $input['due_date'] ?? null;
$subtotal = isset($input['subtotal']) ? (float) $input['subtotal'] : null;
$tax_rate = isset($input['tax_rate']) ? (float) $input['tax_rate'] : null;
$tax_amount = isset($input['tax_amount']) ? (float) $input['tax_amount'] : null;
$discount_amount = isset($input['discount_amount']) ? (float) $input['discount_amount'] : null;
$notes = $input['notes'] ?? null;
$items_json = isset($input['items']) ? json_encode($input['items'], JSON_UNESCAPED_UNICODE) : null;

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
        $insert = $db->prepare('
            INSERT INTO invoices (
                user_id, company_id, invoice_number, total_amount,
                invoice_date, due_date, subtotal, tax_rate, tax_amount, discount_amount, notes, items_json
            ) VALUES (
                :uid, :cid, :inv, :total,
                :inv_date, :due_date, :subtotal, :tax_rate, :tax_amount, :discount_amount, :notes, :items_json
            )
        ');
        $insert->execute([
            ':uid' => $user_id,
            ':cid' => $company_id,
            ':inv' => $invoice_number,
            ':total' => $total_amount,
            ':inv_date' => $invoice_date,
            ':due_date' => $due_date,
            ':subtotal' => $subtotal,
            ':tax_rate' => $tax_rate,
            ':tax_amount' => $tax_amount,
            ':discount_amount' => $discount_amount,
            ':notes' => $notes,
            ':items_json' => $items_json
        ]);
    } else {
        // If it exists (user clicked print multiple times), update it with the latest data instead of ignoring
        $update = $db->prepare('
            UPDATE invoices SET 
                total_amount = :total,
                invoice_date = :inv_date,
                due_date = :due_date,
                subtotal = :subtotal,
                tax_rate = :tax_rate,
                tax_amount = :tax_amount,
                discount_amount = :discount_amount,
                notes = :notes,
                items_json = :items_json
            WHERE invoice_number = :inv AND company_id = :cid
        ');
        $update->execute([
            ':total' => $total_amount,
            ':inv_date' => $invoice_date,
            ':due_date' => $due_date,
            ':subtotal' => $subtotal,
            ':tax_rate' => $tax_rate,
            ':tax_amount' => $tax_amount,
            ':discount_amount' => $discount_amount,
            ':notes' => $notes,
            ':items_json' => $items_json,
            ':inv' => $invoice_number,
            ':cid' => $company_id
        ]);
    }

    echo json_encode(['success' => true]);
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['error' => 'Database error']);
}
