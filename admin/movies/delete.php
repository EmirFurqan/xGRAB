<?php
session_start();
require("../../connect.php");

if (!isset($_SESSION['user_id']) || !$_SESSION['is_admin']) {
    die("Access Denied");
}

if (!isset($_GET['id'])) {
    header("Location: ../dashboard.php");
    exit();
}

$movie_id = (int)$_GET['id'];

// Get movie info for logging
$movie_sql = "SELECT title FROM movies WHERE movie_id = $movie_id";
$movie_result = myQuery($movie_sql);
if (mysqli_num_rows($movie_result) == 0) {
    header("Location: ../dashboard.php");
    exit();
}
$movie = mysqli_fetch_assoc($movie_result);
$movie_title = $movie['title'];

// Delete movie (CASCADE will handle related records)
$delete_sql = "DELETE FROM movies WHERE movie_id = $movie_id";
if (myQuery($delete_sql)) {
    // Log admin action
    $admin_id = $_SESSION['user_id'];
    $log_sql = "INSERT INTO admin_logs (admin_id, action_type, target_type, target_id, description) 
                VALUES ($admin_id, 'movie_delete', 'movie', $movie_id, 'Deleted movie: $movie_title')";
    myQuery($log_sql);
    
    header("Location: ../dashboard.php?success=" . urlencode("Movie deleted successfully"));
} else {
    header("Location: ../dashboard.php?error=" . urlencode("Failed to delete movie"));
}
exit();
?>

