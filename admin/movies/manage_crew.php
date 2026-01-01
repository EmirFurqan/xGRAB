<?php
/**
 * Manage Movie Crew Handler (Admin)
 * Handles adding and removing crew members from movies.
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

// Handle add crew member
if (isset($_POST['add_crew'])) {
    $crew_id = (int) $_POST['crew_id'];
    $role = escapeString($_POST['role']);
    
    if (empty($role)) {
        $error = "Role is required";
    } else {
        // Check if crew member with this role is already in this movie
        $check_sql = "SELECT * FROM movie_crew WHERE movie_id = $movie_id AND crew_id = $crew_id AND role = '$role'";
        $check_result = myQuery($check_sql);
        
        if (mysqli_num_rows($check_result) == 0) {
            $insert_sql = "INSERT INTO movie_crew (movie_id, crew_id, role) 
                           VALUES ($movie_id, $crew_id, '$role')";
            if (myQuery($insert_sql)) {
                // Log admin action
                $admin_id = $_SESSION['user_id'];
                $log_sql = "INSERT INTO admin_logs (admin_id, action_type, target_type, target_id, description) 
                            VALUES ($admin_id, 'movie_crew_add', 'movie_crew', $movie_id, 'Added crew member to movie')";
                myQuery($log_sql);
                
                $success = "Crew member added successfully";
            } else {
                $error = "Failed to add crew member";
            }
        } else {
            $error = "Crew member with this role is already in this movie";
        }
    }
}

// Handle remove crew member
if (isset($_POST['remove_crew'])) {
    $crew_id = (int) $_POST['crew_id'];
    $role = escapeString($_POST['role']);
    
    $delete_sql = "DELETE FROM movie_crew WHERE movie_id = $movie_id AND crew_id = $crew_id AND role = '$role'";
    if (myQuery($delete_sql)) {
        // Log admin action
        $admin_id = $_SESSION['user_id'];
        $log_sql = "INSERT INTO admin_logs (admin_id, action_type, target_type, target_id, description) 
                    VALUES ($admin_id, 'movie_crew_remove', 'movie_crew', $movie_id, 'Removed crew member from movie')";
        myQuery($log_sql);
        
        $success = "Crew member removed successfully";
    } else {
        $error = "Failed to remove crew member";
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

