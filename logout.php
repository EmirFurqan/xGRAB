<?php
/**
 * User Logout Handler
 * Destroys the current user session and redirects to login page.
 * This ensures all session data is cleared when user logs out.
 */

// Start session to access and destroy it
session_start();

// Destroy all session data including user_id, username, and admin status
// This completely logs out the user and clears their session
session_destroy();

// Include config for BASE_URL if available
if (!defined('BASE_URL') && file_exists(__DIR__ . '/includes/config.php')) {
    require_once __DIR__ . '/includes/config.php';
}

// Redirect to login page after logout
$redirect_url = defined('BASE_URL') ? BASE_URL . 'login.php' : 'login.php';
header("Location: " . $redirect_url);
exit();
?>