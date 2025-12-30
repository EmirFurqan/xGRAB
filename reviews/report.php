<?php
/**
 * Review Report Handler
 * Allows users to report inappropriate reviews.
 * Automatically flags reviews after 3 reports for admin moderation.
 */

session_start();
require("../connect.php");

// Require user to be logged in to report reviews
if (!isset($_SESSION['user_id'])) {
    header("Location: ../login.php");
    exit();
}

// Validate review_id parameter is present
if (!isset($_POST['review_id'])) {
    header("Location: ../movies/browse.php");
    exit();
}

$review_id = (int)$_POST['review_id'];
$user_id = $_SESSION['user_id'];
// Get report reason, default to 'Inappropriate content' if not provided
$reason = isset($_POST['reason']) ? escapeString($_POST['reason']) : 'Inappropriate content';

// First, verify the review exists and get its author
// Prevent users from reporting their own reviews
$review_sql = "SELECT movie_id, user_id FROM reviews WHERE review_id = $review_id";
$review_result = myQuery($review_sql);

if (mysqli_num_rows($review_result) == 0) {
    // Review doesn't exist, redirect to browse page
    header("Location: ../movies/browse.php");
    exit();
}

$review_data = mysqli_fetch_assoc($review_result);
$movie_id = $review_data['movie_id'];
$review_author_id = $review_data['user_id'];

// Prevent users from reporting their own reviews
if ($user_id == $review_author_id) {
    header("Location: ../movies/details.php?id=$movie_id&error=You cannot report your own review");
    exit();
}

// Check if user has already reported this review
// Prevents duplicate reports from the same user
$check_sql = "SELECT * FROM review_reports WHERE review_id = $review_id AND user_id = $user_id";
$check_result = myQuery($check_sql);

if (mysqli_num_rows($check_result) > 0) {
    // User has already reported this review
    header("Location: ../movies/details.php?id=$movie_id&error=You have already reported this review");
    exit();
}

// Insert new report record into review_reports table
$insert_sql = "INSERT INTO review_reports (review_id, user_id, reason) 
               VALUES ($review_id, $user_id, '$reason')";
myQuery($insert_sql);

// Increment report count on the review itself
// This counter is used to determine when to auto-flag the review
$update_sql = "UPDATE reviews SET 
               report_count = report_count + 1
               WHERE review_id = $review_id";
myQuery($update_sql);

// Check if review has reached the threshold for automatic flagging
// Reviews with 3 or more reports are automatically flagged for admin review
$check_flag_sql = "SELECT report_count FROM reviews WHERE review_id = $review_id";
$flag_result = myQuery($check_flag_sql);
$flag_data = mysqli_fetch_assoc($flag_result);

if ($flag_data['report_count'] >= 3) {
    // Automatically flag review for admin moderation
    // Flagged reviews are hidden from public view until reviewed
    $flag_sql = "UPDATE reviews SET is_flagged = TRUE WHERE review_id = $review_id";
    myQuery($flag_sql);
}

// Redirect back to movie details page
header("Location: ../movies/details.php?id=$movie_id&success=Review reported successfully");
exit();
?>

