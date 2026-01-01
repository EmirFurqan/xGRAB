<?php
/**
 * Review Like Handler
 * Handles like/unlike toggle functionality for reviews.
 * Prevents duplicate likes and self-likes.
 * Updates like_count in reviews table and maintains review_likes junction table.
 */

session_start();
// Include config if not already loaded
if (!defined('BASE_URL') && file_exists(__DIR__ . '/../includes/config.php')) {
    require_once __DIR__ . '/../includes/config.php';
}
require("../connect.php");

// Require user to be logged in to like reviews
if (!isset($_SESSION['user_id'])) {
    $redirect_url = defined('BASE_URL') ? BASE_URL . 'login.php' : '../login.php';
    header("Location: " . $redirect_url);
    exit();
}

// Validate review_id parameter is present
if (!isset($_POST['review_id'])) {
    $redirect_url = defined('BASE_URL') ? BASE_URL . 'movies/browse.php' : '../movies/browse.php';
    header("Location: " . $redirect_url);
    exit();
}

$review_id = (int) $_POST['review_id'];
$user_id = $_SESSION['user_id'];

// First, verify the review exists and get its author
$review_sql = "SELECT movie_id, user_id FROM reviews WHERE review_id = $review_id";
$review_result = myQuery($review_sql);

if (mysqli_num_rows($review_result) == 0) {
    // Review doesn't exist, redirect to browse page
    $redirect_url = defined('BASE_URL') ? BASE_URL . 'movies/browse.php' : '../movies/browse.php';
    header("Location: " . $redirect_url);
    exit();
}

$review_data = mysqli_fetch_assoc($review_result);
$movie_id = $review_data['movie_id'];
$review_author_id = $review_data['user_id'];

// Prevent users from liking their own reviews
if ($user_id == $review_author_id) {
    $redirect_url = defined('BASE_URL') ? BASE_URL . "movies/details.php?id=$movie_id&error=You cannot like your own review" : "../movies/details.php?id=$movie_id&error=You cannot like your own review";
    header("Location: " . $redirect_url);
    exit();
}

// Check if user has already liked this review
$check_like_sql = "SELECT like_id FROM review_likes WHERE review_id = $review_id AND user_id = $user_id";
$check_like_result = myQuery($check_like_sql);
$already_liked = mysqli_num_rows($check_like_result) > 0;

if ($already_liked) {
    // User has already liked, so unlike it
    // Remove the like record from review_likes table
    $delete_like_sql = "DELETE FROM review_likes WHERE review_id = $review_id AND user_id = $user_id";
    myQuery($delete_like_sql);

    // Decrement like_count in reviews table
    // Use MAX to prevent negative counts
    $update_sql = "UPDATE reviews SET like_count = GREATEST(0, like_count - 1) WHERE review_id = $review_id";
    myQuery($update_sql);

    $action = "unliked";
} else {
    // User hasn't liked yet, so like it
    // Insert like record into review_likes table
    $insert_like_sql = "INSERT INTO review_likes (review_id, user_id) VALUES ($review_id, $user_id)";
    myQuery($insert_like_sql);

    // Increment like_count in reviews table
    $update_sql = "UPDATE reviews SET like_count = like_count + 1 WHERE review_id = $review_id";
    myQuery($update_sql);

    $action = "liked";
}

// Check if it's an AJAX request
if (!empty($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) == 'xmlhttprequest') {
    // Get updated like count
    $count_sql = "SELECT like_count FROM reviews WHERE review_id = $review_id";
    $count_result = myQuery($count_sql);
    $new_count = mysqli_fetch_assoc($count_result)['like_count'];

    header('Content-Type: application/json');
    echo json_encode([
        'success' => true,
        'action' => $action,
        'like_count' => $new_count,
        'message' => $action == 'liked' ? 'Review marked as helpful' : 'Review removed from helpful'
    ]);
    exit();
}

// Redirect back to movie details page for non-AJAX requests
$redirect_url = defined('BASE_URL') ? BASE_URL . "movies/details.php?id=$movie_id" : "../movies/details.php?id=$movie_id";
header("Location: " . $redirect_url);
exit();
?>