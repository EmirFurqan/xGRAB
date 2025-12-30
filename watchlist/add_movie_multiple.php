<?php
/**
 * Add Movie to Multiple Watchlists Handler
 * Allows users to add a single movie to multiple watchlists at once.
 * Verifies ownership for each watchlist and prevents duplicate entries.
 */

session_start();
require("../connect.php");

// Require user to be logged in to add movies to watchlists
if (!isset($_SESSION['user_id'])) {
    header("Location: ../login.php");
    exit();
}

// Validate required parameters are present
if (!isset($_POST['watchlist_ids']) || !isset($_POST['movie_id'])) {
    header("Location: ../movies/browse.php");
    exit();
}

$watchlist_ids = $_POST['watchlist_ids']; // Array of watchlist IDs
$movie_id = (int)$_POST['movie_id'];
$user_id = $_SESSION['user_id'];

// Track success and skip counts for user feedback
$added_count = 0;
$already_exists_count = 0;

// Process each selected watchlist
foreach ($watchlist_ids as $watchlist_id) {
    $watchlist_id = (int) $watchlist_id;

    // Verify that the watchlist belongs to the current user
    // This prevents users from adding movies to other users' watchlists
    $check_sql = "SELECT * FROM watchlists WHERE watchlist_id = $watchlist_id AND user_id = $user_id";
    $check_result = myQuery($check_sql);

    if (mysqli_num_rows($check_result) == 0) {
        // Skip watchlists that don't belong to user
        continue;
    }

    // Check if movie is already in this watchlist
    // Prevents duplicate entries in the same watchlist
    $check_movie_sql = "SELECT * FROM watchlist_movies WHERE watchlist_id = $watchlist_id AND movie_id = $movie_id";
    $check_movie_result = myQuery($check_movie_sql);

    if (mysqli_num_rows($check_movie_result) > 0) {
        // Movie already exists in this watchlist, count it but don't add again
        $already_exists_count++;
        continue;
    }

    // Add movie to watchlist
    // date_added will be set automatically via DEFAULT CURRENT_TIMESTAMP
    $insert_sql = "INSERT INTO watchlist_movies (watchlist_id, movie_id) 
                   VALUES ($watchlist_id, $movie_id)";
    if (myQuery($insert_sql)) {
        $added_count++;
    }
}

// Provide user feedback based on operation results
if ($added_count > 0) {
    // At least one watchlist was updated successfully
    $message = "Movie added to $added_count watchlist(s)";
    if ($already_exists_count > 0) {
        $message .= " ($already_exists_count already had it)";
    }
    header("Location: ../movies/details.php?id=$movie_id&success=" . urlencode($message));
} elseif ($already_exists_count > 0) {
    // All selected watchlists already contained the movie
    header("Location: ../movies/details.php?id=$movie_id&error=Movie already in all selected watchlists");
} else {
    // No watchlists were selected or all were invalid
    header("Location: ../movies/details.php?id=$movie_id&error=No watchlists were selected");
}
exit();
?>