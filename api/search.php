<?php
/**
 * API: Search Products
 * GET /api/search.php?q=searchTerm
 * Returns JSON array of matching products.
 */
header('Content-Type: application/json; charset=utf-8');

require_once __DIR__ . '/../config/database.php';

// Simple session check for API
session_start();
if (!isset($_SESSION['admin_logged_in']) || $_SESSION['admin_logged_in'] !== true) {
    http_response_code(401);
    echo json_encode(['error' => 'Unauthorized']);
    exit;
}

$q = trim($_GET['q'] ?? '');
$user_id = $_SESSION['user_id'];

if ($q === '') {
    // Return all products for this user when search is empty
    $db = getDB();
    $stmt = $db->prepare('SELECT * FROM products WHERE user_id = :user_id ORDER BY created_at DESC');
    $stmt->execute([':user_id' => $user_id]);
    echo json_encode($stmt->fetchAll(PDO::FETCH_ASSOC));
    exit;
}

$db = getDB();
$like = '%' . $q . '%';
$stmt = $db->prepare('SELECT * FROM products WHERE user_id = :user_id AND (barcode LIKE :q1 OR name LIKE :q2) ORDER BY created_at DESC');
$stmt->execute([
    ':user_id' => $user_id,
    ':q1' => $like,
    ':q2' => $like
]);

echo json_encode($stmt->fetchAll(PDO::FETCH_ASSOC));
