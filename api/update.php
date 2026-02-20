<?php
/**
 * API: Update Product
 * POST /api/update.php
 * Body: { "id": 1, "image_url": "...", "barcode": "...", "name": "...", "quantity": 10, "price": 5.99, "comment": "..." }
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

if (!$input || empty($input['id'])) {
    http_response_code(400);
    echo json_encode(['error' => 'Invalid request — product ID required']);
    exit;
}

$db = getDB();

$id = (int) $input['id'];
$image_url = trim($input['image_url'] ?? '');
$barcode = trim($input['barcode'] ?? '');
$name = trim($input['name'] ?? '');
$quantity = (int) ($input['quantity'] ?? 0);
$price = (float) ($input['price'] ?? 0);
$currency = strtoupper(trim($input['currency'] ?? 'GEL'));
if (!in_array($currency, ['GEL', 'EUR', 'USD']))
    $currency = 'GEL';
$comment = trim($input['comment'] ?? '');

if ($barcode === '' || $name === '') {
    http_response_code(400);
    echo json_encode(['error' => 'Barcode and name are required']);
    exit;
}

// Check for barcode uniqueness (excluding current product)
$check = $db->prepare('SELECT id FROM products WHERE barcode = :barcode AND id != :id');
$check->execute([':barcode' => $barcode, ':id' => $id]);
if ($check->fetch()) {
    http_response_code(409);
    echo json_encode(['error' => 'Another product already has this barcode']);
    exit;
}

$stmt = $db->prepare('UPDATE products SET image_url = :image_url, barcode = :barcode, name = :name, quantity = :quantity, price = :price, currency = :currency, comment = :comment WHERE id = :id');
$stmt->execute([
    ':image_url' => $image_url,
    ':barcode' => $barcode,
    ':name' => $name,
    ':quantity' => $quantity,
    ':price' => $price,
    ':currency' => $currency,
    ':comment' => $comment,
    ':id' => $id,
]);

echo json_encode([
    'success' => true,
    'updated' => $stmt->rowCount(),
]);
