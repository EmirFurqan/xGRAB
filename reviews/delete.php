<?php
/**
 * Review Deletion Handler
 * Allows users to delete their own reviews.
 * Automatically recalculates movie average rating after deletion.
 */

session_start();
require("../connect.php");

// Require user to be logged in to delete reviews
if (!isset($_SESSION['user_id'])) {
    header("Location: ../login.php");
    exit();
}

// Validate required parameters are present
if (!isset($_GET['review_id']) || !isset($_GET['movie_id'])) {
    header("Location: ../movies/browse.php");
    exit();
}

$review_id = (int)$_GET['review_id'];
$movie_id = (int)$_GET['movie_id'];
$user_id = $_SESSION['user_id'];

// Verify that the review belongs to the current user
// This prevents users from deleting other users' reviews
$check_sql = "SELECT * FROM reviews WHERE review_id = $review_id AND user_id = $user_id";
$check_result = myQuery($check_sql);

if (mysqli_num_rows($check_result) == 0) {
    header("Location: ../movies/details.php?id=$movie_id&error=Review not found or access denied");
    exit();
}

// Delete the review from database
$delete_sql = "DELETE FROM reviews WHERE review_id = $review_id";
myQuery($delete_sql);

// Recalculate xGrab rating (user-generated ratings)
// COALESCE handles case where no reviews remain (sets to 0 instead of NULL)
// Note: average_rating and total_ratings are reserved for API (TMDB) ratings and should not be updated here
// This ensures xGrab ratings stay accurate after review deletion
$calc_sql = "UPDATE movies SET 
             xgrab_average_rating = COALESCE((SELECT AVG(rating_value) FROM reviews WHERE movie_id = $movie_id), 0),
             xgrab_total_ratings = (SELECT COUNT(*) FROM reviews WHERE movie_id = $movie_id)
             WHERE movie_id = $movie_id";
myQuery($calc_sql);

header("Location: ../movies/details.php?id=$movie_id&success=Review deleted successfully");
exit();
?>

