<?php
/**
 * Watched Movie Toggle Handler
 * Adds or removes movies from user's watched list.
 * Tracks which movies a user has watched independently of watchlists.
 */

session_start();
// Include config if not already loaded
if (!defined('BASE_URL') && file_exists(__DIR__ . '/../includes/config.php')) {
    require_once __DIR__ . '/../includes/config.php';
}
require("../connect.php");

// Require user to be logged in to track watched movies
// Require user to be logged in to track watched movies
if (!isset($_SESSION['user_id'])) {
    // Check if it's an AJAX request
    if (!empty($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) == 'xmlhttprequest') {
        header('Content-Type: application/json');
        echo json_encode([
            'success' => false,
            'message' => 'You must be logged in to mark movies as watched.'
        ]);
        exit();
    }

    $redirect_url = defined('BASE_URL') ? BASE_URL . 'login.php' : '../login.php';
    header("Location: " . $redirect_url);
    exit();
}

// Validate movie_id parameter is present
if (!isset($_POST['movie_id'])) {
    $redirect_url = defined('BASE_URL') ? BASE_URL . 'movies/browse.php?error=Invalid request' : '../movies/browse.php?error=Invalid request';
    header("Location: " . $redirect_url);
    exit();
}

$user_id = $_SESSION['user_id'];
$movie_id = (int) $_POST['movie_id'];

// Verify the movie exists in database before toggling watched status
$check_movie_sql = "SELECT movie_id FROM movies WHERE movie_id = $movie_id";
$check_movie_result = myQuery($check_movie_sql);
if (mysqli_num_rows($check_movie_result) == 0) {
    $redirect_url = defined('BASE_URL') ? BASE_URL . 'movies/browse.php?error=Movie not found' : '../movies/browse.php?error=Movie not found';
    header("Location: " . $redirect_url);
    exit();
}

// Determine redirect URL after operation
// Defaults to movie details page, but can be overridden
$redirect_url = "../movies/details.php?id=$movie_id";
if (isset($_POST['redirect_url'])) {
    $redirect_url = escapeString($_POST['redirect_url']);
}

// Check if movie is already in user's watched list
$check_watched_sql = "SELECT * FROM user_watched_movies 
                      WHERE user_id = $user_id AND movie_id = $movie_id";
$check_watched_result = myQuery($check_watched_sql);

// Detect if request is AJAX (for dynamic UI updates without page refresh)
$is_ajax = !empty($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) == 'xmlhttprequest';

if (mysqli_num_rows($check_watched_result) > 0) {
    // Remove movie from watched list (user unwatched it)
    $delete_sql = "DELETE FROM user_watched_movies 
                   WHERE user_id = $user_id AND movie_id = $movie_id";
    if (myQuery($delete_sql)) {
        if ($is_ajax) {
            header('Content-Type: application/json');
            echo json_encode(['success' => true, 'action' => 'removed', 'message' => 'Removed from watched']);
        } else {
            header("Location: $redirect_url?success=Removed from watched");
        }
    } else {
        if ($is_ajax) {
            header('Content-Type: application/json');
            echo json_encode(['success' => false, 'message' => 'Failed to remove from watched']);
        } else {
            header("Location: $redirect_url?error=Failed to remove from watched");
        }
    }
} else {
    // Add movie to watched list
    // Uses current timestamp as watched_date (via DEFAULT CURRENT_TIMESTAMP)
    $insert_sql = "INSERT INTO user_watched_movies (user_id, movie_id) 
                   VALUES ($user_id, $movie_id)";
    if (myQuery($insert_sql)) {
        if ($is_ajax) {
            header('Content-Type: application/json');
            echo json_encode(['success' => true, 'action' => 'added', 'message' => 'Marked as watched']);
        } else {
            header("Location: $redirect_url?success=Marked as watched");
        }
    } else {
        if ($is_ajax) {
            header('Content-Type: application/json');
            echo json_encode(['success' => false, 'message' => 'Failed to mark as watched']);
        } else {
            header("Location: $redirect_url?error=Failed to mark as watched");
        }
    }
}
exit();
?>