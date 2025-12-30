<?php
/**
 * Delete Movie Handler (Admin)
 * Allows administrators to delete movies from the database.
 * Database CASCADE constraints automatically delete related records.
 * Logs deletion action for audit trail.
 */

session_start();
require("../../connect.php");

// Verify user has admin privileges
if (!isset($_SESSION['user_id']) || !$_SESSION['is_admin']) {
    die("Access Denied");
}

// Validate movie ID parameter
if (!isset($_GET['id'])) {
    header("Location: ../dashboard.php");
    exit();
}

$movie_id = (int)$_GET['id'];

// Retrieve movie title before deletion for logging purposes
$movie_sql = "SELECT title FROM movies WHERE movie_id = $movie_id";
$movie_result = myQuery($movie_sql);
if (mysqli_num_rows($movie_result) == 0) {
    header("Location: ../dashboard.php");
    exit();
}
$movie = mysqli_fetch_assoc($movie_result);
$movie_title = $movie['title'];

// Delete movie record from database
// Foreign key CASCADE constraints will automatically delete:
// - Movie genres (movie_genres.movie_id)
// - Movie cast (movie_cast.movie_id)
// - Movie crew (movie_crew.movie_id)
// - Reviews (reviews.movie_id)
// - Watchlist entries (watchlist_movies.movie_id)
// - Trailers (movie_trailers.movie_id)
// - Favorites (favorites.entity_id where entity_type='movie')
$delete_sql = "DELETE FROM movies WHERE movie_id = $movie_id";
if (myQuery($delete_sql)) {
    // Log admin action for audit trail
    // Logs even though movie is deleted (target_id preserved for reference)
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

