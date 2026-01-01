<?php
/**
 * User Ban Handler (Admin)
 * Handles ban/unban actions for users.
 */

session_start();
// Include config if not already loaded
if (!defined('BASE_URL') && file_exists(__DIR__ . '/../../includes/config.php')) {
    require_once __DIR__ . '/../../includes/config.php';
}
require("../../connect.php");

// Verify user has admin privileges
if (!isset($_SESSION['user_id']) || !$_SESSION['is_admin']) {
    die("Access Denied");
}

// Validate user ID parameter
if (!isset($_POST['user_id'])) {
    $redirect_url = defined('BASE_URL') ? BASE_URL . 'admin/users/manage.php' : 'manage.php';
    header("Location: " . $redirect_url);
    exit();
}

$target_user_id = (int) $_POST['user_id'];
$action = isset($_POST['action']) ? escapeString($_POST['action']) : '';

// Get target user information
$user_sql = "SELECT user_id, username, is_admin, is_banned FROM users WHERE user_id = $target_user_id";
$user_result = myQuery($user_sql);

if (mysqli_num_rows($user_result) == 0) {
    $redirect_url = defined('BASE_URL') ? BASE_URL . 'admin/users/manage.php' : 'manage.php';
    header("Location: " . $redirect_url . "?error=" . urlencode("User not found"));
    exit();
}

$target_user = mysqli_fetch_assoc($user_result);

// Prevent banning admin users
if ($target_user['is_admin']) {
    $redirect_url = defined('BASE_URL') ? BASE_URL . 'admin/users/manage.php' : 'manage.php';
    header("Location: " . $redirect_url . "?error=" . urlencode("Cannot ban admin users"));
    exit();
}

// Prevent banning yourself
if ($target_user_id == $_SESSION['user_id']) {
    $redirect_url = defined('BASE_URL') ? BASE_URL . 'admin/users/manage.php' : 'manage.php';
    header("Location: " . $redirect_url . "?error=" . urlencode("Cannot ban yourself"));
    exit();
}

// Handle ban/unban action
if ($action == 'ban') {
    // Check if is_banned column exists, if not, add it first
    $check_column_sql = "SHOW COLUMNS FROM users LIKE 'is_banned'";
    $check_result = myQuery($check_column_sql);
    
    if (mysqli_num_rows($check_result) == 0) {
        // Column doesn't exist, add it
        $alter_sql = "ALTER TABLE users ADD COLUMN is_banned BOOLEAN DEFAULT FALSE AFTER is_admin";
        myQuery($alter_sql);
    }
    
    $update_sql = "UPDATE users SET is_banned = TRUE WHERE user_id = $target_user_id";
    if (myQuery($update_sql)) {
        // Log admin action
        $admin_id = $_SESSION['user_id'];
        $log_sql = "INSERT INTO admin_logs (admin_id, action_type, target_type, target_id, description) 
                    VALUES ($admin_id, 'user_ban', 'user', $target_user_id, 'Banned user: " . $target_user['username'] . "')";
        myQuery($log_sql);
        
        $redirect_url = defined('BASE_URL') ? BASE_URL . 'admin/users/manage.php' : 'manage.php';
        header("Location: " . $redirect_url . "?success=" . urlencode("User banned successfully"));
        exit();
    } else {
        $redirect_url = defined('BASE_URL') ? BASE_URL . 'admin/users/manage.php' : 'manage.php';
        header("Location: " . $redirect_url . "?error=" . urlencode("Failed to ban user"));
        exit();
    }
} elseif ($action == 'unban') {
    $update_sql = "UPDATE users SET is_banned = FALSE WHERE user_id = $target_user_id";
    if (myQuery($update_sql)) {
        // Log admin action
        $admin_id = $_SESSION['user_id'];
        $log_sql = "INSERT INTO admin_logs (admin_id, action_type, target_type, target_id, description) 
                    VALUES ($admin_id, 'user_unban', 'user', $target_user_id, 'Unbanned user: " . $target_user['username'] . "')";
        myQuery($log_sql);
        
        $redirect_url = defined('BASE_URL') ? BASE_URL . 'admin/users/manage.php' : 'manage.php';
        header("Location: " . $redirect_url . "?success=" . urlencode("User unbanned successfully"));
        exit();
    } else {
        $redirect_url = defined('BASE_URL') ? BASE_URL . 'admin/users/manage.php' : 'manage.php';
        header("Location: " . $redirect_url . "?error=" . urlencode("Failed to unban user"));
        exit();
    }
} else {
    $redirect_url = defined('BASE_URL') ? BASE_URL . 'admin/users/manage.php' : 'manage.php';
    header("Location: " . $redirect_url . "?error=" . urlencode("Invalid action"));
    exit();
}

