<?php
/**
 * Admin Logout
 * Stock Management System (SMS)
 */

define('SMS_INCLUDED', true);

session_start();

// Clear all session data
$_SESSION = array();

// Delete session cookie if it exists
if (ini_get("session.use_cookies")) {
    $params = session_get_cookie_params();
    setcookie(session_name(), '', time() - 42000,
        $params["path"], $params["domain"],
        $params["secure"], $params["httponly"]
    );
}

// Destroy the session
session_destroy();

// Redirect to login page with logout message
header('Location: /sms/login.php?logout=1');
exit();
?>