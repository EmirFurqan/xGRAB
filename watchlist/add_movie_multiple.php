<?php
session_start();
require("../connect.php");

if (!isset($_SESSION['user_id'])) {
    header("Location: ../login.php");
    exit();
}

if (!isset($_POST['watchlist_ids']) || !isset($_POST['movie_id'])) {
    header("Location: ../movies/browse.php");
    exit();
}

$watchlist_ids = $_POST['watchlist_ids']; // Array of watchlist IDs
$movie_id = (int) $_POST['movie_id'];
$user_id = $_SESSION['user_id'];

$added_count = 0;
$already_exists_count = 0;

foreach ($watchlist_ids as $watchlist_id) {
    $watchlist_id = (int) $watchlist_id;

    // Verify watchlist ownership
    $check_sql = "SELECT * FROM watchlists WHERE watchlist_id = $watchlist_id AND user_id = $user_id";
    $check_result = myQuery($check_sql);

    if (mysqli_num_rows($check_result) == 0) {
        continue; // Skip if not owned by user
    }

    // Check if movie already in watchlist
    $check_movie_sql = "SELECT * FROM watchlist_movies WHERE watchlist_id = $watchlist_id AND movie_id = $movie_id";
    $check_movie_result = myQuery($check_movie_sql);

    if (mysqli_num_rows($check_movie_result) > 0) {
        $already_exists_count++;
        continue; // Skip if already exists
    }

    // Add movie to watchlist
    $insert_sql = "INSERT INTO watchlist_movies (watchlist_id, movie_id) 
                   VALUES ($watchlist_id, $movie_id)";
    if (myQuery($insert_sql)) {
        $added_count++;
    }
}

if ($added_count > 0) {
    $message = "Movie added to $added_count watchlist(s)";
    if ($already_exists_count > 0) {
        $message .= " ($already_exists_count already had it)";
    }
    header("Location: ../movies/details.php?id=$movie_id&success=" . urlencode($message));
} elseif ($already_exists_count > 0) {
    header("Location: ../movies/details.php?id=$movie_id&error=Movie already in all selected watchlists");
} else {
    header("Location: ../movies/details.php?id=$movie_id&error=No watchlists were selected");
}
exit();
?>