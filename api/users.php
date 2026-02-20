<?php
/**
 * API: User Management
 * POST /api/users.php
 *
 * Actions:
 *   { "action": "list" }                               — list all users
 *   { "action": "create", "full_name": "...", ... }    — create new user
 *   { "action": "update", "id": 1, ... }               — update user
 *   { "action": "delete", "id": 2 }                    — delete user
 *   { "action": "change_password", "id": 1, "current_password": "...", "new_password": "..." }
 */
header('Content-Type: application/json; charset=utf-8');

require_once __DIR__ . '/../config/database.php';

session_start();
if (!isset($_SESSION['admin_logged_in']) || $_SESSION['admin_logged_in'] !== true) {
    http_response_code(401);
    echo json_encode(['error' => 'Unauthorized']);
    exit;
}

// Only masho can manage users
if (!isset($_SESSION['username']) || strtolower($_SESSION['username']) !== 'masho') {
    http_response_code(403);
    echo json_encode(['error' => 'Forbidden: Admin access required']);
    exit;
}

$input = json_decode(file_get_contents('php://input'), true);
$action = $input['action'] ?? '';

$db = getDB();

switch ($action) {

    // ─── List Users ───
    case 'list':
        $stmt = $db->query('SELECT id, full_name, username, mobile, created_at FROM users ORDER BY created_at ASC');
        echo json_encode(['success' => true, 'users' => $stmt->fetchAll()]);
        break;

    // ─── Create User ───
    case 'create':
        $fullName = trim($input['full_name'] ?? '');
        $username = trim($input['username'] ?? '');
        $mobile = trim($input['mobile'] ?? '');
        $password = $input['password'] ?? '';

        if ($fullName === '' || $username === '' || $password === '') {
            http_response_code(400);
            echo json_encode(['error' => 'Full name, username, and password are required.']);
            break;
        }

        if (strlen($password) < 4) {
            http_response_code(400);
            echo json_encode(['error' => 'Password must be at least 4 characters.']);
            break;
        }

        // Check unique username
        $check = $db->prepare('SELECT id FROM users WHERE username = :u');
        $check->execute([':u' => $username]);
        if ($check->fetch()) {
            http_response_code(409);
            echo json_encode(['error' => 'Username already exists.']);
            break;
        }

        $hash = password_hash($password, PASSWORD_DEFAULT);
        $stmt = $db->prepare('INSERT INTO users (full_name, username, mobile, password) VALUES (:fn, :u, :m, :p)');
        $stmt->execute([':fn' => $fullName, ':u' => $username, ':m' => $mobile, ':p' => $hash]);

        echo json_encode(['success' => true, 'id' => $db->lastInsertId()]);
        break;

    // ─── Update User Profile ───
    case 'update':
        $id = (int) ($input['id'] ?? 0);
        $fullName = trim($input['full_name'] ?? '');
        $username = trim($input['username'] ?? '');
        $mobile = trim($input['mobile'] ?? '');

        if ($id < 1 || $fullName === '' || $username === '') {
            http_response_code(400);
            echo json_encode(['error' => 'ID, full name, and username are required.']);
            break;
        }

        // Check unique username (excluding self)
        $check = $db->prepare('SELECT id FROM users WHERE username = :u AND id != :id');
        $check->execute([':u' => $username, ':id' => $id]);
        if ($check->fetch()) {
            http_response_code(409);
            echo json_encode(['error' => 'Username already taken by another user.']);
            break;
        }

        $stmt = $db->prepare('UPDATE users SET full_name = :fn, username = :u, mobile = :m WHERE id = :id');
        $stmt->execute([':fn' => $fullName, ':u' => $username, ':m' => $mobile, ':id' => $id]);

        // Update session if editing own profile
        if ($id == ($_SESSION['user_id'] ?? 0)) {
            $_SESSION['user_name'] = $fullName;
            $_SESSION['username'] = $username;
        }

        echo json_encode(['success' => true]);
        break;

    // ─── Change Password ───
    case 'change_password':
        $id = (int) ($input['id'] ?? 0);
        $currentPassword = $input['current_password'] ?? '';
        $newPassword = $input['new_password'] ?? '';

        if ($id < 1 || $newPassword === '') {
            http_response_code(400);
            echo json_encode(['error' => 'User ID and new password are required.']);
            break;
        }

        if (strlen($newPassword) < 4) {
            http_response_code(400);
            echo json_encode(['error' => 'New password must be at least 4 characters.']);
            break;
        }

        // If changing own password, verify current password
        if ($id == ($_SESSION['user_id'] ?? 0)) {
            $stmt = $db->prepare('SELECT password FROM users WHERE id = :id');
            $stmt->execute([':id' => $id]);
            $user = $stmt->fetch();
            if (!$user || !password_verify($currentPassword, $user['password'])) {
                http_response_code(403);
                echo json_encode(['error' => 'Current password is incorrect.']);
                break;
            }
        }

        $hash = password_hash($newPassword, PASSWORD_DEFAULT);
        $stmt = $db->prepare('UPDATE users SET password = :p WHERE id = :id');
        $stmt->execute([':p' => $hash, ':id' => $id]);

        echo json_encode(['success' => true]);
        break;

    // ─── Delete User ───
    case 'delete':
        $id = (int) ($input['id'] ?? 0);

        if ($id < 1) {
            http_response_code(400);
            echo json_encode(['error' => 'User ID is required.']);
            break;
        }

        // Can't delete yourself
        if ($id == ($_SESSION['user_id'] ?? 0)) {
            http_response_code(400);
            echo json_encode(['error' => 'You cannot delete your own account.']);
            break;
        }

        $stmt = $db->prepare('DELETE FROM users WHERE id = :id');
        $stmt->execute([':id' => $id]);

        echo json_encode(['success' => true, 'deleted' => $stmt->rowCount()]);
        break;

    default:
        http_response_code(400);
        echo json_encode(['error' => 'Invalid action.']);
}
