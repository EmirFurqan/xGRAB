<?php
/**
 * Manage Movie Cast Handler (Admin)
 * Handles adding and removing cast members from movies.
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

// Validate movie ID parameter
if (!isset($_GET['movie_id'])) {
    $redirect_url = defined('BASE_URL') ? BASE_URL . 'admin/dashboard.php' : '../dashboard.php';
    header("Location: " . $redirect_url);
    exit();
}

$movie_id = (int) $_GET['movie_id'];

// Handle add cast member
if (isset($_POST['add_cast'])) {
    $cast_id = (int) $_POST['cast_id'];
    $character_name = escapeString($_POST['character_name'] ?? '');
    $cast_order = isset($_POST['cast_order']) ? (int) $_POST['cast_order'] : 0;
    
    // Check if cast member is already in this movie
    $check_sql = "SELECT * FROM movie_cast WHERE movie_id = $movie_id AND cast_id = $cast_id";
    $check_result = myQuery($check_sql);
    
    if (mysqli_num_rows($check_result) == 0) {
        $insert_sql = "INSERT INTO movie_cast (movie_id, cast_id, character_name, cast_order) 
                       VALUES ($movie_id, $cast_id, " . ($character_name ? "'$character_name'" : 'NULL') . ", $cast_order)";
        if (myQuery($insert_sql)) {
            // Log admin action
            $admin_id = $_SESSION['user_id'];
            $log_sql = "INSERT INTO admin_logs (admin_id, action_type, target_type, target_id, description) 
                        VALUES ($admin_id, 'movie_cast_add', 'movie_cast', $movie_id, 'Added cast member to movie')";
            myQuery($log_sql);
            
            $success = "Cast member added successfully";
        } else {
            $error = "Failed to add cast member";
        }
    } else {
        $error = "Cast member is already in this movie";
    }
}

// Handle remove cast member
if (isset($_POST['remove_cast'])) {
    $cast_id = (int) $_POST['cast_id'];
    
    $delete_sql = "DELETE FROM movie_cast WHERE movie_id = $movie_id AND cast_id = $cast_id";
    if (myQuery($delete_sql)) {
        // Log admin action
        $admin_id = $_SESSION['user_id'];
        $log_sql = "INSERT INTO admin_logs (admin_id, action_type, target_type, target_id, description) 
                    VALUES ($admin_id, 'movie_cast_remove', 'movie_cast', $movie_id, 'Removed cast member from movie')";
        myQuery($log_sql);
        
        $success = "Cast member removed successfully";
    } else {
        $error = "Failed to remove cast member";
    }
}

// Handle update cast member
if (isset($_POST['update_cast'])) {
    $cast_id = (int) $_POST['cast_id'];
    $character_name = escapeString($_POST['character_name'] ?? '');
    $cast_order = isset($_POST['cast_order']) ? (int) $_POST['cast_order'] : 0;
    
    $update_sql = "UPDATE movie_cast SET 
                   character_name = " . ($character_name ? "'$character_name'" : 'NULL') . ",
                   cast_order = $cast_order
                   WHERE movie_id = $movie_id AND cast_id = $cast_id";
    if (myQuery($update_sql)) {
        // Log admin action
        $admin_id = $_SESSION['user_id'];
        $log_sql = "INSERT INTO admin_logs (admin_id, action_type, target_type, target_id, description) 
                    VALUES ($admin_id, 'movie_cast_update', 'movie_cast', $movie_id, 'Updated cast member in movie')";
        myQuery($log_sql);
        
        $success = "Cast member updated successfully";
    } else {
        $error = "Failed to update cast member";
    }
}

// Redirect back to edit page with message
$redirect_url = defined('BASE_URL') ? BASE_URL . "admin/movies/edit.php?id=$movie_id" : "edit.php?id=$movie_id";
if (isset($success)) {
    // URL already has ?id=, so use & for additional parameters
    header("Location: " . $redirect_url . "&success=" . urlencode($success));
} elseif (isset($error)) {
    // URL already has ?id=, so use & for additional parameters
    header("Location: " . $redirect_url . "&error=" . urlencode($error));
} else {
    header("Location: " . $redirect_url);
}
exit();

