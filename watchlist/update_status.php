<?php
session_start();
require("../connect.php");

if (!isset($_SESSION['user_id'])) {
    header("Location: ../login.php");
    exit();
}

if (!isset($_POST['watchlist_id'])) {
    header("Location: index.php");
    exit();
}

$watchlist_id = (int)$_POST['watchlist_id'];
$user_id = $_SESSION['user_id'];

// Verify watchlist ownership
$check_sql = "SELECT * FROM watchlists WHERE watchlist_id = $watchlist_id AND user_id = $user_id";
$check_result = myQuery($check_sql);

if (mysqli_num_rows($check_result) == 0) {
    header("Location: view.php?id=$watchlist_id&error=Watchlist not found or access denied");
    exit();
}

// Handle bulk actions
if (isset($_POST['bulk_action']) && isset($_POST['movie_ids']) && is_array($_POST['movie_ids'])) {
    $movie_ids = array_map('intval', $_POST['movie_ids']);
    $action = $_POST['bulk_action']; // 'mark_watched' or 'mark_not_watched'
    
    if (count($movie_ids) > 0) {
        $success_count = 0;
        $error_count = 0;
        
        foreach ($movie_ids as $movie_id) {
            if ($action == 'mark_watched') {
                // Add to user_watched_movies (use INSERT IGNORE to avoid duplicates)
                $insert_sql = "INSERT IGNORE INTO user_watched_movies (user_id, movie_id) 
                              VALUES ($user_id, $movie_id)";
                if (myQuery($insert_sql)) {
                    $success_count++;
                } else {
                    $error_count++;
                }
            } else {
                // Remove from user_watched_movies
                $delete_sql = "DELETE FROM user_watched_movies 
                              WHERE user_id = $user_id AND movie_id = $movie_id";
                if (myQuery($delete_sql)) {
                    $success_count++;
                } else {
                    $error_count++;
                }
            }
        }
        
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

// Handle single movie update
if (isset($_POST['movie_id']) && isset($_POST['status'])) {
    $movie_id = (int)$_POST['movie_id'];
    $status = escapeString($_POST['status']);
    
    // Validate status
    if ($status != 'watched' && $status != 'not_watched') {
        header("Location: view.php?id=$watchlist_id&error=Invalid status");
        exit();
    }
    
    // Update status in user_watched_movies table
    if ($status == 'watched') {
        // Add to user_watched_movies
        $update_sql = "INSERT IGNORE INTO user_watched_movies (user_id, movie_id) 
                      VALUES ($user_id, $movie_id)";
    } else {
        // Remove from user_watched_movies
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

