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
    $status = ($_POST['bulk_action'] == 'mark_watched') ? 'watched' : 'not_watched';
    
    if (count($movie_ids) > 0) {
        $movie_ids_str = implode(',', $movie_ids);
        $update_sql = "UPDATE watchlist_movies SET watched_status = '$status' 
                       WHERE watchlist_id = $watchlist_id AND movie_id IN ($movie_ids_str)";
        if (myQuery($update_sql)) {
            header("Location: view.php?id=$watchlist_id&success=" . count($movie_ids) . " movie(s) updated");
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
    
    // Update status
    $update_sql = "UPDATE watchlist_movies SET watched_status = '$status' 
                   WHERE watchlist_id = $watchlist_id AND movie_id = $movie_id";
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

