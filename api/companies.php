<?php
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
            $stmt = $db->prepare('SELECT * FROM companies WHERE user_id = :user_id ORDER BY created_at DESC');
            $stmt->execute([':user_id' => $user_id]);
            echo json_encode($stmt->fetchAll(PDO::FETCH_ASSOC));
            break;

        case 'POST':
            $input = json_decode(file_get_contents('php://input'), true);
            $id = $input['id'] ?? '';
            $name = trim($input['name'] ?? '');
            $company_id_number = trim($input['company_id_number'] ?? '');
            $address = trim($input['address'] ?? '');
            $phone = trim($input['phone'] ?? '');

            if (empty($name) || empty($company_id_number)) {
                http_response_code(400);
                echo json_encode(['error' => 'Name and Company ID required']);
                exit;
            }

            if (!empty($id)) {
                // It's an update (acting as PUT)
                $stmt = $db->prepare('
                    UPDATE companies 
                    SET name = :name, company_id_number = :company_id_number, address = :address, phone = :phone
                    WHERE user_id = :user_id AND id = :id
                ');
                $stmt->execute([
                    ':name' => $name,
                    ':company_id_number' => $company_id_number,
                    ':address' => $address,
                    ':phone' => $phone,
                    ':user_id' => $user_id,
                    ':id' => $id
                ]);
                echo json_encode(['status' => 'success']);
            } else {
                // It's a new insertion
                $stmt = $db->prepare('
                    INSERT INTO companies (user_id, name, company_id_number, address, phone)
                    VALUES (:user_id, :name, :company_id_number, :address, :phone)
                ');
                $stmt->execute([
                    ':user_id' => $user_id,
                    ':name' => $name,
                    ':company_id_number' => $company_id_number,
                    ':address' => $address,
                    ':phone' => $phone
                ]);
                echo json_encode(['status' => 'success', 'id' => $db->lastInsertId()]);
            }
            break;

        case 'PUT':
            $input = json_decode(file_get_contents('php://input'), true);
            $ids = $input['ids'] ?? [];
            if (!empty($ids)) {
                $placeholders = implode(',', array_fill(0, count($ids), '?'));
                $params = array_merge([$user_id], $ids);
                $stmt = $db->prepare("DELETE FROM companies WHERE user_id = ? AND id IN ($placeholders)");
                $stmt->execute($params);
                echo json_encode(['status' => 'success', 'deleted' => $stmt->rowCount()]);
            } else {
                http_response_code(400);
                echo json_encode(['error' => 'No IDs provided']);
            }
            break;
    }

} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode(['error' => 'Database error: ' . $e->getMessage()]);
}
