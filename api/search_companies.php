<?php
header('Content-Type: application/json; charset=utf-8');
require_once __DIR__ . '/../config/database.php';

session_start();
if (!isset($_SESSION['admin_logged_in']) || $_SESSION['admin_logged_in'] !== true) {
    http_response_code(401);
    echo json_encode(['error' => 'Unauthorized']);
    exit;
}

$q = trim($_GET['q'] ?? '');
$user_id = $_SESSION['user_id'];

try {
    $db = getDB();
    if ($q === '') {
        $stmt = $db->prepare("SELECT * FROM companies WHERE user_id = :user_id ORDER BY name ASC LIMIT 20");
        $stmt->execute([':user_id' => $user_id]);
        $results = $stmt->fetchAll(PDO::FETCH_ASSOC);
    } else {
        $stmt = $db->prepare("SELECT * FROM companies WHERE user_id = :user_id AND (name LIKE :query OR company_id_number LIKE :query) ORDER BY name ASC LIMIT 20");
        $stmt->execute([':user_id' => $user_id, ':query' => "%{$q}%"]);
        $results = $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    echo json_encode($results);

} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode(['error' => 'Database search failed.']);
}
