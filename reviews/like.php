<?php
session_start();
require("../connect.php");

if (!isset($_SESSION['user_id'])) {
    header("Location: ../login.php");
    exit();
}

if (!isset($_POST['review_id'])) {
    header("Location: ../movies/browse.php");
    exit();
}

$review_id = (int)$_POST['review_id'];

// Increment like count
$like_sql = "UPDATE reviews SET like_count = like_count + 1 WHERE review_id = $review_id";
myQuery($like_sql);

// Get movie_id to redirect back
$movie_sql = "SELECT movie_id FROM reviews WHERE review_id = $review_id";
$movie_result = myQuery($movie_sql);
if (mysqli_num_rows($movie_result) > 0) {
    $movie = mysqli_fetch_assoc($movie_result);
    header("Location: ../movies/details.php?id=" . $movie['movie_id']);
} else {
    header("Location: ../movies/browse.php");
}
exit();
?>

