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

    // Verify ownership and get existing notes
    $stmt = $db->prepare('SELECT id, notes FROM companies WHERE id = :id AND user_id = :user_id');
    $stmt->execute([':id' => $company_id, ':user_id' => $user_id]);
    $company = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$company) {
        http_response_code(403);
        echo json_encode(['error' => 'Unauthorized or company not found']);
        exit;
    }

    $existing_notes_raw = $company['notes'];
    $notes_array = [];

    if (!empty($existing_notes_raw)) {
        $decoded = json_decode($existing_notes_raw, true);
        if (is_array($decoded)) {
            $notes_array = $decoded;
        } else {
            // Migration: old note was plain text
            $notes_array[] = [
                'text' => $existing_notes_raw,
                'date' => date('Y-m-d\TH:i:sP') // current time as we don't know when it was added
            ];
        }
    }

    // Append new note if it's not empty
    if (trim($notes) !== '') {
        $notes_array[] = [
            'text' => trim($notes),
            'date' => date('Y-m-d\TH:i:sP')
        ];
    }

    $new_notes_json = json_encode($notes_array, JSON_UNESCAPED_UNICODE);

    $update = $db->prepare('UPDATE companies SET notes = :notes WHERE id = :id');
    $update->execute([':notes' => $new_notes_json, ':id' => $company_id]);

    echo json_encode(['success' => true]);
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['error' => 'Database error']);
}
