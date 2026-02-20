<?php
/**
 * Logout — destroy session and redirect to login.
 */
session_start();
$_SESSION = [];
session_destroy();

header('Location: /auth/login.php');
exit;
