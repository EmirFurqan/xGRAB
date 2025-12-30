<?php
/**
 * Remove Movie from Watchlist Handler
 * Removes a movie from a user's watchlist.
 * Verifies watchlist ownership before allowing removal.
 */

session_start();
require("../connect.php");

// Require user to be logged in to remove movies from watchlists
if (!isset($_SESSION['user_id'])) {
    header("Location: ../login.php");
    exit();
}

// Validate required parameters are present
if (!isset($_POST['watchlist_id']) || !isset($_POST['movie_id'])) {
    header("Location: index.php");
    exit();
}

$watchlist_id = (int)$_POST['watchlist_id'];
$movie_id = (int)$_POST['movie_id'];
$user_id = $_SESSION['user_id'];

// Verify that the watchlist belongs to the current user
// This prevents users from removing movies from other users' watchlists
$check_sql = "SELECT * FROM watchlists WHERE watchlist_id = $watchlist_id AND user_id = $user_id";
$check_result = myQuery($check_sql);

if (mysqli_num_rows($check_result) == 0) {
    header("Location: view.php?id=$watchlist_id&error=Watchlist not found or access denied");
    exit();
}

// Remove movie from watchlist
// Deletes the relationship record from watchlist_movies junction table
$delete_sql = "DELETE FROM watchlist_movies WHERE watchlist_id = $watchlist_id AND movie_id = $movie_id";
if (myQuery($delete_sql)) {
    header("Location: view.php?id=$watchlist_id&success=Movie removed from watchlist");
} else {
    header("Location: view.php?id=$watchlist_id&error=Failed to remove movie");
}
exit();
?>

