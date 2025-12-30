<?php
session_start();
require("../connect.php");

if (!isset($_SESSION['user_id'])) {
    header("Location: ../login.php");
    exit();
}

if (!isset($_POST['movie_id']) || !isset($_POST['rating_value']) || !isset($_POST['review_text'])) {
    header("Location: ../movies/browse.php");
    exit();
}

$movie_id = (int)$_POST['movie_id'];
$user_id = $_SESSION['user_id'];
$rating_value = (float)$_POST['rating_value'];
$review_text = escapeString($_POST['review_text']);
$is_spoiler = isset($_POST['is_spoiler']) ? 1 : 0;

// Validate rating
if ($rating_value < 1 || $rating_value > 10) {
    header("Location: ../movies/details.php?id=$movie_id&error=Invalid rating");
    exit();
}

// Validate review text length (check original text before escaping)
$original_review_text = $_POST['review_text'];
if (strlen($original_review_text) < 50 || strlen($original_review_text) > 1000) {
    header("Location: ../movies/details.php?id=$movie_id&error=Review must be between 50 and 1000 characters");
    exit();
}

// Check if review already exists
$check_sql = "SELECT * FROM reviews WHERE movie_id = $movie_id AND user_id = $user_id";
$check_result = myQuery($check_sql);

if (mysqli_num_rows($check_result) > 0) {
    // Update existing review
    $existing_review = mysqli_fetch_assoc($check_result);
    $review_id = $existing_review['review_id'];
    
    // Check if within 24 hours for editing
    $created_time = strtotime($existing_review['created_at']);
    $current_time = time();
    $hours_diff = ($current_time - $created_time) / 3600;
    
    if ($hours_diff > 24) {
        header("Location: ../movies/details.php?id=$movie_id&error=You can only edit reviews within 24 hours of posting");
        exit();
    }
    
    $update_sql = "UPDATE reviews SET 
                   review_text = '$review_text',
                   rating_value = $rating_value,
                   is_spoiler = $is_spoiler,
                   updated_at = NOW()
                   WHERE review_id = $review_id";
    
    $conn = getConnection();
    if (!mysqli_query($conn, $update_sql)) {
        $error_msg = mysqli_error($conn);
        mysqli_close($conn);
        header("Location: ../movies/details.php?id=$movie_id&error=Failed to update review: " . urlencode($error_msg));
        exit();
    }
    mysqli_close($conn);
} else {
    // Insert new review
    $insert_sql = "INSERT INTO reviews (movie_id, user_id, review_text, rating_value, is_spoiler) 
                   VALUES ($movie_id, $user_id, '$review_text', $rating_value, $is_spoiler)";
    
    $conn = getConnection();
    if (!mysqli_query($conn, $insert_sql)) {
        $error_msg = mysqli_error($conn);
        mysqli_close($conn);
        header("Location: ../movies/details.php?id=$movie_id&error=Failed to save review: " . urlencode($error_msg));
        exit();
    }
    mysqli_close($conn);
}

// Recalculate movie rating
$calc_sql = "UPDATE movies SET 
             average_rating = (SELECT AVG(rating_value) FROM reviews WHERE movie_id = $movie_id),
             total_ratings = (SELECT COUNT(*) FROM reviews WHERE movie_id = $movie_id)
             WHERE movie_id = $movie_id";
$conn = getConnection();
mysqli_query($conn, $calc_sql);
mysqli_close($conn);

header("Location: ../movies/details.php?id=$movie_id&success=Review submitted successfully");
exit();
?>

