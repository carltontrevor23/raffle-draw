<?php
/**
 * Customer Appreciation Month Raffle Draw System
 * Admin Authentication - Logout Handler (Phase 5)
 */

session_start();

// Unset all session variables
$_SESSION = [];

// Destroy session cookie if set
if (ini_get('session.use_cookies')) {
    $params = session_get_cookie_params();
    setcookie(
        session_name(),
        '',
        time() - 42000,
        $params['path'],
        $params['domain'],
        $params['secure'],
        $params['httponly']
    );
}

// Destroy session on the server
session_destroy();

// Redirect back to login page with notice
header('Location: login.php?logged_out=1');
exit;
