<?php
/**
 * Auth Guard — include at the top of every protected page.
 * Redirects unauthenticated users to the login page.
 * Requires user_id in session (database-backed auth).
 */

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Ensure pages requiring auth are not cached by the browser (fixes Safari cache issues on reload)
header("Cache-Control: no-store, no-cache, must-revalidate, max-age=0");
header("Cache-Control: post-check=0, pre-check=0", false);
header("Pragma: no-cache");

// Must have both admin flag AND user_id (from new database login)
if (!isset($_SESSION['admin_logged_in']) || $_SESSION['admin_logged_in'] !== true || !isset($_SESSION['user_id'])) {
    // Clear old sessions
    $_SESSION = [];
    session_destroy();

    header('Location: /auth/login');
    exit;
}
