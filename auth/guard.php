<?php
/**
 * Auth Guard — include at the top of every protected page.
 * Redirects unauthenticated users to the login page.
 * Requires user_id in session (database-backed auth).
 */

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Must have both admin flag AND user_id (from new database login)
if (!isset($_SESSION['admin_logged_in']) || $_SESSION['admin_logged_in'] !== true || !isset($_SESSION['user_id'])) {
    // Clear old sessions
    $_SESSION = [];
    session_destroy();

    header('Location: /auth/login.php');
    exit;
}
