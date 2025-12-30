<?php
session_start();
require("../connect.php");

if (!isset($_SESSION['user_id'])) {
    header("Location: ../login.php");
    exit();
}

if (!isset($_POST['movie_id'])) {
    header("Location: ../movies/browse.php?error=Invalid request");
    exit();
}

$user_id = $_SESSION['user_id'];
$movie_id = (int)$_POST['movie_id'];

// Verify movie exists
$check_movie_sql = "SELECT movie_id FROM movies WHERE movie_id = $movie_id";
$check_movie_result = myQuery($check_movie_sql);
if (mysqli_num_rows($check_movie_result) == 0) {
    header("Location: ../movies/browse.php?error=Movie not found");
    exit();
}

// Determine redirect URL
$redirect_url = "../movies/details.php?id=$movie_id";
if (isset($_POST['redirect_url'])) {
    $redirect_url = escapeString($_POST['redirect_url']);
}

// Check if already watched
$check_watched_sql = "SELECT * FROM user_watched_movies 
                      WHERE user_id = $user_id AND movie_id = $movie_id";
$check_watched_result = myQuery($check_watched_sql);

if (mysqli_num_rows($check_watched_result) > 0) {
    // Remove from watched
    $delete_sql = "DELETE FROM user_watched_movies 
                   WHERE user_id = $user_id AND movie_id = $movie_id";
    if (myQuery($delete_sql)) {
        header("Location: $redirect_url?success=Removed from watched");
    } else {
        header("Location: $redirect_url?error=Failed to remove from watched");
    }
} else {
    // Add to watched
    $insert_sql = "INSERT INTO user_watched_movies (user_id, movie_id) 
                   VALUES ($user_id, $movie_id)";
    if (myQuery($insert_sql)) {
        header("Location: $redirect_url?success=Marked as watched");
    } else {
        header("Location: $redirect_url?error=Failed to mark as watched");
    }
}
exit();
?>

