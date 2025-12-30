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
$user_id = $_SESSION['user_id'];
$reason = isset($_POST['reason']) ? escapeString($_POST['reason']) : 'Inappropriate content';

// Check if user already reported this review
$check_sql = "SELECT * FROM review_reports WHERE review_id = $review_id AND user_id = $user_id";
$check_result = myQuery($check_sql);

if (mysqli_num_rows($check_result) > 0) {
    // Already reported
    $movie_sql = "SELECT movie_id FROM reviews WHERE review_id = $review_id";
    $movie_result = myQuery($movie_sql);
    if (mysqli_num_rows($movie_result) > 0) {
        $movie = mysqli_fetch_assoc($movie_result);
        header("Location: ../movies/details.php?id=" . $movie['movie_id'] . "&error=You have already reported this review");
    } else {
        header("Location: ../movies/browse.php");
    }
    exit();
}

// Insert report
$insert_sql = "INSERT INTO review_reports (review_id, user_id, reason) 
               VALUES ($review_id, $user_id, '$reason')";
myQuery($insert_sql);

// Update review report count
$update_sql = "UPDATE reviews SET 
               report_count = report_count + 1
               WHERE review_id = $review_id";
myQuery($update_sql);

// Check if report_count >= 3, then flag
$check_flag_sql = "SELECT report_count FROM reviews WHERE review_id = $review_id";
$flag_result = myQuery($check_flag_sql);
$flag_data = mysqli_fetch_assoc($flag_result);

if ($flag_data['report_count'] >= 3) {
    $flag_sql = "UPDATE reviews SET is_flagged = TRUE WHERE review_id = $review_id";
    myQuery($flag_sql);
}

// Get movie_id to redirect back
$movie_sql = "SELECT movie_id FROM reviews WHERE review_id = $review_id";
$movie_result = myQuery($movie_sql);
if (mysqli_num_rows($movie_result) > 0) {
    $movie = mysqli_fetch_assoc($movie_result);
    header("Location: ../movies/details.php?id=" . $movie['movie_id'] . "&success=Review reported successfully");
} else {
    header("Location: ../movies/browse.php");
}
exit();
?>

