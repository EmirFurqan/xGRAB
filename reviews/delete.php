<?php
session_start();
require("../connect.php");

if (!isset($_SESSION['user_id'])) {
    header("Location: ../login.php");
    exit();
}

if (!isset($_GET['review_id']) || !isset($_GET['movie_id'])) {
    header("Location: ../movies/browse.php");
    exit();
}

$review_id = (int)$_GET['review_id'];
$movie_id = (int)$_GET['movie_id'];
$user_id = $_SESSION['user_id'];

// Verify review belongs to user
$check_sql = "SELECT * FROM reviews WHERE review_id = $review_id AND user_id = $user_id";
$check_result = myQuery($check_sql);

if (mysqli_num_rows($check_result) == 0) {
    header("Location: ../movies/details.php?id=$movie_id&error=Review not found or access denied");
    exit();
}

// Delete review
$delete_sql = "DELETE FROM reviews WHERE review_id = $review_id";
myQuery($delete_sql);

// Recalculate movie rating
$calc_sql = "UPDATE movies SET 
             average_rating = COALESCE((SELECT AVG(rating_value) FROM reviews WHERE movie_id = $movie_id), 0),
             total_ratings = (SELECT COUNT(*) FROM reviews WHERE movie_id = $movie_id)
             WHERE movie_id = $movie_id";
myQuery($calc_sql);

header("Location: ../movies/details.php?id=$movie_id&success=Review deleted successfully");
exit();
?>

