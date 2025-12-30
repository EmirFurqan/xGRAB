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

// Redirect to login page after logout
header("Location: login.php");
exit();
?>

