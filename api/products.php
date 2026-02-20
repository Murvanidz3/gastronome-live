<?php
/**
 * API: Product Listing
 * GET /api/products.php  — returns all products as JSON
 */
header('Content-Type: application/json; charset=utf-8');

require_once __DIR__ . '/../config/database.php';

session_start();
if (!isset($_SESSION['admin_logged_in']) || $_SESSION['admin_logged_in'] !== true || !isset($_SESSION['user_id'])) {
    http_response_code(401);
    echo json_encode(['error' => 'Unauthorized']);
    exit;
}

$user_id = $_SESSION['user_id'];
$method = $_SERVER['REQUEST_METHOD'];

try {
    $db = getDB();

    switch ($method) {
        case 'GET':
            $stmt = $db->prepare('SELECT * FROM products WHERE user_id = :user_id ORDER BY created_at DESC');
            $stmt->execute([':user_id' => $user_id]);
            echo json_encode($stmt->fetchAll(PDO::FETCH_ASSOC));
            break;

        case 'POST':
            $name = $_POST['name'] ?? '';
            $barcode = $_POST['barcode'] ?? '';
            $price = $_POST['price'] ?? 0;
            $currency = $_POST['currency'] ?? 'GEL';
            $image = $_POST['image_url'] ?? '';

            if (empty($name) || empty($barcode)) {
                http_response_code(400);
                echo json_encode(['error' => 'Name and Barcode required']);
                exit;
            }

            $stmt = $db->prepare('
                INSERT INTO products (user_id, image_url, name, barcode, price, currency)
                VALUES (:user_id, :image, :name, :barcode, :price, :currency)
            ');
            $stmt->execute([
                ':user_id' => $user_id,
                ':image' => $image,
                ':name' => $name,
                ':barcode' => $barcode,
                ':price' => $price,
                ':currency' => $currency
            ]);

            echo json_encode(['status' => 'success', 'id' => $db->lastInsertId()]);
            break;

        case 'DELETE':
            $input = json_decode(file_get_contents('php://input'), true);
            $ids = $input['ids'] ?? [];
            if (!empty($ids)) {
                $placeholders = implode(',', array_fill(0, count($ids), '?'));
                // Merge array for execute (user_id + array of ids)
                $params = array_merge([$user_id], $ids);
                $stmt = $db->prepare("DELETE FROM products WHERE user_id = ? AND id IN ($placeholders)");
                $stmt->execute($params);
                echo json_encode(['status' => 'success', 'deleted' => $stmt->rowCount()]);
            } else {
                http_response_code(400);
                echo json_encode(['error' => 'No IDs provided']);
            }
            break;

        default:
            http_response_code(405); // Method Not Allowed
            echo json_encode(['error' => 'Method Not Allowed']);
            break;
    }
} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode(['error' => 'Database error: ' . $e->getMessage()]);
}
