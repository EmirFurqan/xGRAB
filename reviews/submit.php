<?php
/**
 * Review Submission Handler
 * Processes movie review submissions and updates.
 * Handles both new reviews and edits to existing reviews (within 24 hours).
 * Automatically recalculates movie average rating after submission.
 * Supports both AJAX and standard form submissions.
 */

session_start();
require("../connect.php");

// Detect if request is AJAX
$is_ajax = !empty($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) == 'xmlhttprequest';

// Helper function for responses
function sendResponse($success, $message, $movie_id, $is_ajax)
{
    if ($is_ajax) {
        header('Content-Type: application/json');
        echo json_encode(['success' => $success, 'message' => $message]);
        exit();
    } else {
        $param = $success ? 'success' : 'error';
        header("Location: ../movies/details.php?id=$movie_id&$param=" . urlencode($message));
        exit();
    }
}

// Require user to be logged in to submit reviews
if (!isset($_SESSION['user_id'])) {
    if ($is_ajax) {
        header('Content-Type: application/json');
        echo json_encode(['success' => false, 'message' => 'Please log in to submit a review']);
        exit();
    }
    header("Location: ../login.php");
    exit();
}

// Validate required form fields are present
if (!isset($_POST['movie_id']) || !isset($_POST['rating_value']) || !isset($_POST['review_text'])) {
    if ($is_ajax) {
        header('Content-Type: application/json');
        echo json_encode(['success' => false, 'message' => 'Missing required fields']);
        exit();
    }
    header("Location: ../movies/browse.php");
    exit();
}

// Extract and sanitize form data
$movie_id = (int) $_POST['movie_id'];
$user_id = $_SESSION['user_id'];
$rating_value = (float) $_POST['rating_value'];
$review_text = escapeString($_POST['review_text']);
$is_spoiler = isset($_POST['is_spoiler']) ? 1 : 0;

// Validate rating is within acceptable range (1-10)
if ($rating_value < 1 || $rating_value > 10) {
    sendResponse(false, 'Invalid rating', $movie_id, $is_ajax);
}

// Validate review text length
// Check original text before escaping to get accurate character count
$original_review_text = $_POST['review_text'];
if (strlen($original_review_text) < 2 || strlen($original_review_text) > 1000) {
    sendResponse(false, 'Review must be between 2 and 1000 characters', $movie_id, $is_ajax);
}

// Check if user has already reviewed this movie
$check_sql = "SELECT * FROM reviews WHERE movie_id = $movie_id AND user_id = $user_id";
$check_result = myQuery($check_sql);

if (mysqli_num_rows($check_result) > 0) {
    // Update existing review
    $existing_review = mysqli_fetch_assoc($check_result);
    $review_id = $existing_review['review_id'];

    // Calculate time difference since review was created
    // Only allow edits within 24 hours of original posting
    $created_time = strtotime($existing_review['created_at']);
    $current_time = time();
    $hours_diff = ($current_time - $created_time) / 3600;

    if ($hours_diff > 24) {
        sendResponse(false, 'You can only edit reviews within 24 hours of posting', $movie_id, $is_ajax);
    }

    // Update existing review with new text, rating, and spoiler status
    $update_sql = "UPDATE reviews SET 
                   review_text = '$review_text',
                   rating_value = $rating_value,
                   is_spoiler = $is_spoiler,
                   updated_at = NOW()
                   WHERE review_id = $review_id";

    // Use getConnection() for error handling
    $conn = getConnection();
    if (!mysqli_query($conn, $update_sql)) {
        $error_msg = mysqli_error($conn);
        mysqli_close($conn);
        sendResponse(false, 'Failed to update review: ' . $error_msg, $movie_id, $is_ajax);
    }
    mysqli_close($conn);

    $success_message = 'Review updated successfully';
} else {
    // Insert new review record
    $insert_sql = "INSERT INTO reviews (movie_id, user_id, review_text, rating_value, is_spoiler) 
                   VALUES ($movie_id, $user_id, '$review_text', $rating_value, $is_spoiler)";

    // Use getConnection() for error handling
    $conn = getConnection();
    if (!mysqli_query($conn, $insert_sql)) {
        $error_msg = mysqli_error($conn);
        mysqli_close($conn);
        sendResponse(false, 'Failed to save review: ' . $error_msg, $movie_id, $is_ajax);
    }
    mysqli_close($conn);

    $success_message = 'Review submitted successfully';
}

// Recalculate xGrab rating (user-generated ratings)
// Uses subqueries to calculate average and count from all reviews
// Note: average_rating and total_ratings are reserved for API (TMDB) ratings and should not be updated here
// This ensures xGrab ratings stay accurate after each review submission
$calc_sql = "UPDATE movies SET 
             xgrab_average_rating = COALESCE((SELECT AVG(rating_value) FROM reviews WHERE movie_id = $movie_id), 0),
             xgrab_total_ratings = (SELECT COUNT(*) FROM reviews WHERE movie_id = $movie_id)
             WHERE movie_id = $movie_id";
$conn = getConnection();
mysqli_query($conn, $calc_sql);
mysqli_close($conn);

sendResponse(true, $success_message, $movie_id, $is_ajax);
?>