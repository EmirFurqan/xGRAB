<?php
/**
 * Add Movie to Watchlist Handler
 * Adds a movie to a user's watchlist.
 * Verifies watchlist ownership and prevents duplicate entries.
 */

session_start();
require("../connect.php");

// Require user to be logged in to add movies to watchlists
if (!isset($_SESSION['user_id'])) {
    header("Location: ../login.php");
    exit();
}

// Validate required parameters are present
if (!isset($_POST['watchlist_id']) || !isset($_POST['movie_id'])) {
    header("Location: ../movies/browse.php");
    exit();
}

$watchlist_id = (int)$_POST['watchlist_id'];
$movie_id = (int)$_POST['movie_id'];
$user_id = $_SESSION['user_id'];

// Verify that the watchlist belongs to the current user
// This prevents users from adding movies to other users' watchlists
$check_sql = "SELECT * FROM watchlists WHERE watchlist_id = $watchlist_id AND user_id = $user_id";
$check_result = myQuery($check_sql);

if (mysqli_num_rows($check_result) == 0) {
    header("Location: ../movies/details.php?id=$movie_id&error=Watchlist not found or access denied");
    exit();
}

// Check if movie is already in this watchlist
// Prevents duplicate entries in the same watchlist
$check_movie_sql = "SELECT * FROM watchlist_movies WHERE watchlist_id = $watchlist_id AND movie_id = $movie_id";
$check_movie_result = myQuery($check_movie_sql);

if (mysqli_num_rows($check_movie_result) > 0) {
    header("Location: ../movies/details.php?id=$movie_id&error=Movie already in this watchlist");
    exit();
}

// Add movie to watchlist
// date_added will be set automatically via DEFAULT CURRENT_TIMESTAMP
$insert_sql = "INSERT INTO watchlist_movies (watchlist_id, movie_id) 
               VALUES ($watchlist_id, $movie_id)";
if (myQuery($insert_sql)) {
    header("Location: ../movies/details.php?id=$movie_id&success=Movie added to watchlist");
} else {
    header("Location: ../movies/details.php?id=$movie_id&error=Failed to add movie to watchlist");
}
exit();
?>

