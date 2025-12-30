<?php
/**
 * Watchlist Status Update Handler
 * Updates watched status for movies in a watchlist.
 * Supports both single movie updates and bulk operations.
 */

session_start();
require("../connect.php");

// Require user to be logged in to update watched status
if (!isset($_SESSION['user_id'])) {
    header("Location: ../login.php");
    exit();
}

// Validate watchlist_id parameter
if (!isset($_POST['watchlist_id'])) {
    header("Location: index.php");
    exit();
}

$watchlist_id = (int)$_POST['watchlist_id'];
$user_id = $_SESSION['user_id'];

// Verify that the watchlist belongs to the current user
// This prevents users from updating movies in other users' watchlists
$check_sql = "SELECT * FROM watchlists WHERE watchlist_id = $watchlist_id AND user_id = $user_id";
$check_result = myQuery($check_sql);

if (mysqli_num_rows($check_result) == 0) {
    header("Location: view.php?id=$watchlist_id&error=Watchlist not found or access denied");
    exit();
}

// Handle bulk status updates for multiple movies
if (isset($_POST['bulk_action']) && isset($_POST['movie_ids']) && is_array($_POST['movie_ids'])) {
    // Convert movie IDs to integers for safety
    $movie_ids = array_map('intval', $_POST['movie_ids']);
    $action = $_POST['bulk_action']; // 'mark_watched' or 'mark_not_watched'
    
    if (count($movie_ids) > 0) {
        $success_count = 0;
        $error_count = 0;
        
        // Process each selected movie
        foreach ($movie_ids as $movie_id) {
            if ($action == 'mark_watched') {
                // Add movie to user's watched list
                // INSERT IGNORE prevents errors if movie is already marked as watched
                $insert_sql = "INSERT IGNORE INTO user_watched_movies (user_id, movie_id) 
                              VALUES ($user_id, $movie_id)";
                if (myQuery($insert_sql)) {
                    $success_count++;
                } else {
                    $error_count++;
                }
            } else {
                // Remove movie from user's watched list
                $delete_sql = "DELETE FROM user_watched_movies 
                              WHERE user_id = $user_id AND movie_id = $movie_id";
                if (myQuery($delete_sql)) {
                    $success_count++;
                } else {
                    $error_count++;
                }
            }
        }
        
        // Provide feedback on bulk operation results
        if ($success_count > 0) {
            $message = $success_count . " movie(s) updated";
            if ($error_count > 0) {
                $message .= " (" . $error_count . " failed)";
            }
            header("Location: view.php?id=$watchlist_id&success=" . urlencode($message));
        } else {
            header("Location: view.php?id=$watchlist_id&error=Failed to update movies");
        }
    } else {
        header("Location: view.php?id=$watchlist_id&error=No movies selected");
    }
    exit();
}

// Handle single movie status update
if (isset($_POST['movie_id']) && isset($_POST['status'])) {
    $movie_id = (int)$_POST['movie_id'];
    $status = escapeString($_POST['status']);
    
    // Validate status value is one of the allowed options
    if ($status != 'watched' && $status != 'not_watched') {
        header("Location: view.php?id=$watchlist_id&error=Invalid status");
        exit();
    }
    
    // Update watched status in user_watched_movies table
    if ($status == 'watched') {
        // Add movie to watched list
        // INSERT IGNORE prevents duplicate entry errors
        $update_sql = "INSERT IGNORE INTO user_watched_movies (user_id, movie_id) 
                      VALUES ($user_id, $movie_id)";
    } else {
        // Remove movie from watched list
        $update_sql = "DELETE FROM user_watched_movies 
                      WHERE user_id = $user_id AND movie_id = $movie_id";
    }
    
    if (myQuery($update_sql)) {
        header("Location: view.php?id=$watchlist_id&success=Status updated");
    } else {
        header("Location: view.php?id=$watchlist_id&error=Failed to update status");
    }
} else {
    header("Location: view.php?id=$watchlist_id&error=Invalid request");
}
exit();
?>

